<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tmm-container">
    <div class="tmm-header">
        <h1>🚛 Truck Maintenance Dashboard</h1>
        <div class="tmm-header-actions">
            <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>" class="tmm-btn tmm-btn-primary">
                <span class="dashicons dashicons-plus"></span> Add Truck
            </a>
            <button class="tmm-btn tmm-btn-secondary tmm-export-btn">
                <span class="dashicons dashicons-download"></span> Export Data
            </button>
        </div>
    </div>
    
    <?php if (empty($trucks)): ?>
        <div class="tmm-card">
            <div class="tmm-card-content">
                <div class="tmm-no-data">
                    <span class="dashicons dashicons-car"></span>
                    <h3>No trucks in your fleet yet</h3>
                    <p>Start by adding your first truck to begin tracking maintenance schedules.</p>
                    <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add'); ?>" class="tmm-btn tmm-btn-primary">
                        <span class="dashicons dashicons-plus"></span> Add Your First Truck
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="tmm-dashboard-grid">
            <?php foreach ($trucks as $truck): 
                $urgency_score = tmm_calculate_urgency_score($truck);
                $bg_class = tmm_get_urgency_background_color($urgency_score);
                $border_class = tmm_get_urgency_border_color($urgency_score);
            ?>
                <div class="tmm-truck-item <?php echo $bg_class . ' ' . $border_class; ?>" data-urgency-score="<?php echo $urgency_score; ?>">
                    <div class="tmm-truck-header">
                        <div class="tmm-truck-info">
                            <h4><?php echo esc_html($truck->year . ' ' . $truck->make . ' ' . $truck->model); ?></h4>
                            <p class="tmm-vin">VIN: <?php echo esc_html($truck->vin); ?></p>
                            <?php if ($truck->unit_number): ?>
                                <p class="tmm-unit-number">Unit: <?php echo esc_html($truck->unit_number); ?></p>
                            <?php endif; ?>
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
                        
                        <a href="<?php echo admin_url('admin.php?page=truck-maintenance-list&truck_id=' . $truck->id); ?>" class="tmm-btn tmm-btn-small">
                            <span class="dashicons dashicons-visibility"></span> View Details
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=truck-maintenance-add&edit=1&truck_id=' . $truck->id); ?>" class="tmm-btn tmm-btn-small">
                            <span class="dashicons dashicons-edit"></span> Edit
                        </a>
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
