<div class="rp-container rp-beschikbaarheid">
    <header class="rp-header">
        <h1>✅ Beschikbaarheid Doorgeven</h1>
        <div class="rp-header-actions">
            <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
                ← Terug
            </a>
        </div>
    </header>
    
    <?php
    $today = current_time('Y-m-d');
    $deadline_day = date('Y-m-15', strtotime($target_month . '-01'));
    $is_after_deadline = $today > $deadline_day;
    $days_until_deadline = ceil((strtotime($deadline_day) - strtotime($today)) / 86400);
    ?>
    
    <div class="rp-info-bar">
        <div class="rp-month-selector">
            <label>Maand:</label>
            <select id="month-selector" onchange="changeMonth(this.value)">
                <?php for ($i = 1; $i <= 3; $i++): 
                    $month_val = date('Y-m', strtotime("+$i month"));
                    $month_label = date('F Y', strtotime("+$i month"));
                ?>
                <option value="<?php echo $month_val; ?>" <?php selected($target_month, $month_val); ?>>
                    <?php echo $month_label; ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="rp-deadline-info <?php echo $days_until_deadline <= 3 ? 'rp-urgent' : ''; ?>">
            <?php if ($is_after_deadline): ?>
            <span class="rp-alert-text">⚠️ Deadline is verstreken!</span>
            <?php elseif ($days_until_deadline <= 3): ?>
            <span class="rp-alert-text">⚠️ Nog <?php echo $days_until_deadline; ?> dagen tot deadline!</span>
            <?php else: ?>
            <span>📅 Deadline: <?php echo date('d-m-Y', strtotime($deadline_day)); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="rp-location-tabs">
        <?php foreach ($employee_locations as $index => $loc): ?>
        <a href="?month=<?php echo $target_month; ?>&location=<?php echo $loc->location_id; ?>" 
           class="rp-tab <?php echo $location_id == $loc->location_id ? 'rp-active' : ''; ?>">
            <?php echo esc_html($loc->name); ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <form id="availability-form" class="rp-availability-form">
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
                <?php else: 
                    $weekend_class = in_array($day['weekday'], [0, 6]) ? 'rp-weekend' : '';
                    $today_class = $day['is_today'] ? 'rp-today' : '';
                    $has_data = $day['availability'] !== null;
                    $is_available = $day['is_available'];
                    
                    if (!$has_data) {
                        $cell_class = 'rp-no-data';
                    } elseif ($is_available) {
                        $cell_class = 'rp-available';
                    } else {
                        $cell_class = 'rp-unavailable';
                    }
                ?>
                <div class="rp-day <?php echo $weekend_class . ' ' . $today_class; ?>" data-date="<?php echo $day['date']; ?>">
                    <div class="rp-day-header">
                        <span class="rp-day-number"><?php echo $day['day']; ?></span>
                    </div>
                    <div class="rp-day-cell <?php echo $cell_class; ?>">
                        <label class="rp-availability-toggle">
                            <input type="checkbox" 
                                   class="rp-avail-check" 
                                   data-date="<?php echo $day['date']; ?>"
                                   <?php echo $has_data && $is_available ? 'checked' : ''; ?>
                                   <?php echo $is_after_deadline ? 'disabled' : ''; ?>>
                            <span class="rp-toggle-text">
                                <?php echo ($has_data && $is_available) ? '✓' : (($has_data && !$is_available) ? '✗' : '?'); ?>
                            </span>
                        </label>
                        
                        <?php if (!$is_after_deadline): ?>
                        <select class="rp-shift-pref" data-date="<?php echo $day['date']; ?>">
                            <option value="">Geen voorkeur</option>
                            <?php foreach ($shifts as $shift): ?>
                            <option value="<?php echo $shift->id; ?>" 
                                    <?php selected($day['shift_preference'], $shift->id); ?>>
                                <?php echo esc_html($shift->name); ?> (<?php echo substr($shift->start_time, 0, 5); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" 
                               class="rp-notes" 
                               data-date="<?php echo $day['date']; ?>"
                               placeholder="Notities..."
                               value="<?php echo esc_attr($day['notes']); ?>"
                               maxlength="255">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!$is_after_deadline): ?>
        <div class="rp-form-actions">
            <button type="submit" class="rp-btn rp-btn-primary rp-btn-large">
                💾 Beschikbaarheid Opslaan
            </button>
            <div id="save-status" class="rp-save-status"></div>
        </div>
        <?php endif; ?>
    </form>
    
    <div class="rp-instructions">
        <h3>📋 Instructies</h3>
        <ul>
            <li><strong>✓</strong> = Ik ben beschikbaar om te werken</li>
            <li><strong>✗</strong> = Ik ben niet beschikbaar (vrije dag)</li>
            <li>Selecteer je voorkeursshift als je een voorkeur hebt</li>
            <li>Geef eventuele extra informatie bij notities</li>
            <li>Deadline voor doorgeven is de 15e van elke maand</li>
        </ul>
    </div>
</div>

<script>
jQuery('#availability-form').on('submit', function(e) {
    e.preventDefault();
    
    const availability = {};
    
    jQuery('.rp-day[data-date]').each(function() {
        const date = jQuery(this).data('date');
        const isAvailable = jQuery(this).find('.rp-avail-check').is(':checked');
        const shiftId = jQuery(this).find('.rp-shift-pref').val();
        const notes = jQuery(this).find('.rp-notes').val();
        
        availability[date] = {
            available: isAvailable,
            shift_id: shiftId,
            notes: notes
        };
    });
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_submit_availability',
            nonce: rpAjax.nonce,
            location_id: <?php echo $location_id; ?>,
            availability: JSON.stringify(availability)
        },
        beforeSend: function() {
            jQuery('#save-status').html('<span class="rp-saving">Opslaan...</span>');
        },
        success: function(response) {
            if (response.success) {
                jQuery('#save-status').html('<span class="rp-saved">✓ Opgeslagen!</span>');
                setTimeout(function() {
                    jQuery('#save-status').html('');
                }, 3000);
            } else {
                jQuery('#save-status').html('<span class="rp-error">Er is iets misgegaan.</span>');
            }
        },
        error: function() {
            jQuery('#save-status').html('<span class="rp-error">Er is iets misgegaan.</span>');
        }
    });
});

function changeMonth(month) {
    window.location.href = '?month=' + month + '&location=<?php echo $location_id; ?>';
}

// Toggle visual state
jQuery('.rp-avail-check').on('change', function() {
    const $day = jQuery(this).closest('.rp-day-cell');
    const isChecked = jQuery(this).is(':checked');
    
    $day.removeClass('rp-available rp-unavailable rp-no-data');
    if (isChecked) {
        $day.addClass('rp-available');
        jQuery(this).siblings('.rp-toggle-text').text('✓');
    } else {
        $day.addClass('rp-unavailable');
        jQuery(this).siblings('.rp-toggle-text').text('✗');
    }
});
</script>

<style>
.rp-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
.rp-btn-primary { background: #4F46E5; color: #fff; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-btn-large { padding: 14px 28px; font-size: 16px; }
.rp-info-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; padding: 15px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-deadline-info.rp-urgent { color: #dc2626; font-weight: 600; }
.rp-alert-text { animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
.rp-location-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
.rp-tab { padding: 10px 20px; background: #f3f4f6; border-radius: 8px; text-decoration: none; color: #374151; }
.rp-tab.rp-active { background: #4F46E5; color: #fff; }
.rp-calendar-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
.rp-calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); background: #f3f4f6; }
.rp-weekday { text-align: center; padding: 12px; font-weight: 600; color: #6b7280; }
.rp-calendar { display: grid; grid-template-columns: repeat(7, 1fr); min-height: 500px; }
.rp-day { border: 1px solid #e5e7eb; border-top: none; border-left: none; min-height: 100px; padding: 8px; }
.rp-empty { background: #f9fafb; }
.rp-weekend { background: #f3f4f6; }
.rp-today { background: #dbeafe; }
.rp-day-header { margin-bottom: 8px; }
.rp-day-number { font-weight: 600; color: #374151; }
.rp-day-cell { display: flex; flex-direction: column; gap: 6px; }
.rp-availability-toggle { display: flex; align-items: center; justify-content: center; cursor: pointer; margin-bottom: 4px; }
.rp-availability-toggle input { display: none; }
.rp-toggle-text { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; font-size: 18px; font-weight: bold; background: #e5e7eb; transition: all 0.2s; }
.rp-available .rp-toggle-text { background: #d4edda; color: #155724; }
.rp-unavailable .rp-toggle-text { background: #f8d7da; color: #721c24; }
.rp-shift-pref, .rp-notes { width: 100%; padding: 4px 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; }
.rp-notes { resize: none; }
.rp-available { background: #d4edda; }
.rp-unavailable { background: #f8d7da; }
.rp-no-data { background: #f9fafb; }
.rp-form-actions { display: flex; align-items: center; gap: 15px; margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; }
.rp-save-status { font-weight: 500; }
.rp-saving { color: #6b7280; }
.rp-saved { color: #059669; }
.rp-error { color: #dc2626; }
.rp-instructions { margin-top: 20px; padding: 20px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10B981; }
.rp-instructions h3 { margin: 0 0 10px; }
.rp-instructions ul { margin: 0; padding-left: 20px; }
.rp-instructions li { margin: 5px 0; }
@media (max-width: 768px) {
    .rp-calendar { min-height: 300px; }
    .rp-day { min-height: 80px; padding: 4px; }
    .rp-toggle-text { width: 28px; height: 28px; font-size: 14px; }
    .rp-shift-pref, .rp-notes { font-size: 11px; }
}
</style>
