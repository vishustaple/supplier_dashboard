import random
import time
from playwright.sync_api import sync_playwright

LOGIN_URL = "https://www.staplesadvantage.com/idm"
USERNAME = "centerpointstaples"
PASSWORD = "q*pETby5!YH_Xcr"
POST_LOGIN_SELECTOR = 'class.sc-1kcmyi2-4 ouRFd'

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

def login_with_captcha_solver():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, args=["--disable-blink-features=AutomationControlled"])
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
        page.click('button:has-text("Sign in")')
        page.wait_for_selector(POST_LOGIN_SELECTOR, timeout=30000)
        print("Login succeeded with CAPTCHA handling!")
        print(context.cookies())
        browser.close()

print(login_with_captcha_solver())