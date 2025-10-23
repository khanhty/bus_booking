<?php
namespace SwiftSeat\BusBooking;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static $instance;

    private $wpdb;
    private $routes_table;
    private $bookings_table;

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->routes_table = $this->wpdb->prefix . 'swiftseat_routes';
        $this->bookings_table = $this->wpdb->prefix . 'swiftseat_bookings';
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void
    {
        $instance = self::instance();
        $instance->create_tables();
    }

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_post_swiftseat_save_route', [$this, 'handle_route_submission']);
        add_action('admin_post_swiftseat_delete_route', [$this, 'handle_route_deletion']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('swiftseat_bus_booking', [$this, 'render_booking_form']);
    }

    private function create_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();

        $routes_sql = "CREATE TABLE {$this->routes_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(120) NOT NULL,
            origin VARCHAR(120) NOT NULL,
            destination VARCHAR(120) NOT NULL,
            departure_time DATETIME NOT NULL,
            total_seats SMALLINT UNSIGNED NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $bookings_sql = "CREATE TABLE {$this->bookings_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            route_id BIGINT UNSIGNED NOT NULL,
            passenger_name VARCHAR(120) NOT NULL,
            passenger_email VARCHAR(120) NOT NULL,
            seats SMALLINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY route_id (route_id),
            CONSTRAINT fk_route FOREIGN KEY (route_id)
              REFERENCES {$this->routes_table}(id) ON DELETE CASCADE
        ) $charset_collate;";

        dbDelta($routes_sql);
        dbDelta($bookings_sql);
    }

    public function register_admin_menu(): void
    {
        add_menu_page(
            __('SwiftSeat Booking', 'swiftseat'),
            __('SwiftSeat', 'swiftseat'),
            'manage_options',
            'swiftseat_booking',
            [$this, 'render_routes_page'],
            'dashicons-tickets-alt',
            26
        );

        add_submenu_page(
            'swiftseat_booking',
            __('Bookings', 'swiftseat'),
            __('Bookings', 'swiftseat'),
            'manage_options',
            'swiftseat_bookings',
            [$this, 'render_bookings_page']
        );
    }

    public function render_routes_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'swiftseat'));
        }

        $routes = $this->wpdb->get_results("SELECT * FROM {$this->routes_table} ORDER BY departure_time ASC");

        $this->render_template('admin-routes', [
            'routes' => $routes,
        ]);
    }

    public function render_bookings_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'swiftseat'));
        }

        $bookings = $this->wpdb->get_results(
            "SELECT b.*, r.title as route_title FROM {$this->bookings_table} b
             INNER JOIN {$this->routes_table} r ON r.id = b.route_id
             ORDER BY b.created_at DESC"
        );

        $this->render_template('admin-bookings', [
            'bookings' => $bookings,
        ]);
    }

    public function enqueue_assets(): void
    {
        wp_register_style(
            'swiftseat-booking',
            SWIFTSEAT_BUS_BOOKING_URL . 'assets/swiftseat.css',
            [],
            '1.0.0'
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        $screens = [
            'toplevel_page_swiftseat_booking',
            'swiftseat_booking_page_swiftseat_bookings',
        ];

        if (!in_array($hook, $screens, true)) {
            return;
        }

        wp_enqueue_style(
            'swiftseat-booking-admin',
            SWIFTSEAT_BUS_BOOKING_URL . 'assets/swiftseat.css',
            [],
            '1.0.0'
        );
    }

    public function render_booking_form(array $atts = []): string
    {
        $context = [
            'routes' => $this->get_available_routes(),
            'nonce' => wp_create_nonce('swiftseat_public_booking'),
            'errors' => [],
            'success' => isset($_GET['swiftseat_success']) && '1' === sanitize_text_field(wp_unslash($_GET['swiftseat_success'])),
            'old' => [
                'route_id' => '',
                'passenger_name' => '',
                'passenger_email' => '',
                'seats' => 1,
            ],
        ];

        if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['swiftseat_booking_nonce'])) {
            $context = $this->process_public_submission($context);
        }

        if (!wp_style_is('swiftseat-booking', 'registered')) {
            wp_register_style(
                'swiftseat-booking',
                SWIFTSEAT_BUS_BOOKING_URL . 'assets/swiftseat.css',
                [],
                '1.0.0'
            );
        }

        wp_enqueue_style('swiftseat-booking');

        ob_start();
        $this->render_template('public-booking-form', $context);
        return ob_get_clean();
    }

    private function process_public_submission(array $context): array
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['swiftseat_booking_nonce'])), 'swiftseat_public_booking')) {
            wp_die(__('Security check failed. Please try again.', 'swiftseat'));
        }

        $route_id = isset($_POST['route_id']) ? absint($_POST['route_id']) : 0;
        $name = isset($_POST['passenger_name']) ? sanitize_text_field(wp_unslash($_POST['passenger_name'])) : '';
        $email = isset($_POST['passenger_email']) ? sanitize_email(wp_unslash($_POST['passenger_email'])) : '';
        $seats = isset($_POST['seats']) ? absint($_POST['seats']) : 0;

        $context['old'] = [
            'route_id' => $route_id,
            'passenger_name' => $name,
            'passenger_email' => $email,
            'seats' => $seats,
        ];

        if (!$route_id) {
            $context['errors']['route_id'] = __('Please choose a route.', 'swiftseat');
        }

        if ('' === $name) {
            $context['errors']['passenger_name'] = __('Enter the passenger name.', 'swiftseat');
        }

        if ('' === $email || !is_email($email)) {
            $context['errors']['passenger_email'] = __('Provide a valid email address.', 'swiftseat');
        }

        if ($seats < 1) {
            $context['errors']['seats'] = __('Select at least one seat.', 'swiftseat');
        }

        $route = null;
        if ($route_id) {
            $route = $this->wpdb->get_row(
                $this->wpdb->prepare("SELECT * FROM {$this->routes_table} WHERE id = %d", $route_id)
            );

            if (!$route) {
                $context['errors']['route_id'] = __('Selected route is no longer available.', 'swiftseat');
            }
        }

        if (!$context['errors'] && $route) {
            $remaining = $this->get_seats_remaining($route_id);
            if ($seats > $remaining) {
                $context['errors']['seats'] = sprintf(
                    __('Only %d seats remain on this route.', 'swiftseat'),
                    $remaining
                );
            }
        }

        if ($context['errors']) {
            return $context;
        }

        $inserted = $this->wpdb->insert(
            $this->bookings_table,
            [
                'route_id' => $route_id,
                'passenger_name' => $name,
                'passenger_email' => $email,
                'seats' => $seats,
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%d', '%s']
        );

        if (!$inserted) {
            wp_die(__('Unable to create the booking, please try again.', 'swiftseat'));
        }

        $redirect_url = wp_get_referer();

        if (!$redirect_url && function_exists('get_permalink')) {
            $redirect_url = get_permalink();
        }

        if (!$redirect_url) {
            $redirect_url = home_url('/');
        }

        $redirect_url = remove_query_arg('swiftseat_success', $redirect_url);
        $redirect_url = add_query_arg('swiftseat_success', '1', $redirect_url);

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handle_route_submission(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You are not allowed to perform this action.', 'swiftseat'));
        }

        check_admin_referer('swiftseat_route_action');

        $route_id = isset($_POST['route_id']) ? absint($_POST['route_id']) : 0;
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $origin = sanitize_text_field(wp_unslash($_POST['origin'] ?? ''));
        $destination = sanitize_text_field(wp_unslash($_POST['destination'] ?? ''));
        $departure_time = sanitize_text_field(wp_unslash($_POST['departure_time'] ?? ''));
        $total_seats = isset($_POST['total_seats']) ? absint($_POST['total_seats']) : 0;
        $price = isset($_POST['price']) ? floatval(wp_unslash($_POST['price'])) : 0.0;

        if ('' === $title || '' === $origin || '' === $destination || '' === $departure_time || !$total_seats) {
            wp_redirect(add_query_arg('swiftseat_error', 'invalid', admin_url('admin.php?page=swiftseat_booking')));
            exit;
        }

        $datetime = date_create_from_format('Y-m-d\TH:i', $departure_time, wp_timezone());
        if (!$datetime) {
            wp_redirect(add_query_arg('swiftseat_error', 'datetime', admin_url('admin.php?page=swiftseat_booking')));
            exit;
        }

        $datetime->setTimezone(new \DateTimeZone('UTC'));

        $data = [
            'title' => $title,
            'origin' => $origin,
            'destination' => $destination,
            'departure_time' => $datetime->format('Y-m-d H:i:s'),
            'total_seats' => $total_seats,
            'price' => $price,
        ];

        if ($route_id) {
            $this->wpdb->update(
                $this->routes_table,
                $data,
                ['id' => $route_id],
                ['%s', '%s', '%s', '%s', '%d', '%f'],
                ['%d']
            );
        } else {
            $this->wpdb->insert(
                $this->routes_table,
                $data,
                ['%s', '%s', '%s', '%s', '%d', '%f']
            );
        }

        wp_redirect(admin_url('admin.php?page=swiftseat_booking'));
        exit;
    }

    public function handle_route_deletion(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You are not allowed to perform this action.', 'swiftseat'));
        }

        check_admin_referer('swiftseat_route_action');

        $route_id = isset($_GET['route_id']) ? absint($_GET['route_id']) : 0;
        if (!$route_id) {
            wp_redirect(admin_url('admin.php?page=swiftseat_booking'));
            exit;
        }

        $this->wpdb->delete(
            $this->routes_table,
            ['id' => $route_id],
            ['%d']
        );

        wp_redirect(admin_url('admin.php?page=swiftseat_booking'));
        exit;
    }

    private function render_template(string $template, array $context = []): void
    {
        $template_path = SWIFTSEAT_BUS_BOOKING_DIR . 'templates/' . $template . '.php';

        if (!file_exists($template_path)) {
            return;
        }

        extract($context, EXTR_SKIP);
        include $template_path;
    }

    private function get_seats_remaining(int $route_id): int
    {
        $seats_sold = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT IFNULL(SUM(seats), 0) FROM {$this->bookings_table} WHERE route_id = %d",
                $route_id
            )
        );

        $total_seats = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT total_seats FROM {$this->routes_table} WHERE id = %d",
                $route_id
            )
        );

        return max($total_seats - $seats_sold, 0);
    }

    private function get_available_routes(): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT r.*, (
                r.total_seats - IFNULL((
                    SELECT SUM(b.seats) FROM {$this->bookings_table} b WHERE b.route_id = r.id
                ), 0)
            ) AS seats_remaining
            FROM {$this->routes_table} r
            WHERE r.departure_time >= %s
            ORDER BY r.departure_time ASC",
            current_time('mysql', true)
        );

        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}
