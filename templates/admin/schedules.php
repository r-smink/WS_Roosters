<div class="wrap rp-admin-wrap">
    <h1>Roosters Plannen</h1>
    
    <div class="rp-controls">
        <div class="rp-filters">
            <label>Locatie:</label>
            <select id="location-filter" onchange="changeLocation(this.value)">
                <?php foreach ($locations as $location): ?>
                <option value="<?php echo $location->id; ?>" <?php selected($current_location, $location->id); ?>>
                    <?php echo esc_html($location->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <label style="margin-left:20px;">Maand:</label>
            <input type="month" id="month-filter" value="<?php echo $current_month; ?>" onchange="changeMonth(this.value)">
            
            <button type="button" class="button" onclick="showDuplicateModal()" style="margin-left:20px;">
                📋 Dupliceer vorige maand
            </button>
            
            <button type="button" class="button button-secondary" onclick="showAutoScheduleModal()" style="margin-left:10px;">
                🤖 Auto-planning
            </button>
            
            <button type="button" class="button button-link-delete" onclick="clearCalendar()" style="margin-left:10px;">
                🗑️ Kalender Leegmaken
            </button>
            
            <?php
            // Check if month is already finalized
            global $wpdb;
            $is_finalized = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rp_final_schedules WHERE location_id = %d AND month = %s",
                $current_location, $current_month
            ));
            ?>
            
            <?php if ($is_finalized): ?>
            <span class="rp-finalized-badge" style="margin-left:10px; color: #10B981; font-weight: 600;">
                ✅ Maand definitief
            </span>
            <?php else: ?>
            <button type="button" class="button button-success" onclick="finalizeMonth()" style="margin-left:10px; background: #10B981; color: white; border-color: #10B981;">
                🔒 Maand Definitief Maken
            </button>
            <?php endif; ?>
        </div>
        
        <div class="rp-view-toggle">
            <button type="button" class="button <?php echo (!isset($_GET['view']) || $_GET['view'] === 'calendar') ? 'button-primary' : ''; ?>" onclick="changeView('calendar')">
                📅 Kalender
            </button>
            <button type="button" class="button <?php echo (isset($_GET['view']) && $_GET['view'] === 'list') ? 'button-primary' : ''; ?>" onclick="changeView('list')">
                📋 Lijst
            </button>
            <button type="button" class="button <?php echo (isset($_GET['view']) && $_GET['view'] === 'table') ? 'button-primary' : ''; ?>" onclick="changeView('table')">
                📊 Tabel (Excel)
            </button>
        </div>
        
        <div class="rp-actions">
            <button type="button" class="button button-primary" onclick="showAddModal()">
                ➕ Dienst Toevoegen
            </button>
        </div>
    </div>
    
    <?php
    $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'calendar';
    
    // Get schedules for current month and location
    $start_date = $current_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    global $wpdb;
    $schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, sh.name as shift_name, sh.color,
        u.display_name as employee_name, e.id as emp_id, e.is_fixed
        FROM {$wpdb->prefix}rp_schedules s
        LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
        LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        WHERE s.location_id = %d AND s.work_date BETWEEN %s AND %s AND s.status != 'cancelled'
        ORDER BY s.work_date, s.start_time",
        $current_location, $start_date, $end_date
    ));
    
    // Get all shifts for this location
    $location_shifts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rp_shifts WHERE location_id = %d AND is_active = 1 ORDER BY start_time",
        $current_location
    ));
    
    if ($current_view === 'calendar'):
        // Group by date
        $schedule_by_date = [];
        foreach ($schedules as $schedule) {
            $schedule_by_date[$schedule->work_date][] = $schedule;
        }
        
        // Build calendar
        $first_day = strtotime($start_date);
        $days_in_month = date('t', $first_day);
        $start_weekday = date('w', $first_day);
        $weekdays = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'];
    ?>
    
    <div class="rp-calendar-container">
        <div class="rp-calendar-header">
            <?php foreach ($weekdays as $day): ?>
            <div class="rp-weekday"><?php echo $day; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="rp-calendar">
            <?php
            // Empty cells
            for ($i = 0; $i < $start_weekday; $i++) {
                echo '<div class="rp-day rp-empty"></div>';
            }
            
            // Days
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = $current_month . '-' . sprintf('%02d', $day);
                $day_schedules = $schedule_by_date[$date] ?? [];
                $is_today = $date === current_time('Y-m-d');
                ?>
                <div class="rp-day <?php echo $is_today ? 'rp-today' : ''; ?> drop-zone"
                     data-date="<?php echo $date; ?>"
                     ondragover="allowDrop(event)"
                     ondrop="dropSchedule(event)">
                    <div class="rp-day-number"><?php echo $day; ?></div>
                    <div class="rp-day-schedules">
                        <?php foreach ($day_schedules as $schedule): ?>
                        <div class="rp-schedule-item draggable-schedule" 
                             draggable="true"
                             data-schedule-id="<?php echo $schedule->id; ?>"
                             data-employee-id="<?php echo $schedule->employee_id; ?>"
                             data-shift-id="<?php echo $schedule->shift_id; ?>"
                             style="background:<?php echo $schedule->color; ?>20; border-left: 3px solid <?php echo $schedule->color; ?>"
                             onclick="editSchedule(<?php echo $schedule->id; ?>)">
                            <span class="rp-schedule-time"><?php echo substr($schedule->start_time, 0, 5); ?></span>
                            <span class="rp-schedule-name"><?php echo esc_html($schedule->shift_name); ?></span>
                            <span class="rp-schedule-employee"><?php echo esc_html($schedule->employee_name); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="rp-add-btn" onclick="showAddModal('<?php echo $date; ?>')">+</button>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    
    <?php elseif ($current_view === 'list'): ?>
    <!-- LIST VIEW -->
    <div class="rp-list-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Shift</th>
                    <th>Tijd</th>
                    <th>Medewerker</th>
                    <th>Status</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): 
                    $status_labels = [
                        'scheduled' => 'Gepland',
                        'confirmed' => 'Bevestigd',
                        'completed' => 'Afgerond',
                        'swapped' => 'Geruild'
                    ];
                    $status_class = $schedule->status === 'completed' ? 'rp-status-success' : 
                                   ($schedule->status === 'confirmed' ? 'rp-status-info' : 'rp-status-default');
                ?>
                <tr>
                    <td><?php echo date('d-m-Y (D)', strtotime($schedule->work_date)); ?></td>
                    <td><span class="rp-shift-badge" style="background:<?php echo $schedule->color; ?>20; border-left:3px solid <?php echo $schedule->color; ?>"><?php echo esc_html($schedule->shift_name); ?></span></td>
                    <td><?php echo substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5); ?></td>
                    <td><?php echo esc_html($schedule->employee_name); ?> <?php echo $schedule->is_fixed ? '⭐' : ''; ?></td>
                    <td><span class="rp-status-badge <?php echo $status_class; ?>"><?php echo $status_labels[$schedule->status] ?? $schedule->status; ?></span></td>
                    <td><button type="button" class="button" onclick="editSchedule(<?php echo $schedule->id; ?>)">Bewerken</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php elseif ($current_view === 'table'): ?>
    <!-- EXCEL-LIKE TABLE VIEW -->
    <div class="rp-table-container">
        <div class="rp-table-scroll">
            <table class="rp-excel-table">
                <thead>
                    <tr>
                        <th class="rp-sticky-col">Datum / Shift</th>
                        <?php foreach ($location_shifts as $shift): ?>
                        <th>
                            <div class="rp-shift-header" style="border-left:4px solid <?php echo $shift->color; ?>">
                                <strong><?php echo esc_html($shift->name); ?></strong>
                                <small><?php echo substr($shift->start_time, 0, 5) . '-' . substr($shift->end_time, 0, 5); ?></small>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Group schedules by date and shift for quick lookup
                    $schedules_by_date_shift = [];
                    foreach ($schedules as $s) {
                        $schedules_by_date_shift[$s->work_date][$s->shift_id][] = $s;
                    }
                    
                    for ($day = 1; $day <= date('t', strtotime($start_date)); $day++):
                        $date = $current_month . '-' . sprintf('%02d', $day);
                        $is_today = $date === current_time('Y-m-d');
                        $day_name = date('D', strtotime($date));
                    ?>
                    <tr class="<?php echo $is_today ? 'rp-today-row' : ''; ?> <?php echo in_array($day_name, ['Sat', 'Sun']) ? 'rp-weekend-row' : ''; ?>">
                        <td class="rp-sticky-col rp-date-cell">
                            <strong><?php echo date('d-m', strtotime($date)); ?></strong>
                            <small><?php echo $day_name; ?></small>
                        </td>
                        <?php foreach ($location_shifts as $shift): 
                            $cell_schedules = $schedules_by_date_shift[$date][$shift->id] ?? [];
                        ?>
                        <td class="rp-shift-cell <?php echo count($cell_schedules) === 0 ? 'rp-empty-cell' : ''; ?> drop-zone"
                            data-date="<?php echo $date; ?>"
                            data-shift-id="<?php echo $shift->id; ?>"
                            ondragover="allowDrop(event)"
                            ondrop="dropScheduleTable(event)">
                            <?php foreach ($cell_schedules as $cell_s): ?>
                            <div class="rp-cell-employee draggable-schedule"
                                 draggable="true"
                                 data-schedule-id="<?php echo $cell_s->id; ?>"
                                 data-employee-id="<?php echo $cell_s->employee_id; ?>"
                                 data-shift-id="<?php echo $shift->id; ?>"
                                 onclick="editSchedule(<?php echo $cell_s->id; ?>)">
                                <?php echo esc_html($cell_s->employee_name); ?>
                                <?php echo $cell_s->is_fixed ? '<span title="Vaste medewerker">⭐</span>' : ''; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($cell_schedules) === 0): ?>
                            <button type="button" class="button button-small" onclick="showAddModal('<?php echo $date; ?>')">+</button>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Schedule Modal -->
<div id="add-schedule-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content">
        <h2>Dienst Plannen</h2>
        <form id="add-schedule-form">
            <input type="hidden" id="schedule_id" name="schedule_id">
            <input type="hidden" id="location_id" name="location_id" value="<?php echo $current_location; ?>">
            
            <p>
                <label>Datum:</label>
                <input type="date" id="work_date" name="work_date" required>
            </p>
            
            <p>
                <label>Medewerker:</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">-- Selecteer --</option>
                    <?php foreach ($employees as $employee): 
                        $assigned_locs = $employee->assigned_locations ? explode(',', $employee->assigned_locations) : [];
                        $has_location = in_array($current_location, $assigned_locs);
                    ?>
                    <option value="<?php echo $employee->id; ?>" 
                            data-locations="<?php echo esc_attr($employee->assigned_locations); ?>"
                            data-fixed="<?php echo $employee->is_fixed ? '1' : '0'; ?>"
                            data-availability="<?php echo esc_attr(json_encode($availability_by_employee[$employee->id] ?? [])); ?>"
                            <?php if (!$has_location) echo 'style="display:none;"'; ?>>
                        <?php echo esc_html($employee->display_name); ?>
                        <?php if ($employee->is_fixed) echo '⭐'; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="description">
                    Alleen medewerkers met beschikbaarheid voor deze datum worden getoond. 
                    <a href="#" onclick="showAllEmployees(); return false;">Toon alle</a>
                </small>
            </p>
            
            <p>
                <label>Shift:</label>
                <select id="shift_id" name="shift_id" required>
                    <option value="">-- Selecteer --</option>
                    <?php foreach ($shifts as $shift): ?>
                    <option value="<?php echo $shift->id; ?>" 
                            data-start="<?php echo $shift->start_time; ?>"
                            data-end="<?php echo $shift->end_time; ?>"
                            data-location="<?php echo $shift->location_id; ?>">
                        <?php echo esc_html($shift->name . ' (' . substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5) . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label>Aangepaste tijden (optioneel):</label><br>
                <input type="time" id="start_time" name="start_time" placeholder="Start">
                <input type="time" id="end_time" name="end_time" placeholder="Eind">
            </p>
            
            <?php if (get_option('rooster_planner_enable_worked_hours', 0)): ?>
            <div id="worked-hours-section" style="margin-top:15px; padding-top:15px; border-top:1px solid #ddd;">
                <p><strong>Gewerkte uren invullen</strong></p>
                <p>
                    <label>Werkelijke starttijd:</label>
                    <input type="time" id="actual_start_time" name="actual_start_time">
                </p>
                <p>
                    <label>Werkelijke eindtijd:</label>
                    <input type="time" id="actual_end_time" name="actual_end_time">
                </p>
                <p>
                    <label>Pauze (minuten):</label>
                    <input type="number" id="break_minutes" name="break_minutes" min="0" step="5" value="0" style="width:100px;">
                </p>
            </div>
            <?php endif; ?>
            
            <p>
                <label>Notities:</label>
                <textarea id="notes" name="notes" rows="2"></textarea>
            </p>
            
            <p>
                <label style="display:flex;align-items:center;gap:10px;">
                    <input type="checkbox" id="apply-fixed">
                    Gebruik vaste rooster template
                </label>
            </p>
            
            <div class="rp-modal-actions">
                <button type="submit" class="button button-primary">Opslaan</button>
                <button type="button" class="button" onclick="closeModal('add-schedule-modal')">Annuleren</button>
                <button type="button" id="delete-schedule-btn" class="button button-link-delete" style="display:none;" onclick="deleteSchedule()">Verwijderen</button>
            </div>
        </form>
    </div>
</div>

<!-- Duplicate Modal -->
<div id="duplicate-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content">
        <h2>Maand Dupliceren</h2>
        <form method="post">
            <?php wp_nonce_field('rp_admin_action'); ?>
            <input type="hidden" name="rp_action" value="duplicate_schedule">
            <input type="hidden" name="location_id" value="<?php echo $current_location; ?>">
            
            <p>
                <label>Van maand:</label>
                <input type="month" name="source_month" value="<?php echo date('Y-m', strtotime($current_month . ' -1 month')); ?>" required>
            </p>
            
            <p>
                <label>Naar maand:</label>
                <input type="month" name="target_month" value="<?php echo $current_month; ?>" required>
            </p>
            
            <div class="rp-modal-actions">
                <button type="submit" class="button button-primary">Dupliceren</button>
                <button type="button" class="button" onclick="closeModal('duplicate-modal')">Annuleren</button>
            </div>
        </form>
    </div>
</div>

<!-- Auto-Schedule Modal -->
<div id="auto-schedule-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content" style="min-width: 500px;">
        <h2>Automatische Planning</h2>
        <p class="description">Vul automatisch het rooster in op basis van beschikbaarheid, locatie en contracturen.</p>
        
        <form id="auto-schedule-form">
            <input type="hidden" name="location_id" value="<?php echo $current_location; ?>">
            <input type="hidden" name="month" value="<?php echo $current_month; ?>">
            
            <p>
                <label>Maand:</label>
                <strong><?php echo date('F Y', strtotime($current_month . '-01')); ?></strong>
            </p>
            
            <p>
                <label>Locatie:</label>
                <strong><?php echo esc_html($locations[array_search($current_location, array_column($locations, 'id'))]->name ?? 'Onbekend'); ?></strong>
            </p>
            
            <div style="margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px;">
                <p style="margin: 0 0 10px 0; font-weight: 500;">Opties:</p>
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <input type="checkbox" name="respect_contract_hours" value="1" checked>
                    <span>Houd rekening met contracturen</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <input type="checkbox" name="respect_availability" value="1" checked>
                    <span>Alleen medewerkers met beschikbaarheid</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <input type="checkbox" name="balance_shifts" value="1" checked>
                    <span>Verdeel shifts gelijkmatig over medewerkers</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="overwrite_existing" value="1">
                    <span>Overschrijf bestaande planning (let op!)</span>
                </label>
            </div>
            
            <div class="rp-modal-actions">
                <button type="submit" class="button button-primary">Start Auto-planning</button>
                <button type="button" class="button" onclick="closeModal('auto-schedule-modal')">Annuleren</button>
            </div>
        </form>
        
        <div id="auto-schedule-results" style="display:none; margin-top: 20px;">
            <h3>Resultaten</h3>
            <div id="auto-schedule-summary"></div>
            <h4 style="margin-top: 15px;">Openstaande shifts:</h4>
            <div id="open-shifts-list" style="max-height: 300px; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<!-- Conflict Resolution Modal -->
<div id="conflict-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content" style="min-width: 600px; max-width: 800px;">
        <h2>Shift Conflict Oplossen</h2>
        <p class="description">Meerdere medewerkers hebben dezelfde voorkeur voor deze shift. Selecteer wie deze shift krijgt.</p>
        
        <div id="conflict-details" style="margin: 20px 0; padding: 15px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
            <p><strong>Datum:</strong> <span id="conflict-date"></span></p>
            <p><strong>Shift:</strong> <span id="conflict-shift"></span></p>
        </div>
        
        <div id="conflict-employees-list" style="max-height: 400px; overflow-y: auto; margin: 20px 0;">
            <!-- Employees will be listed here -->
        </div>
        
        <div class="rp-modal-actions">
            <button type="button" class="button button-primary" onclick="resolveConflict()">Toewijzen</button>
            <button type="button" class="button" onclick="skipConflict()">Overslaan</button>
            <button type="button" class="button" onclick="closeModal('conflict-modal')">Annuleren</button>
        </div>
    </div>
</div>

<script>
function changeView(view) {
    const url = new URL(window.location.href);
    url.searchParams.set('view', view);
    window.location.href = url.toString();
}

function changeLocation(loc) {
    const url = new URL(window.location.href);
    url.searchParams.set('location', loc);
    window.location.href = url.toString();
}

function changeMonth(month) {
    const url = new URL(window.location.href);
    url.searchParams.set('month', month);
    window.location.href = url.toString();
}

function showAddModal(date = '') {
    document.getElementById('schedule_id').value = '';
    document.getElementById('work_date').value = date;
    document.getElementById('employee_id').value = '';
    document.getElementById('shift_id').value = '';
    document.getElementById('start_time').value = '';
    document.getElementById('end_time').value = '';
    document.getElementById('notes').value = '';
    document.getElementById('delete-schedule-btn').style.display = 'none';
    
    <?php if (get_option('rooster_planner_enable_worked_hours', 0)): ?>
    document.getElementById('actual_start_time').value = '';
    document.getElementById('actual_end_time').value = '';
    document.getElementById('break_minutes').value = '0';
    <?php endif; ?>
    
    filterShiftsByLocation();
    filterEmployeesByAvailability(date);
    document.getElementById('add-schedule-modal').style.display = 'flex';
}

function filterEmployeesByAvailability(date) {
    const employeeSelect = document.getElementById('employee_id');
    const currentLocation = document.getElementById('location_id').value;
    
    Array.from(employeeSelect.options).forEach(option => {
        if (option.value === '') return;
        
        const locations = option.dataset.locations ? option.dataset.locations.split(',') : [];
        const isFixed = option.dataset.fixed === '1';
        const availability = JSON.parse(option.dataset.availability || '{}');
        
        // Check if employee is assigned to current location
        const hasLocation = locations.includes(currentLocation);
        
        // Check if employee has availability for this date (or is fixed)
        const hasAvailability = isFixed || (date && availability[date]);
        
        // Show if has location AND (has availability OR no date selected yet)
        const show = hasLocation && (isFixed || !date || hasAvailability);
        option.style.display = show ? '' : 'none';
    });
}

function showAllEmployees() {
    const employeeSelect = document.getElementById('employee_id');
    Array.from(employeeSelect.options).forEach(option => {
        if (option.value === '') return;
        const locations = option.dataset.locations ? option.dataset.locations.split(',') : [];
        const currentLocation = document.getElementById('location_id').value;
        option.style.display = locations.includes(currentLocation) ? '' : 'none';
    });
}

function filterShiftsByLocation() {
    const currentLocation = document.getElementById('location_id').value;
    const shiftSelect = document.getElementById('shift_id');
    
    Array.from(shiftSelect.options).forEach(option => {
        if (option.value === '') return;
        const locationId = option.getAttribute('data-location');
        option.style.display = (locationId === currentLocation) ? '' : 'none';
    });
    
    // Reset selection if currently selected shift is for different location
    const selectedOption = shiftSelect.options[shiftSelect.selectedIndex];
    if (selectedOption && selectedOption.value !== '') {
        const selectedLocation = selectedOption.getAttribute('data-location');
        if (selectedLocation !== currentLocation) {
            shiftSelect.value = '';
        }
    }
}

function editSchedule(id) {
    // Load schedule data via AJAX
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_get_schedule_data',
            nonce: rpAjax.nonce,
            schedule_id: id
        },
        success: function(response) {
            if (response.success) {
                const s = response.data.schedule;
                document.getElementById('schedule_id').value = s.id;
                document.getElementById('work_date').value = s.work_date;
                document.getElementById('employee_id').value = s.employee_id;
                document.getElementById('shift_id').value = s.shift_id;
                document.getElementById('start_time').value = s.start_time || '';
                document.getElementById('end_time').value = s.end_time || '';
                document.getElementById('notes').value = s.notes || '';
                document.getElementById('delete-schedule-btn').style.display = 'inline-block';
                
                <?php if (get_option('rooster_planner_enable_worked_hours', 0)): ?>
                document.getElementById('actual_start_time').value = s.actual_start_time || '';
                document.getElementById('actual_end_time').value = s.actual_end_time || '';
                document.getElementById('break_minutes').value = s.break_minutes || '0';
                <?php endif; ?>
                
                filterShiftsByLocation();
                document.getElementById('add-schedule-modal').style.display = 'flex';
            }
        }
    });
}

function deleteSchedule() {
    if (!confirm('Weet je zeker dat je deze dienst wilt verwijderen?')) return;
    
    const id = document.getElementById('schedule_id').value;
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_delete_schedule',
            nonce: rpAjax.nonce,
            schedule_id: id
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
}

function showDuplicateModal() {
    document.getElementById('duplicate-modal').style.display = 'flex';
}

function showAutoScheduleModal() {
    document.getElementById('auto-schedule-modal').style.display = 'flex';
    document.getElementById('auto-schedule-results').style.display = 'none';
    document.getElementById('auto-schedule-form').style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Auto-Schedule form handler
jQuery('#auto-schedule-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = jQuery(this).serialize();
    const submitBtn = jQuery(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    
    submitBtn.prop('disabled', true).text('Bezig met plannen...');
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_auto_schedule',
            nonce: rpAjax.nonce,
            data: formData
        },
        success: function(response) {
            submitBtn.prop('disabled', false).text(originalText);
            
            if (response.success) {
                const data = response.data;
                
                // Show results
                document.getElementById('auto-schedule-form').style.display = 'none';
                document.getElementById('auto-schedule-results').style.display = 'block';
                
                // Summary
                document.getElementById('auto-schedule-summary').innerHTML = `
                    <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="margin: 0;"><strong>${data.scheduled} shifts gepland</strong></p>
                        <p style="margin: 5px 0 0 0; color: #155724;">
                            ${data.respected_contract_hours ? '✓ Contracturen gerespecteerd' : ''}
                            ${data.respected_availability ? '<br>✓ Alleen beschikbare medewerkers gebruikt' : ''}
                        </p>
                    </div>
                `;
                
                // Open shifts list
                let openShiftsHtml = '';
                if (data.open_shifts && data.open_shifts.length > 0) {
                    openShiftsHtml = '<table class="widefat" style="margin-top: 10px;">';
                    openShiftsHtml += '<thead><tr><th>Datum</th><th>Shift</th><th>Tijd</th><th>Actie</th></tr></thead><tbody>';
                    
                    data.open_shifts.forEach(function(shift) {
                        openShiftsHtml += `
                            <tr>
                                <td>${shift.date}</td>
                                <td><span style="background:${shift.color}20; border-left:3px solid ${shift.color}; padding:2px 8px;">${shift.shift_name}</span></td>
                                <td>${shift.time}</td>
                                <td><button type="button" class="button button-small" onclick="showAddModal('${shift.date}'); closeModal('auto-schedule-modal');">Invullen</button></td>
                            </tr>
                        `;
                    });
                    
                    openShiftsHtml += '</tbody></table>';
                } else {
                    openShiftsHtml = '<p style="color: #059669;">✅ Alle shifts zijn ingevuld!</p>';
                }
                
                document.getElementById('open-shifts-list').innerHTML = openShiftsHtml;
                
                // Reload page after delay to show new schedules
                setTimeout(function() {
                    if (confirm('Planning voltooid! Pagina herladen om wijzigingen te zien?')) {
                        location.reload();
                    }
                }, 500);
            } else {
                alert('Fout bij auto-planning: ' + response.data);
            }
        },
        error: function() {
            submitBtn.prop('disabled', false).text(originalText);
            alert('Er is een fout opgetreden bij het uitvoeren van de auto-planning.');
        }
    });
});

jQuery('#add-schedule-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        action: 'rp_save_schedule',
        nonce: rpAjax.nonce,
        schedule_id: document.getElementById('schedule_id').value,
        location_id: document.getElementById('location_id').value,
        work_date: document.getElementById('work_date').value,
        employee_id: document.getElementById('employee_id').value,
        shift_id: document.getElementById('shift_id').value,
        start_time: document.getElementById('start_time').value,
        end_time: document.getElementById('end_time').value,
        notes: document.getElementById('notes').value
    };
    
    <?php if (get_option('rooster_planner_enable_worked_hours', 0)): ?>
    formData.actual_start_time = document.getElementById('actual_start_time').value;
    formData.actual_end_time = document.getElementById('actual_end_time').value;
    formData.break_minutes = document.getElementById('break_minutes').value;
    <?php endif; ?>
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
});

// Auto-fill times when shift changes
jQuery('#shift_id').on('change', function() {
    const option = this.options[this.selectedIndex];
    if (option.dataset.start) {
        document.getElementById('start_time').value = option.dataset.start;
        document.getElementById('end_time').value = option.dataset.end;
    }
});

// Auto-fill custom times when employee changes
jQuery('#employee_id').on('change', function() {
    const date = document.getElementById('work_date').value;
    const option = this.options[this.selectedIndex];
    if (option.value && date) {
        const availability = JSON.parse(option.dataset.availability || '{}');
        if (availability[date]) {
            const avail = availability[date];
            // If employee has custom times set, use those
            if (avail.custom_start && avail.custom_end) {
                document.getElementById('start_time').value = avail.custom_start;
                document.getElementById('end_time').value = avail.custom_end;
            } else if (avail.shift_id) {
                // If employee has a shift preference, auto-select that shift
                document.getElementById('shift_id').value = avail.shift_id;
                // Trigger shift change to fill times
                jQuery('#shift_id').trigger('change');
            }
        }
    }
});

// Auto-filter employees when date changes
jQuery('#work_date').on('change', function() {
    const date = this.value;
    if (date) {
        filterEmployeesByAvailability(date);
    }
});

// Drag and Drop functionality
let draggedScheduleId = null;
let draggedEmployeeId = null;
let draggedShiftId = null;

document.addEventListener('DOMContentLoaded', function() {
    initDragAndDrop();
});

function initDragAndDrop() {
    const draggables = document.querySelectorAll('.draggable-schedule');
    
    draggables.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    });
}

function handleDragStart(e) {
    draggedScheduleId = this.getAttribute('data-schedule-id');
    draggedEmployeeId = this.getAttribute('data-employee-id');
    draggedShiftId = this.getAttribute('data-shift-id');
    
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', draggedScheduleId);
    
    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.classList.add('drop-active');
    });
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.classList.remove('drop-active');
        zone.classList.remove('drop-hover');
    });
}

function allowDrop(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const dropZone = e.target.closest('.drop-zone');
    if (dropZone) {
        dropZone.classList.add('drop-hover');
    }
}

function dropSchedule(e) {
    e.preventDefault();
    const dropZone = e.target.closest('.drop-zone');
    if (!dropZone) return;
    dropZone.classList.remove('drop-hover');
    
    const newDate = dropZone.getAttribute('data-date');
    if (!draggedScheduleId || !newDate) return;
    
    if (!confirm('Dienst verplaatsen naar ' + formatDate(newDate) + '?')) return;
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_move_schedule',
            nonce: rpAjax.nonce,
            schedule_id: draggedScheduleId,
            new_date: newDate
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Fout bij verplaatsen: ' + response.data);
            }
        },
        error: function() {
            alert('Er is een fout opgetreden bij het verplaatsen van de dienst.');
        }
    });
}

function dropScheduleTable(e) {
    e.preventDefault();
    const dropZone = e.target.closest('.drop-zone');
    if (!dropZone) return;
    dropZone.classList.remove('drop-hover');
    
    const newDate = dropZone.getAttribute('data-date');
    const newShiftId = dropZone.getAttribute('data-shift-id');
    if (!draggedScheduleId || !newDate || !newShiftId) return;
    
    if (!confirm('Dienst verplaatsen naar ' + formatDate(newDate) + '?')) return;
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_move_schedule',
            nonce: rpAjax.nonce,
            schedule_id: draggedScheduleId,
            new_date: newDate,
            new_shift_id: newShiftId
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Fout bij verplaatsen: ' + response.data);
            }
        },
        error: function() {
            alert('Er is een fout opgetreden bij het verplaatsen van de dienst.');
        }
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' });
}

// Finalize month function
function finalizeMonth() {
    const month = document.getElementById('month-filter').value;
    const locationId = document.getElementById('location-filter').value;
    
    if (!confirm('Deze maand definitief maken?\n\nAlle medewerkers ontvangen één notificatie met een overzicht van hun diensten.\n\nDaarna worden er geen notificaties meer verstuurd bij het toevoegen van nieuwe diensten, behalve bij wijzigingen.')) {
        return;
    }
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_finalize_month',
            nonce: rpAjax.nonce,
            location_id: locationId,
            month: month
        },
        success: function(response) {
            if (response.success) {
                alert('Maand succesvol definitief gemaakt!\n\n' + response.data.message);
                location.reload();
            } else {
                alert('Fout: ' + response.data);
            }
        },
        error: function() {
            alert('Er is een fout opgetreden.');
        }
    });
}

// Clear calendar function
function clearCalendar() {
    const month = document.getElementById('month-filter').value;
    const locationId = document.getElementById('location-filter').value;
    
    if (!confirm('⚠️ WAARSCHUWING\n\nDit verwijdert ALLE diensten voor ' + month + '!\n\nDeze actie kan niet ongedaan worden gemaakt.\n\nWeet je zeker dat je door wilt gaan?')) {
        return;
    }
    
    if (!confirm('Dubbele check: Alle diensten voor deze maand en locatie worden verwijderd.\n\nZeker weten?')) {
        return;
    }
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_clear_calendar',
            nonce: rpAjax.nonce,
            location_id: locationId,
            month: month
        },
        beforeSend: function() {
            jQuery('button[onclick="clearCalendar()"]').prop('disabled', true).text('Bezig met leegmaken...');
        },
        success: function(response) {
            if (response.success) {
                alert('Kalender succesvol geleegd! ' + response.data.deleted + ' diensten verwijderd.');
                location.reload();
            } else {
                alert('Fout: ' + response.data);
                jQuery('button[onclick="clearCalendar()"]').prop('disabled', false).text('🗑️ Kalender Leegmaken');
            }
        },
        error: function() {
            alert('Er is een fout opgetreden.');
            jQuery('button[onclick="clearCalendar()"]').prop('disabled', false).text('🗑️ Kalender Leegmaken');
        }
    });
}

// Conflict Resolution Variables
let currentConflicts = [];
let currentConflictIndex = 0;
let autoScheduleFormData = null;

// Auto-Schedule form handler with conflict detection
jQuery('#auto-schedule-form').on('submit', function(e) {
    e.preventDefault();
    
    autoScheduleFormData = jQuery(this).serialize();
    const submitBtn = jQuery(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    
    submitBtn.prop('disabled', true).text('Bezig met plannen...');
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_auto_schedule',
            nonce: rpAjax.nonce,
            data: autoScheduleFormData,
            resolve_conflicts: true
        },
        success: function(response) {
            submitBtn.prop('disabled', false).text(originalText);
            
            if (response.success) {
                const data = response.data;
                
                // Check if there are conflicts to resolve
                if (data.conflicts && data.conflicts.length > 0) {
                    currentConflicts = data.conflicts;
                    currentConflictIndex = 0;
                    document.getElementById('auto-schedule-modal').style.display = 'none';
                    showConflictModal();
                } else {
                    // No conflicts, show results
                    showAutoScheduleResults(data);
                }
            } else {
                alert('Fout bij auto-planning: ' + response.data);
            }
        },
        error: function() {
            submitBtn.prop('disabled', false).text(originalText);
            alert('Er is een fout opgetreden bij het uitvoeren van de auto-planning.');
        }
    });
});

function showConflictModal() {
    if (currentConflictIndex >= currentConflicts.length) {
        // All conflicts resolved, continue with auto-schedule
        closeModal('conflict-modal');
        finalizeAutoSchedule();
        return;
    }
    
    const conflict = currentConflicts[currentConflictIndex];
    document.getElementById('conflict-date').textContent = conflict.date;
    document.getElementById('conflict-shift').textContent = conflict.shift_name + ' (' + conflict.shift_time + ')';
    
    // Build employee list
    let employeesHtml = '<table class="widefat" style="width:100%;">';
    employeesHtml += '<thead><tr><th>Select</th><th>Medewerker</th><th>Uren deze week</th><th>Eigen tijd</th><th>Info</th></tr></thead>';
    employeesHtml += '<tbody>';
    
    conflict.employees.forEach(function(emp, index) {
        const hasCustomTime = emp.custom_start && emp.custom_end;
        const customTimeText = hasCustomTime ? emp.custom_start + ' - ' + emp.custom_end : 'Standaard';
        const weekHoursText = emp.weekly_hours.toFixed(1) + ' / ' + (emp.contract_hours || '∞') + ' uur';
        
        employeesHtml += `
            <tr style="${index === 0 ? 'background:#f0f9ff;' : ''}">
                <td><input type="radio" name="conflict_employee" value="${emp.employee_id}" ${index === 0 ? 'checked' : ''}></td>
                <td><strong>${emp.employee_name}</strong></td>
                <td>${weekHoursText}</td>
                <td>${customTimeText}</td>
                <td>${emp.contract_hours > 0 && emp.weekly_hours >= emp.contract_hours ? '<span style="color:#f59e0b;">⚠️ Contracturen bereikt</span>' : ''}</td>
            </tr>
        `;
    });
    
    employeesHtml += '</tbody></table>';
    employeesHtml += '<p style="margin-top:10px;color:#6b7280;font-size:12px;">Selecteer de medewerker die deze shift krijgt:</p>';
    
    document.getElementById('conflict-employees-list').innerHTML = employeesHtml;
    document.getElementById('conflict-modal').style.display = 'flex';
}

function resolveConflict() {
    const selectedEmployee = document.querySelector('input[name="conflict_employee"]:checked');
    if (!selectedEmployee) {
        alert('Selecteer een medewerker');
        return;
    }
    
    const employeeId = selectedEmployee.value;
    const conflict = currentConflicts[currentConflictIndex];
    
    // Save the resolved conflict choice
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_resolve_schedule_conflict',
            nonce: rpAjax.nonce,
            date: conflict.date,
            shift_id: conflict.shift_id,
            employee_id: employeeId,
            custom_start: conflict.employees.find(e => e.employee_id == employeeId)?.custom_start || null,
            custom_end: conflict.employees.find(e => e.employee_id == employeeId)?.custom_end || null
        },
        success: function(response) {
            if (response.success) {
                currentConflictIndex++;
                showConflictModal();
            } else {
                alert('Fout bij toewijzen: ' + response.data);
            }
        }
    });
}

function skipConflict() {
    currentConflictIndex++;
    showConflictModal();
}

function finalizeAutoSchedule() {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_auto_schedule_finalize',
            nonce: rpAjax.nonce,
            data: autoScheduleFormData
        },
        success: function(response) {
            if (response.success) {
                showAutoScheduleResults(response.data);
            } else {
                alert('Fout bij finaliseren: ' + response.data);
            }
        }
    });
}

function showAutoScheduleResults(data) {
    document.getElementById('auto-schedule-form').style.display = 'none';
    document.getElementById('auto-schedule-results').style.display = 'block';
    
    // Summary
    document.getElementById('auto-schedule-summary').innerHTML = `
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin: 0;"><strong>${data.scheduled} shifts gepland</strong></p>
            <p style="margin: 5px 0 0 0; color: #155724;">
                ${data.respected_contract_hours ? '✓ Contracturen gerespecteerd' : ''}
                ${data.respected_availability ? '<br>✓ Alleen beschikbare medewerkers gebruikt' : ''}
                ${data.resolved_conflicts ? '<br>✓ ' + data.resolved_conflicts + ' conflicten opgelost' : ''}
            </p>
        </div>
    `;
    
    // Open shifts list
    let openShiftsHtml = '';
    if (data.open_shifts && data.open_shifts.length > 0) {
        openShiftsHtml = '<table class="widefat" style="margin-top: 10px;">';
        openShiftsHtml += '<thead><tr><th>Datum</th><th>Shift</th><th>Tijd</th><th>Actie</th></tr></thead><tbody>';
        
        data.open_shifts.forEach(function(shift) {
            openShiftsHtml += `
                <tr>
                    <td>${shift.date}</td>
                    <td><span style="background:${shift.color}20; border-left:3px solid ${shift.color}; padding:2px 8px;">${shift.shift_name}</span></td>
                    <td>${shift.time}</td>
                    <td><button type="button" class="button button-small" onclick="showAddModal('${shift.date}'); closeModal('auto-schedule-modal');">Invullen</button></td>
                </tr>
            `;
        });
        
        openShiftsHtml += '</tbody></table>';
    } else {
        openShiftsHtml = '<p style="color: #059669;">✅ Alle shifts zijn ingevuld!</p>';
    }
    
    document.getElementById('open-shifts-list').innerHTML = openShiftsHtml;
    
    // Reload page after delay
    setTimeout(function() {
        if (confirm('Planning voltooid! Pagina herladen om wijzigingen te zien?')) {
            location.reload();
        }
    }, 500);
}
</script>

<style>
.rp-admin-wrap { max-width: 1400px; }
.rp-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-filters label {
    font-weight: 500;
    margin-right: 5px;
}
.rp-calendar-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-bottom: 2px;
}
.rp-weekday {
    text-align: center;
    font-weight: 600;
    padding: 10px;
    background: #f3f4f6;
}
.rp-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.rp-day {
    min-height: 120px;
    background: #fafafa;
    padding: 8px;
    position: relative;
}
.rp-empty { background: #f3f4f6; }
.rp-today { background: #dbeafe; border: 2px solid #3b82f6; }
.rp-day-number {
    font-weight: 600;
    margin-bottom: 5px;
}
.rp-day-schedules {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.rp-schedule-item {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
}
.rp-schedule-item:hover { opacity: 0.8; }
.rp-schedule-time { font-weight: 600; }
.rp-schedule-name, .rp-schedule-employee {
    display: block;
}
.rp-add-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: #4F46E5;
    color: #fff;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
}
.rp-day:hover .rp-add-btn { opacity: 1; }
.rp-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.rp-modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    min-width: 450px;
    max-height: 90vh;
    overflow-y: auto;
}
.rp-modal-content p {
    margin: 15px 0;
}
.rp-modal-content label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.rp-modal-content input[type="date"],
.rp-modal-content input[type="time"],
.rp-modal-content select,
.rp-modal-content textarea {
    width: 100%;
    padding: 8px;
}
.rp-modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* View Toggle */
.rp-view-toggle {
    display: flex;
    gap: 5px;
}
.rp-view-toggle .button {
    padding: 5px 12px;
}

/* List View Styles */
.rp-list-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-shift-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.rp-status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}
.rp-status-success { background: #d4edda; color: #155724; }
.rp-status-info { background: #dbeafe; color: #1e40af; }
.rp-status-default { background: #f3f4f6; color: #374151; }

/* Excel-like Table View */
.rp-table-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-table-scroll {
    overflow-x: auto;
    max-width: 100%;
}
.rp-excel-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}
.rp-excel-table th,
.rp-excel-table td {
    border: 1px solid #e5e7eb;
    padding: 8px;
    text-align: left;
}
.rp-excel-table th {
    background: #f9fafb;
    font-weight: 600;
}
.rp-sticky-col {
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 10;
    min-width: 100px;
}
.rp-shift-header {
    display: flex;
    flex-direction: column;
    padding: 4px 8px;
}
.rp-shift-header small {
    color: #6b7280;
    font-weight: normal;
}
.rp-date-cell {
    display: flex;
    flex-direction: column;
    background: #f9fafb;
}
.rp-shift-cell {
    min-width: 120px;
    vertical-align: top;
}
.rp-empty-cell {
    background: #f9fafb;
    text-align: center;
}
.rp-cell-employee {
    padding: 4px 8px;
    margin: 2px 0;
    background: #dbeafe;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}
.rp-cell-employee:hover {
    background: #bfdbfe;
}
.rp-today-row {
    background: #fef3c7 !important;
}
.rp-today-row .rp-sticky-col {
    background: #fef3c7;
}
.rp-weekend-row {
    background: #f3f4f6;
}
.rp-weekend-row .rp-sticky-col {
    background: #f3f4f6;
}

/* Drag and Drop Styles */
.draggable-schedule {
    cursor: grab;
    transition: all 0.2s ease;
}
.draggable-schedule:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.draggable-schedule.dragging {
    cursor: grabbing;
    opacity: 0.5;
    transform: scale(0.95);
}
.drop-zone {
    transition: all 0.2s ease;
}
.drop-zone.drop-active {
    background: #f0f9ff !important;
    border: 2px dashed #3b82f6 !important;
}
.drop-zone.drop-hover {
    background: #dbeafe !important;
    border: 2px solid #3b82f6 !important;
    box-shadow: inset 0 0 0 3px rgba(59, 130, 246, 0.2);
}

/* Finalized Month Badge */
.rp-finalized-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: #d4edda;
    border-radius: 4px;
    font-size: 14px;
}
</style>
