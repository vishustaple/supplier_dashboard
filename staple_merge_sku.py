import re
import os
import sys
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
from threading import Lock
from bs4 import BeautifulSoup
from datetime import datetime
from dotenv import load_dotenv
from threading import Lock, Event
from playwright.async_api import async_playwright
from concurrent.futures import ThreadPoolExecutor, as_completed

# Load .env variables
load_dotenv()

LOG_FILE = os.getenv("CUSTOM_LOG_PATH", "/var/www/html/supplier_ds/importdemo/storage/logs/laravel.log")

def log_to_laravel(message):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    formatted = f"[{timestamp}] local.ERROR: {message}\n"
    with open(LOG_FILE, 'a') as log_file:
        log_file.write(formatted)


# Connect to MySQL
conn1 = mysql.connector.connect(
    host=os.getenv("DB_HOST", "127.0.0.1"),
    user=os.getenv("DB_USERNAME", "roo1"),
    password=os.getenv("DB_PASSWORD", "Password123#@!"),
    database=os.getenv("DB_SECOND_DATABASE", "sp16")
)

cursor1 = conn1.cursor()

# Define industry ID
industry_id = 1

# Define file path
destination_path = os.getenv("DESTINATION_PATH","/var/www/html/supplier_ds/importdemo/public/excel_sheets")

# Fetch the file where cron is 5
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
    # Fetch the file where cron is 11
    cursor1.execute(
        """
        SELECT id, date, file_name, created_by, supplier_id, catalog_price_type_id 
        FROM catalog_attachments 
        WHERE cron = 11 AND supplier_id = 4 AND deleted_by IS NULL
        LIMIT 1
    """
    )
    file_value = cursor1.fetchone()

if file_value:
    file_id, date, input_file, created_by, supplier_id, catalog_price_type_id = (
        file_value
    )

    # Update the cron column from cron = 11 to cron = 5
    cursor1.execute(" UPDATE catalog_attachments SET cron = %s WHERE id = %s",(5,file_id))
    conn1.commit()

    # Fetch supplier field mappings
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
    header_mapping1 = [row[0] for row in column_values]
    header_mapping = {row[0]: row[1] for row in column_values}

    # Parse date
    file_date = datetime.strptime(str(date), "%Y-%m-%d")
    year = file_date.year
    month = file_date.month

    # Month mapping
    month_columns = {
        1: "january",
        2: "february",
        3: "march",
        4: "april",
        5: "may",
        6: "june",
        7: "july",
        8: "august",
        9: "september",
        10: "october",
        11: "november",
        12: "december",
    }
    month_column = month_columns[month]

    # Check if an older file exists
    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11 
        AND MONTH(date) = %s 
        AND YEAR(date) = %s
        AND supplier_id = 4 
        AND deleted_at IS NULL 
        LIMIT 1
    """,
        (
            str(month).zfill(2),
            str(year),
        ),
    )
    first_file_uploaded = cursor1.fetchone()

    # Check if a future file exists
    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11
        AND supplier_id = 4
        AND MONTH(date) > %s 
        AND YEAR(date) >= %s
        AND deleted_at IS NULL
        LIMIT 1
    """,
        (
            str(month).zfill(2),
            str(year),
        ),
    )
    greater_date_file_exist = cursor1.fetchone()

    # Deactivate previous records if needed
    if first_file_uploaded:
        cursor1.execute(
            """
            UPDATE catalog_items 
            SET active = 0, updated_at = %s 
            WHERE supplier_id = %s
        """,
            (
                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                supplier_id,
            ),
        )
        conn1.commit()

    # Load Excel file
    global file_path
    file_path = os.path.join(destination_path, input_file)
  
    # # Load Excel file
    # file_path = f"{destination_path}/{input_file}"

    try:  # Trying to reading excel file
        file_data = pd.read_excel(file_path, dtype=str, header=None, nrows=6)  # Read as strings to preserve data integrity
    except Exception as e:  # Handle exception
        print(f"Error reading input file: {e}")
        exit()

    for i in range(len(file_data)):
        row_values = file_data.iloc[i].str.strip().tolist()  # Convert non-null values to a list of strings
        missing_headers = set(header_mapping1) - set(row_values)  # Check if all required headers exist

        if not missing_headers:
            header_row_index = i  # Set header row index when found
            break  # Stop searching once the header row is found

    if header_row_index is not None:
        print(f"✅ Found header row at index: {header_row_index}")
        df = sku_data = pd.read_excel(file_path, dtype=str, skiprows=header_row_index)
        actual_columns = df.columns.str.strip().tolist()
        
        # Check for missing columns
        missing_columns = [col for col in header_mapping1 if col not in actual_columns]

        if missing_columns:
            print("⚠️ The following required columns are missing from the file:", missing_columns)
            cursor1.execute(
                "UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,)
            )
            conn1.commit()
            exit()
        else:
            # Get header row
            header = actual_columns
            print("✅ All required columns are present.")
    else:
        cursor1.execute(
            "UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,)
        )
        conn1.commit()
        print("❌ Could not find the required columns in the file. Please check the data.")
        exit()
    
    cursor1.close()
    conn1.close()

    sku_data.columns = header

    # Rename columns using header mapping
    sku_data.rename(columns=header_mapping, inplace=True)

    # error_urls = []
    # matched_urls = []
    # SCROLL_TIMES = 18
    # SCROLL_STEP = 300
    # item_numbers = set()
    # processed_links = set()
    # extracted_links = set()
    # SCROLL_DELAY = 1  # seconds
    # semaphore = asyncio.Semaphore(5)
    # BASE_URL = "https://www.staplesadvantage.com"

    # # ------------------------------ Cookie Consent ------------------------------ #
    # async def accept_cookie_consent(page):
    #     try:
    #         await page.wait_for_selector("iframe.truste_popframe", timeout=6000)
    #         frame = page.frame(name="trustarc_cm")

    #         if frame:
    #             await frame.wait_for_selector("a.call", timeout=5000)
    #             agree_button = frame.locator("a.call", has_text="Agree and Proceed")

    #             if await agree_button.is_visible():
    #                 print("🍪 Clicking 'Agree and Proceed' in iframe...")
    #                 async with page.expect_navigation(wait_until="domcontentloaded", timeout=10000):
    #                     await agree_button.click()
    #                 print("🔄 Page reloaded after accepting cookie consent.")
    #             else:
    #                 print("ℹ️ Consent button not visible in iframe.")
    #         else:
    #             print("⚠️ TrustArc iframe not found.")
    #     except Exception as e:
    #         print(f"❌ Error during cookie consent: {e}")

    # # ------------------------------ Link Extraction ------------------------------ #
    # def extract_links(soup):
    #     link_tags = []

    #     patterns = [
    #         lambda x: x and any(cls.startswith("link-box") for cls in x.split()),
    #         lambda x: x and any(cls.startswith("popular-category") for cls in x.split()),
    #         lambda x: x and ("seo-component__seoLink" in x or "popular-category-card__HomePageAnchorDiv" in x),
    #         lambda x: x and "link-box" in x,
    #     ]

    #     for pattern in patterns:
    #         link_tags.extend(soup.find_all("a", class_=pattern))

    #     containers = [
    #         ("div", "link-box__listBoxWrapper"),
    #         ("div", "link-box__appendExtraHeight"),
    #         ("div", lambda x: x and "popular-categories__PopularCategoriesContainer" in x),
    #         ("div", lambda x: x and "sc-1u3l2ng-6" in x)
    #     ]

    #     for tag, cls in containers:
    #         for container in soup.find_all(tag, class_=cls):
    #             a_tag = container.find("a", href=True)
    #             if a_tag:
    #                 link_tags.append(a_tag)

    #     unique_links = {}
    #     for tag in link_tags:
    #         href = tag.get("href")
    #         if href:
    #             span = tag.find("span")
    #             text = span.get_text(strip=True) if span else tag.get_text(strip=True)
    #             unique_links[href] = text

    #     return unique_links

    # # ------------------------------ Scroll Utilities ------------------------------ #
    # async def scroll_and_get_soup(page):
    #     print("🔻 Scrolling page to load more content...")
    #     for i in range(SCROLL_TIMES):
    #         await page.evaluate(f"window.scrollBy(0, {SCROLL_STEP});")
    #         await asyncio.sleep(SCROLL_DELAY)
    #     print("✅ Scroll complete. Parsing HTML...")
    #     content = await page.content()
    #     return BeautifulSoup(content, "html.parser")

    # async def gradual_scroll(page, scroll_step=300, delay=1, max_steps=18):
    #     for _ in range(max_steps):
    #         await page.evaluate(f"window.scrollBy(0, {scroll_step})")
    #         await asyncio.sleep(delay)

    # # ------------------------------ Product Extraction ------------------------------ #
    # async def extract_item_numbers_from_page(page):
    #     print("🔍 Extracting item numbers...")
    #     page_items = set()
    #     try:
    #         while True:
    #             await gradual_scroll(page)
    #             await page.wait_for_selector(".standard-tile__product_id_wrapper", timeout=10000)

    #             items = await page.locator(".standard-tile__product_id").all_inner_texts()
    #             for text in items:
    #                 match = re.search(r"Item:\s*([\w-]+)", text)
    #                 if match:
    #                     item = match.group(1)
    #                     if item not in item_numbers:
    #                         item_numbers.add(item)
    #                         page_items.add(item)

    #             next_btn = page.locator("a.sc-1npzh55-3[aria-label='Next page of results']")
    #             if not await next_btn.is_visible() or await next_btn.get_attribute("aria-disabled") == "true":
    #                 break

    #             print("   ⏭️ Clicking Next page...")
    #             await next_btn.click()
    #             await page.wait_for_timeout(3000)
    #     except Exception as e:
    #         print(f"⚠️ Error extracting items from {page.url}: {e}")

    #     print(f"   ✅ Found {len(page_items)} new items.")

    # # ------------------------------ Retry + Consent ------------------------------ #
    # async def retry_page_load(page, url, retries=2, wait=5):
    #     for attempt in range(retries + 1):
    #         try:
    #             await page.goto(url, wait_until="domcontentloaded")
    #             await asyncio.sleep(wait)

    #             await accept_cookie_consent(page)  # ✅ Always check for cookies

    #             return await scroll_and_get_soup(page)

    #         except Exception as e:
    #             print(f"   🔁 Retry {attempt + 1}/{retries} for {url}: {e}")
    #             if attempt == retries:
    #                 raise e
    #             await asyncio.sleep(wait)

    # # ------------------------------ Target Element Check ------------------------------ #
    # def check_target_element(soup):
    #     return any([
    #         soup.find("span", class_="sc-1npzh55-7 hjrnTk"),
    #         soup.find("div", class_="sc-1npzh55-4 cngNqr"),
    #         soup.find("div", class_="sc-fKAtdO llutEA"),
    #         soup.find("div", class_="sc-1gktvoi-0 iWbUMS"),
    #     ])

    # # ------------------------------ Main Page Processor ------------------------------ #
    # async def process_url(browser, start_url):
    #     async with semaphore:
    #         context = await browser.new_context()
    #         page = await context.new_page()
    #         try:
    #             print(f"\n🌐 Visiting start page: {start_url}")
    #             soup = await retry_page_load(page, start_url)
    #             initial_links = extract_links(soup)
    #             print(f"🔗 Found {len(initial_links)} initial links.")

    #             for href, text in initial_links.items():
    #                 full_url = href if href.startswith("http") else BASE_URL + href
    #                 if "product_" in full_url or full_url in processed_links:
    #                     continue

    #                 processed_links.add(full_url)
    #                 extracted_links.add(full_url)
    #                 print(f"➡️ Following: {full_url}")

    #                 try:
    #                     soup = await retry_page_load(page, full_url)

    #                     if check_target_element(soup):
    #                         print(f"✅ Product listing page: {full_url}")
    #                         matched_urls.append(full_url)
    #                         await extract_item_numbers_from_page(page)
    #                         continue
    #                     else:
    #                         print(f"❌ No product listing elements on: {full_url}")

    #                     next_links = extract_links(soup)
    #                     print(f"🔁 Found {len(next_links)} nested links.")
    #                     for nhref in next_links:
    #                         nested_url = nhref if nhref.startswith("http") else BASE_URL + nhref
    #                         if "product_" in nested_url or nested_url in processed_links:
    #                             continue
    #                         processed_links.add(nested_url)
    #                         extracted_links.add(nested_url)

    #                         try:
    #                             print(f"   🔍 Visiting nested: {nested_url}")
    #                             soup = await retry_page_load(page, nested_url)

    #                             if check_target_element(soup):
    #                                 print(f"   ✅ Product listing: {nested_url}")
    #                                 matched_urls.append(nested_url)
    #                                 await extract_item_numbers_from_page(page)
    #                         except Exception as e:
    #                             print(f"   ❌ Nested URL error on {nested_url}: {e}")
    #                             error_urls.append(nested_url)

    #                 except Exception as e:
    #                     print(f"❌ Error visiting {full_url}: {e}")
    #                     error_urls.append(full_url)

    #         except Exception as e:
    #             print(f"❌ Failed to process start URL {start_url}: {e}")
    #             error_urls.append(start_url)

    #         await context.close()

    # # ------------------------------ Main Entry Point ------------------------------ #
    # async def main():
    #     target_urls = [
    #         f"{BASE_URL}/office-supplies/cat_SC273214",
    #         f"{BASE_URL}/coffee-water-snacks/cat_SC215",
    #         f"{BASE_URL}/paper/cat_SC204",
    #         f"{BASE_URL}/cleaning-supplies/cat_SC213",
    #         f"{BASE_URL}/education/cat_SC208",
    #         f"{BASE_URL}/furniture/cat_SC212",
    #         f"{BASE_URL}/computers-accessories/cat_SC202",
    #         f"{BASE_URL}/phones-cameras-electronics/cat_SC285699",
    #         f"{BASE_URL}/printers-scanners/cat_SC216",
    #         f"{BASE_URL}/shipping-packing-mailing-supplies/cat_SC211",
    #         f"{BASE_URL}/facilities/cat_SC400125",
    #         f"{BASE_URL}/safety-supplies/cat_SC90229",
    #     ]

    #     async with async_playwright() as p:
    #         browser = await p.chromium.launch(headless=True)
    #         await asyncio.gather(*(process_url(browser, url) for url in target_urls))
    #         await browser.close()

    #     print("\n✅ Completed all scraping tasks.")
    #     print(f"\n📄 Total matched listing pages: {len(matched_urls)}")
    #     print(f"🛒 Total unique item numbers: {len(item_numbers)}")

    #     df = pd.DataFrame(sorted(item_numbers), columns=["Item Number"])

    #     output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    #     output_path = f"{output_dir}/staples_item_numbers.xlsx"
        
    #     df.to_excel(output_path, index=False)
    #     print("📊 Saved results to 'staples_item_numbers.xlsx'")

    #     if error_urls:
    #         print("\n🚨 Errors encountered on:")
    #         for url in error_urls:
    #             print(f" - {url}")

    # if __name__ == "__main__":
    #     import nest_asyncio
    #     nest_asyncio.apply()
    #     asyncio.get_event_loop().run_until_complete(main())

    # BASE_URL = "https://www.staplesadvantage.com/search"
    # CONCURRENCY_LIMIT = 10
    # MIN_ITEMS_PER_PAGE = 40
    # MAX_RETRIES = 5

    # async def gradual_scroll(page, step=400, delay=1000, max_scrolls=50):
    #     for _ in range(max_scrolls):
    #         await page.evaluate(f"window.scrollBy(0, {step})")
    #         await page.wait_for_timeout(delay)

    # def get_page_url(term, page_number):
    #     return f"{BASE_URL}?pn={page_number}&term={term.replace(' ', '%20')}"

    # async def scrape_page(context, term, page_number, semaphore, attempt=1):
    #     url = get_page_url(term, page_number)
    #     async with semaphore:
    #         print(f"\n🔗 [Page {page_number}] Attempt {attempt} → {url}")
    #         page = await context.new_page()
    #         try:
    #             await page.goto(url)
    #             await page.wait_for_timeout(3000)

    #             print(f"🔃 [Page {page_number}] Scrolling...")
    #             await gradual_scroll(page)

    #             print(f"🔍 [Page {page_number}] Extracting item numbers...")

    #             raw_items = await page.locator("div.list-tile__id_element, div.standard-tile__product_id").all_inner_texts()

    #             extracted = []
    #             for item in raw_items:
    #                 if item.strip().lower().startswith("item"):
    #                     match = re.search(r"(?:Item|SKU|Part)\s*#?\s*[:\-]?\s*([A-Za-z0-9\-]+)", item)
    #                     if match:
    #                         extracted.append(match.group(1))

    #             print(f"✅ [Page {page_number}] Found {len(extracted)} items.")

    #             if len(extracted) < MIN_ITEMS_PER_PAGE and attempt < MAX_RETRIES:
    #                 print(f"⚠️ [Page {page_number}] Less than {MIN_ITEMS_PER_PAGE} items. Retrying (Attempt {attempt + 1})...")
    #                 await page.close()
    #                 return await scrape_page(context, term, page_number, semaphore, attempt + 1)

    #             if len(extracted) < MIN_ITEMS_PER_PAGE:
    #                 print(f"🚫 [Page {page_number}] Final attempt. Still < {MIN_ITEMS_PER_PAGE} items. Ignoring page.")
    #                 extracted = []

    #             for i in extracted:
    #                 print(f"    🧾 Item: {i}")

    #             # ✅ Enhanced last page detection
    #             next_button = page.locator("a[aria-label*='Next page of results']")
    #             has_next = await next_button.count() > 0
    #             is_disabled = False

    #             if has_next:
    #                 aria_disabled = await next_button.get_attribute("aria-disabled")
    #                 aria_label = await next_button.get_attribute("aria-label")
    #                 has_disabled_attr = await next_button.get_attribute("disabled") is not None

    #                 is_disabled = (
    #                     (aria_disabled and aria_disabled.lower() == "true") or
    #                     (aria_label and "disabled" in aria_label.lower()) or
    #                     has_disabled_attr
    #                 )

    #             await page.close()
    #             return {
    #                 "page": page_number,
    #                 "items": extracted,
    #                 "has_next": has_next and not is_disabled
    #             }

    #         except Exception as e:
    #             print(f"❌ [Page {page_number}] Error: {e}")
    #             await page.close()
    #             return {
    #                 "page": page_number,
    #                 "items": [],
    #                 "has_next": False
    #             }

    # async def scrape_items():
    #     async with async_playwright() as p:
    #         browser = await p.chromium.launch(headless=True)
    #         context = await browser.new_context()

    #         term = "ink and toner"
    #         semaphore = asyncio.Semaphore(CONCURRENCY_LIMIT)
    #         all_items = set()
    #         page_number = 1
    #         has_next = True

    #         while has_next:
    #             print(f"\n🚀 Launching batch: Pages {page_number} to {page_number + CONCURRENCY_LIMIT - 1}")
    #             tasks = [scrape_page(context, term, pn, semaphore) for pn in range(page_number, page_number + CONCURRENCY_LIMIT)]
    #             results = await asyncio.gather(*tasks)

    #             for result in results:
    #                 all_items.update(result["items"])

    #             has_next = any(res["has_next"] for res in results)
    #             page_number += CONCURRENCY_LIMIT

    #         await browser.close()

    #         print(f"\n🎉 Total unique items found: {len(all_items)}")
    #         for item in sorted(all_items):
    #             print(f" - {item}")

    #         df = pd.DataFrame(sorted(all_items), columns=["Item Number"])

    #         output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    #         output_path = f"{output_dir}/staples_items.xlsx"

    #         df.to_excel(output_path, index=False)
    #         print("\n📁 Saved to 'staples_items.xlsx'")

    # if __name__ == "__main__":
    #     asyncio.run(scrape_items())
    # Load .env variables
    # load_dotenv()

    # uploaded_file_path = os.getenv("DESTINATION_PATH","/var/www/html/supplier_ds/importdemo/public/excel_sheets")
    output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")

    f1_output_path = f"{output_dir}/staples_item_numbers.xlsx"
    # f2_output_path = file_path
    f3_output_path = f"{output_dir}/staples_items.xlsx"

    # Step 1: Define file paths
    file1 = f1_output_path  # df1
    # file2 = f2_output_path  # df2
    file3 = f3_output_path  # df3 <-- Replace this with the actual filename

    # print(uploaded_file_path, output_dir, file3)
    # exit()

    # Step 2: Load the files
    df1 = pd.read_excel(file1)
    # df2 = pd.read_excel(file2)
    df3 = pd.read_excel(file3)

    # Step 3: Clean column names
    # df2.columns = df2.columns.str.strip()
    df3.columns = df3.columns.str.strip()

    # Step 4: Merge df1 and df2
    merged_df = pd.merge(
        df1,
        sku_data[['sku','value']],
        left_on='Item Number',
        right_on='sku',
        how='left'
    )

    # Drop 'sku' if not needed
    merged_df.drop(columns=['sku'], inplace=True)

    # Step 5: Merge with df3
    final_df = pd.merge(
        merged_df,
        df3,
        on='Item Number',
        how='left'
    )

    # Step 5.5: Rename 'Item Number' to 'Staples SKU' in final output
    final_df.rename(columns={'Item Number': 'sku'}, inplace=True)

    # Step 6: Save merged result
    merged_output_all_files_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    merged_output_all_files_path = f"{merged_output_all_files_dir}/merged_output_all_files.xlsx"

    final_df.to_excel(merged_output_all_files_path, index=False)
    print(f"✅ Final merge complete. File saved as '{merged_output_all_files_path}'.")

    # Load SKUs
    output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")

    df = pd.read_excel(f"{output_dir}/merged_output_all_files.xlsx")
    sku_list = df["sku"].dropna().astype(str).tolist()

    # Constants
    base_url = "https://www.staplesadvantage.com/ele-lpd/api/sba-sku/"
    # Add realistic browser headers
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) "
                    "Chrome/115.0.0.0 Safari/537.36",
        "Accept": "application/json, text/plain, */*",
        "Accept-Language": "en-US,en;q=0.9",
        "Referer": "https://www.staplesadvantage.com/",
        "Connection": "keep-alive",
    }

    # Global counters and locks
    processed_count = 0
    count_lock = Lock()
    refresh_event = Event()
    cookies_lock = Lock()

    # Shared cookies dictionary
    shared_cookies = {}

    def get_fresh_cookies():
        """Get fresh cookies by opening the homepage."""
        s = requests.Session()
        resp = s.get("https://www.staplesadvantage.com/", headers=headers, timeout=15)
        resp.raise_for_status()
        return s.cookies.get_dict()

    # Initialize shared cookies at startup
    shared_cookies = get_fresh_cookies()

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
        # subcategory_3 = hierarchy.get("department", {}).get("name")
        unit_measure_str = product.get("unitOfMeasureComposite", {}).get("unitOfMeasure", "")
        clean_unit = re.sub(r"^\d+/", "", unit_measure_str)
        return {
            "sku": product.get("partNumber"),
            "description": product.get("name"),
            "supplier_shorthand_name":product.get("itemShortDescription"),
            "unit_of_measure": clean_unit,
            "quantity_per_unit": product.get("unitOfMeasureComposite", {}).get("unitOfMeasureQty"),
            "manufacturer_number": product.get("manufacturerPartNumber"),
            "manual_out_of_stock": item.get("manualOutOfStock", False),
            "specifications": {
                spec.get("name"): spec.get("value")
                for spec in product.get("description", {}).get("specification", [])
            },
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
            # "sub_category_3": subcategory_3,
        }

    def fetch_product_data(item_number, retries=2):
        global processed_count, shared_cookies

        for attempt in range(retries + 1):
            try:
                local_session = requests.Session()
                # Thread-safe copy of shared cookies
                with cookies_lock:
                    cookies_copy = shared_cookies.copy()

                api_url = f"{base_url}product_{item_number}?pgIntlO=Y"

                r = local_session.get(api_url, headers=headers, cookies=cookies_copy, timeout=15)

                if r.status_code == 301:
                    # handle redirect json with path field
                    redirect_path = r.json().get("path")
                    if redirect_path:
                        redirected_url = f"{base_url}{redirect_path}"
                        r = local_session.get(redirected_url, headers=headers, cookies=cookies_copy, timeout=15)

                r.raise_for_status()
                data = r.json()
                items = data.get("SBASkuState", {}).get("skuData", {}).get("items", [])
                result = extract_product_info(items)

                if not result.get("sku"):
                    raise ValueError("Empty or missing SKU in response.")
                break  # success
            except Exception as e:
                result = {"SKU": item_number, "Error": str(e)}
                if attempt < retries:
                    time.sleep(2 + attempt)  # exponential backoff-ish
                    continue
                else:
                    break

        with count_lock:
            processed_count += 1

            # Every 500 processed, print status and sleep
            if processed_count % 500 == 0 or processed_count == 1:
                print(f"\nProcessed {processed_count} items. Sleeping for 10 seconds...")
                print(result)
                time.sleep(10)

            # Every 5000 processed, refresh all cookies thread-safely
            if processed_count % 5000 == 0 and not refresh_event.is_set():
                refresh_event.set()
                new_cookies = get_fresh_cookies()
                with cookies_lock:
                    shared_cookies = new_cookies
                print(f"🔄 Refreshed all cookies at count {processed_count}")
                refresh_event.clear()

        return result

    def adding_record_into_database(matched_row, scrap_data):
        try:
            # Connect to MySQL
            conn = mysql.connector.connect(
                host=os.getenv("DB_HOST", "127.0.0.1"),
                user=os.getenv("DB_USERNAME", "roo1"),
                password=os.getenv("DB_PASSWORD", "Password123#@!"),
                database=os.getenv("DB_SECOND_DATABASE", "sp16")
            )

            cursor = conn.cursor(buffered=True)
            ################ product_details_category Start #################
            # Check if the product_details_category exists
            try:
                # Try to fetch the category ID
                cursor.execute(
                    "SELECT `id` FROM `product_details_category` WHERE `category_name` = %s",
                    (matched_row["category"],),
                )
                product_details_category_result = cursor.fetchone()

                if product_details_category_result:
                    # Category exists, get its ID
                    category_id = product_details_category_result[0]
                else:
                    # Insert a new category and get its ID
                    cursor.execute(
                        "INSERT INTO product_details_category (category_name, created_at, updated_at) VALUES (%s, %s, %s)",
                        (
                            matched_row["category"],
                            datetime.now(),
                            datetime.now(),
                        ),
                    )
                    conn.commit()

                    # Fetch last inserted ID
                    cursor.execute("SELECT LAST_INSERT_ID()")
                    category_id = cursor.fetchone()[0]

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table product_details_category Database error: {e}")
                conn.rollback()  # Rollback in case of error
                category_id = None  # Set category_id to None if operation fails

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table product_details_category Unexpected error: {e}")
                category_id = None
            ################ product_details_category End ###################

            ################ product_details_sub_category Start #################
            try:
                # Check if the sub-category exists
                cursor.execute(
                    "SELECT `id` FROM `product_details_sub_category` WHERE `category_id` = %s AND `sub_category_name` = %s",
                    (
                        category_id,
                        matched_row["sub_category"],
                    ),
                )
                sub_category_result = cursor.fetchone()

                if sub_category_result:
                    # Sub-category exists, get its ID
                    sub_category_id = sub_category_result[0]
                else:
                    # Insert a new sub-category
                    cursor.execute(
                        "INSERT INTO `product_details_sub_category` (`category_id`, `sub_category_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s, %s)",
                        (
                            category_id,
                            matched_row["sub_category"],
                            datetime.now(),
                            datetime.now(),
                        ),
                    )
                    conn.commit()

                    # Fetch last inserted ID
                    cursor.execute("SELECT LAST_INSERT_ID()")
                    sub_category_id = cursor.fetchone()[0]

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table product_details_sub_category Database error: {e}")
                conn.rollback()  # Rollback in case of error
                sub_category_id = None  # Ensure it's not left undefined

            except Exception as e:
                log_to_laravel(f"Table product_details_sub_category Unexpected error: {e}")
                print(f"Unexpected error: {e}")
                sub_category_id = None
            ################ product_details_sub_category End ###################

            ################ manufacturers Start #################
            try:
                # Check if the manufacturer exists
                cursor.execute(
                    "SELECT `id` FROM `manufacturers` WHERE `manufacturer_name` = %s",
                    (scrap_data["manufacturer_name"],),
                )
                manufacturer_result = cursor.fetchone()

                if manufacturer_result:
                    # Manufacturer exists, get its ID
                    manufacturer_id = manufacturer_result[0]
                else:
                    # Insert a new manufacturer
                    cursor.execute(
                        "INSERT INTO `manufacturers` (`manufacturer_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s)",
                        (
                            scrap_data["manufacturer_name"],
                            datetime.now(),
                            datetime.now(),
                        ),
                    )
                    conn.commit()

                    # Fetch last inserted ID
                    cursor.execute("SELECT LAST_INSERT_ID()")
                    manufacturer_id = cursor.fetchone()[0]

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table manufacturers Database error: {e}")
                conn.rollback()  # Rollback in case of error
                manufacturer_id = None  # Ensure it's not left undefined

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table manufacturers Unexpected error: {e}")
                manufacturer_id = None
            ################ manufacturers End ###################

            ################ catalog_items Start #################
            try:
                # Check if the catalog item exists
                cursor.execute(
                    "SELECT `id` FROM `catalog_items` WHERE `sku` = %s AND `supplier_id` = %s",
                    (
                        scrap_data["sku"],
                        supplier_id,
                    ),
                )
                catalog_items_result = cursor.fetchone()

                if catalog_items_result:
                    if not greater_date_file_exist:
                        cursor.execute(
                            "UPDATE `catalog_items` SET `active` = %s, `updated_at` = %s WHERE `sku` = %s AND `supplier_id` = %s",
                            (
                                1,
                                datetime.now(),
                                scrap_data["sku"],
                                supplier_id,
                            ),
                        )
                        conn.commit()
                    # catalog_items exists, get its ID
                    catalog_item_id = catalog_items_result[0]
                else:
                    # Insert a new catalog item
                    cursor.execute(
                        """INSERT INTO catalog_items 
                        (
                            sku,
                            supplier_id,
                            active,
                            industry_id,
                            category_id,
                            sub_category_id,
                            manufacturer_id,
                            unit_of_measure,
                            created_at,
                            updated_at,
                            catalog_item_url,
                            catalog_item_name,
                            quantity_per_unit,
                            supplier_shorthand_name,
                            manufacturer_number       
                        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                        (
                            scrap_data["sku"],
                            supplier_id,
                            1 if not greater_date_file_exist else 0,
                            industry_id,
                            category_id,
                            sub_category_id,
                            manufacturer_id,
                            scrap_data.get("unit_of_measure", None),  # Using .get() to avoid KeyError
                            datetime.now(),
                            datetime.now(),
                            scrap_data.get("url", None),  # Using .get() for safety
                            scrap_data.get("description", None),
                            scrap_data.get("quantity_per_unit", None),
                            scrap_data.get("supplier_shorthand_name", None),
                            scrap_data.get("manufacturer_number", None),
                        ),
                    )
                    conn.commit()

                    # Fetch last inserted ID
                    cursor.execute("SELECT LAST_INSERT_ID()")
                    catalog_item_id = cursor.fetchone()[0]

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table catalog_items Database error: {e}")
                conn.rollback()  # Rollback in case of error
                catalog_item_id = None  # Ensure it's not left undefined

            except KeyError as e:
                print(f"Missing key in matched_row or scrap_data: {e}")
                log_to_laravel(f"Table catalog_items missing key in matched_row or scrap_data error: {e}")
                catalog_item_id = None

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table catalog_items Unexpected error: {e}")
                catalog_item_id = None
            ################ catalog_items End ###################

            ################ product_details_common_attributes Start #################
            try:
                for attribute_name in scrap_data.get("specifications", {}):  # Using .get() to avoid KeyError
                    # Check if the product_details_common_attributes exists
                    cursor.execute(
                        "SELECT id FROM product_details_common_attributes WHERE sub_category_id = %s AND attribute_name = %s",
                        (
                            sub_category_id,
                            attribute_name,
                        ),
                    )
                    product_details_common_attributes_result = cursor.fetchone()

                    ################ product_details_common_values Start #################
                    if product_details_common_attributes_result:
                        common_attribute_id = product_details_common_attributes_result[0]

                        # Check if the common value exists, if not, create it
                        cursor.execute(
                            "SELECT id FROM product_details_common_values WHERE value = %s AND catalog_item_id = %s AND common_attribute_id = %s",
                            (
                                scrap_data["specifications"].get(attribute_name, None),  # Using .get() for safety
                                catalog_item_id,
                                common_attribute_id,
                            ),
                        )
                        product_details_common_values = cursor.fetchone()

                        if not product_details_common_values:
                            cursor.execute(
                                """INSERT INTO product_details_common_values 
                                (value, catalog_item_id, common_attribute_id, created_at, updated_at) 
                                VALUES (%s, %s, %s, %s, %s)""",
                                (
                                    scrap_data["specifications"].get(attribute_name, None),  # Using .get() for safety
                                    catalog_item_id,
                                    common_attribute_id,
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                ),
                            )
                            conn.commit()

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table product_details_common_values Database error: {e}")
                conn.rollback()  # Rollback in case of error

            except KeyError as e:
                print(f"Missing key in scrap_data: {e}")
                log_to_laravel(f"Table product_details_common_values missing key in scrap_data error: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table product_details_common_values Unexpected error: {e}")
            ################ product_details_common_values End ###################
            ################ product_details_common_attributes End #################

            ################ product_details_raw_values Start #################
            try:
                # Check if the raw value exists, if not, create it
                cursor.execute(
                    "SELECT id FROM product_details_raw_values WHERE catalog_item_id = %s",
                    (catalog_item_id,),
                )
                product_details_raw_values = cursor.fetchone()

                # Convert dictionary to JSON string safely
                # json_string = json.dumps(scrap_data.get("breadcrumbs", {}), indent=4)  # Using .get() to avoid KeyError
                
                # Get the description safely
                description = scrap_data.get("description", "")

                # HTML-decode it
                description = html.unescape(description)

                # Convert to JSON string (still a string, so no indenting will help here)
                json_string = json.dumps(description)

                if not product_details_raw_values:
                    cursor.execute(
                        """INSERT INTO product_details_raw_values 
                        (catalog_item_id, raw_values, created_at, updated_at) 
                        VALUES (%s, %s, %s, %s)""",
                        (
                            catalog_item_id,
                            json_string,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        ),
                    )
                    conn.commit()

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table product_details_raw_values Database error: {e}")
                conn.rollback()  # Rollback in case of an error

            except KeyError as e:
                print(f"Missing key in scrap_data: {e}")
                log_to_laravel(f"Table product_details_raw_values missing key in scrap_data error: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table product_details_raw_values Unexpected error: {e}")
            ################ product_details_raw_values End ###################

            ################ catalog_prices Start #################
            try:
                # Check if the catalog_prices exists
                cursor.execute(
                    """SELECT id FROM catalog_prices 
                    WHERE catalog_item_id = %s AND catalog_price_type_id = %s""",
                    (
                        catalog_item_id,
                        catalog_price_type_id,
                    ),
                )
                existing_record = cursor.fetchone()

                if existing_record:
                    catalog_price_id = existing_record[0]

                    # If a greater date catalog file does not exist, update the catalog_prices
                    if not greater_date_file_exist:
                        try:
                            cursor.execute(
                                """UPDATE catalog_prices 
                                SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s 
                                WHERE id = %s""",
                                (
                                    1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                    matched_row["value"],
                                    date,
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    1,
                                    catalog_price_id,
                                ),
                            )
                            conn.commit()
                        except mysql.connector.Error as e:
                            print(f"Error updating catalog_prices: {e}")
                            log_to_laravel(f"Error updating catalog_prices: {e}")
                            conn.rollback()  # Rollback in case of an error
                else:
                    # Insert a new catalog_prices
                    try:
                        cursor.execute(
                            """INSERT INTO catalog_prices 
                            (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list) 
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
                            (
                                1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
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
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_prices: {e}")
                        log_to_laravel(f"Table catalog_prices Unexpected error inserting into catalog_prices: {e}")
                        conn.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table catalog_prices Database error: {e}")
                conn.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Table catalog_prices missing key in matched_row: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table catalog_prices Unexpected error: {e}")
            ################ catalog_prices End ###################

            ################ catalog_prices web price Start #################
            try:
                # Check if the catalog_prices exists
                cursor.execute(
                    """SELECT id FROM catalog_prices 
                    WHERE catalog_item_id = %s AND catalog_price_type_id = %s""",
                    (
                        catalog_item_id,
                        3,
                    ),
                )
                existing_record = cursor.fetchone()

                if existing_record:
                    catalog_price_id = existing_record[0]

                    # If a greater date catalog file does not exist, update the catalog_prices
                    if not greater_date_file_exist:
                        try:
                            cursor.execute(
                                """UPDATE catalog_prices 
                                SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s 
                                WHERE id = %s""",
                                (
                                    1,  # Replace with `scrap_data.get("Customer Id", 1)` if needed
                                    scrap_data["web_price"],
                                    date,
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    0,
                                    catalog_price_id,
                                ),
                            )
                            conn.commit()
                        except mysql.connector.Error as e:
                            print(f"Error updating catalog_prices: {e}")
                            log_to_laravel(f"Error updating web_price catalog_prices : {e}")
                            conn.rollback()  # Rollback in case of an error
                else:
                    # Insert a new catalog_prices
                    try:
                        cursor.execute(
                            """INSERT INTO catalog_prices 
                            (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list) 
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
                            (
                                1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
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
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_prices: {e}")
                        log_to_laravel(f"Error inserting into web_price catalog_price: {e}")
                        conn.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error web_price catalog_price: {e}")
                conn.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Missing key in matched_row catalog_price: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Unexpected error web_price catalog_price: {e}")
            ################ catalog_prices web price End ###################

            ################ catalog_price_history Start #################
            try:
                # Check if the record exists
                query = (
                    "SELECT * FROM catalog_price_history WHERE catalog_item_id = %s AND "
                    "catalog_price_type_id = %s AND year = %s LIMIT 1"
                )
                cursor.execute(
                    query,
                    (
                        catalog_item_id,
                        catalog_price_type_id,
                        year,
                    ),
                )
                price_history = cursor.fetchone()

                if price_history:
                    # Update existing record
                    try:
                        update_query = (
                            f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s "
                            "WHERE id = %s"
                        )
                        cursor.execute(
                            update_query,
                            (
                                matched_row["value"],
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                price_history[0],
                            ),
                        )
                        conn.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating catalog_price_history: {e}")
                        log_to_laravel(f"Error updating catalog_price_history: {e}")
                        conn.rollback()  # Rollback in case of an error
                else:
                    # Insert new record
                    try:
                        insert_query = (
                            f"INSERT INTO catalog_price_history (year, created_at, updated_at, catalog_item_id, "
                            f"catalog_price_type_id, {month_column}) VALUES (%s, %s, %s, %s, %s, %s)"
                        )
                        cursor.execute(
                            insert_query,
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
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_price_history: {e}")
                        log_to_laravel(f"Error inserting into catalog_price_history: {e}")
                        conn.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error inserting into catalog_price_history: {e}")
                conn.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Missing key in matched_row: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Unexpected error inserting into catalog_price_history: {e}")
            ################ catalog_price_history End ###################

            ################ catalog_price_history web price Start #################
            try:
                # Check if the record exists
                query = (
                    "SELECT * FROM catalog_price_history WHERE catalog_item_id = %s AND "
                    "catalog_price_type_id = %s AND year = %s LIMIT 1"
                )
                cursor.execute(
                    query,
                    (
                        catalog_item_id,
                        3,
                        year,
                    ),
                )
                price_history = cursor.fetchone()

                if price_history:
                    # Update existing record
                    try:
                        update_query = (
                            f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s "
                            "WHERE id = %s"
                        )
                        cursor.execute(
                            update_query,
                            (
                                scrap_data["web_price"],
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                price_history[0],  # Fixed incorrect placeholder value (was `0`)
                            ),
                        )
                        conn.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating catalog_price_history: {e}")
                        log_to_laravel(f"Error updating web_price catalog_price_history: {e}")
                        conn.rollback()  # Rollback in case of an error
                else:
                    # Insert new record
                    try:
                        insert_query = (
                            f"INSERT INTO catalog_price_history (year, created_at, updated_at, catalog_item_id, "
                            f"catalog_price_type_id, {month_column}) VALUES (%s, %s, %s, %s, %s, %s)"
                        )
                        cursor.execute(
                            insert_query,
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
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_price_history: {e}")
                        log_to_laravel(f"Error inserting web_price catalog_price_history: {e}")
                        conn.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error web_price catalog_price_history: {e}")
                log_to_laravel(f"Database error web_price catalog_price_history: {e}")
                conn.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row web_price catalog_price_history: {e}")
                log_to_laravel(f"Missing key in matched_row web_price catalog_price_history: {e}")

            except Exception as e:
                print(f"Unexpected error web_price catalog_price_history: {e}")
                log_to_laravel(f"Unexpected error web_price catalog_price_history: {e}")
            ################ catalog_price_history web price End ###################

            ################ check_core_history Start #################
            try:
                # Check if the check_core_history exists
                query = (
                    "SELECT * FROM check_core_history WHERE catalog_item_id = %s AND "
                    "catalog_price_type_id = %s LIMIT 1"
                )
                cursor.execute(
                    query,
                    (
                        catalog_item_id,
                        catalog_price_type_id,
                    ),
                )
                check_core_history = cursor.fetchone()

                core_list_value = 1

                if check_core_history:
                    # If greater date catalog file does not exist, update month data
                    if not greater_date_file_exist:
                        try:
                            update_query = (
                                "UPDATE check_core_history SET updated_at = %s, price_file_date = %s, core_list = %s "
                                "WHERE id = %s"
                            )
                            cursor.execute(
                                update_query,
                                (
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    date,
                                    core_list_value,
                                    check_core_history[0],
                                ),
                            )
                            conn.commit()
                        except mysql.connector.Error as e:
                            print(f"Error updating check_core_history: {e}")
                            log_to_laravel(f"Error updating check_core_history: {e}")
                            conn.rollback()  # Rollback on error
                else:
                    # Insert new check_core_history
                    try:
                        insert_query = (
                            "INSERT INTO check_core_history (customer_id, catalog_item_id, price_file_date, created_at, "
                            "updated_at, catalog_price_type_id, core_list) VALUES (%s, %s, %s, %s, %s, %s, %s)"
                        )
                        cursor.execute(
                            insert_query,
                            (
                                1,  # Replace with `matched_row["Customer Id"]` if needed
                                catalog_item_id,
                                date,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                catalog_price_type_id,
                                core_list_value,
                            ),
                        )
                        conn.commit()
                    except mysql.connector.Error as e:
                        print(f"Error inserting into check_core_history: {e}")
                        log_to_laravel(f"Error inserting into check_core_history: {e}")
                        conn.rollback()  # Rollback on error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error updating check_core_history: {e}")
                conn.rollback()

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Missing key in matched_row check_core_history: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Unexpected error updating check_core_history: {e}")
            ################ check_core_history End ###################
            
        finally:
            cursor.close()
            conn.close()  # Always close the connection to prevent memory leaks

    try:
        # Connect to MySQL
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_SECOND_DATABASE", "sp16")
        )

        cursor = conn.cursor()

        print(supplier_id)
        print("✅ Updating active status and resetting core_list flags...")

        # Begin transaction (optional but recommended)
        cursor.execute("BEGIN;")

        # 1. Update catalog_items
        cursor.execute("""
            UPDATE catalog_items
            SET active = 0
            WHERE supplier_id = %s;
        """, (supplier_id,))

        # 2. Update catalog_prices
        cursor.execute("""
            UPDATE catalog_prices
            SET core_list = 0
            WHERE catalog_item_id IN (
                SELECT id FROM catalog_items WHERE supplier_id = %s
            );
        """, (supplier_id,))

        # 3. Update check_core_history
        cursor.execute("""
            UPDATE check_core_history
            SET core_list = 0
            WHERE catalog_item_id IN (
                SELECT id FROM catalog_items WHERE supplier_id = %s
            );
        """, (supplier_id,))

        # Commit the transaction
        conn.commit()

        print("✅ All updates completed successfully.")
    except mysql.connector.Error as e:
        print(f"Database error: {e}")
        log_to_laravel(f"Database error updating check_core_history: {e}")
        conn.rollback()
    finally:
        cursor.close()
        conn.close()  # Always close the connection to prevent memory leaks

    # Multithreaded execution
    results = []
    with ThreadPoolExecutor(max_workers=5) as executor:  # adjust max_workers as needed
        futures = [executor.submit(fetch_product_data, sku) for sku in sku_list]
        for future in tqdm(as_completed(futures), total=len(futures), desc="Processing items"):
            result = future.result()

            sku_result = result.get("sku")
            if not sku_result:
                print("Missing 'sku' in result:", result)
                log_to_laravel(f"Missing 'sku' in result: {result}")
                continue
            
            # Select the row where 'sku' matches the search term
            record = df.loc[df["sku"] == sku_result]

            # Drop columns where all values are NaN (None is treated as NaN in pandas)
            record = record.dropna(axis=1, how='all')

            # Drop columns that are literally named 'None' (as a string)
            record = record.loc[:, ~record.columns.astype(str).str.contains("^None$", na=False)]

            if not record.empty:
                matched_row = record.to_dict(orient="records")[0]  # Convert to dictionary for database insert

                if not matched_row.get('value'):
                    web_price = result.get("web_price")

                    if web_price is not None:
                        matched_row["value"] = round(web_price * (1 - 0.02), 2)
                    else:
                        matched_row["value"] = 0

                        print(f"Missing web_price for SKU: {result.get('sku')}")
                        log_to_laravel(f"Missing web_price for SKU: {result.get('sku')}")
                        continue

                try:
                    conn5 = mysql.connector.connect(
                        host=os.getenv("DB_HOST", "127.0.0.1"),
                        user=os.getenv("DB_USERNAME", "roo1"),
                        password=os.getenv("DB_PASSWORD", "Password123#@!"),
                        database=os.getenv("DB_SECOND_DATABASE", "sp16")
                    )
                    cursor5 = conn5.cursor()

                    if all(k in result for k in ("category", "sub_category_1", "sub_category_2")):
                        query = """
                        SELECT * FROM temp_category
                        WHERE category = %s AND subcategory_1 = %s AND subcategory_2 = %s
                        """
                        params = (result["category"], result["sub_category_1"], result["sub_category_2"])
                        cursor5.execute(query, params)
                        odp_category_result = cursor5.fetchone()
                        
                        if odp_category_result:
                            matched_row["category"] = odp_category_result[4]
                            matched_row["sub_category"] = odp_category_result[5]
                        else:
                            matched_row["category"] = result["category"]
                            matched_row["sub_category"] = result["sub_category_1"]

                        print(matched_row["category"],matched_row["sub_category"])
                    else:
                        print(f"Missing category fields in result for SKU {result.get('sku')}")
                        log_to_laravel(f"Missing category fields in result for SKU {result.get('sku')}")
                        continue

                    # Validate required fields before inserting
                    required_fields = ['category', 'sub_category']
                    missing_fields = [f for f in required_fields if not matched_row.get(f)]
                    if missing_fields:
                        print(f"Missing fields {missing_fields} for SKU {matched_row.get('sku')}")
                        log_to_laravel(f"Missing fields {missing_fields} for SKU {matched_row.get('sku')}")
                        continue

                    adding_record_into_database(matched_row, result)

                except mysql.connector.Error as e:
                    print(f"Database error: {e}")
                    log_to_laravel(f"Database error: {e}")
                    conn.rollback()

                except Exception as e:
                    print(f"Unexpected error: {e}")
                    log_to_laravel(f"Unexpected error: {e}")

                finally:
                    if 'cursor' in locals(): cursor5.close()
                    conn5.close()

    # Save results to Excel
    output_df = pd.DataFrame(results)
    output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    output_path = f"{output_dir}/staples_product_data.xlsx"
    output_df.to_excel(output_path, index=False)
    print("✅ All data saved to 'staples_product_data.xlsx'")

# Step 7: Delete df1 and df3 files
# try:
#     os.remove(file1)
#     print(f"🗑️ Deleted file: {file1}")
# except FileNotFoundError:
#     print(f"⚠️ File not found: {file1}")

# try:
#     os.remove(file3)
#     print(f"🗑️ Deleted file: {file3}")
# except FileNotFoundError:
#     print(f"⚠️ File not found: {file3}")
