<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_edit = isset($truck) && $truck;
$page_title = $is_edit ? 'Edit Truck' : 'Add New Truck';
?>

<div class="tmm-container">
    <div class="tmm-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="tmm-header-actions">
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-btn tmm-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span> Back to Fleet
            </a>
        </div>
    </div>
    
    <div class="tmm-card">
        <div class="tmm-card-header">
            <h2><?php echo $is_edit ? 'Edit Truck Information' : 'Add New Truck'; ?></h2>
        </div>
        
        <div class="tmm-card-content">
            <form id="tmm-truck-form" method="post">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="truck_id" value="<?php echo $truck->id; ?>">
                <?php endif; ?>
                
                <div class="tmm-form-grid">
                    <div class="tmm-form-group">
                        <label for="unit_number">Unit Number:</label>
                        <input type="text" id="unit_number" name="unit_number" 
                               value="<?php echo $is_edit ? esc_attr($truck->unit_number) : ''; ?>" 
                               placeholder="e.g., TRUCK001">
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="vin">VIN: <span class="required">*</span></label>
                        <input type="text" id="vin" name="vin" required maxlength="17"
                               value="<?php echo $is_edit ? esc_attr($truck->vin) : ''; ?>" 
                               placeholder="17-character VIN">
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="year">Year: <span class="required">*</span></label>
                        <input type="number" id="year" name="year" required min="1900" max="<?php echo date('Y') + 1; ?>"
                               value="<?php echo $is_edit ? esc_attr($truck->year) : ''; ?>" 
                               placeholder="e.g., 2020">
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="make">Make: <span class="required">*</span></label>
                        <input type="text" id="make" name="make" required
                               value="<?php echo $is_edit ? esc_attr($truck->make) : ''; ?>" 
                               placeholder="e.g., Freightliner">
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="model">Model: <span class="required">*</span></label>
                        <input type="text" id="model" name="model" required
                               value="<?php echo $is_edit ? esc_attr($truck->model) : ''; ?>" 
                               placeholder="e.g., Cascadia">
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="current_mileage">Current Mileage: <span class="required">*</span></label>
                        <input type="number" id="current_mileage" name="current_mileage" required min="0"
                               value="<?php echo $is_edit ? esc_attr($truck->current_mileage) : ''; ?>" 
                               placeholder="e.g., 125000">
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="distance_unit">Distance Unit:</label>
                        <select id="distance_unit" name="distance_unit">
                            <option value="miles" <?php echo ($is_edit && $truck->distance_unit === 'miles') ? 'selected' : ''; ?>>Miles</option>
                            <option value="kilometers" <?php echo ($is_edit && $truck->distance_unit === 'kilometers') ? 'selected' : ''; ?>>Kilometers</option>
                        </select>
                    </div>
                    
                    <div class="tmm-form-group">
                        <label for="safety_inspection_expiry">Safety Inspection Expiry:</label>
                        <input type="date" id="safety_inspection_expiry" name="safety_inspection_expiry"
                               value="<?php echo $is_edit ? esc_attr($truck->safety_inspection_expiry) : ''; ?>">
                    </div>
                </div>
                
                <div class="tmm-maintenance-intervals">
                    <h3>Maintenance Intervals</h3>
                    <p class="tmm-help-text">Set the mileage intervals for each type of maintenance. Leave blank to use default values.</p>
                    
                    <div class="tmm-form-grid">
                        <div class="tmm-form-group">
                            <label for="oil_change_interval">Oil Change Interval:</label>
                            <input type="number" id="oil_change_interval" name="oil_change_interval" min="1000" max="50000"
                                   value="<?php echo $is_edit ? esc_attr($truck->oil_change_interval ?: 5000) : '5000'; ?>" 
                                   placeholder="5000">
                            <small>Default: 5,000 miles</small>
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="oil_filter_interval">Oil Filter Interval:</label>
                            <input type="number" id="oil_filter_interval" name="oil_filter_interval" min="1000" max="50000"
                                   value="<?php echo $is_edit ? esc_attr($truck->oil_filter_interval ?: 5000) : '5000'; ?>" 
                                   placeholder="5000">
                            <small>Default: 5,000 miles</small>
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="air_filter_interval">Air Filter Interval:</label>
                            <input type="number" id="air_filter_interval" name="air_filter_interval" min="5000" max="100000"
                                   value="<?php echo $is_edit ? esc_attr($truck->air_filter_interval ?: 15000) : '15000'; ?>" 
                                   placeholder="15000">
                            <small>Default: 15,000 miles</small>
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="fuel_filter_interval">Fuel Filter Interval:</label>
                            <input type="number" id="fuel_filter_interval" name="fuel_filter_interval" min="10000" max="100000"
                                   value="<?php echo $is_edit ? esc_attr($truck->fuel_filter_interval ?: 25000) : '25000'; ?>" 
                                   placeholder="25000">
                            <small>Default: 25,000 miles</small>
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="dpf_cleaning_interval">DPF Cleaning Interval:</label>
                            <input type="number" id="dpf_cleaning_interval" name="dpf_cleaning_interval" min="50000" max="500000"
                                   value="<?php echo $is_edit ? esc_attr($truck->dpf_cleaning_interval ?: 100000) : '100000'; ?>" 
                                   placeholder="100000">
                            <small>Default: 100,000 miles</small>
                        </div>
                    </div>
                </div>
                
                <div class="tmm-form-actions">
                    <button type="submit" class="tmm-btn tmm-btn-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $is_edit ? 'Update Truck' : 'Add Truck'; ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-btn tmm-btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#tmm-truck-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_save_truck&nonce=' + tmm_ajax.nonce;
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        
        console.log('Form data being sent:', formData);
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');
            },
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    alert('Truck saved successfully!');
                    window.location.href = '<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>';
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error saving truck. Please try again.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // VIN validation
    $('#vin').on('input', function() {
        var vin = $(this).val().toUpperCase();
        $(this).val(vin);
        
        if (vin.length === 17) {
            $(this).removeClass('error');
        } else if (vin.length > 0) {
            $(this).addClass('error');
        }
    });
    
    // Year validation
    $('#year').on('input', function() {
        var year = parseInt($(this).val());
        var currentYear = new Date().getFullYear();
        
        if (year >= 1900 && year <= currentYear + 1) {
            $(this).removeClass('error');
        } else if ($(this).val().length > 0) {
            $(this).addClass('error');
        }
    });
});
</script>
