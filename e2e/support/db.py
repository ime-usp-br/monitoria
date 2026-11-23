import os

import pymysql


def connect():
    return pymysql.connect(
        host=os.environ.get("E2E_DB_HOST", "db"),
        port=int(os.environ.get("E2E_DB_PORT", "3306")),
        user=os.environ.get("E2E_DB_USER", "e2e"),
        password=os.environ.get("E2E_DB_PASSWORD", "e2e"),
        database=os.environ.get("E2E_DB_NAME", "monitoria_e2e"),
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True,
    )


def fetch_one(sql: str, params: tuple = ()):
    with connect() as connection:
        with connection.cursor() as cursor:
            cursor.execute(sql, params)
            return cursor.fetchone()
