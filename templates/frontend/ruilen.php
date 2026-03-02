<header class="rp-header">
    <h1>🔄 Dienst Ruilen</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
    </div>
</header>
    
    <!-- Swap Requests For Me -->
    <?php if (!empty($requests_for_me)): ?>
    <div class="rp-alert rp-alert-info">
        <h3>📬 Ruilverzoeken voor jou</h3>
        <p>Je hebt <?php echo count($requests_for_me); ?> openstaand(e) verzoek(en) om een dienst over te nemen.</p>
    </div>
    
    <div class="rp-section">
        <h2>Openstaande Verzoeken</h2>
        <div class="rp-swap-list">
            <?php foreach ($requests_for_me as $request): ?>
            <div class="rp-swap-card rp-swap-incoming">
                <div class="rp-swap-header">
                    <span class="rp-badge rp-badge-info">INCOMING</span>
                </div>
                <div class="rp-swap-details">
                    <div class="rp-shift-info">
                        <h4><?php echo esc_html($request->shift_name); ?></h4>
                        <p>📅 <?php echo date('d-m-Y', strtotime($request->work_date)); ?></p>
                        <p>🕐 <?php echo substr($request->start_time, 0, 5) . ' - ' . substr($request->end_time, 0, 5); ?></p>
                    </div>
                    <div class="rp-requester-info">
                        <p>Van: <strong><?php echo esc_html($request->requester_name); ?></strong></p>
                    </div>
                </div>
                <div class="rp-swap-actions">
                    <button type="button" class="rp-btn rp-btn-success" onclick="respondSwap(<?php echo $request->id; ?>, 'accept')">
                        ✓ Accepteren
                    </button>
                    <button type="button" class="rp-btn rp-btn-danger" onclick="respondSwap(<?php echo $request->id; ?>, 'reject')">
                        ✗ Weigeren
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- My Shifts -->
    <div class="rp-section">
        <h2>Mijn Diensten (te ruilen)</h2>
        <?php if (empty($my_shifts)): ?>
        <p class="rp-empty">Je hebt geen komende diensten om te ruilen.</p>
        <?php else: ?>
        <div class="rp-shift-list">
            <?php foreach ($my_shifts as $shift): ?>
            <div class="rp-shift-card">
                <div class="rp-shift-header">
                    <span class="rp-date"><?php echo date('d-m-Y', strtotime($shift->work_date)); ?></span>
                    <span class="rp-location">📍 <?php echo esc_html($shift->location_name); ?></span>
                </div>
                <div class="rp-shift-body">
                    <h4><?php echo esc_html($shift->shift_name); ?></h4>
                    <p>🕐 <?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?></p>
                </div>
                <div class="rp-shift-footer">
                    <button type="button" class="rp-btn rp-btn-primary" onclick="showSwapModal(<?php echo $shift->id; ?>, '<?php echo esc_js($shift->shift_name); ?>', '<?php echo date('d-m-Y', strtotime($shift->work_date)); ?>')">
                        🔄 Ruilen Aanvragen
                    </button>
                    <button type="button" class="rp-btn <?php echo $shift->is_swappable ? 'rp-btn-success' : 'rp-btn-secondary'; ?>" onclick="toggleSwappable(<?php echo $shift->id; ?>, this)">
                        <?php echo $shift->is_swappable ? '✓ Ruilbaar' : 'Markeren als ruilbaar'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Available Shifts to Take -->
    <div class="rp-section">
        <h2>Beschikbare Diensten (overnemen)</h2>
        <?php if (empty($available_shifts)): ?>
        <p class="rp-empty">Er zijn momenteel geen diensten beschikbaar om over te nemen.</p>
        <?php else: ?>
        <div class="rp-shift-list">
            <?php foreach ($available_shifts as $shift): ?>
            <div class="rp-shift-card rp-shift-available">
                <div class="rp-shift-header">
                    <span class="rp-date"><?php echo date('d-m-Y', strtotime($shift->work_date)); ?></span>
                    <span class="rp-location">📍 <?php echo esc_html($shift->location_name); ?></span>
                </div>
                <div class="rp-shift-body">
                    <h4><?php echo esc_html($shift->shift_name); ?></h4>
                    <p>🕐 <?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?></p>
                    <p>👤 <?php echo esc_html($shift->display_name); ?></p>
                </div>
                <div class="rp-shift-footer">
                    <button type="button" class="rp-btn rp-btn-success" onclick="takeShift(<?php echo $shift->id; ?>, '<?php echo esc_js($shift->display_name); ?>')">
                        ✓ Dienst Overnemen
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- My Requests History -->
    <?php if (!empty($my_requests)): ?>
    <div class="rp-section">
        <h2>Mijn Ruilverzoeken Historie</h2>
        <div class="rp-history-list">
            <?php foreach ($my_requests as $request): ?>
            <div class="rp-history-item">
                <div class="rp-history-status rp-status-<?php echo $request->swap_status; ?>">
                    <?php 
                    $status_labels = ['pending' => '⏳ In behandeling', 'approved' => '✓ Goedgekeurd', 'rejected' => '✗ Afgewezen', 'completed' => '✓ Voltooid'];
                    echo $status_labels[$request->swap_status] ?? $request->swap_status;
                    ?>
                </div>
                <div class="rp-history-details">
                    <p><strong><?php echo esc_html($request->shift_name); ?></strong> op <?php echo date('d-m-Y', strtotime($request->work_date)); ?></p>
                    <?php if ($request->requested_name): ?>
                    <p>Gevraagd aan: <?php echo esc_html($request->requested_name); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<!-- Swap Request Modal -->
<div id="swap-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content">
        <h2>Dienst Ruilen</h2>
            <p id="swap-shift-info"></p>
            <form id="swap-form">
                <p>
                    <label>Specifieke medewerker vragen (optioneel):</label>
                <select id="requested_employee" name="requested_employee">
                    <option value="">Iedereen mag reageren</option>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp->id; ?>"><?php echo esc_html($emp->display_name); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Geen collega's gevonden op jouw locaties</option>
                    <?php endif; ?>
                </select>
                </p>
            <p>
                <label>Reden (optioneel):</label>
                <textarea id="swap_reason" rows="3" placeholder="Waarom wil je deze dienst ruilen?"></textarea>
            </p>
            <div class="rp-modal-actions">
                <button type="submit" class="rp-btn rp-btn-primary">Verzoek Indienen</button>
                <button type="button" class="rp-btn rp-btn-secondary" onclick="closeModal()">Annuleren</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentScheduleId = null;

function showSwapModal(scheduleId, shiftName, date) {
    currentScheduleId = scheduleId;
    document.getElementById('swap-shift-info').innerHTML = '<strong>' + shiftName + '</strong> op ' + date;
    document.getElementById('swap-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('swap-modal').style.display = 'none';
    currentScheduleId = null;
}

jQuery('#swap-form').on('submit', function(e) {
    e.preventDefault();
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_request_swap',
            nonce: rpAjax.nonce,
            schedule_id: currentScheduleId,
            requested_employee_id: document.getElementById('requested_employee').value,
            reason: document.getElementById('swap_reason').value
        },
        success: function(response) {
            if (response.success) {
                alert('Ruilverzoek ingediend!');
                location.reload();
            } else {
                alert('Er is iets misgegaan: ' + response.data);
            }
        }
    });
});

function respondSwap(swapId, action) {
    const confirmed = confirm('Weet je zeker dat je dit verzoek wilt ' + (action === 'accept' ? 'accepteren' : 'weigeren') + '?');
    if (!confirmed) return;
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_respond_swap',
            nonce: rpAjax.nonce,
            swap_id: swapId,
            action: action
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Er is iets misgegaan.');
            }
        }
    });
}

function takeShift(scheduleId, currentHolder) {
    const confirmed = confirm('Weet je zeker dat je deze dienst wilt overnemen van ' + currentHolder + '?');
    if (!confirmed) return;
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_claim_swappable',
            nonce: rpAjax.nonce,
            schedule_id: scheduleId
        },
        success: function(response) {
            if (response.success) {
                alert('Dienst overgenomen! Het rooster is bijgewerkt.');
                location.reload();
            } else {
                alert('Er is iets misgegaan: ' + response.data);
            }
        }
    });
}

function toggleSwappable(scheduleId, btn) {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_toggle_swappable',
            nonce: rpAjax.nonce,
            schedule_id: scheduleId
        },
        success: function(response) {
            if (response.success) {
                // Update button appearance
                if (response.data.is_swappable) {
                    jQuery(btn).removeClass('rp-btn-secondary').addClass('rp-btn-success');
                    jQuery(btn).text('✓ Ruilbaar');
                } else {
                    jQuery(btn).removeClass('rp-btn-success').addClass('rp-btn-secondary');
                    jQuery(btn).text('Markeren als ruilbaar');
                }
            } else {
                alert('Er is iets misgegaan: ' + response.data);
            }
        }
    });
}
</script>

<style>
.rp-container { max-width: 1000px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
.rp-btn-primary { background: #4F46E5; color: #fff; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-btn-success { background: #10B981; color: #fff; }
.rp-btn-danger { background: #EF4444; color: #fff; }
.rp-section { background: #fff; padding: 20px; margin: 20px 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
.rp-alert-info { background: #dbeafe; border: 1px solid #3b82f6; }
.rp-alert h3 { margin: 0 0 5px; }
.rp-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.rp-badge-info { background: #4F46E5; color: #fff; }
.rp-empty { color: #9ca3af; font-style: italic; padding: 20px 0; text-align: center; }
.rp-shift-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
.rp-shift-card, .rp-swap-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; }
.rp-shift-available { border-color: #10B981; background: #f0fdf4; }
.rp-swap-incoming { border-color: #F59E0B; background: #fffbeb; }
.rp-shift-header, .rp-swap-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.rp-date { font-weight: 600; color: #4F46E5; }
.rp-location { font-size: 12px; color: #6b7280; }
.rp-shift-body h4 { margin: 0 0 8px; }
.rp-shift-body p { margin: 4px 0; font-size: 14px; color: #6b7280; }
.rp-shift-footer, .rp-swap-actions { margin-top: 15px; display: flex; gap: 10px; }
.rp-requester-info { padding: 10px; background: #f9fafb; border-radius: 6px; margin-top: 10px; }
.rp-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.rp-modal-content { background: #fff; padding: 30px; border-radius: 12px; min-width: 400px; max-width: 90%; }
.rp-modal-content h2 { margin-top: 0; }
.rp-modal-content label { display: block; margin-bottom: 5px; font-weight: 500; }
.rp-modal-content select, .rp-modal-content textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 15px; }
.rp-modal-actions { display: flex; gap: 10px; }
.rp-history-list { display: flex; flex-direction: column; gap: 10px; }
.rp-history-item { display: flex; align-items: center; gap: 15px; padding: 12px; background: #f9fafb; border-radius: 6px; }
.rp-history-status { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.rp-status-pending { background: #FEF3C7; color: #92400E; }
.rp-status-approved, .rp-status-completed { background: #D1FAE5; color: #065F46; }
.rp-status-rejected { background: #FEE2E2; color: #991B1B; }
@media (max-width: 768px) {
    .rp-shift-list { grid-template-columns: 1fr; }
    .rp-swap-details { flex-direction: column; }
    .rp-modal-content { min-width: auto; width: 90%; padding: 20px; }
}
</style>
