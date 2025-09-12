# main.py
import json
from db_insert import final_insert
from db_insert import bootstrap_from_db
from data_scraper import get_product_data
from staples_item_numbers import get_item_numbers

# Bootstrap DB + file/headers
boot = bootstrap_from_db()
if not boot:
    print("Nothing to process (no cron=11 file).")
    raise SystemExit(0)

sku_data = boot["sku_data"]

results = get_item_numbers()
result_list = (
    results.squeeze("columns")                  # collapse 1-col DF -> Series
           .astype(str)                         # ensure string
           .str.replace(r"\.0$", "", regex=True)  # fix Excel '13944.0' cases
           .str.strip()                         # trim whitespace
           .dropna()
           .drop_duplicates()
           .tolist()
)

df, json_records = get_product_data(result_list)

final_insert(df=df, boot=boot)

# If you also want a JSON string:
json_str = json.dumps(json_records, ensure_ascii=False)

# (Optional) Write to a .json file:
with open("staples_product_data.json", "w", encoding="utf-8") as f:
    json.dump(json_records, f, ensure_ascii=False, indent=2)

print("Extracted", len(results["item_numbers"]), "unique item numbers.")
