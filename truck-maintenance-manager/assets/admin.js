// Truck Maintenance Manager - Admin JavaScript

jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initializeAdmin();
    
    function initializeAdmin() {
        // Add truck form handler
        $('#add-truck-form').on('submit', handleAddTruck);
        
        // Import form handler
        $('#import-form').on('submit', handleImportTrucks);
        
        // Delete truck handlers
        $('.delete-truck').on('click', handleDeleteTruck);
        
        // Maintenance update handlers
        $('.tmm-maintenance-item').on('click', handleMaintenanceUpdate);
        
        // QR code generation
        $('.generate-qr').on('click', handleGenerateQR);
        
        // Modal handlers
        $('.tmm-close').on('click', closeModal);
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('tmm-modal')) {
                closeModal();
            }
        });
    }
    
    function handleAddTruck(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_save_truck&nonce=' + tmm_ajax.nonce;
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#add-truck-form').addClass('tmm-loading');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Truck added successfully!', 'success');
                    $('#add-truck-form')[0].reset();
                    setTimeout(function() {
                        window.location.href = 'admin.php?page=truck-maintenance-list';
                    }, 1500);
                } else {
                    showMessage('Error adding truck: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error adding truck. Please try again.', 'error');
            },
            complete: function() {
                $('#add-truck-form').removeClass('tmm-loading');
            }
        });
    }
    
    function handleImportTrucks(e) {
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
            beforeSend: function() {
                $('#import-form').addClass('tmm-loading');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Imported ' + response.data.imported + ' trucks successfully!', 'success');
                    if (response.data.errors.length > 0) {
                        showMessage('Errors: ' + response.data.errors.join(', '), 'error');
                    }
                } else {
                    showMessage('Error importing trucks: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error importing trucks. Please try again.', 'error');
            },
            complete: function() {
                $('#import-form').removeClass('tmm-loading');
            }
        });
    }
    
    function handleDeleteTruck(e) {
        e.preventDefault();
        
        var truckId = $(this).data('truck-id');
        
        if (!confirm('Are you sure you want to delete this truck?')) {
            return;
        }
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tmm_delete_truck',
                truck_id: truckId,
                nonce: tmm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Truck deleted successfully!', 'success');
                    location.reload();
                } else {
                    showMessage('Error deleting truck: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error deleting truck. Please try again.', 'error');
            }
        });
    }
    
    function handleMaintenanceUpdate(e) {
        e.preventDefault();
        
        var truckId = $(this).closest('.tmm-truck-card').data('truck-id');
        var maintenanceType = $(this).data('maintenance-type');
        
        // Open maintenance update modal
        openMaintenanceModal(truckId, maintenanceType);
    }
    
    function handleGenerateQR(e) {
        e.preventDefault();
        
        var truckId = $(this).data('truck-id');
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tmm_generate_qr',
                truck_id: truckId,
                nonce: tmm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showQRModal(response.data.qr_url);
                } else {
                    showMessage('Error generating QR code: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error generating QR code. Please try again.', 'error');
            }
        });
    }
    
    function openMaintenanceModal(truckId, maintenanceType) {
        var modal = $('#maintenance-modal');
        if (modal.length === 0) {
            // Create modal if it doesn't exist
            $('body').append(createMaintenanceModal());
            modal = $('#maintenance-modal');
        }
        
        // Populate modal with truck and maintenance type
        modal.find('#modal-truck-id').val(truckId);
        modal.find('#modal-maintenance-type').val(maintenanceType);
        modal.find('.tmm-modal-title').text('Update ' + formatMaintenanceType(maintenanceType));
        
        modal.show();
    }
    
    function showQRModal(qrUrl) {
        var modal = $('#qr-modal');
        if (modal.length === 0) {
            $('body').append(createQRModal());
            modal = $('#qr-modal');
        }
        
        modal.find('#qr-image').attr('src', qrUrl);
        modal.show();
    }
    
    function createMaintenanceModal() {
        return `
            <div id="maintenance-modal" class="tmm-modal">
                <div class="tmm-modal-content">
                    <div class="tmm-modal-header">
                        <h3 class="tmm-modal-title">Update Maintenance</h3>
                        <span class="tmm-close">&times;</span>
                    </div>
                    <form id="maintenance-form">
                        <input type="hidden" id="modal-truck-id" name="truck_id">
                        <input type="hidden" id="modal-maintenance-type" name="maintenance_type">
                        
                        <div class="tmm-form-group">
                            <label for="maintenance-date">Service Date:</label>
                            <input type="date" id="maintenance-date" name="maintenance_date" required>
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="maintenance-mileage">Mileage:</label>
                            <input type="number" id="maintenance-mileage" name="maintenance_mileage" required>
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="next-date">Next Service Date:</label>
                            <input type="date" id="next-date" name="next_date">
                        </div>
                        
                        <div class="tmm-form-group">
                            <label for="maintenance-notes">Notes:</label>
                            <textarea id="maintenance-notes" name="notes"></textarea>
                        </div>
                        
                        <button type="submit" class="tmm-button">Update Maintenance</button>
                        <button type="button" class="tmm-button tmm-button-secondary tmm-close">Cancel</button>
                    </form>
                </div>
            </div>
        `;
    }
    
    function createQRModal() {
        return `
            <div id="qr-modal" class="tmm-modal">
                <div class="tmm-modal-content">
                    <div class="tmm-modal-header">
                        <h3>QR Code</h3>
                        <span class="tmm-close">&times;</span>
                    </div>
                    <div style="text-align: center;">
                        <img id="qr-image" src="" alt="QR Code" style="max-width: 200px;">
                        <p>Scan this QR code to access truck details</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    function closeModal() {
        $('.tmm-modal').hide();
    }
    
    function formatMaintenanceType(type) {
        var types = {
            'oil': 'Oil Change',
            'airFilter': 'Air Filter',
            'fuelFilter': 'Fuel Filter',
            'dpfCleaning': 'DPF Cleaning',
            'safetyInspection': 'Safety Inspection'
        };
        return types[type] || type;
    }
    
    function showMessage(message, type) {
        var messageDiv = $('<div class="tmm-message ' + type + '">' + message + '</div>');
        $('.wrap').prepend(messageDiv);
        
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Handle maintenance form submission
    $(document).on('submit', '#maintenance-form', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=tmm_update_maintenance&nonce=' + tmm_ajax.nonce;
        
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#maintenance-form').addClass('tmm-loading');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Maintenance updated successfully!', 'success');
                    closeModal();
                    location.reload();
                } else {
                    showMessage('Error updating maintenance: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error updating maintenance. Please try again.', 'error');
            },
            complete: function() {
                $('#maintenance-form').removeClass('tmm-loading');
            }
        });
    });
});

// Global function for delete truck (called from PHP)
function deleteTruck(truckId) {
    if (!confirm('Are you sure you want to delete this truck?')) {
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
                alert('Error deleting truck: ' + response.data);
            }
        },
        error: function() {
            alert('Error deleting truck. Please try again.');
        }
    });
}
