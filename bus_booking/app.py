"""Application entrypoint for the SwiftSeat bus booking GUI."""

from __future__ import annotations

import argparse
from datetime import datetime, timedelta
from pathlib import Path
from typing import Iterable

from .database import Database, DEFAULT_DB_PATH
from .models import Route
from .repository import BusRepository
from .gui import launch_gui


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="SwiftSeat bus booking application")
    parser.add_argument(
        "--database",
        type=Path,
        default=DEFAULT_DB_PATH,
        help="Location of the SQLite database file (defaults to data/bus_booking.sqlite3)",
    )
    parser.add_argument(
        "--with-sample-data",
        action="store_true",
        help="Populate a few demo routes on start-up if the table is empty.",
    )
    return parser.parse_args()


def ensure_sample_data(repository: BusRepository) -> None:
    if repository.list_routes():
        return
    base_time = datetime.now().replace(minute=0, second=0, microsecond=0)
    demo_routes: Iterable[Route] = (
        Route(None, "HX101", "New York", "Washington", base_time + timedelta(hours=6), 40, 49.99),
        Route(None, "HX205", "San Francisco", "Los Angeles", base_time + timedelta(hours=10), 48, 79.99),
        Route(None, "HX315", "Chicago", "Detroit", base_time + timedelta(hours=4), 36, 39.99),
    )
    for route in demo_routes:
        repository.add_route(route)


def main() -> None:
    args = parse_args()
    database = Database(args.database)
    repository = BusRepository(database)

    if args.with_sample_data:
        ensure_sample_data(repository)

    launch_gui(repository)


if __name__ == "__main__":
    main()
