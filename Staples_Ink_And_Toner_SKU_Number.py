import asyncio
import re
import time
from playwright.async_api import async_playwright
from tqdm import tqdm
import pandas as pd

CONCURRENCY_LIMIT_BRAND = 10
CONCURRENCY_LIMIT_ITEMS = 30

USERNAME = "centerpointstaples"
PASSWORD = "q*pETby5!YH_Xcr"

item_numbers = set()

async def login_staples():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True, slow_mo=100)
        context = await browser.new_context()
        page = await context.new_page()

        print("Navigating to login page...")
        await page.goto("https://www.staplesadvantage.com/idm", timeout=60000)

        # TrustArc iframe cookie consent
        try:
            print("Waiting for TrustArc iframe to load...")
            await page.wait_for_selector("iframe.truste_popframe", timeout=15000)
            frame_locator = page.frame_locator("iframe.truste_popframe")
            xpath_accept = "/html/body/div[8]/div[1]/div/div[4]/a[1]"
            await frame_locator.locator(f"xpath={xpath_accept}").wait_for(timeout=10000)

            previous_url = page.url
            await frame_locator.locator(f"xpath={xpath_accept}").click()
            print("Clicked 'Accept Cookies'")

            try:
                print("Waiting for page URL to change after cookie acceptance...")
                await page.wait_for_function(f"window.location.href !== '{previous_url}'", timeout=15000)
            except:
                print("URL did not change, waiting for load event instead...")
                await page.wait_for_load_state("load", timeout=15000)
            print("Page reload (or navigation) complete after accepting cookies.")
        except Exception as e:
            print(f"No TrustArc iframe or failed to click accept button: {e}")

        # Enter username & password
        await page.wait_for_selector("#userId", timeout=15000)
        await page.fill("#userId", USERNAME)
        await page.click("button#Next")
        print("Entered username and clicked Next")

        await page.wait_for_selector("#password", timeout=15000)
        await page.fill("#password", PASSWORD)
        print("Entered password")

        # Click 'Keep me signed in'
        try:
            checkbox = await page.query_selector('input[type="checkbox"]')
            if checkbox and not await checkbox.is_checked():
                await checkbox.check()
                print("Checked the checkbox input")
            else:
                await page.locator("div.sc-1nqal5c-3").click()
                print("Clicked checkbox container as fallback")
        except Exception as e:
            print(f"Could not check 'Keep me signed in': {e}")

        # Click sign-in button and wait for URL change
        previous_url = page.url
        try:
            await page.get_by_role("button", name="Sign in").click()
            print("Clicked 'Sign in'")
        except:
            await page.click("button#Sign\\ in")
            print("Clicked 'Sign in' fallback button")

        try:
            print("Waiting for URL to change after sign-in...")
            await page.wait_for_function("window.location.href !== arguments[0]", previous_url, timeout=30000)
            print("Login completed and URL changed.")
        except:
            print("Timeout waiting for URL change after login; proceeding anyway.")

        # --- ADD: verify redirect URL starts with sahome ---
        current_url = page.url
        if current_url.startswith("https://www.staplesadvantage.com/sahome"):
            print(f"✅ Redirect verified: {current_url}")
            cookies = await context.cookies()
            user_agent = await page.evaluate("() => navigator.userAgent")
        else:
            print(f"❌ Redirect URL not as expected: {current_url}")
            cookies = []
            user_agent = ""

        await page.screenshot(path="logged_in.png")
        print("Screenshot taken after login.")

        await browser.close()
        return cookies, user_agent

async def extract_links_from_brand(semaphore, context, brand_link):
    links = []
    async with semaphore:
        brand_page = await context.new_page()
        try:
            print(f"\n🔗 Visiting brand: {brand_link}")
            await brand_page.goto(brand_link)
            await brand_page.wait_for_timeout(2000)

            tab_buttons = await brand_page.query_selector_all("div[role='tab']")
            await asyncio.sleep(2)

            for tab in tab_buttons:
                tab_text = await tab.inner_text()
                if "VIEW ALL" in tab_text.upper():
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
                        pass
        except Exception as e:
            print(f"❌ Error visiting brand link {brand_link}: {e}")
        finally:
            await brand_page.close()
    return links

async def gradual_scroll(page):
    for i in range(0, 1000, 200):
        await page.evaluate(f"window.scrollBy(0, {i});")
        await page.wait_for_timeout(100)

async def extract_item_numbers_from_page(page):
    page_item_numbers = set()

    try:
        while True:
            await gradual_scroll(page)

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

            try:
                next_button = page.locator("a[aria-label^='Next page']")
                if await next_button.count() == 0:
                    break
                disabled_attr = await next_button.get_attribute("aria-disabled")
                if disabled_attr == "true":
                    break
                await next_button.click()
                await page.wait_for_timeout(2000)
            except:
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

    pbar.update(1)
    return page_item_numbers

async def main():
    print("Logging in to get cookies and user-agent...")
    cookies, user_agent = await login_staples()

    semaphore_brand = asyncio.Semaphore(CONCURRENCY_LIMIT_BRAND)
    semaphore_items = asyncio.Semaphore(CONCURRENCY_LIMIT_ITEMS)

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)

        # Create context with cookies and custom headers including User-Agent
        context = await browser.new_context()
        await context.add_cookies(cookies)
        await context.set_extra_http_headers({
            "User-Agent": user_agent,
            "Accept-Language": "en-US,en;q=0.9",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "Referer": "https://www.staplesadvantage.com/"
        })

        page = await context.new_page()
        url = "https://www.staplesadvantage.com/printer-ink-cartridges-toner-finder/cat_SC43"
        await page.goto(url)
        await page.click("#view-all-brands-link")
        await page.wait_for_selector("div[id^='brand-name-'] a")

        brand_links = await page.eval_on_selector_all(
            "div[id^='brand-name-'] a",
            "elements => elements.map(el => el.href)"
        )
        await page.close()

        print(f"Found {len(brand_links)} brand links")

        # Extract product links from brands concurrently
        tasks_brand = [
            extract_links_from_brand(semaphore_brand, context, brand_link)
            for brand_link in brand_links
        ]
        brand_results = await asyncio.gather(*tasks_brand)

        all_product_links = []
        for res in brand_results:
            all_product_links.extend(res)

        print(f"\n✅ Total extracted product links: {len(all_product_links)}")

        # Extract item numbers from product links concurrently with progress bar
        with tqdm(total=len(all_product_links), desc="Processing Product Links") as pbar:
            tasks_items = [
                process_link(semaphore_items, context, link, pbar)
                for link in all_product_links
            ]
            item_results = await asyncio.gather(*tasks_items)

        # Merge all item numbers extracted
        for res in item_results:
            item_numbers.update(res)

        await browser.close()

    print(f"\n🎯 Total unique item numbers extracted: {len(item_numbers)}")
    df = pd.DataFrame(sorted(item_numbers), columns=["Item Number"])
    df.to_excel("staples_item_numbers.xlsx", index=False)
    print("✅ Item numbers saved to staples_item_numbers.xlsx")

if __name__ == "__main__":
    import nest_asyncio
    nest_asyncio.apply()
    asyncio.get_event_loop().run_until_complete(main())
