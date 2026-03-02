<header class="rp-header">
    <h1>🏖️ Verlof Aanvragen</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
    </div>
</header>

<div class="rp-info-bar">
    <p>Stuur een verlofaanvraag naar de planner. Je ontvangt een bericht wanneer deze is goedgekeurd of afgekeurd.</p>
</div>

<div class="rp-timeoff-form-wrapper">
    <form id="timeoff-form" class="rp-timeoff-form">
        <div class="rp-form-group">
            <label for="start_date">Start datum *</label>
            <input type="date" id="start_date" name="start_date" required min="<?php echo current_time('Y-m-d'); ?>">
        </div>
        
        <div class="rp-form-group">
            <label for="end_date">Eind datum *</label>
            <input type="date" id="end_date" name="end_date" required min="<?php echo current_time('Y-m-d'); ?>">
        </div>
        
        <div class="rp-form-group">
            <label for="type">Type verlof *</label>
            <select id="type" name="type" required>
                <option value="vacation">Vakantie</option>
                <option value="personal">Persoonlijk</option>
                <option value="appointment">Afspraak/dokter</option>
                <option value="other">Overig</option>
            </select>
        </div>
        
        <div class="rp-form-group">
            <label for="reason">Reden / Toelichting</label>
            <textarea id="reason" name="reason" rows="4" placeholder="Geef een korte toelichting voor je verlofaanvraag..."></textarea>
        </div>
        
        <div class="rp-form-actions">
            <button type="submit" class="rp-btn rp-btn-primary rp-btn-large">
                📤 Verlof Aanvragen
            </button>
            <div id="save-status" class="rp-save-status"></div>
        </div>
    </form>
</div>

<div class="rp-timeoff-history">
    <h3>📋 Mijn Verlofaanvragen</h3>
    <?php if (empty($my_timeoff_requests)): ?>
    <p class="rp-no-data">Je hebt nog geen verlofaanvragen ingediend.</p>
    <?php else: ?>
    <div class="rp-timeoff-list">
        <?php foreach ($my_timeoff_requests as $request): 
            $status_class = '';
            $status_text = '';
            switch($request->status) {
                case 'pending':
                    $status_class = 'rp-status-pending';
                    $status_text = '⏳ In behandeling';
                    break;
                case 'approved':
                    $status_class = 'rp-status-approved';
                    $status_text = '✅ Goedgekeurd';
                    break;
                case 'rejected':
                    $status_class = 'rp-status-rejected';
                    $status_text = '❌ Afgekeurd';
                    break;
            }
            $type_labels = [
                'vacation' => '🏖️ Vakantie',
                'personal' => '👤 Persoonlijk',
                'appointment' => '🏥 Afspraak',
                'other' => '📝 Overig'
            ];
        ?>
        <div class="rp-timeoff-item <?php echo $status_class; ?>">
            <div class="rp-timeoff-header">
                <span class="rp-timeoff-type"><?php echo $type_labels[$request->type] ?? $request->type; ?></span>
                <span class="rp-timeoff-status"><?php echo $status_text; ?></span>
            </div>
            <div class="rp-timeoff-dates">
                <?php echo date('d-m-Y', strtotime($request->start_date)); ?> 
                t/m 
                <?php echo date('d-m-Y', strtotime($request->end_date)); ?>
                (<?php echo $request->days_requested; ?> dagen)
            </div>
            <?php if ($request->reason): ?>
            <div class="rp-timeoff-reason"><?php echo esc_html($request->reason); ?></div>
            <?php endif; ?>
            <?php if ($request->admin_notes): ?>
            <div class="rp-timeoff-admin-notes">
                <strong>Reactie planner:</strong> <?php echo esc_html($request->admin_notes); ?>
            </div>
            <?php endif; ?>
            <div class="rp-timeoff-date">
                Aangevraagd: <?php echo date('d-m-Y', strtotime($request->created_at)); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery('#timeoff-form').on('submit', function(e) {
    e.preventDefault();
    
    const startDate = jQuery('#start_date').val();
    const endDate = jQuery('#end_date').val();
    
    if (new Date(endDate) < new Date(startDate)) {
        jQuery('#save-status').html('<span class="rp-error">Eind datum moet na start datum liggen.</span>');
        return;
    }
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_request_timeoff',
            nonce: rpAjax.nonce,
            start_date: startDate,
            end_date: endDate,
            type: jQuery('#type').val(),
            reason: jQuery('#reason').val()
        },
        beforeSend: function() {
            jQuery('#save-status').html('<span class="rp-saving">Verzenden...</span>');
        },
        success: function(response) {
            if (response.success) {
                jQuery('#save-status').html('<span class="rp-saved">✓ Aanvraag verzonden!</span>');
                jQuery('#timeoff-form')[0].reset();
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                jQuery('#save-status').html('<span class="rp-error">' + (response.data || 'Er is iets misgegaan.') + '</span>');
            }
        },
        error: function() {
            jQuery('#save-status').html('<span class="rp-error">Er is iets misgegaan.</span>');
        }
    });
});

// Set min date for end_date based on start_date
jQuery('#start_date').on('change', function() {
    jQuery('#end_date').attr('min', jQuery(this).val());
});
</script>

<style>
.rp-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
.rp-btn-primary { background: #4F46E5; color: #fff; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-btn-large { padding: 14px 28px; font-size: 16px; }
.rp-info-bar { margin-bottom: 20px; padding: 15px; background: #dbeafe; border-radius: 8px; border-left: 4px solid #3b82f6; }
.rp-timeoff-form-wrapper { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px; }
.rp-form-group { margin-bottom: 20px; }
.rp-form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
.rp-form-group input,
.rp-form-group select,
.rp-form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
.rp-form-group textarea { resize: vertical; }
.rp-form-actions { display: flex; align-items: center; gap: 15px; margin-top: 25px; }
.rp-save-status { font-weight: 500; }
.rp-saving { color: #6b7280; }
.rp-saved { color: #059669; }
.rp-error { color: #dc2626; }
.rp-timeoff-history { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-timeoff-history h3 { margin: 0 0 20px 0; color: #374151; }
.rp-no-data { color: #6b7280; font-style: italic; }
.rp-timeoff-list { display: flex; flex-direction: column; gap: 15px; }
.rp-timeoff-item { padding: 15px; border-radius: 8px; border-left: 4px solid; }
.rp-timeoff-item.rp-status-pending { background: #fef3c7; border-color: #f59e0b; }
.rp-timeoff-item.rp-status-approved { background: #d4edda; border-color: #10B981; }
.rp-timeoff-item.rp-status-rejected { background: #f8d7da; border-color: #dc2626; }
.rp-timeoff-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.rp-timeoff-type { font-weight: 600; color: #374151; }
.rp-timeoff-status { font-size: 12px; font-weight: 500; }
.rp-timeoff-dates { color: #6b7280; font-size: 14px; margin-bottom: 8px; }
.rp-timeoff-reason { color: #374151; font-size: 14px; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb; }
.rp-timeoff-admin-notes { color: #6b7280; font-size: 13px; font-style: italic; margin-top: 8px; padding: 8px; background: rgba(255,255,255,0.5); border-radius: 4px; }
.rp-timeoff-date { color: #9ca3af; font-size: 12px; margin-top: 8px; }
</style>
