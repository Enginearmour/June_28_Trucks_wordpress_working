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

<div class="tmm-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
        <h1>Truck Fleet</h1>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <input type="text" id="tmm-search" placeholder="Search trucks..." value="<?php echo esc_attr($search_term); ?>" 
                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
            
            <select id="tmm-sort" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="urgency" <?php selected($sort_by, 'urgency'); ?>>Sort by Urgency</option>
                <option value="year" <?php selected($sort_by, 'year'); ?>>Sort by Year</option>
                <option value="make" <?php selected($sort_by, 'make'); ?>>Sort by Make</option>
                <option value="mileage" <?php selected($sort_by, 'mileage'); ?>>Sort by Mileage</option>
            </select>
            
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>" class="tmm-btn">Add Truck</a>
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-import'); ?>" class="tmm-btn secondary">Import</a>
        </div>
    </div>
    
    <?php if (empty($trucks)): ?>
        <div class="tmm-card" style="text-align: center; padding: 60px 20px;">
            <h2 style="margin-bottom: 10px; color: #333;">No trucks found</h2>
            <?php if ($search_term): ?>
                <p style="color: #666; margin-bottom: 30px;">No trucks match your search criteria. Try a different search term.</p>
            <?php else: ?>
                <p style="color: #666; margin-bottom: 30px;">You haven't added any trucks to your fleet yet.</p>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>" class="tmm-btn">Add Your First Truck</a>
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-import'); ?>" class="tmm-btn secondary">Import Trucks</a>
            </div>
        </div>
    <?php else: ?>
        <div class="tmm-truck-list">
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
                <div class="tmm-truck-item <?php echo $bg_class . ' ' . $border_class; ?>" 
                     data-urgency-score="<?php echo $urgency_score; ?>" 
                     data-mileage="<?php echo $truck->current_mileage; ?>">
                    
                    <div class="tmm-truck-header">
                        <div class="tmm-truck-info">
                            <h4>
                                <span class="tmm-truck-year"><?php echo esc_html($truck->year); ?></span>
                                <span class="tmm-truck-make"><?php echo esc_html($truck->make); ?></span>
                                <?php echo esc_html($truck->model); ?>
                            </h4>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                <p>VIN: <?php echo esc_html($truck->vin); ?></p>
                                <?php if ($truck->unit_number): ?>
                                    <p class="tmm-unit-number">Unit: <?php echo esc_html($truck->unit_number); ?></p>
                                <?php endif; ?>
                            </div>
                            <p style="font-weight: 600; margin-top: 5px;">
                                Current <?php echo $truck->distance_unit === 'miles' ? 'Mileage' : 'Distance'; ?>: 
                                <?php echo number_format($truck->current_mileage) . ' ' . $truck->distance_unit; ?>
                            </p>
                            <p class="tmm-urgency-score">Urgency Score: <?php echo $urgency_score; ?>/100</p>
                        </div>
                        
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list&truck_id=' . $truck->id); ?>" class="tmm-view-btn">View Details</a>
                            <button class="tmm-generate-qr tmm-btn secondary" data-truck-id="<?php echo $truck->id; ?>" style="padding: 8px 12px;">QR Code</button>
                        </div>
                    </div>
                    
                    <div class="tmm-maintenance-grid">
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
                                        $next_info = 'Expired on ' . $expiry_date->format('M j, Y') . ' <span class="tmm-maintenance-card-overdue">EXPIRED</span>';
                                    } elseif ($thirty_days > $expiry_date) {
                                        $status = 'approaching';
                                        $next_info = 'Expires on ' . $expiry_date->format('M j, Y') . ' <span class="tmm-maintenance-card-soon">SOON</span>';
                                    } else {
                                        $status = 'ok';
                                        $next_info = 'Expires on ' . $expiry_date->format('M j, Y');
                                    }
                                } else {
                                    $status = 'due';
                                    $next_info = 'N/A <span class="tmm-maintenance-card-overdue">NEEDED</span>';
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
                                                   ' <span class="tmm-maintenance-card-overdue">(' . number_format($overdue_miles) . ' ' . $truck->distance_unit . ' OVERDUE)</span>';
                                    } elseif ($truck->current_mileage + $approaching_threshold >= $next_due_mileage) {
                                        $status = 'approaching';
                                        $next_info = 'Due at ' . number_format($next_due_mileage) . ' ' . $truck->distance_unit . 
                                                   ' <span class="tmm-maintenance-card-soon">SOON</span>';
                                    } else {
                                        $status = 'ok';
                                        $next_info = 'Due at ' . number_format($next_due_mileage) . ' ' . $truck->distance_unit;
                                    }
                                } else {
                                    $status = 'due';
                                    $next_info = 'Initial service needed <span class="tmm-maintenance-card-overdue">OVERDUE</span>';
                                }
                            }
                        ?>
                            <div class="tmm-maintenance-card status-<?php echo $status; ?> tmm-maintenance-card-clickable" 
                                 data-maintenance-type="<?php echo $type; ?>" 
                                 data-truck-id="<?php echo $truck->id; ?>">
                                
                                <div class="tmm-maintenance-card-header">
                                    <span class="tmm-maintenance-card-icon"><?php echo $config['icon']; ?></span>
                                    <h5 class="tmm-maintenance-card-title"><?php echo esc_html($config['label']); ?></h5>
                                </div>
                                
                                <div class="tmm-maintenance-card-content">
                                    <p>Last: <?php echo esc_html($last_date); ?></p>
                                    <?php if ($type !== 'safetyInspection'): ?>
                                        <p>At: <?php echo esc_html($last_mileage . ' ' . $truck->distance_unit); ?></p>
                                    <?php endif; ?>
                                    <?php if ($next_info): ?>
                                        <p class="tmm-maintenance-card-next"><?php echo $next_info; ?></p>
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

<script>
jQuery(document).ready(function($) {
    // Handle search
    $('#tmm-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.tmm-truck-item').each(function() {
            var truckText = $(this).text().toLowerCase();
            if (truckText.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Handle sort
    $('#tmm-sort').on('change', function() {
        var sortBy = $(this).val();
        var url = new URL(window.location);
        url.searchParams.set('sort', sortBy);
        window.location = url;
    });
    
    // Handle maintenance card clicks
    $('.tmm-maintenance-card-clickable').on('click', function() {
        var maintenanceType = $(this).data('maintenance-type');
        var truckId = $(this).data('truck-id');
        
        // Show maintenance form modal (this would be implemented in the main admin.js)
        if (typeof showMaintenanceModal === 'function') {
            showMaintenanceModal(truckId, maintenanceType);
        }
    });
});
</script>
