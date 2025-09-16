import os
import re
import time
import html
import json
import random
import asyncio
import requests
import threading
import pandas as pd
from tqdm import tqdm
import mysql.connector
from bs4 import BeautifulSoup
from datetime import datetime
from dotenv import load_dotenv
from threading import Lock, Event
from concurrent.futures import ThreadPoolExecutor, as_completed
from playwright.sync_api import sync_playwright
from playwright.async_api import async_playwright

# ──────────────────────────────────────────────────────────────────────────────
# Environment & constants
# ──────────────────────────────────────────────────────────────────────────────
load_dotenv()

LOG_FILE = os.getenv("CUSTOM_LOG_PATH", "/var/www/html/supplier_ds/importdemo/storage/logs/laravel.log")

LOGIN_URL = "https://www.staplesadvantage.com/idm"
USERNAME = os.getenv("STAPLES_USER", "centerpointstaples")
PASSWORD = os.getenv("STAPLES_PASS", "q*pETby5!YH_Xcr")


# Where we persist the authenticated storage (cookies + origins/localStorage)
PW_STATE_PATH = os.getenv("PW_STATE_PATH", "/staples_storage_state.json")

# ──────────────────────────────────────────────────────────────────────────────
# Helpers: logging, human-like input
# ──────────────────────────────────────────────────────────────────────────────
def log_to_laravel(message: str):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open(LOG_FILE, "a") as f:
        f.write(f"[{timestamp}] local.ERROR: {message}\n")

def human_type(page, selector, text):
    for ch in text:
        page.type(selector, ch, delay=random.uniform(100, 200))
    page.wait_for_timeout(random.uniform(300, 700))

def human_mouse_move(page):
    box = page.locator("body").bounding_box()
    x0, y0 = box["width"] / 2, box["height"] / 2
    page.mouse.move(x0, y0)
    for _ in range(5):
        page.mouse.move(
            x0 + random.uniform(-100, 100),
            y0 + random.uniform(-100, 100),
            steps=10,
        )
        page.wait_for_timeout(random.uniform(200, 500))

# ──────────────────────────────────────────────────────────────────────────────
# Single login step: produce (1) saved storage_state for Playwright,
# and (2) a simple dict of cookies for requests.
# ──────────────────────────────────────────────────────────────────────────────
def login_and_capture_auth():
    """
    - Launches Chromium (non-headless), performs login (including TrustArc),
    - Saves storage_state to PW_STATE_PATH for later reuse by async Playwright,
    - Returns a {cookie_name: value} dict for requests.
    """
    with sync_playwright() as p:
        browser = p.chromium.launch(
            headless=False,
            args=["--disable-blink-features=AutomationControlled"],
        )
        context = browser.new_context(
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/139.0.0.0 Safari/537.36"
            ),
            locale="en-US",
        )
        page = context.new_page()
        page.add_init_script("try { delete Object.getPrototypeOf(navigator).webdriver; } catch {}")

        page.goto(LOGIN_URL)
        human_mouse_move(page)

        # ✅ Accept TrustArc cookie banner if present
        try:
            frame = page.frame_locator("iframe.truste_popframe")
            button = frame.get_by_role("button", name="Agree and Proceed")
            button.wait_for(timeout=10000)

            # Wait for navigation after click
            with page.expect_navigation(wait_until="load", timeout=15000):
                button.click()
            print("✅ Cookie banner dismissed and page reloaded")
        except Exception as e:
            print("⚠️ Cookie banner not found or already handled:", e)

        # Perform login
        page.wait_for_selector('input[name="userId"]', timeout=30000)
        human_type(page, 'input[name="userId"]', USERNAME)
        page.click("button#Next")
        time.sleep(3)
        page.wait_for_selector('input[name="password"]', timeout=30000)
        human_type(page, 'input[name="password"]', PASSWORD)
        page.click('button:has-text("Sign in")')

        # Wait for post-login UI (adjust if you have a stronger signal)
        page.wait_for_timeout(5000)

        # Ensure parent folder exists
        os.makedirs(os.path.dirname(PW_STATE_PATH), exist_ok=True)

        # Persist storage for later authenticated contexts
        context.storage_state(path=PW_STATE_PATH)

        # Return a plain cookie dict for requests
        cookies_list = context.cookies()
        cookie_dict = {c["name"]: c["value"] for c in cookies_list}

        browser.close()
        return cookie_dict

# ──────────────────────────────────────────────────────────────────────────────
# DB bootstrap: locate incoming file, header mapping, set cron, etc.
# (unchanged logic; just wrapped for readability)
# ──────────────────────────────────────────────────────────────────────────────
def bootstrap_from_db():
    conn1 = mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        user=os.getenv("DB_USERNAME", "roo1"),
        password=os.getenv("DB_PASSWORD", "Password123#@!"),
        database=os.getenv("DB_SECOND_DATABASE", "sp16"),
    )
    cursor1 = conn1.cursor()

    # Industry fixed
    industry_id = 1
    destination_path = os.getenv(
        "DESTINATION_PATH",
        "/var/www/html/supplier_ds/importdemo/public/excel_sheets",
    )

    # Is a file already in processing?
    cursor1.execute(
        """
        SELECT id, date, file_name, created_by, supplier_id, catalog_price_type_id
        FROM catalog_attachments 
        WHERE cron = 5 AND deleted_by IS NULL
        LIMIT 1
        """
    )
    already_processing_file = cursor1.fetchone()

    if already_processing_file:
        file_value = None
    else:
        cursor1.execute(
            """
            SELECT id, date, file_name, created_by, supplier_id, catalog_price_type_id 
            FROM catalog_attachments 
            WHERE cron = 11 AND supplier_id = 4 AND deleted_by IS NULL
            LIMIT 1
            """
        )
        file_value = cursor1.fetchone()

    if not file_value:
        cursor1.close()
        conn1.close()
        return None

    file_id, date, input_file, created_by, supplier_id, catalog_price_type_id = file_value

    # Move picked file into "processing" state
    cursor1.execute("UPDATE catalog_attachments SET cron = %s WHERE id = %s", (5, file_id))
    conn1.commit()

    # Header mapping
    cursor1.execute(
        """
        SELECT csf.label, crf.field_name 
        FROM catalog_supplier_fields csf
        LEFT JOIN catalog_required_fields crf ON csf.catalog_required_field_id = crf.id
        WHERE csf.deleted = 0 AND csf.supplier_id = %s
        """,
        (supplier_id,),
    )
    column_values = cursor1.fetchall()
    header_labels = [row[0] for row in column_values]
    header_mapping = {row[0]: row[1] for row in column_values}

    # Date handling
    file_date = datetime.strptime(str(date), "%Y-%m-%d")
    year, month = file_date.year, file_date.month
    month_columns = {
        1: "january", 2: "february", 3: "march", 4: "april", 5: "may", 6: "june",
        7: "july", 8: "august", 9: "september", 10: "october", 11: "november", 12: "december",
    }
    month_column = month_columns[month]

    # Prior/future file checks
    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11 AND MONTH(date) = %s AND YEAR(date) = %s
          AND supplier_id = 4 AND deleted_at IS NULL
        LIMIT 1
        """,
        (str(month).zfill(2), str(year)),
    )
    first_file_uploaded = cursor1.fetchone()

    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11 AND supplier_id = 4 AND MONTH(date) > %s AND YEAR(date) >= %s
          AND deleted_at IS NULL
        LIMIT 1
        """,
        (str(month).zfill(2), str(year)),
    )
    greater_date_file_exist = cursor1.fetchone()

    # Deactivate previous if needed
    if first_file_uploaded:
        cursor1.execute(
            """
            UPDATE catalog_items 
            SET active = 0, updated_at = %s 
            WHERE supplier_id = %s
            """,
            (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), supplier_id),
        )
        conn1.commit()

    # Load Excel and detect header row
    file_path = os.path.join(destination_path, input_file)
    try:
        # read a small window to detect header row
        file_data = pd.read_excel(file_path, dtype=str, header=None, nrows=6)
    except Exception as e:
        print(f"Error reading input file: {e}")
        cursor1.close()
        conn1.close()
        raise SystemExit(1)

    header_row_index = None
    for i in range(len(file_data)):
        row_values = file_data.iloc[i].astype(str).str.strip().tolist()
        missing_headers = set(header_labels) - set(row_values)
        if not missing_headers:
            header_row_index = i
            break

    if header_row_index is None:
        cursor1.execute("UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,))
        conn1.commit()
        print("❌ Could not find the required columns in the file. Please check the data.")
        cursor1.close()
        conn1.close()
        raise SystemExit(1)

    df = sku_data = pd.read_excel(file_path, dtype=str, skiprows=header_row_index)
    actual_columns = df.columns.astype(str).str.strip().tolist()

    missing_columns = [col for col in header_labels if col not in actual_columns]
    if missing_columns:
        print("⚠️ Missing required columns:", missing_columns)
        cursor1.execute("UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,))
        conn1.commit()
        cursor1.close()
        conn1.close()
        raise SystemExit(1)

    # finalize
    sku_data.columns = actual_columns
    sku_data.rename(columns=header_mapping, inplace=True)

    cursor1.close()
    conn1.close()

    return {
        "file_id": file_id,
        "date": file_date,
        "year": year,
        "month": month,
        "month_column": month_column,
        "supplier_id": supplier_id,
        "catalog_price_type_id": catalog_price_type_id,
        "first_file_uploaded": first_file_uploaded,
        "greater_date_file_exist": greater_date_file_exist,
        "sku_data": sku_data,
        "file_path": file_path,
        "industry_id": industry_id,
    }

# ──────────────────────────────────────────────────────────────────────────────
# Playwright scraping utils (authenticated via storage_state)
# ──────────────────────────────────────────────────────────────────────────────
async def accept_cookie_consent(page):
    try:
        await page.wait_for_selector("iframe.truste_popframe", timeout=6000)
        frame = page.frame(name="trustarc_cm")
        if frame:
            await frame.wait_for_selector("a.call", timeout=5000)
            agree_button = frame.locator("a.call", has_text="Agree and Proceed")
            if await agree_button.is_visible():
                async with page.expect_navigation(wait_until="domcontentloaded", timeout=10000):
                    await agree_button.click()
    except Exception:
        pass

def extract_links(soup: BeautifulSoup):
    link_tags = []
    patterns = [
        lambda x: x and any(cls.startswith("link-box") for cls in x.split()),
        lambda x: x and any(cls.startswith("popular-category") for cls in x.split()),
        lambda x: x and ("seo-component__seoLink" in x or "popular-category-card__HomePageAnchorDiv" in x),
        lambda x: x and "link-box" in x,
    ]
    for pattern in patterns:
        link_tags.extend(soup.find_all("a", class_=pattern))
    containers = [
        ("div", "link-box__listBoxWrapper"),
        ("div", "link-box__appendExtraHeight"),
        ("div", lambda x: x and "popular-categories__PopularCategoriesContainer" in x),
        ("div", lambda x: x and "sc-1u3l2ng-6" in x),
    ]
    for tag, cls in containers:
        for container in soup.find_all(tag, class_=cls):
            a_tag = container.find("a", href=True)
            if a_tag:
                link_tags.append(a_tag)

    unique_links = {}
    for tag in link_tags:
        href = tag.get("href")
        if not href:
            continue
        span = tag.find("span")
        text = span.get_text(strip=True) if span else tag.get_text(strip=True)
        unique_links[href] = text
    return unique_links

async def gradual_scroll(page, step=300, delay=1, max_steps=18):
    for _ in range(max_steps):
        await page.evaluate(f"window.scrollBy(0, {step})")
        await asyncio.sleep(delay)

async def scroll_and_get_soup(page):
    await gradual_scroll(page)
    content = await page.content()
    return BeautifulSoup(content, "html.parser")

def check_target_element(soup: BeautifulSoup):
    return any(
        [
            soup.find("span", class_="sc-1npzh55-7 hjrnTk"),
            soup.find("div", class_="sc-1npzh55-4 cngNqr"),
            soup.find("div", class_="sc-fKAtdO llutEA"),
            soup.find("div", class_="sc-1gktvoi-0 iWbUMS"),
        ]
    )

async def retry_page_load(page, url, retries=2, wait=5):
    for attempt in range(retries + 1):
        try:
            await page.goto(url, wait_until="domcontentloaded")
            await asyncio.sleep(wait)
            await accept_cookie_consent(page)
            return await scroll_and_get_soup(page)
        except Exception as e:
            print(f"   🔁 Retry {attempt + 1}/{retries} for {url}: {e}")
            if attempt == retries:
                raise
            await asyncio.sleep(wait)

# ──────────────────────────────────────────────────────────────────────────────
# Scraper 1: Category tree crawl (authenticated)
# ──────────────────────────────────────────────────────────────────────────────
async def crawl_categories(storage_state_path, out_items_path):
    BASE_URL = "https://www.staplesadvantage.com"
    target_urls = [
        f"{BASE_URL}/office-supplies/cat_SC273214",
        f"{BASE_URL}/coffee-water-snacks/cat_SC215",
        f"{BASE_URL}/paper/cat_SC204",
        f"{BASE_URL}/cleaning-supplies/cat_SC213",
        f"{BASE_URL}/education/cat_SC208",
        f"{BASE_URL}/furniture/cat_SC212",
        f"{BASE_URL}/computers-accessories/cat_SC202",
        f"{BASE_URL}/phones-cameras-electronics/cat_SC285699",
        f"{BASE_URL}/printers-scanners/cat_SC216",
        f"{BASE_URL}/shipping-packing-mailing-supplies/cat_SC211",
        f"{BASE_URL}/facilities/cat_SC400125",
        f"{BASE_URL}/safety-supplies/cat_SC90229",
    ]

    error_urls, matched_urls = [], []
    item_numbers, processed_links, extracted_links = set(), set(), set()
    semaphore = asyncio.Semaphore(5)

    async def extract_item_numbers_from_page(page):
        page_items = set()
        try:
            while True:
                await gradual_scroll(page)
                await page.wait_for_selector(".standard-tile__product_id_wrapper", timeout=10000)
                texts = await page.locator(".standard-tile__product_id").all_inner_texts()
                for text in texts:
                    m = re.search(r"Item:\s*([\w-]+)", text)
                    if m:
                        item = m.group(1)
                        if item not in item_numbers:
                            item_numbers.add(item)
                            page_items.add(item)

                next_btn = page.locator("a.sc-1npzh55-3[aria-label='Next page of results']")
                if not await next_btn.is_visible() or (await next_btn.get_attribute("aria-disabled")) == "true":
                    break
                await next_btn.click()
                await page.wait_for_timeout(3000)
        except Exception as e:
            print(f"⚠️ Error extracting items from {page.url}: {e}")
        return page_items

    async def process_url(context, start_url):
        async with semaphore:
            page = await context.new_page()
            try:
                soup = await retry_page_load(page, start_url)
                initial_links = extract_links(soup)
                for href in initial_links.keys():
                    full_url = href if href.startswith("http") else BASE_URL + href
                    if "product_" in full_url or full_url in processed_links:
                        continue
                    processed_links.add(full_url)
                    extracted_links.add(full_url)

                    try:
                        soup = await retry_page_load(page, full_url)
                        if check_target_element(soup):
                            matched_urls.append(full_url)
                            await extract_item_numbers_from_page(page)
                            continue

                        # dive one level deeper
                        next_links = extract_links(soup)
                        for nhref in next_links:
                            nested_url = nhref if nhref.startswith("http") else BASE_URL + nhref
                            if "product_" in nested_url or nested_url in processed_links:
                                continue
                            processed_links.add(nested_url)
                            extracted_links.add(nested_url)
                            try:
                                soup = await retry_page_load(page, nested_url)
                                if check_target_element(soup):
                                    matched_urls.append(nested_url)
                                    await extract_item_numbers_from_page(page)
                            except Exception as e:
                                error_urls.append(nested_url)
                    except Exception as e:
                        error_urls.append(full_url)
            except Exception:
                error_urls.append(start_url)
            finally:
                await page.close()

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        # IMPORTANT: Authenticated context via storage_state
        context = await browser.new_context(storage_state=storage_state_path)

        await asyncio.gather(*(process_url(context, url) for url in target_urls))

        await context.close()
        await browser.close()

    # persist results
    df = pd.DataFrame(sorted(item_numbers), columns=["Item Number"])
    df.to_excel(out_items_path, index=False)
    print(f"📊 Category crawl saved: {out_items_path}")

# ──────────────────────────────────────────────────────────────────────────────
# Scraper 2: Search paginator (authenticated)
# ──────────────────────────────────────────────────────────────────────────────
async def crawl_search(storage_state_path, term, out_items_path):
    BASE_URL = "https://www.staplesadvantage.com/search"
    CONCURRENCY_LIMIT = 10
    MIN_ITEMS_PER_PAGE = 40
    MAX_RETRIES = 5

    async def gradual_scroll(page, step=400, delay=1000, max_scrolls=50):
        for _ in range(max_scrolls):
            await page.evaluate(f"window.scrollBy(0, {step})")
            await page.wait_for_timeout(delay)

    def get_page_url(q, page_number):
        return f"{BASE_URL}?pn={page_number}&term={q.replace(' ', '%20')}"

    async def scrape_page(context, q, page_number, semaphore, attempt=1):
        url = get_page_url(q, page_number)
        async with semaphore:
            page = await context.new_page()
            try:
                await page.goto(url)
                await page.wait_for_timeout(3000)
                await gradual_scroll(page)

                raw_items = await page.locator(
                    "div.list-tile__id_element, div.standard-tile__product_id"
                ).all_inner_texts()

                extracted = []
                for item in raw_items:
                    if item.strip().lower().startswith("item"):
                        m = re.search(r"(?:Item|SKU|Part)\s*#?\s*[:\-]?\s*([A-Za-z0-9\-]+)", item)
                        if m:
                            extracted.append(m.group(1))

                if len(extracted) < MIN_ITEMS_PER_PAGE and attempt < MAX_RETRIES:
                    await page.close()
                    return await scrape_page(context, q, page_number, semaphore, attempt + 1)

                if len(extracted) < MIN_ITEMS_PER_PAGE:
                    extracted = []

                # next page detection
                next_button = page.locator("a[aria-label*='Next page of results']")
                has_next = await next_button.count() > 0
                is_disabled = False
                if has_next:
                    aria_disabled = await next_button.get_attribute("aria-disabled")
                    aria_label = await next_button.get_attribute("aria-label")
                    has_disabled_attr = await next_button.get_attribute("disabled") is not None
                    is_disabled = (
                        (aria_disabled and aria_disabled.lower() == "true")
                        or (aria_label and "disabled" in aria_label.lower())
                        or has_disabled_attr
                    )

                return {"page": page_number, "items": extracted, "has_next": has_next and not is_disabled}
            except Exception:
                return {"page": page_number, "items": [], "has_next": False}
            finally:
                await page.close()

    all_items = set()
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        # IMPORTANT: Authenticated context via storage_state
        context = await browser.new_context(storage_state=storage_state_path)

        semaphore = asyncio.Semaphore(CONCURRENCY_LIMIT)
        page_number, has_next = 1, True

        while has_next:
            tasks = [
                scrape_page(context, term, pn, semaphore)
                for pn in range(page_number, page_number + CONCURRENCY_LIMIT)
            ]
            results = await asyncio.gather(*tasks)
            for r in results:
                all_items.update(r["items"])
            has_next = any(r["has_next"] for r in results)
            page_number += CONCURRENCY_LIMIT

        await context.close()
        await browser.close()

    df = pd.DataFrame(sorted(all_items), columns=["Item Number"])
    df.to_excel(out_items_path, index=False)
    print(f"📁 Search crawl saved: {out_items_path}")

# ──────────────────────────────────────────────────────────────────────────────
# Requests-side product API fetch (reuses the same login cookies)
# ──────────────────────────────────────────────────────────────────────────────
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
    unit_measure_str = product.get("unitOfMeasureComposite", {}).get("unitOfMeasure", "")
    clean_unit = re.sub(r"^\d+/", "", unit_measure_str)
    return {
        "sku": product.get("partNumber"),
        "description": product.get("name"),
        "supplier_shorthand_name": product.get("itemShortDescription"),
        "unit_of_measure": clean_unit,
        "quantity_per_unit": product.get("unitOfMeasureComposite", {}).get("unitOfMeasureQty"),
        "manufacturer_number": product.get("manufacturerPartNumber"),
        "manual_out_of_stock": item.get("manualOutOfStock", False),
        "specifications": {spec.get("name"): spec.get("value") for spec in product.get("description", {}).get("specification", [])},
        "web_price": (
            price.get("item")[0]
            .get("data", {})
            .get("priceInfo", [{}])[0]
            .get("finalPrice")
            if price.get("item") else None
        ),
        "manufacturer_name": product.get("manufacturerName"),
        "url": product.get("productURL"),
        "category": category,
        "sub_category_1": subcategory_1,
        "sub_category_2": subcategory_2,
    }

def fetch_product_data(item_number, headers, base_url, cookies_dict, locks, counters, relogin_func):
    """
    Uses the same logged-in cookies the Playwright login produced.
    Relogs (once per batch threshold) by calling relogin_func to refresh cookies.
    """
    count_lock, cookies_lock, refresh_event = locks
    processed_count, shared_cookies = counters

    def do_request(session, sku, cookies_local):
        api_url = f"{base_url}product_{sku}?pgIntlO=Y"
        r = session.get(api_url, headers=headers, cookies=cookies_local, timeout=15)
        if r.status_code == 301:
            redirect_path = r.json().get("path")
            if redirect_path:
                redirected_url = f"{base_url}{redirect_path}"
                r = session.get(redirected_url, headers=headers, cookies=cookies_local, timeout=15)
        r.raise_for_status()
        return r.json()

    retries = 2
    result = {"SKU": item_number, "Error": "Unknown"}
    for attempt in range(retries + 1):
        try:
            s = requests.Session()
            with cookies_lock:
                cookies_copy = dict(shared_cookies)
            data = do_request(s, item_number, cookies_copy)
            items = data.get("SBASkuState", {}).get("skuData", {}).get("items", [])
            result = extract_product_info(items)
            if not result.get("sku"):
                raise ValueError("Empty or missing SKU in response.")
            break
        except Exception as e:
            result = {"SKU": item_number, "Error": str(e)}
            if attempt < retries:
                time.sleep(2 + attempt)
                continue
            else:
                break

    # counters / refresh policy
    with count_lock:
        processed_count[0] += 1
        if processed_count[0] % 500 == 0 or processed_count[0] == 1:
            print(f"\nProcessed {processed_count[0]} items. Sleeping for 10 seconds...")
            print(result)
            time.sleep(10)

        if processed_count[0] % 5000 == 0 and not refresh_event.is_set():
            refresh_event.set()
            # re-login to refresh cookies
            new_cookies = relogin_func()
            with cookies_lock:
                shared_cookies.clear()
                shared_cookies.update(new_cookies)
            print(f"🔄 Refreshed all cookies at count {processed_count[0]}")
            refresh_event.clear()

    return result

# ──────────────────────────────────────────────────────────────────────────────
# DB write helpers (your original logic preserved, just grouped)
# ──────────────────────────────────────────────────────────────────────────────
def adding_record_into_database(matched_row, scrap_data, year, month_column, supplier_id, industry_id, catalog_price_type_id, date, greater_date_file_exist):
    try:
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_SECOND_DATABASE", "sp16"),
        )
        cursor = conn.cursor(buffered=True)

        # product_details_category
        try:
            cursor.execute(
                "SELECT `id` FROM `product_details_category` WHERE `category_name` = %s",
                (matched_row["category"],),
            )
            r = cursor.fetchone()
            if r:
                category_id = r[0]
            else:
                cursor.execute(
                    "INSERT INTO product_details_category (category_name, created_at, updated_at) VALUES (%s, %s, %s)",
                    (matched_row["category"], datetime.now(), datetime.now()),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                category_id = cursor.fetchone()[0]
        except mysql.connector.Error as e:
            log_to_laravel(f"Table product_details_category Database error: {e}")
            conn.rollback()
            category_id = None

        # product_details_sub_category
        try:
            cursor.execute(
                "SELECT `id` FROM `product_details_sub_category` WHERE `category_id` = %s AND `sub_category_name` = %s",
                (category_id, matched_row["sub_category"]),
            )
            r = cursor.fetchone()
            if r:
                sub_category_id = r[0]
            else:
                cursor.execute(
                    "INSERT INTO `product_details_sub_category` (`category_id`, `sub_category_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s, %s)",
                    (category_id, matched_row["sub_category"], datetime.now(), datetime.now()),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                sub_category_id = cursor.fetchone()[0]
        except mysql.connector.Error as e:
            log_to_laravel(f"Table product_details_sub_category Database error: {e}")
            conn.rollback()
            sub_category_id = None

        # manufacturers
        try:
            cursor.execute(
                "SELECT `id` FROM `manufacturers` WHERE `manufacturer_name` = %s",
                (scrap_data["manufacturer_name"],),
            )
            r = cursor.fetchone()
            if r:
                manufacturer_id = r[0]
            else:
                cursor.execute(
                    "INSERT INTO `manufacturers` (`manufacturer_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s)",
                    (scrap_data["manufacturer_name"], datetime.now(), datetime.now()),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                manufacturer_id = cursor.fetchone()[0]
        except mysql.connector.Error as e:
            log_to_laravel(f"Table manufacturers Database error: {e}")
            conn.rollback()
            manufacturer_id = None

        # catalog_items
        try:
            cursor.execute(
                "SELECT `id` FROM `catalog_items` WHERE `sku` = %s AND `supplier_id` = %s",
                (scrap_data["sku"], supplier_id),
            )
            r = cursor.fetchone()
            if r:
                catalog_item_id = r[0]
                if not greater_date_file_exist:
                    cursor.execute(
                        "UPDATE `catalog_items` SET `active` = %s, `updated_at` = %s WHERE `sku` = %s AND `supplier_id` = %s",
                        (1, datetime.now(), scrap_data["sku"], supplier_id),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO catalog_items 
                    (sku, supplier_id, active, industry_id, category_id, sub_category_id, manufacturer_id,
                     unit_of_measure, created_at, updated_at, catalog_item_url, catalog_item_name,
                     quantity_per_unit, supplier_shorthand_name, manufacturer_number)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        scrap_data["sku"],
                        supplier_id,
                        1 if not greater_date_file_exist else 0,
                        industry_id,
                        category_id,
                        sub_category_id,
                        manufacturer_id,
                        scrap_data.get("unit_of_measure"),
                        datetime.now(),
                        datetime.now(),
                        scrap_data.get("url"),
                        scrap_data.get("description"),
                        scrap_data.get("quantity_per_unit"),
                        scrap_data.get("supplier_shorthand_name"),
                        scrap_data.get("manufacturer_number"),
                    ),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                catalog_item_id = cursor.fetchone()[0]
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"Table catalog_items error: {e}")
            conn.rollback()
            catalog_item_id = None

        # product_details_common_values
        try:
            for attribute_name in scrap_data.get("specifications", {}):
                cursor.execute(
                    "SELECT id FROM product_details_common_attributes WHERE sub_category_id = %s AND attribute_name = %s",
                    (sub_category_id, attribute_name),
                )
                r = cursor.fetchone()
                if not r:
                    continue
                common_attribute_id = r[0]
                cursor.execute(
                    "SELECT id FROM product_details_common_values WHERE value = %s AND catalog_item_id = %s AND common_attribute_id = %s",
                    (scrap_data["specifications"].get(attribute_name), catalog_item_id, common_attribute_id),
                )
                v = cursor.fetchone()
                if not v:
                    cursor.execute(
                        """
                        INSERT INTO product_details_common_values 
                        (value, catalog_item_id, common_attribute_id, created_at, updated_at)
                        VALUES (%s, %s, %s, %s, %s)
                        """,
                        (
                            scrap_data["specifications"].get(attribute_name),
                            catalog_item_id,
                            common_attribute_id,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        ),
                    )
                    conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"product_details_common_values error: {e}")
            conn.rollback()

        # product_details_raw_values
        try:
            cursor.execute(
                "SELECT id FROM product_details_raw_values WHERE catalog_item_id = %s",
                (catalog_item_id,),
            )
            if not cursor.fetchone():
                description = html.unescape(scrap_data.get("description", ""))
                json_string = json.dumps(description)
                cursor.execute(
                    """
                    INSERT INTO product_details_raw_values
                    (catalog_item_id, raw_values, created_at, updated_at)
                    VALUES (%s, %s, %s, %s)
                    """,
                    (
                        catalog_item_id,
                        json_string,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"product_details_raw_values error: {e}")
            conn.rollback()

        # catalog_prices (core)
        try:
            cursor.execute(
                "SELECT id FROM catalog_prices WHERE catalog_item_id = %s AND catalog_price_type_id = %s",
                (catalog_item_id, catalog_price_type_id),
            )
            r = cursor.fetchone()
            if r:
                catalog_price_id = r[0]
                if not greater_date_file_exist:
                    cursor.execute(
                        """
                        UPDATE catalog_prices 
                        SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s
                        WHERE id = %s
                        """,
                        (1, matched_row["value"], date, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 1, catalog_price_id),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO catalog_prices 
                    (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        1,
                        matched_row["value"],
                        catalog_item_id,
                        date,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_price_type_id,
                        1,
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_prices core error: {e}")
            conn.rollback()

        # catalog_prices (web price, type_id=3)
        try:
            cursor.execute(
                "SELECT id FROM catalog_prices WHERE catalog_item_id = %s AND catalog_price_type_id = %s",
                (catalog_item_id, 3),
            )
            r = cursor.fetchone()
            if r:
                catalog_price_id = r[0]
                if not greater_date_file_exist:
                    cursor.execute(
                        """
                        UPDATE catalog_prices 
                        SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s
                        WHERE id = %s
                        """,
                        (1, scrap_data["web_price"], date, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 0, catalog_price_id),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO catalog_prices 
                    (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        1,
                        scrap_data["web_price"],
                        catalog_item_id,
                        date,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        3,
                        0,
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_prices web error: {e}")
            conn.rollback()

        # catalog_price_history (core)
        try:
            cursor.execute(
                """
                SELECT id FROM catalog_price_history 
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s AND year = %s LIMIT 1
                """,
                (catalog_item_id, catalog_price_type_id, year),
            )
            ph = cursor.fetchone()
            if ph:
                cursor.execute(
                    f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s WHERE id = %s",
                    (matched_row["value"], datetime.now().strftime("%Y-%m-%d %H:%M:%S"), ph[0]),
                )
                conn.commit()
            else:
                cursor.execute(
                    f"""
                    INSERT INTO catalog_price_history 
                    (year, created_at, updated_at, catalog_item_id, catalog_price_type_id, {month_column})
                    VALUES (%s, %s, %s, %s, %s, %s)
                    """,
                    (
                        year,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_item_id,
                        catalog_price_type_id,
                        matched_row["value"],
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_price_history core error: {e}")
            conn.rollback()

        # catalog_price_history (web, type_id=3)
        try:
            cursor.execute(
                """
                SELECT id FROM catalog_price_history 
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s AND year = %s LIMIT 1
                """,
                (catalog_item_id, 3, year),
            )
            ph = cursor.fetchone()
            if ph:
                cursor.execute(
                    f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s WHERE id = %s",
                    (scrap_data["web_price"], datetime.now().strftime("%Y-%m-%d %H:%M:%S"), ph[0]),
                )
                conn.commit()
            else:
                cursor.execute(
                    f"""
                    INSERT INTO catalog_price_history 
                    (year, created_at, updated_at, catalog_item_id, catalog_price_type_id, {month_column})
                    VALUES (%s, %s, %s, %s, %s, %s)
                    """,
                    (
                        year,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_item_id,
                        3,
                        scrap_data["web_price"],
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_price_history web error: {e}")
            conn.rollback()

        # check_core_history
        try:
            cursor.execute(
                """
                SELECT id FROM check_core_history
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s LIMIT 1
                """,
                (catalog_item_id, catalog_price_type_id),
            )
            ch = cursor.fetchone()
            core_list_value = 1
            if ch:
                if not greater_date_file_exist:
                    cursor.execute(
                        """
                        UPDATE check_core_history SET updated_at = %s, price_file_date = %s, core_list = %s
                        WHERE id = %s
                        """,
                        (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), date, core_list_value, ch[0]),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO check_core_history
                    (customer_id, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        1,
                        catalog_item_id,
                        date,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_price_type_id,
                        core_list_value,
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"check_core_history error: {e}")
            conn.rollback()

    finally:
        try:
            cursor.close()
            conn.close()
        except Exception:
            pass

# ──────────────────────────────────────────────────────────────────────────────
# Bulk “reset flags” step (unchanged)
# ──────────────────────────────────────────────────────────────────────────────
def reset_active_and_core_flags(supplier_id):
    try:
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_SECOND_DATABASE", "sp16"),
        )
        cursor = conn.cursor()
        print(supplier_id)
        print("✅ Updating active status and resetting core_list flags...")

        cursor.execute("BEGIN;")
        cursor.execute("UPDATE catalog_items SET active = 0 WHERE supplier_id = %s;", (supplier_id,))
        cursor.execute(
            """
            UPDATE catalog_prices SET core_list = 0
            WHERE catalog_item_id IN (SELECT id FROM catalog_items WHERE supplier_id = %s)
            """,
            (supplier_id,),
        )
        cursor.execute(
            """
            UPDATE check_core_history SET core_list = 0
            WHERE catalog_item_id IN (SELECT id FROM catalog_items WHERE supplier_id = %s)
            """,
            (supplier_id,),
        )
        conn.commit()
        print("✅ All updates completed successfully.")
    except mysql.connector.Error as e:
        print(f"Database error: {e}")
        log_to_laravel(f"Database error updating check_core_history: {e}")
        conn.rollback()
    finally:
        try:
            cursor.close()
            conn.close()
        except Exception:
            pass

# ──────────────────────────────────────────────────────────────────────────────
# Main flow
# ──────────────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    # 1) Login once; persist storage for Playwright and capture cookies for requests
    print("🔐 Logging in and capturing authenticated state...")
    shared_cookies = login_and_capture_auth()  # {name:value}
    print(f"✅ Storage saved → {PW_STATE_PATH}. Cookies captured: {len(shared_cookies)}")

    # 2) Bootstrap DB + file/headers
    boot = bootstrap_from_db()
    if not boot:
        print("Nothing to process (no cron=11 file).")
        raise SystemExit(0)

    file_id = boot["file_id"]
    date = boot["date"]
    year = boot["year"]
    month = boot["month"]
    month_column = boot["month_column"]
    supplier_id = boot["supplier_id"]
    catalog_price_type_id = boot["catalog_price_type_id"]
    first_file_uploaded = boot["first_file_uploaded"]
    greater_date_file_exist = boot["greater_date_file_exist"]
    sku_data = boot["sku_data"]
    file_path = boot["file_path"]
    industry_id = boot["industry_id"]

    # 3) Authenticated Playwright scrapers
    output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    numbers_path = f"{output_dir}/staples_item_numbers.xlsx"
    search_path = f"{output_dir}/staples_items.xlsx"

    # Category crawl
    print("🕷️ Running category crawl (authenticated)...")
    # asyncio.run(crawl_categories(PW_STATE_PATH, numbers_path))

    # Search crawl
    print("🕷️ Running search crawl (authenticated)...")
    asyncio.run(crawl_search(PW_STATE_PATH, term="ink and toner", out_items_path=search_path))

    # 4) Merge Excel outputs with sku_data (unchanged logic)
    df1 = pd.read_excel(numbers_path)
    df3 = pd.read_excel(search_path)
    df3.columns = df3.columns.str.strip()

    merged_df = pd.merge(
        df1,
        sku_data[["sku", "value"]],
        left_on="Item Number",
        right_on="sku",
        how="left",
    )
    merged_df.drop(columns=["sku"], inplace=True)
    final_df = pd.merge(merged_df, df3, on="Item Number", how="left")
    final_df.rename(columns={"Item Number": "sku"}, inplace=True)

    # Format: YYYYMMDD
    date_str = datetime.now().strftime("%Y%m%d")

    merged_output_all_files_path = os.path.join(
        output_dir, f"{date_str}_merged_output_all_files.xlsx"
    )

    # merged_output_all_files_path = f"{output_dir}/merged_output_all_files.xlsx"
    final_df.to_excel(merged_output_all_files_path, index=False)
    print(f"✅ Final merge saved: {merged_output_all_files_path}")

    # 5) Prepare list of SKUs for API calls
    df = pd.read_excel(merged_output_all_files_path)
    sku_list = df["sku"].dropna().astype(str).tolist()
    sku_len = len(sku_list)

    # Requests headers
    base_url = "https://www.staplesadvantage.com/ele-lpd/api/sba-sku/"
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36"
        ),
        "Accept": "application/json, text/plain, */*",
        "Accept-Language": "en-US,en;q=0.9",
        "Referer": "https://www.staplesadvantage.com/",
        "Connection": "keep-alive",
    }

    # 6) Reset flags before inserting (unchanged)
    reset_active_and_core_flags(supplier_id)

    # 7) Multithreaded API fetch using the SAME cookies from login
    processed_count = [0]  # list to make it mutable by reference
    count_lock = Lock()
    cookies_lock = Lock()
    refresh_event = Event()

    # relogin function (used at refresh thresholds)
    def relogin_func():
        print("🔁 Re-login to refresh cookies/state...")
        new_cookie_dict = login_and_capture_auth()
        return new_cookie_dict

    results = []
    locks_tuple = (count_lock, cookies_lock, refresh_event)
    counters_tuple = (processed_count, shared_cookies)
    processed_sku_count = 0
    last_written_percent = -1

    with ThreadPoolExecutor(max_workers=5) as executor:
        futures = [
            executor.submit(
                fetch_product_data,
                sku,
                headers,
                base_url,
                shared_cookies,
                locks_tuple,
                counters_tuple,
                relogin_func,
            )
            for sku in sku_list
        ]
        for future in tqdm(as_completed(futures), total=len(futures), desc="Processing items"):
            result = future.result()

            processed_sku_count += 1

            sku_result = result.get("sku")
            if not sku_result:
                print("Missing 'sku' in result:", result)
                log_to_laravel(f"Missing 'sku' in result: {result}")
                continue

            # Match row from merged DF for DB insert values
            record = df.loc[df["sku"] == sku_result]
            record = record.dropna(axis=1, how="all")
            record = record.loc[:, ~record.columns.astype(str).str.contains("^None$", na=False)]

            if record.empty:
                continue

            matched_row = record.to_dict(orient="records")[0]
            if not matched_row.get("value"):
                web_price = result.get("web_price")
                if web_price is not None:
                    matched_row["value"] = round(web_price * (1 - 0.02), 2)
                else:
                    matched_row["value"] = 0
                    print(f"Missing web_price for SKU: {result.get('sku')}")
                    log_to_laravel(f"Missing web_price for SKU: {result.get('sku')}")
                    continue

            # ODP category mapping (unchanged)
            try:
                conn5 = mysql.connector.connect(
                    host=os.getenv("DB_HOST", "127.0.0.1"),
                    user=os.getenv("DB_USERNAME", "roo1"),
                    password=os.getenv("DB_PASSWORD", "Password123#@!"),
                    database=os.getenv("DB_SECOND_DATABASE", "sp16"),
                )
                cursor5 = conn5.cursor()

                if all(k in result for k in ("category", "sub_category_1", "sub_category_2")):
                    query = """
                        SELECT * FROM temp_category
                        WHERE category = %s AND subcategory_1 = %s AND subcategory_2 = %s
                    """
                    params = (result["category"], result["sub_category_1"], result["sub_category_2"])
                    cursor5.execute(query, params)
                    odp = cursor5.fetchone()
                    if odp:
                        matched_row["category"] = odp[4]
                        matched_row["sub_category"] = odp[5]
                    else:
                        matched_row["category"] = result["category"]
                        matched_row["sub_category"] = result["sub_category_1"]
                else:
                    print(f"Missing category fields in result for SKU {result.get('sku')}")
                    log_to_laravel(f"Missing category fields in result for SKU {result.get('sku')}")
                    cursor5.close()
                    conn5.close()
                    continue
            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error: {e}")
                try:
                    cursor5.close()
                    conn5.close()
                except Exception:
                    pass
                continue
            finally:
                try:
                    cursor5.close()
                    conn5.close()
                except Exception:
                    pass

            # Required fields
            required_fields = ["category", "sub_category"]
            if any(not matched_row.get(f) for f in required_fields):
                print(f"Missing fields {required_fields} for SKU {matched_row.get('sku')}")
                log_to_laravel(f"Missing fields {required_fields} for SKU {matched_row.get('sku')}")
                continue

            # Write all DB tables for this item
            adding_record_into_database(
                matched_row=matched_row,
                scrap_data=result,
                year=year,
                month_column=month_column,
                supplier_id=supplier_id,
                industry_id=industry_id,
                catalog_price_type_id=catalog_price_type_id,
                date=date,
                greater_date_file_exist=greater_date_file_exist,
            )
            results.append({
                "sku": matched_row.get("sku"),
                "category": matched_row.get("category"),
                "sub_category": matched_row.get("sub_category"),
                "value": matched_row.get("value"),
                "scrap_data": json.dumps(result),  # store raw API response as JSON
                "year": year,
                "month_column": month_column,
                "supplier_id": supplier_id,
                "industry_id": industry_id,
                "catalog_price_type_id": catalog_price_type_id,
                "date": date,
                "greater_date_file_exist": greater_date_file_exist,
            })
            
            if sku_len > 0:
                new_percent = int((processed_sku_count / sku_len) * 100)
            else:
                new_percent = 0  # Or handle appropriately if zero total SKUs

            if new_percent > last_written_percent:
                progress_percent_db = new_percent
                last_written_percent = new_percent
                try:
                    conn1 = mysql.connector.connect(
                        host=os.getenv("DB_HOST", "127.0.0.1"),
                        user=os.getenv("DB_USERNAME", "roo1"),
                        password=os.getenv("DB_PASSWORD", "Password123#@!"),
                        database=os.getenv("DB_SECOND_DATABASE", "sp16")
                    )

                    cursor1 = conn1.cursor()
                    cursor1.execute(
                        "UPDATE catalog_attachments SET file_upload_percent = %s WHERE id = %s",
                        (progress_percent_db, file_id)
                    )
                    conn1.commit()
                except mysql.connector.Error as err:
                    print(f"❌ DB Error: {err}")
                    log_to_laravel(f"❌ DB Error during percentage update: {err}")
                    if conn1.is_connected():
                        conn1.rollback()
                finally:
                    cursor1.close()
                    conn1.close()

    try:
        conn4 = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_SECOND_DATABASE", "sp16")
        )
        cursor4 = conn4.cursor()
        
        # Update cron status to indicate completion
        cursor4.execute("UPDATE catalog_attachments SET cron = 6 WHERE id = %s", (file_id,))
        conn4.commit()

    except mysql.connector.Error as err:
        print(f"❌ DB Error: {err}")
        log_to_laravel(f"❌ DB Error during cron update: {err}")
    finally:
        cursor4.close()
        conn4.close()

    # 8) Save the raw results (if you want them)
    output_path = f"{output_dir}/staples_product_data.xlsx"
    df_out = pd.json_normalize(results, sep="_")
    df_out.to_excel(output_path, index=False)
    # pd.DataFrame(results).to_excel(output_path, index=False)
    print(f"✅ All data saved to '{output_path}'")
