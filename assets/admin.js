jQuery(document).ready(function($) {
    
    // Save truck form
    $('#tmm-truck-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_save_truck&nonce=' + tmm_ajax.nonce;
        
        $.post(tmm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                showMessage('Truck saved successfully!', 'success');
                if (!$('#truck_id').val()) {
                    $('#tmm-truck-form')[0].reset();
                }
            } else {
                showMessage('Error saving truck: ' + response.data, 'error');
            }
        });
    });
    
    // Update maintenance form
    $('#tmm-maintenance-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_update_maintenance&nonce=' + tmm_ajax.nonce;
        
        $.post(tmm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                showMessage('Maintenance updated successfully!', 'success');
                location.reload();
            } else {
                showMessage('Error updating maintenance: ' + response.data, 'error');
            }
        });
    });
    
    // Delete truck
    $('.tmm-delete-truck').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this truck?')) {
            return;
        }
        
        var truckId = $(this).data('truck-id');
        
        $.post(tmm_ajax.ajax_url, {
            action: 'tmm_delete_truck',
            truck_id: truckId,
            nonce: tmm_ajax.nonce
        }, function(response) {
            if (response.success) {
                showMessage('Truck deleted successfully!', 'success');
                location.reload();
            } else {
                showMessage('Error deleting truck: ' + response.data, 'error');
            }
        });
    });
    
    // Import trucks
    $('#tmm-import-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'tmm_import_trucks');
        formData.append('nonce', tmm_ajax.nonce);
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var message = 'Imported ' + response.data.imported + ' trucks successfully!';
                    if (response.data.errors.length > 0) {
                        message += '\n\nErrors:\n' + response.data.errors.join('\n');
                    }
                    showMessage(message, 'success');
                    $('#tmm-import-form')[0].reset();
                } else {
                    showMessage('Error importing trucks: ' + response.data, 'error');
                }
            }
        });
    });
    
    // Generate QR code
    $('.tmm-generate-qr').on('click', function(e) {
        e.preventDefault();
        
        var truckId = $(this).data('truck-id');
        
        $.post(tmm_ajax.ajax_url, {
            action: 'tmm_generate_qr',
            truck_id: truckId,
            nonce: tmm_ajax.nonce
        }, function(response) {
            if (response.success) {
                var qrModal = $('<div class="tmm-modal">' +
                    '<div class="tmm-modal-content">' +
                    '<span class="tmm-modal-close">&times;</span>' +
                    '<h3>QR Code for Truck</h3>' +
                    '<img src="' + response.data.qr_url + '" alt="QR Code" style="max-width: 100%;">' +
                    '<p>Scan this QR code to quickly access this truck\'s details.</p>' +
                    '</div>' +
                    '</div>');
                
                $('body').append(qrModal);
                qrModal.show();
                
                qrModal.on('click', '.tmm-modal-close, .tmm-modal', function(e) {
                    if (e.target === this) {
                        qrModal.remove();
                    }
                });
            } else {
                showMessage('Error generating QR code: ' + response.data, 'error');
            }
        });
    });
    
    // Maintenance card clicks
    $('.tmm-maintenance-card').on('click', function() {
        var maintenanceType = $(this).data('maintenance-type');
        var truckId = $(this).data('truck-id');
        
        // Show maintenance form modal
        showMaintenanceModal(truckId, maintenanceType);
    });
    
    // Search functionality
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
    
    // Sort functionality
    $('#tmm-sort').on('change', function() {
        var sortBy = $(this).val();
        var container = $('.tmm-truck-list');
        var items = container.children('.tmm-truck-item').get();
        
        items.sort(function(a, b) {
            var aVal, bVal;
            
            switch(sortBy) {
                case 'urgency':
                    aVal = parseInt($(a).data('urgency-score')) || 0;
                    bVal = parseInt($(b).data('urgency-score')) || 0;
                    return bVal - aVal; // Descending
                    
                case 'year':
                    aVal = parseInt($(a).find('.tmm-truck-year').text()) || 0;
                    bVal = parseInt($(b).find('.tmm-truck-year').text()) || 0;
                    return bVal - aVal; // Descending
                    
                case 'make':
                    aVal = $(a).find('.tmm-truck-make').text().toLowerCase();
                    bVal = $(b).find('.tmm-truck-make').text().toLowerCase();
                    return aVal.localeCompare(bVal); // Ascending
                    
                case 'mileage':
                    aVal = parseInt($(a).data('mileage')) || 0;
                    bVal = parseInt($(b).data('mileage')) || 0;
                    return bVal - aVal; // Descending
                    
                default:
                    return 0;
            }
        });
        
        $.each(items, function(index, item) {
            container.append(item);
        });
    });
    
    // Auto-calculate next maintenance date
    $('.tmm-maintenance-date, .tmm-maintenance-mileage').on('change', function() {
        calculateNextMaintenanceDate();
    });
    
    function calculateNextMaintenanceDate() {
        var maintenanceDate = $('#maintenance_date').val();
        var maintenanceMileage = parseInt($('#maintenance_mileage').val()) || 0;
        var maintenanceType = $('#maintenance_type').val();
        
        if (!maintenanceDate || !maintenanceMileage) return;
        
        // Get interval based on maintenance type
        var interval = 0;
        switch(maintenanceType) {
            case 'oil':
                interval = parseInt($('#oil_change_interval').val()) || 5000;
                break;
            case 'airFilter':
                interval = parseInt($('#air_filter_interval').val()) || 15000;
                break;
            case 'fuelFilter':
                interval = parseInt($('#fuel_filter_interval').val()) || 25000;
                break;
            case 'dpfCleaning':
                interval = parseInt($('#dpf_cleaning_interval').val()) || 100000;
                break;
        }
        
        if (interval > 0) {
            // Calculate next date (approximate based on average monthly mileage)
            var avgMonthlyMileage = 2000; // Assumption
            var monthsToNext = interval / avgMonthlyMileage;
            var nextDate = new Date(maintenanceDate);
            nextDate.setMonth(nextDate.getMonth() + monthsToNext);
            
            $('#next_date').val(nextDate.toISOString().split('T')[0]);
        }
    }
    
    function showMaintenanceModal(truckId, maintenanceType) {
        var modal = $('<div class="tmm-modal">' +
            '<div class="tmm-modal-content">' +
            '<span class="tmm-modal-close">&times;</span>' +
            '<h3>Update ' + getMaintenanceTypeLabel(maintenanceType) + '</h3>' +
            '<form id="tmm-maintenance-modal-form">' +
            '<input type="hidden" name="truck_id" value="' + truckId + '">' +
            '<input type="hidden" name="maintenance_type" value="' + maintenanceType + '">' +
            '<div class="tmm-form-group">' +
            '<label>Service Date:</label>' +
            '<input type="date" name="maintenance_date" required>' +
            '</div>' +
            '<div class="tmm-form-group">' +
            '<label>Mileage:</label>' +
            '<input type="number" name="maintenance_mileage" required>' +
            '</div>' +
            '<div class="tmm-form-group">' +
            '<label>Next Due Date:</label>' +
            '<input type="date" name="next_date">' +
            '</div>' +
            '<div class="tmm-form-group">' +
            '<label>Notes:</label>' +
            '<textarea name="notes"></textarea>' +
            '</div>' +
            '<button type="submit" class="tmm-btn">Update Maintenance</button>' +
            '</form>' +
            '</div>' +
            '</div>');
        
        $('body').append(modal);
        modal.show();
        
        modal.on('click', '.tmm-modal-close, .tmm-modal', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
        
        modal.find('form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            formData += '&action=tmm_update_maintenance&nonce=' + tmm_ajax.nonce;
            
            $.post(tmm_ajax.ajax_url, formData, function(response) {
                if (response.success) {
                    showMessage('Maintenance updated successfully!', 'success');
                    modal.remove();
                    location.reload();
                } else {
                    showMessage('Error updating maintenance: ' + response.data, 'error');
                }
            });
        });
    }
    
    function getMaintenanceTypeLabel(type) {
        var labels = {
            'oil': 'Oil Change',
            'airFilter': 'Air Filter',
            'fuelFilter': 'Fuel Filter',
            'dpfCleaning': 'DPF Cleaning',
            'safetyInspection': 'Safety Inspection'
        };
        return labels[type] || type;
    }
    
    function showMessage(message, type) {
        var messageDiv = $('<div class="tmm-message ' + type + '">' + message + '</div>');
        $('.tmm-container').prepend(messageDiv);
        
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                messageDiv.remove();
            });
        }, 5000);
    }
});

// Modal CSS
$('<style>')
    .prop('type', 'text/css')
    .html(`
        .tmm-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .tmm-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }
        
        .tmm-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 15px;
            top: 10px;
            cursor: pointer;
        }
        
        .tmm-modal-close:hover {
            color: #000;
        }
    `)
    .appendTo('head');
