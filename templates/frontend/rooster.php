<header class="rp-header">
    <h1>📅 Mijn Rooster</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
    </div>
</header>
    
    <div class="rp-controls">
        <div class="rp-month-nav">
            <a href="?month=<?php echo date('Y-m', strtotime($current_month . ' -1 month')); ?>" class="rp-nav-btn">
                ← Vorige
            </a>
            <span class="rp-current-month"><?php echo date('F Y', strtotime($current_month . '-01')); ?></span>
            <a href="?month=<?php echo date('Y-m', strtotime($current_month . ' +1 month')); ?>" class="rp-nav-btn">
                Volgende →
            </a>
        </div>
        
        <?php if ($employee->is_admin): ?>
        <div class="rp-view-toggle">
            <a href="?view=personal" class="rp-btn rp-btn-small <?php echo $view === 'personal' ? 'rp-btn-primary' : 'rp-btn-secondary'; ?>">
                Persoonlijk
            </a>
            <a href="?view=all" class="rp-btn rp-btn-small <?php echo $view === 'all' ? 'rp-btn-primary' : 'rp-btn-secondary'; ?>">
                Algemeen
            </a>
        </div>
        <?php endif; ?>
        
        <div class="rp-display-toggle">
            <button type="button" class="rp-btn rp-btn-small rp-btn-secondary" onclick="toggleViewMode('calendar')" id="btn-calendar-view">
                📅 Kalender
            </button>
            <button type="button" class="rp-btn rp-btn-small rp-btn-secondary" onclick="toggleViewMode('list')" id="btn-list-view">
                📋 Lijst
            </button>
        </div>
        
        <?php if (!$is_finalized && $view !== 'all'): ?>
        <span class="rp-not-finalized-badge">⏳ Rooster nog niet definitief</span>
        <?php endif; ?>
    </div>
    
    <?php if ($view === 'all' && $employee->is_admin): ?>
    <div class="rp-location-filter">
        <label>Locatie:</label>
        <select onchange="window.location.href='?view=all&location=' + this.value + '&month=<?php echo $current_month; ?>'">
            <?php foreach ($locations as $location): ?>
            <option value="<?php echo $location->id; ?>" <?php selected($location_id, $location->id); ?>>
                <?php echo esc_html($location->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <?php if (!$is_finalized && $view !== 'all'): ?>
    <!-- Month not finalized - show message instead of calendar -->
    <div class="rp-not-finalized-message">
        <div class="rp-message-content">
            <h3>⏳ Rooster nog niet definitief</h3>
            <p>Het rooster voor <?php echo date('F Y', strtotime($current_month . '-01')); ?> is nog niet definitief gemaakt door de planner.</p>
            <p>Je ontvangt automatisch een notificatie zodra je rooster beschikbaar is.</p>
        </div>
    </div>
    <?php else: ?>
    <!-- Show calendar with schedules -->
    <div id="calendar-view" class="rp-view-container">
        <div class="rp-calendar-wrapper">
            <div class="rp-calendar-header">
                <div class="rp-weekday">Zo</div>
                <div class="rp-weekday">Ma</div>
                <div class="rp-weekday">Di</div>
                <div class="rp-weekday">Wo</div>
                <div class="rp-weekday">Do</div>
                <div class="rp-weekday">Vr</div>
                <div class="rp-weekday">Za</div>
            </div>
            <div class="rp-calendar">
                <?php foreach ($calendar as $day): ?>
                <?php if ($day['type'] === 'empty'): ?>
                <div class="rp-day rp-empty"></div>
                <?php else: ?>
                <div class="rp-day <?php echo $day['is_today'] ? 'rp-today' : ''; ?>">
                    <div class="rp-day-header">
                        <span class="rp-day-number"><?php echo $day['day']; ?></span>
                    </div>
                    <div class="rp-day-content">
                        <?php foreach ($day['schedules'] as $schedule): 
                            $is_past = strtotime($schedule->work_date . ' ' . $schedule->end_time) < current_time('timestamp');
                            $is_started = strtotime($schedule->work_date . ' ' . $schedule->start_time) < current_time('timestamp');
                        ?>
                        <div class="rp-schedule-item <?php echo $is_past ? 'rp-past-shift' : ''; ?>" style="border-left-color: <?php echo $schedule->color; ?>">
                            <div class="rp-schedule-time"><?php echo substr($schedule->start_time, 0, 5); ?></div>
                            <div class="rp-schedule-name"><?php echo esc_html($schedule->shift_name); ?></div>
                            <?php if ($view === 'all' && !empty($schedule->employee_name)): ?>
                            <div class="rp-schedule-employee"><?php echo esc_html($schedule->employee_name); ?></div>
                            <?php endif; ?>
                            <?php if ($view === 'personal'): ?>
                            <div class="rp-schedule-actions">
                                <?php if (!$is_started): ?>
                                <a href="<?php echo home_url('/medewerker-ruilen/?action=swap&schedule=' . $schedule->id); ?>" class="rp-link">
                                    Ruilen
                                </a>
                                <?php endif; ?>
                                <?php if ($is_past && get_option('rooster_planner_enable_worked_hours', 0)): ?>
                                <button type="button" class="rp-link" onclick="openHoursModal(<?php echo $schedule->id; ?>)">
                                    <?php echo $schedule->actual_start_time ? 'Uren wijzigen' : 'Uren invullen'; ?>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- List View -->
    <div id="list-view" class="rp-view-container" style="display:none;">
        <div class="rp-list-wrapper">
            <?php
            // Collect all schedules with dates for list view
            $list_schedules = [];
            foreach ($calendar as $day) {
                if ($day['type'] !== 'empty' && !empty($day['schedules'])) {
                    foreach ($day['schedules'] as $schedule) {
                        $list_schedules[] = array_merge((array)$schedule, ['day' => $day['day'], 'is_today' => $day['is_today']]);
                    }
                }
            }
            // Sort by date then time
            usort($list_schedules, function($a, $b) {
                $date_compare = strcmp($a['work_date'], $b['work_date']);
                if ($date_compare !== 0) return $date_compare;
                return strcmp($a['start_time'], $b['start_time']);
            });
            ?>
            
            <?php if (empty($list_schedules)): ?>
            <div class="rp-empty-list">
                <p>Geen diensten gepland voor deze maand.</p>
            </div>
            <?php else: ?>
            <div class="rp-list">
                <?php 
                $current_date = '';
                foreach ($list_schedules as $schedule): 
                    $is_past = strtotime($schedule['work_date'] . ' ' . $schedule['end_time']) < current_time('timestamp');
                    $is_started = strtotime($schedule['work_date'] . ' ' . $schedule['start_time']) < current_time('timestamp');
                    
                    if ($current_date !== $schedule['work_date']) {
                        $current_date = $schedule['work_date'];
                        $date_obj = strtotime($schedule['work_date']);
                        $weekday = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'][date('w', $date_obj)];
                ?>
                <div class="rp-list-date-header <?php echo $schedule['is_today'] ? 'rp-today-header' : ''; ?>">
                    <span class="rp-list-weekday"><?php echo $weekday; ?></span>
                    <span class="rp-list-date"><?php echo date('d M', $date_obj); ?></span>
                </div>
                <?php } ?>
                
                <div class="rp-list-item <?php echo $is_past ? 'rp-past-item' : ''; ?>" style="border-left-color: <?php echo $schedule['color']; ?>">
                    <div class="rp-list-time">
                        <?php echo substr($schedule['start_time'], 0, 5) . ' - ' . substr($schedule['end_time'], 0, 5); ?>
                    </div>
                    <div class="rp-list-details">
                        <div class="rp-list-name"><?php echo esc_html($schedule['shift_name']); ?></div>
                        <?php if ($view === 'all' && !empty($schedule['employee_name'])): ?>
                        <div class="rp-list-employee"><?php echo esc_html($schedule['employee_name']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($schedule['location_name'])): ?>
                        <div class="rp-list-location">📍 <?php echo esc_html($schedule['location_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($view === 'personal'): ?>
                    <div class="rp-list-actions">
                        <?php if (!$is_started): ?>
                        <a href="<?php echo home_url('/medewerker-ruilen/?action=swap&schedule=' . $schedule['id']); ?>" class="rp-btn rp-btn-small rp-btn-secondary">
                            Ruilen
                        </a>
                        <?php endif; ?>
                        <?php if ($is_past && get_option('rooster_planner_enable_worked_hours', 0)): ?>
                        <button type="button" class="rp-btn rp-btn-small rp-btn-primary" onclick="openHoursModal(<?php echo $schedule['id']; ?>)">
                            <?php echo !empty($schedule['actual_start_time']) ? 'Wijzigen' : 'Uren'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($view === 'personal' && get_option('rooster_planner_enable_worked_hours', 0)): ?>
    <?php 
    // Get completed shifts for this month
    global $wpdb;
    $completed_shifts = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, sh.name as shift_name, sh.start_time as planned_start, sh.end_time as planned_end,
        l.name as location_name
        FROM {$wpdb->prefix}rp_schedules s
        LEFT JOIN {$wpdb->prefix}rp_shifts sh ON s.shift_id = sh.id
        LEFT JOIN {$wpdb->prefix}rp_locations l ON s.location_id = l.id
        WHERE s.employee_id = %d AND s.work_date LIKE %s 
        AND s.work_date < %s
        AND s.status != 'cancelled'
        ORDER BY s.work_date DESC",
        $employee->id, $current_month . '%', current_time('Y-m-d')
    ));
    ?>
    <?php if (!empty($completed_shifts)): ?>
    <div class="rp-completed-shifts">
        <h3>✅ Gewerkte Diensten - Uren Invullen</h3>
        <div class="rp-completed-list">
            <?php foreach ($completed_shifts as $shift): 
                $has_hours = !empty($shift->actual_start_time) && !empty($shift->actual_end_time);
                $shift_datetime = $shift->work_date . ' ' . $shift->planned_end;
                $can_enter_hours = strtotime($shift_datetime) < current_time('timestamp');
            ?>
            <div class="rp-completed-item <?php echo $has_hours ? 'rp-hours-filled' : 'rp-hours-pending'; ?>">
                <div class="rp-completed-info">
                    <strong><?php echo date('d-m-Y', strtotime($shift->work_date)); ?></strong> - 
                    <?php echo esc_html($shift->shift_name); ?>
                    <span class="rp-location">📍 <?php echo esc_html($shift->location_name); ?></span>
                    <span class="rp-planned">Gepland: <?php echo substr($shift->planned_start, 0, 5) . ' - ' . substr($shift->planned_end, 0, 5); ?></span>
                    <?php if ($has_hours): ?>
                    <span class="rp-actual">Gewerkt: <?php echo substr($shift->actual_start_time, 0, 5) . ' - ' . substr($shift->actual_end_time, 0, 5); ?></span>
                    <?php endif; ?>
                </div>
                <div class="rp-completed-actions">
                    <?php if ($can_enter_hours): ?>
                    <button type="button" class="rp-btn rp-btn-small <?php echo $has_hours ? 'rp-btn-secondary' : 'rp-btn-primary'; ?>" onclick="openHoursModal(<?php echo $shift->id; ?>)">
                        <?php echo $has_hours ? 'Wijzigen' : 'Uren invullen'; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    
    <div class="rp-legend">
        <h3>Legenda</h3>
        <?php foreach ($shifts as $shift): ?>
        <span class="rp-legend-item">
            <span class="rp-color-dot" style="background:<?php echo $shift->color; ?>"></span>
            <?php echo esc_html($shift->name); ?> (<?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?>)
        </span>
        <?php endforeach; ?>
    </div>

<!-- Hours Input Modal -->
<div id="rp-hours-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content">
        <div class="rp-modal-header">
            <h3>📝 Gewerkte Uren Invullen</h3>
            <button type="button" class="rp-modal-close" onclick="closeHoursModal()">&times;</button>
        </div>
        <div class="rp-modal-body">
            <form id="rp-hours-form">
                <input type="hidden" id="hours_schedule_id" name="schedule_id">
                <div class="rp-form-row">
                    <label>Werkelijke starttijd:</label>
                    <input type="time" id="hours_start_time" name="actual_start_time" required>
                </div>
                <div class="rp-form-row">
                    <label>Werkelijke eindtijd:</label>
                    <input type="time" id="hours_end_time" name="actual_end_time" required>
                </div>
                <div class="rp-form-row">
                    <label>Pauze (minuten):</label>
                    <input type="number" id="hours_break" name="break_minutes" min="0" value="0">
                </div>
                <div class="rp-form-actions">
                    <button type="submit" class="rp-btn rp-btn-primary">Opslaan</button>
                    <button type="button" class="rp-btn rp-btn-secondary" onclick="closeHoursModal()">Annuleren</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rp-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.rp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}
.rp-header h1 {
    margin: 0;
}
.rp-btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}
.rp-btn-primary {
    background: #4F46E5;
    color: #fff;
}
.rp-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}
.rp-btn-small {
    padding: 6px 12px;
    font-size: 13px;
}
.rp-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}
.rp-month-nav {
    display: flex;
    align-items: center;
    gap: 15px;
}
.rp-nav-btn {
    padding: 8px 16px;
    background: #f3f4f6;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
}
.rp-current-month {
    font-size: 18px;
    font-weight: 600;
}
.rp-display-toggle {
    display: flex;
    gap: 5px;
}

/* List View Styles */
.rp-view-container {
    width: 100%;
}

.rp-list-wrapper {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
    max-height: 600px;
    overflow-y: auto;
}

.rp-empty-list {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

.rp-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rp-list-date-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f3f4f6;
    border-radius: 8px;
    margin-top: 10px;
    font-weight: 600;
}

.rp-list-date-header.rp-today-header {
    background: #dbeafe;
    color: #4F46E5;
}

.rp-list-weekday {
    font-size: 14px;
    text-transform: uppercase;
}

.rp-list-date {
    font-size: 16px;
}

.rp-list-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid;
}

.rp-list-item.rp-past-item {
    opacity: 0.7;
}

.rp-list-time {
    font-weight: 600;
    color: #4F46E5;
    font-size: 14px;
    min-width: 100px;
}

.rp-list-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rp-list-name {
    font-weight: 500;
    color: #1f2937;
    font-size: 15px;
}

.rp-list-employee {
    font-size: 13px;
    color: #6b7280;
}

.rp-list-location {
    font-size: 13px;
    color: #6b7280;
}

.rp-list-actions {
    display: flex;
    gap: 8px;
}

@media (max-width: 768px) {
    .rp-display-toggle {
        width: 100%;
    }
    .rp-display-toggle .rp-btn {
        flex: 1;
    }
    .rp-list-wrapper {
        padding: 10px;
        max-height: 70vh;
    }
    .rp-list-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 12px;
    }
    .rp-list-time {
        min-width: auto;
    }
    .rp-list-actions {
        width: 100%;
    }
    .rp-list-actions .rp-btn {
        flex: 1;
    }
}

@media (max-width: 480px) {
    .rp-list-date-header {
        padding: 10px 12px;
    }
    .rp-list-weekday {
        font-size: 12px;
    }
    .rp-list-date {
        font-size: 14px;
    }
    .rp-list-name {
        font-size: 14px;
    }
}
.rp-location-filter {
    margin-bottom: 20px;
}
.rp-location-filter select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
}
.rp-calendar-wrapper {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}
.rp-calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f3f4f6;
}
.rp-weekday {
    text-align: center;
    padding: 12px;
    font-weight: 600;
    color: #6b7280;
}
.rp-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    min-height: 500px;
}
.rp-day {
    border: 1px solid #e5e7eb;
    border-top: none;
    border-left: none;
    min-height: 100px;
    padding: 8px;
}
.rp-day:nth-child(7n) {
    border-right: none;
}
.rp-empty {
    background: #f9fafb;
}
.rp-today {
    background: #dbeafe;
}
.rp-day-header {
    margin-bottom: 5px;
}
.rp-day-number {
    font-weight: 600;
    color: #374151;
}
.rp-today .rp-day-number {
    color: #4F46E5;
}
.rp-day-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.rp-schedule-item {
    padding: 6px 8px;
    background: #f9fafb;
    border-radius: 4px;
    border-left: 3px solid;
    font-size: 12px;
}
.rp-schedule-time {
    font-weight: 600;
    color: #4F46E5;
}
.rp-schedule-name {
    color: #374151;
}
.rp-schedule-employee {
    color: #6b7280;
    font-size: 11px;
}
.rp-schedule-actions {
    margin-top: 4px;
}
.rp-link {
    color: #4F46E5;
    text-decoration: none;
    font-size: 11px;
}
.rp-legend {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
}
.rp-legend h3 {
    margin: 0 0 10px;
    font-size: 14px;
}
.rp-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-right: 20px;
    margin-bottom: 8px;
    font-size: 13px;
}
.rp-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}
@media (max-width: 768px) {
    .rp-calendar {
        min-height: 300px;
    }
    .rp-day {
        min-height: 60px;
        padding: 4px;
    }
    .rp-schedule-item {
        padding: 3px 4px;
        font-size: 10px;
    }
    .rp-legend-item {
        display: flex;
    }
}

/* Completed Shifts Section */
.rp-completed-shifts {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.rp-completed-shifts h3 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #1f2937;
}
.rp-completed-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.rp-completed-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #d1d5db;
}
.rp-completed-item.rp-hours-filled {
    border-left-color: #10B981;
    background: #f0fdf4;
}
.rp-completed-item.rp-hours-pending {
    border-left-color: #f59e0b;
    background: #fffbeb;
}
.rp-completed-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.rp-completed-info strong {
    color: #1f2937;
}
.rp-completed-info .rp-location {
    color: #6b7280;
    font-size: 13px;
}
.rp-completed-info .rp-planned {
    color: #6b7280;
    font-size: 13px;
}
.rp-completed-info .rp-actual {
    color: #059669;
    font-size: 13px;
    font-weight: 500;
}
.rp-past-shift {
    opacity: 0.7;
}
.rp-past-shift .rp-schedule-time {
    color: #6b7280;
}

/* Modal Styles */
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
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
.rp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}
.rp-modal-header h3 {
    margin: 0;
    font-size: 18px;
}
.rp-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
}
.rp-modal-close:hover {
    color: #1f2937;
}
.rp-modal-body {
    padding: 20px;
}
.rp-form-row {
    margin-bottom: 15px;
}
.rp-form-row label {
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    color: #374151;
}
.rp-form-row input {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}
.rp-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.rp-form-actions .rp-btn {
    flex: 1;
}

/* Not Finalized Styles */
.rp-not-finalized-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: #fffbeb;
    color: #d97706;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid #fcd34d;
}
.rp-not-finalized-message {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 40px;
    text-align: center;
}
.rp-message-content h3 {
    color: #d97706;
    font-size: 20px;
    margin: 0 0 15px 0;
}
.rp-message-content p {
    color: #6b7280;
    margin: 8px 0;
}
</style>

<script>
function toggleViewMode(mode) {
    var calendarView = document.getElementById('calendar-view');
    var listView = document.getElementById('list-view');
    var btnCalendar = document.getElementById('btn-calendar-view');
    var btnList = document.getElementById('btn-list-view');
    
    if (mode === 'calendar') {
        calendarView.style.display = 'block';
        listView.style.display = 'none';
        btnCalendar.classList.remove('rp-btn-secondary');
        btnCalendar.classList.add('rp-btn-primary');
        btnList.classList.remove('rp-btn-primary');
        btnList.classList.add('rp-btn-secondary');
        localStorage.setItem('rp_schedule_view', 'calendar');
    } else {
        calendarView.style.display = 'none';
        listView.style.display = 'block';
        btnCalendar.classList.remove('rp-btn-primary');
        btnCalendar.classList.add('rp-btn-secondary');
        btnList.classList.remove('rp-btn-secondary');
        btnList.classList.add('rp-btn-primary');
        localStorage.setItem('rp_schedule_view', 'list');
    }
}

// Restore view preference on page load
jQuery(document).ready(function() {
    var savedView = localStorage.getItem('rp_schedule_view');
    if (savedView === 'list') {
        toggleViewMode('list');
    }
});

function openHoursModal(scheduleId) {
    document.getElementById('hours_schedule_id').value = scheduleId;
    document.getElementById('rp-hours-modal').style.display = 'flex';
    
    // Load existing hours if any
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_get_schedule_data',
            nonce: rpAjax.nonce,
            schedule_id: scheduleId
        },
        success: function(response) {
            if (response.success && response.data.schedule) {
                var schedule = response.data.schedule;
                if (schedule.actual_start_time) {
                    document.getElementById('hours_start_time').value = schedule.actual_start_time.substring(0, 5);
                }
                if (schedule.actual_end_time) {
                    document.getElementById('hours_end_time').value = schedule.actual_end_time.substring(0, 5);
                }
                if (schedule.break_minutes) {
                    document.getElementById('hours_break').value = schedule.break_minutes;
                }
            }
        }
    });
}

function closeHoursModal() {
    document.getElementById('rp-hours-modal').style.display = 'none';
    document.getElementById('rp-hours-form').reset();
}

jQuery('#rp-hours-form').on('submit', function(e) {
    e.preventDefault();
    
    var formData = {
        action: 'rp_save_schedule',
        nonce: rpAjax.nonce,
        schedule_id: document.getElementById('hours_schedule_id').value,
        actual_start_time: document.getElementById('hours_start_time').value,
        actual_end_time: document.getElementById('hours_end_time').value,
        break_minutes: document.getElementById('hours_break').value
    };
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Uren succesvol opgeslagen!');
                location.reload();
            } else {
                alert('Er is een fout opgetreden: ' + response.data);
            }
        },
        error: function() {
            alert('Er is een fout opgetreden bij het opslaan.');
        }
    });
});

// Close modal when clicking outside
jQuery(document).on('click', '#rp-hours-modal', function(e) {
    if (e.target === this) {
        closeHoursModal();
    }
});
</script>
