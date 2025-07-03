<?php
if (!defined('ABSPATH')) {
    exit;
}

$urgency_score = tmm_calculate_urgency_score($truck);
$bg_class = tmm_get_urgency_background_color($urgency_score);
$border_class = tmm_get_urgency_border_color($urgency_score);
$maintenance_history = json_decode($truck->maintenance_history, true) ?: array();

// Sort maintenance history by date (newest first)
usort($maintenance_history, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>

<div class="tmm-container">
    <div class="tmm-header">
        <h1>🚛 <?php echo esc_html($truck->year . ' ' . $truck->make . ' ' . $truck->model); ?></h1>
        <div class="tmm-header-actions">
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add&edit=1&truck_id=' . $truck->id); ?>" class="tmm-btn tmm-btn-primary">
                <span class="dashicons dashicons-edit"></span> Edit Truck
            </a>
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-btn tmm-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span> Back to Fleet
            </a>
        </div>
    </div>
    
    <div class="tmm-truck-details <?php echo $bg_class . ' ' . $border_class; ?>">
        <div class="tmm-truck-overview">
            <div class="tmm-truck-info">
                <div class="tmm-info-group">
                    <label>Unit Number:</label>
                    <span><?php echo esc_html($truck->unit_number ?: 'Not assigned'); ?></span>
                </div>
                
                <div class="tmm-info-group">
                    <label>VIN:</label>
                    <span><?php echo esc_html($truck->vin); ?></span>
                </div>
                
                <div class="tmm-info-group">
                    <label>Current Mileage:</label>
                    <span class="tmm-mileage-display"><?php echo number_format($truck->current_mileage) . ' ' . $truck->distance_unit; ?></span>
                    <button class="tmm-btn tmm-btn-small tmm-update-mileage" data-truck-id="<?php echo $truck->id; ?>">Update</button>
                </div>
                
                <div class="tmm-info-group">
                    <label>Safety Inspection:</label>
                    <span><?php echo $truck->safety_inspection_expiry ? date('M j, Y', strtotime($truck->safety_inspection_expiry)) : 'Not set'; ?></span>
                </div>
            </div>
            
            <div class="tmm-urgency-display">
                <div class="tmm-urgency-circle" data-score="<?php echo $urgency_score; ?>">
                    <span class="tmm-urgency-number"><?php echo $urgency_score; ?></span>
                    <span class="tmm-urgency-label">Urgency Score</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tmm-dashboard-grid">
        <div class="tmm-card">
            <div class="tmm-card-header">
                <h2>🔧 Maintenance Status</h2>
                <button class="tmm-btn tmm-btn-primary tmm-maintenance-btn" data-truck-id="<?php echo $truck->id; ?>">
                    <span class="dashicons dashicons-plus"></span> Add Service
                </button>
            </div>
            
            <div class="tmm-card-content">
                <div class="tmm-maintenance-grid">
                    <?php
                    $maintenance_types = array(
                        'oil' => array('label' => 'Oil Change', 'interval' => $truck->oil_change_interval ?: 5000, 'icon' => '🛢️'),
                        'oilFilter' => array('label' => 'Oil Filter', 'interval' => $truck->oil_filter_interval ?: 5000, 'icon' => '🔧'),
                        'airFilter' => array('label' => 'Air Filter', 'interval' => $truck->air_filter_interval ?: 15000, 'icon' => '🌬️'),
                        'fuelFilter' => array('label' => 'Fuel Filter', 'interval' => $truck->fuel_filter_interval ?: 25000, 'icon' => '⛽'),
                        'dpfCleaning' => array('label' => 'DPF Cleaning', 'interval' => $truck->dpf_cleaning_interval ?: 100000, 'icon' => '💨'),
                        'safetyInspection' => array('label' => 'Safety Inspection', 'interval' => 0, 'icon' => '📋')
                    );
                    
                    foreach ($maintenance_types as $type => $config):
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
                    ?>
                        <div class="tmm-maintenance-card <?php echo $status_class; ?>" data-maintenance-type="<?php echo $type; ?>">
                            <div class="tmm-maintenance-card-header">
                                <span class="tmm-maintenance-icon"><?php echo $config['icon']; ?></span>
                                <h4><?php echo esc_html($config['label']); ?></h4>
                                <span class="tmm-status-badge tmm-status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                            </div>
                            
                            <div class="tmm-maintenance-card-content">
                                <div class="tmm-maintenance-info">
                                    <p><strong>Last Service:</strong> <?php echo esc_html($last_date); ?></p>
                                    <?php if ($type !== 'safetyInspection'): ?>
                                        <p><strong>At Mileage:</strong> <?php echo esc_html($last_mileage . ' ' . $truck->distance_unit); ?></p>
                                        <p><strong>Interval:</strong> <?php echo number_format($config['interval']) . ' ' . $truck->distance_unit; ?></p>
                                    <?php endif; ?>
                                    <p><strong>Status:</strong> <?php echo esc_html($next_info); ?></p>
                                </div>
                                
                                <button class="tmm-btn tmm-btn-small tmm-maintenance-btn" data-truck-id="<?php echo $truck->id; ?>" data-maintenance-type="<?php echo $type; ?>">
                                    Update
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="tmm-card">
            <div class="tmm-card-header">
                <h2>📋 Maintenance History</h2>
            </div>
            
            <div class="tmm-card-content">
                <?php if (empty($maintenance_history)): ?>
                    <div class="tmm-no-data">
                        <span class="dashicons dashicons-clipboard"></span>
                        <p>No maintenance records yet</p>
                        <button class="tmm-btn tmm-btn-primary tmm-maintenance-btn" data-truck-id="<?php echo $truck->id; ?>">
                            Add First Service Record
                        </button>
                    </div>
                <?php else: ?>
                    <div class="tmm-history-list">
                        <?php foreach ($maintenance_history as $record): 
                            $type_labels = array(
                                'oil' => 'Oil Change',
                                'oilFilter' => 'Oil Filter Change',
                                'airFilter' => 'Air Filter Change',
                                'fuelFilter' => 'Fuel Filter Change',
                                'dpfCleaning' => 'DPF Cleaning',
                                'safetyInspection' => 'Safety Inspection'
                            );
                            
                            $type_icons = array(
                                'oil' => '🛢️',
                                'oilFilter' => '🔧',
                                'airFilter' => '🌬️',
                                'fuelFilter' => '⛽',
                                'dpfCleaning' => '💨',
                                'safetyInspection' => '📋'
                            );
                        ?>
                            <div class="tmm-history-item">
                                <div class="tmm-history-icon">
                                    <?php echo $type_icons[$record['type']] ?? '🔧'; ?>
                                </div>
                                
                                <div class="tmm-history-content">
                                    <h4><?php echo esc_html($type_labels[$record['type']] ?? $record['type']); ?></h4>
                                    <p class="tmm-history-date"><?php echo date('M j, Y', strtotime($record['date'])); ?></p>
                                    <p class="tmm-history-mileage"><?php echo number_format($record['mileage']) . ' ' . $truck->distance_unit; ?></p>
                                    <?php if (!empty($record['notes'])): ?>
                                        <p class="tmm-history-notes"><?php echo esc_html($record['notes']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($record['nextDate'])): ?>
                                        <p class="tmm-history-next">Next due: <?php echo date('M j, Y', strtotime($record['nextDate'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Modal -->
<div id="tmm-maintenance-modal" class="tmm-modal" style="display: none;">
    <div class="tmm-modal-content">
        <div class="tmm-modal-header">
            <h3>Add Maintenance Record</h3>
            <span class="tmm-close">&times;</span>
        </div>
        <form id="tmm-maintenance-form">
            <input type="hidden" id="tmm-truck-id" name="truck_id" value="<?php echo $truck->id; ?>">
            
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
            <input type="hidden" id="tmm-mileage-truck-id" name="truck_id" value="<?php echo $truck->id; ?>">
            
            <div class="tmm-form-group">
                <label for="tmm-new-mileage">Current Mileage:</label>
                <input type="number" id="tmm-new-mileage" name="mileage" min="<?php echo $truck->current_mileage; ?>" 
                       value="<?php echo $truck->current_mileage; ?>" required>
                <small>Current: <?php echo number_format($truck->current_mileage) . ' ' . $truck->distance_unit; ?></small>
            </div>
            
            <div class="tmm-form-actions">
                <button type="submit" class="tmm-btn tmm-btn-primary">Update Mileage</button>
                <button type="button" class="tmm-btn tmm-btn-secondary tmm-close">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Truck details page loaded - initializing event handlers');
    
    // Initialize event handlers for this page
    function initializeTruckDetailsHandlers() {
        // Maintenance modal handlers
        $('.tmm-maintenance-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Add Service button clicked!');
            
            var truckId = $(this).data('truck-id');
            var maintenanceType = $(this).data('maintenance-type') || '';
            
            console.log('Truck ID:', truckId, 'Maintenance Type:', maintenanceType);
            
            // Reset form
            $('#tmm-maintenance-form')[0].reset();
            $('#tmm-truck-id').val(truckId);
            
            if (maintenanceType) {
                $('#tmm-maintenance-type').val(maintenanceType);
            }
            
            // Set today's date as default
            var today = new Date().toISOString().split('T')[0];
            $('#tmm-maintenance-date').val(today);
            
            // Get current truck mileage and set as default
            var currentMileage = $('.tmm-mileage-display').text().replace(/[^\d]/g, '');
            if (currentMileage) {
                $('#tmm-maintenance-mileage').val(currentMileage);
            }
            
            // Calculate next due date
            calculateNextDueDate();
            
            // Show modal
            $('#tmm-maintenance-modal').show();
            console.log('Modal should be visible now');
        });
        
        // Mileage update handlers
        $('.tmm-update-mileage').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Update mileage button clicked!');
            
            var truckId = $(this).data('truck-id');
            $('#tmm-mileage-truck-id').val(truckId);
            
            // Get current mileage and set as default
            var currentMileage = $('.tmm-mileage-display').text().replace(/[^\d]/g, '');
            if (currentMileage) {
                $('#tmm-new-mileage').val(currentMileage);
            }
            
            // Show modal
            $('#tmm-mileage-modal').show();
        });
        
        // Modal close handlers
        $('.tmm-close').off('click').on('click', function() {
            $('.tmm-modal').hide();
        });
        
        // Close modal when clicking outside
        $(window).off('click.tmm').on('click.tmm', function(event) {
            if ($(event.target).hasClass('tmm-modal')) {
                $('.tmm-modal').hide();
            }
        });
    }
    
    function calculateNextDueDate() {
        var type = $('#tmm-maintenance-type').val();
        var currentDate = new Date($('#tmm-maintenance-date').val() || new Date());
        var nextDate = new Date(currentDate);
        
        // Add typical intervals
        switch(type) {
            case 'oil':
                nextDate.setMonth(nextDate.getMonth() + 3); // 3 months
                break;
            case 'oilFilter':
                nextDate.setMonth(nextDate.getMonth() + 3); // 3 months
                break;
            case 'airFilter':
                nextDate.setMonth(nextDate.getMonth() + 6); // 6 months
                break;
            case 'fuelFilter':
                nextDate.setMonth(nextDate.getMonth() + 12); // 12 months
                break;
            case 'dpfCleaning':
                nextDate.setMonth(nextDate.getMonth() + 24); // 24 months
                break;
            case 'safetyInspection':
                nextDate.setFullYear(nextDate.getFullYear() + 1); // 1 year
                break;
        }
        
        if (type) {
            $('#tmm-next-due-date').val(nextDate.toISOString().split('T')[0]);
        }
    }
    
    // Auto-calculate next due date based on maintenance type
    $('#tmm-maintenance-type').on('change', calculateNextDueDate);
    $('#tmm-maintenance-date').on('change', calculateNextDueDate);
    
    // Form submissions
    $('#tmm-maintenance-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        console.log('Maintenance form submitted');
        
        var formData = $(this).serialize();
        formData += '&action=tmm_save_maintenance&nonce=' + tmm_ajax.nonce;
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');
            },
            success: function(response) {
                console.log('Maintenance save response:', response);
                if (response.success) {
                    alert('Maintenance record saved successfully!');
                    $('.tmm-modal').hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error saving maintenance record. Please try again.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    $('#tmm-mileage-form').off('submit').on('submit', function(e) {
        e.preventDefault();
        console.log('Mileage form submitted');
        
        var formData = $(this).serialize();
        formData += '&action=tmm_update_mileage&nonce=' + tmm_ajax.nonce;
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Updating...');
            },
            success: function(response) {
                console.log('Mileage update response:', response);
                if (response.success) {
                    alert('Mileage updated successfully!');
                    $('.tmm-modal').hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error updating mileage. Please try again.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Initialize handlers
    initializeTruckDetailsHandlers();
    
    // Update urgency circle colors
    $('.tmm-urgency-circle').each(function() {
        var score = parseInt($(this).data('score'));
        var color;
        
        if (score >= 80) {
            color = 'linear-gradient(135deg, #dc3545, #b02a37)';
        } else if (score >= 60) {
            color = 'linear-gradient(135deg, #fd7e14, #e8590c)';
        } else if (score >= 40) {
            color = 'linear-gradient(135deg, #ffc107, #e0a800)';
        } else if (score >= 20) {
            color = 'linear-gradient(135deg, #28a745, #1e7e34)';
        } else {
            color = 'linear-gradient(135deg, #17a2b8, #138496)';
        }
        
        $(this).css('background', color);
    });
});
</script>
