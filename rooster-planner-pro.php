<?php
/**
 * Plugin Name: RoosterPlanner Pro
 * Description: Compleet roosterplanningssysteem voor medewerkers met admin portal en mobile web app
 * Version: 1.0.0
 * Author: RoosterPlanner
 * Text Domain: roosterplanner
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ROOSTER_PLANNER_VERSION', '1.0.0');
define('ROOSTER_PLANNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROOSTER_PLANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'RoosterPlanner\\';
    $base_dir = ROOSTER_PLANNER_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Database installation
register_activation_hook(__FILE__, 'rooster_planner_activate');
function rooster_planner_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Locations table
    $sql_locations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_locations (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        address text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Shifts table
    $sql_shifts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_shifts (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        location_id bigint(20) NOT NULL,
        name varchar(100) NOT NULL,
        start_time time NOT NULL,
        end_time time NOT NULL,
        color varchar(7) DEFAULT '#4F46E5',
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY location_id (location_id)
    ) $charset_collate;";

    // Employees table (extends WordPress users)
    $sql_employees = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_employees (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        phone varchar(20),
        is_admin tinyint(1) DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    // Employee locations (many-to-many)
    $sql_employee_locations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_employee_locations (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        employee_id bigint(20) NOT NULL,
        location_id bigint(20) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY employee_location (employee_id, location_id)
    ) $charset_collate;";

    // Schedules table
    $sql_schedules = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_schedules (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        employee_id bigint(20) NOT NULL,
        location_id bigint(20) NOT NULL,
        shift_id bigint(20) NOT NULL,
        work_date date NOT NULL,
        start_time time,
        end_time time,
        status enum('scheduled','confirmed','completed','cancelled','swapped') DEFAULT 'scheduled',
        notes text,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY employee_date (employee_id, work_date),
        KEY location_date (location_id, work_date)
    ) $charset_collate;";

    // Availability table
    $sql_availability = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_availability (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        employee_id bigint(20) NOT NULL,
        location_id bigint(20) NOT NULL,
        work_date date NOT NULL,
        is_available tinyint(1) DEFAULT 1,
        shift_preference bigint(20) DEFAULT NULL,
        notes varchar(255),
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY employee_location_date (employee_id, location_id, work_date),
        KEY submitted_at (submitted_at)
    ) $charset_collate;";

    // Shift swaps table
    $sql_swaps = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_shift_swaps (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        schedule_id bigint(20) NOT NULL,
        requester_id bigint(20) NOT NULL,
        requested_employee_id bigint(20) DEFAULT NULL,
        status enum('pending','approved','rejected','completed') DEFAULT 'pending',
        reason text,
        admin_notes text,
        requested_at datetime DEFAULT CURRENT_TIMESTAMP,
        responded_at datetime,
        PRIMARY KEY (id),
        KEY schedule_id (schedule_id),
        KEY requester_id (requester_id)
    ) $charset_collate;";

    // Time off requests table
    $sql_timeoff = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_timeoff (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        employee_id bigint(20) NOT NULL,
        start_date date NOT NULL,
        end_date date NOT NULL,
        type enum('vacation','sick','personal','other') DEFAULT 'vacation',
        status enum('pending','approved','rejected') DEFAULT 'pending',
        reason text,
        admin_notes text,
        requested_at datetime DEFAULT CURRENT_TIMESTAMP,
        responded_at datetime,
        PRIMARY KEY (id),
        KEY employee_id (employee_id)
    ) $charset_collate;";

    // Chat messages table
    $sql_chat = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_chat_messages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        sender_id bigint(20) NOT NULL,
        message text NOT NULL,
        is_announcement tinyint(1) DEFAULT 0,
        location_id bigint(20) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Notifications table
    $sql_notifications = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_notifications (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(50) NOT NULL,
        title varchar(255) NOT NULL,
        message text,
        is_read tinyint(1) DEFAULT 0,
        related_id bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id, is_read)
    ) $charset_collate;";

    // Fixed schedules template table
    $sql_fixed_schedules = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_fixed_schedules (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        employee_id bigint(20) NOT NULL,
        location_id bigint(20) NOT NULL,
        shift_id bigint(20) NOT NULL,
        day_of_week tinyint(1) NOT NULL COMMENT '0=Sunday, 1=Monday, etc.',
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY employee_location_day (employee_id, location_id, day_of_week)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_locations);
    dbDelta($sql_shifts);
    dbDelta($sql_employees);
    dbDelta($sql_employee_locations);
    dbDelta($sql_schedules);
    dbDelta($sql_availability);
    dbDelta($sql_swaps);
    dbDelta($sql_timeoff);
    dbDelta($sql_chat);
    dbDelta($sql_notifications);
    dbDelta($sql_fixed_schedules);

    // Insert default locations
    $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}rp_locations (id, name) VALUES 
        (1, 'Serva'),
        (2, 'Isselt')");

    // Insert default shifts
    $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}rp_shifts (location_id, name, start_time, end_time, color) VALUES
        (1, 'Kassa openen', '05:50:00', '12:00:00', '#4F46E5'),
        (1, 'Tussen dienst', '10:00:00', '16:00:00', '#10B981'),
        (1, 'Bakery openen', '05:50:00', '12:00:00', '#F59E0B'),
        (1, 'Bakery tussendienst', '11:00:00', '19:00:00', '#EF4444'),
        (2, 'Kassa openen', '05:50:00', '12:00:00', '#4F46E5'),
        (2, 'Tussen dienst', '10:00:00', '16:00:00', '#10B981'),
        (2, 'Bakery openen', '05:50:00', '12:00:00', '#F59E0B'),
        (2, 'Bakery tussendienst', '11:00:00', '19:00:00', '#EF4444')");

    add_option('rooster_planner_version', ROOSTER_PLANNER_VERSION);
}

// Initialize plugin
add_action('plugins_loaded', 'rooster_planner_init');
function rooster_planner_init() {
    load_plugin_textdomain('roosterplanner', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    
    // Include required files
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-admin.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-frontend.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-ajax.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-notifications.php';
    
    // Initialize classes
    new RoosterPlanner\Admin();
    new RoosterPlanner\Frontend();
    new RoosterPlanner\Ajax();
    new RoosterPlanner\Notifications();
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', 'rooster_planner_admin_assets');
function rooster_planner_admin_assets($hook) {
    if (strpos($hook, 'rooster-planner') === false) return;
    
    wp_enqueue_style('rooster-planner-admin-css', ROOSTER_PLANNER_PLUGIN_URL . 'assets/css/admin.css', [], ROOSTER_PLANNER_VERSION);
    wp_enqueue_script('rooster-planner-admin-js', ROOSTER_PLANNER_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], ROOSTER_PLANNER_VERSION, true);
    wp_localize_script('rooster-planner-admin-js', 'rpAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rp_nonce')
    ]);
}

// Enqueue frontend assets
add_action('wp_enqueue_scripts', 'rooster_planner_frontend_assets');
function rooster_planner_frontend_assets() {
    wp_enqueue_style('rooster-planner-css', ROOSTER_PLANNER_PLUGIN_URL . 'assets/css/frontend.css', [], ROOSTER_PLANNER_VERSION);
    wp_enqueue_script('rooster-planner-js', ROOSTER_PLANNER_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], ROOSTER_PLANNER_VERSION, true);
    wp_localize_script('rooster-planner-js', 'rpAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rp_nonce'),
        'isLoggedIn' => is_user_logged_in()
    ]);
}
