"""Database repository for the bus booking application."""

from __future__ import annotations

from datetime import datetime
from typing import List

from .database import Database
from .exceptions import SeatAvailabilityError
from .models import Booking, Route, RouteAvailability


class BusRepository:
    """High level data-access layer."""

    def __init__(self, database: Database) -> None:
        self.database = database
        self.database.initialize_schema()

    # Route operations
    def add_route(self, route: Route) -> Route:
        departure = route.departure_time.isoformat(timespec="minutes")
        with self.database.connection() as conn:
            cursor = conn.execute(
                """
                INSERT INTO routes (bus_number, origin, destination, departure_time, total_seats, price)
                VALUES (?, ?, ?, ?, ?, ?)
                """,
                (
                    route.bus_number,
                    route.origin,
                    route.destination,
                    departure,
                    route.total_seats,
                    route.price,
                ),
            )
            route_id = cursor.lastrowid
        return Route(
            id=route_id,
            bus_number=route.bus_number,
            origin=route.origin,
            destination=route.destination,
            departure_time=route.departure_time,
            total_seats=route.total_seats,
            price=route.price,
        )

    def list_routes(self) -> List[RouteAvailability]:
        with self.database.connection() as conn:
            rows = conn.execute(
                """
                SELECT
                    r.id,
                    r.bus_number,
                    r.origin,
                    r.destination,
                    r.departure_time,
                    r.total_seats,
                    r.price,
                    r.total_seats - IFNULL(SUM(b.seats_booked), 0) AS seats_available
                FROM routes AS r
                LEFT JOIN bookings AS b ON r.id = b.route_id
                GROUP BY r.id
                ORDER BY r.departure_time ASC
                """
            ).fetchall()
        results: List[RouteAvailability] = []
        for row in rows:
            route = Route(
                id=row["id"],
                bus_number=row["bus_number"],
                origin=row["origin"],
                destination=row["destination"],
                departure_time=datetime.fromisoformat(row["departure_time"]),
                total_seats=row["total_seats"],
                price=row["price"],
            )
            results.append(RouteAvailability(route=route, seats_available=row["seats_available"]))
        return results

    # Booking operations
    def add_booking(self, booking: Booking) -> Booking:
        available = self.get_available_seats(booking.route_id)
        if booking.seats_booked > available:
            raise SeatAvailabilityError(
                f"Only {available} seats remaining for this route."
            )
        with self.database.connection() as conn:
            cursor = conn.execute(
                """
                INSERT INTO bookings (
                    route_id, passenger_name, passenger_contact, seats_booked, booked_at
                ) VALUES (?, ?, ?, ?, ?)
                """,
                (
                    booking.route_id,
                    booking.passenger_name,
                    booking.passenger_contact,
                    booking.seats_booked,
                    booking.booked_at.isoformat(timespec="minutes"),
                ),
            )
            booking_id = cursor.lastrowid
        return Booking(
            id=booking_id,
            route_id=booking.route_id,
            passenger_name=booking.passenger_name,
            passenger_contact=booking.passenger_contact,
            seats_booked=booking.seats_booked,
            booked_at=booking.booked_at,
        )

    def list_bookings(self) -> List[Booking]:
        with self.database.connection() as conn:
            rows = conn.execute(
                """
                SELECT id, route_id, passenger_name, passenger_contact, seats_booked, booked_at
                FROM bookings
                ORDER BY booked_at DESC
                """
            ).fetchall()
        return [
            Booking(
                id=row["id"],
                route_id=row["route_id"],
                passenger_name=row["passenger_name"],
                passenger_contact=row["passenger_contact"],
                seats_booked=row["seats_booked"],
                booked_at=datetime.fromisoformat(row["booked_at"]),
            )
            for row in rows
        ]

    def get_available_seats(self, route_id: int) -> int:
        with self.database.connection() as conn:
            row = conn.execute(
                """
                SELECT
                    total_seats - IFNULL((
                        SELECT SUM(seats_booked) FROM bookings WHERE route_id = ?
                    ), 0) AS seats_available
                FROM routes
                WHERE id = ?
                """,
                (route_id, route_id),
            ).fetchone()
        if row is None:
            raise SeatAvailabilityError("Route does not exist.")
        return max(row["seats_available"], 0)
