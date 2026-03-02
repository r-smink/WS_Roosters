<?php
namespace RoosterPlanner;

class Notifications {
    
    public function __construct() {
        add_action('init', [$this, 'schedule_reminders']);
        add_action('rp_daily_reminder', [$this, 'send_daily_reminders']);
        add_action('rp_availability_reminder', [$this, 'send_availability_reminders']);
        add_action('wp_footer', [$this, 'add_notification_bell']);
        add_action('rest_api_init', [$this, 'register_rest']);
    }

    private function notification_target_url($type, $related_id = null) {
        switch ($type) {
            case 'swap_request':
            case 'swap_response':
            case 'swap_claimed_other':
            case 'swap_claimed_self':
                return home_url('/medewerker-ruilen/');
            case 'replacement_needed':
                return home_url('/medewerker-ruilen/');
            case 'announcement':
            case 'admin_notice':
                return home_url('/medewerker-berichten/');
            case 'timeoff':
            case 'timeoff_decision':
                return home_url('/medewerker-verlof/');
            case 'availability_reminder':
                return home_url('/medewerker-beschikbaarheid/');
            case 'schedule_assigned':
            case 'schedule_updated':
            default:
                return home_url('/medewerker-rooster/');
        }
    }
    
    public function schedule_reminders() {
        if (!wp_next_scheduled('rp_daily_reminder')) {
            wp_schedule_event(strtotime('08:00:00'), 'daily', 'rp_daily_reminder');
        }
        if (!wp_next_scheduled('rp_availability_reminder')) {
            wp_schedule_event(strtotime('09:00:00'), 'daily', 'rp_availability_reminder');
        }
    }
    
    public function send_daily_reminders() {
        global $wpdb;
        
        // Get employees with shifts tomorrow
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $employees = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT e.user_id, u.display_name, s.work_date, sh.name as shift_name, sh.start_time
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE s.work_date = %s AND s.status IN ('scheduled', 'confirmed')",
            $tomorrow
        ));
        
        foreach ($employees as $emp) {
            $this->create_notification($emp->user_id, 'shift_reminder',
                'Herinnering: Dienst morgen',
                'Je hebt morgen (' . date('d-m-Y', strtotime($emp->work_date)) . ') een dienst: ' . $emp->shift_name . ' om ' . substr($emp->start_time, 0, 5)
            );
            
            // Also send email
            $user = get_user_by('id', $emp->user_id);
            if ($user) {
                wp_mail($user->user_email, 'Herinnering: Dienst morgen',
                    "Hallo {$emp->display_name}," .
                    "Dit is een herinnering dat je morgen (" . date('d-m-Y', strtotime($emp->work_date)) . ") een dienst hebt:" .
                    "Shift: {$emp->shift_name}" .
                    "Starttijd: " . substr($emp->start_time, 0, 5) . "" .
                    "Bekijk je rooster in de app." .
                    "Groet," .
                    get_bloginfo('name')
                );
            }
        }
    }
    
    public function send_availability_reminders() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        $day = date('j', strtotime($today));
        
        // Check if today is the 14th - reminder day
        if ($day == 14) {
            $next_month = date('F Y', strtotime('+1 month'));
            $deadline = date('15 F Y', strtotime('+1 month'));
            
            $employees = $wpdb->get_results("SELECT e.user_id, u.display_name, u.user_email
                FROM {$wpdb->prefix}rp_employees e
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                WHERE e.is_active = 1");
            
            foreach ($employees as $emp) {
                $this->create_notification($emp->user_id, 'availability_reminder',
                    'Herinnering: Beschikbaarheid doorgeven',
                    "Vergeet niet om je beschikbaarheid door te geven voor {$next_month}. Deadline: {$deadline}"
                );
                
                if ($emp->user_email) {
                    wp_mail($emp->user_email, 'Herinnering: Beschikbaarheid doorgeven',
                        "Hallo {$emp->display_name}," .
                        "Dit is een vriendelijke herinnering om je beschikbaarheid door te geven voor {$next_month}." .
                        "Deadline: {$deadline}" .
                        "Ga naar de app om je beschikbaarheid in te vullen." .
                        "Groet," .
                        get_bloginfo('name')
                    );
                }
            }
        }
    }
    
    public function add_notification_bell() {
        if (!is_user_logged_in()) return;
        
        global $wpdb;
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_employees WHERE user_id = %d",
            get_current_user_id()
        ));
        
        if (!$employee) return;
        
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_notifications WHERE user_id = %d AND is_read = 0",
            get_current_user_id()
        ));
        
        echo '<div id="rp-notification-bell" class="rp-notification-bell" style="display:none;">';
        echo '<span class="rp-bell-icon">🔔</span>';
        if ($unread_count > 0) {
            echo '<span class="rp-notification-badge">' . $unread_count . '</span>';
        }
        echo '</div>';
        echo '<div id="rp-notification-panel" class="rp-notification-panel" style="display:none;"></div>';
    }
    
    private function create_notification($user_id, $type, $title, $message, $related_id = null) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'rp_notifications', [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $related_id,
            'created_at' => current_time('mysql')
        ]);

        // Fire web push to all subscriptions of this user
        $this->send_web_push($user_id, $title, $message, $type, $related_id);
    }

    /**
     * Web Push REST endpoints for subscription management
     */
    public function register_rest() {
        register_rest_route('roosterplanner/v1', '/push/subscribe', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_subscribe'],
            'permission_callback' => function() { return is_user_logged_in(); }
        ]);

        register_rest_route('roosterplanner/v1', '/push/public-key', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_public_key'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function rest_public_key() {
        $public_key = get_option('rooster_planner_vapid_public', '');
        return ['publicKey' => $public_key];
    }

    public function rest_subscribe(\WP_REST_Request $request) {
        global $wpdb;
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new \WP_Error('not_logged_in', 'Login vereist', ['status' => 401]);
        }

        $endpoint = sanitize_text_field($request->get_param('endpoint'));
        $p256dh = sanitize_text_field($request->get_param('p256dh'));
        $auth = sanitize_text_field($request->get_param('auth'));

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            return new \WP_Error('invalid_subscription', 'Incomplete subscription data', ['status' => 400]);
        }

        // Prevent duplicates
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rp_push_subscriptions WHERE user_id = %d AND endpoint = %s",
            $user_id, $endpoint
        ));
        if ($exists) {
            return ['status' => 'ok', 'message' => 'Subscription already stored'];
        }

        $wpdb->insert($wpdb->prefix . 'rp_push_subscriptions', [
            'user_id' => $user_id,
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'created_at' => current_time('mysql')
        ]);

        return ['status' => 'ok'];
    }

    /**
     * Send web push using VAPID keys (if configured)
     */
    private function send_web_push($user_id, $title, $message, $type = 'generic', $related_id = null) {
        $public = get_option('rooster_planner_vapid_public', '');
        $private = get_option('rooster_planner_vapid_private', '');
        if (empty($public) || empty($private)) {
            return; // Push disabled until keys set
        }

        if (!class_exists('\\Minishlink\\WebPush\\WebPush')) {
            return; // Library not loaded
        }

        global $wpdb;
        $subs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_push_subscriptions WHERE user_id = %d",
            $user_id
        ));
        if (empty($subs)) return;

        $auth = [
            'VAPID' => [
                'subject' => get_site_url(),
                'publicKey' => $public,
                'privateKey' => $private,
            ],
        ];
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        foreach ($subs as $sub) {
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
            ]);
            $payload = json_encode([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'url' => $this->notification_target_url($type, $related_id)
            ]);
            $webPush->queueNotification($subscription, $payload);
        }
        // Send without blocking
        foreach ($webPush->flush() as $report) {
            // Silently ignore failures; could log if needed
        }
    }
}
