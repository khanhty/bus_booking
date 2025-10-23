from datetime import datetime

import pytest

from bus_booking.database import Database
from bus_booking.exceptions import SeatAvailabilityError
from bus_booking.models import Booking, Route
from bus_booking.repository import BusRepository


def create_repository(tmp_path):
    database = Database(tmp_path / "test.sqlite3")
    return BusRepository(database)


def test_route_creation_and_availability(tmp_path):
    repo = create_repository(tmp_path)
    route = repo.add_route(
        Route(
            id=None,
            bus_number="AA101",
            origin="City A",
            destination="City B",
            departure_time=datetime(2024, 5, 1, 10, 0),
            total_seats=20,
            price=25.0,
        )
    )
    assert repo.get_available_seats(route.id) == 20

    repo.add_booking(
        Booking(
            id=None,
            route_id=route.id,
            passenger_name="Alice",
            passenger_contact="+1234567890",
            seats_booked=5,
            booked_at=datetime(2024, 4, 1, 9, 0),
        )
    )
    assert repo.get_available_seats(route.id) == 15

    with pytest.raises(SeatAvailabilityError):
        repo.add_booking(
            Booking(
                id=None,
                route_id=route.id,
                passenger_name="Bob",
                passenger_contact="+1987654321",
                seats_booked=16,
                booked_at=datetime(2024, 4, 1, 9, 30),
            )
        )
