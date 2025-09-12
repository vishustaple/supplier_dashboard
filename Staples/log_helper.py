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

# ──────────────────────────────────────────────────────────────────────────────
# Helpers: logging, human-like input
# ──────────────────────────────────────────────────────────────────────────────
def log_to_laravel(message: str):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open(LOG_FILE, "a") as f:
        f.write(f"[{timestamp}] local.ERROR: {message}\n")