<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('SwiftSeat Bookings', 'swiftseat'); ?></h1>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php esc_html_e('Passenger', 'swiftseat'); ?></th>
                <th><?php esc_html_e('Route', 'swiftseat'); ?></th>
                <th><?php esc_html_e('Seats', 'swiftseat'); ?></th>
                <th><?php esc_html_e('Booked at', 'swiftseat'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bookings)) : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e('No bookings captured yet.', 'swiftseat'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($bookings as $booking) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($booking->passenger_name); ?></strong><br>
                            <a href="mailto:<?php echo esc_attr($booking->passenger_email); ?>">
                                <?php echo esc_html($booking->passenger_email); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($booking->route_title); ?></td>
                        <td><?php echo esc_html($booking->seats); ?></td>
                        <td><?php echo esc_html(get_date_from_gmt($booking->created_at, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
