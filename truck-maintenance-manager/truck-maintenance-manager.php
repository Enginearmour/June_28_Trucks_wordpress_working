<?php
/**
 * Plugin Name: Truck Maintenance Manager
 * Description: Complete truck maintenance management system with scheduling, tracking, and reporting
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TruckMaintenanceManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_tmm_save_truck', array($this, 'ajax_save_truck'));
        add_action('wp_ajax_tmm_delete_truck', array($this, 'ajax_delete_truck'));
        add_action('wp_ajax_tmm_save_maintenance', array($this, 'ajax_save_maintenance'));
        add_action('wp_ajax_tmm_get_truck_details', array($this, 'ajax_get_truck_details'));
        add_action('wp_ajax_tmm_update_mileage', array($this, 'ajax_update_mileage'));
        
        // Frontend AJAX (for logged-in users)
        add_action('wp_ajax_nopriv_tmm_get_truck_details', array($this, 'ajax_get_truck_details'));
        
        add_shortcode('truck_maintenance_dashboard', array($this, 'frontend_dashboard'));
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            unit_number varchar(50),
            vin varchar(17) NOT NULL,
            year int(4),
            make varchar(50),
            model varchar(50),
            current_mileage int(11) DEFAULT 0,
            distance_unit varchar(10) DEFAULT 'miles',
            oil_change_interval int(11) DEFAULT 5000,
            oil_filter_interval int(11) DEFAULT 5000,
            air_filter_interval int(11) DEFAULT 15000,
            fuel_filter_interval int(11) DEFAULT 25000,
            dpf_cleaning_interval int(11) DEFAULT 100000,
            safety_inspection_expiry date,
            maintenance_history longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vin (vin)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function admin_menu() {
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
            'Truck List',
            'Truck List',
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
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'truck-maintenance') === false) {
            return;
        }
        
        wp_enqueue_style('tmm-admin-style', plugin_dir_url(__FILE__) . 'assets/admin-style.css', array(), '1.0.0');
        wp_enqueue_script('tmm-admin-script', plugin_dir_url(__FILE__) . 'assets/admin-script.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('tmm-admin-script', 'tmm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmm_nonce')
        ));
    }
    
    public function frontend_scripts() {
        wp_enqueue_style('tmm-frontend-style', plugin_dir_url(__FILE__) . 'assets/frontend-style.css', array(), '1.0.0');
        wp_enqueue_script('tmm-frontend-script', plugin_dir_url(__FILE__) . 'assets/frontend-script.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('tmm-frontend-script', 'tmm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmm_nonce')
        ));
    }
    
    public function dashboard_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        $trucks = $wpdb->get_results("SELECT * FROM $table_name");
        
        // Sort trucks by urgency score (highest first)
        usort($trucks, function($a, $b) {
            $urgency_a = tmm_calculate_urgency_score($a);
            $urgency_b = tmm_calculate_urgency_score($b);
            
            // Primary sort: urgency score (descending)
            if ($urgency_a !== $urgency_b) {
                return $urgency_b - $urgency_a;
            }
            
            // Secondary sort: updated_at (most recent first)
            return strtotime($b->updated_at) - strtotime($a->updated_at);
        });
        
        include plugin_dir_path(__FILE__) . 'templates/admin/dashboard.php';
    }
    
    public function truck_list_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        // Handle single truck view
        if (isset($_GET['truck_id'])) {
            $truck_id = intval($_GET['truck_id']);
            $truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
            if ($truck) {
                include plugin_dir_path(__FILE__) . 'templates/admin/truck-details.php';
                return;
            }
        }
        
        $trucks = $wpdb->get_results("SELECT * FROM $table_name");
        
        // Sort trucks by urgency score (highest first)
        usort($trucks, function($a, $b) {
            $urgency_a = tmm_calculate_urgency_score($a);
            $urgency_b = tmm_calculate_urgency_score($b);
            
            // Primary sort: urgency score (descending)
            if ($urgency_a !== $urgency_b) {
                return $urgency_b - $urgency_a;
            }
            
            // Secondary sort: updated_at (most recent first)
            return strtotime($b->updated_at) - strtotime($a->updated_at);
        });
        
        include plugin_dir_path(__FILE__) . 'templates/admin/truck-list.php';
    }
    
    public function add_truck_page() {
        // Handle edit mode
        $truck = null;
        if (isset($_GET['edit']) && isset($_GET['truck_id'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'truck_maintenance';
            $truck_id = intval($_GET['truck_id']);
            $truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
        }
        
        include plugin_dir_path(__FILE__) . 'templates/admin/add-truck.php';
    }
    
    public function ajax_save_truck() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tmm_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        // Debug: Log all received data
        error_log('TMM Save Truck - All POST data: ' . print_r($_POST, true));
        
        $truck_data = array(
            'unit_number' => sanitize_text_field($_POST['unit_number']),
            'vin' => sanitize_text_field($_POST['vin']),
            'year' => intval($_POST['year']),
            'make' => sanitize_text_field($_POST['make']),
            'model' => sanitize_text_field($_POST['model']),
            'current_mileage' => intval($_POST['current_mileage']),
            'distance_unit' => sanitize_text_field($_POST['distance_unit']),
            'oil_change_interval' => intval($_POST['oil_change_interval']),
            'oil_filter_interval' => intval($_POST['oil_filter_interval']),
            'air_filter_interval' => intval($_POST['air_filter_interval']),
            'fuel_filter_interval' => intval($_POST['fuel_filter_interval']),
            'dpf_cleaning_interval' => intval($_POST['dpf_cleaning_interval']),
            'safety_inspection_expiry' => sanitize_text_field($_POST['safety_inspection_expiry'])
        );
        
        // Debug: Log the truck data being saved
        error_log('TMM Save Truck - Truck data: ' . print_r($truck_data, true));
        
        if (isset($_POST['truck_id']) && !empty($_POST['truck_id'])) {
            $truck_id = intval($_POST['truck_id']);
            
            // Debug: Log update operation
            error_log('TMM Save Truck - Updating truck ID: ' . $truck_id);
            error_log('TMM Save Truck - Oil filter interval being saved: ' . $truck_data['oil_filter_interval']);
            
            $result = $wpdb->update($table_name, $truck_data, array('id' => $truck_id));
            
            // Debug: Log update result
            error_log('TMM Save Truck - Update result: ' . $result);
            error_log('TMM Save Truck - Last error: ' . $wpdb->last_error);
            
            if ($result !== false) {
                // Verify the data was actually saved
                $saved_truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
                error_log('TMM Save Truck - Verified saved data: ' . print_r($saved_truck, true));
                
                wp_send_json_success(array(
                    'message' => 'Truck updated successfully',
                    'truck_id' => $truck_id,
                    'saved_data' => $saved_truck
                ));
            } else {
                wp_send_json_error('Failed to update truck: ' . $wpdb->last_error);
            }
        } else {
            // Debug: Log insert operation
            error_log('TMM Save Truck - Inserting new truck');
            
            $result = $wpdb->insert($table_name, $truck_data);
            
            // Debug: Log insert result
            error_log('TMM Save Truck - Insert result: ' . $result);
            error_log('TMM Save Truck - Last error: ' . $wpdb->last_error);
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'Truck added successfully',
                    'truck_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error('Failed to add truck: ' . $wpdb->last_error);
            }
        }
    }
    
    public function ajax_delete_truck() {
        if (!wp_verify_nonce($_POST['nonce'], 'tmm_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_id = intval($_POST['truck_id']);
        $result = $wpdb->delete($table_name, array('id' => $truck_id));
        
        if ($result !== false) {
            wp_send_json_success('Truck deleted successfully');
        } else {
            wp_send_json_error('Failed to delete truck: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_save_maintenance() {
        if (!wp_verify_nonce($_POST['nonce'], 'tmm_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_id = intval($_POST['truck_id']);
        $maintenance_type = sanitize_text_field($_POST['maintenance_type']);
        
        // Prepare maintenance data
        $maintenance_data = array(
            'type' => $maintenance_type,
            'date' => sanitize_text_field($_POST['maintenance_date']),
            'mileage' => intval($_POST['maintenance_mileage']),
            'notes' => sanitize_textarea_field($_POST['maintenance_notes']),
            'timestamp' => current_time('mysql')
        );
        
        // Add next due date if provided
        if (!empty($_POST['next_due_date'])) {
            $maintenance_data['nextDate'] = sanitize_text_field($_POST['next_due_date']);
        }
        
        // Get current truck data
        $truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
        
        if (!$truck) {
            wp_send_json_error('Truck not found');
            return;
        }
        
        // Handle safety inspection separately - update the truck's safety_inspection_expiry field
        if ($maintenance_type === 'safetyInspection') {
            $update_data = array();
            
            // Update safety inspection expiry date if next due date is provided
            if (!empty($_POST['next_due_date'])) {
                $update_data['safety_inspection_expiry'] = sanitize_text_field($_POST['next_due_date']);
            }
            
            // Update the truck record
            if (!empty($update_data)) {
                $wpdb->update($table_name, $update_data, array('id' => $truck_id));
            }
        }
        
        // Get current maintenance history
        $history = array();
        if (!empty($truck->maintenance_history)) {
            $history = json_decode($truck->maintenance_history, true) ?: array();
        }
        
        // Remove any existing record of the same type (keep only the latest)
        $history = array_filter($history, function($record) use ($maintenance_type) {
            return $record['type'] !== $maintenance_type;
        });
        
        // Add new record
        $history[] = $maintenance_data;
        
        // Update maintenance history
        $result = $wpdb->update(
            $table_name,
            array('maintenance_history' => json_encode($history)),
            array('id' => $truck_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Maintenance record saved successfully',
                'truck_id' => $truck_id,
                'maintenance_type' => $maintenance_type
            ));
        } else {
            wp_send_json_error('Failed to save maintenance record: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_get_truck_details() {
        if (!wp_verify_nonce($_POST['nonce'], 'tmm_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_id = intval($_POST['truck_id']);
        $truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
        
        if ($truck) {
            $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
            $urgency_score = tmm_calculate_urgency_score($truck);
            
            wp_send_json_success(array(
                'truck' => $truck,
                'maintenance_history' => $maintenance_history,
                'urgency_score' => $urgency_score
            ));
        } else {
            wp_send_json_error('Truck not found');
        }
    }
    
    public function ajax_update_mileage() {
        if (!wp_verify_nonce($_POST['nonce'], 'tmm_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        
        $truck_id = intval($_POST['truck_id']);
        $new_mileage = intval($_POST['mileage']);
        
        $result = $wpdb->update(
            $table_name,
            array('current_mileage' => $new_mileage),
            array('id' => $truck_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Mileage updated successfully',
                'truck_id' => $truck_id,
                'new_mileage' => $new_mileage
            ));
        } else {
            wp_send_json_error('Failed to update mileage: ' . $wpdb->last_error);
        }
    }
    
    public function frontend_dashboard($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'truck_maintenance';
        $trucks = $wpdb->get_results("SELECT * FROM $table_name");
        
        // Sort trucks by urgency score (highest first)
        usort($trucks, function($a, $b) {
            $urgency_a = tmm_calculate_urgency_score($a);
            $urgency_b = tmm_calculate_urgency_score($b);
            
            // Primary sort: urgency score (descending)
            if ($urgency_a !== $urgency_b) {
                return $urgency_b - $urgency_a;
            }
            
            // Secondary sort: updated_at (most recent first)
            return strtotime($b->updated_at) - strtotime($a->updated_at);
        });
        
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/frontend/dashboard.php';
        return ob_get_clean();
    }
}

// Helper functions
function tmm_calculate_urgency_score($truck) {
    $score = 0;
    
    $maintenance_history = array();
    if (!empty($truck->maintenance_history)) {
        $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
    }
    
    $maintenance_types = array(
        'oil' => array('interval' => $truck->oil_change_interval ?: 5000, 'weight' => 30),
        'oilFilter' => array('interval' => $truck->oil_filter_interval ?: 5000, 'weight' => 25),
        'airFilter' => array('interval' => $truck->air_filter_interval ?: 15000, 'weight' => 15),
        'fuelFilter' => array('interval' => $truck->fuel_filter_interval ?: 25000, 'weight' => 15),
        'dpfCleaning' => array('interval' => $truck->dpf_cleaning_interval ?: 100000, 'weight' => 20)
    );
    
    foreach ($maintenance_types as $type => $config) {
        if (!$config['interval']) continue;
        
        $record = null;
        foreach ($maintenance_history as $hist) {
            if ($hist['type'] === $type) {
                $record = $hist;
                break;
            }
        }
        
        if (!$record) {
            $score += $config['weight'];
            continue;
        }
        
        $today = new DateTime();
        
        // Check by date
        if (!empty($record['nextDate'])) {
            $next_date = new DateTime($record['nextDate']);
            if ($today > $next_date) {
                $score += $config['weight'];
            } elseif ($today->diff($next_date)->days <= 14) {
                $score += $config['weight'] * 0.7;
            }
        }
        
        // Check by mileage
        if (!empty($record['mileage']) && $truck->current_mileage) {
            $next_due_mileage = $record['mileage'] + $config['interval'];
            $approaching_threshold = ($truck->distance_unit === 'miles') ? 500 : 800;
            
            if ($truck->current_mileage >= $next_due_mileage) {
                $score += $config['weight'];
            } elseif ($truck->current_mileage + $approaching_threshold >= $next_due_mileage) {
                $score += $config['weight'] * 0.7;
            }
        }
    }
    
    // Safety inspection check - improved logic
    if ($truck->safety_inspection_expiry) {
        $expiry_date = new DateTime($truck->safety_inspection_expiry);
        $today = new DateTime();
        $thirty_days = new DateTime();
        $thirty_days->modify('+30 days');
        
        if ($today > $expiry_date) {
            $score += 20; // Expired
        } elseif ($thirty_days > $expiry_date) {
            $score += 14; // Expires within 30 days
        }
    } else {
        // No safety inspection date set
        $score += 20;
    }
    
    return min($score, 100);
}

function tmm_get_urgency_background_color($score) {
    if ($score >= 80) return 'tmm-urgency-critical';
    if ($score >= 60) return 'tmm-urgency-high';
    if ($score >= 40) return 'tmm-urgency-medium';
    if ($score >= 20) return 'tmm-urgency-low';
    return 'tmm-urgency-none';
}

function tmm_get_urgency_border_color($score) {
    if ($score >= 80) return 'tmm-border-critical';
    if ($score >= 60) return 'tmm-border-high';
    if ($score >= 40) return 'tmm-border-medium';
    if ($score >= 20) return 'tmm-border-low';
    return 'tmm-border-none';
}

// Initialize the plugin
new TruckMaintenanceManager();
