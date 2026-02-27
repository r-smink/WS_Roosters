<?php
namespace RoosterPlanner;

class Frontend {
    
    public function __construct() {
        add_shortcode('roosterplanner_login', [$this, 'render_login']);
        add_shortcode('roosterplanner_dashboard', [$this, 'render_dashboard']);
        add_shortcode('roosterplanner_rooster', [$this, 'render_rooster']);
        add_shortcode('roosterplanner_beschikbaarheid', [$this, 'render_beschikbaarheid']);
        add_shortcode('roosterplanner_ruilenen', [$this, 'render_ruilen']);
        add_shortcode('roosterplanner_chat', [$this, 'render_chat']);
        add_shortcode('roosterplanner_ziekmelden', [$this, 'render_ziekmelden']);
        add_shortcode('roosterplanner_verlof', [$this, 'render_verlof']);
        add_shortcode('roosterplanner_profielformulier', [$this, 'render_profielformulier']);
        add_shortcode('roosterplanner_berichten', [$this, 'render_berichten']);
        
        add_action('wp_login', [$this, 'after_login_redirect'], 10, 2);
    }
    
    public function render_login($atts) {
        if (is_user_logged_in()) {
            $employee = $this->get_current_employee();
            if ($employee) {
                wp_redirect(home_url('/medewerker-dashboard/'));
                exit;
            }
            return '<div class="rp-notice rp-notice-info">Je bent al ingelogd, maar je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        ob_start();
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/login.php';
        return ob_get_clean();
    }
    
    public function render_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken. <a href="' . home_url() . '">Log in</a></div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        // Get upcoming shifts (next 7 days only)
        $today = current_time('Y-m-d');
        $next_week = date('Y-m-d', strtotime('+7 days'));
        $upcoming_shifts = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, l.name as location_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            WHERE s.employee_id = %d AND s.work_date >= %s AND s.work_date <= %s AND s.status != 'cancelled'
            ORDER BY s.work_date ASC",
            $employee->id, $today, $next_week
        ));
        
        // Get unread notifications count
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_notifications WHERE user_id = %d AND is_read = 0",
            get_current_user_id()
        ));
        
        // Get pending swap requests
        $pending_swaps = $wpdb->get_results($wpdb->prepare(
            "SELECT sw.*, s.work_date, sh.name as shift_name
            FROM {$wpdb->prefix}rp_shift_swaps sw
            LEFT JOIN {$wpdb->prefix}rp_schedules s ON sw.schedule_id = s.id
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            WHERE sw.requested_employee_id = %d AND sw.status = 'pending'",
            $employee->id
        ));
        
        // Get availability deadline (15th of current month for next month)
        $today = current_time('Y-m-d');
        $current_month_15th = date('Y-m-15');
        $next_month = date('Y-m', strtotime('+1 month'));
        $deadline_passed = $today > $current_month_15th;
        $has_submitted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_availability 
            WHERE employee_id = %d AND work_date LIKE %s",
            $employee->id, $next_month . '%'
        ));
        
        ob_start();
        echo '<div class="rp-container rp-dashboard' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/dashboard.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_rooster($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get employee's locations
        $employee_locations = $wpdb->get_col($wpdb->prepare(
            "SELECT location_id FROM {$wpdb->prefix}rp_employee_locations WHERE employee_id = %d",
            $employee->id
        ));
        
        $atts = shortcode_atts([
            'view' => 'personal', // 'personal' or 'all'
            'location' => ''
        ], $atts);
        
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : $atts['view'];
        $location_id = isset($_GET['location']) ? intval($_GET['location']) : ($employee_locations[0] ?? 0);
        $current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : current_time('Y-m');
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        if (!in_array($location_id, $employee_locations) && !$employee->is_admin) {
            $location_id = $employee_locations[0] ?? 0;
        }
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        
        // Get schedules
        if ($view === 'all' && $employee->is_admin) {
            $schedules = $this->get_all_schedules($location_id, $current_month);
        } else {
            $schedules = $this->get_personal_schedules($employee->id, $current_month);
        }
        
        // Check if month is finalized for this location
        $is_finalized = $this->is_month_finalized($location_id, $current_month);
        
        // Build calendar data
        $calendar = $this->build_calendar($current_month, $schedules, $view, $location_id);
        
        ob_start();
        echo '<div class="rp-container rp-rooster' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/rooster.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_beschikbaarheid($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get next month for availability submission
        $target_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m', strtotime('+1 month'));
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        $location_id = isset($_GET['location']) ? intval($_GET['location']) : 0;
        
        // Validate location
        $employee_locations = $wpdb->get_results($wpdb->prepare(
            "SELECT el.location_id, l.name FROM {$wpdb->prefix}rp_employee_locations el
            LEFT JOIN {$wpdb->prefix}rp_locations l ON el.location_id = l.id
            WHERE el.employee_id = %d",
            $employee->id
        ));
        
        if (!$location_id && !empty($employee_locations)) {
            $location_id = $employee_locations[0]->location_id;
        }
        
        // Get shifts for this location
        $shifts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_shifts WHERE location_id = %d AND is_active = 1 ORDER BY start_time",
            $location_id
        ));
        
        // Get existing availability
        $existing_availability = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_availability 
            WHERE employee_id = %d AND location_id = %d AND work_date LIKE %s",
            $employee->id, $location_id, $target_month . '%'
        ));
        
        // Re-index by date for easier lookup
        $existing_by_date = [];
        foreach ($existing_availability as $avail) {
            $existing_by_date[$avail->work_date] = $avail;
        }
        $existing_availability = $existing_by_date;
        
        // Build calendar for month
        $calendar = $this->build_availability_calendar($target_month, $existing_availability, $shifts);
        
        ob_start();
        echo '<div class="rp-container rp-beschikbaarheid' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/beschikbaarheid.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_ruilen($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        // Get my shifts that can be swapped
        $my_shifts = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, l.name as location_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            WHERE s.employee_id = %d AND s.work_date >= %s AND s.status IN ('scheduled', 'confirmed')
            ORDER BY s.work_date ASC",
            $employee->id, current_time('Y-m-d')
        ));
        
        // Get available shifts from others (only those marked as swappable)
        $my_locations = $wpdb->get_col($wpdb->prepare(
            "SELECT location_id FROM {$wpdb->prefix}rp_employee_locations WHERE employee_id = %d",
            $employee->id
        ));
        
        $available_shifts = [];
        if (!empty($my_locations)) {
            $placeholders = implode(',', array_fill(0, count($my_locations), '%d'));
            $available_shifts = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, l.name as location_name, u.display_name
                FROM {$wpdb->prefix}rp_schedules s
                LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
                LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
                LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                WHERE s.employee_id != %d AND s.work_date >= %s 
                AND s.status IN ('scheduled', 'confirmed') AND s.location_id IN ($placeholders)
                AND s.is_swappable = 1
                AND s.id NOT IN (SELECT schedule_id FROM {$wpdb->prefix}rp_shift_swaps WHERE status = 'pending')
                ORDER BY s.work_date ASC
                LIMIT 50",
                array_merge([$employee->id, current_time('Y-m-d')], $my_locations)
            ));
        }
        
        // Get my swap requests
        $my_requests = $wpdb->get_results($wpdb->prepare(
            "SELECT sw.*, s.work_date, sh.name as shift_name, sh.start_time, sh.end_time,
            u.display_name as requested_name, sw.status as swap_status
            FROM {$wpdb->prefix}rp_shift_swaps sw
            LEFT JOIN {$wpdb->prefix}rp_schedules s ON sw.schedule_id = s.id
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_employees e ON sw.requested_employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE sw.requester_id = %d
            ORDER BY sw.requested_at DESC",
            $employee->id
        ));
        
        // Get swap requests for me
        $requests_for_me = $wpdb->get_results($wpdb->prepare(
            "SELECT sw.*, s.work_date, sh.name as shift_name, sh.start_time, sh.end_time,
            u.display_name as requester_name
            FROM {$wpdb->prefix}rp_shift_swaps sw
            LEFT JOIN {$wpdb->prefix}rp_schedules s ON sw.schedule_id = s.id
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_employees e ON sw.requester_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE sw.requested_employee_id = %d AND sw.status = 'pending'
            ORDER BY sw.requested_at DESC",
            $employee->id
        ));
        
        ob_start();
        echo '<div class="rp-container rp-ruilen' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/ruilen.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_chat($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        // Get recent messages (last 50)
        $messages = $wpdb->get_results("SELECT m.*, u.display_name as sender_name, u.ID as sender_user_id
            FROM {$wpdb->prefix}rp_chat_messages m
            LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
            WHERE m.is_announcement = 1 OR m.sender_id = " . get_current_user_id() . "
            ORDER BY m.created_at DESC
            LIMIT 50");
        
        $messages = array_reverse($messages);
        
        ob_start();
        echo '<div class="rp-container rp-chat' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/chat.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_ziekmelden($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        // Check if self sick reporting is enabled
        $self_sick_enabled = get_option('rooster_planner_enable_self_sick_report', 1);
        
        // Get upcoming shifts
        $upcoming_shifts = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, l.name as location_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            WHERE s.employee_id = %d AND s.work_date >= %s AND s.status IN ('scheduled', 'confirmed')
            ORDER BY s.work_date ASC
            LIMIT 30",
            $employee->id, current_time('Y-m-d')
        ));
        
        ob_start();
        echo '<div class="rp-container rp-ziekmelden' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/ziekmelden.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_verlof($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        // Get employee's timeoff requests
        $my_timeoff_requests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_timeoff 
            WHERE employee_id = %d 
            ORDER BY created_at DESC 
            LIMIT 20",
            $employee->id
        ));
        
        ob_start();
        echo '<div class="rp-container rp-verlof' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/verlof.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_profielformulier($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        $user = wp_get_current_user();
        $employee_locations = $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM {$wpdb->prefix}rp_employee_locations el
            LEFT JOIN {$wpdb->prefix}rp_locations l ON el.location_id = l.id
            WHERE el.employee_id = %d",
            $employee->id
        ));
        
        ob_start();
        echo '<div class="rp-container rp-profiel' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/profiel.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function render_berichten($atts) {
        if (!is_user_logged_in()) {
            return '<div class="rp-notice rp-notice-warning">Je moet ingelogd zijn om dit te bekijken.</div>';
        }
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            return '<div class="rp-notice rp-notice-error">Je hebt geen toegang tot het roostersysteem.</div>';
        }
        
        global $wpdb;
        
        // Get theme preference
        $theme_preference = $employee->theme_preference ?: 'light';
        
        // Get all notifications for user
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_notifications
            WHERE user_id = %d
            ORDER BY created_at DESC
            LIMIT 50",
            get_current_user_id()
        ));
        
        ob_start();
        echo '<div class="rp-container rp-berichten' . ($theme_preference === 'dark' ? ' rp-dark-theme' : '') . '">';
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/frontend/berichten.php';
        echo '</div>';
        return ob_get_clean();
    }
    
    private function get_current_employee() {
        global $wpdb;
        if (!is_user_logged_in()) return null;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_employees WHERE user_id = %d",
            get_current_user_id()
        ));
    }
    
    private function get_personal_schedules($employee_id, $month) {
        global $wpdb;
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, sh.color,
            l.name as location_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            WHERE s.employee_id = %d AND s.work_date BETWEEN %s AND %s
            ORDER BY s.work_date, sh.start_time",
            $employee_id, $start_date, $end_date
        ));
    }
    
    private function get_all_schedules($location_id, $month) {
        global $wpdb;
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, sh.color,
            l.name as location_name, u.display_name as employee_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE s.location_id = %d AND s.work_date BETWEEN %s AND %s
            ORDER BY s.work_date, sh.start_time",
            $location_id, $start_date, $end_date
        ));
    }
    
    private function build_calendar($month, $schedules, $view, $location_id) {
        $first_day = strtotime($month . '-01');
        $days_in_month = date('t', $first_day);
        $start_weekday = date('w', $first_day); // 0 = Sunday
        
        $calendar = [];
        
        // Empty cells before first day
        for ($i = 0; $i < $start_weekday; $i++) {
            $calendar[] = ['type' => 'empty'];
        }
        
        // Days
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            $day_schedules = array_filter($schedules, function($s) use ($date) {
                return $s->work_date === $date;
            });
            
            $calendar[] = [
                'type' => 'day',
                'date' => $date,
                'day' => $day,
                'is_today' => $date === current_time('Y-m-d'),
                'schedules' => array_values($day_schedules)
            ];
        }
        
        return $calendar;
    }
    
    private function build_availability_calendar($month, $existing, $shifts) {
        $first_day = strtotime($month . '-01');
        $days_in_month = date('t', $first_day);
        $start_weekday = date('w', $first_day);
        
        $calendar = [];
        
        // Empty cells
        for ($i = 0; $i < $start_weekday; $i++) {
            $calendar[] = ['type' => 'empty'];
        }
        
        // Days
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            $avail = isset($existing[$date]) ? $existing[$date] : null;
            
            $calendar[] = [
                'type' => 'day',
                'date' => $date,
                'day' => $day,
                'weekday' => date('w', strtotime($date)),
                'is_today' => $date === current_time('Y-m-d'),
                'availability' => $avail,
                'is_available' => $avail ? $avail->is_available : null,
                'shift_preference' => $avail ? $avail->shift_preference : null,
                'custom_start' => $avail ? $avail->custom_start : '',
                'custom_end' => $avail ? $avail->custom_end : '',
                'notes' => $avail ? $avail->notes : ''
            ];
        }
        
        return $calendar;
    }
    
    public function after_login_redirect($user_login, $user) {
        global $wpdb;
        
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_employees WHERE user_id = %d AND is_active = 1",
            $user->ID
        ));
        
        if ($employee) {
            // Check if there's a redirect_to parameter
            if (isset($_REQUEST['redirect_to']) && !empty($_REQUEST['redirect_to'])) {
                return;
            }
            
            // Redirect to employee dashboard
            wp_redirect(home_url('/medewerker-dashboard/'));
            exit;
        }
    }
    
    private function is_month_finalized($location_id, $month) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rp_final_schedules WHERE location_id = %d AND month = %s",
            $location_id, $month
        )) > 0;
    }
}
