import os
from datetime import datetime
from dotenv import load_dotenv

# ──────────────────────────────────────────────────────────────────────────────
# Environment & constants
# ──────────────────────────────────────────────────────────────────────────────
load_dotenv()

LOG_FILE = os.getenv("CUSTOM_LOG_PATH", "/var/www/html/supplier_ds/importdemo/storage/logs/laravel.log")

# ──────────────────────────────────────────────────────────────────────────────
# Helpers: logging, human-like input
# ──────────────────────────────────────────────────────────────────────────────
def log_to_laravel(message: str):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open(LOG_FILE, "a") as f:
        f.write(f"[{timestamp}] local.ERROR: {message}\n")