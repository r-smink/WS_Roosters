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
        add_action('wp_ajax_rp_toggle_swappable', [$this, 'toggle_swappable']);
        add_action('wp_ajax_rp_save_theme_preference', [$this, 'save_theme_preference']);
        add_action('wp_ajax_rp_save_email_preference', [$this, 'save_email_preference']);
        add_action('wp_ajax_rp_save_push_preference', [$this, 'save_push_preference']);
        add_action('wp_ajax_rp_auto_schedule', [$this, 'auto_schedule']);
        add_action('wp_ajax_rp_resolve_schedule_conflict', [$this, 'resolve_schedule_conflict']);
        add_action('wp_ajax_rp_auto_schedule_finalize', [$this, 'auto_schedule_finalize']);
        add_action('wp_ajax_rp_move_schedule', [$this, 'move_schedule']);
        add_action('wp_ajax_rp_finalize_month', [$this, 'finalize_month']);
        add_action('wp_ajax_rp_regenerate_ical_token', [$this, 'regenerate_ical_token']);
        add_action('wp_ajax_rp_clear_calendar', [$this, 'clear_calendar']);
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
        
        // Handle worked hours fields
        if (!empty($_POST['actual_start_time'])) {
            $data['actual_start_time'] = sanitize_text_field($_POST['actual_start_time']);
        }
        if (!empty($_POST['actual_end_time'])) {
            $data['actual_end_time'] = sanitize_text_field($_POST['actual_end_time']);
        }
        if (isset($_POST['break_minutes'])) {
            $data['break_minutes'] = intval($_POST['break_minutes']);
        }
        
        $schedule_id = !empty($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
        $is_edit = $schedule_id > 0;
        
        if ($is_edit) {
            $wpdb->update($wpdb->prefix . 'rp_schedules', $data, ['id' => $schedule_id]);
        } else {
            $wpdb->insert($wpdb->prefix . 'rp_schedules', $data);
            $schedule_id = $wpdb->insert_id;
        }
        
        // Check if month is finalized - only send notifications if finalized OR if it's an edit
        $is_month_finalized = $this->is_month_finalized($data['location_id'], date('Y-m', strtotime($data['work_date'])));
        
        // Send notification only if month is finalized (for new schedules) OR if editing an existing schedule
        if ($is_month_finalized || $is_edit) {
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
                
                $notification_type = $is_edit ? 'schedule_updated' : 'schedule_assigned';
                $notification_title = $is_edit ? 'Dienst gewijzigd' : 'Nieuwe dienst toegekend';
                $notification_message = $is_edit 
                    ? 'Je dienst is gewijzigd: ' . ($shift ? $shift->name : 'een dienst') . ' op ' . date('d-m-Y', strtotime($data['work_date']))
                    : 'Je bent ingepland voor ' . ($shift ? $shift->name : 'een dienst') . ' op ' . date('d-m-Y', strtotime($data['work_date']));
                
                $this->create_notification($employee->user_id, $notification_type, 
                    $notification_title,
                    $notification_message
                );
            }
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
        
        // Ensure worked hours fields are included in response
        $schedule->actual_start_time = $schedule->actual_start_time ?? '';
        $schedule->actual_end_time = $schedule->actual_end_time ?? '';
        $schedule->break_minutes = $schedule->break_minutes ?? 0;
        
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
                'custom_start' => !empty($data['custom_start']) ? sanitize_text_field($data['custom_start']) : null,
                'custom_end' => !empty($data['custom_end']) ? sanitize_text_field($data['custom_end']) : null,
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
    
    /**
     * Toggle swappable status for a schedule
     */
    public function toggle_swappable() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $schedule_id = intval($_POST['schedule_id']);
        
        // Verify ownership
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_schedules WHERE id = %d AND employee_id = %d",
            $schedule_id, $employee->id
        ));
        
        if (!$schedule) {
            wp_send_json_error('Dienst niet gevonden');
        }
        
        // Toggle the is_swappable status
        $new_status = $schedule->is_swappable ? 0 : 1;
        
        $wpdb->update($wpdb->prefix . 'rp_schedules', [
            'is_swappable' => $new_status
        ], ['id' => $schedule_id]);
        
        wp_send_json_success([
            'is_swappable' => $new_status,
            'message' => $new_status ? 'Dienst is nu gemarkeerd als ruilbaar' : 'Dienst is niet meer ruilbaar'
        ]);
    }
    
    /**
     * Save theme preference for employee
     */
    public function save_theme_preference() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $theme = sanitize_text_field($_POST['theme']);
        
        if (!in_array($theme, ['light', 'dark'])) {
            wp_send_json_error('Ongeldig thema');
        }
        
        $wpdb->update($wpdb->prefix . 'rp_employees', [
            'theme_preference' => $theme
        ], ['id' => $employee->id]);
        
        wp_send_json_success(['theme' => $theme]);
    }
    
    /**
     * Save email notification preference for employee
     */
    public function save_email_preference() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $enabled = intval($_POST['enabled']);
        
        $wpdb->update($wpdb->prefix . 'rp_employees', [
            'email_notifications' => $enabled
        ], ['id' => $employee->id]);
        
        wp_send_json_success(['enabled' => $enabled]);
    }
    
    /**
     * Save push notification preference for employee
     */
    public function save_push_preference() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        $enabled = intval($_POST['enabled']);
        
        $wpdb->update($wpdb->prefix . 'rp_employees', [
            'push_notifications' => $enabled
        ], ['id' => $employee->id]);
        
        wp_send_json_success(['enabled' => $enabled]);
    }
    
    /**
     * Auto-schedule shifts for a month based on availability and contract hours
     * Modified to detect conflicts and respect custom times
     */
    public function auto_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        // Parse form data
        parse_str($_POST['data'], $form_data);
        
        $location_id = intval($form_data['location_id']);
        $month = sanitize_text_field($form_data['month']);
        $respect_contract_hours = !empty($form_data['respect_contract_hours']);
        $respect_availability = !empty($form_data['respect_availability']);
        $balance_shifts = !empty($form_data['balance_shifts']);
        $overwrite_existing = !empty($form_data['overwrite_existing']);
        $resolve_conflicts = !empty($_POST['resolve_conflicts']);
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Get all shifts for this location
        $shifts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_shifts WHERE location_id = %d AND is_active = 1 ORDER BY start_time",
            $location_id
        ));
        
        // Get employees with their availability for this location
        $employees = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, u.display_name, GROUP_CONCAT(el.location_id) as assigned_locations
            FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}rp_employee_locations el ON e.id = el.employee_id
            WHERE e.is_active = 1
            GROUP BY e.id
            HAVING FIND_IN_SET(%d, assigned_locations)
            ORDER BY u.display_name",
            $location_id
        ));
        
        // Index employees by ID for quick lookup
        $employees_by_id = [];
        foreach ($employees as $emp) {
            $employees_by_id[$emp->id] = $emp;
        }
        
        // Get all availability for this month and location
        $availability_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_availability 
            WHERE location_id = %d AND work_date BETWEEN %s AND %s AND is_available = 1",
            $location_id, $start_date, $end_date
        ));
        
        // Index availability by employee_id and date
        $availability_by_employee = [];
        foreach ($availability_data as $a) {
            $availability_by_employee[$a->employee_id][$a->work_date] = $a;
        }
        
        // Get existing schedules for this month
        $existing_schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_schedules 
            WHERE location_id = %d AND work_date BETWEEN %s AND %s AND status != 'cancelled'",
            $location_id, $start_date, $end_date
        ));
        
        // Index existing schedules by date and shift
        $existing_by_date_shift = [];
        foreach ($existing_schedules as $s) {
            $existing_by_date_shift[$s->work_date][$s->shift_id] = $s;
        }
        
        $conflicts = [];
        $open_shifts = [];
        $scheduled_count = 0;
        
        // Loop through each day of the month
        $days_in_month = date('t', strtotime($start_date));
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            
            // Check if date is in the past (skip past dates)
            if ($date < current_time('Y-m-d')) {
                continue;
            }
            
            // For each shift on this date, find employees with preference
            foreach ($shifts as $shift) {
                // Check if shift is already scheduled for this date
                if (!$overwrite_existing && isset($existing_by_date_shift[$date][$shift->id])) {
                    continue;
                }
                
                // Find all employees who prefer this shift on this date
                $preferred_employees = [];
                
                foreach ($employees as $emp) {
                    // Check if employee has availability for this date
                    if (!isset($availability_by_employee[$emp->id][$date])) {
                        continue;
                    }
                    
                    $avail = $availability_by_employee[$emp->id][$date];
                    
                    // Check if employee prefers this specific shift
                    if (empty($avail->shift_preference) || $avail->shift_preference != $shift->id) {
                        continue;
                    }
                    
                    // Check if employee is already scheduled for this date
                    $already_scheduled = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules 
                        WHERE employee_id = %d AND work_date = %s AND status != 'cancelled'",
                        $emp->id, $date
                    ));
                    
                    if ($already_scheduled > 0 && !$overwrite_existing) {
                        continue;
                    }
                    
                    // Calculate shift hours using custom times if provided
                    $shift_hours = 0;
                    if (!empty($avail->custom_start) && !empty($avail->custom_end)) {
                        $shift_hours = $this->calculate_shift_hours($avail->custom_start, $avail->custom_end);
                    } else {
                        $shift_hours = $this->calculate_shift_hours($shift->start_time, $shift->end_time);
                    }
                    
                    // Get weekly hours for contract checking
                    $weekly_hours = $this->get_scheduled_hours_for_week($emp->id, $date);
                    
                    // Check contract hours
                    $contract_hours_ok = true;
                    if ($respect_contract_hours && $emp->contract_hours > 0) {
                        if (($weekly_hours + $shift_hours) > $emp->contract_hours) {
                            $contract_hours_ok = false;
                        }
                    }
                    
                    // Check for time conflicts
                    $time_conflict = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules s
                        LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
                        WHERE s.employee_id = %d AND s.work_date = %s AND s.status != 'cancelled'
                        AND ((sh.start_time < %s AND sh.end_time > %s) OR (sh.start_time < %s AND sh.end_time > %s)
                        OR (sh.start_time >= %s AND sh.end_time <= %s))",
                        $emp->id, $date, $shift->end_time, $shift->start_time, 
                        $shift->end_time, $shift->start_time, $shift->start_time, $shift->end_time
                    ));
                    
                    if ($time_conflict > 0 && !$overwrite_existing) {
                        continue;
                    }
                    
                    $preferred_employees[] = [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->display_name,
                        'contract_hours' => $emp->contract_hours,
                        'weekly_hours' => $weekly_hours,
                        'shift_hours' => $shift_hours,
                        'custom_start' => $avail->custom_start,
                        'custom_end' => $avail->custom_end,
                        'contract_hours_ok' => $contract_hours_ok
                    ];
                }
                
                // If multiple employees prefer this shift, it's a conflict
                if (count($preferred_employees) > 1) {
                    $conflicts[] = [
                        'date' => $date,
                        'shift_id' => $shift->id,
                        'shift_name' => $shift->name,
                        'shift_time' => substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5),
                        'employees' => $preferred_employees
                    ];
                } elseif (count($preferred_employees) === 1) {
                    // Only one employee prefers this shift - assign directly
                    $emp = $preferred_employees[0];
                    
                    // If overwriting, delete existing schedule first
                    if ($overwrite_existing && isset($existing_by_date_shift[$date][$shift->id])) {
                        $wpdb->delete($wpdb->prefix . 'rp_schedules', ['id' => $existing_by_date_shift[$date][$shift->id]->id]);
                    }
                    
                    // Determine start and end times - use custom times from availability if provided
                    $start_time = $emp['custom_start'] ?: $shift->start_time;
                    $end_time = $emp['custom_end'] ?: $shift->end_time;
                    
                    // Schedule the shift
                    $wpdb->insert($wpdb->prefix . 'rp_schedules', [
                        'employee_id' => $emp['employee_id'],
                        'location_id' => $location_id,
                        'shift_id' => $shift->id,
                        'work_date' => $date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'status' => 'scheduled',
                        'created_by' => get_current_user_id()
                    ]);
                    
                    // Mark this shift as filled
                    $existing_by_date_shift[$date][$shift->id] = (object)['id' => $wpdb->insert_id];
                    $scheduled_count++;
                } else {
                    // No employees prefer this shift - will be filled in second pass or marked as open
                    // For now, just track it
                }
            }
        }
        
        // If we're in conflict resolution mode and there are conflicts, return them
        if ($resolve_conflicts && !empty($conflicts)) {
            wp_send_json_success([
                'conflicts' => $conflicts,
                'scheduled' => $scheduled_count,
                'respected_contract_hours' => $respect_contract_hours,
                'respected_availability' => $respect_availability
            ]);
        }
        
        // Continue with second pass to fill remaining shifts without specific preferences
        // ... (second pass logic would go here)
        
        wp_send_json_success([
            'scheduled' => $scheduled_count,
            'conflicts' => $conflicts,
            'open_shifts' => $open_shifts,
            'respected_contract_hours' => $respect_contract_hours,
            'respected_availability' => $respect_availability
        ]);
    }
    
    /**
     * Get total scheduled hours for an employee in a specific week
     */
    private function get_scheduled_hours_for_week($employee_id, $date) {
        global $wpdb;
        
        // Get the week containing this date (Monday to Sunday)
        $timestamp = strtotime($date);
        $day_of_week = date('N', $timestamp); // 1 = Monday, 7 = Sunday
        
        $week_start = date('Y-m-d', strtotime('-' . ($day_of_week - 1) . ' days', $timestamp));
        $week_end = date('Y-m-d', strtotime('+' . (7 - $day_of_week) . ' days', $timestamp));
        
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.start_time, sh.end_time FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            WHERE s.employee_id = %d AND s.work_date BETWEEN %s AND %s AND s.status != 'cancelled'",
            $employee_id, $week_start, $week_end
        ));
        
        $total_hours = 0;
        foreach ($schedules as $schedule) {
            $total_hours += $this->calculate_shift_hours($schedule->start_time, $schedule->end_time);
        }
        
        return $total_hours;
    }
    
    /**
     * Get total scheduled hours for an employee in a month
     */
    private function get_scheduled_hours_for_month($employee_id, $month) {
        global $wpdb;
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.start_time, sh.end_time FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            WHERE s.employee_id = %d AND s.work_date BETWEEN %s AND %s AND s.status != 'cancelled'",
            $employee_id, $start_date, $end_date
        ));
        
        $total_hours = 0;
        foreach ($schedules as $schedule) {
            $total_hours += $this->calculate_shift_hours($schedule->start_time, $schedule->end_time);
        }
        
        return $total_hours;
    }
    
    /**
     * Calculate shift hours between start and end time
     */
    private function calculate_shift_hours($start_time, $end_time) {
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        
        // Handle overnight shifts
        if ($end < $start) {
            $end = strtotime('+1 day', $end);
        }
        
        $diff_hours = ($end - $start) / 3600;
        return round($diff_hours, 2);
    }
    
    /**
     * Move a schedule to a new date or shift (drag and drop)
     */
    public function move_schedule() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $schedule_id = intval($_POST['schedule_id']);
        $new_date = sanitize_text_field($_POST['new_date']);
        $new_shift_id = !empty($_POST['new_shift_id']) ? intval($_POST['new_shift_id']) : null;
        
        // Get the current schedule
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_schedules WHERE id = %d",
            $schedule_id
        ));
        
        if (!$schedule) {
            wp_send_json_error('Dienst niet gevonden');
        }
        
        // If new shift is provided, update it; otherwise keep the existing shift
        if ($new_shift_id) {
            // Verify the new shift exists and get its times
            $shift = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rp_shifts WHERE id = %d",
                $new_shift_id
            ));
            
            if (!$shift) {
                wp_send_json_error('Shift niet gevonden');
            }
            
            // Check for conflicts at the new date and shift
            $conflict = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules 
                WHERE work_date = %s AND shift_id = %d AND id != %d AND status != 'cancelled'",
                $new_date, $new_shift_id, $schedule_id
            ));
            
            if ($conflict > 0) {
                wp_send_json_error('Er is al een dienst gepland op dit tijdstip');
            }
            
            // Update the schedule with new date and shift
            $wpdb->update($wpdb->prefix . 'rp_schedules', [
                'work_date' => $new_date,
                'shift_id' => $new_shift_id,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time
            ], ['id' => $schedule_id]);
        } else {
            // Just update the date, keep the same shift
            // Check for conflicts on the new date with the same employee
            $conflict = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules s
                LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
                WHERE s.work_date = %s AND s.employee_id = %d AND s.id != %d AND s.status != 'cancelled'
                AND ((sh.start_time < %s AND sh.end_time > %s) OR (sh.start_time >= %s AND sh.end_time <= %s))",
                $new_date, $schedule->employee_id, $schedule_id,
                $schedule->end_time, $schedule->start_time, $schedule->start_time, $schedule->end_time
            ));
            
            if ($conflict > 0) {
                wp_send_json_error('Er is een tijdsconflict op de nieuwe datum');
            }
            
            $wpdb->update($wpdb->prefix . 'rp_schedules', [
                'work_date' => $new_date
            ], ['id' => $schedule_id]);
        }
        
        wp_send_json_success(['message' => 'Dienst verplaatst']);
    }
    
    /**
     * Check if a month is finalized for a location
     */
    private function is_month_finalized($location_id, $month) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rp_final_schedules WHERE location_id = %d AND month = %s",
            $location_id, $month
        )) > 0;
    }
    
    /**
     * Finalize a month and send bulk notifications to employees
     */
    public function finalize_month() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $location_id = intval($_POST['location_id']);
        $month = sanitize_text_field($_POST['month']);
        
        // Check if already finalized
        if ($this->is_month_finalized($location_id, $month)) {
            wp_send_json_error('Deze maand is al definitief gemaakt');
        }
        
        // Get all schedules for this month and location
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, sh.name as shift_name, u.display_name as employee_name, e.user_id
            FROM {$wpdb->prefix}rp_schedules s
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE s.location_id = %d AND s.work_date LIKE %s AND s.status != 'cancelled'
            ORDER BY e.id, s.work_date",
            $location_id, $month . '%'
        ));
        
        if (empty($schedules)) {
            wp_send_json_error('Er zijn geen diensten om definitief te maken');
        }
        
        // Group schedules by employee
        $employee_schedules = [];
        foreach ($schedules as $schedule) {
            $employee_schedules[$schedule->user_id][] = $schedule;
        }
        
        // Send one bulk notification per employee
        $notification_count = 0;
        foreach ($employee_schedules as $user_id => $emp_schedules) {
            $shift_count = count($emp_schedules);
            $first_date = date('d-m-Y', strtotime($emp_schedules[0]->work_date));
            $last_date = date('d-m-Y', strtotime($emp_schedules[$shift_count - 1]->work_date));
            
            // Build summary of shifts
            $shift_summary = [];
            foreach ($emp_schedules as $s) {
                $shift_summary[] = date('d-m', strtotime($s->work_date)) . ': ' . $s->shift_name;
            }
            
            $message = "Je rooster voor " . date('F Y', strtotime($month . '-01')) . " is definitief.\n\n";
            $message .= "Je hebt " . $shift_count . " dienst(en) gepland:\n";
            $message .= implode("\n", array_slice($shift_summary, 0, 10));
            if (count($shift_summary) > 10) {
                $message .= "\n... en " . (count($shift_summary) - 10) . " meer";
            }
            
            $this->create_notification($user_id, 'schedule_finalized', 
                'Rooster definitief - ' . date('F Y', strtotime($month . '-01')),
                $message
            );
            
            $notification_count++;
        }
        
        // Mark month as finalized
        $wpdb->insert($wpdb->prefix . 'rp_final_schedules', [
            'location_id' => $location_id,
            'month' => $month,
            'finalized_at' => current_time('mysql'),
            'finalized_by' => get_current_user_id()
        ]);
        
        wp_send_json_success([
            'message' => sprintf(
                '%d medewerkers hebben een notificatie ontvangen met hun roosteroverzicht.',
                $notification_count
            )
        ]);
    }
    
    /**
     * Regenerate iCal token for current employee
     */
    public function regenerate_ical_token() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        global $wpdb;
        
        $employee = $this->get_current_employee();
        if (!$employee) {
            wp_send_json_error('Geen toegang');
        }
        
        // Generate new token
        $new_token = ICalExport::generate_token($employee->id);
        $new_url = ICalExport::get_ical_url($employee->id);
        
        wp_send_json_success([
            'url' => $new_url,
            'message' => 'Nieuwe iCal link gegenereerd'
        ]);
    }
    
    /**
     * Clear all schedules for a month and location
     */
    public function clear_calendar() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $location_id = intval($_POST['location_id']);
        $month = sanitize_text_field($_POST['month']);
        
        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            wp_send_json_error('Ongeldig maandformaat');
        }
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Check if month is finalized
        $is_finalized = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rp_final_schedules WHERE location_id = %d AND month = %s",
            $location_id, $month
        ));
        
        if ($is_finalized) {
            wp_send_json_error('Deze maand is definitief gemaakt en kan niet worden gewijzigd');
        }
        
        // Mark all schedules as cancelled for this month and location
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}rp_schedules 
            SET status = 'cancelled' 
            WHERE location_id = %d AND work_date BETWEEN %s AND %s",
            $location_id, $start_date, $end_date
        ));
        
        wp_send_json_success([
            'deleted' => $result,
            'message' => $result . ' diensten verwijderd'
        ]);
    }
    
    /**
     * Resolve a schedule conflict by assigning an employee to a shift
     */
    public function resolve_schedule_conflict() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        $date = sanitize_text_field($_POST['date']);
        $shift_id = intval($_POST['shift_id']);
        $employee_id = intval($_POST['employee_id']);
        $custom_start = !empty($_POST['custom_start']) ? sanitize_text_field($_POST['custom_start']) : null;
        $custom_end = !empty($_POST['custom_end']) ? sanitize_text_field($_POST['custom_end']) : null;
        
        // Get shift and location info
        $shift = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_shifts WHERE id = %d",
            $shift_id
        ));
        
        if (!$shift) {
            wp_send_json_error('Shift niet gevonden');
        }
        
        // Determine start and end times
        $start_time = $custom_start ?: $shift->start_time;
        $end_time = $custom_end ?: $shift->end_time;
        
        // Store the resolved conflict in a temporary table or session
        // For now, we'll create the schedule directly
        $wpdb->insert($wpdb->prefix . 'rp_schedules', [
            'employee_id' => $employee_id,
            'location_id' => $shift->location_id,
            'shift_id' => $shift_id,
            'work_date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => 'scheduled',
            'created_by' => get_current_user_id()
        ]);
        
        wp_send_json_success([
            'schedule_id' => $wpdb->insert_id,
            'message' => 'Dienst toegewezen'
        ]);
    }
    
    /**
     * Finalize auto-schedule after all conflicts are resolved
     */
    public function auto_schedule_finalize() {
        check_ajax_referer('rp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Geen toegang');
        }
        
        global $wpdb;
        
        // Parse form data
        parse_str($_POST['data'], $form_data);
        
        $location_id = intval($form_data['location_id']);
        $month = sanitize_text_field($form_data['month']);
        $respect_contract_hours = !empty($form_data['respect_contract_hours']);
        $respect_availability = !empty($form_data['respect_availability']);
        
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Count how many were scheduled
        $scheduled_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules 
            WHERE location_id = %d AND work_date BETWEEN %s AND %s 
            AND status = 'scheduled' AND created_by = %d",
            $location_id, $start_date, $end_date, get_current_user_id()
        ));
        
        // Get remaining open shifts (shifts without assignments)
        $shifts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_shifts WHERE location_id = %d AND is_active = 1",
            $location_id
        ));
        
        $open_shifts = [];
        $days_in_month = date('t', strtotime($start_date));
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            
            if ($date < current_time('Y-m-d')) {
                continue;
            }
            
            foreach ($shifts as $shift) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}rp_schedules 
                    WHERE location_id = %d AND work_date = %s AND shift_id = %d AND status != 'cancelled'",
                    $location_id, $date, $shift->id
                ));
                
                if (!$exists) {
                    $open_shifts[] = [
                        'date' => date('d-m-Y', strtotime($date)),
                        'date_raw' => $date,
                        'shift_name' => $shift->name,
                        'time' => substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5),
                        'color' => $shift->color
                    ];
                }
            }
        }
        
        wp_send_json_success([
            'scheduled' => $scheduled_count,
            'open_shifts' => $open_shifts,
            'respected_contract_hours' => $respect_contract_hours,
            'respected_availability' => $respect_availability
        ]);
    }
}
