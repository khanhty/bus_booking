<?php
/**
 * Plugin Name: SwiftSeat Bus Booking and User Management
 * Description: Mobile-friendly bus booking and user management system with custom users, scheduling, feedback, reporting, and admin desk features.
 * Version: 1.0.0
 * Author: Codex
 * Text Domain: swiftseat-bus-booking
 */

if (!defined('ABSPATH')) {
    exit;
}

final class SwiftSeat_Bus_Booking {
    private const SESSION_COOKIE = 'ssb_session_token';
    private const COOKIE_LIFETIME = 1209600; // 14 days

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);

        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [$this, 'register_actions']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('template_redirect', [$this, 'handle_lang_switch']);
    }

    public function activate(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'ssb_';

        $sql = [];
        $sql[] = "CREATE TABLE {$prefix}users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(30) DEFAULT '',
            employee_id VARCHAR(50) DEFAULT '',
            department VARCHAR(100) DEFAULT '',
            position VARCHAR(100) DEFAULT '',
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$prefix}buses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bus_code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            total_seats INT UNSIGNED NOT NULL DEFAULT 40,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY bus_code (bus_code)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$prefix}schedules (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            schedule_date DATE NOT NULL,
            schedule_time TIME NOT NULL,
            pickup VARCHAR(150) NOT NULL,
            dropoff VARCHAR(150) NOT NULL,
            bus_id BIGINT UNSIGNED NOT NULL,
            passenger_unit INT UNSIGNED NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'available',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY schedule_date (schedule_date)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$prefix}bookings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            schedule_id BIGINT UNSIGNED NOT NULL,
            seats INT UNSIGNED NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            admin_note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY schedule_id (schedule_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$prefix}feedback (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$prefix}chat_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sender_id BIGINT UNSIGNED NOT NULL,
            sender_role VARCHAR(20) NOT NULL DEFAULT 'user',
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    public function enqueue_assets(): void {
        wp_enqueue_style('ssb-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], '1.0.0');
        wp_enqueue_script('ssb-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '1.0.0', true);
    }

    private function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'ssb_' . $name;
    }

    private function get_current_user_row(): ?object {
        global $wpdb;
        $token = sanitize_text_field($_COOKIE[self::SESSION_COOKIE] ?? '');
        if (!$token) {
            return null;
        }

        $session = get_transient('ssb_session_' . $token);
        if (!$session || empty($session['user_id'])) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table('users')} WHERE id = %d", (int) $session['user_id']));
    }

    private function is_admin(?object $user = null): bool {
        $user = $user ?: $this->get_current_user_row();
        return $user && in_array($user->role, ['admin', 'owner'], true);
    }

    private function require_login(string $redirect_to = ''): void {
        if ($this->get_current_user_row()) {
            return;
        }

        $url = add_query_arg([
            'redirect_to' => rawurlencode($redirect_to ?: home_url(add_query_arg([]))),
            'notice' => 'login_required',
        ], site_url('/login'));

        wp_safe_redirect($url);
        exit;
    }

    public function register_actions(): void {
        $actions = ['register', 'login', 'logout', 'profile', 'book', 'cancel_booking', 'feedback', 'chat', 'admin_save'];
        foreach ($actions as $action) {
            add_action('admin_post_nopriv_ssb_' . $action, [$this, 'action_' . $action]);
            add_action('admin_post_ssb_' . $action, [$this, 'action_' . $action]);
        }
    }

    public function register_shortcodes(): void {
        add_shortcode('ssb_register', [$this, 'render_register']);
        add_shortcode('ssb_login', [$this, 'render_login']);
        add_shortcode('ssb_profile', [$this, 'render_profile']);
        add_shortcode('ssb_booking', [$this, 'render_booking']);
        add_shortcode('ssb_feedback', [$this, 'render_feedback']);
        add_shortcode('ssb_chat', [$this, 'render_chat']);
        add_shortcode('ssb_admin', [$this, 'render_admin']);
    }

    private function labels(): array {
        $lang = sanitize_text_field($_COOKIE['ssb_lang'] ?? 'en');
        $en = [
            'register' => 'Register',
            'login' => 'Login',
            'logout' => 'Logout',
            'booking' => 'Booking',
            'profile' => 'Profile',
            'feedback' => 'Feedback',
            'chat' => 'Chat',
        ];
        $lo = [
            'register' => 'ລົງທະບຽນ',
            'login' => 'ເຂົ້າລະບົບ',
            'logout' => 'ອອກຈາກລະບົບ',
            'booking' => 'ຈອງລົດ',
            'profile' => 'ໂປຣໄຟລ໌',
            'feedback' => 'ຄຳເຫັນ',
            'chat' => 'ແຊັດ',
        ];
        return $lang === 'lo' ? $lo : $en;
    }

    public function handle_lang_switch(): void {
        if (!isset($_GET['ssb_lang'])) {
            return;
        }
        $lang = sanitize_text_field($_GET['ssb_lang']) === 'lo' ? 'lo' : 'en';
        setcookie('ssb_lang', $lang, time() + self::COOKIE_LIFETIME, COOKIEPATH, COOKIE_DOMAIN);

        $redirect = remove_query_arg('ssb_lang');
        wp_safe_redirect($redirect);
        exit;
    }

    private function flash_notice(): string {
        $notice = sanitize_text_field($_GET['notice'] ?? '');
        if (!$notice) {
            return '';
        }
        return '<div class="ssb-toast" data-autohide="3000">' . esc_html(str_replace('_', ' ', ucfirst($notice))) . '</div>';
    }

    private function redirect_with_notice(string $url, string $notice): void {
        wp_safe_redirect(add_query_arg('notice', $notice, $url));
        exit;
    }

    public function action_register(): void {
        global $wpdb;
        $data = [
            'username' => sanitize_user($_POST['username'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'employee_id' => sanitize_text_field($_POST['employee_id'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'password_hash' => password_hash((string) ($_POST['password'] ?? ''), PASSWORD_DEFAULT),
            'role' => 'user',
        ];

        if (empty($data['username']) || empty($_POST['password']) || empty($data['email'])) {
            $this->redirect_with_notice(wp_get_referer() ?: home_url('/register'), 'registration_failed');
        }

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table('users')} WHERE username = %s", $data['username']));
        if ($exists) {
            $this->redirect_with_notice(wp_get_referer() ?: home_url('/register'), 'user_exists');
        }

        $wpdb->insert($this->table('users'), $data);
        $this->redirect_with_notice(site_url('/login'), 'registration_successful');
    }

    public function action_login(): void {
        global $wpdb;
        $username = sanitize_user($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $redirect = esc_url_raw($_POST['redirect_to'] ?? site_url('/booking'));

        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table('users')} WHERE username = %s", $username));
        if (!$user || !password_verify($password, $user->password_hash)) {
            $this->redirect_with_notice(site_url('/login'), 'invalid_login');
        }

        $token = wp_generate_password(40, false, false);
        set_transient('ssb_session_' . $token, ['user_id' => (int) $user->id], self::COOKIE_LIFETIME);
        setcookie(self::SESSION_COOKIE, $token, time() + self::COOKIE_LIFETIME, COOKIEPATH, COOKIE_DOMAIN);

        $this->redirect_with_notice($redirect, 'login_successful');
    }

    public function action_logout(): void {
        $token = sanitize_text_field($_COOKIE[self::SESSION_COOKIE] ?? '');
        if ($token) {
            delete_transient('ssb_session_' . $token);
        }
        setcookie(self::SESSION_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        $this->redirect_with_notice(site_url('/login'), 'logout_successful');
    }

    public function action_profile(): void {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$user) {
            $this->redirect_with_notice(site_url('/login'), 'login_required');
        }

        $wpdb->update(
            $this->table('users'),
            [
                'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
                'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
                'employee_id' => sanitize_text_field($_POST['employee_id'] ?? ''),
                'department' => sanitize_text_field($_POST['department'] ?? ''),
                'position' => sanitize_text_field($_POST['position'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
            ],
            ['id' => (int) $user->id]
        );

        $this->redirect_with_notice(wp_get_referer() ?: site_url('/profile'), 'profile_updated');
    }

    public function action_book(): void {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$user) {
            $this->redirect_with_notice(site_url('/login'), 'login_required');
        }

        $schedule_id = (int) ($_POST['schedule_id'] ?? 0);
        $seats = max(1, (int) ($_POST['seats'] ?? 1));
        $schedule = $wpdb->get_row($wpdb->prepare("SELECT s.*, b.total_seats FROM {$this->table('schedules')} s LEFT JOIN {$this->table('buses')} b ON b.id=s.bus_id WHERE s.id=%d", $schedule_id));

        if (!$schedule || $schedule->status !== 'available') {
            $this->redirect_with_notice(wp_get_referer() ?: site_url('/booking'), 'schedule_unavailable');
        }

        $booked = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(seats),0) FROM {$this->table('bookings')} WHERE schedule_id=%d AND status='active'", $schedule_id));
        if ($booked + $seats > (int) $schedule->total_seats) {
            $this->redirect_with_notice(wp_get_referer() ?: site_url('/booking'), 'not_enough_seats');
        }

        $wpdb->insert($this->table('bookings'), [
            'user_id' => (int) $user->id,
            'schedule_id' => $schedule_id,
            'seats' => $seats,
            'status' => 'active',
        ]);

        wp_mail(get_option('admin_email'), 'New bus booking', sprintf('User %s booked %d seat(s) on schedule #%d.', $user->username, $seats, $schedule_id));
        $this->redirect_with_notice(wp_get_referer() ?: site_url('/booking'), 'booking_completed');
    }

    public function action_cancel_booking(): void {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$user) {
            $this->redirect_with_notice(site_url('/login'), 'login_required');
        }

        $booking_id = (int) ($_POST['booking_id'] ?? 0);
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table('bookings')} SET status='cancelled' WHERE id=%d AND (user_id=%d OR %d=1)",
            $booking_id,
            (int) $user->id,
            $this->is_admin($user) ? 1 : 0
        ));

        $this->redirect_with_notice(wp_get_referer() ?: site_url('/booking'), 'booking_cancelled');
    }

    public function action_feedback(): void {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$user) {
            $this->redirect_with_notice(site_url('/login'), 'login_required');
        }

        $wpdb->insert($this->table('feedback'), [
            'user_id' => (int) $user->id,
            'rating' => max(1, min(5, (int) ($_POST['rating'] ?? 5))),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
        ]);

        $this->redirect_with_notice(wp_get_referer() ?: site_url('/feedback'), 'feedback_submitted');
    }

    public function action_chat(): void {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$user) {
            $this->redirect_with_notice(site_url('/login'), 'login_required');
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if (!$message) {
            $this->redirect_with_notice(wp_get_referer() ?: site_url('/chat'), 'message_required');
        }

        $wpdb->insert($this->table('chat_messages'), [
            'sender_id' => (int) $user->id,
            'sender_role' => $this->is_admin($user) ? 'admin' : 'user',
            'message' => $message,
        ]);

        $webhook = esc_url_raw(get_option('ssb_ms_teams_webhook', ''));
        if ($webhook) {
            wp_remote_post($webhook, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode(['text' => sprintf('[SwiftSeat chat] %s: %s', $user->username, $message)]),
                'timeout' => 5,
            ]);
        }

        $this->redirect_with_notice(wp_get_referer() ?: site_url('/chat'), 'message_sent');
    }

    public function action_admin_save(): void {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$this->is_admin($user)) {
            $this->redirect_with_notice(site_url('/login'), 'admin_required');
        }

        $type = sanitize_text_field($_POST['type'] ?? '');
        if ($type === 'bus') {
            $wpdb->insert($this->table('buses'), [
                'bus_code' => sanitize_text_field($_POST['bus_code'] ?? ''),
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'total_seats' => max(1, (int) ($_POST['total_seats'] ?? 40)),
                'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            ]);
        } elseif ($type === 'schedule') {
            $date = sanitize_text_field($_POST['schedule_date'] ?? '');
            $time = sanitize_text_field($_POST['schedule_time'] ?? '');
            $frequency = sanitize_text_field($_POST['frequency'] ?? 'once');
            $iterations = max(1, min(31, (int) ($_POST['iterations'] ?? 1)));

            $base = new DateTimeImmutable($date);
            for ($i = 0; $i < $iterations; $i++) {
                $scheduleDate = $base;
                if ($frequency === 'daily') {
                    $scheduleDate = $base->modify("+{$i} days");
                } elseif ($frequency === 'weekly') {
                    $scheduleDate = $base->modify("+{$i} weeks");
                } elseif ($frequency === 'monthly') {
                    $scheduleDate = $base->modify("+{$i} months");
                }

                $wpdb->insert($this->table('schedules'), [
                    'schedule_date' => $scheduleDate->format('Y-m-d'),
                    'schedule_time' => $time,
                    'pickup' => sanitize_text_field($_POST['pickup'] ?? ''),
                    'dropoff' => sanitize_text_field($_POST['dropoff'] ?? ''),
                    'bus_id' => (int) ($_POST['bus_id'] ?? 0),
                    'passenger_unit' => max(1, (int) ($_POST['passenger_unit'] ?? 1)),
                    'status' => 'available',
                ]);
            }
        } elseif ($type === 'booking_move') {
            $wpdb->update($this->table('bookings'), [
                'schedule_id' => (int) ($_POST['new_schedule_id'] ?? 0),
                'admin_note' => sanitize_text_field($_POST['admin_note'] ?? ''),
            ], ['id' => (int) ($_POST['booking_id'] ?? 0)]);
        } elseif ($type === 'bulk_delete') {
            $entity = sanitize_text_field($_POST['entity'] ?? '');
            $ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['ids'] ?? ''))));
            if ($ids && in_array($entity, ['users', 'buses', 'schedules', 'bookings'], true)) {
                $in = implode(',', array_map('intval', $ids));
                $wpdb->query("DELETE FROM {$this->table($entity)} WHERE id IN ({$in})");
            }
        }

        $this->update_overdue_schedules();
        $this->redirect_with_notice(wp_get_referer() ?: site_url('/admin-desk'), 'admin_update_successful');
    }

    private function update_overdue_schedules(): void {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table('schedules')} SET status='unavailable' WHERE CONCAT(schedule_date,' ',schedule_time) < %s",
                current_time('mysql')
            )
        );
    }

    private function top_nav(): string {
        $l = $this->labels();
        $user = $this->get_current_user_row();
        $links = [
            '<a href="' . esc_url(site_url('/booking')) . '">' . esc_html($l['booking']) . '</a>',
            '<a href="' . esc_url(site_url('/profile')) . '">' . esc_html($l['profile']) . '</a>',
            '<a href="' . esc_url(site_url('/feedback')) . '">' . esc_html($l['feedback']) . '</a>',
            '<a href="' . esc_url(site_url('/chat')) . '">' . esc_html($l['chat']) . '</a>',
            '<a href="' . esc_url(add_query_arg('ssb_lang', 'en')) . '">EN</a>/<a href="' . esc_url(add_query_arg('ssb_lang', 'lo')) . '">ລາວ</a>',
        ];

        if ($this->is_admin($user)) {
            $links[] = '<a href="' . esc_url(site_url('/admin-desk')) . '">Admin Desk</a>';
        }

        if ($user) {
            $links[] = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ssb_logout"><button type="submit" class="ssb-link-button">' . esc_html($l['logout']) . '</button></form>';
        } else {
            $links[] = '<a href="' . esc_url(site_url('/login')) . '">' . esc_html($l['login']) . '</a>';
            $links[] = '<a href="' . esc_url(site_url('/register')) . '">' . esc_html($l['register']) . '</a>';
        }

        return '<div class="ssb-nav"><button class="ssb-toggle" type="button">☰ Menu</button><div class="ssb-nav-items">' . implode('', array_map(fn ($x) => '<span>' . $x . '</span>', $links)) . '</div></div>';
    }

    public function render_register(): string {
        return $this->top_nav() . $this->flash_notice() . $this->template('register.php', []);
    }

    public function render_login(): string {
        return $this->top_nav() . $this->flash_notice() . $this->template('login.php', ['redirect_to' => esc_url_raw($_GET['redirect_to'] ?? site_url('/booking'))]);
    }

    public function render_profile(): string {
        $this->require_login(site_url('/profile'));
        return $this->top_nav() . $this->flash_notice() . $this->template('profile.php', ['user' => $this->get_current_user_row()]);
    }

    public function render_booking(): string {
        global $wpdb;
        $this->require_login(site_url('/booking'));
        $this->update_overdue_schedules();

        $date = sanitize_text_field($_GET['date'] ?? current_time('Y-m-d'));
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, b.name AS bus_name, b.total_seats,
                COALESCE((SELECT SUM(seats) FROM {$this->table('bookings')} bk WHERE bk.schedule_id=s.id AND bk.status='active'),0) AS booked_seats
             FROM {$this->table('schedules')} s
             LEFT JOIN {$this->table('buses')} b ON b.id=s.bus_id
             WHERE s.schedule_date=%s
             ORDER BY s.schedule_time ASC",
            $date
        ));

        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, s.schedule_date, s.schedule_time, s.pickup, s.dropoff
             FROM {$this->table('bookings')} b
             INNER JOIN {$this->table('schedules')} s ON s.id=b.schedule_id
             WHERE b.user_id=%d ORDER BY b.created_at DESC",
            (int) $this->get_current_user_row()->id
        ));

        return $this->top_nav() . $this->flash_notice() . $this->template('booking.php', [
            'date' => $date,
            'schedules' => $schedules,
            'bookings' => $bookings,
        ]);
    }

    public function render_feedback(): string {
        global $wpdb;
        $this->require_login(site_url('/feedback'));

        $feedback = $wpdb->get_results("SELECT f.*, u.username FROM {$this->table('feedback')} f LEFT JOIN {$this->table('users')} u ON u.id=f.user_id ORDER BY f.created_at DESC LIMIT 100");
        return $this->top_nav() . $this->flash_notice() . $this->template('feedback.php', ['feedback' => $feedback]);
    }

    public function render_chat(): string {
        global $wpdb;
        $this->require_login(site_url('/chat'));
        $messages = $wpdb->get_results("SELECT c.*, u.username FROM {$this->table('chat_messages')} c LEFT JOIN {$this->table('users')} u ON u.id=c.sender_id ORDER BY c.created_at DESC LIMIT 100");
        return $this->top_nav() . $this->flash_notice() . $this->template('chat.php', ['messages' => array_reverse($messages)]);
    }

    public function render_admin(): string {
        global $wpdb;
        $user = $this->get_current_user_row();
        if (!$this->is_admin($user)) {
            $this->require_login(site_url('/admin-desk'));
            return '';
        }

        $from = sanitize_text_field($_GET['from'] ?? date('Y-m-01'));
        $to = sanitize_text_field($_GET['to'] ?? date('Y-m-d'));

        $buses = $wpdb->get_results("SELECT * FROM {$this->table('buses')} ORDER BY id DESC");
        $schedules = $wpdb->get_results("SELECT s.*, b.name AS bus_name FROM {$this->table('schedules')} s LEFT JOIN {$this->table('buses')} b ON b.id=s.bus_id ORDER BY s.schedule_date DESC, s.schedule_time DESC");
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT bk.*, u.username, s.schedule_date, s.schedule_time
             FROM {$this->table('bookings')} bk
             LEFT JOIN {$this->table('users')} u ON u.id=bk.user_id
             LEFT JOIN {$this->table('schedules')} s ON s.id=bk.schedule_id
             WHERE DATE(bk.created_at) BETWEEN %s AND %s
             ORDER BY bk.created_at DESC",
            $from,
            $to
        ));
        $users = $wpdb->get_results("SELECT * FROM {$this->table('users')} ORDER BY id DESC");

        return $this->top_nav() . $this->flash_notice() . $this->template('admin.php', compact('from', 'to', 'buses', 'schedules', 'bookings', 'users'));
    }

    private function template(string $file, array $vars): string {
        $path = plugin_dir_path(__FILE__) . 'templates/' . $file;
        if (!file_exists($path)) {
            return '';
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}

new SwiftSeat_Bus_Booking();
