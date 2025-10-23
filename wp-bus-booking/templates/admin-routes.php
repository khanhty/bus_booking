<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('SwiftSeat Routes', 'swiftseat'); ?></h1>
    <?php if (isset($_GET['swiftseat_error'])) : ?>
        <div class="notice notice-error">
            <p>
                <?php
                switch (sanitize_text_field(wp_unslash($_GET['swiftseat_error']))) {
                    case 'invalid':
                        esc_html_e('Please fill in all required fields to create a route.', 'swiftseat');
                        break;
                    case 'datetime':
                        esc_html_e('Invalid departure time provided. Please use the picker controls.', 'swiftseat');
                        break;
                    default:
                        esc_html_e('Unable to save the route. Please try again.', 'swiftseat');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    <div class="swiftseat-admin-grid">
        <div class="swiftseat-admin-card">
            <h2><?php esc_html_e('Create route', 'swiftseat'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="swiftseat_save_route">
                <?php wp_nonce_field('swiftseat_route_action'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="swiftseat-title"><?php esc_html_e('Title', 'swiftseat'); ?></label></th>
                            <td><input name="title" type="text" id="swiftseat-title" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="swiftseat-origin"><?php esc_html_e('Origin', 'swiftseat'); ?></label></th>
                            <td><input name="origin" type="text" id="swiftseat-origin" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="swiftseat-destination"><?php esc_html_e('Destination', 'swiftseat'); ?></label></th>
                            <td><input name="destination" type="text" id="swiftseat-destination" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="swiftseat-departure"><?php esc_html_e('Departure', 'swiftseat'); ?></label></th>
                            <td><input name="departure_time" type="datetime-local" id="swiftseat-departure" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="swiftseat-seats"><?php esc_html_e('Seats', 'swiftseat'); ?></label></th>
                            <td><input name="total_seats" type="number" min="1" id="swiftseat-seats" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="swiftseat-price"><?php esc_html_e('Price', 'swiftseat'); ?></label></th>
                            <td><input name="price" type="number" min="0" step="0.01" id="swiftseat-price"></td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save route', 'swiftseat'); ?></button></p>
            </form>
        </div>
        <div class="swiftseat-admin-card">
            <h2><?php esc_html_e('Active routes', 'swiftseat'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'swiftseat'); ?></th>
                        <th><?php esc_html_e('Origin', 'swiftseat'); ?></th>
                        <th><?php esc_html_e('Destination', 'swiftseat'); ?></th>
                        <th><?php esc_html_e('Departure', 'swiftseat'); ?></th>
                        <th><?php esc_html_e('Seats', 'swiftseat'); ?></th>
                        <th><?php esc_html_e('Price', 'swiftseat'); ?></th>
                        <th><?php esc_html_e('Actions', 'swiftseat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($routes)) : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('No routes found yet.', 'swiftseat'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($routes as $route) : ?>
                            <tr>
                                <td><?php echo esc_html($route->title); ?></td>
                                <td><?php echo esc_html($route->origin); ?></td>
                                <td><?php echo esc_html($route->destination); ?></td>
                                <td><?php echo esc_html(get_date_from_gmt($route->departure_time, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                                <td><?php echo esc_html($route->total_seats); ?></td>
                                <td><?php echo esc_html(number_format((float) $route->price, 2)); ?></td>
                                <td>
                                    <?php
                                    $delete_url = add_query_arg(
                                        [
                                            'action' => 'swiftseat_delete_route',
                                            'route_id' => (int) $route->id,
                                        ],
                                        admin_url('admin-post.php')
                                    );
                                    ?>
                                    <a class="button" href="<?php echo esc_url(wp_nonce_url($delete_url, 'swiftseat_route_action')); ?>">
                                        <?php esc_html_e('Delete', 'swiftseat'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
