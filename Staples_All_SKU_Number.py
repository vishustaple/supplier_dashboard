import asyncio
import re
import os
from playwright.async_api import async_playwright
from bs4 import BeautifulSoup
import pandas as pd

semaphore = asyncio.Semaphore(5)
matched_urls = []
error_urls = []
processed_links = set()
extracted_links = set()
item_numbers = set()

def extract_links(soup):
    link_tags = []

    link_tags.extend(soup.find_all("a", class_=lambda x: x and any(cls.startswith("link-box") for cls in x.split())))
    link_tags.extend(soup.find_all("a", class_=lambda x: x and any(cls.startswith("popular-category") for cls in x.split())))
    link_tags.extend(soup.find_all("a", class_=lambda x: x and (
        "seo-component__seoLink" in x or
        "popular-category-card__HomePageAnchorDiv" in x
    )))
    for container in soup.find_all("div", class_="link-box__listBoxWrapper"):
        link_tags.extend(container.find_all("a", href=True))
    for div in soup.find_all("div", class_="link-box__appendExtraHeight"):
        a_tag = div.find("a", href=True)
        if a_tag:
            link_tags.append(a_tag)
    link_tags.extend(soup.find_all("a", class_=lambda x: x and "link-box" in x))
    for div in soup.find_all("div", class_=lambda x: x and "popular-categories__PopularCategoriesContainer" in x):
        a_tag = div.find("a", href=True)
        if a_tag:
            link_tags.append(a_tag)
    for div in soup.find_all("div", class_=lambda x: x and "sc-1u3l2ng-6" in x):
        a_tag = div.find("a", href=True)
        if a_tag:
            href = a_tag["href"]
            if href not in link_tags:
                link_tags.append(a_tag)

    unique_links = {}
    for tag in link_tags:
        href = tag.get("href")
        if href and href not in unique_links:
            span = tag.find("span")
            text = span.get_text(strip=True) if span else tag.get_text(strip=True)
            unique_links[href] = text

    return unique_links

def check_target_element(soup):
    return (
        soup.find("span", class_="sc-1npzh55-7 hjrnTk") or
        soup.find("div", class_="sc-1npzh55-4 cngNqr") or
        soup.find("div", class_="sc-fKAtdO llutEA") or
        soup.find("div", class_="sc-1gktvoi-0 iWbUMS")
    )

async def scroll_and_get_soup(page):
    scroll_pause_time = 1
    scroll_increment = 500

    last_scroll_position = await page.evaluate("window.pageYOffset")
    total_height = await page.evaluate("document.body.scrollHeight")

    while last_scroll_position + await page.evaluate("window.innerHeight") < total_height:
        await page.evaluate(f"window.scrollBy(0, {scroll_increment})")
        await asyncio.sleep(scroll_pause_time)
        last_scroll_position = await page.evaluate("window.pageYOffset")
        total_height = await page.evaluate("document.body.scrollHeight")

    html = await page.content()
    return BeautifulSoup(html, "html.parser")

async def gradual_scroll(page, scroll_step=300, delay=0.3):
    previous_height = await page.evaluate("() => document.body.scrollHeight")
    current_position = 0
    while current_position < previous_height:
        current_position += scroll_step
        await page.evaluate(f"window.scrollTo(0, {current_position})")
        await asyncio.sleep(delay)
        previous_height = await page.evaluate("() => document.body.scrollHeight")
    await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")

async def extract_item_numbers_from_page(page):
    print("   🕵️ Extracting item numbers from product listing page...")
    page_item_numbers = set()

    try:
        while True:
            await gradual_scroll(page)
            await page.wait_for_selector(".standard-tile__product_id_wrapper", timeout=15000)

            items = await page.locator(".standard-tile__product_id").all_inner_texts()
            for text in items:
                if "Item:" in text:
                    match = re.search(r"Item:\s*([\w-]+)", text)
                    if match:
                        item = match.group(1)
                        if item not in item_numbers:
                            item_numbers.add(item)
                            page_item_numbers.add(item)

            next_button = page.locator("a[aria-label^='Next page']")
            disabled = await next_button.get_attribute("aria-disabled")
            if disabled == "true":
                break
            await next_button.click()
            await page.wait_for_timeout(2000)

    except Exception as e:
        print(f"   ⚠️ Warning during item extraction: {e}")

    print(f"   ✅ Extracted {len(page_item_numbers)} new item numbers on this page.")

async def retry_page_load(page, url, retries=2, wait=2):
    for attempt in range(retries + 1):
        try:
            await page.goto(url, wait_until="domcontentloaded")
            await asyncio.sleep(wait)
            return await scroll_and_get_soup(page)
        except Exception as e:
            if attempt < retries:
                print(f"   🔁 Retry {attempt+1}/{retries} for {url} due to: {e}")
                await asyncio.sleep(wait)
            else:
                print(f"   ❌ Failed after {retries+1} attempts for {url}")
                raise e

async def process_url(browser, start_url):
    async with semaphore:
        context = await browser.new_context()
        page = await context.new_page()
        try:
            print(f"\n🌐 Starting on: {start_url}")
            soup = await retry_page_load(page, start_url)
            initial_links = extract_links(soup)
            print(f"🔗 Found {len(initial_links)} initial links on start page.")

            for href, text in initial_links.items():
                full_url = href if href.startswith("http") else "https://www.staplesadvantage.com" + href
                if "product_" in full_url or full_url in processed_links:
                    continue
                processed_links.add(full_url)
                extracted_links.add(full_url)

                try:
                    print(f"🌍 Visiting: {full_url}")
                    soup = await retry_page_load(page, full_url)

                    if check_target_element(soup):
                        print(f"✅ Found product listing page: {full_url}")
                        matched_urls.append(full_url)
                        await extract_item_numbers_from_page(page)
                        print(f"   → Total matched so far: {len(matched_urls)}")
                        continue
                    else:
                        print(f"❎ No target element found on: {full_url}")

                    next_links = extract_links(soup)
                    for nhref in next_links:
                        full_nhref = nhref if nhref.startswith("http") else "https://www.staplesadvantage.com" + nhref
                        if "product_" in full_nhref or full_nhref in processed_links:
                            continue
                        processed_links.add(full_nhref)
                        extracted_links.add(full_nhref)

                        try:
                            print(f"🌍 Visiting nested: {full_nhref}")
                            soup = await retry_page_load(page, full_nhref)

                            if check_target_element(soup):
                                print(f"✅ Found product listing page: {full_nhref}")
                                matched_urls.append(full_nhref)
                                await extract_item_numbers_from_page(page)
                                print(f"   → Total matched so far: {len(matched_urls)}")
                            else:
                                print(f"❎ No target element found on: {full_nhref}")

                        except Exception as e:
                            print(f"❌ Error visiting {full_nhref}: {e}")
                            error_urls.append(full_nhref)

                except Exception as e:
                    print(f"❌ Error visiting {full_url}: {e}")
                    error_urls.append(full_url)

        except Exception as e:
            print(f"❌ Failed to load {start_url}: {e}")
            error_urls.append(start_url)

        await context.close()

async def main():
    target_urls = [
        "https://www.staplesadvantage.com/office-supplies/cat_SC273214",
        "https://www.staplesadvantage.com/coffee-water-snacks/cat_SC215",
        "https://www.staplesadvantage.com/paper/cat_SC204",
        "https://www.staplesadvantage.com/cleaning-supplies/cat_SC213",
        "https://www.staplesadvantage.com/education/cat_SC208",
        "https://www.staplesadvantage.com/furniture/cat_SC212",
        "https://www.staplesadvantage.com/computers-accessories/cat_SC202",
        "https://www.staplesadvantage.com/phones-cameras-electronics/cat_SC285699",
        "https://www.staplesadvantage.com/printers-scanners/cat_SC216",
        "https://www.staplesadvantage.com/shipping-packing-mailing-supplies/cat_SC211",
        "https://www.staplesadvantage.com/facilities/cat_SC400125",
        "https://www.staplesadvantage.com/safety-supplies/cat_SC90229"
    ]

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        tasks = [process_url(browser, url) for url in target_urls]
        await asyncio.gather(*tasks)
        await browser.close()

    print("\n✅ Finished all URLs")

    print("\n📄 Matched URLs:")
    for url in matched_urls:
        print(url)

    print("\n📝 Total unique item numbers extracted:", len(item_numbers))

    df = pd.DataFrame(sorted(item_numbers), columns=["Item Number"])

    output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    output_path = f"{output_dir}/staples_item_numbers.xlsx"
    
    df.to_excel(output_path, index=False)
    print(f"📊 Saved item numbers to '{output_path}'")

    print("\n🚨 Error URLs:")
    for url in error_urls:
        print(url)

if __name__ == "__main__":
    import nest_asyncio
    nest_asyncio.apply()
    asyncio.get_event_loop().run_until_complete(main())
