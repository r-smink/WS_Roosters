<?php
namespace RoosterPlanner;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * JSON REST API endpoints for native/mobile clients.
 * Authentication: WordPress cookie or Application Password (recommended for apps).
 */
class Rest {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('roosterplanner/v1', '/me', [
            'methods' => 'GET',
            'callback' => [$this, 'get_me'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/locations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_locations'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/shifts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_shifts'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/schedules', [
            'methods' => 'GET',
            'callback' => [$this, 'get_schedules'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/availability', [
            'methods' => 'GET',
            'callback' => [$this, 'get_availability'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/availability', [
            'methods' => 'POST',
            'callback' => [$this, 'upsert_availability'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/notifications', [
            'methods' => 'GET',
            'callback' => [$this, 'get_notifications'],
            'permission_callback' => [$this, 'check_logged_in']
        ]);

        register_rest_route('roosterplanner/v1', '/notifications/(?P<id>\\d+)/read', [
            'methods' => 'POST',
            'callback' => [$this, 'mark_notification_read'],
            'permission_callback' => [$this, 'check_logged_in'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) { return is_numeric($param); }
                ]
            ]
        ]);
    }

    public function check_logged_in() {
        return get_current_user_id() > 0;
    }

    /**
 * Helpers
 */
    private function current_employee() {
        global $wpdb;
        $user_id = get_current_user_id();
        if (!$user_id) return null;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_employees WHERE user_id = %d AND is_active = 1",
            $user_id
        ));
    }

    private function ensure_employee_or_error() {
        $employee = $this->current_employee();
        if (!$employee) {
            return new WP_Error('rp_no_employee', 'Je hebt geen toegang tot het roostersysteem.', ['status' => 403]);
        }
        return $employee;
    }

    private function user_location_ids($employee_id) {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT location_id FROM {$wpdb->prefix}rp_employee_locations WHERE employee_id = %d",
            $employee_id
        ));
    }

    /**
     * GET /me
     */
    public function get_me(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $user = wp_get_current_user();
        $locations = $this->user_location_ids($employee->id);

        $location_rows = [];
        if (!empty($locations)) {
            $location_rows = $wpdb->get_results(
                'SELECT id, name, address FROM ' . $wpdb->prefix . 'rp_locations WHERE id IN (' . implode(',', array_map('intval', $locations)) . ')'
            );
        }

        return new WP_REST_Response([
            'user' => [
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
            ],
            'employee' => [
                'id' => intval($employee->id),
                'is_admin' => (bool)$employee->is_admin,
                'is_fixed' => (bool)$employee->is_fixed,
                'theme_preference' => $employee->theme_preference ?: 'light',
                'email_notifications' => (bool)$employee->email_notifications,
                'push_notifications' => (bool)$employee->push_notifications,
            ],
            'locations' => $location_rows,
        ]);
    }

    /**
     * GET /locations
     */
    public function get_locations(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $locations = $this->user_location_ids($employee->id);
        if (empty($locations)) return new WP_REST_Response([]);

        $rows = $wpdb->get_results(
            'SELECT id, name, address FROM ' . $wpdb->prefix . 'rp_locations WHERE id IN (' . implode(',', array_map('intval', $locations)) . ') ORDER BY name'
        );

        return new WP_REST_Response($rows);
    }

    /**
     * GET /shifts?location_id=ID
     */
    public function get_shifts(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $location_id = intval($request->get_param('location_id'));
        $allowed_locations = $this->user_location_ids($employee->id);
        if ($location_id && !in_array($location_id, $allowed_locations)) {
            return new WP_Error('rp_forbidden_location', 'Geen toegang tot deze locatie', ['status' => 403]);
        }

        $query = "SELECT id, location_id, name, start_time, end_time, color FROM {$wpdb->prefix}rp_shifts WHERE is_active = 1";
        $params = [];
        if ($location_id) {
            $query .= " AND location_id = %d";
            $params[] = $location_id;
        } else {
            if (!empty($allowed_locations)) {
                $query .= ' AND location_id IN (' . implode(',', array_map('intval', $allowed_locations)) . ')';
            }
        }

        $query .= ' ORDER BY start_time';
        $shifts = $params ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);

        return new WP_REST_Response($shifts);
    }

    /**
     * GET /schedules?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     */
    public function get_schedules(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $start = $request->get_param('start_date') ?: current_time('Y-m-01');
        $end = $request->get_param('end_date') ?: date('Y-m-t', strtotime($start));

        $start = sanitize_text_field($start);
        $end = sanitize_text_field($end);

        $schedules = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.work_date, s.start_time, s.end_time, s.status, s.notes, s.is_swappable,
                    s.actual_start_time, s.actual_end_time, s.break_minutes,
                    sh.id as shift_id, sh.name as shift_name, sh.color,
                    l.id as location_id, l.name as location_name
             FROM {$wpdb->prefix}rp_schedules s
             LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
             LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
             WHERE s.employee_id = %d AND s.status != 'cancelled' AND s.work_date BETWEEN %s AND %s
             ORDER BY s.work_date ASC",
            $employee->id,
            $start,
            $end
        ));

        return new WP_REST_Response($schedules);
    }

    /**
     * GET /availability?month=YYYY-MM&location_id=ID
     */
    public function get_availability(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $month = sanitize_text_field($request->get_param('month') ?: date('Y-m', strtotime('+1 month')));
        $location_id = intval($request->get_param('location_id'));

        $allowed_locations = $this->user_location_ids($employee->id);
        if ($location_id && !in_array($location_id, $allowed_locations)) {
            return new WP_Error('rp_forbidden_location', 'Geen toegang tot deze locatie', ['status' => 403]);
        }
        if (!$location_id && !empty($allowed_locations)) {
            $location_id = $allowed_locations[0];
        }

        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT id, work_date, is_available, shift_preference, custom_start, custom_end, notes
             FROM {$wpdb->prefix}rp_availability
             WHERE employee_id = %d AND location_id = %d AND work_date LIKE %s
             ORDER BY work_date ASC",
            $employee->id,
            $location_id,
            $month . '%'
        ));

        return new WP_REST_Response([
            'location_id' => $location_id,
            'month' => $month,
            'items' => $availability
        ]);
    }

    /**
     * POST /availability
     * Body: {location_id: int, entries: [{date: Y-m-d, is_available: bool, shift_preference?: int, custom_start?: string, custom_end?: string, notes?: string}]}
     */
    public function upsert_availability(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $body = $request->get_json_params();
        $location_id = intval($body['location_id'] ?? 0);
        $entries = $body['entries'] ?? [];

        if (!$location_id) {
            return new WP_Error('rp_missing_location', 'location_id is verplicht', ['status' => 400]);
        }

        $allowed_locations = $this->user_location_ids($employee->id);
        if (!in_array($location_id, $allowed_locations)) {
            return new WP_Error('rp_forbidden_location', 'Geen toegang tot deze locatie', ['status' => 403]);
        }

        if (!is_array($entries) || empty($entries)) {
            return new WP_Error('rp_no_entries', 'Geen availability entries ontvangen', ['status' => 400]);
        }

        $processed = 0;
        foreach ($entries as $entry) {
            $date = sanitize_text_field($entry['date'] ?? '');
            if (!$date) continue;

            $data = [
                'employee_id' => $employee->id,
                'location_id' => $location_id,
                'work_date' => $date,
                'is_available' => isset($entry['is_available']) ? intval($entry['is_available']) : 1,
                'notes' => isset($entry['notes']) ? sanitize_text_field($entry['notes']) : '',
            ];

            if (isset($entry['shift_preference'])) {
                $data['shift_preference'] = intval($entry['shift_preference']);
            }
            if (!empty($entry['custom_start'])) {
                $data['custom_start'] = sanitize_text_field($entry['custom_start']);
            }
            if (!empty($entry['custom_end'])) {
                $data['custom_end'] = sanitize_text_field($entry['custom_end']);
            }

            $wpdb->replace($wpdb->prefix . 'rp_availability', $data);
            $processed++;
        }

        return new WP_REST_Response(['updated' => $processed]);
    }

    /**
     * GET /notifications?limit=50&unread_only=1
     */
    public function get_notifications(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $limit = min(100, max(1, intval($request->get_param('limit') ?: 50)));
        $unread_only = intval($request->get_param('unread_only')) === 1;

        $sql = "SELECT id, type, title, message, is_read, created_at
                FROM {$wpdb->prefix}rp_notifications
                WHERE user_id = %d";
        $params = [$employee->user_id];
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        $notifications = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        return new WP_REST_Response($notifications);
    }

    /**
     * POST /notifications/{id}/read
     */
    public function mark_notification_read(WP_REST_Request $request) {
        global $wpdb;
        $employee = $this->ensure_employee_or_error();
        if (is_wp_error($employee)) return $employee;

        $id = intval($request['id']);
        $updated = $wpdb->update(
            $wpdb->prefix . 'rp_notifications',
            ['is_read' => 1],
            ['id' => $id, 'user_id' => $employee->user_id]
        );

        if ($updated === false) {
            return new WP_Error('rp_update_failed', 'Kon notificatie niet bijwerken', ['status' => 500]);
        }

        return new WP_REST_Response(['updated' => (int)$updated]);
    }
}
