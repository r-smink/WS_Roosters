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
        </div>
        
        <div class="rp-actions">
            <button type="button" class="button button-primary" onclick="showAddModal()">
                ➕ Dienst Toevoegen
            </button>
        </div>
    </div>
    
    <?php
    // Get schedules for current month and location
    $start_date = $current_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    global $wpdb;
    $schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, sh.color,
        u.display_name as employee_name, e.id as emp_id
        FROM {$wpdb->prefix}rp_schedules s
        LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
        LEFT JOIN {$wpdb->prefix}rp_employees e ON s.employee_id = e.id
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        WHERE s.location_id = %d AND s.work_date BETWEEN %s AND %s AND s.status != 'cancelled'
        ORDER BY s.work_date, sh.start_time",
        $current_location, $start_date, $end_date
    ));
    
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
                <div class="rp-day <?php echo $is_today ? 'rp-today' : ''; ?>">
                    <div class="rp-day-number"><?php echo $day; ?></div>
                    <div class="rp-day-schedules">
                        <?php foreach ($day_schedules as $schedule): ?>
                        <div class="rp-schedule-item" 
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
                    <?php foreach ($employees as $employee): ?>
                    <option value="<?php echo $employee->id; ?>"><?php echo esc_html($employee->display_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label>Shift:</label>
                <select id="shift_id" name="shift_id" required>
                    <option value="">-- Selecteer --</option>
                    <?php foreach ($shifts as $shift): ?>
                    <option value="<?php echo $shift->id; ?>" 
                            data-start="<?php echo $shift->start_time; ?>"
                            data-end="<?php echo $shift->end_time; ?>">
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

<script>
function changeLocation(loc) {
    window.location.href = '<?php echo admin_url('admin.php?page=rooster-planner-schedules'); ?>&location=' + loc + '&month=' + document.getElementById('month-filter').value;
}

function changeMonth(month) {
    window.location.href = '<?php echo admin_url('admin.php?page=rooster-planner-schedules'); ?>&location=' + document.getElementById('location-filter').value + '&month=' + month;
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
    document.getElementById('add-schedule-modal').style.display = 'flex';
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

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

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
</style>
