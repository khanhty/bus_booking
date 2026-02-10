# SwiftSeat WordPress Plugin Guide

This plugin implements a **bus booking and user management system** for WordPress based on your specification.

## Implemented features

- Custom user registration/login profile fields: username, password, email, first/last name, phone, employee ID, department, position.
- Booking limited to authenticated custom users (separate from default WordPress users).
- Booking page defaults to today's schedule and supports date filtering.
- Multi-seat booking with seat capacity validation.
- Booking cancellation for users; admin can cancel/move bookings.
- Bus management and schedule management (add + serial generation daily/weekly/monthly).
- Feedback and rating submission + list page.
- Admin desk (users, buses, schedules, bookings monitor).
- Bulk delete for users/buses/schedules/bookings by IDs.
- Date range filter reporting in admin desk.
- Responsive UI and toggle menu.
- Flash pop-up notifications auto-close in 3 seconds.
- Bilingual labels for key navigation in English/Lao and language switch links.
- Teams webhook integration for user chat messages (set option `ssb_ms_teams_webhook`).

## Installation

1. Copy folder `swiftseat-bus-booking` into `wp-content/plugins/`.
2. Activate **SwiftSeat Bus Booking and User Management** in WordPress plugins.
3. Create the following pages and place each shortcode:

| Page slug | Shortcode |
|---|---|
| `/register` | `[ssb_register]` |
| `/login` | `[ssb_login]` |
| `/profile` | `[ssb_profile]` |
| `/booking` | `[ssb_booking]` |
| `/feedback` | `[ssb_feedback]` |
| `/chat` | `[ssb_chat]` |
| `/admin-desk` | `[ssb_admin]` |

4. Register users normally; set role to `admin` or `owner` directly in table `wp_ssb_users` for plugin-level admin rights.

## Quick Admin Guide

1. Register account from `/register`.
2. Set the account `role` to `admin` in `wp_ssb_users` table.
3. Open `/admin-desk`:
   - Add buses.
   - Add schedules (one-time or repeated serial).
   - Filter booking reports by date range.
   - Move or cancel bookings.
   - Run bulk delete.

## Quick User Guide (One Page)

1. Register and login.
2. Open `/booking` to view today's schedules.
3. Change date if needed.
4. Book one or more seats.
5. Cancel from **My Bookings**.
6. Update data in `/profile`.
7. Give feedback in `/feedback`.
8. Chat in `/chat`.
9. Switch language using EN/ລາວ.

## Notes

- Overdue schedules are auto-marked unavailable when booking/admin desk pages load.
- Admin receives booking email notifications using WordPress `admin_email`.
- Use HTTPS in production for secure cookie handling and keep WordPress hardened.
