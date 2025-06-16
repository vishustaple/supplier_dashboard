import asyncio
from playwright.async_api import async_playwright
import time
import re
from tqdm import tqdm

CONCURRENCY_LIMIT = 10

async def extract_links_from_brand(semaphore, context, brand_link):
    links = []
    async with semaphore:
        brand_page = await context.new_page()
        try:
            print(f"\n🔗 Visiting brand: {brand_link}")
            await brand_page.goto(brand_link)
            await brand_page.wait_for_timeout(2000)

            tab_buttons = await brand_page.query_selector_all("div[role='tab']")
            time.sleep(2)

            for tab in tab_buttons:
                tab_text = await tab.inner_text()
                if "VIEW ALL" in tab_text.upper():
                    #print(f"🟦 Clicking tab: {tab_text}")
                    try:
                        await tab.click()
                        await brand_page.wait_for_timeout(2000)

                        matching_links = await brand_page.eval_on_selector_all(
                            "div[id^='link-'].sc-izol8d-0.ippIIi a, div[id^='short-name-link-'].sc-izol8d-0.ippIIi a",
                            "els => els.map(el => el.getAttribute('href'))"
                        )

                        clean_links = [
                            href if href.startswith("http") else f"https://www.staplesadvantage.com{href}"
                            for href in matching_links if href
                        ]

                        links.extend(clean_links)
                        print(f"  ➤ Found {len(clean_links)} product links")

                    except Exception as e:
                        continue
                        #print(f"⚠️ Failed to click tab '{tab_text}': {e}")
        except Exception as e:
            print(f"❌ Error visiting brand link {brand_link}: {e}")
        finally:
            await brand_page.close()
    return links


async def run():
    all_links = []
    semaphore = asyncio.Semaphore(CONCURRENCY_LIMIT)

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context()
        page = await context.new_page()

        url = "https://www.staplesadvantage.com/printer-ink-cartridges-toner-finder/cat_SC43"
        await page.goto(url)
        await page.click("#view-all-brands-link")
        await page.wait_for_selector("div[id^='brand-name-'] a")

        brand_links = await page.eval_on_selector_all(
            "div[id^='brand-name-'] a",
            "elements => elements.map(el => el.href)"
        )

        tasks = [
            extract_links_from_brand(semaphore, context, brand_link)
            for brand_link in brand_links
        ]

        results = await asyncio.gather(*tasks)
        for link_list in results:
            all_links.extend(link_list)

        await browser.close()
        

    print(f"\n✅ Total extracted links: {len(all_links)}")
    return all_links

# Run the async function
# all_links = await run()
all_links = asyncio.run(run())

item_numbers = set()
CONCURRENCY_LIMIT = 30

async def gradual_scroll(page):
    for i in range(0, 1000, 200):
        await page.evaluate(f"window.scrollBy(0, {i});")
        await page.wait_for_timeout(100)

async def extract_item_numbers_from_page(page):
    page_item_numbers = set()

    try:
        while True:
            await gradual_scroll(page)

            # Extract from .standard-tile__product_id
            try:
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
            except:
                pass

            # Extract from .list-tile__id_element
            try:
                elements = await page.locator(".list-tile__id_element").all()
                for el in elements:
                    spans = await el.locator("span").all_inner_texts()
                    for i, t in enumerate(spans):
                        if "Item" in t and i + 1 < len(spans):
                            item = spans[i + 1].strip()
                            if item not in item_numbers:
                                item_numbers.add(item)
                                page_item_numbers.add(item)
            except:
                pass

            #await asyncio.sleep(2)

            # Pagination
            try:
                next_button = page.locator("a[aria-label^='Next page']")
                if await next_button.count() == 0:
                    break  # No next button found
            
                disabled_attr = await next_button.get_attribute("aria-disabled")
                if disabled_attr == "true":
                    break  # Next button is disabled
            
                await next_button.click()
                await page.wait_for_timeout(2000)
            except Exception as e:
                break

    except Exception as e:
        pass

    return page_item_numbers

async def process_link(semaphore, context, link, pbar):
    page_item_numbers = set()
    async with semaphore:
        page = await context.new_page()
        try:
            await page.goto(link, timeout=30000)
            page_item_numbers = await extract_item_numbers_from_page(page)
        except Exception as e:
            pass
        await page.close()
    
    pbar.update(1)  # Update progress bar after processing the link
    return page_item_numbers

async def run(all_links):
    semaphore = asyncio.Semaphore(CONCURRENCY_LIMIT)

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context()

        # Initialize the progress bar
        with tqdm(total=len(all_links), desc="Processing Links") as pbar:
            tasks = [process_link(semaphore, context, link, pbar) for link in all_links]
            results = await asyncio.gather(*tasks)

            # Update item_numbers with all the extracted results
            for result in results:
                item_numbers.update(result)

        await browser.close()

    # Final progress bar print
    print(f"\n🎯 Total unique item numbers extracted: {len(item_numbers)}")
    print(item_numbers)

# Example use:
# all_links = ["https://www.staplesadvantage.com/8300/CL90000014504", ...]
# await run(all_links)
# all_links = asyncio.run(run())
asyncio.run(run(all_links))