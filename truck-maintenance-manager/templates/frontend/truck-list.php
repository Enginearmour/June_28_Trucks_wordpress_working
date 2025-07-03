<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle search and sorting
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'urgency';

// Filter trucks based on search
if ($search_term) {
    $trucks = array_filter($trucks, function($truck) use ($search_term) {
        $search_lower = strtolower($search_term);
        return (
            strpos(strtolower($truck->vin), $search_lower) !== false ||
            strpos(strtolower($truck->make), $search_lower) !== false ||
            strpos(strtolower($truck->model), $search_lower) !== false ||
            strpos(strtolower($truck->year), $search_lower) !== false ||
            strpos(strtolower($truck->unit_number), $search_lower) !== false
        );
    });
}

// Sort trucks
switch ($sort_by) {
    case 'urgency':
        usort($trucks, function($a, $b) {
            return tmm_calculate_urgency_score($b) - tmm_calculate_urgency_score($a);
        });
        break;
    case 'year':
        usort($trucks, function($a, $b) {
            return $b->year - $a->year;
        });
        break;
    case 'make':
        usort($trucks, function($a, $b) {
            return strcmp($a->make, $b->make);
        });
        break;
    case 'mileage':
        usort($trucks, function($a, $b) {
            return $b->current_mileage - $a->current_mileage;
        });
        break;
}
?>

<div class="tmm-frontend-container">
    <div class="tmm-frontend-header">
        <h1 class="tmm-frontend-title">Truck Fleet</h1>
        
        <div class="tmm-frontend-controls">
            <input type="text" id="tmm-frontend-search" class="tmm-frontend-search" 
                   placeholder="Search trucks..." value="<?php echo esc_attr($search_term); ?>">
            
            <select id="tmm-frontend-sort" class="tmm-frontend-sort">
                <option value="urgency" <?php selected($sort_by, 'urgency'); ?>>Sort by Urgency</option>
                <option value="year" <?php selected($sort_by, 'year'); ?>>Sort by Year</option>
                <option value="make" <?php selected($sort_by, 'make'); ?>>Sort by Make</option>
                <option value="mileage" <?php selected($sort_by, 'mileage'); ?>>Sort by Mileage</option>
            </select>
            
            <?php if ($show_add_button): ?>
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>" class="tmm-frontend-btn">Add Truck</a>
            <?php endif; ?>
            
            <?php if (current_user_can('manage_options')): ?>
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance'); ?>" class="tmm-frontend-btn secondary">Admin Panel</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (empty($trucks)): ?>
        <div class="tmm-frontend-card">
            <div class="tmm-frontend-empty-state">
                <h3>No trucks found</h3>
                <?php if ($search_term): ?>
                    <p>No trucks match your search criteria. Try a different search term.</p>
                <?php else: ?>
                    <p>You don't have access to any trucks yet.</p>
                <?php endif; ?>
                
                <?php if (current_user_can('manage_options')): ?>
                    <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
                        <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>" class="tmm-frontend-btn">Add Your First Truck</a>
                        <a href="<?php echo admin_url('admin.php?page=truck-maintenance-import'); ?>" class="tmm-frontend-btn secondary">Import Trucks</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="tmm-frontend-truck-list">
            <?php foreach ($trucks as $truck): 
                $urgency_score = tmm_calculate_urgency_score($truck);
                $bg_class = tmm_get_urgency_background_color($urgency_score);
                $border_class = tmm_get_urgency_border_color($urgency_score);
                $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
                
                // Get maintenance status for each type
                $maintenance_types = array(
                    'oil' => array('label' => 'Oil Change', 'interval' => $truck->oil_change_interval ?: 5000, 'icon' => '🛢️'),
                    'airFilter' => array('label' => 'Air Filter', 'interval' => $truck->air_filter_interval ?: 15000, 'icon' => '🔧'),
                    'fuelFilter' => array('label' => 'Fuel Filter', 'interval' => $truck->fuel_filter_interval ?: 25000, 'icon' => '⛽'),
                    'dpfCleaning' => array('label' => 'DPF Cleaning', 'interval' => $truck->dpf_cleaning_interval ?: 100000, 'icon' => '💨'),
                    'safetyInspection' => array('label' => 'Safety Inspection', 'interval' => 0, 'icon' => '📋')
                );
            ?>
                <div class="tmm-frontend-truck-item <?php echo $bg_class . ' ' . $border_class; ?>" 
                     data-urgency-score="<?php echo $urgency_score; ?>" 
                     data-mileage="<?php echo $truck->current_mileage; ?>"
                     data-truck-id="<?php echo $truck->id; ?>">
                    
                    <div class="tmm-frontend-truck-header">
                        <div class="tmm-frontend-truck-info">
                            <h4>
                                <span class="tmm-frontend-truck-year"><?php echo esc_html($truck->year); ?></span>
                                <span class="tmm-frontend-truck-make"><?php echo esc_html($truck->make); ?></span>
                                <?php echo esc_html($truck->model); ?>
                            </h4>
                            
                            <!-- PROMINENT TOP SECTION -->
                            <div class="tmm-frontend-truck-info">
                                <div class="tmm-frontend-info-item">
                                    <span class="tmm-info-label">VIN:</span>
                                    <span class="tmm-info-value"><?php echo esc_html($truck->vin); ?></span>
                                </div>
                                
                                <?php if ($truck->unit_number): ?>
                                <div class="tmm-frontend-info-item">
                                    <span class="tmm-info-label">Unit:</span>
                                    <span class="tmm-info-value"><?php echo esc_html($truck->unit_number); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="tmm-frontend-info-item">
                                    <span class="tmm-info-label"><?php echo $truck->distance_unit === 'miles' ? 'Mileage:' : 'Distance:'; ?></span>
                                    <span class="tmm-info-value tmm-mileage-display"><?php echo number_format($truck->current_mileage) . ' ' . $truck->distance_unit; ?></span>
                                </div>
                            </div>
                            
                            <p class="tmm-frontend-urgency-score">Urgency Score: <?php echo $urgency_score; ?>/100</p>
                        </div>
                        
                        <div class="tmm-frontend-truck-actions">
                            <button class="tmm-frontend-btn small tmm-frontend-generate-qr" data-truck-id="<?php echo $truck->id; ?>">QR Code</button>
                        </div>
                    </div>
                    
                    <div class="tmm-frontend-maintenance-grid">
                        <?php foreach ($maintenance_types as $type => $config): 
                            if ($type !== 'safetyInspection' && !$config['interval']) continue;
                            
                            $record = null;
                            foreach ($maintenance_history as $hist) {
                                if ($hist['type'] === $type) {
                                    $record = $hist;
                                    break;
                                }
                            }
                            
                            $status = 'unknown';
                            $last_date = 'Never';
                            $last_mileage = 'N/A';
                            $next_info = '';
                            
                            if ($type === 'safetyInspection') {
                                // Handle safety inspection separately
                                if ($truck->safety_inspection_date) {
                                    $last_date = date('M j, Y', strtotime($truck->safety_inspection_date));
                                }
                                
                                if ($truck->safety_inspection_expiry) {
                                    $expiry_date = new DateTime($truck->safety_inspection_expiry);
                                    $today = new DateTime();
                                    $thirty_days = new DateTime();
                                    $thirty_days->modify('+30 days');
                                    
                                    if ($today > $expiry_date) {
                                        $status = 'due';
                                        $next_info = 'Expired on ' . $expiry_date->format('M j, Y') . ' <span class="tmm-frontend-maintenance-card-overdue">EXPIRED</span>';
                                    } elseif ($thirty_days > $expiry_date) {
                                        $status = 'approaching';
                                        $next_info = 'Expires on ' . $expiry_date->format('M j, Y') . ' <span class="tmm-frontend-maintenance-card-soon">SOON</span>';
                                    } else {
                                        $status = 'ok';
                                        $next_info = 'Expires on ' . $expiry_date->format('M j, Y');
                                    }
                                } else {
                                    $status = 'due';
                                    $next_info = 'N/A <span class="tmm-frontend-maintenance-card-overdue">NEEDED</span>';
                                }
                            } else {
                                // Handle regular maintenance
                                if ($record) {
                                    $last_date = date('M j, Y', strtotime($record['date']));
                                    $last_mileage = number_format($record['mileage']);
                                    
                                    $next_due_mileage = $record['mileage'] + $config['interval'];
                                    $approaching_threshold = ($truck->distance_unit === 'miles') ? 500 : 800;
                                    
                                    if ($truck->current_mileage >= $next_due_mileage) {
                                        $status = 'due';
                                        $overdue_miles = $truck->current_mileage - $next_due_mileage;
                                        $next_info = 'Due at ' . number_format($next_due_mileage) . ' ' . $truck->distance_unit . 
                                                   ' <span class="tmm-frontend-maintenance-card-overdue">(' . number_format($overdue_miles) . ' ' . $truck->distance_unit . ' OVERDUE)</span>';
                                    } elseif ($truck->current_mileage + $approaching_threshold >= $next_due_mileage) {
                                        $status = 'approaching';
                                        $next_info = 'Due at ' . number_format($next_due_mileage) . ' ' . $truck->distance_unit . 
                                                   ' <span class="tmm-frontend-maintenance-card-soon">SOON</span>';
                                    } else {
                                        $status = 'ok';
                                        $next_info = 'Due at ' . number_format($next_due_mileage) . ' ' . $truck->distance_unit;
                                    }
                                } else {
                                    $status = 'due';
                                    $next_info = 'Initial service needed <span class="tmm-frontend-maintenance-card-overdue">OVERDUE</span>';
                                }
                            }
                        ?>
                            <div class="tmm-frontend-maintenance-card status-<?php echo $status; ?>" 
                                 data-maintenance-type="<?php echo $type; ?>" 
                                 data-truck-id="<?php echo $truck->id; ?>">
                                
                                <div class="tmm-frontend-maintenance-card-header">
                                    <span class="tmm-frontend-maintenance-card-icon"><?php echo $config['icon']; ?></span>
                                    <h5 class="tmm-frontend-maintenance-card-title"><?php echo esc_html($config['label']); ?></h5>
                                </div>
                                
                                <div class="tmm-frontend-maintenance-card-content">
                                    <p>Last: <?php echo esc_html($last_date); ?></p>
                                    <?php if ($type !== 'safetyInspection'): ?>
                                        <p>At: <?php echo esc_html($last_mileage . ' ' . $truck->distance_unit); ?></p>
                                    <?php endif; ?>
                                    <?php if ($next_info): ?>
                                        <p class="tmm-frontend-maintenance-card-next"><?php echo $next_info; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
