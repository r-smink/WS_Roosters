<?php
namespace RoosterPlanner;

class Notifications {
    
    public function __construct() {
        add_action('init', [$this, 'schedule_reminders']);
        add_action('rp_daily_reminder', [$this, 'send_daily_reminders']);
        add_action('rp_availability_reminder', [$this, 'send_availability_reminders']);
        add_action('wp_footer', [$this, 'add_notification_bell']);
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
                        "Deadline: {$deadline}\n\n" .
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
    
    private function create_notification($user_id, $type, $title, $message) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'rp_notifications', [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'created_at' => current_time('mysql')
        ]);
    }
}
