import requests
import pandas as pd
import time
import random
import json
from concurrent.futures import ThreadPoolExecutor, as_completed
from tqdm import tqdm
from threading import Lock
import os
output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
# df = pd.read_excel("/kaggle/input/staples-skus/staples_item_numbers.xlsx")
df = pd.read_excel(f"{output_dir}/staples_item_numbers.xlsx")

url = "https://www.staplesadvantage.com/"

# Create a session object to persist cookies
session = requests.Session()

# Make a GET request
response = session.get(url)

# Extract JSESSIONID from cookies
jsessionid = session.cookies.get("JSESSIONID")


base_url = "https://www.staplesadvantage.com/ele-lpd/api/sba-sku/"
headers = {
    "User-Agent": "Mozilla/5.0",
    "Accept": "application/json",
    "Referer": base_url,
}
cookies = {
    "JSESSIONID": jsessionid,  # Replace with your actual session ID
}

# Shared counter and lock for thread-safe increments
processed_count = 0
count_lock = Lock()

def fetch_product_data(item_number):
    global processed_count

    api_url = f"{base_url}product_{item_number}?pgIntlO=Y"

    try:
        r = requests.get(api_url, headers=headers, cookies=cookies)

        if r.status_code == 301:
            redirect_path = r.json().get("path")
            if redirect_path:
                redirect_url = f"{base_url}{redirect_path}"
                redirected_response = requests.get(redirect_url, headers=headers, cookies=cookies)

                try:
                    redirected_data = redirected_response.json()
                    # Extracting only the "items" part from the redirected response
                    items = redirected_data.get("SBASkuState", {}).get("skuData", {}).get("items", [])
                except json.JSONDecodeError:
                    redirected_data = redirected_response.text
                    items = []
                
                result = {
                    "Items": items  # Return only the "items" field
                }
        else:
            # For non-redirect responses, directly extract the "items" part
            response_data = r.json()
            items = response_data.get("SBASkuState", {}).get("skuData", {}).get("items", [])

            result = {
                "Items": items  # Return only the "items" field
            }
    except Exception as e:
        result = {
            "Items": str(e)  # Store the error message if any issue occurs
        }

    # Increment the counter and do a 30s sleep every 100 items processed
    with count_lock:
        processed_count += 1
        if processed_count % 500 == 0 or processed_count == 1:
            print(f"\nProcessed {processed_count} items. Sleeping for 30 seconds...")
            print(result)
            time.sleep(30)
            
    return result


results = []

with ThreadPoolExecutor(max_workers=5) as executor:
    futures = [executor.submit(fetch_product_data, row["Item Number"]) for _, row in df.iterrows()]
    for future in tqdm(as_completed(futures), total=len(futures), desc="Processing items"):
        results.append(future.result())


with open("results.json", "w", encoding="utf-8") as f:
    json.dump(results, f, ensure_ascii=False, indent=4)
