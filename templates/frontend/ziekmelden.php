<header class="rp-header">
    <h1>🤒 Ziekmelden</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
    </div>
</header>
    
    <div class="rp-alert rp-alert-warning">
        <strong>Belangrijk:</strong> Bij ziekmelding wordt er automatisch een verzoek voor vervanging naar alle collega's gestuurd.
    </div>
    
    <div class="rp-section">
        <h2>Mijn Komende Diensten</h2>
        <p>Selecteer de dienst(en) waarvoor je je ziek wilt melden:</p>
        
        <?php if (empty($upcoming_shifts)): ?>
        <p class="rp-empty">Je hebt geen komende diensten gepland.</p>
        <?php else: ?>
        <form id="sick-form" class="rp-sick-form">
            <div class="rp-shift-list">
                <?php foreach ($upcoming_shifts as $shift): ?>
                <label class="rp-shift-checkbox">
                    <input type="checkbox" name="schedule_ids[]" value="<?php echo $shift->id; ?>">
                    <div class="rp-shift-card">
                        <div class="rp-shift-header">
                            <span class="rp-date"><?php echo date('d-m-Y', strtotime($shift->work_date)); ?></span>
                            <span class="rp-badge"><?php echo esc_html($shift->location_name); ?></span>
                        </div>
                        <div class="rp-shift-body">
                            <h4><?php echo esc_html($shift->shift_name); ?></h4>
                            <p>🕐 <?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?></p>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="rp-form-section">
                <label for="sick-notes">Extra informatie (optioneel):</label>
                <textarea id="sick-notes" name="notes" rows="3" placeholder="Bijv. verwachte hersteltijd, bijzonderheden..."></textarea>
            </div>
            
            <div class="rp-form-actions">
                <button type="submit" class="rp-btn rp-btn-danger rp-btn-large">
                    ⚠️ Ziekmelding Indienen
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <div class="rp-section rp-urgent-section">
        <div class="rp-urgent-header">
            <span class="rp-urgent-icon">🚨</span>
            <div>
                <h3>Spoed? Bel direct de teamleider!</h3>
                <p>Bij spoed of vragen, neem direct telefonisch contact op:</p>
            </div>
        </div>
        <div class="rp-contact-list">
            <?php
            global $wpdb;
            $admins = $wpdb->get_results("SELECT e.*, u.display_name FROM {$wpdb->prefix}rp_employees e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID WHERE e.is_admin = 1");
            foreach ($admins as $admin):
            ?>
            <div class="rp-contact-item">
                <div class="rp-contact-info">
                    <span class="rp-contact-name"><?php echo esc_html($admin->display_name); ?></span>
                    <span class="rp-contact-role">Teamleider</span>
                </div>
                <?php if ($admin->phone): ?>
                <div class="rp-contact-actions">
                    <span class="rp-contact-phone"><?php echo esc_html($admin->phone); ?></span>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $admin->phone)); ?>" class="rp-call-btn" title="Bel nu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        Bel nu
                    </a>
                </div>
                <?php else: ?>
                <span class="rp-contact-no-phone">Geen telefoonnummer beschikbaar</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

<script>
jQuery('#sick-form').on('submit', function(e) {
    e.preventDefault();
    
    const selected = jQuery('input[name="schedule_ids[]"]:checked');
    if (selected.length === 0) {
        alert('Selecteer minstens één dienst waarvoor je je ziek wilt melden.');
        return;
    }
    
    const confirmed = confirm('Weet je zeker dat je je ziek wilt melden voor de geselecteerde dienst(en)? Dit kan niet ongedaan worden gemaakt.');
    if (!confirmed) return;
    
    const notes = document.getElementById('sick-notes').value;
    let completed = 0;
    let total = selected.length;
    
    selected.each(function() {
        const scheduleId = jQuery(this).val();
        
        jQuery.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_report_sick',
                nonce: rpAjax.nonce,
                schedule_id: scheduleId,
                notes: notes
            },
            success: function(response) {
                completed++;
                if (completed === total) {
                    alert('Ziekmelding ingediend. Er is een verzoek voor vervanging gestuurd naar je collega\'s. Beterschap!');
                    window.location.href = '<?php echo home_url('/medewerker-dashboard/'); ?>';
                }
            },
            error: function() {
                completed++;
                if (completed === total) {
                    alert('Er is iets misgegaan. Neem contact op met je manager.');
                }
            }
        });
    });
});
</script>

<style>
.rp-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
.rp-btn-danger { background: #dc2626; color: #fff; }
.rp-btn-danger:hover { background: #b91c1c; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-btn-large { padding: 14px 28px; font-size: 16px; }
.rp-alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
.rp-alert-warning { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; }
.rp-section { background: #fff; padding: 20px; margin: 20px 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-empty { color: #9ca3af; font-style: italic; padding: 20px 0; text-align: center; }
.rp-shift-checkbox { display: block; cursor: pointer; }
.rp-shift-checkbox input { display: none; }
.rp-shift-checkbox input:checked + .rp-shift-card { border-color: #dc2626; background: #fef2f2; }
.rp-shift-list { display: flex; flex-direction: column; gap: 10px; }
.rp-shift-card { border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; transition: all 0.2s; }
.rp-shift-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.rp-date { font-weight: 600; color: #4F46E5; }
.rp-badge { background: #f3f4f6; padding: 4px 10px; border-radius: 12px; font-size: 12px; }
.rp-shift-body h4 { margin: 0 0 5px; }
.rp-shift-body p { margin: 0; color: #6b7280; font-size: 14px; }
.rp-form-section { margin-top: 20px; }
.rp-form-section label { display: block; margin-bottom: 8px; font-weight: 500; }
.rp-form-section textarea { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; }
.rp-form-actions { margin-top: 20px; }
.rp-urgent-section { background: #fff; border: 2px solid #dc2626; border-radius: 12px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 8px rgba(220,38,38,0.12); }
.rp-urgent-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.rp-urgent-icon { font-size: 32px; }
.rp-urgent-header h3 { margin: 0 0 4px; font-size: 18px; color: #dc2626; }
.rp-urgent-header p { margin: 0; font-size: 14px; color: #6b7280; }
.rp-contact-list { display: flex; flex-direction: column; gap: 10px; }
.rp-contact-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: #fef2f2; border-radius: 8px; border: 1px solid #fecaca; }
.rp-contact-info { display: flex; flex-direction: column; }
.rp-contact-name { font-weight: 600; font-size: 15px; color: #1f2937; }
.rp-contact-role { font-size: 12px; color: #6b7280; margin-top: 2px; }
.rp-contact-actions { display: flex; align-items: center; gap: 10px; }
.rp-contact-phone { color: #374151; font-size: 14px; font-weight: 500; }
.rp-call-btn { display: inline-flex; align-items: center; gap: 6px; background: #dc2626; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s; box-shadow: 0 2px 6px rgba(220,38,38,0.3); }
.rp-call-btn:hover { background: #b91c1c; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(220,38,38,0.4); color: #fff; }
.rp-call-btn:active { transform: translateY(0); }
.rp-call-btn svg { flex-shrink: 0; }
.rp-contact-no-phone { color: #9ca3af; font-style: italic; font-size: 13px; }
@media (max-width: 600px) {
    .rp-contact-item { flex-direction: column; align-items: flex-start; gap: 10px; }
    .rp-contact-actions { width: 100%; }
    .rp-call-btn { width: 100%; justify-content: center; padding: 12px 18px; font-size: 16px; }
}
</style>
