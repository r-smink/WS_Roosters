<?php
namespace RoosterPlanner;

class Ajax {
    
    public function __construct() {
        // Admin AJAX handlers
        add_action('wp_ajax_rp_save_schedule', [$this, 'save_schedule']);
        add_action('wp_ajax_rp_delete_schedule', [$this, 'delete_schedule']);
        add_action('wp_ajax_rp_get_employee_availability', [$this, 'get_employee_availability']);
        add_action('wp_ajax_rp_get_schedule_data', [$this, 'get_schedule_data']);
        add_action('wp_ajax_rp_bulk_schedule', [$this, 'bulk_schedule']);
        add_action('wp_ajax_rp_apply_fixed_schedule', [$this, 'apply_fixed_schedule']);
        
        // Import AJAX handlers
        add_action('wp_ajax_rp_import_demo_data', [$this, 'import_demo_data']);
        add_action('wp_ajax_rp_import_employees_csv', [$this, 'import_employees_csv']);
        add_action('wp_ajax_rp_import_shifts_csv', [$this, 'import_shifts_csv']);
        
        // Bulk update AJAX handlers
        add_action('wp_ajax_rp_bulk_update_employees', [$this, 'bulk_update_employees']);
        add_action('wp_ajax_rp_bulk_update_shifts', [$this, 'bulk_update_shifts']);
        
        // Frontend AJAX handlers
        add_action('wp_ajax_rp_submit_availability', [$this, 'submit_availability']);
        add_action('wp_ajax_rp_request_swap', [$this, 'request_swap']);
        add_action('wp_ajax_rp_respond_swap', [$this, 'respond_swap']);
        add_action('wp_ajax_rp_request_timeoff', [$this, 'request_timeoff']);
        add_action('wp_ajax_rp_send_chat_message', [$this, 'send_chat_message']);
        add_action('wp_ajax_rp_get_chat_messages', [$this, 'get_chat_messages']);
        add_action('wp_ajax_rp_mark_notification_read', [$this, 'mark_notification_read']);
        add_action('wp_ajax_rp_get_notifications', [$this, 'get_notifications']);
        add_action('wp_ajax_rp_report_sick', [$this, 'report_sick']);
        add_action('wp_ajax_rp_save_fixed_schedule', [$this, 'save_fixed_schedule']);
    }
    
    public function save_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $data = [
            'employee_id' => intval($_POST['employee_id']),
            'location_id' => intval($_POST['location_id']),
            'shift_id' => intval($_POST['shift_id']),
            'work_date' => sanitize_text_field($_POST['work_date']),
            'created_by' => get_current_user_id()
        ];
        
        if (!empty($_POST['start_time'])) {
            $data['start_time'] = sanitize_text_field($_POST['start_time']);
        }
        if (!empty($_POST['end_time'])) {
            $data['end_time'] = sanitize_text_field($_POST['end_time']);
        }
        if (!empty($_POST['notes'])) {
            $data['notes'] = sanitize_textarea_field($_POST['notes']);
        }
        
        $schedule_id = !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        
        if ($schedule_id) {
            $wpdb->update($wpdb->prefix . 'rp_schedules', $data, ['id' => $schedule_id]);
        } else {
            $wpdb->insert($wpdb->prefix . 'rp_schedules', $data);
            $schedule_id = $wpdb->insert_id;
        }
        
        // Send notification to employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}rp_employees WHERE id = %d",
            $data['employee_id']
        ));
        
        if ($employee) {
            $shift = $wpdb->get_row($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}rp_shifts WHERE id = %d",
                $data['shift_id']
            ));
            
            $this->create_notification($employee->user_id, 'schedule_assigned', 
                'Nieuwe dienst toegekend',
                'Je bent ingepland voor ' . ($shift ? $shift->name : 'een dienst') . ' op ' . date('d-m-Y', strtotime($data['work_date']))
            );
        }
        
        wp_send_json_success(['schedule_id' => $schedule_id]);
    }
    
    public function delete_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $schedule_id = intval($_POST['schedule_id']);
        $wpdb->update($wpdb->prefix . 'rp_schedules', ['status' => 'cancelled'], ['id' => $schedule_id]);
        
        wp_send_json_success();
    }
    
    public function get_employee_availability() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $employee_id = intval($_POST['employee_id']);
        $month = sanitize_text_field($_POST['month']);
        
        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, s.name as shift_name FROM {$wpdb->prefix}rp_availability a
            LEFT JOIN {$wpdb->prefix}rp_shifts s ON a.shift_preference = s.id
            WHERE a.employee_id = %d AND a.work_date LIKE %s",
            $employee_id, $month . '%'
        ));
        
        wp_send_json_success(['availability' => $availability]);
    }
    
    public function get_schedule_data() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $schedule_id = intval($_POST['schedule_id']);
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, u.display_name as employee_name
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE s.id = %d",
            $schedule_id
        ));
        
        if (!$schedule) {
            wp_send_json_error('Dienst niet gevonden');
        }
        
        // Check permissions
        $current_employee = $this->get_current_employee();
        if (!current_user_can('manage_options') && $schedule->employee_id != ($current_employee ? $current_employee->id : 0)) {
            wp_send_json_error('Geen toegang');
        }
        
        wp_send_json_success(['schedule' => $schedule]);
    }
    
    public function bulk_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $employee_id = intval($_POST['employee_id']);
        $location_id = intval($_POST['location_id']);
        $shift_id = intval($_POST['shift_id']);
        $dates = json_decode(stripslashes($_POST['dates']), true);
        
        $inserted = 0;
        foreach ($dates as $date) {
            $date = sanitize_text_field($date);
            
            // Check if already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rp_schedules 
                WHERE employee_id = %d AND work_date = %s AND location_id = %d AND status != 'cancelled'",
                $employee_id, $date, $location_id
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'rp_schedules', [
                    'employee_id' => $employee_id,
                    'location_id' => $location_id,
                    'shift_id' => $shift_id,
                    'work_date' => $date,
                    'created_by' => get_current_user_id()
                ]);
                $inserted++;
            }
        }
        
        wp_send_json_success(['inserted' => $inserted]);
    }
    
    public function submit_availability() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $location_id = intval($_POST['location_id']);
        $availability_data = json_decode(stripslashes($_POST['availability']), true);
        
        foreach ($availability_data as $date => $data) {
            $wpdb->replace($wpdb->prefix . 'rp_availability', [
                'employee_id' => $employee->id,
                'location_id' => $location_id,
                'work_date' => sanitize_text_field($date),
                'is_available' => $data['available'] ? 1 : 0,
                'shift_preference' => !empty($data['shift_id']) ? intval($data['shift_id']) : null,
                'notes' => !empty($data['notes']) ? sanitize_text_field($data['notes']) : null
            ]);
        }
        
        wp_send_json_success();
    }
    
    public function request_swap() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $schedule_id = intval($_POST['schedule_id']);
        $requested_employee_id = !empty($_POST['requested_employee_id']) ? intval($_POST['requested_employee_id']) : null;
        $reason = sanitize_textarea_field($_POST['reason']);
        
        // Verify ownership of schedule
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_schedules WHERE id = %d AND employee_id = %d",
            $schedule_id, $employee->id
        ));
        
        if (!$schedule) {
            wp_send_json_error('Dienst niet gevonden');
        }
        
        $wpdb->insert($wpdb->prefix . 'rp_shift_swaps', [
            'schedule_id' => $schedule_id,
            'requester_id' => $employee->id,
            'requested_employee_id' => $requested_employee_id,
            'reason' => $reason,
            'status' => 'pending'
        ]);
        
        $swap_id = $wpdb->insert_id;
        
        // Send notification to requested employee or all admin
        if ($requested_employee_id) {
            $target_employee = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}rp_employees WHERE id = %d",
                $requested_employee_id
            ));
            if ($target_employee) {
                $this->create_notification($target_employee->user_id, 'swap_request',
                    'Dienst ruil verzoek',
                    'Iemand wil een dienst met je ruilen. Bekijk het verzoek in de app.'
                );
            }
        }
        
        // Notify admins
        $this->notify_admins('Nieuw ruilverzoek', 'Er is een nieuw dienst ruilverzoek ingediend.');
        
        wp_send_json_success(['swap_id' => $swap_id]);
    }
    
    public function respond_swap() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $swap_id = intval($_POST['swap_id']);
        $action = sanitize_text_field($_POST['action']); // accept or reject
        
        $swap = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_shift_swaps WHERE id = %d AND requested_employee_id = %d",
            $swap_id, $employee->id
        ));
        
        if (!$swap) {
            wp_send_json_error('Ruilverzoek niet gevonden');
        }
        
        $status = ($action === 'accept') ? 'completed' : 'rejected';
        
        $wpdb->update($wpdb->prefix . 'rp_shift_swaps', [
            'status' => $status,
            'responded_at' => current_time('mysql')
        ], ['id' => $swap_id]);
        
        if ($action === 'accept') {
            // Update the schedule with new employee
            $wpdb->update($wpdb->prefix . 'rp_schedules', [
                'employee_id' => $employee->id,
                'status' => 'swapped'
            ], ['id' => $swap->schedule_id]);
        }
        
        // Notify requester
        $requester = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}rp_employees WHERE id = %d",
            $swap->requester_id
        ));
        
        if ($requester) {
            $this->create_notification($requester->user_id, 'swap_response',
                'Reactie op ruilverzoek',
                'Je ruilverzoek is ' . ($action === 'accept' ? 'geaccepteerd' : 'afgewezen') . '.'
            );
        }
        
        wp_send_json_success();
    }
    
    public function request_timeoff() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $type = sanitize_text_field($_POST['type']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        $wpdb->insert($wpdb->prefix . 'rp_timeoff', [
            'employee_id' => $employee->id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'type' => $type,
            'reason' => $reason,
            'status' => 'pending'
        ]);
        
        // Notify admins
        $this->notify_admins('Nieuw verlofverzoek', 
            'Er is een nieuw verlofverzoek ingediend van ' . date('d-m-Y', strtotime($start_date)) . ' tot ' . date('d-m-Y', strtotime($end_date)));
        
        wp_send_json_success();
    }
    
    public function report_sick() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $schedule_id = intval($_POST['schedule_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Cancel the schedule
        $wpdb->update($wpdb->prefix . 'rp_schedules', [
            'status' => 'cancelled',
            'notes' => $notes
        ], ['id' => $schedule_id, 'employee_id' => $employee->id]);
        
        // Create timeoff record for sick leave
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT work_date FROM {$wpdb->prefix}rp_schedules WHERE id = %d",
            $schedule_id
        ));
        
        if ($schedule) {
            $wpdb->insert($wpdb->prefix . 'rp_timeoff', [
                'employee_id' => $employee->id,
                'start_date' => $schedule->work_date,
                'end_date' => $schedule->work_date,
                'type' => 'sick',
                'reason' => $notes,
                'status' => 'approved'
            ]);
        }
        
        // Notify admins
        $this->notify_admins('Ziekmelding', 
            'Er is een ziekmelding binnengekomen. Er moet vervanging worden gezocht.');
        
        // Notify all employees for replacement
        $employees = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}rp_employees WHERE is_active = 1 AND id != {$employee->id}");
        foreach ($employees as $emp) {
            $this->create_notification($emp->user_id, 'replacement_needed',
                'Vervanging nodig',
                'Er is een dienst vrijgekomen door ziekmelding. Ben je beschikbaar?'
            );
        }
        
        wp_send_json_success();
    }
    
    public function send_chat_message() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Geen toegang');
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $is_announcement = !empty($_POST['is_announcement']) && current_user_can('manage_options') ? 1 : 0;
        $location_id = !empty($_POST['location_id']) ? intval($_POST['location_id']) : null;
        
        $wpdb->insert($wpdb->prefix . 'rp_chat_messages', [
            'sender_id' => get_current_user_id(),
            'message' => $message,
            'is_announcement' => $is_announcement,
            'location_id' => $location_id
        ]);
        
        // If announcement, notify all employees
        if ($is_announcement) {
            $employees = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}rp_employees WHERE is_active = 1");
            foreach ($employees as $emp) {
                if ($emp->user_id != get_current_user_id()) {
                    $this->create_notification($emp->user_id, 'announcement',
                        'Nieuw bericht van de planner',
                        substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '')
                    );
                }
            }
        }
        
        wp_send_json_success(['message_id' => $wpdb->insert_id]);
    }
    
    public function get_chat_messages() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $last_id = !empty($_POST['last_id']) ? intval($_POST['last_id']) : 0;
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
            FROM {$wpdb->prefix}rp_chat_messages m
            LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
            WHERE m.id > %d
            ORDER BY m.created_at ASC
            LIMIT 50",
            $last_id
        ));
        
        wp_send_json_success(['messages' => $messages]);
    }
    
    public function mark_notification_read() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $notification_id = intval($_POST['notification_id']);
        
        $wpdb->update($wpdb->prefix . 'rp_notifications', [
            'is_read' => 1
        ], [
            'id' => $notification_id,
            'user_id' => get_current_user_id()
        ]);
        
        wp_send_json_success();
    }
    
    public function get_notifications() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_notifications
            WHERE user_id = %d
            ORDER BY created_at DESC
            LIMIT 20",
            get_current_user_id()
        ));
        
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_notifications
            WHERE user_id = %d AND is_read = 0",
            get_current_user_id()
        ));
        
        wp_send_json_success([
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
    }
    
    public function apply_fixed_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $employee_id = intval($_POST['employee_id']);
        $month = sanitize_text_field($_POST['month']);
        
        // Get fixed schedules
        $fixed = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_fixed_schedules
            WHERE employee_id = %d AND is_active = 1",
            $employee_id
        ));
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $inserted = 0;
        
        // Loop through each day of the month
        for ($day = 1; $day <= date('t', strtotime($start_date)); $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            $day_of_week = date('w', strtotime($date));
            
            // Find matching fixed schedule
            foreach ($fixed as $fs) {
                if ($fs->day_of_week == $day_of_week) {
                    // Check if already exists
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}rp_schedules
                        WHERE employee_id = %d AND work_date = %s AND location_id = %d AND status != 'cancelled'",
                        $employee_id, $date, $fs->location_id
                    ));
                    
                    if (!$exists) {
                        $wpdb->insert($wpdb->prefix . 'rp_schedules', [
                            'employee_id' => $employee_id,
                            'location_id' => $fs->location_id,
                            'shift_id' => $fs->shift_id,
                            'work_date' => $date,
                            'created_by' => get_current_user_id()
                        ]);
                        $inserted++;
                    }
                    break;
                }
            }
        }
        
        wp_send_json_success(['inserted' => $inserted]);
    }
    
    public function save_fixed_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !$this->is_current_employee_admin()) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $employee_id = intval($_POST['employee_id']);
        $day_of_week = intval($_POST['day_of_week']);
        $location_id = intval($_POST['location_id']);
        $shift_id = intval($_POST['shift_id']);
        
        // Check if entry exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rp_fixed_schedules
            WHERE employee_id = %d AND day_of_week = %d AND location_id = %d",
            $employee_id, $day_of_week, $location_id
        ));
        
        if ($existing) {
            if ($shift_id === 0) {
                // Remove fixed schedule
                $wpdb->delete($wpdb->prefix . 'rp_fixed_schedules', ['id' => $existing]);
            } else {
                $wpdb->update($wpdb->prefix . 'rp_fixed_schedules', [
                    'shift_id' => $shift_id
                ], ['id' => $existing]);
            }
        } else if ($shift_id > 0) {
            $wpdb->insert($wpdb->prefix . 'rp_fixed_schedules', [
                'employee_id' => $employee_id,
                'location_id' => $location_id,
                'shift_id' => $shift_id,
                'day_of_week' => $day_of_week
            ]);
        }
        
        wp_send_json_success();
    }
    
    private function get_current_employee() {
        global $wpdb;
        if (!is_user_logged_in()) return null;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_employees WHERE user_id = %d",
            get_current_user_id()
        ));
    }
    
    private function is_current_employee_admin() {
        $emp = $this->get_current_employee();
        return $emp && $emp->is_admin;
    }
    
    private function create_notification($user_id, $type, $title, $message) {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'rp_notifications', [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message
        ]);
    }
    
    private function notify_admins($title, $message) {
        global $wpdb;
        
        $admins = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}rp_employees WHERE is_admin = 1 OR is_active = 1");
        foreach ($admins as $admin) {
            $this->create_notification($admin->user_id, 'admin_notice', $title, $message);
        }
    }
    
    /**
     * Import demo data (locations and shifts)
     */
    public function import_demo_data() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        $result = rooster_planner_import_demo_data();
        
        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Import employees from CSV
     */
    public function import_employees_csv() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error('Geen bestand geüpload');
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Upload fout: ' . $file['error']);
        }
        
        // Check file extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['csv', 'txt'])) {
            wp_send_json_error('Alleen CSV bestanden zijn toegestaan');
        }
        
        $csv_data = file_get_contents($file['tmp_name']);
        
        if (empty($csv_data)) {
            wp_send_json_error('Bestand is leeg');
        }
        
        $result = rooster_planner_import_employees_csv($csv_data);
        
        wp_send_json_success([
            'imported' => $result['imported'],
            'existing' => $result['existing'],
            'errors' => $result['errors'],
            'message' => sprintf(
                '%d medewerkers geïmporteerd, %d bestonden al, %d fouten',
                $result['imported'],
                $result['existing'],
                count($result['errors'])
            )
        ]);
    }
    
    /**
     * Import shifts from CSV
     */
    public function import_shifts_csv() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error('Geen bestand geüpload');
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Upload fout: ' . $file['error']);
        }
        
        // Check file extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['csv', 'txt'])) {
            wp_send_json_error('Alleen CSV bestanden zijn toegestaan');
        }
        
        $csv_data = file_get_contents($file['tmp_name']);
        
        if (empty($csv_data)) {
            wp_send_json_error('Bestand is leeg');
        }
        
        $result = rooster_planner_import_shifts_csv($csv_data);
        
        wp_send_json_success([
            'imported' => $result['imported'],
            'existing' => $result['existing'],
            'errors' => $result['errors'],
            'message' => sprintf(
                '%d shifts geïmporteerd, %d bestonden al, %d fouten',
                $result['imported'],
                $result['existing'],
                count($result['errors'])
            )
        ]);
    }
    
    /**
     * Bulk update employees
     */
    public function bulk_update_employees() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        $updates = !empty($_POST['updates']) ? json_decode(stripslashes($_POST['updates']), true) : [];
        
        if (empty($updates)) {
            wp_send_json_error('Geen updates ontvangen');
        }
        
        $result = rooster_planner_bulk_update_employees($updates);
        
        wp_send_json_success([
            'updated' => $result['updated'],
            'errors' => $result['errors'],
            'message' => sprintf('%d medewerkers bijgewerkt', $result['updated'])
        ]);
    }
    
    /**
     * Bulk update shifts
     */
    public function bulk_update_shifts() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        $updates = !empty($_POST['updates']) ? json_decode(stripslashes($_POST['updates']), true) : [];
        
        if (empty($updates)) {
            wp_send_json_error('Geen updates ontvangen');
        }
        
        $result = rooster_planner_bulk_update_shifts($updates);
        
        wp_send_json_success([
            'updated' => $result['updated'],
            'errors' => $result['errors'],
            'message' => sprintf('%d shifts bijgewerkt', $result['updated'])
        ]);
    }
}
