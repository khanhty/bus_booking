# SwiftSeat Bus Booking (WordPress)

SwiftSeat is a minimalist WordPress plugin that gives small coach operators a clean way to publish routes and capture seat reservations directly from their site. It ships with an opinionated admin experience for managing departures and a polished front-end form that keeps passengers informed about remaining seats in real time.

## Highlights

- ✅ **Purpose-built database tables** that keep routes and bookings separate while enforcing seat limits.
- 🧰 **WordPress-native admin screens** for adding, listing, and deleting routes and auditing recent bookings.
- 🛡️ **Security-first form handling** with nonces, sanitisation, and prepared statements to prevent overbooking and injection issues.
- 🎨 **Responsive front-end form** exposed through a shortcode and styled for modern WordPress themes.

## Installation

1. Copy the `wp-bus-booking` directory into your WordPress installation under `wp-content/plugins/`.
2. From the WordPress dashboard, navigate to **Plugins → Installed Plugins** and activate **SwiftSeat Bus Booking**.
3. On activation the plugin will create the `wp_swiftseat_routes` and `wp_swiftseat_bookings` tables (table prefixes respect your WordPress configuration).

## Usage

### Manage routes

1. Go to **SwiftSeat → Routes** in the WordPress admin.
2. Fill in the route title, origin, destination, departure date/time, seats, and optional ticket price.
3. Saved routes appear in the *Active routes* table where you can review details or delete a departure.

### Review bookings

- Open **SwiftSeat → Bookings** to see the latest reservations, including passenger contact info and seat counts.

### Embed the booking form

1. Create or edit any WordPress page or post.
2. Add the shortcode:

   ```
   [swiftseat_bus_booking]
   ```

3. Publish the page. Visitors will see a styled form that lists all upcoming routes and dynamically enforces available seat counts when submitting.

## Customisation tips

- Use CSS variables in your theme or custom CSS plugin to override the `.swiftseat-*` classes defined in `assets/swiftseat.css`.
- Filter the shortcode output if you need to wrap the form in additional markup by using the standard WordPress filters on page content.
- Extend the plugin by hooking into `SwiftSeat\BusBooking\Plugin` methods or forking the repository to add features such as email notifications.

## Development

- The plugin relies solely on core WordPress APIs—no external dependencies are required.
- Tables are installed via `dbDelta` on activation; deactivation deliberately leaves data intact so you can preserve booking history.
- When modifying the database schema, bump the plugin version and rerun activation (or call `Plugin::activate()`) to update the tables safely.

Enjoy offering a streamlined booking experience directly within WordPress!
