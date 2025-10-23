"""Tkinter based graphical user interface."""

from __future__ import annotations

import sqlite3
import tkinter as tk
from datetime import datetime
from tkinter import ttk
from typing import Dict, List, Optional

from .exceptions import SeatAvailabilityError, ValidationError
from .models import Booking, Route, RouteAvailability
from .repository import BusRepository
from .validators import (
    parse_departure,
    require_non_negative_float,
    require_positive_int,
    require_text,
    validate_contact,
)


class Application(ttk.Frame):
    """Main application window."""

    def __init__(self, master: tk.Tk, repository: BusRepository) -> None:
        super().__init__(master, padding=20)
        self.repository = repository
        self.route_lookup: Dict[int, RouteAvailability] = {}

        self.pack(fill="both", expand=True)
        self._configure_root(master)
        self._create_styles()
        self._build_ui()
        self.refresh_all()

    def _configure_root(self, master: tk.Tk) -> None:
        master.title("SwiftSeat Bus Booking")
        master.geometry("980x640")
        master.minsize(860, 560)

    def _create_styles(self) -> None:
        style = ttk.Style()
        try:
            style.theme_use("clam")
        except tk.TclError:
            pass
        style.configure("Card.TLabelframe", padding=18)
        style.configure("Accent.TButton", padding=8)
        style.map(
            "Accent.TButton",
            foreground=[("pressed", "white"), ("active", "white")],
            background=[("pressed", "#005f73"), ("active", "#0a9396")],
        )

    def _build_ui(self) -> None:
        notebook = ttk.Notebook(self)
        notebook.pack(fill="both", expand=True)

        self.routes_frame = ttk.Frame(notebook, padding=10)
        self.bookings_frame = ttk.Frame(notebook, padding=10)

        notebook.add(self.routes_frame, text="Routes")
        notebook.add(self.bookings_frame, text="Bookings")

        self._build_routes_tab()
        self._build_bookings_tab()

        self.status = tk.StringVar(value="Ready")
        self.status_label = ttk.Label(self, textvariable=self.status, anchor="w")
        self.status_label.pack(fill="x", pady=(12, 0))

    # Routes tab
    def _build_routes_tab(self) -> None:
        form_group = ttk.LabelFrame(self.routes_frame, text="Add new route", style="Card.TLabelframe")
        form_group.pack(fill="x", pady=(0, 16))

        self.route_entries = {
            "bus_number": self._create_labeled_entry(form_group, "Bus number", 0, 0),
            "origin": self._create_labeled_entry(form_group, "Origin", 0, 1),
            "destination": self._create_labeled_entry(form_group, "Destination", 0, 2),
            "departure": self._create_labeled_entry(form_group, "Departure (YYYY-MM-DD HH:MM)", 1, 0),
            "total_seats": self._create_labeled_entry(form_group, "Total seats", 1, 1),
            "price": self._create_labeled_entry(form_group, "Ticket price", 1, 2),
        }

        add_button = ttk.Button(
            form_group,
            text="Add route",
            style="Accent.TButton",
            command=self._handle_add_route,
        )
        add_button.grid(row=2, column=0, columnspan=3, sticky="ew", pady=(12, 0))

        self.routes_tree = ttk.Treeview(
            self.routes_frame,
            columns=("bus", "origin", "destination", "departure", "available", "price"),
            show="headings",
            height=10,
        )
        for column, heading, width in (
            ("bus", "Bus", 120),
            ("origin", "Origin", 120),
            ("destination", "Destination", 140),
            ("departure", "Departure", 180),
            ("available", "Seats left", 100),
            ("price", "Price", 80),
        ):
            self.routes_tree.heading(column, text=heading)
            self.routes_tree.column(column, width=width, anchor="center")
        self.routes_tree.pack(fill="both", expand=True)

    def _create_labeled_entry(
        self,
        parent: tk.Widget,
        label: str,
        row: int,
        column: int,
    ) -> ttk.Entry:
        frame = ttk.Frame(parent)
        frame.grid(row=row, column=column, padx=6, pady=6, sticky="ew")
        parent.grid_columnconfigure(column, weight=1)

        ttk.Label(frame, text=label).pack(anchor="w")
        entry = ttk.Entry(frame)
        entry.pack(fill="x")
        return entry

    # Bookings tab
    def _build_bookings_tab(self) -> None:
        card = ttk.LabelFrame(self.bookings_frame, text="Create booking", style="Card.TLabelframe")
        card.pack(fill="x", pady=(0, 16))

        self.route_selection = tk.StringVar()
        ttk.Label(card, text="Route").grid(row=0, column=0, sticky="w", padx=6, pady=(0, 4))
        self.route_combo = ttk.Combobox(card, textvariable=self.route_selection, state="readonly")
        self.route_combo.grid(row=1, column=0, columnspan=2, sticky="ew", padx=6)

        self.passenger_entries = {
            "name": self._create_labeled_entry(card, "Passenger name", 2, 0),
            "contact": self._create_labeled_entry(card, "Contact", 2, 1),
            "seats": self._create_labeled_entry(card, "Seats to book", 3, 0),
        }
        card.grid_columnconfigure(0, weight=1)
        card.grid_columnconfigure(1, weight=1)

        ttk.Button(
            card,
            text="Confirm booking",
            style="Accent.TButton",
            command=self._handle_booking,
        ).grid(row=4, column=0, columnspan=2, sticky="ew", pady=(12, 0))

        self.availability_label = ttk.Label(card, text="")
        self.availability_label.grid(row=5, column=0, columnspan=2, sticky="w", padx=6, pady=(10, 0))

        self.bookings_tree = ttk.Treeview(
            self.bookings_frame,
            columns=("passenger", "route", "seats", "contact", "booked"),
            show="headings",
            height=10,
        )
        for column, heading, width in (
            ("passenger", "Passenger", 160),
            ("route", "Route", 240),
            ("seats", "Seats", 60),
            ("contact", "Contact", 150),
            ("booked", "Booked at", 150),
        ):
            self.bookings_tree.heading(column, text=heading)
            anchor = "center" if column in {"seats", "booked"} else "w"
            self.bookings_tree.column(column, width=width, anchor=anchor)
        self.bookings_tree.pack(fill="both", expand=True)

        self.route_combo.bind("<<ComboboxSelected>>", lambda _: self._update_availability())

    def refresh_all(self) -> None:
        self._load_routes()
        self._load_bookings()
        self._update_availability()

    def _load_routes(self) -> None:
        for item in self.routes_tree.get_children():
            self.routes_tree.delete(item)
        routes = self.repository.list_routes()
        self.route_lookup = {route.route.id: route for route in routes if route.route.id is not None}

        options: List[str] = []
        for route in routes:
            departure = route.route.departure_time.strftime("%Y-%m-%d %H:%M")
            price = f"${route.route.price:,.2f}"
            self.routes_tree.insert(
                "",
                "end",
                iid=str(route.route.id),
                values=(
                    route.route.bus_number,
                    route.route.origin,
                    route.route.destination,
                    departure,
                    route.seats_available,
                    price,
                ),
            )
            options.append(
                f"{route.route.bus_number} | {route.route.origin} → {route.route.destination} | {departure}"
            )
        self.route_combo["values"] = options
        if options:
            if self.route_selection.get() not in options:
                self.route_selection.set(options[0])
        else:
            self.route_selection.set("")

    def _load_bookings(self) -> None:
        for item in self.bookings_tree.get_children():
            self.bookings_tree.delete(item)
        bookings = self.repository.list_bookings()
        for booking in bookings:
            route_info = self.route_lookup.get(booking.route_id)
            route_summary = "Unknown route"
            if route_info:
                route = route_info.route
                route_summary = (
                    f"{route.bus_number} {route.origin}→{route.destination}"
                    f" @ {route.departure_time.strftime('%Y-%m-%d %H:%M')}"
                )
            self.bookings_tree.insert(
                "",
                "end",
                values=(
                    booking.passenger_name,
                    route_summary,
                    booking.seats_booked,
                    booking.passenger_contact,
                    booking.booked_at.strftime("%Y-%m-%d %H:%M"),
                ),
            )

    def _update_availability(self) -> None:
        selection = self.route_selection.get()
        if not selection:
            self.availability_label.config(text="")
            return
        route_id = self._selected_route_id()
        if route_id is None:
            self.availability_label.config(text="")
            return
        seats = self.repository.get_available_seats(route_id)
        self.availability_label.config(text=f"Seats remaining: {seats}")

    def _selected_route_id(self) -> Optional[int]:
        selection = self.route_selection.get()
        for route_id, availability in self.route_lookup.items():
            departure = availability.route.departure_time.strftime("%Y-%m-%d %H:%M")
            summary = (
                f"{availability.route.bus_number} | {availability.route.origin} → {availability.route.destination} | {departure}"
            )
            if summary == selection:
                return route_id
        return None

    def _handle_add_route(self) -> None:
        try:
            bus_number = require_text("Bus number", self.route_entries["bus_number"].get())
            origin = require_text("Origin", self.route_entries["origin"].get())
            destination = require_text("Destination", self.route_entries["destination"].get())
            departure = parse_departure("Departure", self.route_entries["departure"].get())
            total_seats = require_positive_int("Total seats", self.route_entries["total_seats"].get())
            price = require_non_negative_float("Ticket price", self.route_entries["price"].get())
        except ValidationError as exc:
            self._set_status(str(exc), error=True)
            return

        route = Route(
            id=None,
            bus_number=bus_number,
            origin=origin,
            destination=destination,
            departure_time=departure,
            total_seats=total_seats,
            price=price,
        )
        try:
            self.repository.add_route(route)
        except sqlite3.IntegrityError:
            self._set_status("Bus number must be unique.", error=True)
            return
        except Exception as exc:  # pragma: no cover - defensive fallback
            self._set_status(f"Failed to add route: {exc}", error=True)
            return

        for entry in self.route_entries.values():
            entry.delete(0, tk.END)
        self._set_status("Route added successfully.")
        self.refresh_all()

    def _handle_booking(self) -> None:
        route_id = self._selected_route_id()
        if route_id is None:
            self._set_status("Please select a route to book.", error=True)
            return
        try:
            passenger_name = require_text("Passenger name", self.passenger_entries["name"].get())
            contact = validate_contact(self.passenger_entries["contact"].get())
            seats = require_positive_int("Seats", self.passenger_entries["seats"].get())
        except ValidationError as exc:
            self._set_status(str(exc), error=True)
            return

        booking = Booking(
            id=None,
            route_id=route_id,
            passenger_name=passenger_name,
            passenger_contact=contact,
            seats_booked=seats,
            booked_at=datetime.now(),
        )
        try:
            self.repository.add_booking(booking)
        except SeatAvailabilityError as exc:
            self._set_status(str(exc), error=True)
            return
        except Exception as exc:  # pragma: no cover - defensive fallback
            self._set_status(f"Failed to create booking: {exc}", error=True)
            return

        for entry in self.passenger_entries.values():
            entry.delete(0, tk.END)
        self._set_status("Booking confirmed.")
        self.refresh_all()

    def _set_status(self, message: str, *, error: bool = False) -> None:
        self.status.set(message)
        color = "#ae2012" if error else "#0a9396"
        self.status_label.configure(foreground=color)


def launch_gui(repository: BusRepository) -> None:
    root = tk.Tk()
    Application(root, repository)
    root.mainloop()
