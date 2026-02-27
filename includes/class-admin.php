<?php
namespace RoosterPlanner;

class Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_init', [$this, 'handle_admin_actions']);
        add_action('admin_post_rp_admin_sick_report', [$this, 'handle_admin_sick_report']);
    }
    
    public function add_menu_pages() {
        add_menu_page(
            'Rooster Planner',
            'Rooster Planner',
            'manage_options',
            'rooster-planner',
            [$this, 'render_dashboard'],
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'rooster-planner',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'rooster-planner',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Locaties & Shifts',
            'Locaties & Shifts',
            'manage_options',
            'rooster-planner-locations',
            [$this, 'render_locations']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Medewerkers',
            'Medewerkers',
            'manage_options',
            'rooster-planner-employees',
            [$this, 'render_employees']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Roosters Plannen',
            'Roosters Plannen',
            'manage_options',
            'rooster-planner-schedules',
            [$this, 'render_schedules']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Beschikbaarheid',
            'Beschikbaarheid',
            'manage_options',
            'rooster-planner-availability',
            [$this, 'render_availability']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Ruilingen & Verlof',
            'Ruilingen & Verlof',
            'manage_options',
            'rooster-planner-swaps',
            [$this, 'render_swaps']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Chat & Berichten',
            'Chat & Berichten',
            'manage_options',
            'rooster-planner-chat',
            [$this, 'render_chat_admin']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Rapportages',
            'Rapportages',
            'manage_options',
            'rooster-planner-reports',
            [$this, 'render_reports']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Verzuim',
            'Verzuim',
            'manage_options',
            'rooster-planner-sick-report',
            [$this, 'render_sick_report']
        );
        
        add_submenu_page(
            'rooster-planner',
            'Instellingen',
            'Instellingen',
            'manage_options',
            'rooster-planner-settings',
            [$this, 'render_settings']
        );
    }
    
    public function render_dashboard() {
        global $wpdb;
        
        $total_employees = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rp_employees WHERE is_active = 1");
        $pending_swaps = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rp_shift_swaps WHERE status = 'pending'");
        $pending_timeoff = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rp_timeoff WHERE status = 'pending'");
        $today_schedules = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules WHERE work_date = %s",
            current_time('Y-m-d')
        ));
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function render_locations() {
        global $wpdb;
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        $shifts = $wpdb->get_results("SELECT s.*, l.name as location_name FROM {$wpdb->prefix}rp_shifts s 
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id 
            ORDER BY l.name, s.start_time");
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/locations.php';
    }
    
    public function render_employees() {
        global $wpdb;
        
        $employees = $wpdb->get_results("SELECT e.*, u.display_name, u.user_email, u.user_login,
            GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR ', ') as locations
            FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}rp_employee_locations el ON e.id = el.employee_id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON el.location_id = l.id
            GROUP BY e.id
            ORDER BY u.display_name");
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        $wp_users = $wpdb->get_results("SELECT ID, display_name, user_email FROM {$wpdb->users} 
            WHERE ID NOT IN (SELECT user_id FROM {$wpdb->prefix}rp_employees) 
            ORDER BY display_name");
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/employees.php';
    }
    
    public function render_schedules() {
        global $wpdb;
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        
        // Get employees with their location assignments
        $employees = $wpdb->get_results("SELECT e.*, u.display_name,
            GROUP_CONCAT(DISTINCT el.location_id) as assigned_locations
            FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}rp_employee_locations el ON e.id = el.employee_id
            WHERE e.is_active = 1
            GROUP BY e.id
            ORDER BY u.display_name");
        
        $shifts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_shifts WHERE is_active = 1 ORDER BY name");
        
        $current_location = isset($_GET['location']) ? intval($_GET['location']) : ($locations[0]->id ?? 1);
        $current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : current_time('Y-m');
        
        // Get availability for current month and location
        $start_date = $current_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, e.id as employee_id
            FROM {$wpdb->prefix}rp_availability a
            LEFT JOIN {$wpdb->prefix}rp_employees e ON a.employee_id = e.id
            WHERE a.work_date BETWEEN %s AND %s AND a.location_id = %d AND a.is_available = 1",
            $start_date, $end_date, $current_location
        ));
        
        // Index availability by employee_id and date for easy lookup
        $availability_by_employee = [];
        foreach ($availability as $a) {
            $availability_by_employee[$a->employee_id][$a->work_date] = $a;
        }
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/schedules.php';
    }
    
    public function render_availability() {
        global $wpdb;
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        $current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m', strtotime('+1 month'));
        $current_location = isset($_GET['location']) ? intval($_GET['location']) : ($locations[0]->id ?? 1);
        
        // Get employees assigned to the selected location only
        $employees = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, u.display_name FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            INNER JOIN {$wpdb->prefix}rp_employee_locations el ON e.id = el.employee_id
            WHERE e.is_active = 1 AND el.location_id = %d
            ORDER BY u.display_name",
            $current_location
        ));
        
        // Get availability for selected month and location
        $start_date = $current_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name, s.name as shift_name
            FROM {$wpdb->prefix}rp_availability a
            LEFT JOIN {$wpdb->prefix}rp_employees e ON a.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}rp_shifts s ON a.shift_preference = s.id
            WHERE a.work_date BETWEEN %s AND %s AND a.location_id = %d
            ORDER BY a.work_date, u.display_name",
            $start_date, $end_date, $current_location
        ));
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/availability.php';
    }
    
    public function render_swaps() {
        global $wpdb;
        
        $pending_swaps = $wpdb->get_results("SELECT sw.*, 
            u1.display_name as requester_name,
            u2.display_name as requested_name,
            s.work_date, sh.name as shift_name, sh.start_time, sh.end_time,
            l.name as location_name
            FROM {$wpdb->prefix}rp_shift_swaps sw
            LEFT JOIN {$wpdb->prefix}rp_schedules s ON sw.schedule_id = s.id
            LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
            LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
            LEFT JOIN {$wpdb->prefix}rp_employees e1 ON sw.requester_id = e1.id
            LEFT JOIN {$wpdb->users} u1 ON e1.user_id = u1.ID
            LEFT JOIN {$wpdb->prefix}rp_employees e2 ON sw.requested_employee_id = e2.id
            LEFT JOIN {$wpdb->users} u2 ON e2.user_id = u2.ID
            WHERE sw.status = 'pending'
            ORDER BY sw.requested_at DESC");
        
        $pending_timeoff = $wpdb->get_results("SELECT t.*, u.display_name
            FROM {$wpdb->prefix}rp_timeoff t
            LEFT JOIN {$wpdb->prefix}rp_employees e ON t.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE t.status = 'pending'
            ORDER BY t.requested_at DESC");
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/swaps.php';
    }
    
    public function render_chat_admin() {
        global $wpdb;
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        
        // Get recent messages
        $messages = $wpdb->get_results("SELECT m.*, u.display_name as sender_name
            FROM {$wpdb->prefix}rp_chat_messages m
            LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
            ORDER BY m.created_at DESC
            LIMIT 100");
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/chat.php';
    }
    
    public function render_sick_report() {
        global $wpdb;
        
        $employees = $wpdb->get_results("SELECT e.*, u.display_name 
            FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.is_active = 1
            ORDER BY u.display_name");
        
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        
        // Get upcoming shifts for selected employee (if any)
        $selected_employee = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
        $upcoming_shifts = [];
        if ($selected_employee) {
            $upcoming_shifts = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, l.name as location_name
                FROM {$wpdb->prefix}rp_schedules s
                LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
                LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
                WHERE s.employee_id = %d AND s.work_date >= %s AND s.status IN ('scheduled', 'confirmed')
                ORDER BY s.work_date ASC
                LIMIT 30",
                $selected_employee, current_time('Y-m-d')
            ));
        }
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/sick-report.php';
    }
    
    public function render_settings() {
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    public function handle_admin_actions() {
        if (!isset($_POST['rp_action']) || !current_user_can('manage_options')) return;
        
        global $wpdb;
        
        switch ($_POST['rp_action']) {
            case 'add_location':
                check_admin_referer('rp_admin_action');
                $name = sanitize_text_field($_POST['location_name']);
                $address = sanitize_textarea_field($_POST['location_address']);
                $wpdb->insert($wpdb->prefix . 'rp_locations', ['name' => $name, 'address' => $address]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-locations&msg=added'));
                exit;
                
            case 'edit_location':
                check_admin_referer('rp_admin_action');
                $id = intval($_POST['location_id']);
                $name = sanitize_text_field($_POST['location_name']);
                $address = sanitize_textarea_field($_POST['location_address']);
                $wpdb->update($wpdb->prefix . 'rp_locations', ['name' => $name, 'address' => $address], ['id' => $id]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-locations&msg=updated'));
                exit;
                
            case 'delete_location':
                check_admin_referer('rp_admin_action');
                $id = intval($_POST['location_id']);
                $wpdb->delete($wpdb->prefix . 'rp_locations', ['id' => $id]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-locations&msg=deleted'));
                exit;
                
            case 'add_shift':
                check_admin_referer('rp_admin_action');
                $wpdb->insert($wpdb->prefix . 'rp_shifts', [
                    'location_id' => intval($_POST['shift_location']),
                    'name' => sanitize_text_field($_POST['shift_name']),
                    'start_time' => sanitize_text_field($_POST['start_time']),
                    'end_time' => sanitize_text_field($_POST['end_time']),
                    'color' => sanitize_hex_color($_POST['shift_color'])
                ]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-locations&msg=shift_added'));
                exit;
                
            case 'edit_shift':
                check_admin_referer('rp_admin_action');
                $id = intval($_POST['shift_id']);
                $wpdb->update($wpdb->prefix . 'rp_shifts', [
                    'location_id' => intval($_POST['shift_location']),
                    'name' => sanitize_text_field($_POST['shift_name']),
                    'start_time' => sanitize_text_field($_POST['start_time']),
                    'end_time' => sanitize_text_field($_POST['end_time']),
                    'color' => sanitize_hex_color($_POST['shift_color'])
                ], ['id' => $id]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-locations&msg=shift_updated'));
                exit;
                
            case 'delete_shift':
                check_admin_referer('rp_admin_action');
                $id = intval($_POST['shift_id']);
                $wpdb->delete($wpdb->prefix . 'rp_shifts', ['id' => $id]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-locations&msg=shift_deleted'));
                exit;

            case 'generate_vapid_keys':
                check_admin_referer('rp_admin_action');
                if (!class_exists('\\Minishlink\\WebPush\\VAPID')) {
                    wp_redirect(admin_url('admin.php?page=rooster-planner-settings&msg=vapid_missing_lib'));
                    exit;
                }
                $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
                update_option('rooster_planner_vapid_public', $keys['publicKey']);
                update_option('rooster_planner_vapid_private', $keys['privateKey']);
                wp_redirect(admin_url('admin.php?page=rooster-planner-settings&msg=vapid_generated'));
                exit;
                
            case 'add_employee':
                check_admin_referer('rp_admin_action');
                $user_id = intval($_POST['user_id']);
                $phone = sanitize_text_field($_POST['phone']);
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                $is_fixed = isset($_POST['is_fixed']) ? 1 : 0;
                $contract_hours = isset($_POST['contract_hours']) ? intval($_POST['contract_hours']) : 0;
                $job_role = sanitize_text_field($_POST['job_role'] ?? '');
                $locations = isset($_POST['employee_locations']) ? array_map('intval', $_POST['employee_locations']) : [];
                
                $wpdb->insert($wpdb->prefix . 'rp_employees', [
                    'user_id' => $user_id,
                    'phone' => $phone,
                    'is_admin' => $is_admin,
                    'is_fixed' => $is_fixed,
                    'contract_hours' => $contract_hours,
                    'job_role' => $job_role
                ]);
                $employee_id = $wpdb->insert_id;
                
                foreach ($locations as $loc_id) {
                    $wpdb->insert($wpdb->prefix . 'rp_employee_locations', [
                        'employee_id' => $employee_id,
                        'location_id' => $loc_id
                    ]);
                }
                
                wp_redirect(admin_url('admin.php?page=rooster-planner-employees&msg=added'));
                exit;
                
            case 'edit_employee':
                check_admin_referer('rp_admin_action');
                $id = intval($_POST['employee_id']);
                $phone = sanitize_text_field($_POST['phone']);
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                $is_fixed = isset($_POST['is_fixed']) ? 1 : 0;
                $contract_hours = isset($_POST['contract_hours']) ? intval($_POST['contract_hours']) : 0;
                $job_role = sanitize_text_field($_POST['job_role'] ?? '');
                $locations = isset($_POST['employee_locations']) ? array_map('intval', $_POST['employee_locations']) : [];
                
                $wpdb->update($wpdb->prefix . 'rp_employees', [
                    'phone' => $phone,
                    'is_admin' => $is_admin,
                    'is_fixed' => $is_fixed,
                    'contract_hours' => $contract_hours,
                    'job_role' => $job_role
                ], ['id' => $id]);
                
                // Update locations
                $wpdb->delete($wpdb->prefix . 'rp_employee_locations', ['employee_id' => $id]);
                foreach ($locations as $loc_id) {
                    $wpdb->insert($wpdb->prefix . 'rp_employee_locations', [
                        'employee_id' => $id,
                        'location_id' => $loc_id
                    ]);
                }
                
                wp_redirect(admin_url('admin.php?page=rooster-planner-employees&msg=updated'));
                exit;
                
            case 'toggle_employee':
                check_admin_referer('rp_admin_action');
                $id = intval($_POST['employee_id']);
                $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM {$wpdb->prefix}rp_employees WHERE id = %d", $id));
                $wpdb->update($wpdb->prefix . 'rp_employees', ['is_active' => !$current], ['id' => $id]);
                wp_redirect(admin_url('admin.php?page=rooster-planner-employees&msg=toggled'));
                exit;
                
            case 'process_swap':
                check_admin_referer('rp_admin_action');
                $swap_id = intval($_POST['swap_id']);
                $action = sanitize_text_field($_POST['swap_action']);
                $notes = sanitize_textarea_field($_POST['admin_notes']);
                
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                $wpdb->update($wpdb->prefix . 'rp_shift_swaps', [
                    'status' => $status,
                    'admin_notes' => $notes,
                    'responded_at' => current_time('mysql')
                ], ['id' => $swap_id]);
                
                // If approved, update the schedule
                if ($action === 'approve') {
                    $swap = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rp_shift_swaps WHERE id = %d", $swap_id));
                    if ($swap && $swap->requested_employee_id) {
                        $wpdb->update($wpdb->prefix . 'rp_schedules', [
                            'employee_id' => $swap->requested_employee_id,
                            'status' => 'swapped'
                        ], ['id' => $swap->schedule_id]);
                    }
                }
                
                wp_redirect(admin_url('admin.php?page=rooster-planner-swaps&msg=swap_' . $status));
                exit;
                
            case 'process_timeoff':
                check_admin_referer('rp_admin_action');
                $timeoff_id = intval($_POST['timeoff_id']);
                $action = sanitize_text_field($_POST['timeoff_action']);
                $notes = sanitize_textarea_field($_POST['admin_notes']);
                
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                $wpdb->update($wpdb->prefix . 'rp_timeoff', [
                    'status' => $status,
                    'admin_notes' => $notes,
                    'responded_at' => current_time('mysql')
                ], ['id' => $timeoff_id]);
                
                wp_redirect(admin_url('admin.php?page=rooster-planner-swaps&msg=timeoff_' . $status));
                exit;
                
            case 'duplicate_schedule':
                check_admin_referer('rp_admin_action');
                $source_month = sanitize_text_field($_POST['source_month']);
                $target_month = sanitize_text_field($_POST['target_month']);
                $location_id = intval($_POST['location_id']);
                
                $this->duplicate_month_schedule($source_month, $target_month, $location_id);
                
                wp_redirect(admin_url('admin.php?page=rooster-planner-schedules&location=' . $location_id . '&month=' . $target_month . '&msg=duplicated'));
                exit;
        }
    }
    
    public function handle_admin_sick_report() {
        if (!current_user_can('manage_options')) {
            wp_die('Geen toegang');
        }
        
        check_admin_referer('rp_admin_action');
        
        global $wpdb;
        
        $employee_id = intval($_POST['employee_id']);
        $schedule_ids = isset($_POST['schedule_ids']) ? array_map('intval', $_POST['schedule_ids']) : [];
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $send_push = isset($_POST['send_push_notifications']) ? 1 : 0;
        
        if (empty($employee_id) || empty($schedule_ids)) {
            wp_redirect(admin_url('admin.php?page=rooster-planner-sick-report&error=no_selection'));
            exit;
        }
        
        // Get the date range from the selected schedules
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_schedules WHERE id IN (" . implode(',', array_fill(0, count($schedule_ids), '%d')) . ")",
            $schedule_ids
        ));
        
        if (empty($schedules)) {
            wp_redirect(admin_url('admin.php?page=rooster-planner-sick-report&error=invalid_schedules'));
            exit;
        }
        
        $dates = array_column($schedules, 'work_date');
        $start_date = min($dates);
        $end_date = max($dates);
        
        // Insert sick report in timeoff table
        $wpdb->insert($wpdb->prefix . 'rp_timeoff', [
            'employee_id' => $employee_id,
            'type' => 'sick',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'notes' => $notes,
            'status' => 'approved',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
        
        $timeoff_id = $wpdb->insert_id;
        
        // Cancel the selected schedules
        foreach ($schedules as $schedule) {
            $wpdb->update($wpdb->prefix . 'rp_schedules', [
                'status' => 'cancelled',
                'notes' => 'Ziekmelding: ' . $notes
            ], ['id' => $schedule->id]);
            
            // Create swap request for each cancelled schedule
            $wpdb->insert($wpdb->prefix . 'rp_shift_swaps', [
                'schedule_id' => $schedule->id,
                'requester_id' => $employee_id,
                'requested_employee_id' => 0, // Open for anyone
                'status' => 'pending',
                'notes' => 'Vervanging nodig wegens ziekmelding: ' . $notes,
                'requested_at' => current_time('mysql')
            ]);
        }
        
        // Send notifications if enabled
        if ($send_push) {
            $this->send_sick_report_notifications($employee_id, $schedules, $notes);
        }
        
        wp_redirect(admin_url('admin.php?page=rooster-planner-sick-report&msg=sick_reported'));
        exit;
    }
    
    private function send_sick_report_notifications($employee_id, $schedules, $notes) {
        global $wpdb;
        
        // Get employee name
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.id = %d",
            $employee_id
        ));
        
        if (!$employee) return;
        
        // Get all active employees with push notifications enabled
        $notify_employees = $wpdb->get_results(
            "SELECT e.*, u.ID as user_id FROM {$wpdb->prefix}rp_employees e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.is_active = 1 AND e.push_notifications = 1"
        );
        
        $shift_info = '';
        foreach ($schedules as $s) {
            $shift_info .= date('d-m-Y', strtotime($s->work_date)) . ', ';
        }
        $shift_info = rtrim($shift_info, ', ');
        
        // Create notification for each employee
        foreach ($notify_employees as $emp) {
            $wpdb->insert($wpdb->prefix . 'rp_notifications', [
                'user_id' => $emp->user_id,
                'type' => 'sick_replacement_needed',
                'title' => 'Vervanging nodig wegens ziekmelding',
                'message' => $employee->display_name . ' is ziek gemeld voor: ' . $shift_info . ($notes ? '. Notities: ' . $notes : ''),
                'is_read' => 0,
                'created_at' => current_time('mysql')
            ]);
        }
    }
    
    private function duplicate_month_schedule($source_month, $target_month, $location_id) {
        global $wpdb;
        
        $source_start = $source_month . '-01';
        $source_end = date('Y-m-t', strtotime($source_start));
        $target_start = $target_month . '-01';
        
        // Get all schedules from source month
        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_schedules 
            WHERE location_id = %d AND work_date BETWEEN %s AND %s",
            $location_id, $source_start, $source_end
        ));
        
        $day_offset = strtotime($target_start) - strtotime($source_start);
        
        foreach ($schedules as $schedule) {
            $new_date = date('Y-m-d', strtotime($schedule->work_date) + $day_offset);
            
            // Check if schedule already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rp_schedules 
                WHERE employee_id = %d AND work_date = %s AND location_id = %d",
                $schedule->employee_id, $new_date, $location_id
            ));
            
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'rp_schedules', [
                    'employee_id' => $schedule->employee_id,
                    'location_id' => $schedule->location_id,
                    'shift_id' => $schedule->shift_id,
                    'work_date' => $new_date,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'status' => 'scheduled',
                    'created_by' => get_current_user_id()
                ]);
            }
        }
    }
    
    public function render_reports() {
        global $wpdb;
        
        // Get filter parameters
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'hours';
        $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
        $employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
        
        // Get all locations and employees for filters
        $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rp_locations ORDER BY name");
        $employees = $wpdb->get_results("SELECT e.*, u.display_name FROM {$wpdb->prefix}rp_employees e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID WHERE e.is_active = 1 ORDER BY u.display_name");
        
        // Build date range
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Get report data based on type
        $report_data = [];
        
        if ($report_type === 'hours') {
            // Hours per employee report
            $where_clause = "s.work_date BETWEEN %s AND %s AND s.status != 'cancelled'";
            $params = [$start_date, $end_date];
            
            if ($location_id > 0) {
                $where_clause .= " AND s.location_id = %d";
                $params[] = $location_id;
            }
            if ($employee_id > 0) {
                $where_clause .= " AND s.employee_id = %d";
                $params[] = $employee_id;
            }
            
            // Calculate days in month for expected hours calculation
            $days_in_month = date('t', strtotime($start_date));
            $weeks_in_month = $days_in_month / 7;
            
            $report_data = $wpdb->get_results($wpdb->prepare(
                "SELECT e.id as employee_id, u.display_name as employee_name, e.contract_hours,
                    COUNT(s.id) as total_shifts,
                    SUM(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) / 60) as total_hours,
                    (e.contract_hours * {$weeks_in_month}) as expected_monthly_hours,
                    GROUP_CONCAT(DISTINCT l.name SEPARATOR ', ') as locations
                FROM {$wpdb->prefix}rp_employees e
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}rp_schedules s ON e.id = s.employee_id AND {$where_clause}
                LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
                WHERE e.is_active = 1
                GROUP BY e.id
                HAVING total_shifts > 0 OR %d = 0
                ORDER BY u.display_name",
                array_merge($params, [$employee_id])
            ));
        } elseif ($report_type === 'sickness') {
            // Sickness per employee report
            $where_clause = "t.created_at BETWEEN %s AND %s";
            $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
            
            if ($employee_id > 0) {
                $where_clause .= " AND t.employee_id = %d";
                $params[] = $employee_id;
            }
            
            $report_data = $wpdb->get_results($wpdb->prepare(
                "SELECT e.id as employee_id, u.display_name as employee_name,
                    COUNT(t.id) as sickness_reports,
                    SUM(DATEDIFF(t.end_date, t.start_date) + 1) as total_days,
                    GROUP_CONCAT(DISTINCT CONCAT(DATE_FORMAT(t.start_date, '%d-%m-%Y'), ' tot ', DATE_FORMAT(t.end_date, '%d-%m-%Y')) SEPARATOR '; ') as periods
                FROM {$wpdb->prefix}rp_employees e
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}rp_timeoff t ON e.id = t.employee_id AND t.type = 'sick' AND {$where_clause}
                WHERE e.is_active = 1
                GROUP BY e.id
                HAVING sickness_reports > 0 OR %d = 0
                ORDER BY sickness_reports DESC, u.display_name",
                array_merge($params, [$employee_id])
            ));
        }
        
        include ROOSTER_PLANNER_PLUGIN_DIR . 'templates/admin/reports.php';
    }
}
