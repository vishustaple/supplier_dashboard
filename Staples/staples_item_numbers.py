import time
import random
import re
import json
import asyncio
from pathlib import Path
from typing import Dict, Set, List, Tuple

from playwright.sync_api import sync_playwright
from playwright.async_api import async_playwright, Page, BrowserContext
from bs4 import BeautifulSoup
import pandas as pd

# ------------------------- Your original constants (login left untouched) -------------------------
LOGIN_URL = "https://www.staplesadvantage.com/idm"
USERNAME = "centerpointstaples"
PASSWORD = "q*pETby5!YH_Xcr"
STATE_PATH = "staples_storage_state.json"  # session handoff file


# ------------------------- Category crawl targets -------------------------
TARGET_URLS = [
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
    "https://www.staplesadvantage.com/safety-supplies/cat_SC90229",
]

# ------------------------- Search crawl config (your provided code integrated) -------------------------
BASE_URL = "https://www.staplesadvantage.com/search"
SEARCH_TERMS = ["ink and toner"]  # you can add more terms here
CONCURRENCY_LIMIT = 5          # total workers; tabs won’t sit idle
MIN_ITEMS_PER_PAGE = 40
MAX_RETRIES = 2
# Terms that should NOT enforce the MIN_ITEMS_PER_PAGE threshold
NO_MIN_THRESHOLD_TERMS = {"ink and toner"}

# ------------------------- Shared collections -------------------------
matched_urls: List[str] = []
error_urls: List[str] = []
processed_links: Set[str] = set()
extracted_links: Set[str] = set()
item_numbers: Set[str] = set()

# =========================================================
# ============== HTML / parsing helper functions ==========
# =========================================================
def extract_links(soup: BeautifulSoup) -> Dict[str, str]:
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
        if a_tag and a_tag not in link_tags:
            link_tags.append(a_tag)

    unique_links: Dict[str, str] = {}
    for tag in link_tags:
        href = tag.get("href")
        if href and href not in unique_links:
            span = tag.find("span")
            text = span.get_text(strip=True) if span else tag.get_text(strip=True)
            unique_links[href] = text
    return unique_links

def check_target_element(soup: BeautifulSoup) -> bool:
    return (
        soup.find("span", class_="sc-1npzh55-7 hjrnTk") or
        soup.find("div", class_="sc-1npzh55-4 cngNqr") or
        soup.find("div", class_="sc-fKAtdO llutEA") or
        soup.find("div", class_="sc-1gktvoi-0 iWbUMS")
    ) is not None

# =========================================================
# ================== Your original helpers ================
# =========================================================
def human_type(page, selector, text):
    for char in text:
        page.type(selector, char, delay=random.uniform(100, 200))
    page.wait_for_timeout(random.uniform(300, 700))

def human_mouse_move(page):
    box = page.query_selector('body').bounding_box()
    x0, y0 = box['width'] / 2, box['height'] / 2
    page.mouse.move(x0, y0)
    for _ in range(5):
        page.mouse.move(
            x0 + random.uniform(-100, 100),
            y0 + random.uniform(-100, 100),
            steps=10
        )
        page.wait_for_timeout(random.uniform(200, 500))

# =========================================================
# ==================== UNCHANGED LOGIN ====================
# =========================================================
def login_with_captcha_solver():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled"])
        context = browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64)...Chrome/139.0.0.0 Safari/537.36",
            locale="en-US",
        )
        page = context.new_page()
        page.add_init_script("try { delete Object.getPrototypeOf(navigator).webdriver; } catch {}")

        page.goto(LOGIN_URL)
        human_mouse_move(page)

        try:
            # Wait for TrustArc iframe
            frame = page.frame_locator("iframe.truste_popframe")
            # Target the actual button
            frame.get_by_role("button", name="Agree and Proceed").click()
            print("✅ Clicked Agree and Proceed")
        except Exception as e:
            print("⚠️ Cookie popup not found or already dismissed:", e)

        time.sleep(10)
        human_type(page, 'input[name="userId"]', USERNAME)
        page.click('button#Next')
        page.wait_for_selector('input[name="password"]')
        human_type(page, 'input[name="password"]', PASSWORD)

        # solver = CaptchaSolver(api_key="YOUR_2CAPTCHA_KEY")
        # solver.solve(page)  # Auto-detect and solve CAPTCHA if present

        page.click('button:has-text("Sign in")')

        # ------- URL-based success check -------
        try:
            page.wait_for_url(
                re.compile(r"^https://www\.staplesadvantage\.com/sahome\?billTo=[^&]+&shipTo=[^&]+(&onLoad=hideMessage)?"),
                timeout=60000
            )
            print("✅ Reached SA Home; login confirmed by URL.")
        except Exception:
            print(f"ℹ️ Current URL after sign-in: {page.url}")
            page.wait_for_load_state("domcontentloaded")
            try:
                page.wait_for_url(
                    re.compile(r"^https://www\.staplesadvantage\.com/sahome\?billTo=[^&]+&shipTo=[^&]+"),
                    timeout=15000
                )
                print("✅ Reached SA Home on second check.")
            except Exception:
                print("⚠️ SA Home URL not confirmed; proceeding anyway (cookies should be set).")

        # Persist storage state so the async crawler can reuse the SAME session
        context.storage_state(path=STATE_PATH)
        print(f"💾 Saved storage state to {STATE_PATH}")

        # Keep the original window alive while we crawl (optional)
        return browser, context, page

# =========================================================
# ============== Async helpers (category crawl) ===========
# =========================================================
async def a_gradual_scroll(page: Page, scroll_step=200, delay=0.4):
    previous_height = await page.evaluate("() => document.body.scrollHeight")
    current_position = 0
    while current_position < previous_height:
        current_position += scroll_step
        await page.evaluate(f"window.scrollTo(0, {current_position})")
        await asyncio.sleep(delay)
        previous_height = await page.evaluate("() => document.body.scrollHeight")
    await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")

async def a_scroll_and_get_soup(page: Page) -> BeautifulSoup:
    scroll_pause_time = 1
    scroll_increment = 300

    last_scroll_position = await page.evaluate("() => window.pageYOffset")
    total_height = await page.evaluate("() => document.body.scrollHeight")

    while (last_scroll_position + await page.evaluate("() => window.innerHeight")) < total_height:
        await page.evaluate(f"window.scrollBy(0, {scroll_increment})")
        await asyncio.sleep(scroll_pause_time)
        last_scroll_position = await page.evaluate("() => window.pageYOffset")
        total_height = await page.evaluate("() => document.body.scrollHeight")

    html = await page.content()
    return BeautifulSoup(html, "html.parser")

async def a_retry_page_load(page: Page, url: str, retries=5, wait=2) -> BeautifulSoup:
    for attempt in range(retries + 1):
        try:
            await page.goto(url, wait_until="domcontentloaded")
            await asyncio.sleep(wait)
            return await a_scroll_and_get_soup(page)
        except Exception as e:
            if attempt < retries:
                print(f"   🔁 Retry {attempt+1}/{retries} for {url} due to: {e}")
                await asyncio.sleep(wait)
            else:
                print(f"   ❌ Failed after {retries+1} attempts for {url}")
                raise

async def a_extract_item_numbers_from_page(page: Page):
    print("   🕵️ Extracting item numbers from product listing page...")
    page_item_numbers: Set[str] = set()
    try:
        while True:
            await a_gradual_scroll(page)
            await page.wait_for_selector(".standard-tile__product_id_wrapper", timeout=15000)

            texts = await page.locator(".standard-tile__product_id").all_inner_texts()
            for text in texts:
                if "Item:" in text:
                    m = re.search(r"Item:\s*([\w-]+)", text)
                    if m:
                        item = m.group(1)
                        if item not in item_numbers:
                            item_numbers.add(item)
                            page_item_numbers.add(item)

            next_button = page.locator("a[aria-label^='Next page']")
            if await next_button.count() == 0:
                break
            disabled = await next_button.get_attribute("aria-disabled")
            if disabled == "true":
                break
            await next_button.click()
            await page.wait_for_timeout(2000)
    except Exception as e:
        print(f"   ⚠️ Warning during item extraction: {e}")
    print(f"   ✅ Extracted {len(page_item_numbers)} new item numbers on this page.")

async def a_process_category(page: Page, start_url: str):
    try:
        print(f"\n🌐 [CAT] Start: {start_url}")
        soup = await a_retry_page_load(page, start_url)
        initial_links = extract_links(soup)
        print(f"🔗 [CAT] Found {len(initial_links)} links on start.")

        for href, _ in initial_links.items():
            full_url = href if href.startswith("http") else "https://www.staplesadvantage.com" + href
            if "product_" in full_url or full_url in processed_links:
                continue
            processed_links.add(full_url)
            extracted_links.add(full_url)

            try:
                print(f"🌍 [CAT] Visiting: {full_url}")
                soup2 = await a_retry_page_load(page, full_url)

                if check_target_element(soup2):
                    print(f"✅ [CAT] Listing page: {full_url}")
                    matched_urls.append(full_url)
                    await a_extract_item_numbers_from_page(page)
                    print(f"   → [CAT] Total matched so far: {len(matched_urls)}")
                    continue
                else:
                    print(f"❎ [CAT] No target element on: {full_url}")

                next_links = extract_links(soup2)
                for nhref in next_links:
                    full_nhref = nhref if nhref.startswith("http") else "https://www.staplesadvantage.com" + nhref
                    if "product_" in full_nhref or full_nhref in processed_links:
                        continue
                    processed_links.add(full_nhref)
                    extracted_links.add(full_nhref)

                    try:
                        print(f"🌍 [CAT] Visiting nested: {full_nhref}")
                        soup3 = await a_retry_page_load(page, full_nhref)

                        if check_target_element(soup3):
                            print(f"✅ [CAT] Listing page: {full_nhref}")
                            matched_urls.append(full_nhref)
                            await a_extract_item_numbers_from_page(page)
                            print(f"   → [CAT] Total matched so far: {len(matched_urls)}")
                        else:
                            print(f"❎ [CAT] No target element on: {full_nhref}")

                    except Exception as e:
                        print(f"❌ [CAT] Error visiting {full_nhref}: {e}")
                        error_urls.append(full_nhref)

            except Exception as e:
                print(f"❌ [CAT] Error visiting {full_url}: {e}")
                error_urls.append(full_url)

    except Exception as e:
        print(f"❌ [CAT] Failed start {start_url}: {e}")
        error_urls.append(start_url)

# =========================================================
# =========== Async helpers (SEARCH pages you added) ======
# =========================================================
def get_page_url(term: str, page_number: int) -> str:
    return f"{BASE_URL}?pn={page_number}&term={term.replace(' ', '%20')}"

async def a_gradual_scroll_fixed(page: Page, step=400, delay=1000, max_scrolls=50):
    for _ in range(max_scrolls):
        await page.evaluate(f"window.scrollBy(0, {step})")
        await page.wait_for_timeout(delay)

async def a_process_search_page(page: Page, term: str, page_number: int) -> Dict:
    url = get_page_url(term, page_number)
    print(f"\n🔗 [SRCH {term}] Page {page_number} → {url}")
    attempt = 1
    term_no_min = term.strip().lower() in NO_MIN_THRESHOLD_TERMS

    while attempt <= MAX_RETRIES:
        try:
            await page.goto(url)
            await page.wait_for_timeout(3000)
            print(f"🔃 [SRCH {term}] Page {page_number} scrolling...")
            await a_gradual_scroll_fixed(page)

            raw_items = await page.locator("div.list-tile__id_element, div.standard-tile__product_id").all_inner_texts()

            extracted: List[str] = []
            for item in raw_items:
                if item.strip().lower().startswith("item"):
                    match = re.search(r"(?:Item|SKU|Part)\s*#?\s*[:\-]?\s*([A-Za-z0-9\-]+)", item)
                    if match:
                        extracted.append(match.group(1))

            print(f"✅ [SRCH {term}] Page {page_number} found {len(extracted)} items.")

            # Only enforce minimum threshold if the term is NOT in the allow-list
            if not term_no_min and len(extracted) < MIN_ITEMS_PER_PAGE and attempt < MAX_RETRIES:
                print(f"⚠️ [SRCH {term}] Page {page_number} < {MIN_ITEMS_PER_PAGE}. Retry {attempt+1}/{MAX_RETRIES}...")
                attempt += 1
                continue

            if not term_no_min and len(extracted) < MIN_ITEMS_PER_PAGE:
                print(f"🚫 [SRCH {term}] Page {page_number} final attempt < {MIN_ITEMS_PER_PAGE}. Ignoring page.")
                extracted = []
            elif term_no_min and len(extracted) < MIN_ITEMS_PER_PAGE:
                print(f"ℹ️ [SRCH {term}] Page {page_number} below min, but keeping results as requested.")

            for i in extracted:
                item_numbers.add(i)  # merge directly to global set

            next_button = page.locator("a[aria-label*='Next page of results']")
            has_next = await next_button.count() > 0
            is_disabled = False
            if has_next:
                aria_disabled = await next_button.get_attribute("aria-disabled")
                aria_label = await next_button.get_attribute("aria-label")
                has_disabled_attr = await next_button.get_attribute("disabled") is not None
                is_disabled = (
                    (aria_disabled and aria_disabled.lower() == "true") or
                    (aria_label and "disabled" in aria_label.lower()) or
                    has_disabled_attr
                )
            return {"term": term, "page": page_number, "has_next": has_next and not is_disabled}

        except Exception as e:
            print(f"❌ [SRCH {term}] Page {page_number} error: {e}")
            attempt += 1

    return {"term": term, "page": page_number, "has_next": False}

# =========================================================
# =========== Unified 10-worker pool & scheduler ==========
# =========================================================
async def run_async_crawl(storage_state_path: str, category_urls: List[str], search_terms: List[str], concurrency: int = CONCURRENCY_LIMIT):
    work_q: asyncio.Queue = asyncio.Queue()
    search_result_q: asyncio.Queue = asyncio.Queue()

    # Enqueue category tasks
    for url in category_urls:
        await work_q.put({"type": "category", "url": url})

    # Search control state (batching per term like your code: pages 1..10, then 11..20 if any had next)
    search_state = {
        term: {"next_batch_start": 1, "pages_in_batch": set(), "awaiting": 0, "more": True}
        for term in search_terms
    }

    # Enqueue initial search batches
    for term in search_terms:
        start = search_state[term]["next_batch_start"]
        pages = list(range(start, start + concurrency))
        search_state[term]["pages_in_batch"] = set(pages)
        search_state[term]["awaiting"] = len(pages)
        search_state[term]["next_batch_start"] += concurrency
        for pn in pages:
            await work_q.put({"type": "search", "term": term, "page_number": pn})

    async with async_playwright() as ap:
        browser = await ap.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled"])
        context = await browser.new_context(storage_state=storage_state_path, locale="en-US")

        # Each worker owns ONE tab and stays busy until no tasks remain (no idle tabs)
        async def worker(worker_id: int):
            page = await context.new_page()
            try:
                while True:
                    try:
                        task = await asyncio.wait_for(work_q.get(), timeout=3.0)
                    except asyncio.TimeoutError:
                        # If all search terms finished and no category tasks remain, exit
                        if all(not st["more"] and st["awaiting"] == 0 for st in search_state.values()) and work_q.empty():
                            break
                        else:
                            continue

                    if task["type"] == "category":
                        await a_process_category(page, task["url"])
                    elif task["type"] == "search":
                        res = await a_process_search_page(page, task["term"], task["page_number"])
                        await search_result_q.put(res)
                        # mark batch progress
                        st = search_state[task["term"]]
                        if task["page_number"] in st["pages_in_batch"]:
                            st["awaiting"] -= 1
                    else:
                        pass
                    work_q.task_done()
            finally:
                await page.close()

        # A manager monitors search batches and enqueues next batches when needed
        async def search_manager():
            # For each term, keep batching until a full batch returns with no "has_next"
            active_terms = set(search_terms)
            while active_terms:
                res = await search_result_q.get()
                term = res["term"]
                st = search_state[term]
                # Track if any page in this batch reports has_next
                st.setdefault("batch_has_next", False)
                if res.get("has_next"):
                    st["batch_has_next"] = True

                # When all pages in current batch are processed, decide next
                if st["awaiting"] == 0:
                    if st["batch_has_next"]:
                        # enqueue next batch
                        start = st["next_batch_start"]
                        pages = list(range(start, start + CONCURRENCY_LIMIT))
                        st["pages_in_batch"] = set(pages)
                        st["awaiting"] = len(pages)
                        st["next_batch_start"] += CONCURRENCY_LIMIT
                        st["batch_has_next"] = False
                        for pn in pages:
                            await work_q.put({"type": "search", "term": term, "page_number": pn})
                    else:
                        # No more pages for this term
                        st["more"] = False
                        active_terms.discard(term)

        # Start workers and search manager
        workers = [asyncio.create_task(worker(i)) for i in range(concurrency)]
        mgr = asyncio.create_task(search_manager())

        # Wait for all tasks to finish
        await asyncio.gather(*workers)
        # Manager may still be waiting; cancel it safely
        if not mgr.done():
            mgr.cancel()
            with contextlib.suppress(asyncio.CancelledError):
                await mgr

        await context.close()
        await browser.close()

# =========================================================
# ===================== Main orchestration =================
# =========================================================
def get_item_numbers():
    # 1) Login (unchanged) and persist state
    # NOTE: login_with_captcha_solver() uses a `with` block, so returned page/context/browser
    # are already invalid by the time we get here.
    _, _, _ = login_with_captcha_solver()  # ignore returned handles

    # 2) Kick off unified async crawl with 10-way concurrency using the same session
    try:
        asyncio.run(run_async_crawl(STATE_PATH, TARGET_URLS, SEARCH_TERMS, concurrency=CONCURRENCY_LIMIT))
    finally:
        print("\n✅ Finished all work")
        print("\n📄 Matched category listing URLs:")
        for url in matched_urls:
            print(url)
        
        print("\n📝 Total unique item numbers extracted:", len(item_numbers))
        df = pd.DataFrame(sorted(item_numbers), columns=["Item Number"])
        output_path = "staples_item_numbers.xlsx"
        df.to_excel(output_path, index=False)
        print(f"📊 Saved all item numbers to '{output_path}'")
        return df

# Needed for manager cancellation
import contextlib

if __name__ == "__main__":
    get_item_numbers()
