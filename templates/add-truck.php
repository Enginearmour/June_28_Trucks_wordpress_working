<?php
if (!defined('ABSPATH')) {
    exit;
}

$truck_id = isset($_GET['truck_id']) ? intval($_GET['truck_id']) : 0;
$truck = null;

if ($truck_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_maintenance';
    $truck = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $truck_id));
}
?>

<div class="tmm-container">
    <h1><?php echo $truck ? 'Edit Truck' : 'Add New Truck'; ?></h1>
    
    <div class="tmm-card">
        <form id="tmm-truck-form" class="tmm-form">
            <?php if ($truck): ?>
                <input type="hidden" id="truck_id" name="truck_id" value="<?php echo $truck->id; ?>">
            <?php endif; ?>
            
            <div class="tmm-form-row">
                <div class="tmm-form-group">
                    <label for="vin">VIN *</label>
                    <input type="text" id="vin" name="vin" required maxlength="17" 
                           value="<?php echo $truck ? esc_attr($truck->vin) : ''; ?>">
                </div>
                
                <div class="tmm-form-group">
                    <label for="unit_number">Unit Number</label>
                    <input type="text" id="unit_number" name="unit_number" 
                           value="<?php echo $truck ? esc_attr($truck->unit_number) : ''; ?>">
                </div>
            </div>
            
            <div class="tmm-form-row">
                <div class="tmm-form-group">
                    <label for="year">Year *</label>
                    <input type="number" id="year" name="year" required min="1900" max="<?php echo date('Y') + 1; ?>" 
                           value="<?php echo $truck ? $truck->year : ''; ?>">
                </div>
                
                <div class="tmm-form-group">
                    <label for="make">Make *</label>
                    <input type="text" id="make" name="make" required 
                           value="<?php echo $truck ? esc_attr($truck->make) : ''; ?>">
                </div>
            </div>
            
            <div class="tmm-form-row">
                <div class="tmm-form-group">
                    <label for="model">Model *</label>
                    <input type="text" id="model" name="model" required 
                           value="<?php echo $truck ? esc_attr($truck->model) : ''; ?>">
                </div>
                
                <div class="tmm-form-group">
                    <label for="current_mileage">Current Mileage *</label>
                    <input type="number" id="current_mileage" name="current_mileage" required min="0" 
                           value="<?php echo $truck ? $truck->current_mileage : ''; ?>">
                </div>
            </div>
            
            <div class="tmm-form-group">
                <label for="distance_unit">Distance Unit</label>
                <select id="distance_unit" name="distance_unit">
                    <option value="miles" <?php echo ($truck && $truck->distance_unit === 'miles') ? 'selected' : ''; ?>>Miles</option>
                    <option value="kilometers" <?php echo ($truck && $truck->distance_unit === 'kilometers') ? 'selected' : ''; ?>>Kilometers</option>
                </select>
            </div>
            
            <h3 style="margin: 30px 0 20px 0; color: #333;">Maintenance Intervals</h3>
            
            <div class="tmm-form-row">
                <div class="tmm-form-group">
                    <label for="oil_change_interval">Oil Change Interval</label>
                    <input type="number" id="oil_change_interval" name="oil_change_interval" min="0" 
                           value="<?php echo $truck ? $truck->oil_change_interval : '5000'; ?>" 
                           placeholder="5000">
                </div>
                
                <div class="tmm-form-group">
                    <label for="air_filter_interval">Air Filter Interval</label>
                    <input type="number" id="air_filter_interval" name="air_filter_interval" min="0" 
                           value="<?php echo $truck ? $truck->air_filter_interval : '15000'; ?>" 
                           placeholder="15000">
                </div>
            </div>
            
            <div class="tmm-form-row">
                <div class="tmm-form-group">
                    <label for="fuel_filter_interval">Fuel Filter Interval</label>
                    <input type="number" id="fuel_filter_interval" name="fuel_filter_interval" min="0" 
                           value="<?php echo $truck ? $truck->fuel_filter_interval : '25000'; ?>" 
                           placeholder="25000">
                </div>
                
                <div class="tmm-form-group">
                    <label for="dpf_cleaning_interval">DPF Cleaning Interval</label>
                    <input type="number" id="dpf_cleaning_interval" name="dpf_cleaning_interval" min="0" 
                           value="<?php echo $truck ? $truck->dpf_cleaning_interval : '100000'; ?>" 
                           placeholder="100000">
                </div>
            </div>
            
            <div style="margin-top: 30px; display: flex; gap: 15px;">
                <button type="submit" class="tmm-btn"><?php echo $truck ? 'Update Truck' : 'Add Truck'; ?></button>
                <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list'); ?>" class="tmm-btn secondary">Cancel</a>
                <?php if ($truck): ?>
                    <button type="button" class="tmm-btn danger tmm-delete-truck" data-truck-id="<?php echo $truck->id; ?>">Delete Truck</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
