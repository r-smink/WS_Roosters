<?php
namespace RoosterPlanner;

/**
 * iCal Export Handler
 * Generates persistent iCal feeds for employees
 */
class ICalExport {
    
    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_ical_request']);
        add_filter('query_vars', [$this, 'add_query_vars']);
    }
    
    /**
     * Add rewrite rules for iCal endpoints
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^ical/([^/]+)/?$', 'index.php?rp_ical=1&rp_ical_token=$matches[1]', 'top');
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'rp_ical';
        $vars[] = 'rp_ical_token';
        return $vars;
    }
    
    /**
     * Handle iCal export requests
     */
    public function handle_ical_request() {
        if (!get_query_var('rp_ical')) {
            return;
        }
        
        $token = sanitize_text_field(get_query_var('rp_ical_token'));
        
        if (empty($token)) {
            wp_die('Ongeldige iCal link', 'Fout', ['response' => 403]);
        }
        
        global $wpdb;
        
        // Find employee by token
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name, u.user_email 
            FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.ical_token = %s AND e.is_active = 1",
            $token
        ));
        
        if (!$employee) {
            wp_die('Ongeldige of verlopen iCal link', 'Fout', ['response' => 403]);
        }
        
        // Generate iCal feed
        $this->generate_ical_feed($employee);
        exit;
    }
    
    /**
     * Generate iCal feed for employee
     */
    private function generate_ical_feed($employee) {
        global $wpdb;
        
        // Get all finalized schedules for this employee
        // Include schedules from finalized months + all future schedules
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, sh.color, l.name as location_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            LEFT JOIN {$wpdb->prefix}rp_final_schedules fs ON s.location_id = fs.location_id 
                AND DATE_FORMAT(s.work_date, '%Y-%m') = fs.month
            WHERE s.employee_id = %d 
                AND s.status != 'cancelled'
                AND (fs.id IS NOT NULL OR s.work_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH))
            ORDER BY s.work_date, s.start_time",
            $employee->id
        ));
        
        // Set headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="rooster-' . sanitize_file_name($employee->display_name) . '.ics"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Generate iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//NextBuzz//RoosterPlanner Pro//NL\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:Rooster " . $this->escape_ical_text($employee->display_name) . "\r\n";
        $ical .= "X-WR-TIMEZONE:Europe/Amsterdam\r\n";
        
        foreach ($schedules as $schedule) {
            $ical .= $this->generate_vevent($schedule);
        }
        
        $ical .= "END:VCALENDAR\r\n";
        
        echo $ical;
    }
    
    /**
     * Generate VEVENT for a schedule
     */
    private function generate_vevent($schedule) {
        $uid = 'rp-' . $schedule->id . '@roosterplanner.local';
        $dtstamp = gmdate('Ymd\THis\Z');
        $dtstart = date('Ymd\THis', strtotime($schedule->work_date . ' ' . $schedule->start_time));
        $dtend = date('Ymd\THis', strtotime($schedule->work_date . ' ' . $schedule->end_time));
        
        $summary = $this->escape_ical_text($schedule->shift_name);
        $location = $this->escape_ical_text($schedule->location_name);
        
        // Calculate duration for overnight shifts
        $start_ts = strtotime($schedule->work_date . ' ' . $schedule->start_time);
        $end_ts = strtotime($schedule->work_date . ' ' . $schedule->end_time);
        if ($end_ts < $start_ts) {
            $dtend = date('Ymd\THis', strtotime('+1 day', $end_ts));
        }
        
        $vevent = "BEGIN:VEVENT\r\n";
        $vevent .= "UID:$uid\r\n";
        $vevent .= "DTSTAMP:$dtstamp\r\n";
        $vevent .= "DTSTART;TZID=Europe/Amsterdam:$dtstart\r\n";
        $vevent .= "DTEND;TZID=Europe/Amsterdam:$dtend\r\n";
        $vevent .= "SUMMARY:$summary\r\n";
        $vevent .= "LOCATION:$location\r\n";
        $vevent .= "DESCRIPTION:" . $this->escape_ical_text("Dienst: $schedule->shift_name\nLocatie: $schedule->location_name") . "\r\n";
        $vevent .= "STATUS:CONFIRMED\r\n";
        $vevent .= "END:VEVENT\r\n";
        
        return $vevent;
    }
    
    /**
     * Escape text for iCal format
     */
    private function escape_ical_text($text) {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);
        return $text;
    }
    
    /**
     * Generate unique token for employee
     */
    public static function generate_token($employee_id) {
        $token = wp_generate_password(32, false, false);
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rp_employees',
            ['ical_token' => $token],
            ['id' => $employee_id],
            ['%s'],
            ['%d']
        );
        
        return $token;
    }
    
    /**
     * Get iCal URL for employee
     */
    public static function get_ical_url($employee_id) {
        global $wpdb;
        
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT ical_token FROM {$wpdb->prefix}rp_employees WHERE id = %d",
            $employee_id
        ));
        
        if (!$employee || empty($employee->ical_token)) {
            $token = self::generate_token($employee_id);
        } else {
            $token = $employee->ical_token;
        }
        
        return home_url("/ical/$token/");
    }
    
    /**
     * Invalidate iCal token (e.g., when employee is deactivated)
     */
    public static function invalidate_token($employee_id) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'rp_employees',
            ['ical_token' => null],
            ['id' => $employee_id],
            ['%s'],
            ['%d']
        );
    }
}
