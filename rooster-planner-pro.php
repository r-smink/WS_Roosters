<?php
/**
 * Plugin Name: RoosterPlanner Pro
 * Description: Compleet roosterplanningssysteem voor medewerkers met admin portal en mobile web app
 * Version: 1.4.6
 * Author: NextBuzz
 * Text Domain: roosterplanner
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ROOSTER_PLANNER_VERSION', '1.4.6');
define('ROOSTER_PLANNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROOSTER_PLANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoload (for web-push etc.)
$composer_autoload = ROOSTER_PLANNER_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

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
        is_fixed tinyint(1) DEFAULT 0 COMMENT 'Vaste medewerker - geen beschikbaarheid nodig alleen voor vrij vragen',
        theme_preference varchar(20) DEFAULT 'light' COMMENT 'light of dark theme voorkeur',
        email_notifications tinyint(1) DEFAULT 1 COMMENT 'Email notificaties ingeschakeld',
        push_notifications tinyint(1) DEFAULT 1 COMMENT 'Push notificaties ingeschakeld',
        is_active tinyint(1) DEFAULT 1,
        ical_token varchar(64) DEFAULT NULL COMMENT 'Token for persistent iCal feed',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY ical_token (ical_token)
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
        actual_start_time time DEFAULT NULL,
        actual_end_time time DEFAULT NULL,
        break_minutes int(11) DEFAULT 0,
        status enum('scheduled','confirmed','completed','cancelled','swapped') DEFAULT 'scheduled',
        is_swappable tinyint(1) DEFAULT 0 COMMENT 'Medewerker heeft dienst als ruilbaar gemarkeerd',
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
        custom_start time DEFAULT NULL,
        custom_end time DEFAULT NULL,
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

    // Push subscriptions table (Web Push)
    $sql_push_subscriptions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_push_subscriptions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        endpoint text NOT NULL,
        p256dh varchar(255) DEFAULT NULL,
        auth varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
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
    dbDelta($sql_push_subscriptions);

    // Create frontend pages with shortcodes (no demo data)
    rooster_planner_create_pages();

    add_option('rooster_planner_version', ROOSTER_PLANNER_VERSION);
    add_option('rooster_planner_demo_data_imported', false);
    
    // Run database upgrades
    rooster_planner_run_upgrades();
}

/**
 * Run database upgrades for existing installations
 */
function rooster_planner_run_upgrades() {
    global $wpdb;
    $installed_version = get_option('rooster_planner_version', '1.0.0');
    
    // Add worked hours columns to schedules table (version 1.3.0+)
    if (version_compare($installed_version, '1.3.0', '<')) {
        // Check if columns exist
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_schedules");
        
        if (!in_array('actual_start_time', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_schedules ADD COLUMN actual_start_time time DEFAULT NULL AFTER end_time");
        }
        if (!in_array('actual_end_time', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_schedules ADD COLUMN actual_end_time time DEFAULT NULL AFTER actual_start_time");
        }
        if (!in_array('break_minutes', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_schedules ADD COLUMN break_minutes int(11) DEFAULT 0 AFTER actual_end_time");
        }
    }
    
    // Add is_fixed column to employees table (version 1.3.1+)
    if (version_compare($installed_version, '1.3.1', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_employees");
        
        if (!in_array('is_fixed', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN is_fixed tinyint(1) DEFAULT 0 AFTER is_admin");
        }
    }
    
    // Add theme_preference to employees table (version 1.3.2+)
    if (version_compare($installed_version, '1.3.2', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_employees");
        
        if (!in_array('theme_preference', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN theme_preference varchar(20) DEFAULT 'light' AFTER is_fixed");
        }
    }
    
    // Add is_swappable to schedules table (version 1.3.2+)
    if (version_compare($installed_version, '1.3.2', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_schedules");
        
        if (!in_array('is_swappable', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_schedules ADD COLUMN is_swappable tinyint(1) DEFAULT 0 AFTER status");
        }
    }
    
    // Add custom_start and custom_end to availability table (version 1.3.5+)
    if (version_compare($installed_version, '1.3.5', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_availability");
        
        if (!in_array('custom_start', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_availability ADD COLUMN custom_start time DEFAULT NULL AFTER shift_preference");
        }
        if (!in_array('custom_end', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_availability ADD COLUMN custom_end time DEFAULT NULL AFTER custom_start");
        }
    }
    
    // Add email_notifications and push_notifications to employees table (version 1.3.6+)
    if (version_compare($installed_version, '1.3.6', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_employees");
        
        if (!in_array('email_notifications', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN email_notifications tinyint(1) DEFAULT 1");
        }
        if (!in_array('push_notifications', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN push_notifications tinyint(1) DEFAULT 1");
        }
    }
    
    // Add contract_hours and job_role to employees table (version 1.3.8+)
    if (version_compare($installed_version, '1.3.8', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_employees");
        
        if (!in_array('contract_hours', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN contract_hours int(11) DEFAULT 0");
        }
        if (!in_array('job_role', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN job_role varchar(100) DEFAULT NULL");
        }
    }
    
    // Create final_schedules table (version 1.3.9+)
    if (version_compare($installed_version, '1.3.9', '<')) {
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_final_schedules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            location_id bigint(20) NOT NULL,
            month varchar(7) NOT NULL,
            finalized_at datetime DEFAULT CURRENT_TIMESTAMP,
            finalized_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_location_month (location_id, month)
        ) {$charset_collate};");
    }
    
    // Add ical_token to employees table (version 1.4.0+)
    if (version_compare($installed_version, '1.4.0', '<')) {
        $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_employees");
        
        if (!in_array('ical_token', $columns)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN ical_token varchar(64) DEFAULT NULL");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD KEY ical_token (ical_token)");
        }
    }
    
    // Update version
    update_option('rooster_planner_version', ROOSTER_PLANNER_VERSION);
    
    // Failsafe: Always ensure required columns exist regardless of version
    $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}rp_employees");
    
    if (!in_array('contract_hours', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN contract_hours int(11) DEFAULT 0");
    }
    if (!in_array('job_role', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN job_role varchar(100) DEFAULT NULL");
    }
    if (!in_array('ical_token', $columns)) {
        $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD COLUMN ical_token varchar(64) DEFAULT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}rp_employees ADD KEY ical_token (ical_token)");
    }
}

/**
 * Create custom WordPress roles for Rooster Planner
 */
function rooster_planner_create_roles() {
    // Planner role - can manage schedules, employees, locations
    add_role('planner', 'Planner', [
        'read' => true,
        'manage_rooster_planner' => true,
        'rp_view_schedules' => true,
        'rp_edit_schedules' => true,
        'rp_view_employees' => true,
        'rp_edit_employees' => true,
        'rp_view_locations' => true,
        'rp_edit_locations' => true,
        'rp_view_reports' => true,
        'rp_view_swaps' => true,
        'rp_process_swaps' => true,
        'rp_view_timeoff' => true,
        'rp_process_timeoff' => true,
        'rp_finalize_months' => true,
    ]);
    
    // Medewerker role - can only view their own schedule and submit availability
    add_role('medewerker', 'Medewerker', [
        'read' => true,
        'rp_view_own_schedule' => true,
        'rp_submit_availability' => true,
        'rp_request_swap' => true,
        'rp_request_timeoff' => true,
        'rp_view_chat' => true,
        'rp_send_chat' => true,
    ]);
    
    // Also add capabilities to administrator
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_rooster_planner');
        $admin->add_cap('rp_view_schedules');
        $admin->add_cap('rp_edit_schedules');
        $admin->add_cap('rp_view_employees');
        $admin->add_cap('rp_edit_employees');
        $admin->add_cap('rp_view_locations');
        $admin->add_cap('rp_edit_locations');
        $admin->add_cap('rp_view_reports');
        $admin->add_cap('rp_view_swaps');
        $admin->add_cap('rp_process_swaps');
        $admin->add_cap('rp_view_timeoff');
        $admin->add_cap('rp_process_timeoff');
        $admin->add_cap('rp_finalize_months');
        $admin->add_cap('rp_view_own_schedule');
        $admin->add_cap('rp_submit_availability');
        $admin->add_cap('rp_request_swap');
        $admin->add_cap('rp_request_timeoff');
        $admin->add_cap('rp_view_chat');
        $admin->add_cap('rp_send_chat');
    }
}

/**
 * Remove custom roles on plugin deactivation
 */
register_deactivation_hook(__FILE__, 'rooster_planner_deactivate');
function rooster_planner_deactivate() {
    remove_role('planner');
    remove_role('medewerker');
}

/**
 * Create frontend pages with shortcodes embedded
 */
function rooster_planner_create_pages() {
    $pages = [
        'medewerker-login' => [
            'title' => 'Medewerker Login',
            'content' => '[roosterplanner_login]'
        ],
        'medewerker-dashboard' => [
            'title' => 'Mijn Dashboard',
            'content' => '[roosterplanner_dashboard]'
        ],
        'medewerker-rooster' => [
            'title' => 'Mijn Rooster',
            'content' => '[roosterplanner_rooster]'
        ],
        'medewerker-beschikbaarheid' => [
            'title' => 'Mijn Beschikbaarheid',
            'content' => '[roosterplanner_beschikbaarheid]'
        ],
        'medewerker-ruilen' => [
            'title' => 'Shifts Ruilen',
            'content' => '[roosterplanner_ruilenen]'
        ],
        'medewerker-chat' => [
            'title' => 'Team Chat',
            'content' => '[roosterplanner_chat]'
        ],
        'medewerker-ziekmelden' => [
            'title' => 'Ziekmelden',
            'content' => '[roosterplanner_ziekmelden]'
        ],
        'medewerker-verlof' => [
            'title' => 'Verlof Aanvragen',
            'content' => '[roosterplanner_verlof]'
        ],
        'medewerker-profiel' => [
            'title' => 'Mijn Profiel',
            'content' => '[roosterplanner_profielformulier]'
        ],
        'medewerker-berichten' => [
            'title' => 'Mijn Berichten',
            'content' => '[roosterplanner_berichten]'
        ]
    ];
    
    $created_pages = [];
    
    foreach ($pages as $slug => $page_data) {
        // Check if page already exists
        $existing_page = get_page_by_path($slug);
        
        if (!$existing_page) {
            $page_id = wp_insert_post([
                'post_title' => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug,
                'comment_status' => 'closed'
            ]);
            
            if ($page_id) {
                $created_pages[$slug] = $page_id;
            }
        }
    }
    
    // Store created pages in option for reference
    if (!empty($created_pages)) {
        update_option('rooster_planner_pages', $created_pages);
    }
}

// Initialize plugin
add_action('plugins_loaded', 'rooster_planner_init');
function rooster_planner_init() {
    load_plugin_textdomain('roosterplanner', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    
    // Run database upgrades for existing installations
    rooster_planner_run_upgrades();
    
    // Create custom roles
    rooster_planner_create_roles();
    
    // Register settings
    add_action('admin_init', 'rooster_planner_register_settings');
    
    // Include required files
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-admin.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-frontend.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-ajax.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-notifications.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-rest.php';
    require_once ROOSTER_PLANNER_PLUGIN_DIR . 'includes/class-ical-export.php';
    
    // Initialize classes
    new RoosterPlanner\Admin();
    new RoosterPlanner\Frontend();
    new RoosterPlanner\Ajax();
    new RoosterPlanner\Notifications();
    new RoosterPlanner\Rest();
    new RoosterPlanner\ICalExport();
}

function rooster_planner_register_settings() {
    register_setting('rooster_planner_options', 'rooster_planner_deadline_day');
    register_setting('rooster_planner_options', 'rooster_planner_reminder_day');
    register_setting('rooster_planner_options', 'rooster_planner_email_notifications');
    register_setting('rooster_planner_options', 'rooster_planner_push_notifications');
    register_setting('rooster_planner_options', 'rooster_planner_vapid_public');
    register_setting('rooster_planner_options', 'rooster_planner_vapid_private');
    register_setting('rooster_planner_options', 'rooster_planner_enable_worked_hours');
    register_setting('rooster_planner_options', 'rooster_planner_enable_self_sick_report');
    register_setting('rooster_planner_options', 'rooster_planner_enable_dark_theme');
    register_setting('rooster_planner_options', 'rooster_planner_custom_css');
    
    // PWA Settings
    register_setting('rooster_planner_pwa_options', 'rooster_planner_pwa_app_name');
    register_setting('rooster_planner_pwa_options', 'rooster_planner_pwa_app_short_name');
    register_setting('rooster_planner_pwa_options', 'rooster_planner_pwa_theme_color');
    register_setting('rooster_planner_pwa_options', 'rooster_planner_pwa_background_color');
    register_setting('rooster_planner_pwa_options', 'rooster_planner_pwa_icon_192');
    register_setting('rooster_planner_pwa_options', 'rooster_planner_pwa_icon_512');
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

// Add PWA manifest and service worker for frontend pages
add_action('wp_head', 'rooster_planner_pwa_head');
function rooster_planner_pwa_head() {
    // Only add PWA tags on our plugin pages
    if (!is_page()) return;
    
    $page_slug = get_post_field('post_name', get_the_ID());
    $plugin_pages = ['medewerker-login', 'medewerker-dashboard', 'medewerker-rooster', 
                     'medewerker-beschikbaarheid', 'medewerker-ruilen', 'medewerker-chat',
                     'medewerker-ziekmelden', 'medewerker-verlof', 'medewerker-profiel', 'medewerker-berichten'];
    
    if (!in_array($page_slug, $plugin_pages)) return;
    
    // Get PWA settings
    $app_name = get_option('rooster_planner_pwa_app_name', 'Rooster Planner');
    $theme_color = get_option('rooster_planner_pwa_theme_color', '#4F46E5');
    $icon_192 = get_option('rooster_planner_pwa_icon_192') ?: ROOSTER_PLANNER_PLUGIN_URL . 'assets/images/icon-192x192.png';
    
    echo '<link rel="manifest" href="' . ROOSTER_PLANNER_PLUGIN_URL . 'assets/manifest.json">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' . "\n";
    echo '<meta name="theme-color" content="' . esc_attr($theme_color) . '">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr($app_name) . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($icon_192) . '">' . "\n";
    
    // Load Google Font
    echo '<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">' . "\n";
    
    // Output custom CSS
    $custom_css = get_option('rooster_planner_custom_css', '');
    if (!empty($custom_css)) {
        echo '<style>' . wp_strip_all_tags($custom_css) . '</style>' . "\n";
    }
}

// Login redirect to medewerker-dashboard
add_action('wp_login', 'rooster_planner_login_redirect', 10, 2);
function rooster_planner_login_redirect($user_login, $user) {
    // Check if user is an employee
    global $wpdb;
    $employee = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}rp_employees WHERE user_id = %d AND is_active = 1",
        $user->ID
    ));
    
    if ($employee) {
        // Get dashboard page URL
        $dashboard_page = get_page_by_path('medewerker-dashboard');
        if ($dashboard_page) {
            wp_redirect(get_permalink($dashboard_page->ID));
            exit;
        }
    }
}
add_action('wp_enqueue_scripts', 'rooster_planner_frontend_assets');
function rooster_planner_frontend_assets() {
    wp_enqueue_style('rooster-planner-css', ROOSTER_PLANNER_PLUGIN_URL . 'assets/css/frontend.css', [], ROOSTER_PLANNER_VERSION);
    wp_enqueue_script('rooster-planner-js', ROOSTER_PLANNER_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], ROOSTER_PLANNER_VERSION, true);
    wp_localize_script('rooster-planner-js', 'rpAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => home_url('/wp-json/'),
        'nonce' => wp_create_nonce('rp_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'currentUserId' => get_current_user_id(),
        'pluginUrl' => ROOSTER_PLANNER_PLUGIN_URL
    ]);
}

/**
 * Import Demo Data (Locations and Shifts)
 * Called via AJAX from admin
 */
function rooster_planner_import_demo_data() {
    global $wpdb;
    
    // Check if already imported
    if (get_option('rooster_planner_demo_data_imported')) {
        return ['success' => false, 'message' => 'Demo data is al geïmporteerd'];
    }
    
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
    
    update_option('rooster_planner_demo_data_imported', true);
    
    return ['success' => true, 'message' => 'Demo data succesvol geïmporteerd'];
}

/**
 * Import Employees from CSV/Excel
 * Expected columns: voornaam, achternaam, email, telefoon, locaties, is_admin
 * locaties: comma-separated location names
 */
function rooster_planner_import_employees_csv($csv_data) {
    global $wpdb;
    $results = ['imported' => 0, 'errors' => [], 'existing' => 0];
    
    $lines = explode("\n", $csv_data);
    $headers = str_getcsv(array_shift($lines));
    
    foreach ($lines as $line_num => $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv($line);
        if (count($data) < 4) continue;
        
        $row = array_combine($headers, $data);
        
        // Check required fields
        if (empty($row['email']) || empty($row['voornaam']) || empty($row['achternaam'])) {
            $results['errors'][] = "Regel " . ($line_num + 2) . ": Ontbrekende verplichte velden";
            continue;
        }
        
        // Check if user already exists
        $existing_user = get_user_by('email', $row['email']);
        if ($existing_user) {
            $results['existing']++;
            continue;
        }
        
        // Create WordPress user
        $username = sanitize_user($row['voornaam'] . '.' . $row['achternaam']);
        $counter = 1;
        $original_username = $username;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        $password = wp_generate_password(12, false);
        
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => sanitize_email($row['email']),
            'first_name' => sanitize_text_field($row['voornaam']),
            'last_name' => sanitize_text_field($row['achternaam']),
            'display_name' => sanitize_text_field($row['voornaam'] . ' ' . $row['achternaam']),
            'role' => 'subscriber'
        ]);
        
        if (is_wp_error($user_id)) {
            $results['errors'][] = "Regel " . ($line_num + 2) . ": " . $user_id->get_error_message();
            continue;
        }
        
        // Create employee record
        $phone = !empty($row['telefoon']) ? sanitize_text_field($row['telefoon']) : '';
        $is_admin = !empty($row['is_admin']) && in_array(strtolower($row['is_admin']), ['ja', 'yes', '1', 'true']) ? 1 : 0;
        
        $wpdb->insert(
            $wpdb->prefix . 'rp_employees',
            [
                'user_id' => $user_id,
                'phone' => $phone,
                'is_admin' => $is_admin,
                'is_active' => 1
            ],
            ['%d', '%s', '%d', '%d']
        );
        
        $employee_id = $wpdb->insert_id;
        
        // Assign locations
        if (!empty($row['locaties']) && $employee_id) {
            $location_names = array_map('trim', explode(',', $row['locaties']));
            foreach ($location_names as $loc_name) {
                $location = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}rp_locations WHERE name = %s",
                    $loc_name
                ));
                
                if ($location) {
                    $wpdb->insert(
                        $wpdb->prefix . 'rp_employee_locations',
                        [
                            'employee_id' => $employee_id,
                            'location_id' => $location->id
                        ],
                        ['%d', '%d']
                    );
                }
            }
        }
        
        // Send welcome email with credentials
        $to = $row['email'];
        $subject = 'Welkom bij RoosterPlanner Pro';
        $message = "Hallo " . $row['voornaam'] . ",\n\n";
        $message .= "Er is een account voor je aangemaakt in RoosterPlanner Pro.\n\n";
        $message .= "Je inloggegevens:\n";
        $message .= "Gebruikersnaam: " . $username . "\n";
        $message .= "Wachtwoord: " . $password . "\n\n";
        $message .= "Log in op: " . get_permalink(get_page_by_path('medewerker-login')) . "\n\n";
        $message .= "Verander je wachtwoord na de eerste keer inloggen voor de veiligheid.\n\n";
        $message .= "Met vriendelijke groet,\n";
        $message .= get_bloginfo('name');
        
        wp_mail($to, $subject, $message);
        
        $results['imported']++;
    }
    
    return $results;
}

/**
 * Import Shifts from CSV/Excel
 * Expected columns: locatie, naam, start_tijd, eind_tijd, kleur
 */
function rooster_planner_import_shifts_csv($csv_data) {
    global $wpdb;
    $results = ['imported' => 0, 'errors' => [], 'existing' => 0];
    
    $lines = explode("\n", $csv_data);
    $headers = str_getcsv(array_shift($lines));
    
    foreach ($lines as $line_num => $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv($line);
        if (count($data) < 4) continue;
        
        $row = array_combine($headers, $data);
        
        // Check required fields
        if (empty($row['locatie']) || empty($row['naam']) || empty($row['start_tijd']) || empty($row['eind_tijd'])) {
            $results['errors'][] = "Regel " . ($line_num + 2) . ": Ontbrekende verplichte velden";
            continue;
        }
        
        // Get location ID
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rp_locations WHERE name = %s",
            trim($row['locatie'])
        ));
        
        if (!$location) {
            $results['errors'][] = "Regel " . ($line_num + 2) . ": Locatie '" . $row['locatie'] . "' niet gevonden";
            continue;
        }
        
        // Check if shift already exists for this location
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_shifts 
            WHERE location_id = %d AND name = %s AND start_time = %s AND end_time = %s",
            $location->id,
            trim($row['naam']),
            trim($row['start_tijd']),
            trim($row['eind_tijd'])
        ));
        
        if ($existing) {
            $results['existing']++;
            continue;
        }
        
        // Validate time format
        $start_time = trim($row['start_tijd']);
        $end_time = trim($row['eind_tijd']);
        
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start_time) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $end_time)) {
            $results['errors'][] = "Regel " . ($line_num + 2) . ": Ongeldig tijdformaat (gebruik HH:MM of HH:MM:SS)";
            continue;
        }
        
        // Add seconds if not present
        if (strlen($start_time) == 5) $start_time .= ':00';
        if (strlen($end_time) == 5) $end_time .= ':00';
        
        // Default colors based on shift name
        $color = !empty($row['kleur']) ? sanitize_hex_color($row['kleur']) : rooster_planner_get_default_color($row['naam']);
        
        $wpdb->insert(
            $wpdb->prefix . 'rp_shifts',
            [
                'location_id' => $location->id,
                'name' => sanitize_text_field(trim($row['naam'])),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'color' => $color,
                'is_active' => 1
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d']
        );
        
        if ($wpdb->insert_id) {
            $results['imported']++;
        } else {
            $results['errors'][] = "Regel " . ($line_num + 2) . ": Database fout bij invoegen";
        }
    }
    
    return $results;
}

/**
 * Get default color for shift type
 */
function rooster_planner_get_default_color($shift_name) {
    $name_lower = strtolower($shift_name);
    
    if (strpos($name_lower, 'kassa') !== false) {
        return '#4F46E5'; // Indigo
    } elseif (strpos($name_lower, 'bakery') !== false) {
        return '#F59E0B'; // Amber
    } elseif (strpos($name_lower, 'tussen') !== false || strpos($name_lower, 'tussendienst') !== false) {
        return '#10B981'; // Emerald
    } elseif (strpos($name_lower, 'sluit') !== false || strpos($name_lower, 'afsluit') !== false) {
        return '#EF4444'; // Red
    } else {
        return '#4F46E5'; // Default indigo
    }
}

/**
 * Bulk update employees
 */
function rooster_planner_bulk_update_employees($updates) {
    global $wpdb;
    $results = ['updated' => 0, 'errors' => []];
    
    foreach ($updates as $employee_id => $data) {
        $employee_id = intval($employee_id);
        if (!$employee_id) continue;
        
        $set = [];
        $formats = [];
        $values = [];
        
        if (isset($data['is_active'])) {
            $set[] = 'is_active = %d';
            $formats[] = '%d';
            $values[] = intval($data['is_active']);
        }
        
        if (isset($data['is_admin'])) {
            $set[] = 'is_admin = %d';
            $formats[] = '%d';
            $values[] = intval($data['is_admin']);
        }
        
        if (isset($data['phone'])) {
            $set[] = 'phone = %s';
            $formats[] = '%s';
            $values[] = sanitize_text_field($data['phone']);
        }
        
        if (empty($set)) continue;
        
        $values[] = $employee_id;
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}rp_employees SET " . implode(', ', $set) . " WHERE id = %d",
            $values
        ));
        
        if ($result !== false) {
            $results['updated']++;
        } else {
            $results['errors'][] = "Fout bij updaten medewerker ID " . $employee_id;
        }
    }
    
    return $results;
}

/**
 * Bulk update shifts
 */
function rooster_planner_bulk_update_shifts($updates) {
    global $wpdb;
    $results = ['updated' => 0, 'errors' => []];
    
    foreach ($updates as $shift_id => $data) {
        $shift_id = intval($shift_id);
        if (!$shift_id) continue;
        
        $set = [];
        $values = [];
        
        if (isset($data['start_time'])) {
            $set[] = 'start_time = %s';
            $values[] = sanitize_text_field($data['start_time']);
        }
        
        if (isset($data['end_time'])) {
            $set[] = 'end_time = %s';
            $values[] = sanitize_text_field($data['end_time']);
        }
        
        if (isset($data['color'])) {
            $set[] = 'color = %s';
            $values[] = sanitize_hex_color($data['color']);
        }
        
        if (isset($data['is_active'])) {
            $set[] = 'is_active = %d';
            $values[] = intval($data['is_active']);
        }
        
        if (empty($set)) continue;
        
        $values[] = $shift_id;
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}rp_shifts SET " . implode(', ', $set) . " WHERE id = %d",
            $values
        ));
        
        if ($result !== false) {
            $results['updated']++;
        } else {
            $results['errors'][] = "Fout bij updaten shift ID " . $shift_id;
        }
    }
    
    return $results;
}

/**
 * Export worked hours to CSV format (Excel compatible)
 * Columns: Datum | Medewerker1 | Medewerker2 | etc.
 * Rows: Dates | Hours worked per employee
 */
add_action('admin_init', 'rooster_planner_handle_exports');
function rooster_planner_handle_exports() {
    if (!is_admin() || !current_user_can('manage_options')) return;
    
    if (isset($_GET['page']) && $_GET['page'] === 'rooster-planner-settings' && isset($_GET['export'])) {
        $export_type = sanitize_text_field($_GET['export']);
        
        if ($export_type === 'worked_hours') {
            rooster_planner_export_worked_hours();
            exit;
        }
    }
}

function rooster_planner_export_worked_hours() {
    global $wpdb;
    
    // Get all active employees
    $employees = $wpdb->get_results(
        "SELECT e.id, u.display_name, u.user_login 
        FROM {$wpdb->prefix}rp_employees e
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        WHERE e.is_active = 1
        ORDER BY u.display_name"
    );
    
    // Get current month (default) or requested month
    $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : current_time('Y-m');
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Get all schedules with worked hours for this month
    $schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, sh.name as shift_name, u.display_name as employee_name, e.id as employee_id
        FROM {$wpdb->prefix}rp_schedules s
        LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
        LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        WHERE s.work_date BETWEEN %s AND %s
        AND s.status != 'cancelled'
        AND e.is_active = 1
        ORDER BY s.work_date, u.display_name",
        $start_date, $end_date
    ));
    
    // Prepare data structure: date => employee_id => hours
    $data = [];
    $dates = [];
    
    foreach ($schedules as $schedule) {
        $date = $schedule->work_date;
        if (!in_array($date, $dates)) {
            $dates[] = $date;
        }
        
        if (!isset($data[$date])) {
            $data[$date] = [];
        }
        
        // Calculate hours worked
        $hours = '';
        if ($schedule->actual_start_time && $schedule->actual_end_time) {
            $start = strtotime($schedule->actual_start_time);
            $end = strtotime($schedule->actual_end_time);
            $break_minutes = intval($schedule->break_minutes);
            $diff = $end - $start - ($break_minutes * 60);
            $hours = round($diff / 3600, 2);
        }
        
        $data[$date][$schedule->employee_id] = [
            'hours' => $hours,
            'shift' => $schedule->shift_name,
            'planned_start' => $schedule->start_time,
            'planned_end' => $schedule->end_time,
            'actual_start' => $schedule->actual_start_time,
            'actual_end' => $schedule->actual_end_time
        ];
    }
    
    sort($dates);
    
    // Generate CSV
    $filename = 'gewerkte-uren-' . $month . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Add BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header row with employee names
    $header = ['Datum'];
    $employee_map = [];
    foreach ($employees as $emp) {
        $header[] = $emp->display_name ?: $emp->user_login;
        $employee_map[$emp->id] = $emp;
    }
    fputcsv($output, $header, ';');
    
    // Data rows
    foreach ($dates as $date) {
        $row = [date('d-m-Y', strtotime($date))];
        
        foreach ($employees as $emp) {
            if (isset($data[$date][$emp->id])) {
                $hours_data = $data[$date][$emp->id];
                if ($hours_data['hours'] !== '') {
                    $row[] = str_replace('.', ',', $hours_data['hours']); // Excel format
                } else {
                    $row[] = '-'; // Scheduled but no hours entered
                }
            } else {
                $row[] = ''; // No schedule
            }
        }
        
        fputcsv($output, $row, ';');
    }
    
    // Add empty row
    fputcsv($output, [], ';');
    
    // Add summary row - total hours per employee
    $summary_row = ['TOTAAL UREN'];
    foreach ($employees as $emp) {
        $total_hours = 0;
        foreach ($dates as $date) {
            if (isset($data[$date][$emp->id]) && $data[$date][$emp->id]['hours'] !== '') {
                $total_hours += floatval($data[$date][$emp->id]['hours']);
            }
        }
        $summary_row[] = $total_hours > 0 ? str_replace('.', ',', round($total_hours, 2)) : '';
    }
    fputcsv($output, $summary_row, ';');
    
    fclose($output);
}
