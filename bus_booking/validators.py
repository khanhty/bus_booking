"""Validation helpers for form data."""

from __future__ import annotations

import re
from datetime import datetime

from .exceptions import ValidationError

ISO_DATETIME_FORMAT = "%Y-%m-%d %H:%M"
CONTACT_PATTERN = re.compile(r"^[+\d][\d\s-]{6,}$")


def require_text(field: str, value: str) -> str:
    if not value or not value.strip():
        raise ValidationError(f"{field} is required.")
    return value.strip()


def require_positive_int(field: str, value: str) -> int:
    require_text(field, value)
    try:
        number = int(value)
    except ValueError as exc:
        raise ValidationError(f"{field} must be a whole number.") from exc
    if number <= 0:
        raise ValidationError(f"{field} must be greater than zero.")
    return number


def require_non_negative_float(field: str, value: str) -> float:
    require_text(field, value)
    try:
        number = float(value)
    except ValueError as exc:
        raise ValidationError(f"{field} must be a valid number.") from exc
    if number < 0:
        raise ValidationError(f"{field} cannot be negative.")
    return number


def parse_departure(field: str, value: str) -> datetime:
    require_text(field, value)
    try:
        return datetime.strptime(value, ISO_DATETIME_FORMAT)
    except ValueError as exc:
        raise ValidationError(
            f"{field} must match the format YYYY-MM-DD HH:MM"
        ) from exc


def validate_contact(value: str) -> str:
    value = require_text("Contact", value)
    if not CONTACT_PATTERN.match(value):
        raise ValidationError(
            "Contact number must start with + or digits and contain at least 7 digits."
        )
    return value
