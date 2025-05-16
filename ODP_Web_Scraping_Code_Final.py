import re
import os
import sys
import json
import time
import requests
import threading
import pandas as pd
from tqdm import tqdm
import mysql.connector
from threading import Lock
from datetime import datetime
from bs4 import BeautifulSoup
from dotenv import load_dotenv
from concurrent.futures import ThreadPoolExecutor, as_completed

load_dotenv()  # This loads .env from current directory or parent
lock = Lock()

LOG_FILE = os.getenv("CUSTOM_LOG_PATH", "/var/www/html/supplier_ds/importdemo/storage/logs/laravel.log")
# print(LOG_FILE)
# exit()

def log_to_laravel(message):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    formatted = f"[{timestamp}] local.ERROR: {message}\n"
    with open(LOG_FILE, 'a') as log_file:
        log_file.write(formatted)

def adding_record_into_database(matched_row, scrap_data):
    try:
        # Connect to MySQL
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_DATABASE", "sp16")
        )

        cursor = conn.cursor(buffered=True)
        ################ product_details_category Start #################
        # Check if the product_details_category exists
        try:
            # Try to fetch the category ID
            cursor.execute(
                "SELECT `id` FROM `product_details_category` WHERE `category_name` = %s",
                (scrap_data["category"],),
            )
            product_details_category_result = cursor.fetchone()

            if product_details_category_result:
                # Category exists, get its ID
                category_id = product_details_category_result[0]
            else:
                # Insert a new category and get its ID
                cursor.execute(
                    "INSERT INTO product_details_category (category_name, created_at, updated_at) VALUES (%s, %s, %s)",
                    (
                        scrap_data["category"],
                        datetime.now(),
                        datetime.now(),
                    ),
                )
                conn.commit()

                # Fetch last inserted ID
                cursor.execute("SELECT LAST_INSERT_ID()")
                category_id = cursor.fetchone()[0]

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table product_details_category Database error: {e}")
            conn.rollback()  # Rollback in case of error
            category_id = None  # Set category_id to None if operation fails

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Table product_details_category Unexpected error: {e}")
            category_id = None
        ################ product_details_category End ###################

        ################ product_details_sub_category Start #################
        try:
            # Check if the sub-category exists
            cursor.execute(
                "SELECT `id` FROM `product_details_sub_category` WHERE `category_id` = %s AND `sub_category_name` = %s",
                (
                    category_id,
                    scrap_data["sub_category"],
                ),
            )
            sub_category_result = cursor.fetchone()

            if sub_category_result:
                # Sub-category exists, get its ID
                sub_category_id = sub_category_result[0]
            else:
                # Insert a new sub-category
                cursor.execute(
                    "INSERT INTO `product_details_sub_category` (`category_id`, `sub_category_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s, %s)",
                    (
                        category_id,
                        scrap_data["sub_category"],
                        datetime.now(),
                        datetime.now(),
                    ),
                )
                conn.commit()

                # Fetch last inserted ID
                cursor.execute("SELECT LAST_INSERT_ID()")
                sub_category_id = cursor.fetchone()[0]

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table product_details_sub_category Database error: {e}")
            conn.rollback()  # Rollback in case of error
            sub_category_id = None  # Ensure it's not left undefined

        except Exception as e:
            log_to_laravel(f"Table product_details_sub_category Unexpected error: {e}")
            print(f"Unexpected error: {e}")
            sub_category_id = None
        ################ product_details_sub_category End ###################

        ################ manufacturers Start #################
        try:
            # Check if the manufacturer exists
            cursor.execute(
                "SELECT `id` FROM `manufacturers` WHERE `manufacturer_name` = %s",
                (scrap_data["manufacturer_name"],),
            )
            manufacturer_result = cursor.fetchone()

            if manufacturer_result:
                # Manufacturer exists, get its ID
                manufacturer_id = manufacturer_result[0]
            else:
                # Insert a new manufacturer
                cursor.execute(
                    "INSERT INTO `manufacturers` (`manufacturer_name`, `created_at`, `updated_at`) VALUES (%s, %s, %s)",
                    (
                        scrap_data["manufacturer_name"],
                        datetime.now(),
                        datetime.now(),
                    ),
                )
                conn.commit()

                # Fetch last inserted ID
                cursor.execute("SELECT LAST_INSERT_ID()")
                manufacturer_id = cursor.fetchone()[0]

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table manufacturers Database error: {e}")
            conn.rollback()  # Rollback in case of error
            manufacturer_id = None  # Ensure it's not left undefined

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Table manufacturers Unexpected error: {e}")
            manufacturer_id = None
        ################ manufacturers End ###################

        ################ catalog_items Start #################
        try:
            # Check if the catalog item exists
            cursor.execute(
                "SELECT `id` FROM `catalog_items` WHERE `sku` = %s AND `supplier_id` = %s",
                (
                    matched_row["sku"],
                    supplier_id,
                ),
            )
            catalog_items_result = cursor.fetchone()

            if catalog_items_result:
                if not greater_date_file_exist:
                    cursor.execute(
                        "UPDATE `catalog_items` SET `active` = %s, `updated_at` = %s WHERE `sku` = %s AND `supplier_id` = %s",
                        (
                            1,
                            datetime.now(),
                            matched_row["sku"],
                            supplier_id,
                        ),
                    )
                    conn.commit()
                # catalog_items exists, get its ID
                catalog_item_id = catalog_items_result[0]
            else:
                # Insert a new catalog item
                cursor.execute(
                    """INSERT INTO catalog_items 
                    (
                        sku,
                        supplier_id,
                        active,
                        industry_id,
                        category_id,
                        sub_category_id,
                        manufacturer_id,
                        unit_of_measure,
                        created_at,
                        updated_at,
                        catalog_item_url,
                        catalog_item_name,
                        quantity_per_unit,
                        supplier_shorthand_name,
                        manufacturer_number       
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                    (
                        matched_row["sku"],
                        supplier_id,
                        1 if not greater_date_file_exist else 0,
                        industry_id,
                        category_id,
                        sub_category_id,
                        manufacturer_id,
                        matched_row.get("unit_of_measure", None),  # Using .get() to avoid KeyError
                        datetime.now(),
                        datetime.now(),
                        scrap_data.get("url", None),  # Using .get() for safety
                        scrap_data.get("description", None),
                        matched_row.get("quantity_per_unit", None),
                        matched_row.get("supplier_shorthand_name", None),
                        scrap_data.get("manufacturer_number", None),
                    ),
                )
                conn.commit()

                # Fetch last inserted ID
                cursor.execute("SELECT LAST_INSERT_ID()")
                catalog_item_id = cursor.fetchone()[0]

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table catalog_items Database error: {e}")
            conn.rollback()  # Rollback in case of error
            catalog_item_id = None  # Ensure it's not left undefined

        except KeyError as e:
            print(f"Missing key in matched_row or scrap_data: {e}")
            log_to_laravel(f"Table catalog_items missing key in matched_row or scrap_data error: {e}")
            catalog_item_id = None

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Table catalog_items Unexpected error: {e}")
            catalog_item_id = None
        ################ catalog_items End ###################

        ################ product_details_common_attributes Start #################
        try:
            for attribute_name in scrap_data.get("specifications", {}):  # Using .get() to avoid KeyError
                # Check if the product_details_common_attributes exists
                cursor.execute(
                    "SELECT id FROM product_details_common_attributes WHERE sub_category_id = %s AND attribute_name = %s",
                    (
                        sub_category_id,
                        attribute_name,
                    ),
                )
                product_details_common_attributes_result = cursor.fetchone()

                ################ product_details_common_values Start #################
                if product_details_common_attributes_result:
                    common_attribute_id = product_details_common_attributes_result[0]

                    # Check if the common value exists, if not, create it
                    cursor.execute(
                        "SELECT id FROM product_details_common_values WHERE value = %s AND catalog_item_id = %s AND common_attribute_id = %s",
                        (
                            scrap_data["specifications"].get(attribute_name, None),  # Using .get() for safety
                            catalog_item_id,
                            common_attribute_id,
                        ),
                    )
                    product_details_common_values = cursor.fetchone()

                    if not product_details_common_values:
                        cursor.execute(
                            """INSERT INTO product_details_common_values 
                            (value, catalog_item_id, common_attribute_id, created_at, updated_at) 
                            VALUES (%s, %s, %s, %s, %s)""",
                            (
                                scrap_data["specifications"].get(attribute_name, None),  # Using .get() for safety
                                catalog_item_id,
                                common_attribute_id,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            ),
                        )
                        conn.commit()

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table product_details_common_values Database error: {e}")
            conn.rollback()  # Rollback in case of error

        except KeyError as e:
            print(f"Missing key in scrap_data: {e}")
            log_to_laravel(f"Table product_details_common_values missing key in scrap_data error: {e}")

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Table product_details_common_values Unexpected error: {e}")
        ################ product_details_common_values End ###################
        ################ product_details_common_attributes End #################

        ################ product_details_raw_values Start #################
        try:
            # Check if the raw value exists, if not, create it
            cursor.execute(
                "SELECT id FROM product_details_raw_values WHERE catalog_item_id = %s",
                (catalog_item_id,),
            )
            product_details_raw_values = cursor.fetchone()

            # Convert dictionary to JSON string safely
            json_string = json.dumps(scrap_data.get("breadcrumbs", {}), indent=4)  # Using .get() to avoid KeyError

            if not product_details_raw_values:
                cursor.execute(
                    """INSERT INTO product_details_raw_values 
                    (catalog_item_id, raw_values, created_at, updated_at) 
                    VALUES (%s, %s, %s, %s)""",
                    (
                        catalog_item_id,
                        json_string,
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                    ),
                )
                conn.commit()

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table product_details_raw_values Database error: {e}")
            conn.rollback()  # Rollback in case of an error

        except KeyError as e:
            print(f"Missing key in scrap_data: {e}")
            log_to_laravel(f"Table product_details_raw_values missing key in scrap_data error: {e}")

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Table product_details_raw_values Unexpected error: {e}")
        ################ product_details_raw_values End ###################

        ################ catalog_prices Start #################
        try:
            # Check if the catalog_prices exists
            cursor.execute(
                """SELECT id FROM catalog_prices 
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s""",
                (
                    catalog_item_id,
                    catalog_price_type_id,
                ),
            )
            existing_record = cursor.fetchone()

            if existing_record:
                catalog_price_id = existing_record[0]

                # If a greater date catalog file does not exist, update the catalog_prices
                if not greater_date_file_exist:
                    try:
                        cursor.execute(
                            """UPDATE catalog_prices 
                            SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s 
                            WHERE id = %s""",
                            (
                                1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                matched_row["value"],
                                date,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                1 if matched_row["core_list"].strip() == "CN" else 0,
                                catalog_price_id,
                            ),
                        )
                        conn.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating catalog_prices: {e}")
                        log_to_laravel(f"Error updating catalog_prices: {e}")
                        conn.rollback()  # Rollback in case of an error
            else:
                # Insert a new catalog_prices
                try:
                    cursor.execute(
                        """INSERT INTO catalog_prices 
                        (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list) 
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
                        (
                            1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                            matched_row["value"],
                            catalog_item_id,
                            date,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            catalog_price_type_id,
                            1 if matched_row["core_list"].strip() == "CN" else 0,
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error inserting into catalog_prices: {e}")
                    log_to_laravel(f"Table catalog_prices Unexpected error inserting into catalog_prices: {e}")
                    conn.rollback()  # Rollback in case of an error

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Table catalog_prices Database error: {e}")
            conn.rollback()  # Ensure rollback if any failure occurs

        except KeyError as e:
            print(f"Missing key in matched_row: {e}")
            log_to_laravel(f"Table catalog_prices missing key in matched_row: {e}")

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Table catalog_prices Unexpected error: {e}")
        ################ catalog_prices End ###################

        ################ catalog_prices web price Start #################
        try:
            # Check if the catalog_prices exists
            cursor.execute(
                """SELECT id FROM catalog_prices 
                WHERE catalog_item_id = %s AND catalog_price_type_id = %s""",
                (
                    catalog_item_id,
                    3,
                ),
            )
            existing_record = cursor.fetchone()

            if existing_record:
                catalog_price_id = existing_record[0]

                # If a greater date catalog file does not exist, update the catalog_prices
                if not greater_date_file_exist:
                    try:
                        cursor.execute(
                            """UPDATE catalog_prices 
                            SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s 
                            WHERE id = %s""",
                            (
                                1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                matched_row["web_price"],
                                date,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                0,
                                catalog_price_id,
                            ),
                        )
                        conn.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating catalog_prices: {e}")
                        log_to_laravel(f"Error updating web_price catalog_prices : {e}")
                        conn.rollback()  # Rollback in case of an error
            else:
                # Insert a new catalog_prices
                try:
                    cursor.execute(
                        """INSERT INTO catalog_prices 
                        (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list) 
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
                        (
                            1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                            matched_row["web_price"],
                            catalog_item_id,
                            date,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            3,
                            0,
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error inserting into catalog_prices: {e}")
                    log_to_laravel(f"Error inserting into web_price catalog_price: {e}")
                    conn.rollback()  # Rollback in case of an error

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Database error web_price catalog_price: {e}")
            conn.rollback()  # Ensure rollback if any failure occurs

        except KeyError as e:
            print(f"Missing key in matched_row: {e}")
            log_to_laravel(f"Missing key in matched_row catalog_price: {e}")

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Unexpected error web_price catalog_price: {e}")
        ################ catalog_prices web price End ###################

        ################ catalog_price_history Start #################
        try:
            # Check if the record exists
            query = (
                "SELECT * FROM catalog_price_history WHERE catalog_item_id = %s AND "
                "catalog_price_type_id = %s AND year = %s LIMIT 1"
            )
            cursor.execute(
                query,
                (
                    catalog_item_id,
                    catalog_price_type_id,
                    year,
                ),
            )
            price_history = cursor.fetchone()

            if price_history:
                # Update existing record
                try:
                    update_query = (
                        f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s "
                        "WHERE id = %s"
                    )
                    cursor.execute(
                        update_query,
                        (
                            matched_row["value"],
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            price_history[0],
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error updating catalog_price_history: {e}")
                    log_to_laravel(f"Error updating catalog_price_history: {e}")
                    conn.rollback()  # Rollback in case of an error
            else:
                # Insert new record
                try:
                    insert_query = (
                        f"INSERT INTO catalog_price_history (year, created_at, updated_at, catalog_item_id, "
                        f"catalog_price_type_id, {month_column}) VALUES (%s, %s, %s, %s, %s, %s)"
                    )
                    cursor.execute(
                        insert_query,
                        (
                            year,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            catalog_item_id,
                            catalog_price_type_id,
                            matched_row["value"],
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error inserting into catalog_price_history: {e}")
                    log_to_laravel(f"Error inserting into catalog_price_history: {e}")
                    conn.rollback()  # Rollback in case of an error

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Database error inserting into catalog_price_history: {e}")
            conn.rollback()  # Ensure rollback if any failure occurs

        except KeyError as e:
            print(f"Missing key in matched_row: {e}")
            log_to_laravel(f"Missing key in matched_row: {e}")

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Unexpected error inserting into catalog_price_history: {e}")
        ################ catalog_price_history End ###################

        ################ catalog_price_history web price Start #################
        try:
            # Check if the record exists
            query = (
                "SELECT * FROM catalog_price_history WHERE catalog_item_id = %s AND "
                "catalog_price_type_id = %s AND year = %s LIMIT 1"
            )
            cursor.execute(
                query,
                (
                    catalog_item_id,
                    3,
                    year,
                ),
            )
            price_history = cursor.fetchone()

            if price_history:
                # Update existing record
                try:
                    update_query = (
                        f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s "
                        "WHERE id = %s"
                    )
                    cursor.execute(
                        update_query,
                        (
                            matched_row["web_price"],
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            price_history[0],  # Fixed incorrect placeholder value (was `0`)
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error updating catalog_price_history: {e}")
                    log_to_laravel(f"Error updating web_price catalog_price_history: {e}")
                    conn.rollback()  # Rollback in case of an error
            else:
                # Insert new record
                try:
                    insert_query = (
                        f"INSERT INTO catalog_price_history (year, created_at, updated_at, catalog_item_id, "
                        f"catalog_price_type_id, {month_column}) VALUES (%s, %s, %s, %s, %s, %s)"
                    )
                    cursor.execute(
                        insert_query,
                        (
                            year,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            catalog_item_id,
                            3,
                            matched_row["web_price"],
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error inserting into catalog_price_history: {e}")
                    log_to_laravel(f"Error inserting web_price catalog_price_history: {e}")
                    conn.rollback()  # Rollback in case of an error

        except mysql.connector.Error as e:
            print(f"Database error web_price catalog_price_history: {e}")
            log_to_laravel(f"Database error web_price catalog_price_history: {e}")
            conn.rollback()  # Ensure rollback if any failure occurs

        except KeyError as e:
            print(f"Missing key in matched_row web_price catalog_price_history: {e}")
            log_to_laravel(f"Missing key in matched_row web_price catalog_price_history: {e}")

        except Exception as e:
            print(f"Unexpected error web_price catalog_price_history: {e}")
            log_to_laravel(f"Unexpected error web_price catalog_price_history: {e}")
        ################ catalog_price_history web price End ###################

        ################ check_core_history Start #################
        try:
            # Check if the check_core_history exists
            query = (
                "SELECT * FROM check_core_history WHERE catalog_item_id = %s AND "
                "catalog_price_type_id = %s LIMIT 1"
            )
            cursor.execute(
                query,
                (
                    catalog_item_id,
                    catalog_price_type_id,
                ),
            )
            check_core_history = cursor.fetchone()

            core_list_value = 1 if matched_row.get("core_list", "").strip() == "CN" else 0

            if check_core_history:
                # If greater date catalog file does not exist, update month data
                if not greater_date_file_exist:
                    try:
                        update_query = (
                            "UPDATE check_core_history SET updated_at = %s, price_file_date = %s, core_list = %s "
                            "WHERE id = %s"
                        )
                        cursor.execute(
                            update_query,
                            (
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                date,
                                core_list_value,
                                check_core_history[0],
                            ),
                        )
                        conn.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating check_core_history: {e}")
                        log_to_laravel(f"Error updating check_core_history: {e}")
                        conn.rollback()  # Rollback on error
            else:
                # Insert new check_core_history
                try:
                    insert_query = (
                        "INSERT INTO check_core_history (customer_id, catalog_item_id, price_file_date, created_at, "
                        "updated_at, catalog_price_type_id, core_list) VALUES (%s, %s, %s, %s, %s, %s, %s)"
                    )
                    cursor.execute(
                        insert_query,
                        (
                            1,  # Replace with `matched_row["Customer Id"]` if needed
                            catalog_item_id,
                            date,
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                            catalog_price_type_id,
                            core_list_value,
                        ),
                    )
                    conn.commit()
                except mysql.connector.Error as e:
                    print(f"Error inserting into check_core_history: {e}")
                    log_to_laravel(f"Error inserting into check_core_history: {e}")
                    conn.rollback()  # Rollback on error

        except mysql.connector.Error as e:
            print(f"Database error: {e}")
            log_to_laravel(f"Database error updating check_core_history: {e}")
            conn.rollback()

        except KeyError as e:
            print(f"Missing key in matched_row: {e}")
            log_to_laravel(f"Missing key in matched_row check_core_history: {e}")

        except Exception as e:
            print(f"Unexpected error: {e}")
            log_to_laravel(f"Unexpected error updating check_core_history: {e}")
        ################ check_core_history End ###################
        
    finally:
        cursor.close()
        conn.close()  # Always close the connection to prevent memory leaks

# Connect to MySQL
conn1 = mysql.connector.connect(
    host=os.getenv("DB_HOST", "127.0.0.1"),
    user=os.getenv("DB_USERNAME", "roo1"),
    password=os.getenv("DB_PASSWORD", "Password123#@!"),
    database=os.getenv("DB_DATABASE", "sp16")
)

cursor1 = conn1.cursor()

# Define industry ID
industry_id = 1

# Define file path
destination_path = os.getenv("DESTINATION_PATH","/var/www/html/supplier_ds/importdemo/public/excel_sheets")

# Fetch the file where cron is 5
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
    # Fetch the file where cron is 11
    cursor1.execute(
        """
        SELECT id, date, file_name, created_by, supplier_id, catalog_price_type_id 
        FROM catalog_attachments 
        WHERE cron = 11 AND deleted_by IS NULL
        LIMIT 1
    """
    )
    file_value = cursor1.fetchone()
    # print(file_value)

if file_value:
    file_id, date, input_file, created_by, supplier_id, catalog_price_type_id = (
        file_value
    )

    # Update the cron column from cron = 11 to cron = 5
    cursor1.execute(" UPDATE catalog_attachments SET cron = %s WHERE id = %s",(5,file_id))
    conn1.commit()

    # Fetch supplier field mappings
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
    header_mapping1 = [row[0] for row in column_values]
    header_mapping = {row[0]: row[1] for row in column_values}

    # Parse date
    file_date = datetime.strptime(str(date), "%Y-%m-%d")
    year = file_date.year
    month = file_date.month

    # Month mapping
    month_columns = {
        1: "january",
        2: "february",
        3: "march",
        4: "april",
        5: "may",
        6: "june",
        7: "july",
        8: "august",
        9: "september",
        10: "october",
        11: "november",
        12: "december",
    }
    month_column = month_columns[month]

    # Check if an older file exists
    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11 
        AND MONTH(date) = %s 
        AND YEAR(date) = %s 
        AND deleted_at IS NULL 
        LIMIT 1
    """,
        (
            str(month).zfill(2),
            str(year),
        ),
    )
    first_file_uploaded = cursor1.fetchone()

    # Check if a future file exists
    cursor1.execute(
        """
        SELECT id FROM catalog_attachments 
        WHERE cron != 11
        AND MONTH(date) > %s 
        AND YEAR(date) >= %s
        AND deleted_at IS NULL
        LIMIT 1
    """,
        (
            str(month).zfill(2),
            str(year),
        ),
    )
    greater_date_file_exist = cursor1.fetchone()

    # Deactivate previous records if needed
    if first_file_uploaded:
        cursor1.execute(
            """
            UPDATE catalog_items 
            SET active = 0, updated_at = %s 
            WHERE supplier_id = %s
        """,
            (
                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                supplier_id,
            ),
        )
        conn1.commit()

    # Load Excel file
    file_path = f"{destination_path}/{input_file}"

    try:  # Trying to reading excel file
        file_data = pd.read_excel(file_path, dtype=str, header=None, nrows=6)  # Read as strings to preserve data integrity
    except Exception as e:  # Handle exception
        print(f"Error reading input file: {e}")
        exit()

    for i in range(len(file_data)):
        row_values = file_data.iloc[i].str.strip().tolist()  # Convert non-null values to a list of strings
        missing_headers = set(header_mapping1) - set(row_values)  # Check if all required headers exist

        if not missing_headers:
            header_row_index = i  # Set header row index when found
            break  # Stop searching once the header row is found

    if header_row_index is not None:
        print(f"✅ Found header row at index: {header_row_index}")
        df = sku_data = pd.read_excel(file_path, dtype=str, skiprows=header_row_index)
        actual_columns = df.columns.str.strip().tolist()
        
        # Check for missing columns
        missing_columns = [col for col in header_mapping1 if col not in actual_columns]

        if missing_columns:
            print("⚠️ The following required columns are missing from the file:", missing_columns)
            cursor1.execute(
                "UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,)
            )
            conn1.commit()
            exit()
        else:
            # Get header row
            header = actual_columns
            print("✅ All required columns are present.")
    else:
        cursor1.execute(
            "UPDATE catalog_attachments SET cron = 10 WHERE id = %s", (file_id,)
        )
        conn1.commit()
        print("❌ Could not find the required columns in the file. Please check the data.")
        exit()
    
    cursor1.close()
    conn1.close()

    sku_data.columns = header
    
    # Rename columns using header mapping
    sku_data.rename(columns=header_mapping, inplace=True)
    
    # remove .xlsx or .xls
    json_file_name = re.sub(r"\.xlsb|\.xlsx|\.xls", "", input_file)

    sku_column = "sku"
    search_terms = (
        sku_data[sku_column].dropna().astype(str).tolist()
    )  # Getting specific column

    # Base search URL
    base_url = "https://www.odpbusiness.com/a/products/{}/"

    # Headers to mimic a browser request
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    }

    results = []
    failed_skus = []  # Track failed SKUs
    max_retries = 2  # Maximum retries

    # Function to extract product details
    def extract_product_details(soup, product_info):
        try:
            # Select elements with class "sku-failure-heading"
            sku_failure = soup.select(".sku-failure-heading")
            if sku_failure:
                product_info["sku_availabe"] = sku_failure[0].get_text(strip=True)
        except Exception as e:
            # print(f"Error extracting sku_availabe: {e}")
            product_info["sku_availabe"] = None

        breadcrumbs = soup.select("ul.od-breadcrumb-list li span[itemprop='name']")
        try:
            product_info["category"] = breadcrumbs[1].text.strip() if len(breadcrumbs) > 1 else None
        except Exception as e:
            product_info["category"] = None

        try:
            product_info["sub_category"] = breadcrumbs[2].text.strip() if len(breadcrumbs) > 2 else None
        except Exception as e:
            product_info["sub_category"] = None

        try:
            product_info["breadcrumbs"] = [
                breadcrumbs.text.strip()
                for breadcrumbs in soup.select(
                    "ul.od-breadcrumb-list li span[itemprop='name']"
                )
            ]
        except:
            product_info["breadcrumbs"] = "N/A"

        try:
            product_info["description"] = soup.find(
                "h1", {"itemprop": "name", "auid": "sku-heading"}
            ).text.strip()
        except:
            product_info["description"] = "N/A"

        try:
            specs_table = soup.select("table.sku-table tbody tr")
            specification = {}
            
            product_info.setdefault("manufacturer_number", "N/A")
            product_info.setdefault("manufacturer_name", "N/A")
            
            for row in specs_table:
                cells = row.find_all("td")
                if len(cells) != 2:
                    continue
                
                key = cells[0].text.strip().lower()
                value = cells[1].text.strip()
                
                if key == "manufacturer #":
                    product_info["manufacturer_number"] = value if value else "N/A"
                elif key == "manufacturer":
                    product_info["manufacturer_name"] = value if value else "N/A"
                else:
                    specification[key] = value
            
            product_info["specifications"] = specification
        except Exception:
            pass

        try:
            price_element = soup.find(
                "span", class_="od-graphql-price-little-price"
            ) or soup.find("span", class_="od-graphql-price-big-price")
            product_info["price"] = (
                price_element.text.strip() if price_element else "N/A"
            )
        except:
            product_info["price"] = "N/A"

    # Function to process a search term
    def process_search_term(search_term):
        product_info = {
            "url": "",
            "price": 0,
            "category": "",
            "description": "",
            "sku_availabe":None,
            "sub_category": None,
            "specifications": {},
            "manufacturer_name":"",
            "manufacturer_number": 0,
            "search_term": search_term,
        }
        try:
            product_url = base_url.format(search_term)
            if product_url:
                product_info["url"] = product_url
                response = requests.get(
                    product_url, headers=headers, timeout=60
                )
                if response.status_code == 200:
                    product_soup = BeautifulSoup(
                        response.content, "html.parser"
                    )
                    extract_product_details(product_soup, product_info)
                    return product_info
                    
        except requests.exceptions.Timeout as e:
            failed_skus.append({"sku": search_term, "error": "Timeout"})
            return None
        except Exception as e:
            error_msg = f"SKU {search_term} error: {str(e)}"
            log_to_laravel(error_msg)
            print(f"Error processing {search_term}: {e}")
        return None

    def adding_record_without_scraping_into_database(matched_row, catalog_item_id):
        # Connect to MySQL
        conn2 = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_DATABASE", "sp16")
        )

        if not conn2.is_connected():
            conn2.reconnect()

        # Ensure manual control over transactions
        conn2.autocommit = False

        # Use buffered=True to prevent "Unread result found"
        cursor2 = conn2.cursor(buffered=True)
        try:
            ################ catalog_prices Start #################
            try:
                # Check if the catalog_prices exists
                cursor2.execute(
                    """SELECT id FROM catalog_prices 
                    WHERE catalog_item_id = %s AND catalog_price_type_id = %s""",
                    (
                        catalog_item_id,
                        catalog_price_type_id,
                    ),
                )
                existing_record = cursor2.fetchone()

                if existing_record:
                    catalog_price_id = existing_record[0]

                    # If a greater date catalog file does not exist, update the catalog_prices
                    if not greater_date_file_exist:
                        try:
                            cursor2.execute(
                                """UPDATE catalog_prices 
                                SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s 
                                WHERE id = %s""",
                                (
                                    1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                    matched_row["value"],
                                    date,
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    1 if matched_row["core_list"].strip() == "CN" else 0,
                                    catalog_price_id,
                                ),
                            )
                            conn2.commit()
                        except mysql.connector.Error as e:
                            print(f"Error updating catalog_prices: {e}")
                            log_to_laravel(f"Error updating catalog_prices: {e}")
                            conn2.rollback()  # Rollback in case of an error
                else:
                    # Insert a new catalog_prices
                    try:
                        cursor2.execute(
                            """INSERT INTO catalog_prices 
                            (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list) 
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
                            (
                                1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                matched_row["value"],
                                catalog_item_id,
                                date,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                catalog_price_type_id,
                                1 if matched_row["core_list"].strip() == "CN" else 0,
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_prices: {e}")
                        log_to_laravel(f"Table catalog_prices Unexpected error inserting into catalog_prices: {e}")
                        conn2.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Table catalog_prices Database error: {e}")
                conn2.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Table catalog_prices missing key in matched_row: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Table catalog_prices Unexpected error: {e}")
            ################ catalog_prices End ###################

            ################ catalog_prices web price Start #################
            try:
                # Check if the catalog_prices exists
                cursor2.execute(
                    """SELECT id FROM catalog_prices 
                    WHERE catalog_item_id = %s AND catalog_price_type_id = %s""",
                    (
                        catalog_item_id,
                        3,
                    ),
                )
                existing_record = cursor2.fetchone()

                if existing_record:
                    catalog_price_id = existing_record[0]

                    # If a greater date catalog file does not exist, update the catalog_prices
                    if not greater_date_file_exist:
                        try:
                            cursor2.execute(
                                """UPDATE catalog_prices 
                                SET customer_id = %s, value = %s, price_file_date = %s, updated_at = %s, core_list = %s 
                                WHERE id = %s""",
                                (
                                    1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                    matched_row["web_price"],
                                    date,
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    0,
                                    catalog_price_id,
                                ),
                            )
                            conn2.commit()
                        except mysql.connector.Error as e:
                            print(f"Error updating catalog_prices: {e}")
                            log_to_laravel(f"Error updating web_price catalog_prices : {e}")
                            conn2.rollback()  # Rollback in case of an error
                else:
                    # Insert a new catalog_prices
                    try:
                        cursor2.execute(
                            """INSERT INTO catalog_prices 
                            (customer_id, value, catalog_item_id, price_file_date, created_at, updated_at, catalog_price_type_id, core_list) 
                            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
                            (
                                1,  # Replace with `matched_row.get("Customer Id", 1)` if needed
                                matched_row["web_price"],
                                catalog_item_id,
                                date,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                3,
                                0,
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_prices: {e}")
                        log_to_laravel(f"Error inserting into web_price catalog_price: {e}")
                        conn2.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error web_price catalog_price: {e}")
                conn2.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Missing key in matched_row catalog_price: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Unexpected error web_price catalog_price: {e}")
            ################ catalog_prices web price End ###################

            ################ catalog_price_history Start #################
            try:
                # Check if the record exists
                query = (
                    "SELECT * FROM catalog_price_history WHERE catalog_item_id = %s AND "
                    "catalog_price_type_id = %s AND year = %s LIMIT 1"
                )
                cursor2.execute(
                    query,
                    (
                        catalog_item_id,
                        catalog_price_type_id,
                        year,
                    ),
                )
                price_history = cursor2.fetchone()

                if price_history:
                    # Update existing record
                    try:
                        update_query = (
                            f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s "
                            "WHERE id = %s"
                        )
                        cursor2.execute(
                            update_query,
                            (
                                matched_row["value"],
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                price_history[0],
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating catalog_price_history: {e}")
                        log_to_laravel(f"Error updating catalog_price_history: {e}")
                        conn2.rollback()  # Rollback in case of an error
                else:
                    # Insert new record
                    try:
                        insert_query = (
                            f"INSERT INTO catalog_price_history (year, created_at, updated_at, catalog_item_id, "
                            f"catalog_price_type_id, {month_column}) VALUES (%s, %s, %s, %s, %s, %s)"
                        )
                        cursor2.execute(
                            insert_query,
                            (
                                year,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                catalog_item_id,
                                catalog_price_type_id,
                                matched_row["value"],
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_price_history: {e}")
                        log_to_laravel(f"Error inserting into catalog_price_history: {e}")
                        conn2.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error inserting into catalog_price_history: {e}")
                conn2.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Missing key in matched_row: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Unexpected error inserting into catalog_price_history: {e}")
            ################ catalog_price_history End ###################

            ################ catalog_price_history web price Start #################
            try:
                # Check if the record exists
                query = (
                    "SELECT * FROM catalog_price_history WHERE catalog_item_id = %s AND "
                    "catalog_price_type_id = %s AND year = %s LIMIT 1"
                )
                cursor2.execute(
                    query,
                    (
                        catalog_item_id,
                        3,
                        year,
                    ),
                )
                price_history = cursor2.fetchone()

                if price_history:
                    # Update existing record
                    try:
                        update_query = (
                            f"UPDATE catalog_price_history SET {month_column} = %s, updated_at = %s "
                            "WHERE id = %s"
                        )
                        cursor2.execute(
                            update_query,
                            (
                                matched_row["web_price"],
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                price_history[0],  # Fixed incorrect placeholder value (was `0`)
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error updating catalog_price_history: {e}")
                        log_to_laravel(f"Error updating web_price catalog_price_history: {e}")
                        conn2.rollback()  # Rollback in case of an error
                else:
                    # Insert new record
                    try:
                        insert_query = (
                            f"INSERT INTO catalog_price_history (year, created_at, updated_at, catalog_item_id, "
                            f"catalog_price_type_id, {month_column}) VALUES (%s, %s, %s, %s, %s, %s)"
                        )
                        cursor2.execute(
                            insert_query,
                            (
                                year,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                catalog_item_id,
                                3,
                                matched_row["web_price"],
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error inserting into catalog_price_history: {e}")
                        log_to_laravel(f"Error inserting web_price catalog_price_history: {e}")
                        conn2.rollback()  # Rollback in case of an error

            except mysql.connector.Error as e:
                print(f"Database error web_price catalog_price_history: {e}")
                log_to_laravel(f"Database error web_price catalog_price_history: {e}")
                conn2.rollback()  # Ensure rollback if any failure occurs

            except KeyError as e:
                print(f"Missing key in matched_row web_price catalog_price_history: {e}")
                log_to_laravel(f"Missing key in matched_row web_price catalog_price_history: {e}")

            except Exception as e:
                print(f"Unexpected error web_price catalog_price_history: {e}")
                log_to_laravel(f"Unexpected error web_price catalog_price_history: {e}")
            ################ catalog_price_history web price End ###################

            ################ check_core_history Start #################
            try:
                # Check if the check_core_history exists
                query = (
                    "SELECT * FROM check_core_history WHERE catalog_item_id = %s AND "
                    "catalog_price_type_id = %s LIMIT 1"
                )
                cursor2.execute(
                    query,
                    (
                        catalog_item_id,
                        catalog_price_type_id,
                    ),
                )
                check_core_history = cursor2.fetchone()

                core_list_value = 1 if matched_row.get("core_list", "").strip() == "CN" else 0

                if check_core_history:
                    # If greater date catalog file does not exist, update month data
                    if not greater_date_file_exist:
                        try:
                            update_query = (
                                "UPDATE check_core_history SET updated_at = %s, price_file_date = %s, core_list = %s "
                                "WHERE id = %s"
                            )
                            cursor2.execute(
                                update_query,
                                (
                                    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                    date,
                                    core_list_value,
                                    check_core_history[0],
                                ),
                            )
                            conn2.commit()
                        except mysql.connector.Error as e:
                            print(f"Error updating check_core_history: {e}")
                            log_to_laravel(f"Error updating check_core_history: {e}")
                            conn2.rollback()  # Rollback on error
                else:
                    # Insert new check_core_history
                    try:
                        insert_query = (
                            """INSERT INTO check_core_history (customer_id, catalog_item_id, price_file_date, created_at,
                            updated_at, catalog_price_type_id, core_list) VALUES (%s, %s, %s, %s, %s, %s, %s)"""
                        )
                        cursor2.execute(
                            insert_query,
                            (
                                1,  # Replace with `matched_row["Customer Id"]` if needed
                                catalog_item_id,
                                date,
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                                catalog_price_type_id,
                                core_list_value,
                            ),
                        )
                        conn2.commit()
                    except mysql.connector.Error as e:
                        print(f"Error inserting into check_core_history: {e}")
                        log_to_laravel(f"Error inserting into check_core_history: {e}")
                        conn2.rollback()  # Rollback on error

            except mysql.connector.Error as e:
                print(f"Database error: {e}")
                log_to_laravel(f"Database error updating check_core_history: {e}")
                conn2.rollback()

            except KeyError as e:
                print(f"Missing key in matched_row: {e}")
                log_to_laravel(f"Missing key in matched_row check_core_history: {e}")

            except Exception as e:
                print(f"Unexpected error: {e}")
                log_to_laravel(f"Unexpected error updating check_core_history: {e}")
            ################ check_core_history End ###################
        except mysql.connector.Error as err:
            print(f"❌ DB Error for item {catalog_item_id}: {err}")
            with lock:
                current_faileds.append(catalog_item_id)
            if conn2.is_connected():
                conn2.rollback()

        finally:
            cursor2.close()
            conn2.close()  # Always close the connection to prevent memory leaks
            return True

    # Process search terms with retries
    def process_with_retries(search_terms, max_retries):
        global main_search_term,progress_percent_db,future_to_sku,last_written_percent,current_failed,not_available_sku_on_web,current_faileds
        processed_count = 0
        current_faileds = []
        not_available_sku_on_web = []
        progress_percent_db = 0
        future_to_sku = {}
        last_written_percent = -1
        main_search_term = search_terms
        try:
            conn = mysql.connector.connect(
                host=os.getenv("DB_HOST", "127.0.0.1"),
                user=os.getenv("DB_USERNAME", "roo1"),
                password=os.getenv("DB_PASSWORD", "Password123#@!"),
                database=os.getenv("DB_DATABASE", "sp16")
            )
            cursor = conn.cursor()
            chunk_size = 100000

            print("🔄 Dropping and creating temporary table...")
            cursor.execute("DROP TEMPORARY TABLE IF EXISTS temp_skus")
            cursor.execute("""
                CREATE TEMPORARY TABLE temp_skus (
                    sku VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY
                )
            """)

            print("🚀 Inserting SKUs into temp_skus...")
            insert_query = "INSERT IGNORE INTO temp_skus (sku) VALUES (%s)"
            for i in range(0, len(search_terms), chunk_size):
                chunk = search_terms[i:i+chunk_size]
                cursor.executemany(insert_query, [(sku,) for sku in chunk])
            conn.commit()

            print("🔍 Fetching matching SKUs...")
            cursor.execute("""
                SELECT c.id, t.sku
                FROM temp_skus t
                JOIN catalog_items c ON c.sku = t.sku
            """)
            matching_rows = cursor.fetchall()
            matching_sku = {item_id: sku for item_id, sku in matching_rows}

            print("✅ Updating active status...")
            cursor.execute("""
                UPDATE catalog_items c
                JOIN temp_skus t ON c.sku = t.sku
                SET c.active = 1
            """)
            conn.commit()

            print("🔎 Getting non-matching SKUs...")
            cursor.execute("""
                SELECT t.sku
                FROM temp_skus t
                LEFT JOIN catalog_items c ON c.sku = t.sku
                LEFT JOIN failed_sku f ON f.sku = t.sku
                WHERE c.sku IS NULL AND f.sku IS NULL
            """)
            non_matching_skus = [row[0] for row in cursor.fetchall()]
        except mysql.connector.Error as err:
            print(f"❌ DB Error: {err}")
            if conn.is_connected():
                conn.rollback()
        finally:
            cursor.close()
            conn.close()

        print("📦 Processing matched SKUs...")
        if matching_sku:
            tqdm_args = dict(
                total=len(matching_sku),
                desc="Processing SKUs",
                file=sys.stdout,
                dynamic_ncols=True,
                leave=True,
                ncols=100
            )

            batch_size = 1000  # or 5000 depending on memory
            all_items = list(matching_sku.items())

            with ThreadPoolExecutor(max_workers=2) as executor:                        
                with tqdm(**tqdm_args) as pbar:
                    for i in range(0, len(all_items), batch_size):
                        batch = all_items[i:i+batch_size]
                        futures = []
                        for item_id, sku in batch:
                            record = sku_data.loc[sku_data["sku"] == sku]
                            record = record.dropna(axis=1, how='all')
                            record = record.loc[:, ~record.columns.astype(str).str.contains("^None$", na=False)]

                            if not record.empty:
                                matched_row = record.to_dict(orient="records")[0]
                                if matched_row.get('web_price'):
                                    futures.append(executor.submit(adding_record_without_scraping_into_database, matched_row, item_id))
                            else:
                                print(f"SKU {sku} not found in Excel.")

                        for future in as_completed(futures):
                            try: 
                                result = future.result(timeout=30)  # timeout for safety
                            except Exception as e:
                                print(f"❌ Error in threaded insert: {e}")
                            finally:
                                with lock:
                                    processed_count += 1
                                    pbar.update(1)
                                    new_percent = int((processed_count / len(main_search_term)) * 100)

                                    if new_percent > last_written_percent:
                                        progress_percent_db = new_percent
                                        last_written_percent = new_percent
                                        try:
                                            conn1 = mysql.connector.connect(
                                                host=os.getenv("DB_HOST", "127.0.0.1"),
                                                user=os.getenv("DB_USERNAME", "roo1"),
                                                password=os.getenv("DB_PASSWORD", "Password123#@!"),
                                                database=os.getenv("DB_DATABASE", "sp16")
                                            )

                                            cursor1 = conn1.cursor()
                                            cursor1.execute(
                                                "UPDATE catalog_attachments SET file_upload_percent = %s WHERE id = %s",
                                                (progress_percent_db, file_id)
                                            )
                                            conn1.commit()
                                        except mysql.connector.Error as err:
                                            log_to_laravel(f"❌ DB Error during percentage update: {err}")
                                            print(f"❌ DB Error: {err}")
                                            if conn1.is_connected():
                                                conn1.rollback()
                                        finally:
                                            cursor1.close()
                                            conn1.close()
    
        search_terms = non_matching_skus

        print(f"\n✅ Matching SKUs: {len(matching_sku)}")
        print(f"❌ Non-Matching SKUs: {len(non_matching_skus)}")
        
        print(len(search_terms))
        # processed_count = 133976

        # For web scraping
        for attempt in range(max_retries + 1):
            with tqdm(
                total=len(search_terms), desc=f"Processing SKUs (Attempt {attempt + 1})"
            ) as pbar:
                with ThreadPoolExecutor(max_workers=2) as executor:
                    future_to_sku = {
                        executor.submit(process_search_term, term): term
                        for term in search_terms
                    }

                    current_failed = []
                    # failed_skus.clear()

                    for future in as_completed(future_to_sku):
                        sku = future_to_sku[future]
                        result = future.result()
                        if result:
                            # print(result["breadcrumbs"])
                            if result["sku_availabe"] is None:
                                if result["category"] and result["sub_category"]:
                                    # Select the row where 'sku' matches the search term
                                    record = sku_data.loc[sku_data["sku"] == result["search_term"]]

                                    # Drop columns where all values are NaN (None is treated as NaN in pandas)
                                    record = record.dropna(axis=1, how='all')

                                    # Drop columns that are literally named 'None' (as a string)
                                    record = record.loc[:, ~record.columns.astype(str).str.contains("^None$", na=False)]

                                    if not record.empty:
                                        matched_row = record.to_dict(orient="records")[0]  # Convert to dictionary for database insert

                                        if matched_row.get('web_price'):  # safely checks key
                                            # Passing excel and web scraped data into database insert function
                                            adding_record_into_database(matched_row, result)
                                    else:
                                        print(f"SKU {result['search_term']} not found.")

                                    results.append(result)
                            else:
                                not_available_sku_on_web.append(results)
                        else:
                            sku = future_to_sku[future]
                            if any(f['sku'] == sku and f['error'] == 'Timeout' for f in failed_skus):
                                current_failed.append(sku)

                        if processed_count % 250 == 0:
                            print("Waiting for 60 seconds...")
                            time.sleep(60)

                        pbar.update(1)

                        processed_count += 1
                        new_percent = int((processed_count / len(main_search_term)) * 100)

                        if new_percent > last_written_percent:
                            progress_percent_db = new_percent
                            last_written_percent = new_percent
                            try:
                                conn1 = mysql.connector.connect(
                                    host=os.getenv("DB_HOST", "127.0.0.1"),
                                    user=os.getenv("DB_USERNAME", "roo1"),
                                    password=os.getenv("DB_PASSWORD", "Password123#@!"),
                                    database=os.getenv("DB_DATABASE", "sp16")
                                )

                                cursor1 = conn1.cursor()
                                cursor1.execute(
                                    "UPDATE catalog_attachments SET file_upload_percent = %s WHERE id = %s",
                                    (progress_percent_db, file_id)
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
                    if not current_failed:
                        break
                    else:
                        processed_count = processed_count - int(len(current_failed))

                    search_terms = current_failed
                executor.shutdown(wait=True)  # Ensure all threads finish before exiting
    start_time = time.time()
    process_with_retries(search_terms, max_retries)
    
    # pause_thread.join()  # Ensure the thread exits cleanly

    # Remove rows where "Sku Number" is in failed_skus
    # sku_data = sku_data[
    #     ~sku_data["sku"].astype(str).isin(failed_skus)
    # ]  # Convert column to string

    # Save back to the same Excel file (overwrite)
    # sku_data.to_excel(input_file, index=False)

    try:
        conn4 = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_DATABASE", "sp16")
        )
        cursor4 = conn4.cursor()
        
        # Update cron status to indicate completion
        cursor4.execute("UPDATE catalog_attachments SET cron = 6 WHERE id = %s", (file_id,))
        conn4.commit()

    except mysql.connector.Error as err:
        print(f"❌ DB Error: {err}")
        log_to_laravel(f"❌ DB Error during cron update: {err}")
        # if 'conn4' in locals() and conn4.is_connected():
            # conn4.rollback()

    finally:
        if 'cursor4' in locals() and cursor4:
            cursor4.close()
        if 'conn4' in locals() and conn4.is_connected():
            conn4.close()

    print("Uploaded files processed successfully.")
    print(current_failed)
    print(failed_skus)
    # exit()
    # Get output directory from .env
    output_dir = os.getenv("CATALOG_JSON_OUTPUT_DIR", "/var/www/html/supplier_ds/importdemo/storage/catalog_json")
    
    os.makedirs(output_dir, exist_ok=True)  # Make sure directory exists
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    json_file_name = f"catalog_output_{timestamp}"


    if results:
        with open(os.path.join(output_dir, f"{timestamp}_{json_file_name}.json"), "w") as json_file:
            json.dump(results, json_file, indent=4)

    if not_available_sku_on_web:
        conn5 = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            user=os.getenv("DB_USERNAME", "roo1"),
            password=os.getenv("DB_PASSWORD", "Password123#@!"),
            database=os.getenv("DB_DATABASE", "sp16")
        )
        cursor5 = conn5.cursor()

        for sku in not_available_sku_on_web:
            try:
                # Check if the sku exists
                cursor5.execute("SELECT * FROM failed_sku WHERE sku = %s LIMIT 1", (sku["search_term"],),)
                exist_sku = cursor5.fetchone()
                
                if exist_sku is None:
                    # Insert not find skus into failed_sku table
                    cursor5.execute("INSERT INTO `failed_sku` (`sku`, `created_at`, `updated_at`) VALUES(%s, %s, %s)", (sku["search_term"], datetime.now().strftime("%Y-%m-%d %H:%M:%S"), datetime.now().strftime("%Y-%m-%d %H:%M:%S")))
                    conn5.commit()

            except mysql.connector.Error as err:
                print(f"❌ DB Error: {err}")
                log_to_laravel(f"❌ DB Error during cron update: {err}")
            finally:
                if 'cursor5' in locals() and cursor5:
                    cursor5.close()
                if 'conn5' in locals() and conn5.is_connected():
                    conn5.close()    
        
        pd.DataFrame(not_available_sku_on_web).to_excel(
            os.path.join(output_dir, f"{timestamp}_{json_file_name}_faild_skus.xlsx"), index=False
        )

    if current_failed:    
        pd.DataFrame(current_failed).to_excel(
            os.path.join(output_dir, f"{timestamp}_{json_file_name}_faild_skus.xlsx"), index=False
        )

    print(f"Total time taken: {(time.time() - start_time) / 60:.2f} minutes.")
