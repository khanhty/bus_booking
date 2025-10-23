<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="swiftseat-booking-form">
    <?php if ($success) : ?>
        <div class="swiftseat-notice swiftseat-notice--success">
            <p><?php esc_html_e('Booking confirmed! Check your inbox for a confirmation email.', 'swiftseat'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($errors) : ?>
        <div class="swiftseat-notice swiftseat-notice--error">
            <ul>
                <?php foreach ($errors as $error) : ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($routes) : ?>
        <form method="post" class="swiftseat-form">
            <input type="hidden" name="swiftseat_booking_nonce" value="<?php echo esc_attr($nonce); ?>">
            <div class="swiftseat-field">
                <label for="swiftseat-route"><?php esc_html_e('Choose a route', 'swiftseat'); ?></label>
                <select id="swiftseat-route" name="route_id" required>
                    <option value=""><?php esc_html_e('Select a route', 'swiftseat'); ?></option>
                    <?php foreach ($routes as $route) : ?>
                        <?php
                        $label = sprintf(
                            '%s — %s ➜ %s (%s %s)',
                            $route['title'],
                            $route['origin'],
                            $route['destination'],
                            number_format_i18n((float) $route['seats_remaining'], 0),
                            __('seats left', 'swiftseat')
                        );

                        if ((float) $route['price'] > 0) {
                            $label .= sprintf(
                                ' · %s',
                                sprintf(
                                    /* translators: %s is the formatted ticket price. */
                                    __('Ticket: %s', 'swiftseat'),
                                    number_format_i18n((float) $route['price'], 2)
                                )
                            );
                        }
                        ?>
                        <option value="<?php echo esc_attr($route['id']); ?>" <?php selected($old['route_id'], (int) $route['id']); ?> <?php disabled($route['seats_remaining'] <= 0); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="swiftseat-field">
                <label for="swiftseat-name"><?php esc_html_e('Passenger name', 'swiftseat'); ?></label>
                <input type="text" id="swiftseat-name" name="passenger_name" value="<?php echo esc_attr($old['passenger_name']); ?>" required>
            </div>
            <div class="swiftseat-field">
                <label for="swiftseat-email"><?php esc_html_e('Email address', 'swiftseat'); ?></label>
                <input type="email" id="swiftseat-email" name="passenger_email" value="<?php echo esc_attr($old['passenger_email']); ?>" required>
            </div>
            <div class="swiftseat-field">
                <label for="swiftseat-seats"><?php esc_html_e('Seats needed', 'swiftseat'); ?></label>
                <input type="number" id="swiftseat-seats" name="seats" min="1" value="<?php echo esc_attr($old['seats']); ?>" required>
            </div>
            <button type="submit" class="swiftseat-button"><?php esc_html_e('Reserve seats', 'swiftseat'); ?></button>
        </form>
    <?php else : ?>
        <p class="swiftseat-empty">
            <?php esc_html_e('All departures are sold out or unpublished right now. Please check back soon.', 'swiftseat'); ?>
        </p>
    <?php endif; ?>
</div>
