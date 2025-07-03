<?php
/**
 * Plugin Name: Truck Maintenance Manager
 * Plugin URI: https://example.com/truck-maintenance-manager
 * Description: Complete truck maintenance management system with urgency-based visual indicators, QR codes, and comprehensive tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: truck-maintenance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TMM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TMM_VERSION', '1.0.0');

class TruckMaintenanceManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_tmm_save_truck', array($this, 'ajax_save_truck'));
        add_action('wp_ajax_tmm_delete_truck', array($this, 'ajax_delete_truck'));
        add_action('wp_ajax_tmm_update_maintenance', array($this, 'ajax_update_maintenance'));
        add_action('wp_ajax_tmm_import_trucks', array($this, 'ajax_import_trucks'));
        add_action('wp_ajax_tmm_generate_qr', array($this, 'ajax_generate_qr'));
    }
    
    public function activate() {
        $this->create_tables();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vin varchar(17) NOT NULL UNIQUE,
            unit_number varchar(50) DEFAULT '',
            year int(4) NOT NULL,
            make varchar(100) NOT NULL,
            model varchar(100) NOT NULL,
            current_mileage int(11) DEFAULT 0,
            distance_unit varchar(10) DEFAULT 'miles',
            oil_change_interval int(11) DEFAULT 5000,
            air_filter_interval int(11) DEFAULT 15000,
            fuel_filter_interval int(11) DEFAULT 25000,
            dpf_cleaning_interval int(11) DEFAULT 100000,
            maintenance_history longtext DEFAULT '',
            safety_inspection_date date DEFAULT NULL,
            safety_inspection_expiry date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Truck Maintenance',
            'Truck Maintenance',
            'manage_options',
            'truck-maintenance',
            array($this, 'dashboard_page'),
            'dashicons-car',
            30
        );
        
        add_submenu_page(
            'truck-maintenance',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'truck-maintenance',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'truck-maintenance',
            'All Trucks',
            'All Trucks',
            'manage_options',
            'truck-maintenance-list',
            array($this, 'truck_list_page')
        );
        
        add_submenu_page(
            'truck-maintenance',
            'Add Truck',
            'Add Truck',
            'manage_options',
            'truck-maintenance-add',
            array($this, 'add_truck_page')
        );
        
        add_submenu_page(
            'truck-maintenance',
            'Import Trucks',
            'Import Trucks',
            'manage_options',
            'truck-maintenance-import',
            array($this, 'import_trucks_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'truck-maintenance') === false) {
            return;
        }
        
        wp_enqueue_script('tmm-admin', TMM_PLUGIN_URL . 'assets/admin.js', array('jquery'), TMM_VERSION, true);
        wp_enqueue_style('tmm-admin', TMM_PLUGIN_URL . 'assets/admin.css', array(), TMM_VERSION);
        
        wp_localize_script('tmm-admin', 'tmm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmm_nonce')
        ));
    }
    
    public function dashboard_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $trucks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC");
        
        // Process trucks data
        foreach ($trucks as &$truck) {
            $truck->maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
        }
        
        include TMM_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    public function truck_list_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $trucks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC");
        
        // Process trucks data
        foreach ($trucks as &$truck) {
            $truck->maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
        }
        
        include TMM_PLUGIN_PATH . 'templates/truck-list.php';
    }
    
    public function add_truck_page() {
        include TMM_PLUGIN_PATH . 'templates/add-truck.php';
    }
    
    public function import_trucks_page() {
        include TMM_PLUGIN_PATH . 'templates/import-trucks.php';
    }
    
    // AJAX handlers
    public function ajax_save_truck() {
        check_ajax_referer('tmm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_data = array(
            'vin' => sanitize_text_field($_POST['vin']),
            'unit_number' => sanitize_text_field($_POST['unit_number']),
            'year' => intval($_POST['year']),
            'make' => sanitize_text_field($_POST['make']),
            'model' => sanitize_text_field($_POST['model']),
            'current_mileage' => intval($_POST['current_mileage']),
            'distance_unit' => sanitize_text_field($_POST['distance_unit']),
            'oil_change_interval' => intval($_POST['oil_change_interval']),
            'air_filter_interval' => intval($_POST['air_filter_interval']),
            'fuel_filter_interval' => intval($_POST['fuel_filter_interval']),
            'dpf_cleaning_interval' => intval($_POST['dpf_cleaning_interval'])
        );
        
        if (isset($_POST['truck_id']) && !empty($_POST['truck_id'])) {
            // Update existing truck
            $wpdb->update($table_name, $truck_data, array('id' => intval($_POST['truck_id'])));
            $truck_id = intval($_POST['truck_id']);
        } else {
            // Insert new truck
            $wpdb->insert($table_name, $truck_data);
            $truck_id = $wpdb->insert_id;
        }
        
        wp_send_json_success(array('truck_id' => $truck_id));
    }
    
    public function ajax_update_maintenance() {
        check_ajax_referer('tmm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_id = intval($_POST['truck_id']);
        $maintenance_type = sanitize_text_field($_POST['maintenance_type']);
        $maintenance_date = sanitize_text_field($_POST['maintenance_date']);
        $maintenance_mileage = intval($_POST['maintenance_mileage']);
        $next_date = sanitize_text_field($_POST['next_date']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // Get current truck data
        $truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
        
        if (!$truck) {
            wp_send_json_error('Truck not found');
        }
        
        $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
        
        // Add new maintenance record
        $new_record = array(
            'type' => $maintenance_type,
            'date' => $maintenance_date,
            'mileage' => $maintenance_mileage,
            'nextDate' => $next_date,
            'notes' => $notes,
            'timestamp' => current_time('mysql')
        );
        
        // Remove existing record of same type and add new one
        $maintenance_history = array_filter($maintenance_history, function($record) use ($maintenance_type) {
            return $record['type'] !== $maintenance_type;
        });
        
        $maintenance_history[] = $new_record;
        
        // Update truck record
        $update_data = array(
            'maintenance_history' => json_encode($maintenance_history),
            'current_mileage' => $maintenance_mileage
        );
        
        // Handle safety inspection separately
        if ($maintenance_type === 'safetyInspection') {
            $update_data['safety_inspection_date'] = $maintenance_date;
            if (!empty($next_date)) {
                $update_data['safety_inspection_expiry'] = $next_date;
            }
        }
        
        $wpdb->update($table_name, $update_data, array('id' => $truck_id));
        
        wp_send_json_success();
    }
    
    public function ajax_delete_truck() {
        check_ajax_referer('tmm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_id = intval($_POST['truck_id']);
        $wpdb->delete($table_name, array('id' => $truck_id));
        
        wp_send_json_success();
    }
    
    public function ajax_import_trucks() {
        check_ajax_referer('tmm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['csv_file'];
        $csv_data = array_map('str_getcsv', file($file['tmp_name']));
        $headers = array_shift($csv_data);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $imported = 0;
        $errors = array();
        
        foreach ($csv_data as $row) {
            $truck_data = array_combine($headers, $row);
            
            $insert_data = array(
                'vin' => sanitize_text_field($truck_data['vin']),
                'unit_number' => sanitize_text_field($truck_data['unit_number'] ?? ''),
                'year' => intval($truck_data['year']),
                'make' => sanitize_text_field($truck_data['make']),
                'model' => sanitize_text_field($truck_data['model']),
                'current_mileage' => intval($truck_data['current_mileage'] ?? 0),
                'distance_unit' => sanitize_text_field($truck_data['distance_unit'] ?? 'miles')
            );
            
            $result = $wpdb->insert($table_name, $insert_data);
            
            if ($result) {
                $imported++;
            } else {
                $errors[] = "Failed to import truck with VIN: " . $truck_data['vin'];
            }
        }
        
        wp_send_json_success(array(
            'imported' => $imported,
            'errors' => $errors
        ));
    }
    
    public function ajax_generate_qr() {
        check_ajax_referer('tmm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $truck_id = intval($_POST['truck_id']);
        $truck_url = admin_url('admin.php?page=truck-maintenance-list&truck_id=' . $truck_id);
        
        // Generate QR code using Google Charts API
        $qr_url = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($truck_url);
        
        wp_send_json_success(array('qr_url' => $qr_url));
    }
}

// Initialize the plugin
new TruckMaintenanceManager();

// Helper functions
function tmm_calculate_urgency_score($truck) {
    $max_urgency = 0;
    $today = new DateTime();
    
    $maintenance_history = is_string($truck->maintenance_history) ? 
        json_decode($truck->maintenance_history, true) : $truck->maintenance_history;
    
    if (empty($maintenance_history)) {
        if ($truck->oil_change_interval || $truck->air_filter_interval || 
            $truck->fuel_filter_interval || $truck->dpf_cleaning_interval) {
            return 100; // Maximum urgency for trucks with no maintenance history
        }
    }
    
    $maintenance_types = array(
        array('type' => 'oil', 'interval' => $truck->oil_change_interval ?: 5000, 'weight' => 1.0),
        array('type' => 'airFilter', 'interval' => $truck->air_filter_interval ?: 15000, 'weight' => 0.8),
        array('type' => 'fuelFilter', 'interval' => $truck->fuel_filter_interval ?: 25000, 'weight' => 0.9),
        array('type' => 'dpfCleaning', 'interval' => $truck->dpf_cleaning_interval ?: 100000, 'weight' => 0.7)
    );
    
    foreach ($maintenance_types as $maintenance) {
        if (!$maintenance['interval']) continue;
        
        $record = null;
        if ($maintenance_history) {
            foreach ($maintenance_history as $hist) {
                if ($hist['type'] === $maintenance['type']) {
                    $record = $hist;
                    break;
                }
            }
        }
        
        if (!$record) {
            $max_urgency = max($max_urgency, 90 * $maintenance['weight']);
            continue;
        }
        
        $urgency = 0;
        
        // Check by date
        if (!empty($record['nextDate'])) {
            $next_date = new DateTime($record['nextDate']);
            $days_diff = $today->diff($next_date)->days;
            $is_overdue = $today > $next_date;
            
            if ($is_overdue) {
                $urgency = min(100, 80 + ($days_diff * 2));
            } elseif ($days_diff <= 14) {
                $urgency = max($urgency, 80 - ($days_diff * 2.8));
            } elseif ($days_diff <= 30) {
                $urgency = max($urgency, 40 - (($days_diff - 14) * 1.25));
            }
        }
        
        // Check by mileage
        if (!empty($record['mileage']) && $truck->current_mileage) {
            $next_due_mileage = $record['mileage'] + $maintenance['interval'];
            $mileage_diff = $next_due_mileage - $truck->current_mileage;
            $approaching_threshold = ($truck->distance_unit === 'miles') ? 1000 : 1600;
            
            if ($mileage_diff <= 0) {
                $miles_overdue = abs($mileage_diff);
                $overdue_percentage = ($miles_overdue / $maintenance['interval']) * 100;
                $urgency = max($urgency, min(100, 80 + $overdue_percentage));
            } elseif ($mileage_diff <= $approaching_threshold) {
                $approaching_percentage = (($approaching_threshold - $mileage_diff) / $approaching_threshold) * 100;
                $urgency = max($urgency, 20 + ($approaching_percentage * 0.6));
            }
        }
        
        $max_urgency = max($max_urgency, $urgency * $maintenance['weight']);
    }
    
    // Check safety inspection
    if ($truck->safety_inspection_expiry) {
        $expiry_date = new DateTime($truck->safety_inspection_expiry);
        $days_diff = $today->diff($expiry_date)->days;
        $is_expired = $today > $expiry_date;
        
        if ($is_expired) {
            $max_urgency = max($max_urgency, min(100, 90 + $days_diff));
        } elseif ($days_diff <= 30) {
            $max_urgency = max($max_urgency, 90 - ($days_diff * 2));
        }
    } elseif ($truck->year) {
        $max_urgency = max($max_urgency, 95);
    }
    
    return round($max_urgency);
}

function tmm_get_urgency_background_color($urgency_score) {
    if ($urgency_score === 0) {
        return 'tmm-bg-green';
    } elseif ($urgency_score <= 30) {
        return 'tmm-bg-yellow-light';
    } elseif ($urgency_score <= 50) {
        return 'tmm-bg-yellow';
    } elseif ($urgency_score <= 70) {
        return 'tmm-bg-orange-light';
    } elseif ($urgency_score <= 85) {
        return 'tmm-bg-orange';
    } else {
        return 'tmm-bg-red';
    }
}

function tmm_get_urgency_border_color($urgency_score) {
    if ($urgency_score === 0) {
        return 'tmm-border-green';
    } elseif ($urgency_score <= 30) {
        return 'tmm-border-yellow-light';
    } elseif ($urgency_score <= 50) {
        return 'tmm-border-yellow';
    } elseif ($urgency_score <= 70) {
        return 'tmm-border-orange-light';
    } elseif ($urgency_score <= 85) {
        return 'tmm-border-orange';
    } else {
        return 'tmm-border-red';
    }
}
