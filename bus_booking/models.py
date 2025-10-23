"""Domain models for the bus booking application."""

from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from typing import Optional


def parse_datetime(value: str) -> datetime:
    return datetime.fromisoformat(value)


@dataclass(slots=True)
class Route:
    id: Optional[int]
    bus_number: str
    origin: str
    destination: str
    departure_time: datetime
    total_seats: int
    price: float


@dataclass(slots=True)
class RouteAvailability:
    route: Route
    seats_available: int


@dataclass(slots=True)
class Booking:
    id: Optional[int]
    route_id: int
    passenger_name: str
    passenger_contact: str
    seats_booked: int
    booked_at: datetime
