import os
import html
import json
import pandas as pd
import mysql.connector
from datetime import datetime
from dotenv import load_dotenv
from log_helper import log_to_laravel

# ──────────────────────────────────────────────────────────────────────────────
# Environment & constants
# ──────────────────────────────────────────────────────────────────────────────
load_dotenv()

# ──────────────────────────────────────────────────────────────────────────────
# DB bootstrap: locate incoming file, header mapping, set cron, etc.
# (unchanged logic; just wrapped for readability)
# ──────────────────────────────────────────────────────────────────────────────
def bootstrap_from_db():
    conn1 = mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        user=os.getenv("DB_USERNAME", "roo1"),
        password=os.getenv("DB_PASSWORD", "Password123#@!"),
        database=os.getenv("DB_SECOND_DATABASE", "sp16"),
    )
    cursor1 = conn1.cursor()

    # Industry fixed
    industry_id = 1
    destination_path = os.getenv(
        "DESTINATION_PATH",
        "/var/www/html/supplier_ds/importdemo/public/excel_sheets",
    )

    # Is a file already in processing?
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
        cursor1.execute(
            """
            SELECT id, date, file_name, created_by, supplier_id, catalog_price_type_id 
            FROM catalog_attachments 
            WHERE cron = 11 AND supplier_id = 4 AND deleted_by IS NULL
            LIMIT 1
            """
        )
        file_value = cursor1.fetchone()

    if not file_value:
        cursor1.close()
        conn1.close()
        return None

    file_id, date, input_file, created_by, supplier_id, catalog_price_type_id = file_value

    # Move picked file into "processing" state
    cursor1.execute("UPDATE catalog_attachments SET cron = %s WHERE id = %s", (5, file_id))
    conn1.commit()

    # Header mapping
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
    header_labels = [row[0] for row in column_values]
    header_mapping = {row[0]: row[1] for row in column_values}

    # Date handling
    file_date = datetime.strptime(str(date), "%Y-%m-%d")
    year, month = file_date.year, file_date.month
    month_columns = {
        1: "january", 2: "february", 3: "march", 4: "april", 5: "may", 6: "june",
        7: "july", 8: "august", 9: "september", 10: "october", 11: "november", 12: "december",
    }
    month_column = month_columns[month]

    # Prior/future file checks
    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11 AND MONTH(date) = %s AND YEAR(date) = %s
          AND supplier_id = 4 AND deleted_at IS NULL
        LIMIT 1
        """,
        (str(month).zfill(2), str(year)),
    )
    first_file_uploaded = cursor1.fetchone()

    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11 AND supplier_id = 4 AND MONTH(date) > %s AND YEAR(date) >= %s
          AND deleted_at IS NULL
        LIMIT 1
        """,
        (str(month).zfill(2), str(year)),
    )
    greater_date_file_exist = cursor1.fetchone()

    # Deactivate previous if needed
    if first_file_uploaded:
        cursor1.execute(
            """
            UPDATE catalog_items 
            SET active = 0, updated_at = %s 
            WHERE supplier_id = %s
            """,
            (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), supplier_id),
        )
        conn1.commit()

    # Load Excel and detect header row
    file_path = os.path.join(destination_path, input_file)
    try:
        # read a small window to detect header row
        file_data = pd.read_excel(file_path, dtype=str, header=None, nrows=6)
    except Exception as e:
        print(f"Error reading input file: {e}")
        cursor1.close()
        conn1.close()
        raise SystemExit(1)

    header_row_index = None
    for i in range(len(file_data)):
        row_values = file_data.iloc[i].astype(str).str.strip().tolist()
        missing_headers = set(header_labels) - set(row_values)
        if not missing_headers:
            header_row_index = i
            break

    if header_row_index is None:
        cursor1.execute("UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,))
        conn1.commit()
        print("❌ Could not find the required columns in the file. Please check the data.")
        cursor1.close()
        conn1.close()
        raise SystemExit(1)

    df = sku_data = pd.read_excel(file_path, dtype=str, skiprows=header_row_index)
    actual_columns = df.columns.astype(str).str.strip().tolist()

    missing_columns = [col for col in header_labels if col not in actual_columns]
    if missing_columns:
        print("⚠️ Missing required columns:", missing_columns)
        cursor1.execute("UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,))
        conn1.commit()
        cursor1.close()
        conn1.close()
        raise SystemExit(1)

    # finalize
    sku_data.columns = actual_columns
    sku_data.rename(columns=header_mapping, inplace=True)

    cursor1.close()
    conn1.close()

    return {
        "file_id": file_id,
        "date": file_date,
        "year": year,
        "month": month,
        "month_column": month_column,
        "supplier_id": supplier_id,
        "catalog_price_type_id": catalog_price_type_id,
        "first_file_uploaded": first_file_uploaded,
        "greater_date_file_exist": greater_date_file_exist,
        "sku_data": sku_data,
        "file_path": file_path,
        "industry_id": industry_id,
    }

# ──────────────────────────────────────────────────────────────────────────────
# DB write helpers (your original logic preserved, just grouped)
# ──────────────────────────────────────────────────────────────────────────────
def adding_record_into_database(scrap_data, year, month_column, supplier_id, industry_id, catalog_price_type_id, date, greater_date_file_exist):
    try:
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_SECOND_DATABASE", "sp16"),
        )
        cursor = conn.cursor(buffered=True)

        # product_details_category
        try:
            cursor.execute(
                "SELECT `id` FROM `product_details_category` WHERE `category_name` = %s",
                (scrap_data["category"],),
            )
            r = cursor.fetchone()
            if r:
                category_id = r[0]
            else:
                cursor.execute(
                    "INSERT INTO product_details_category (category_name, created_at, updated_at) VALUES (%s, %s, %s)",
                    (scrap_data["category"], datetime.now(), datetime.now()),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                category_id = cursor.fetchone()[0]
        except mysql.connector.Error as e:
            log_to_laravel(f"Table product_details_category Database error: {e}")
            conn.rollback()
            category_id = None

        # product_details_sub_category
        try:
            cursor.execute(
                "SELECT `id` FROM `product_details_sub_category` WHERE `category_id` = %s AND `sub_category_name` = %s",
                (category_id, scrap_data["sub_category"]),
            )
            r = cursor.fetchone()
            if r:
                sub_category_id = r[0]
            else:
                cursor.execute(
                    "INSERT INTO `product_details_sub_category` (`category_id`, `sub_category_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s, %s)",
                    (category_id, scrap_data["sub_category"], datetime.now(), datetime.now()),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                sub_category_id = cursor.fetchone()[0]
        except mysql.connector.Error as e:
            log_to_laravel(f"Table product_details_sub_category Database error: {e}")
            conn.rollback()
            sub_category_id = None

        # manufacturers
        try:
            cursor.execute(
                "SELECT `id` FROM `manufacturers` WHERE `manufacturer_name` = %s",
                (scrap_data["manufacturer_name"],),
            )
            r = cursor.fetchone()
            if r:
                manufacturer_id = r[0]
            else:
                cursor.execute(
                    "INSERT INTO `manufacturers` (`manufacturer_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s)",
                    (scrap_data["manufacturer_name"], datetime.now(), datetime.now()),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                manufacturer_id = cursor.fetchone()[0]
        except mysql.connector.Error as e:
            log_to_laravel(f"Table manufacturers Database error: {e}")
            conn.rollback()
            manufacturer_id = None

        # catalog_items
        try:
            cursor.execute(
                "SELECT `id` FROM `catalog_items` WHERE `sku` = %s AND `supplier_id` = %s",
                (scrap_data["sku"], supplier_id),
            )
            r = cursor.fetchone()
            if r:
                catalog_item_id = r[0]
                if not greater_date_file_exist:
                    cursor.execute(
                        "UPDATE `catalog_items` SET `active` = %s, `updated_at` = %s WHERE `sku` = %s AND `supplier_id` = %s",
                        (1, datetime.now(), scrap_data["sku"], supplier_id),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO catalog_items 
                    (sku, supplier_id, active, industry_id, category_id, sub_category_id, manufacturer_id,
                     unit_of_measure, created_at, updated_at, catalog_item_url, catalog_item_name,
                     quantity_per_unit, supplier_shorthand_name, manufacturer_number)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        scrap_data["sku"],
                        supplier_id,
                        1 if not greater_date_file_exist else 0,
                        industry_id,
                        category_id,
                        sub_category_id,
                        manufacturer_id,
                        scrap_data.get("unit_of_measure"),
                        datetime.now(),
                        datetime.now(),
                        scrap_data.get("url"),
                        scrap_data.get("description"),
                        scrap_data.get("quantity_per_unit"),
                        scrap_data.get("supplier_shorthand_name"),
                        scrap_data.get("manufacturer_number"),
                    ),
                )
                conn.commit()
                cursor.execute("SELECT LAST_INSERT_ID()")
                catalog_item_id = cursor.fetchone()[0]
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"Table catalog_items error: {e}")
            conn.rollback()
            catalog_item_id = None

        # product_details_common_values
        try:
            for attribute_name in scrap_data.get("specifications", {}):
                cursor.execute(
                    "SELECT id FROM product_details_common_attributes WHERE sub_category_id = %s AND attribute_name = %s",
                    (sub_category_id, attribute_name),
                )
                r = cursor.fetchone()
                if not r:
                    continue
                common_attribute_id = r[0]
                cursor.execute(
                    "SELECT id FROM product_details_common_values WHERE value = %s AND catalog_item_id = %s AND common_attribute_id = %s",
                    (scrap_data["specifications"].get(attribute_name), catalog_item_id, common_attribute_id),
                )
                v = cursor.fetchone()
                if not v:
                    cursor.execute(
                        """
                        INSERT INTO product_details_common_values 
                        (value, catalog_item_id, common_attribute_id, created_at, updated_at)
                        VALUES (%s, %s, %s, %s, %s)
                        """,
                        (
                            scrap_data["specifications"].get(attribute_name),
                            catalog_item_id,
                            common_attribute_id,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        ),
                    )
                    conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"product_details_common_values error: {e}")
            conn.rollback()

        # product_details_raw_values
        try:
            cursor.execute(
                "SELECT id FROM product_details_raw_values WHERE catalog_item_id = %s",
                (catalog_item_id,),
            )
            if not cursor.fetchone():
                description = html.unescape(scrap_data.get("description", ""))
                json_string = json.dumps(description)
                cursor.execute(
                    """
                    INSERT INTO product_details_raw_values
                    (catalog_item_id, raw_values, created_at, updated_at)
                    VALUES (%s, %s, %s, %s)
                    """,
                    (
                        catalog_item_id,
                        json_string,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"product_details_raw_values error: {e}")
            conn.rollback()

        # catalog_prices (core)
        try:
            cursor.execute(
                "SELECT id FROM catalog_prices WHERE catalog_item_id = %s AND catalog_price_type_id = %s",
                (catalog_item_id, catalog_price_type_id),
            )
            r = cursor.fetchone()
            if r:
                catalog_price_id = r[0]
                if not greater_date_file_exist:
                    cursor.execute(
                        """
                        UPDATE catalog_prices 
                        SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s
                        WHERE id = %s
                        """,
                        (1, scrap_data["value"], date, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 1, catalog_price_id),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO catalog_prices 
                    (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        1,
                        scrap_data["value"],
                        catalog_item_id,
                        date,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_price_type_id,
                        1,
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_prices core error: {e}")
            conn.rollback()

        # catalog_prices (web price, type_id=3)
        try:
            cursor.execute(
                "SELECT id FROM catalog_prices WHERE catalog_item_id = %s AND catalog_price_type_id = %s",
                (catalog_item_id, 3),
            )
            r = cursor.fetchone()
            if r:
                catalog_price_id = r[0]
                if not greater_date_file_exist:
                    cursor.execute(
                        """
                        UPDATE catalog_prices 
                        SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s
                        WHERE id = %s
                        """,
                        (1, scrap_data["web_price"], date, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 0, catalog_price_id),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO catalog_prices 
                    (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        1,
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
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_prices web error: {e}")
            conn.rollback()

        # catalog_price_history (core)
        try:
            cursor.execute(
                """
                SELECT id FROM catalog_price_history 
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s AND year = %s LIMIT 1
                """,
                (catalog_item_id, catalog_price_type_id, year),
            )
            ph = cursor.fetchone()
            if ph:
                cursor.execute(
                    f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s WHERE id = %s",
                    (scrap_data["value"], datetime.now().strftime("%Y-%m-%d %H:%M:%S"), ph[0]),
                )
                conn.commit()
            else:
                cursor.execute(
                    f"""
                    INSERT INTO catalog_price_history 
                    (year, created_at, updated_at, catalog_item_id, catalog_price_type_id, {month_column})
                    VALUES (%s, %s, %s, %s, %s, %s)
                    """,
                    (
                        year,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_item_id,
                        catalog_price_type_id,
                        scrap_data["value"],
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_price_history core error: {e}")
            conn.rollback()

        # catalog_price_history (web, type_id=3)
        try:
            cursor.execute(
                """
                SELECT id FROM catalog_price_history 
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s AND year = %s LIMIT 1
                """,
                (catalog_item_id, 3, year),
            )
            ph = cursor.fetchone()
            if ph:
                cursor.execute(
                    f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s WHERE id = %s",
                    (scrap_data["web_price"], datetime.now().strftime("%Y-%m-%d %H:%M:%S"), ph[0]),
                )
                conn.commit()
            else:
                cursor.execute(
                    f"""
                    INSERT INTO catalog_price_history 
                    (year, created_at, updated_at, catalog_item_id, catalog_price_type_id, {month_column})
                    VALUES (%s, %s, %s, %s, %s, %s)
                    """,
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
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"catalog_price_history web error: {e}")
            conn.rollback()

        # check_core_history
        try:
            cursor.execute(
                """
                SELECT id FROM check_core_history
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s LIMIT 1
                """,
                (catalog_item_id, catalog_price_type_id),
            )
            ch = cursor.fetchone()
            core_list_value = 1
            if ch:
                if not greater_date_file_exist:
                    cursor.execute(
                        """
                        UPDATE check_core_history SET updated_at = %s, price_file_date = %s, core_list = %s
                        WHERE id = %s
                        """,
                        (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), date, core_list_value, ch[0]),
                    )
                    conn.commit()
            else:
                cursor.execute(
                    """
                    INSERT INTO check_core_history
                    (customer_id, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        1,
                        catalog_item_id,
                        date,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        catalog_price_type_id,
                        core_list_value,
                    ),
                )
                conn.commit()
        except (mysql.connector.Error, KeyError) as e:
            log_to_laravel(f"check_core_history error: {e}")
            conn.rollback()

    finally:
        try:
            cursor.close()
            conn.close()
        except Exception:
            pass

# ──────────────────────────────────────────────────────────────────────────────
# DB Insert helpers
# ──────────────────────────────────────────────────────────────────────────────
def final_insert(df, boot):
    sku_len = len(df)
    sku_data = boot["sku_data"]
    processed_sku_count = 0
    last_written_percent = -1
    for result in df:
        processed_sku_count += 1

        sku_result = result.get("sku")
        if not sku_result:
            print("Missing 'sku' in result:", result)
            log_to_laravel(f"Missing 'sku' in result: {result}")
            continue
        
        if not result.get("value"):
            print("Missing 'CPG Price' in result:", result)
            log_to_laravel(f"Missing 'CPG Price' in result: {result}")
            continue

        # Match row from merged sku_data for DB insert values
        record = sku_data.loc[sku_data["sku"] == sku_result]
        record = record.dropna(axis=1, how="all")
        record = record.loc[:, ~record.columns.astype(str).str.contains("^None$", na=False)]

        if not record.empty:
            matched_row = record.to_dict(orient="records")[0]

            if not matched_row.get("value"):
                web_price = result.get("web_price")
                
                if web_price is not None:
                    result["value"] = round(web_price * (1 - 0.02), 2)
                else:
                    result["value"] = 0
                    print(f"Missing web_price for SKU: {result.get('sku')}")
                    log_to_laravel(f"Missing web_price for SKU: {result.get('sku')}")
                    continue
            else:
                result["value"] = matched_row.get("value")
        else:
            web_price = result.get("web_price")
            if web_price is not None:
                result["value"] = round(web_price * (1 - 0.02), 2)
            else:
                result["value"] = 0
                print(f"Missing web_price for SKU: {result.get('sku')}")
                log_to_laravel(f"Missing web_price for SKU: {result.get('sku')}")
                continue

        # ODP category mapping (unchanged)
        try:
            conn5 = mysql.connector.connect(
                host=os.getenv("DB_HOST", "127.0.0.1"),
                user=os.getenv("DB_USERNAME", "roo1"),
                password=os.getenv("DB_PASSWORD", "Password123#@!"),
                database=os.getenv("DB_SECOND_DATABASE", "sp16"),
            )
            cursor5 = conn5.cursor()

            if all(k in result for k in ("category", "sub_category_1", "sub_category_2")):
                query = """
                    SELECT * FROM temp_category
                    WHERE category = %s AND subcategory_1 = %s AND subcategory_2 = %s
                """
                params = (result["category"], result["sub_category_1"], result["sub_category_2"])
                cursor5.execute(query, params)
                odp = cursor5.fetchone()
                if odp:
                    result["category"] = odp[4]
                    result["sub_category"] = odp[5]
                else:
                    result["category"] = result["category"]
                    result["sub_category"] = result["sub_category_1"]
            else:
                print(f"Missing category fields in result for SKU {result.get('sku')}")
                log_to_laravel(f"Missing category fields in result for SKU {result.get('sku')}")
                cursor5.close()
                conn5.close()
                continue
        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Database error: {e}")
            try:
                cursor5.close()
                conn5.close()
            except Exception:
                pass
            continue
        finally:
            try:
                cursor5.close()
                conn5.close()
            except Exception:
                pass

        # Required fields
        required_fields = ["category", "sub_category"]
        if any(not result.get(f) for f in required_fields):
            print(f"Missing fields {required_fields} for SKU {result.get('sku')}")
            log_to_laravel(f"Missing fields {required_fields} for SKU {result.get('sku')}")
            continue

        # Write all DB tables for this item
        adding_record_into_database(
            scrap_data=result,
            year=boot["year"],
            month_column=boot["month_column"],
            supplier_id=boot["supplier_id"],
            industry_id=boot["industry_id"],
            catalog_price_type_id=boot["catalog_price_type_id"],
            date=boot["date"],
            greater_date_file_exist=boot["greater_date_file_exist"],
        )
        
        if sku_len > 0:
            new_percent = int((processed_sku_count / sku_len) * 100)
        else:
            new_percent = 0  # Or handle appropriately if zero total SKUs

        if new_percent > last_written_percent:
            progress_percent_db = new_percent
            last_written_percent = new_percent
            try:
                conn1 = mysql.connector.connect(
                    host=os.getenv("DB_HOST", "127.0.0.1"),
                    user=os.getenv("DB_USERNAME", "roo1"),
                    password=os.getenv("DB_PASSWORD", "Password123#@!"),
                    database=os.getenv("DB_SECOND_DATABASE", "sp16")
                )

                cursor1 = conn1.cursor()
                cursor1.execute(
                    "UPDATE catalog_attachments SET file_upload_percent = %s WHERE id = %s",
                    (progress_percent_db, boot["file_id"])
                )
                conn1.commit()
            except mysql.connector.Error as err:
                print(f"❌ DB Error: {err}")
                log_to_laravel(f"❌ DB Error during percentage update: {err}")
                if conn1.is_connected():
                    conn1.rollback()
            finally:
                cursor1.close()
                conn1.close()

    try:
        conn4 = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_SECOND_DATABASE", "sp16")
        )
        cursor4 = conn4.cursor()
        
        # Update cron status to indicate completion
        cursor4.execute("UPDATE catalog_attachments SET cron = 6 WHERE id = %s", (boot["file_id"],))
        conn4.commit()

    except mysql.connector.Error as err:
        print(f"❌ DB Error: {err}")
        log_to_laravel(f"❌ DB Error during cron update: {err}")
    finally:
        cursor4.close()
        conn4.close()