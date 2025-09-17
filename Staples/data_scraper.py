import time
import json
import random
import requests
import pandas as pd
from tqdm import tqdm
from pathlib import Path
from threading import Lock, Event
from requests.exceptions import HTTPError, RequestException
from concurrent.futures import ThreadPoolExecutor, as_completed

# -------------------------
# Config
# -------------------------

# Path to your Playwright-style storage state JSON
COOKIE_FILE = "staples_storage_state.json"

# Output Excel
OUTPUT_EXCEL_PATH = "staples_product_data.xlsx"

# API base
BASE_URL = "https://www.staplesadvantage.com/ele-lpd/api/sba-sku/"

# Realistic browser headers
HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
        "(KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36"
    ),
    "Accept": "application/json, text/plain, */*",
    "Accept-Language": "en-US,en;q=0.9",
    "Referer": "https://www.staplesadvantage.com/",
    "Connection": "keep-alive",
}

# -------------------------
# Cookie utilities
# -------------------------

def load_cookies_from_storage_state(file_path, domains=None, include_session=True):
    """
    Read Playwright-style storage state JSON and return a {name: value} dict
    suitable for requests.get(..., cookies=...).

    - domains: list of domain substrings to keep (e.g., ["staplesadvantage.com"])
    - include_session: keep session cookies (expires == -1)
    """
    p = Path(file_path)
    if not p.exists():
        raise FileNotFoundError(f"Cookie file not found: {p.resolve()}")

    data = json.loads(p.read_text(encoding="utf-8"))

    now = time.time()
    cookies_list = data.get("cookies", [])
    cookie_dict = {}

    for c in cookies_list:
        name = c.get("name")
        value = c.get("value")
        domain = (c.get("domain") or "").lstrip(".")
        expires = c.get("expires", -1)

        # Domain filter
        if domains:
            if not any(d in domain for d in domains):
                continue

        # Expiration filter
        if expires not in (None, ""):
            if expires == -1:
                if not include_session:
                    continue
            else:
                try:
                    if float(expires) <= now:
                        continue  # expired cookie
                except Exception:
                    pass  # keep if parse fails

        if name and value is not None:
            cookie_dict[name] = value

    return cookie_dict


def get_fresh_cookies():
    """Get fresh cookies by opening the homepage anonymously (fallback)."""
    s = requests.Session()
    resp = s.get("https://www.staplesadvantage.com/", headers=HEADERS, timeout=15)
    resp.raise_for_status()
    return s.cookies.get_dict()


# -------------------------
# Data extraction helpers
# -------------------------

def extract_product_info(items):
    if not items or not isinstance(items, list):
        return {}

    item = items[0]
    product = item.get("product", {})
    price = item.get("price", {})
    hierarchy = product.get("hierarchy", {})

    category = hierarchy.get("supercategory", {}).get("name")
    subcategory_1 = hierarchy.get("category", {}).get("name")
    subcategory_2 = hierarchy.get("class", {}).get("name")
    subcategory_3 = hierarchy.get("department", {}).get("name")

    # Build specifications map safely
    specs_src = product.get("description", {}).get("specification", [])
    specs = {}
    if isinstance(specs_src, list):
        for spec in specs_src:
            n = spec.get("name")
            v = spec.get("value")
            if n is not None:
                specs[n] = v

    final_price = None
    try:
        if price.get("item"):
            final_price = (
                price.get("item")[0]
                .get("data", {})
                .get("priceInfo", [{}])[0]
                .get("finalPrice")
            )
    except Exception:
        final_price = None

    web_price = None
    try:
        if price.get("item"):
            web_price = (
                price.get("item")[0]
                .get("data", {})
                .get("priceInfo", [{}])[0]
                .get("basePrice")
            )
    except Exception:
        web_price = None

    contract_flag = None
    try:
        if price.get("item"):
            contract_flag = (
                price.get("item")[0]
                .get("data", {})
                .get("priceInfo", [{}])[0]
                .get("deprecableFieldsAtPriceLevel")
                .get("onContract")
            )
    except Exception:
        contract_flag = None

    return {
        "sku": product.get("partNumber"),
        "description": product.get("name"),
        "manufacturer_name": product.get("manufacturerName"),
        "manual_out_of_stock": item.get("manualOutOfStock", False),
        "manufacturer_number": product.get("manufacturerPartNumber"),
        "supplier_shorthand_name": product.get("itemShortDescription"),
        "unit_of_measure": product.get("unitOfMeasureComposite", {}).get("unitOfMeasure"),
        "quantity_per_unit": product.get("unitOfMeasureComposite", {}).get("unitOfMeasureQty"),
        "specifications": specs,
        # "value": final_price,
        "web_price": web_price,
        "url": product.get("productURL"),
        "Contract Flag": contract_flag,
        "category": category,
        "sub_category_1": subcategory_1,
        "sub_category_2": subcategory_2,
        # "sub_category_3": subcategory_3,
    }


# -------------------------
# Global counters and locks
# -------------------------

processed_count = 0
count_lock = Lock()
refresh_event = Event()
cookies_lock = Lock()

# Shared cookies dictionary (initialized below)
shared_cookies = {}

# -------------------------
# Main fetch function (multithreaded)
# -------------------------

def fetch_product_data(item_number, retries=2):
    global processed_count, shared_cookies
 
    result = {"SKU": str(item_number), "Error": "Unknown error"}
    for attempt in range(retries + 1):
        try:
            local_session = requests.Session()
 
            # Thread-safe copy of shared cookies
            with cookies_lock:
                cookies_copy = shared_cookies.copy()
 
            # API URL
            api_url = f"{BASE_URL}product_{item_number}?pgIntlO=Y"
 
            # Request
            r = local_session.get(api_url, headers=HEADERS, cookies=cookies_copy, timeout=20)
 
            # Handle redirect w/ JSON 'path'
            if r.status_code in (301, 302):
                try:
                    redirect_path = r.json().get("path")
                    if redirect_path:
                        redirected_url = f"{BASE_URL}{redirect_path}"
                        r = local_session.get(redirected_url, headers=HEADERS, cookies=cookies_copy, timeout=20)
                except ValueError:
                    # Not JSON, proceed to raise_for_status below
                    pass
 
            r.raise_for_status()
 
            data = r.json()
            items = data.get("SBASkuState", {}).get("skuData", {}).get("items", [])
            parsed = extract_product_info(items)
 
            if not parsed.get("sku"):
                raise ValueError("Empty or missing SKU in response.")
 
            result = parsed
            break  # success
 
        except HTTPError as e:
            status = getattr(e.response, "status_code", None)
            # If unauthorized/forbidden, refresh cookies once and retry
            if attempt < retries and status in (401, 403):
                with cookies_lock:
                    try:
                        # Fallback refresh (anonymous)
                        shared_cookies = get_fresh_cookies()
                    except RequestException:
                        pass
                time.sleep(1 + attempt)
                continue
            else:
                result = {"SKU": str(item_number), "Error": f"HTTPError {status}: {str(e)}"}
                if attempt < retries:
                    time.sleep(2 + attempt)
                    continue
                break
 
        except RequestException as e:
            result = {"SKU": str(item_number), "Error": f"RequestException: {str(e)}"}
            if attempt < retries:
                # Jittered backoff
                time.sleep(1.5 + attempt + random.random())
                continue
            break
 
        except Exception as e:
            result = {"SKU": str(item_number), "Error": str(e)}
            if attempt < retries:
                time.sleep(2 + attempt)
                continue
            break
 
    # Progress + periodic sleep/refresh
    with count_lock:
        processed_count += 1
 
        if processed_count % 500 == 0 or processed_count == 1:
            print(f"\nProcessed {processed_count} items. Sleeping for 10 seconds...")
            print(result)
            time.sleep(10)

    return result

# -------------------------
# Public runner (module entry point)
# -------------------------

def get_product_data(sku_list):
    """
    Scrape product data for the given list of SKUs.

    Parameters
    ----------
    sku_list : Iterable[str|int]

    Returns
    -------
    (pandas.DataFrame, list[dict])
        DataFrame of results (also written to Excel) and a JSON-ready list of dicts.
    """
    global shared_cookies

    shared_cookies = load_cookies_from_storage_state(
        COOKIE_FILE, domains=["staplesadvantage.com"], include_session=True
    )
    if not shared_cookies:
        print("No cookies loaded from storage state; fetching fresh anonymous cookies...")
        shared_cookies = get_fresh_cookies()

    results = []
    with ThreadPoolExecutor(max_workers=5) as executor:
        futures = [executor.submit(fetch_product_data, sku) for sku in sku_list]
        for future in tqdm(as_completed(futures), total=len(futures), desc="Processing items"):
            results.append(future.result())

    output_df = pd.DataFrame(results)

    # file_path = "staples_product_data.xlsx"
    # try:
    #     # read a small window to detect header row
    #     output_df = pd.read_excel(file_path, dtype=str)
    # except Exception as e:
    #     raise SystemExit(1)

    output_df.to_excel(OUTPUT_EXCEL_PATH, index=False)
    print(f"✅ All data saved to '{OUTPUT_EXCEL_PATH}'")

    # ---> Add these lines:
    json_records = output_df.to_dict(orient="records")
    return output_df, json_records


# Optional: keep a tiny demo for direct execution (safe, takes only one input)
if __name__ == "__main__":
    # Example usage (replace with your own test SKUs):
    demo_skus = ["2317935", "24434798"]
    get_product_data(demo_skus)
