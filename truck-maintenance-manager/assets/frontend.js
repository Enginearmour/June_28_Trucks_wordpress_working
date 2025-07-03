// Truck Maintenance Manager - Frontend JavaScript

jQuery(document).ready(function($) {
    
    // Initialize frontend functionality
    initializeFrontend();
    
    function initializeFrontend() {
        // Search functionality
        $('#tmm-search').on('input', handleSearch);
        
        // Filter buttons
        $('.tmm-filter-btn').on('click', handleFilter);
        
        // Truck card clicks
        $('.tmm-truck-card').on('click', handleTruckCardClick);
        
        // Maintenance item clicks
        $('.tmm-maintenance-item').on('click', handleMaintenanceClick);
        
        // Add truck form (if present)
        $('#frontend-add-truck-form').on('submit', handleAddTruck);
        
        // Modal handlers
        $('.tmm-close').on('click', closeModal);
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('tmm-modal')) {
                closeModal();
            }
        });
        
        // Initialize tooltips or other UI enhancements
        initializeUIEnhancements();
    }
    
    function handleSearch() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.tmm-truck-card').each(function() {
            var cardText = $(this).text().toLowerCase();
            if (cardText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        updateEmptyState();
    }
    
    function handleFilter(e) {
        e.preventDefault();
        
        var filterType = $(this).data('filter');
        
        // Update active filter button
        $('.tmm-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        // Apply filter
        $('.tmm-truck-card').each(function() {
            var urgencyScore = parseInt($(this).data('urgency-score')) || 0;
            var shouldShow = false;
            
            switch(filterType) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'critical':
                    shouldShow = urgencyScore >= 86;
                    break;
                case 'high':
                    shouldShow = urgencyScore >= 51 && urgencyScore <= 85;
                    break;
                case 'medium':
                    shouldShow = urgencyScore >= 31 && urgencyScore <= 50;
                    break;
                case 'low':
                    shouldShow = urgencyScore <= 30;
                    break;
            }
            
            if (shouldShow) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        updateEmptyState();
    }
    
    function handleTruckCardClick(e) {
        // Don't trigger if clicking on maintenance items
        if ($(e.target).closest('.tmm-maintenance-item').length) {
            return;
        }
        
        var truckId = $(this).data('truck-id');
        if (truckId) {
            showTruckDetails(truckId);
        }
    }
    
    function handleMaintenanceClick(e) {
        e.stopPropagation();
        
        var truckId = $(this).closest('.tmm-truck-card').data('truck-id');
        var maintenanceType = $(this).data('maintenance-type');
        
        if (truckId && maintenanceType) {
            openMaintenanceModal(truckId, maintenanceType);
        }
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
                $('#frontend-add-truck-form').addClass('tmm-loading');
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Truck added successfully!', 'success');
                    $('#frontend-add-truck-form')[0].reset();
                    
                    // Refresh page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('Error adding truck: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Error adding truck. Please try again.', 'error');
            },
            complete: function() {
                $('#frontend-add-truck-form').removeClass('tmm-loading');
            }
        });
    }
    
    function showTruckDetails(truckId) {
        $.ajax({
            url: tmm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tmm_get_truck_details',
                truck_id: truckId,
                nonce: tmm_ajax.nonce
            },
            beforeSend: function() {
                showLoadingModal();
            },
            success: function(response) {
                if (response.success) {
                    showTruckDetailsModal(response.data);
                } else {
                    showMessage('Error loading truck details: ' + response.data, 'error');
                    closeModal();
                }
            },
            error: function() {
                showMessage('Error loading truck details. Please try again.', 'error');
                closeModal();
            }
        });
    }
    
    function openMaintenanceModal(truckId, maintenanceType) {
        var modal = $('#maintenance-modal');
        if (modal.length === 0) {
            $('body').append(createMaintenanceModal());
            modal = $('#maintenance-modal');
            
            // Bind form submission
            modal.find('#maintenance-form').on('submit', handleMaintenanceSubmit);
        }
        
        // Reset and populate form
        modal.find('#maintenance-form')[0].reset();
        modal.find('#modal-truck-id').val(truckId);
        modal.find('#modal-maintenance-type').val(maintenanceType);
        modal.find('.tmm-modal-title').text('Update ' + formatMaintenanceType(maintenanceType));
        
        // Set today's date as default
        modal.find('#maintenance-date').val(new Date().toISOString().split('T')[0]);
        
        modal.show();
    }
    
    function handleMaintenanceSubmit(e) {
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
                    
                    // Refresh page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
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
    }
    
    function showLoadingModal() {
        var modal = $('#loading-modal');
        if (modal.length === 0) {
            $('body').append(createLoadingModal());
            modal = $('#loading-modal');
        }
        modal.show();
    }
    
    function showTruckDetailsModal(truck) {
        var modal = $('#truck-details-modal');
        if (modal.length === 0) {
            $('body').append(createTruckDetailsModal());
            modal = $('#truck-details-modal');
        }
        
        // Populate modal with truck data
        modal.find('.tmm-modal-title').text(truck.year + ' ' + truck.make + ' ' + truck.model);
        modal.find('#truck-vin').text(truck.vin);
        modal.find('#truck-unit').text(truck.unit_number || 'N/A');
        modal.find('#truck-mileage').text(number_format(truck.current_mileage) + ' ' + truck.distance_unit);
        modal.find('#truck-urgency').text(truck.urgency_score);
        
        // Update urgency badge color
        var urgencyBadge = modal.find('#truck-urgency');
        urgencyBadge.removeClass().addClass('tmm-urgency-badge ' + getUrgencyClass(truck.urgency_score));
        
        // Populate maintenance history
        populateMaintenanceHistory(modal, truck.maintenance_history || []);
        
        modal.show();
    }
    
    function populateMaintenanceHistory(modal, history) {
        var historyContainer = modal.find('#maintenance-history');
        historyContainer.empty();
        
        if (history.length === 0) {
            historyContainer.append('<p>No maintenance history available.</p>');
            return;
        }
        
        history.forEach(function(record) {
            var recordHtml = `
                <div class="maintenance-record">
                    <h4>${formatMaintenanceType(record.type)}</h4>
                    <p><strong>Date:</strong> ${record.date}</p>
                    <p><strong>Mileage:</strong> ${number_format(record.mileage)}</p>
                    ${record.nextDate ? '<p><strong>Next Due:</strong> ' + record.nextDate + '</p>' : ''}
                    ${record.notes ? '<p><strong>Notes:</strong> ' + record.notes + '</p>' : ''}
                    <p><small>Updated by ${record.user_name} on ${record.timestamp}</small></p>
                </div>
            `;
            historyContainer.append(recordHtml);
        });
    }
    
    function createMaintenanceModal() {
        return `
            <div id="maintenance-modal" class="tmm-modal">
                <div class="tmm-modal-content">
                    <div class="tmm-modal-header">
                        <h3 class="tmm-modal-title">Update Maintenance</h3>
                        <button class="tmm-close">&times;</button>
                    </div>
                    <div class="tmm-modal-body">
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
                                <label for="next-date">Next Service Date (Optional):</label>
                                <input type="date" id="next-date" name="next_date">
                            </div>
                            
                            <div class="tmm-form-group">
                                <label for="maintenance-notes">Notes (Optional):</label>
                                <textarea id="maintenance-notes" name="notes" placeholder="Any additional notes about this service..."></textarea>
                            </div>
                            
                            <div class="tmm-form-actions">
                                <button type="button" class="tmm-button tmm-button-secondary tmm-close">Cancel</button>
                                <button type="submit" class="tmm-button">Update Maintenance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }
    
    function createTruckDetailsModal() {
        return `
            <div id="truck-details-modal" class="tmm-modal">
                <div class="tmm-modal-content">
                    <div class="tmm-modal-header">
                        <h3 class="tmm-modal-title">Truck Details</h3>
                        <button class="tmm-close">&times;</button>
                    </div>
                    <div class="tmm-modal-body">
                        <div class="truck-info-grid">
                            <div class="info-item">
                                <label>VIN:</label>
                                <span id="truck-vin"></span>
                            </div>
                            <div class="info-item">
                                <label>Unit Number:</label>
                                <span id="truck-unit"></span>
                            </div>
                            <div class="info-item">
                                <label>Current Mileage:</label>
                                <span id="truck-mileage"></span>
                            </div>
                            <div class="info-item">
                                <label>Urgency Score:</label>
                                <span id="truck-urgency" class="tmm-urgency-badge"></span>
                            </div>
                        </div>
                        
                        <h4>Maintenance History</h4>
                        <div id="maintenance-history"></div>
                    </div>
                </div>
            </div>
        `;
    }
    
    function createLoadingModal() {
        return `
            <div id="loading-modal" class="tmm-modal">
                <div class="tmm-modal-content">
                    <div class="tmm-modal-body" style="text-align: center; padding: 40px;">
                        <div class="tmm-loading"></div>
                        <p>Loading truck details...</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    function closeModal() {
        $('.tmm-modal').hide();
    }
    
    function updateEmptyState() {
        var visibleCards = $('.tmm-truck-card:visible').length;
        var emptyState = $('.tmm-empty-state');
        
        if (visibleCards === 0) {
            if (emptyState.length === 0) {
                $('.tmm-truck-grid').after(`
                    <div class="tmm-empty-state">
                        <h3>No trucks found</h3>
                        <p>Try adjusting your search or filter criteria.</p>
                    </div>
                `);
            } else {
                emptyState.show();
            }
        } else {
            emptyState.hide();
        }
    }
    
    function initializeUIEnhancements() {
        // Add urgency scores to cards for filtering
        $('.tmm-truck-card').each(function() {
            var urgencyText = $(this).find('.tmm-urgency-badge').text();
            var urgencyScore = parseInt(urgencyText) || 0;
            $(this).attr('data-urgency-score', urgencyScore);
        });
        
        // Initialize search if search box exists
        if ($('#tmm-search').length && $('#tmm-search').val()) {
            $('#tmm-search').trigger('input');
        }
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
    
    function getUrgencyClass(score) {
        if (score === 0) return 'tmm-bg-green';
        if (score <= 30) return 'tmm-bg-yellow-light';
        if (score <= 50) return 'tmm-bg-yellow';
        if (score <= 70) return 'tmm-bg-orange-light';
        if (score <= 85) return 'tmm-bg-orange';
        return 'tmm-bg-red';
    }
    
    function number_format(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function showMessage(message, type) {
        var messageDiv = $('<div class="tmm-message ' + type + '">' + message + '</div>');
        
        // Find the best place to insert the message
        var container = $('.tmm-dashboard, .tmm-add-truck-form, body').first();
        container.prepend(messageDiv);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 20
        }, 300);
    }
});
