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
                    <?php foreach ($day['schedules'] as $schedule): ?>
                    <div class="rp-schedule-item" style="border-left-color: <?php echo $schedule->color; ?>">
                        <div class="rp-schedule-time"><?php echo substr($schedule->start_time, 0, 5); ?></div>
                        <div class="rp-schedule-name"><?php echo esc_html($schedule->shift_name); ?></div>
                        <?php if ($view === 'all' && !empty($schedule->employee_name)): ?>
                        <div class="rp-schedule-employee"><?php echo esc_html($schedule->employee_name); ?></div>
                        <?php endif; ?>
                        <?php if ($view === 'personal'): ?>
                        <div class="rp-schedule-actions">
                            <a href="<?php echo home_url('/medewerker-ruilen/?action=swap&schedule=' . $schedule->id); ?>" class="rp-link">
                                Ruilen
                            </a>
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
    
    <div class="rp-legend">
        <h3>Legenda</h3>
        <?php foreach ($shifts as $shift): ?>
        <span class="rp-legend-item">
            <span class="rp-color-dot" style="background:<?php echo $shift->color; ?>"></span>
            <?php echo esc_html($shift->name); ?> (<?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?>)
        </span>
        <?php endforeach; ?>
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
.rp-view-toggle {
    display: flex;
    gap: 5px;
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
</style>
