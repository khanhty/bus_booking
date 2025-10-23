# SwiftSeat Bus Booking

SwiftSeat is a desktop bus booking manager with an intuitive Tkinter based user interface. It lets you manage bus routes, track available seats, and record passenger bookings in a secure SQLite database.

## Features

- Modern tabbed interface built with themed Tk widgets.
- Route management with validation for duplicate bus numbers and clean ISO timestamp parsing.
- Live seat availability tracking that prevents overbooking.
- Secure parameterised database access with automatic schema migrations.
- Optional sample data seeding for quick demos.

## Getting started

1. **Install dependencies** – the application only requires the Python standard library. Ensure Python 3.10+ is installed.
2. **Launch the GUI**:

   ```bash
   python -m bus_booking.app --with-sample-data
   ```

   The `--with-sample-data` flag inserts a few demo routes if the database is empty. You can omit it to start with a clean system.

3. **Create bookings** – add new routes from the *Routes* tab, then switch to the *Bookings* tab to confirm passenger reservations. The status bar at the bottom shows validation errors or success messages.

## Configuration

- Use the `--database` flag to store data at a custom path:

  ```bash
  python -m bus_booking.app --database /tmp/my_bus_app.sqlite3
  ```

- The default database file lives at `data/bus_booking.sqlite3`. It is created automatically if missing.

## Project layout

```
bus_booking/
├── app.py             # Application entrypoint and CLI options
├── database.py        # SQLite helper with schema creation
├── exceptions.py      # Domain-specific exception hierarchy
├── gui.py             # Tkinter user interface
├── models.py          # Dataclasses describing routes and bookings
├── repository.py      # Data access layer and business logic
└── validators.py      # Form validation utilities
```

## Development tips

- Run `python -m bus_booking.app --with-sample-data` to verify that route creation and booking operations behave as expected.
- Delete the generated SQLite file if you need to reset the data store.

Enjoy managing your bus fleet with SwiftSeat!
