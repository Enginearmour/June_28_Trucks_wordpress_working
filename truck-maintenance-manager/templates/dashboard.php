<?php
if (!defined('ABSPATH')) {
    exit;
}

// Calculate statistics
$total_trucks = count($trucks);
$maintenance_due = 0;
$recent_maintenance = 0;

$one_month_ago = new DateTime();
$one_month_ago->modify('-1 month');

foreach ($trucks as $truck) {
    // Check if maintenance is due
    $urgency_score = tmm_calculate_urgency_score($truck);
    if ($urgency_score > 0) {
        $maintenance_due++;
    }
    
    // Check for recent maintenance
    if (!empty($truck->maintenance_history)) {
        $maintenance_history = json_decode($truck->maintenance_history, true);
        foreach ($maintenance_history as $record) {
            if (!empty($record['date'])) {
                $service_date = new DateTime($record['date']);
                if ($service_date > $one_month_ago) {
                    $recent_maintenance++;
                    break;
                }
            }
        }
    }
}

// Get trucks due for maintenance (sorted by urgency)
$trucks_due = array_filter($trucks, function($truck) {
    return tmm_calculate_urgency_score($truck) > 0;
});

usort($trucks_due, function($a, $b) {
    return tmm_calculate_urgency_score($b) - tmm_calculate_urgency_score($a);
});

$trucks_due = array_slice($trucks_due, 0, 5);

// Get recently serviced trucks
$recently_serviced = array_filter($trucks, function($truck) {
    return !empty($truck->maintenance_history);
});

usort($recently_serviced, function($a, $b) {
    $a_history = json_decode($a->maintenance_history, true) ?: array();
    $b_history = json_decode($b->maintenance_history, true) ?: array();
    
    $a_latest = null;
    $b_latest = null;
    
    foreach ($a_history as $record) {
        if (!empty($record['date'])) {
            $date = new DateTime($record['date']);
            if (!$a_latest || $date > $a_latest) {
                $a_latest = $date;
            }
        }
    }
    
    foreach ($b_history as $record) {
        if (!empty($record['date'])) {
            $date = new DateTime($record['date']);
            if (!$b_latest || $date > $b_latest) {
                $b_latest = $date;
            }
        }
    }
    
    if (!$a_latest) return 1;
    if (!$b_latest) return -1;
    
    return $b_latest <=> $a_latest;
});

$recently_serviced = array_slice($recently_serviced, 0, 5);
?>

<div class="tmm-container">
    <h1>Fleet Maintenance Dashboard</h1>
    
    <div class="tmm-stats-grid">
        <div class="tmm-stat-card">
            <div class="tmm-stat-icon primary">
                <span class="dashicons dashicons-car"></span>
            </div>
            <div class="tmm-stat-content">
                <h3><?php echo $total_trucks; ?></h3>
                <p>Total Trucks</p>
            </div>
        </div>
        
        <div class="tmm-stat-card">
            <div class="tmm-stat-icon warning">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="tmm-stat-content">
                <h3><?php echo $maintenance_due; ?></h3>
                <p>Maintenance Due</p>
            </div>
        </div>
        
        <div class="tmm-stat-card">
            <div class="tmm-stat-icon success">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="tmm-stat-content">
                <h3><?php echo $recent_maintenance; ?></h3>
                <p>Recent Maintenance</p>
            </div>
        </div>
    </div>
    
    <div class="tmm-dashboard-grid">
        <div class="tmm-card">
            <div class="tmm-section-header">
                <h2 class="tmm-section-title">Maintenance Due Soon</h2>
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-view-all">View All</a>
            </div>
            
            <?php if (empty($trucks_due)): ?>
                <p style="text-align: center; color: #666; padding: 40px 0;">No maintenance due soon</p>
            <?php else: ?>
                <div class="tmm-truck-list">
                    <?php foreach ($trucks_due as $truck): 
                        $urgency_score = tmm_calculate_urgency_score($truck);
                        $bg_class = tmm_get_urgency_background_color($urgency_score);
                        $border_class = tmm_get_urgency_border_color($urgency_score);
                        $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
                        
                        // Get due maintenance items
                        $due_items = array();
                        
                        // Check each maintenance type
                        $maintenance_types = array(
                            'oil' => array('label' => 'Oil Change', 'interval' => $truck->oil_change_interval ?: 5000),
                            'airFilter' => array('label' => 'Air Filter', 'interval' => $truck->air_filter_interval ?: 15000),
                            'fuelFilter' => array('label' => 'Fuel Filter', 'interval' => $truck->fuel_filter_interval ?: 25000),
                            'dpfCleaning' => array('label' => 'DPF Cleaning', 'interval' => $truck->dpf_cleaning_interval ?: 100000)
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
                                $due_items[] = array(
                                    'type' => $type,
                                    'label' => $config['label'],
                                    'status' => 'due',
                                    'message' => 'Initial service needed'
                                );
                                continue;
                            }
                            
                            $today = new DateTime();
                            $is_due = false;
                            $message = '';
                            
                            // Check by date
                            if (!empty($record['nextDate'])) {
                                $next_date = new DateTime($record['nextDate']);
                                if ($today > $next_date) {
                                    $is_due = true;
                                    $message = 'Due on ' . $next_date->format('M j, Y');
                                } elseif ($today->diff($next_date)->days <= 14) {
                                    $is_due = true;
                                    $message = 'Due on ' . $next_date->format('M j, Y');
                                }
                            }
                            
                            // Check by mileage
                            if (!empty($record['mileage']) && $truck->current_mileage) {
                                $next_due_mileage = $record['mileage'] + $config['interval'];
                                $approaching_threshold = ($truck->distance_unit === 'miles') ? 500 : 800;
                                
                                if ($truck->current_mileage >= $next_due_mileage || 
                                    $truck->current_mileage + $approaching_threshold >= $next_due_mileage) {
                                    $is_due = true;
                                    $status = $truck->current_mileage >= $next_due_mileage ? 'overdue' : 'approaching';
                                    $message = 'Due at ' . number_format($next_due_mileage) . ' ' . $truck->distance_unit;
                                }
                            }
                            
                            if ($is_due) {
                                $due_items[] = array(
                                    'type' => $type,
                                    'label' => $config['label'],
                                    'status' => isset($status) ? $status : 'due',
                                    'message' => $message
                                );
                            }
                        }
                        
                        // Check safety inspection
                        if ($truck->safety_inspection_expiry) {
                            $expiry_date = new DateTime($truck->safety_inspection_expiry);
                            $today = new DateTime();
                            $thirty_days = new DateTime();
                            $thirty_days->modify('+30 days');
                            
                            if ($today > $expiry_date) {
                                $due_items[] = array(
                                    'type' => 'safetyInspection',
                                    'label' => 'Safety Inspection',
                                    'status' => 'overdue',
                                    'message' => 'Expired on ' . $expiry_date->format('M j, Y')
                                );
                            } elseif ($thirty_days > $expiry_date) {
                                $due_items[] = array(
                                    'type' => 'safetyInspection',
                                    'label' => 'Safety Inspection',
                                    'status' => 'approaching',
                                    'message' => 'Expires on ' . $expiry_date->format('M j, Y')
                                );
                            }
                        } elseif ($truck->year) {
                            $due_items[] = array(
                                'type' => 'safetyInspection',
                                'label' => 'Safety Inspection',
                                'status' => 'due',
                                'message' => 'Safety inspection needed'
                            );
                        }
                        
                        if (empty($due_items)) continue;
                    ?>
                        <div class="tmm-truck-item <?php echo $bg_class . ' ' . $border_class; ?>" data-urgency-score="<?php echo $urgency_score; ?>">
                            <div class="tmm-truck-header">
                                <div class="tmm-truck-info">
                                    <h4><?php echo esc_html($truck->year . ' ' . $truck->make . ' ' . $truck->model); ?></h4>
                                    <?php if ($truck->unit_number): ?>
                                        <p class="tmm-unit-number">Unit: <?php echo esc_html($truck->unit_number); ?></p>
                                    <?php endif; ?>
                                    <p>VIN: <?php echo esc_html($truck->vin); ?></p>
                                    <p class="tmm-urgency-score">Urgency Score: <?php echo $urgency_score; ?>/100</p>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list&truck_id=' . $truck->id); ?>" class="tmm-view-btn">View</a>
                            </div>
                            
                            <div class="tmm-maintenance-list">
                                <p>Maintenance Due:</p>
                                <div class="tmm-maintenance-items">
                                    <?php foreach ($due_items as $item): ?>
                                        <div class="tmm-maintenance-item">
                                            <span class="tmm-maintenance-icon <?php echo $item['status']; ?>">
                                                <?php
                                                $icons = array(
                                                    'oil' => '🛢️',
                                                    'airFilter' => '🔧',
                                                    'fuelFilter' => '⛽',
                                                    'dpfCleaning' => '💨',
                                                    'safetyInspection' => '📋'
                                                );
                                                echo $icons[$item['type']] ?? '🔧';
                                                ?>
                                            </span>
                                            <span class="tmm-maintenance-text"><?php echo esc_html($item['label']); ?>: </span>
                                            <span class="tmm-maintenance-status <?php echo $item['status']; ?>">
                                                <?php echo esc_html($item['message']); ?>
                                                <?php if ($item['status'] === 'overdue'): ?> (OVERDUE)<?php endif; ?>
                                                <?php if ($item['status'] === 'approaching'): ?> (SOON)<?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tmm-card">
            <div class="tmm-section-header">
                <h2 class="tmm-section-title">Recently Serviced</h2>
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-view-all">View All</a>
            </div>
            
            <?php if (empty($recently_serviced)): ?>
                <p style="text-align: center; color: #666; padding: 40px 0;">No recent maintenance records</p>
            <?php else: ?>
                <div class="tmm-truck-list">
                    <?php foreach ($recently_serviced as $truck): 
                        $urgency_score = tmm_calculate_urgency_score($truck);
                        $bg_class = tmm_get_urgency_background_color($urgency_score);
                        $border_class = tmm_get_urgency_border_color($urgency_score);
                        $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
                        
                        // Find most recent maintenance
                        $latest_record = null;
                        $latest_date = null;
                        
                        foreach ($maintenance_history as $record) {
                            if (!empty($record['date'])) {
                                $record_date = new DateTime($record['date']);
                                if (!$latest_date || $record_date > $latest_date) {
                                    $latest_date = $record_date;
                                    $latest_record = $record;
                                }
                            }
                        }
                        
                        if (!$latest_record) continue;
                        
                        $type_labels = array(
                            'oil' => 'Oil Change',
                            'airFilter' => 'Air Filter Change',
                            'fuelFilter' => 'Fuel Filter Change',
                            'dpfCleaning' => 'DPF Cleaning',
                            'safetyInspection' => 'Safety Inspection'
                        );
                        
                        $has_due_maintenance = $urgency_score > 0;
                    ?>
                        <div class="tmm-truck-item <?php echo $bg_class . ' ' . $border_class; ?>" data-urgency-score="<?php echo $urgency_score; ?>">
                            <div class="tmm-truck-header">
                                <div class="tmm-truck-info">
                                    <h4><?php echo esc_html($truck->year . ' ' . $truck->make . ' ' . $truck->model); ?></h4>
                                    <?php if ($truck->unit_number): ?>
                                        <p class="tmm-unit-number">Unit: <?php echo esc_html($truck->unit_number); ?></p>
                                    <?php endif; ?>
                                    <p>VIN: <?php echo esc_html($truck->vin); ?></p>
                                    <p class="tmm-urgency-score">Urgency Score: <?php echo $urgency_score; ?>/100</p>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list&truck_id=' . $truck->id); ?>" class="tmm-view-btn">View</a>
                            </div>
                            
                            <div class="tmm-maintenance-list">
                                <p>Recent Service: <?php if ($has_due_maintenance): ?><span style="color: #d32f2f; font-size: 12px; font-weight: 600;">(Maintenance Due)</span><?php endif; ?></p>
                                <div style="font-size: 13px; margin-top: 5px;">
                                    <p><strong>Type:</strong> <?php echo esc_html($type_labels[$latest_record['type']] ?? $latest_record['type']); ?></p>
                                    <p><strong>Date:</strong> <?php echo esc_html($latest_date->format('M j, Y')); ?></p>
                                    <p><strong>Mileage:</strong> <?php echo esc_html(number_format($latest_record['mileage']) . ' ' . $truck->distance_unit); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
