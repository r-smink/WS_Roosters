/**
 * Rooster Planner Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initScheduleCalendar();
        initBulkActions();
    });

    /**
     * Schedule Calendar Functions
     */
    function initScheduleCalendar() {
        // Make days droppable for drag-drop scheduling
        $('.rp-day').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('rp-drag-over');
        });

        $('.rp-day').on('dragleave', function() {
            $(this).removeClass('rp-drag-over');
        });

        $('.rp-day').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('rp-drag-over');
            
            const employeeId = e.originalEvent.dataTransfer.getData('employeeId');
            const shiftId = e.originalEvent.dataTransfer.getData('shiftId');
            const date = $(this).data('date');
            
            if (employeeId && shiftId && date) {
                quickSchedule(employeeId, shiftId, date);
            }
        });
    }

    function quickSchedule(employeeId, shiftId, date) {
        showLoading();
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_save_schedule',
                nonce: rpAjax.nonce,
                employee_id: employeeId,
                shift_id: shiftId,
                work_date: date,
                location_id: $('#location-filter').val()
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    location.reload();
                } else {
                    alert('Er is iets misgegaan: ' + response.data);
                }
            },
            error: function() {
                hideLoading();
                alert('Er is een fout opgetreden.');
            }
        });
    }

    /**
     * Bulk Actions
     */
    function initBulkActions() {
        $('#rp-bulk-schedule').on('click', function() {
            const selectedEmployees = [];
            const selectedDays = [];
            const shiftId = $('#bulk-shift').val();
            
            $('.rp-employee-check:checked').each(function() {
                selectedEmployees.push($(this).val());
            });
            
            $('.rp-day-check:checked').each(function() {
                selectedDays.push($(this).val());
            });
            
            if (!selectedEmployees.length || !selectedDays.length || !shiftId) {
                alert('Selecteer medewerkers, dagen en een shift.');
                return;
            }
            
            bulkSchedule(selectedEmployees, selectedDays, shiftId);
        });
    }

    function bulkSchedule(employees, dates, shiftId) {
        showLoading();
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_bulk_schedule',
                nonce: rpAjax.nonce,
                employee_id: employees[0], // For now, one at a time
                dates: JSON.stringify(dates),
                shift_id: shiftId,
                location_id: $('#location-filter').val()
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    alert(response.data.inserted + ' diensten ingepland!');
                    location.reload();
                }
            }
        });
    }

    /**
     * Fixed Schedule Template
     */
    window.applyFixedSchedule = function(employeeId) {
        const month = prompt('Voor welke maand? (YYYY-MM)', new Date().toISOString().slice(0, 7));
        if (!month) return;
        
        showLoading();
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_apply_fixed_schedule',
                nonce: rpAjax.nonce,
                employee_id: employeeId,
                month: month
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    alert(response.data.inserted + ' vaste diensten toegepast!');
                    location.reload();
                }
            }
        });
    };

    /**
     * Employee Availability Popup
     */
    window.showEmployeeAvailability = function(employeeId, employeeName) {
        const month = $('#month-filter').val() || new Date().toISOString().slice(0, 7);
        
        showLoading();
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_get_employee_availability',
                nonce: rpAjax.nonce,
                employee_id: employeeId,
                month: month
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    renderAvailabilityModal(employeeName, response.data.availability);
                }
            }
        });
    };

    function renderAvailabilityModal(employeeName, availability) {
        let html = `
            <div class="rp-modal" id="availability-modal">
                <div class="rp-modal-content">
                    <h2>Beschikbaarheid: ${employeeName}</h2>
                    <div class="rp-availability-grid">
        `;
        
        availability.forEach(function(a) {
            const status = a.is_available ? '✓ Beschikbaar' : '✗ Niet beschikbaar';
            const shift = a.shift_name || 'Geen voorkeur';
            html += `
                <div class="rp-availability-item ${a.is_available ? 'rp-available' : 'rp-unavailable'}">
                    <strong>${new Date(a.work_date).getDate()}</strong>
                    <span>${status}</span>
                    <small>${shift}</small>
                </div>
            `;
        });
        
        html += `
                    </div>
                    <button type="button" class="button" onclick="jQuery('#availability-modal').remove()">Sluiten</button>
                </div>
            </div>
        `;
        
        $('body').append(html);
    }

    /**
     * Loading Overlay
     */
    function showLoading() {
        if (!$('.rp-loading-overlay').length) {
            $('body').append('<div class="rp-loading-overlay"><div class="rp-spinner"></div></div>');
        }
    }

    function hideLoading() {
        $('.rp-loading-overlay').remove();
    }

    /**
     * Export Functions
     */
    window.exportToCSV = function(type) {
        const locationId = $('#location-filter').val();
        const month = $('#month-filter').val();
        
        window.location.href = `${rpAjax.ajaxUrl}?action=rp_export&nonce=${rpAjax.nonce}&type=${type}&location=${locationId}&month=${month}`;
    };

})(jQuery);
