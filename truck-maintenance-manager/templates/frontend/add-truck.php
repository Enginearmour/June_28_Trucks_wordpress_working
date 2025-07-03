<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tmm-frontend-container">
    <div class="tmm-frontend-header">
        <h1 class="tmm-frontend-title">Add New Truck</h1>
        <div class="tmm-frontend-controls">
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-frontend-btn secondary">Back to List</a>
        </div>
    </div>
    
    <div class="tmm-frontend-card">
        <form id="tmm-frontend-truck-form" class="tmm-frontend-form">
            <div class="tmm-frontend-form-row">
                <div class="tmm-frontend-form-group">
                    <label for="vin">VIN (Vehicle Identification Number) *</label>
                    <input type="text" id="vin" name="vin" required maxlength="17" 
                           placeholder="Enter 17-character VIN">
                </div>
                
                <div class="tmm-frontend-form-group">
                    <label for="unit_number">Unit Number</label>
                    <input type="text" id="unit_number" name="unit_number" 
                           placeholder="Optional unit identifier">
                </div>
            </div>
            
            <div class="tmm-frontend-form-row">
                <div class="tmm-frontend-form-group">
                    <label for="year">Year *</label>
                    <input type="number" id="year" name="year" required 
                           min="1900" max="<?php echo date('Y') + 1; ?>" 
                           placeholder="<?php echo date('Y'); ?>">
                </div>
                
                <div class="tmm-frontend-form-group">
                    <label for="make">Make *</label>
                    <input type="text" id="make" name="make" required 
                           placeholder="e.g., Freightliner, Peterbilt">
                </div>
            </div>
            
            <div class="tmm-frontend-form-row">
                <div class="tmm-frontend-form-group">
                    <label for="model">Model *</label>
                    <input type="text" id="model" name="model" required 
                           placeholder="e.g., Cascadia, 579">
                </div>
                
                <div class="tmm-frontend-form-group">
                    <label for="current_mileage">Current Mileage/Distance</label>
                    <input type="number" id="current_mileage" name="current_mileage" 
                           min="0" placeholder="0">
                </div>
            </div>
            
            <div class="tmm-frontend-form-group">
                <label for="distance_unit">Distance Unit</label>
                <select id="distance_unit" name="distance_unit">
                    <option value="miles">Miles</option>
                    <option value="kilometers">Kilometers</option>
                </select>
            </div>
            
            <h3 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
                Maintenance Intervals
            </h3>
            
            <div class="tmm-frontend-form-row">
                <div class="tmm-frontend-form-group">
                    <label for="oil_change_interval">Oil Change Interval</label>
                    <input type="number" id="oil_change_interval" name="oil_change_interval" 
                           min="0" placeholder="5000" value="5000">
                </div>
                
                <div class="tmm-frontend-form-group">
                    <label for="air_filter_interval">Air Filter Interval</label>
                    <input type="number" id="air_filter_interval" name="air_filter_interval" 
                           min="0" placeholder="15000" value="15000">
                </div>
            </div>
            
            <div class="tmm-frontend-form-row">
                <div class="tmm-frontend-form-group">
                    <label for="fuel_filter_interval">Fuel Filter Interval</label>
                    <input type="number" id="fuel_filter_interval" name="fuel_filter_interval" 
                           min="0" placeholder="25000" value="25000">
                </div>
                
                <div class="tmm-frontend-form-group">
                    <label for="dpf_cleaning_interval">DPF Cleaning Interval</label>
                    <input type="number" id="dpf_cleaning_interval" name="dpf_cleaning_interval" 
                           min="0" placeholder="100000" value="100000">
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" 
                   class="tmm-frontend-btn secondary">Cancel</a>
                <button type="submit" class="tmm-frontend-btn">Add Truck</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#tmm-frontend-truck-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_save_truck&nonce=' + tmm_ajax.nonce;
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.text('Adding Truck...').prop('disabled', true);
        
        $.post(tmm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Show success message
                var messageDiv = $('<div class="tmm-frontend-message success">Truck added successfully!</div>');
                $('.tmm-frontend-container').prepend(messageDiv);
                
                // Reset form
                $('#tmm-frontend-truck-form')[0].reset();
                
                // Scroll to top
                $('html, body').animate({ scrollTop: 0 }, 300);
                
                // Auto-hide message
                setTimeout(function() {
                    messageDiv.fadeOut(function() {
                        messageDiv.remove();
                    });
                }, 5000);
            } else {
                var messageDiv = $('<div class="tmm-frontend-message error">Error adding truck: ' + response.data + '</div>');
                $('.tmm-frontend-container').prepend(messageDiv);
                
                setTimeout(function() {
                    messageDiv.fadeOut(function() {
                        messageDiv.remove();
                    });
                }, 5000);
            }
            
            submitBtn.text(originalText).prop('disabled', false);
        }).fail(function() {
            var messageDiv = $('<div class="tmm-frontend-message error">Error adding truck. Please try again.</div>');
            $('.tmm-frontend-container').prepend(messageDiv);
            
            setTimeout(function() {
                messageDiv.fadeOut(function() {
                    messageDiv.remove();
                });
            }, 5000);
            
            submitBtn.text(originalText).prop('disabled', false);
        });
    });
    
    // VIN validation
    $('#vin').on('input', function() {
        var vin = $(this).val().toUpperCase();
        $(this).val(vin);
        
        if (vin.length === 17) {
            $(this).css('border-color', '#4caf50');
        } else {
            $(this).css('border-color', '');
        }
    });
});
</script>
