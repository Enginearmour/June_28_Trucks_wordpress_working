<?php
if (!defined('ABSPATH')) {
    exit;
}

// Calculate statistics
$total_trucks = count($trucks);
$maintenance_due = 0;

foreach ($trucks as $truck) {
    $urgency_score = tmm_calculate_urgency_score($truck);
    if ($urgency_score > 0) {
        $maintenance_due++;
    }
}

// Sort trucks by urgency
usort($trucks, function($a, $b) {
    return tmm_calculate_urgency_score($b) - tmm_calculate_urgency_score($a);
});
?>

<div class="tmm-frontend-container">
    <div class="tmm-frontend-header">
        <h1>🚛 Truck Maintenance Dashboard</h1>
        <div class="tmm-frontend-stats">
            <div class="tmm-frontend-stat">
                <span class="tmm-stat-number"><?php echo $total_trucks; ?></span>
                <span class="tmm-stat-label">Total Trucks</span>
            </div>
            <div class="tmm-frontend-stat tmm-stat-warning">
                <span class="tmm-stat-number"><?php echo $maintenance_due; ?></span>
                <span class="tmm-stat-label">Need Maintenance</span>
            </div>
        </div>
    </div>
    
    <?php if (empty($trucks)): ?>
        <div class="tmm-card">
            <div class="tmm-card-content">
                <div class="tmm-no-data">
                    <span class="dashicons dashicons-car"></span>
                    <h3>No trucks in your fleet yet</h3>
                    <p>Contact your administrator to add trucks to the system.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="tmm-dashboard-grid">
            <?php foreach ($trucks as $truck): 
                $urgency_score = tmm_calculate_urgency_score($truck);
                $bg_class = tmm_get_urgency_background_color($urgency_score);
                $border_class = tmm_get_urgency_border_color($urgency_score);
                $maintenance_history = json_decode($truck->maintenance_history, true) ?: array();
                
                // Prepare maintenance data
                $maintenance_types = array(
                    'oil' => array('label' => 'Oil Change', 'interval' => $truck->oil_change_interval ?: 5000, 'icon' => '🛢️'),
                    'oilFilter' => array('label' => 'Oil Filter', 'interval' => $truck->oil_filter_interval ?: 5000, 'icon' => '🔧'),
                    'airFilter' => array('label' => 'Air Filter', 'interval' => $truck->air_filter_interval ?: 15000, 'icon' => '🌬️'),
                    'fuelFilter' => array('label' => 'Fuel Filter', 'interval' => $truck->fuel_filter_interval ?: 25000, 'icon' => '⛽'),
                    'dpfCleaning' => array('label' => 'DPF Cleaning', 'interval' => $truck->dpf_cleaning_interval ?: 100000, 'icon' => '💨'),
                    'safetyInspection' => array('label' => 'Safety Inspection', 'interval' => 0, 'icon' => '📋')
                );
                
                $maintenance_cards = array();
                
                // Process each maintenance type
                foreach ($maintenance_types as $type => $config) {
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
                    $status_class = 'tmm-status-unknown';
                    
                    if ($type === 'safetyInspection') {
                        if ($truck->safety_inspection_expiry) {
                            $expiry_date = new DateTime($truck->safety_inspection_expiry);
                            $today = new DateTime();
                            $thirty_days = new DateTime();
                            $thirty_days->modify('+30 days');
                            
                            if ($today > $expiry_date) {
                                $status = 'overdue';
                                $status_class = 'tmm-status-overdue';
                                $next_info = 'Expired on ' . $expiry_date->format('M j, Y');
                            } elseif ($thirty_days > $expiry_date) {
                                $status = 'approaching';
                                $status_class = 'tmm-status-approaching';
                                $next_info = 'Expires on ' . $expiry_date->format('M j, Y');
                            } else {
                                $status = 'ok';
                                $status_class = 'tmm-status-ok';
                                $next_info = 'Expires on ' . $expiry_date->format('M j, Y');
                            }
                        } else {
                            $status = 'due';
                            $status_class = 'tmm-status-due';
                            $next_info = 'Not scheduled';
                        }
                    } else {
                        if ($record) {
                            $last_date = date('M j, Y', strtotime($record['date']));
                            $last_mileage = number_format($record['mileage']);
                            
                            $next_due_mileage = $record['mileage'] + $config['interval'];
                            $approaching_threshold = ($truck->distance_unit === 'miles') ? 500 : 800;
                            
                            if ($truck->current_mileage >= $next_due_mileage) {
                                $status = 'overdue';
                                $status_class = 'tmm-status-overdue';
                                $overdue_miles = $truck->current_mileage - $next_due_mileage;
                                $next_info = number_format($overdue_miles) . ' ' . $truck->distance_unit . ' overdue';
                            } elseif ($truck->current_mileage + $approaching_threshold >= $next_due_mileage) {
                                $status = 'approaching';
                                $status_class = 'tmm-status-approaching';
                                $remaining_miles = $next_due_mileage - $truck->current_mileage;
                                $next_info = number_format($remaining_miles) . ' ' . $truck->distance_unit . ' remaining';
                            } else {
                                $status = 'ok';
                                $status_class = 'tmm-status-ok';
                                $remaining_miles = $next_due_mileage - $truck->current_mileage;
                                $next_info = number_format($remaining_miles) . ' ' . $truck->distance_unit . ' remaining';
                            }
                        } else {
                            $status = 'due';
                            $status_class = 'tmm-status-due';
                            $next_info = 'Initial service needed';
                        }
                    }
                    
                    $maintenance_cards[] = array(
                        'type' => $type,
                        'label' => $config['label'],
                        'icon' => $config['icon'],
                        'status' => $status,
                        'status_class' => $status_class,
                        'last_date' => $last_date,
                        'last_mileage' => $last_mileage,
                        'next_info' => $next_info,
                        'record' => $record,
                        'interval' => $config['interval']
                    );
                }
            ?>
                <div class="tmm-truck-item <?php echo $bg_class . ' ' . $border_class; ?>" 
                     data-truck-id="<?php echo $truck->id; ?>" 
                     data-urgency-score="<?php echo $urgency_score; ?>">
                    
                    <div class="tmm-expand-indicator">▼</div>
                    
                    <div class="tmm-truck-header">
                        <div class="tmm-truck-info">
                            <h4>
                                <?php if ($truck->unit_number): ?>
                                    Unit <?php echo esc_html($truck->unit_number); ?> - 
                                <?php endif; ?>
                                <?php echo esc_html($truck->year . ' ' . $truck->make . ' ' . $truck->model); ?>
                            </h4>
                            <p class="tmm-vin">VIN: <?php echo esc_html($truck->vin); ?></p>
                            <p class="tmm-mileage-display"><?php echo number_format($truck->current_mileage) . ' ' . $truck->distance_unit; ?></p>
                        </div>
                        
                        <div class="tmm-urgency-circle" data-score="<?php echo $urgency_score; ?>">
                            <span class="tmm-urgency-number"><?php echo $urgency_score; ?></span>
                            <span class="tmm-urgency-label">Urgency</span>
                        </div>
                    </div>
                    
                    <div class="tmm-truck-actions">
                        <button class="tmm-btn tmm-btn-small tmm-maintenance-btn" data-truck-id="<?php echo $truck->id; ?>">
                            <span class="dashicons dashicons-admin-tools"></span> Add Service
                        </button>
                        
                        <button class="tmm-btn tmm-btn-small tmm-update-mileage" data-truck-id="<?php echo $truck->id; ?>">
                            <span class="dashicons dashicons-edit"></span> Update Mileage
                        </button>
                        
                        <button class="tmm-btn tmm-btn-small tmm-details-btn" data-truck-id="<?php echo $truck->id; ?>">
                            <span class="dashicons dashicons-visibility"></span> View Details
                        </button>
                    </div>
                    
                    <!-- Expandable Content -->
                    <div class="tmm-expandable-content">
                        <div class="tmm-maintenance-grid">
                            <?php foreach ($maintenance_cards as $card): ?>
                                <div class="tmm-maintenance-card">
                                    <div class="tmm-maintenance-card-header">
                                        <span class="tmm-maintenance-icon"><?php echo $card['icon']; ?></span>
                                        <h5><?php echo esc_html($card['label']); ?></h5>
                                        <span class="tmm-status-badge <?php echo $card['status_class']; ?>">
                                            <?php echo strtoupper($card['status']); ?>
                                        </span>
                                    </div>
                                    <div class="tmm-maintenance-card-content">
                                        <div class="tmm-maintenance-info">
                                            <p><strong>Last Service:</strong> <?php echo esc_html($card['last_date']); ?></p>
                                            <?php if ($card['type'] !== 'safetyInspection'): ?>
                                                <p><strong>At Mileage:</strong> <?php echo esc_html($card['last_mileage'] . ' ' . $truck->distance_unit); ?></p>
                                                <p><strong>Interval:</strong> <?php echo number_format($card['interval']) . ' ' . $truck->distance_unit; ?></p>
                                            <?php endif; ?>
                                            <p><strong>Status:</strong> <?php echo esc_html($card['next_info']); ?></p>
                                            <?php if ($card['record'] && !empty($card['record']['notes'])): ?>
                                                <p><strong>Notes:</strong> <?php echo esc_html($card['record']['notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <button class="tmm-btn tmm-btn-small tmm-maintenance-btn" 
                                                data-truck-id="<?php echo $truck->id; ?>" 
                                                data-maintenance-type="<?php echo $card['type']; ?>">
                                            Update
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Maintenance Modal -->
<div id="tmm-maintenance-modal" class="tmm-modal" style="display: none;">
    <div class="tmm-modal-content">
        <div class="tmm-modal-header">
            <h3>Add Maintenance Record</h3>
            <span class="tmm-close">&times;</span>
        </div>
        <form id="tmm-maintenance-form">
            <input type="hidden" id="tmm-truck-id" name="truck_id">
            
            <div class="tmm-form-group">
                <label for="tmm-maintenance-type">Maintenance Type:</label>
                <select id="tmm-maintenance-type" name="maintenance_type" required>
                    <option value="">Select maintenance type</option>
                    <option value="oil">Oil Change</option>
                    <option value="oilFilter">Oil Filter Change</option>
                    <option value="airFilter">Air Filter Change</option>
                    <option value="fuelFilter">Fuel Filter Change</option>
                    <option value="dpfCleaning">DPF Cleaning</option>
                    <option value="safetyInspection">Safety Inspection</option>
                </select>
            </div>
            
            <div class="tmm-form-group">
                <label for="tmm-maintenance-date">Service Date:</label>
                <input type="date" id="tmm-maintenance-date" name="maintenance_date" required>
            </div>
            
            <div class="tmm-form-group">
                <label for="tmm-maintenance-mileage">Mileage at Service:</label>
                <input type="number" id="tmm-maintenance-mileage" name="maintenance_mileage" min="0" required>
            </div>
            
            <div class="tmm-form-group">
                <label for="tmm-next-due-date">Next Due Date:</label>
                <input type="date" id="tmm-next-due-date" name="next_due_date">
            </div>
            
            <div class="tmm-form-group">
                <label for="tmm-maintenance-notes">Notes:</label>
                <textarea id="tmm-maintenance-notes" name="maintenance_notes" rows="3" placeholder="Optional notes about the service"></textarea>
            </div>
            
            <div class="tmm-form-actions">
                <button type="submit" class="tmm-btn tmm-btn-primary">Save Maintenance Record</button>
                <button type="button" class="tmm-btn tmm-btn-secondary tmm-close">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Mileage Update Modal -->
<div id="tmm-mileage-modal" class="tmm-modal" style="display: none;">
    <div class="tmm-modal-content">
        <div class="tmm-modal-header">
            <h3>Update Mileage</h3>
            <span class="tmm-close">&times;</span>
        </div>
        <form id="tmm-mileage-form">
            <input type="hidden" id="tmm-mileage-truck-id" name="truck_id">
            
            <div class="tmm-form-group">
                <label for="tmm-new-mileage">Current Mileage:</label>
                <input type="number" id="tmm-new-mileage" name="mileage" min="0" required>
            </div>
            
            <div class="tmm-form-actions">
                <button type="submit" class="tmm-btn tmm-btn-primary">Update Mileage</button>
                <button type="button" class="tmm-btn tmm-btn-secondary tmm-close">Cancel</button>
            </div>
        </form>
    </div>
</div>
