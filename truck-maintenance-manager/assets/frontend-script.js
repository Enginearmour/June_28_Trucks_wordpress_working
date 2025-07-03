jQuery(document).ready(function($) {
    
    // Truck item click to expand/collapse
    $(document).on('click', '.tmm-truck-item', function(e) {
        // Don't expand if clicking on buttons or form elements
        if ($(e.target).closest('.tmm-truck-actions, .tmm-btn, button, input, select, textarea').length) {
            return;
        }
        
        $(this).toggleClass('expanded');
    });
    
    // Maintenance button click
    $(document).on('click', '.tmm-maintenance-btn', function(e) {
        e.stopPropagation();
        
        var truckId = $(this).data('truck-id');
        var maintenanceType = $(this).data('maintenance-type') || '';
        
        $('#tmm-truck-id').val(truckId);
        
        // Pre-select maintenance type if specified
        if (maintenanceType) {
            $('#tmm-maintenance-type').val(maintenanceType);
        } else {
            $('#tmm-maintenance-type').val('');
        }
        
        // Set today's date as default
        var today = new Date().toISOString().split('T')[0];
        $('#tmm-maintenance-date').val(today);
        
        // Clear other fields
        $('#tmm-maintenance-mileage').val('');
        $('#tmm-next-due-date').val('');
        $('#tmm-maintenance-notes').val('');
        
        $('#tmm-maintenance-modal').show();
    });
    
    // Update mileage button click
    $(document).on('click', '.tmm-update-mileage', function(e) {
        e.stopPropagation();
        
        var truckId = $(this).data('truck-id');
        $('#tmm-mileage-truck-id').val(truckId);
        
        // Clear the mileage field
        $('#tmm-new-mileage').val('');
        
        $('#tmm-mileage-modal').show();
    });
    
    // Details button click
    $(document).on('click', '.tmm-details-btn', function(e) {
        e.stopPropagation();
        
        var truckId = $(this).data('truck-id');
        
        // Get truck details via AJAX
        $.post(tmm_ajax.ajax_url, {
            action: 'tmm_get_truck_details',
            truck_id: truckId,
            nonce: tmm_ajax.nonce
        }, function(response) {
            if (response.success) {
                var truck = response.data.truck;
                var maintenanceHistory = response.data.maintenance_history;
                var urgencyScore = response.data.urgency_score;
                
                // Build modal content
                var modalContent = '<div class="tmm-modal-truck-info">';
                modalContent += '<div class="tmm-modal-info-group">';
                modalContent += '<h4>Vehicle Info</h4>';
                modalContent += '<p>' + truck.year + ' ' + truck.make + ' ' + truck.model + '</p>';
                modalContent += '</div>';
                
                if (truck.unit_number) {
                    modalContent += '<div class="tmm-modal-info-group">';
                    modalContent += '<h4>Unit Number</h4>';
                    modalContent += '<p>' + truck.unit_number + '</p>';
                    modalContent += '</div>';
                }
                
                modalContent += '<div class="tmm-modal-info-group">';
                modalContent += '<h4>VIN</h4>';
                modalContent += '<p>' + truck.vin + '</p>';
                modalContent += '</div>';
                
                modalContent += '<div class="tmm-modal-info-group">';
                modalContent += '<h4>Current Mileage</h4>';
                modalContent += '<p>' + parseInt(truck.current_mileage).toLocaleString() + ' ' + truck.distance_unit + '</p>';
                modalContent += '</div>';
                
                modalContent += '<div class="tmm-modal-info-group">';
                modalContent += '<h4>Urgency Score</h4>';
                modalContent += '<p>' + urgencyScore + '/100</p>';
                modalContent += '</div>';
                
                if (truck.safety_inspection_expiry) {
                    modalContent += '<div class="tmm-modal-info-group">';
                    modalContent += '<h4>Safety Inspection</h4>';
                    modalContent += '<p>' + new Date(truck.safety_inspection_expiry).toLocaleDateString() + '</p>';
                    modalContent += '</div>';
                }
                
                modalContent += '</div>';
                
                // Add maintenance history if available
                if (maintenanceHistory && maintenanceHistory.length > 0) {
                    modalContent += '<h4>Recent Maintenance History</h4>';
                    modalContent += '<div class="tmm-modal-maintenance-grid">';
                    
                    maintenanceHistory.forEach(function(record) {
                        var typeLabels = {
                            'oil': 'Oil Change',
                            'oilFilter': 'Oil Filter',
                            'airFilter': 'Air Filter',
                            'fuelFilter': 'Fuel Filter',
                            'dpfCleaning': 'DPF Cleaning',
                            'safetyInspection': 'Safety Inspection'
                        };
                        
                        var typeIcons = {
                            'oil': '🛢️',
                            'oilFilter': '🔧',
                            'airFilter': '🌬️',
                            'fuelFilter': '⛽',
                            'dpfCleaning': '💨',
                            'safetyInspection': '📋'
                        };
                        
                        modalContent += '<div class="tmm-modal-maintenance-card">';
                        modalContent += '<div class="tmm-modal-maintenance-header">';
                        modalContent += '<span class="tmm-modal-maintenance-icon">' + (typeIcons[record.type] || '🔧') + '</span>';
                        modalContent += '<h5>' + (typeLabels[record.type] || record.type) + '</h5>';
                        modalContent += '</div>';
                        modalContent += '<div class="tmm-modal-maintenance-info">';
                        modalContent += '<p><strong>Date:</strong> ' + new Date(record.date).toLocaleDateString() + '</p>';
                        modalContent += '<p><strong>Mileage:</strong> ' + parseInt(record.mileage).toLocaleString() + ' ' + truck.distance_unit + '</p>';
                        if (record.notes) {
                            modalContent += '<p><strong>Notes:</strong> ' + record.notes + '</p>';
                        }
                        modalContent += '</div>';
                        modalContent += '</div>';
                    });
                    
                    modalContent += '</div>';
                }
                
                $('#tmm-modal-body').html(modalContent);
                $('#tmm-modal-title').text('Truck Details - ' + (truck.unit_number ? 'Unit ' + truck.unit_number : truck.year + ' ' + truck.make));
                $('#tmm-frontend-modal').show();
            } else {
                alert('Error loading truck details: ' + response.data);
            }
        });
    });
    
    // Close modal functionality
    $(document).on('click', '.tmm-close', function() {
        $('.tmm-modal').hide();
    });
    
    // Close modal when clicking outside
    $(document).on('click', '.tmm-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Maintenance form submission
    $('#tmm-maintenance-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_save_maintenance&nonce=' + tmm_ajax.nonce;
        
        $.post(tmm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Maintenance record saved successfully!');
                $('#tmm-maintenance-modal').hide();
                location.reload(); // Refresh to show updated data
            } else {
                alert('Error saving maintenance record: ' + response.data);
            }
        });
    });
    
    // Mileage form submission
    $('#tmm-mileage-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_update_mileage&nonce=' + tmm_ajax.nonce;
        
        $.post(tmm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Mileage updated successfully!');
                $('#tmm-mileage-modal').hide();
                location.reload(); // Refresh to show updated data
            } else {
                alert('Error updating mileage: ' + response.data);
            }
        });
    });
    
    // Set urgency circle colors based on score
    $('.tmm-urgency-circle').each(function() {
        var score = parseInt($(this).data('score'));
        var color = '#28a745'; // Default green
        
        if (score >= 80) color = '#b71c1c';      // Dark red
        else if (score >= 60) color = '#dc3545'; // Red
        else if (score >= 40) color = '#fd7e14'; // Orange
        else if (score >= 20) color = '#ffc107'; // Yellow
        
        $(this).css('background-color', color);
    });
});
