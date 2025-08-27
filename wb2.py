# play_parallel_wbmason.py
# Usage:
#   pip install playwright openpyxl pandas tqdm
#   playwright install
#
#   WB_USERNAME="your-user" WB_PASSWORD="your-pass" \
#   INPUT_FILE="skus.xlsx" CONCURRENCY=6 SAVE_HTML=0 HEADFUL=0 \
#   python play_parallel_wbmason.py
#
# Input Excel format (header required, case-insensitive):
#   ItemID, UOM
#   AAC45015Y, CT
#   ABC12345, EA
#
# Outputs:
#   out_products.jsonl  (one JSON per line, streamed)
#   out_products.csv    (flat table, second pass)
#   raw_{sku}_{uom}.html (if SAVE_HTML=1)

import os
import re
import csv
import json
import asyncio
from pathlib import Path
from typing import Dict, Any, List, Optional, Tuple

import pandas as pd
from playwright.async_api import async_playwright, TimeoutError as PWTimeoutError, Error as PWError

# --- progress bar (tqdm) ---
try:
    from tqdm import tqdm  # type: ignore
except ImportError:
    class tqdm:  # minimal fallback
        def __init__(self, total=0, desc=""): pass
        def update(self, n=1): pass
        def close(self): pass

LOGIN_URL = (
    "https://login.wbmason.com/Account/Login?ReturnUrl=%2Fconnect%2Fauthorize%2Fcallback%3Fclient_id%3DecomClient%26"
    "response_type%3Dcode%26scope%3Dopenid%2520api1%2520profile%2520offline_access%26redirect_uri%3Dhttps%253A%252F%252Fwww.wbmason.com%252F"
    "AccountLoading.aspx%253Fr%253Dhttps%253A%252F%252Fwww.wbmason.com%26state%3Dc775e7b757ede630cd0aa1113bd102661ab38829ca52a6422ab782862f268646%257C"
    "https%253A%252F%252Fwww.wbmason.com%252FAccountLoading.aspx%253Fr%253Dhttps%253A%252F%252Fwww.wbmason.com%257Cstd%26nonce%3D9mLw7c3sV7lkpHIAHGKk8ugccYzWn%252BPs038%252BCOKluzassAj0%252Bdt%252FoW%252FKmnCXPuzwle2tPLQjAKgZkIBJak8NRw%253D%253D%26prompt%3Dlogin%26response_mode%3Dform_post%26"
    "code_challenge%3D51FaJvQFsiNdiFWIq2EMWUKeAqD47dqU_cHzJpfHl-Q%26code_challenge_method%3DS256%26Dgl%3DTrue%26Drl%3DTrue%26se%3DFalse%26ku%3DTrue%26suppressed_prompt%3Dlogin"
)
BASE_PD_URL = "https://www.wbmason.com/pd/{sku}?uom={uom}"

# --- Config (use env vars; do NOT hardcode secrets) ---
USERNAME = os.getenv("WB_USERNAME", "reporting@centerpointgroup.com")
PASSWORD = os.getenv("WB_PASSWORD", "7!QkFiXtDm!XvQ5")
INPUT_FILE = os.getenv("INPUT_FILE", "wb catalog.xlsx")
CONCURRENCY = int(os.getenv("CONCURRENCY", "6"))            # conservative default
SAVE_HTML = bool(int(os.getenv("SAVE_HTML", "0")))          # 0/1 -> False/True
HEADFUL = bool(int(os.getenv("HEADFUL", "0")))              # default headless for stability

OUT_JSONL = "out_products.jsonl"
OUT_CSV = "out_products.csv"


def build_url(sku: str, uom: str) -> str:
    return BASE_PD_URL.format(sku=sku.strip(), uom=uom.strip())


async def login_and_get_state(browser) -> Dict[str, Any]:
    """Log in once and return storage_state dict for reuse."""
    context = await browser.new_context()
    page = await context.new_page()

    await page.goto(LOGIN_URL, wait_until="domcontentloaded")

    if not USERNAME or not PASSWORD:
        raise SystemExit("Set WB_USERNAME and WB_PASSWORD env vars.")

    await page.fill("#userNameInput", USERNAME)
    await page.fill("#passwordInput", PASSWORD)

    try:
        await page.check("#rememberLogin")
    except Exception:
        pass

    await page.wait_for_function(
        """() => {
            const btn = document.querySelector('#btnLogin');
            return btn && !btn.disabled;
        }""",
        timeout=15000,
    )
    await page.click("#btnLogin")

    # Let auth flow settle; then touch main domain for cookie scope.
    for _ in (15000, 10000):
        try:
            await page.wait_for_load_state("networkidle", timeout=_)
            break
        except PWTimeoutError:
            pass
    await page.goto("https://www.wbmason.com", wait_until="domcontentloaded")
    try:
        await page.wait_for_load_state("networkidle", timeout=8000)
    except PWTimeoutError:
        pass

    state = await context.storage_state()
    await context.close()
    return state


async def first_text(page, selector: str, timeout: int = 8000) -> Optional[str]:
    try:
        loc = page.locator(selector).first
        await loc.wait_for(state="visible", timeout=timeout)
        txt = await loc.inner_text()
        return txt.strip()
    except Exception:
        return None


async def all_texts(page, selector: str) -> List[str]:
    try:
        locs = page.locator(selector)
        cnt = await locs.count()
        out: List[str] = []
        for i in range(cnt):
            try:
                out.append((await locs.nth(i).inner_text()).strip())
            except Exception:
                pass
        return out
    except Exception:
        return []


async def extract_specs(page) -> Dict[str, str]:
    """
    Ensures the 'Product Details' accordion is expanded, then reads the
    alternating key/value spans from the grid.
    """
    specs: Dict[str, str] = {}
    container = page.locator('[data-testid="product-about-this-item-product-details"]').first

    # If container isn't on the page, bail early.
    try:
        await container.wait_for(state="visible", timeout=5000)
    except Exception:
        return specs

    # Grid that holds the spec cells; class names are hashed so match loosely.
    grid = container.locator('.wb-grid, [class^="_grid_"]').first

    # If grid isn't visible yet, the section is likely collapsed; click header to expand.
    try:
        await grid.wait_for(state="visible", timeout=1500)
    except Exception:
        header = container.locator('.wb-pointer').first
        try:
            await header.click(timeout=2000)
            await grid.wait_for(state="visible", timeout=3000)
        except Exception:
            return specs  # couldn't expand

    # Collect all label/value spans; they appear in alternating order.
    spans = grid.locator('span._primary_wgnoi_32._line-height-large_wgnoi_6')
    try:
        count = await spans.count()
    except Exception:
        count = 0

    items: List[str] = []
    for i in range(count):
        try:
            txt = (await spans.nth(i).inner_text()).strip()
            if txt:
                items.append(txt)
        except Exception:
            pass

    # Convert [k0, v0, k1, v1, ...] -> dict
    for i in range(0, len(items), 2):
        k = items[i] if i < len(items) else None
        v = items[i + 1] if i + 1 < len(items) else None
        if k and v:
            specs[k] = v

    return specs


async def extract_product_fields(page) -> Dict[str, Any]:
    """Extract fields directly from the DOM (Playwright-only)."""
    # Name & SKU
    name = await first_text(page, '[data-testid="product-name"]')
    sku = await first_text(page, '[data-testid="product-id"]')

    # Price (first available) -> quick selector pass; fallback to regex scan
    prices = await all_texts(page, '[data-testid="product-details-price-price"]')
    price = next((p for p in prices if p), None)
    if not price:
        try:
            body_text = await page.locator("body").inner_text()
            m = re.search(r"\$\s*\d[\d,]*\.?\d*", body_text)
            price = m.group(0) if m else None
        except Exception:
            price = None

    # Specs from expandable 'Product Details' section
    specs = await extract_specs(page)

    # Category (text starts with "View more ...")
    category = None
    try:
        cat_loc = page.locator("span", has_text=re.compile(r"^View more", re.I)).first
        await cat_loc.wait_for(state="visible", timeout=5000)
        cat_text = (await cat_loc.inner_text()).strip()
        m = re.match(r"(?i)^view more\s+(.*)$", cat_text)
        category = m.group(1) if m else cat_text
    except PWTimeoutError:
        pass

    return {
        "name": name,
        "sku": sku,
        "price": price,
        "specifications": specs,
        "category": category,
    }


async def is_product_not_found(page) -> Tuple[bool, Optional[str]]:
    """
    Ultra-fast negative check to avoid expensive scraping when the product page
    is a 'not found' or soft-404 template.
    """
    # 1) Explicit banner/message
    try:
        nf = page.locator(
            "span._h2_wgnoi_172._line-height-normal_wgnoi_1.wb-margin-bottom-10",
            has_text=re.compile(r"^Sorry, we couldn't find", re.I)
        ).first
        await nf.wait_for(state="visible", timeout=1500)
        return True, "product_not_found"
    except Exception:
        pass

    # 2) Quick body text scan (very short timeout)
    try:
        body = await page.locator("body").inner_text(timeout=1500)
        if re.search(r"(product not found|we couldn'?t find)", body, re.I):
            return True, "product_not_found"
    except Exception:
        pass

    return False, None


# -------------------
# Page Pool for reuse (resilient)
# -------------------
class PagePool:
    def __init__(self, context, queue: asyncio.Queue):
        self._ctx = context
        self._q = queue

    @classmethod
    async def create(cls, context, size: int):
        size = max(1, size)
        q: asyncio.Queue = asyncio.Queue(maxsize=size)
        for _ in range(size):
            p = await context.new_page()
            try:
                await p.set_default_timeout(25000)
                await p.set_default_navigation_timeout(25000)
            except Exception:
                pass
            p._use_count = 0  # type: ignore[attr-defined]
            await q.put(p)
        return cls(context, q)

    async def acquire(self):
        page = await self._q.get()
        try:
            if page.is_closed():
                page = await self._new_page()
        except Exception:
            page = await self._new_page()
        return page

    async def _new_page(self):
        p = await self._ctx.new_page()
        try:
            await p.set_default_timeout(25000)
            await p.set_default_navigation_timeout(25000)
        except Exception:
            pass
        p._use_count = 0  # type: ignore[attr-defined]
        return p

    async def recycle(self, page):
        try:
            if not page.is_closed():
                await page.close()
        except Exception:
            pass
        return await self._new_page()

    async def release(self, page):
        try:
            if page.is_closed():
                page = await self._new_page()
            else:
                try:
                    await page.goto("about:blank", wait_until="domcontentloaded", timeout=5000)
                except Exception:
                    page = await self.recycle(page)

            # Periodic recycle to avoid renderer bloat
            page._use_count = getattr(page, "_use_count", 0) + 1  # type: ignore[attr-defined]
            if page._use_count >= 500:  # tune as needed
                page = await self.recycle(page)
        except Exception:
            page = await self._new_page()

        await self._q.put(page)

    async def close_all(self):
        pages = []
        while not self._q.empty():
            pages.append(self._q.get_nowait())
        for p in pages:
            try:
                await p.close()
            except Exception:
                pass


async def fetch_one_on_page(page, sku: str, uom: str) -> Dict[str, Any]:
    """
    Uses a *reused* page object from a pool; does not create/close the page.
    """
    url = build_url(sku, uom)
    result: Dict[str, Any] = {"input_sku": sku, "input_uom": uom, "url": url}

    try:
        # Clear to about:blank to avoid leftover state impacting nav
        try:
            await page.goto("about:blank", wait_until="domcontentloaded", timeout=5000)
        except Exception:
            pass

        response = await page.goto(url, wait_until="domcontentloaded", timeout=25000)

        # If redirected to login (session expired, etc.)
        if "login.wbmason.com/Account/Login" in page.url:
            result["status"] = "error"
            result["error"] = "login_redirect"
            return result

        # Fast HTTP-level not found
        if response and response.status in (404, 410):
            result["status"] = "not_found"
            result["error"] = f"http_{response.status}"
            return result

        # Small breath to let client-side template render
        await page.wait_for_timeout(400)

        # DOM-level not found checks (fast)
        nf, reason = await is_product_not_found(page)
        if nf:
            result["status"] = "not_found"
            result["error"] = reason
            return result

        # Optionally save raw HTML before any heavy DOM scraping (cheap)
        if SAVE_HTML:
            Path(f"raw_{sku}_{uom}.html").write_text(await page.content(), encoding="utf-8")
            result["raw_html_path"] = f"raw_{sku}_{uom}.html"

        # Extract structured fields
        fields = await extract_product_fields(page)
        result.update(fields)
        result["status"] = "ok"
    except Exception as e:
        result["status"] = "error"
        result["error"] = repr(e)
    return result


def read_input_rows(path: str) -> List[Tuple[str, str]]:
    """
    Reads an Excel file with headers 'ItemID' and 'UOM' (case-insensitive)
    and returns a list of (sku, uom) tuples (deduped, order-preserving).
    """
    df = pd.read_excel(path, engine="openpyxl")
    # Normalize column names for matching (lowercase, trimmed)
    df.columns = [str(c).strip().lower() for c in df.columns]

    if not {"itemid", "uom"}.issubset(df.columns):
        raise ValueError("Input Excel must have columns 'ItemID' and 'UOM' (case-insensitive).")

    df = df[["itemid", "uom"]].rename(columns={"itemid": "sku", "uom": "uom"}).dropna(how="any")

    rows: List[Tuple[str, str]] = []
    for _, r in df.iterrows():
        sku = str(r["sku"]).strip()
        uom = str(r["uom"]).strip()
        if sku and uom and sku.lower() != "nan" and uom.lower() != "nan":
            rows.append((sku, uom))

    # Deduplicate while preserving order (avoids redundant calls)
    seen = set()
    deduped: List[Tuple[str, str]] = []
    for pair in rows:
        if pair not in seen:
            seen.add(pair)
            deduped.append(pair)
    return deduped


def jsonl_to_csv(jsonl_path: str, csv_path: str):
    """
    Two-pass conversion without loading all rows into memory:
    Pass 1: discover spec_* keys
    Pass 2: write CSV
    """
    base_cols = ["input_sku", "input_uom", "url", "name", "sku", "price", "category", "status", "error"]

    # Pass 1: collect spec keys
    spec_keys = set()
    with open(jsonl_path, "r", encoding="utf-8") as jf:
        for line in jf:
            if not line.strip():
                continue
            r = json.loads(line)
            specs = r.get("specifications") or {}
            if isinstance(specs, dict):
                for k in specs.keys():
                    spec_keys.add(f"spec_{k}")

    fieldnames = base_cols + sorted(spec_keys)

    # Pass 2: write CSV
    with open(jsonl_path, "r", encoding="utf-8") as jf, open(csv_path, "w", newline="", encoding="utf-8") as cf:
        wr = csv.DictWriter(cf, fieldnames=fieldnames)
        wr.writeheader()
        for line in jf:
            if not line.strip():
                continue
            r = json.loads(line)
            row = {k: r.get(k) for k in base_cols}
            specs = r.get("specifications") or {}
            if isinstance(specs, dict):
                for k, v in specs.items():
                    row[f"spec_{k}"] = v
            wr.writerow(row)


async def run_streaming(items: List[Tuple[str, str]], page_pool: PagePool, progress_desc="Fetching products"):
    """
    Stream items through a bounded number of workers.
    Writes JSONL incrementally to avoid OOM. Retries once per item on Playwright errors.
    """
    q: asyncio.Queue = asyncio.Queue()
    for pair in items:
        q.put_nowait(pair)

    jf = open(OUT_JSONL, "w", encoding="utf-8", buffering=1)  # line-buffered
    progress = tqdm(total=len(items), desc=progress_desc)
    progress_lock = asyncio.Lock()

    async def one_worker():
        while True:
            try:
                sku, uom = await q.get()
            except asyncio.CancelledError:
                break

            page = await page_pool.acquire()
            try:
                try:
                    r = await fetch_one_on_page(page, sku, uom)
                except (PWError, PWTimeoutError):
                    # recycle page and retry once on a fresh page
                    await page_pool.recycle(page)
                    page = await page_pool.acquire()
                    r = await fetch_one_on_page(page, sku, uom)

                jf.write(json.dumps(r, ensure_ascii=False) + "\n")
            finally:
                await page_pool.release(page)
                q.task_done()
                # update progress
                async with progress_lock:
                    progress.update(1)

    workers = [asyncio.create_task(one_worker()) for _ in range(CONCURRENCY)]
    await q.join()
    for w in workers:
        w.cancel()
    progress.close()
    jf.close()


async def main():
    if not USERNAME or not PASSWORD:
        raise SystemExit("Set WB_USERNAME and WB_PASSWORD env vars.")

    items = read_input_rows(INPUT_FILE)
    if not items:
        raise SystemExit(f"No rows found in {INPUT_FILE}. Need columns: ItemID, UOM")

    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=not HEADFUL,
            args=[
                "--disable-dev-shm-usage",  # stability in Docker/WSL
                "--no-sandbox",             # often necessary in CI containers
            ],
        )

        # Login once; reuse authenticated storage state
        auth_state = await login_and_get_state(browser)

        # Reuse ONE context for all workers (faster + lighter)
        context = await browser.new_context(storage_state=auth_state)

        # Block heavy assets globally for the context
        async def route_handler(route):
            r = route.request
            try:
                if r.resource_type in ("image", "media", "font"):
                    await route.abort()
                else:
                    await route.continue_()
            except Exception:
                # if route blows up for any reason, try to continue
                try:
                    await route.continue_()
                except Exception:
                    try:
                        await route.abort()
                    except Exception:
                        pass

        await context.route("**/*", route_handler)

        # Create a pool of persistent pages (tabs)
        pool_size = CONCURRENCY if CONCURRENCY > 0 else 5
        page_pool = await PagePool.create(context, size=pool_size)

        # STREAM the work instead of spawning 30k tasks
        await run_streaming(items, page_pool, progress_desc="Fetching products")

        # Clean up pool pages and context/browser
        await page_pool.close_all()
        await context.close()
        await browser.close()

    # Convert JSONL -> CSV (two-pass, low memory)
    jsonl_to_csv(OUT_JSONL, OUT_CSV)

    # Quick summary (streamed, so recount here)
    ok = 0
    total = 0
    with open(OUT_JSONL, "r", encoding="utf-8") as jf:
        for line in jf:
            if not line.strip():
                continue
            total += 1
            try:
                r = json.loads(line)
                if r.get("status") == "ok":
                    ok += 1
            except Exception:
                pass
    err = total - ok
    print(f"\nDone. {ok} succeeded, {err} failed.")
    print(f"JSONL: {OUT_JSONL}")
    print(f"CSV:   {OUT_CSV}")
    if SAVE_HTML:
        print("Raw HTML saved per item as raw_{sku}_{uom}.html")


if __name__ == "__main__":
    asyncio.run(main())
