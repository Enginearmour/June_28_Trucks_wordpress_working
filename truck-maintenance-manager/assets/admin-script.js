// Truck Maintenance Manager - Admin JavaScript

jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initializeAdmin();
    
    function initializeAdmin() {
        // Maintenance modal handlers
        $('.tmm-maintenance-btn').on('click', handleMaintenanceModal);
        $('.tmm-close').on('click', closeModal);
        
        // Mileage update handlers
        $('.tmm-update-mileage').on('click', handleMileageModal);
        
        // Modal form submissions
        $('#tmm-maintenance-form').on('submit', handleMaintenanceSubmit);
        $('#tmm-mileage-form').on('submit', handleMileageSubmit);
        
        // Export functionality
        $('.tmm-export-btn').on('click', handleExport);
        
        // Close modal when clicking outside
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('tmm-modal')) {
                closeModal();
            }
        });
        
        // Update urgency circle colors
        updateUrgencyCircles();
        
        // Auto-calculate next due date based on maintenance type
        $('#tmm-maintenance-type').on('change', calculateNextDueDate);
        $('#tmm-maintenance-date').on('change', calculateNextDueDate);
    }
    
    function handleMaintenanceModal(e) {
        e.preventDefault();
        
        var truckId = $(this).data('truck-id');
        var maintenanceType = $(this).data('maintenance-type') || '';
        
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
        var truckCard = $(this).closest('.tmm-truck-item, .tmm-truck-details');
        var currentMileage = truckCard.find('.tmm-mileage-display').text().replace(/[^\d]/g, '');
        if (currentMileage) {
            $('#tmm-maintenance-mileage').val(currentMileage);
        }
        
        // Calculate next due date
        calculateNextDueDate();
        
        // Show modal
        $('#tmm-maintenance-modal').show();
    }
    
    function handleMileageModal(e) {
        e.preventDefault();
        
        var truckId = $(this).data('truck-id');
        $('#tmm-mileage-truck-id').val(truckId);
        
        // Get current mileage and set as default
        var truckCard = $(this).closest('.tmm-truck-item, .tmm-truck-details');
        var currentMileage = truckCard.find('.tmm-mileage-display').text().replace(/[^\d]/g, '');
        if (currentMileage) {
            $('#tmm-new-mileage').val(currentMileage);
        }
        
        // Show modal
        $('#tmm-mileage-modal').show();
    }
    
    function handleMaintenanceSubmit(e) {
        e.preventDefault();
        
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
                if (response.success) {
                    showMessage('Maintenance record saved successfully!', 'success');
                    closeModal();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('Error: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error saving maintenance record. Please try again.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function handleMileageSubmit(e) {
        e.preventDefault();
        
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
                if (response.success) {
                    showMessage('Mileage updated successfully!', 'success');
                    closeModal();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('Error: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error updating mileage. Please try again.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function calculateNextDueDate() {
        var type = $('#tmm-maintenance-type').val();
        var currentDate = new Date($('#tmm-maintenance-date').val() || new Date());
        var nextDate = new Date(currentDate);
        
        // Add typical intervals (these could be made configurable)
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
    
    function handleExport() {
        // Simple CSV export functionality
        var csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Unit Number,VIN,Year,Make,Model,Current Mileage,Urgency Score\n";
        
        $('.tmm-truck-item').each(function() {
            var unitNumber = $(this).find('.tmm-unit-number').text().replace('Unit: ', '') || 'N/A';
            var vin = $(this).find('.tmm-vin').text().replace('VIN: ', '');
            var truckTitle = $(this).find('h4').first().text().split(' ');
            var year = truckTitle[0];
            var make = truckTitle[1];
            var model = truckTitle.slice(2).join(' ');
            var mileage = $(this).find('.tmm-mileage-display').text().replace(/[^\d]/g, '') || '0';
            var urgencyScore = $(this).data('urgency-score') || '0';
            
            csvContent += `"${unitNumber}","${vin}","${year}","${make}","${model}","${mileage}","${urgencyScore}"\n`;
        });
        
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "truck_fleet_data.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showMessage('Fleet data exported successfully!', 'success');
    }
    
    function closeModal() {
        $('.tmm-modal').hide();
    }
    
    function updateUrgencyCircles() {
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
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        $('.tmm-message').remove();
        
        var messageDiv = $('<div class="tmm-message ' + type + '">' + message + '</div>');
        $('.tmm-container').prepend(messageDiv);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to top to show message
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
});

// Global functions for PHP integration
function deleteTruck(truckId) {
    if (!confirm('Are you sure you want to delete this truck? This action cannot be undone.')) {
        return;
    }
    
    jQuery.ajax({
        url: tmm_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'tmm_delete_truck',
            truck_id: truckId,
            nonce: tmm_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                alert('Truck deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('Error deleting truck. Please try again.');
        }
    });
}

function showMaintenanceModal(truckId, maintenanceType) {
    jQuery('#tmm-maintenance-form')[0].reset();
    jQuery('#tmm-truck-id').val(truckId);
    
    if (maintenanceType) {
        jQuery('#tmm-maintenance-type').val(maintenanceType);
    }
    
    var today = new Date().toISOString().split('T')[0];
    jQuery('#tmm-maintenance-date').val(today);
    
    jQuery('#tmm-maintenance-modal').show();
}
