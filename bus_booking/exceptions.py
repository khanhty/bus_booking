"""Custom exceptions used by the bus booking domain."""


class BookingError(Exception):
    """Base class for booking related issues."""


class ValidationError(BookingError):
    """Raised when user input fails validation."""


class SeatAvailabilityError(BookingError):
    """Raised when there are not enough seats remaining for a booking."""
