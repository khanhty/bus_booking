<?php
/**
 * Plugin Name: SwiftSeat Bus Booking
 * Description: Lightweight route management and booking workflow tailored for small coach operators.
 * Version: 1.0.0
 * Author: SwiftSeat Contributors
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SWIFTSEAT_BUS_BOOKING_DIR', plugin_dir_path(__FILE__));
define('SWIFTSEAT_BUS_BOOKING_URL', plugin_dir_url(__FILE__));

require_once SWIFTSEAT_BUS_BOOKING_DIR . 'includes/class-bus-booking-plugin.php';

function swiftseat_bus_booking_activate() {
    \SwiftSeat\BusBooking\Plugin::activate();
}
register_activation_hook(__FILE__, 'swiftseat_bus_booking_activate');

function swiftseat_bus_booking_init() {
    $plugin = \SwiftSeat\BusBooking\Plugin::instance();
    $plugin->init();
}
add_action('plugins_loaded', 'swiftseat_bus_booking_init');
