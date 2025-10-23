"""Database utilities for the bus booking application."""

from __future__ import annotations

import sqlite3
from contextlib import contextmanager
from pathlib import Path
from typing import Iterator

DEFAULT_DB_PATH = Path(__file__).resolve().parent.parent / "data" / "bus_booking.sqlite3"


class Database:
    """Simple SQLite database wrapper with schema initialization."""

    def __init__(self, db_path: Path | str = DEFAULT_DB_PATH) -> None:
        self.db_path = Path(db_path)
        self.db_path.parent.mkdir(parents=True, exist_ok=True)

    def connect(self) -> sqlite3.Connection:
        connection = sqlite3.connect(self.db_path)
        connection.row_factory = sqlite3.Row
        connection.execute("PRAGMA foreign_keys = ON")
        return connection

    @contextmanager
    def connection(self) -> Iterator[sqlite3.Connection]:
        conn = self.connect()
        try:
            yield conn
            conn.commit()
        except Exception:
            conn.rollback()
            raise
        finally:
            conn.close()

    def initialize_schema(self) -> None:
        with self.connection() as conn:
            conn.executescript(
                """
                CREATE TABLE IF NOT EXISTS routes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    bus_number TEXT NOT NULL UNIQUE,
                    origin TEXT NOT NULL,
                    destination TEXT NOT NULL,
                    departure_time TEXT NOT NULL,
                    total_seats INTEGER NOT NULL CHECK(total_seats > 0),
                    price REAL NOT NULL CHECK(price >= 0)
                );

                CREATE TABLE IF NOT EXISTS bookings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    route_id INTEGER NOT NULL,
                    passenger_name TEXT NOT NULL,
                    passenger_contact TEXT NOT NULL,
                    seats_booked INTEGER NOT NULL CHECK(seats_booked > 0),
                    booked_at TEXT NOT NULL DEFAULT (datetime('now')),
                    FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE CASCADE
                );
                """
            )
