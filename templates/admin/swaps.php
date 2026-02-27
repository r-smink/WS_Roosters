<div class="wrap rp-admin-wrap">
    <h1>Ruilingen & Verlof Beheren</h1>
    
    <?php if (isset($_GET['msg'])): ?>
    <div class="rp-notice rp-notice-success">
        <?php
        $messages = [
            'swap_approved' => 'Ruilverzoek goedgekeurd.',
            'swap_rejected' => 'Ruilverzoek afgewezen.',
            'timeoff_approved' => 'Verlofverzoek goedgekeurd.',
            'timeoff_rejected' => 'Verlofverzoek afgewezen.'
        ];
        echo $messages[$_GET['msg']] ?? 'Bewerking voltooid.';
        ?>
    </div>
    <?php endif; ?>
    
    <div class="rp-section">
        <h2>Openstaande Ruilverzoeken (<?php echo count($pending_swaps); ?>)</h2>
        
        <?php if (empty($pending_swaps)): ?>
        <p class="rp-empty-state">Geen openstaande ruilverzoeken.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Locatie</th>
                    <th>Shift</th>
                    <th>Aanvrager</th>
                    <th>Gevraagde</th>
                    <th>Reden</th>
                    <th>Aangevraagd</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_swaps as $swap): ?>
                <tr>
                    <td><?php echo date('d-m-Y', strtotime($swap->work_date)); ?></td>
                    <td><?php echo esc_html($swap->location_name); ?></td>
                    <td>
                        <?php echo esc_html($swap->shift_name); ?><br>
                        <small><?php echo substr($swap->start_time, 0, 5) . ' - ' . substr($swap->end_time, 0, 5); ?></small>
                    </td>
                    <td><?php echo esc_html($swap->requester_name); ?></td>
                    <td><?php echo $swap->requested_name ? esc_html($swap->requested_name) : '<em>Iedereen</em>'; ?></td>
                    <td><?php echo esc_html($swap->reason); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($swap->requested_at)); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="process_swap">
                            <input type="hidden" name="swap_id" value="<?php echo $swap->id; ?>">
                            <input type="hidden" name="swap_action" value="approve">
                            <button type="submit" class="button button-primary">Goedkeuren</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="process_swap">
                            <input type="hidden" name="swap_id" value="<?php echo $swap->id; ?>">
                            <input type="hidden" name="swap_action" value="reject">
                            <button type="button" class="button" onclick="showRejectModal('swap', <?php echo $swap->id; ?>)">Afwijzen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <div class="rp-section">
        <h2>Openstaande Verlofverzoeken (<?php echo count($pending_timeoff); ?>)</h2>
        
        <?php if (empty($pending_timeoff)): ?>
        <p class="rp-empty-state">Geen openstaande verlofverzoeken.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Medewerker</th>
                    <th>Van</th>
                    <th>Tot</th>
                    <th>Type</th>
                    <th>Reden</th>
                    <th>Aangevraagd</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_timeoff as $timeoff): ?>
                <tr>
                    <td><?php echo esc_html($timeoff->display_name); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($timeoff->start_date)); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($timeoff->end_date)); ?></td>
                    <td>
                        <?php
                        $types = ['vacation' => 'Vakantie', 'sick' => 'Ziek', 'personal' => 'Persoonlijk', 'other' => 'Overig'];
                        echo $types[$timeoff->type] ?? $timeoff->type;
                        ?>
                    </td>
                    <td><?php echo esc_html($timeoff->reason); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($timeoff->requested_at)); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="process_timeoff">
                            <input type="hidden" name="timeoff_id" value="<?php echo $timeoff->id; ?>">
                            <input type="hidden" name="timeoff_action" value="approve">
                            <button type="submit" class="button button-primary">Goedkeuren</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="process_timeoff">
                            <input type="hidden" name="timeoff_id" value="<?php echo $timeoff->id; ?>">
                            <input type="hidden" name="timeoff_action" value="reject">
                            <button type="button" class="button" onclick="showRejectModal('timeoff', <?php echo $timeoff->id; ?>)">Afwijzen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="rp-section">
        <h2>Recent Goedgekeurde Verlofaanvragen</h2>
        <?php if (empty($approved_timeoff)): ?>
            <p class="rp-empty-state">Nog geen goedgekeurde aanvragen.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Medewerker</th>
                    <th>Van</th>
                    <th>Tot</th>
                    <th>Type</th>
                    <th>Opmerking</th>
                    <th>Goedgekeurd op</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approved_timeoff as $timeoff): ?>
                <tr>
                    <td><?php echo esc_html($timeoff->display_name); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($timeoff->start_date)); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($timeoff->end_date)); ?></td>
                    <td>
                        <?php
                        $types = ['vacation' => 'Vakantie', 'sick' => 'Ziek', 'personal' => 'Persoonlijk', 'other' => 'Overig'];
                        echo $types[$timeoff->type] ?? $timeoff->type;
                        ?>
                    </td>
                    <td><?php echo esc_html($timeoff->reason); ?></td>
                    <td><?php echo $timeoff->responded_at ? date('d-m-Y H:i', strtotime($timeoff->responded_at)) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="rp-modal" style="display:none;">
    <div class="rp-modal-content">
        <h2>Reden voor Afwijzing</h2>
        <form method="post">
            <?php wp_nonce_field('rp_admin_action'); ?>
            <input type="hidden" name="rp_action" id="reject_action">
            <input type="hidden" name="swap_id" id="reject_swap_id">
            <input type="hidden" name="timeoff_id" id="reject_timeoff_id">
            <input type="hidden" name="swap_action" value="reject">
            <input type="hidden" name="timeoff_action" value="reject">
            
            <p>
                <label for="admin_notes">Notities voor medewerker:</label>
                <textarea name="admin_notes" id="admin_notes" rows="4" class="regular-text"></textarea>
            </p>
            
            <div class="rp-modal-actions">
                <button type="submit" class="button button-primary">Versturen</button>
                <button type="button" class="button" onclick="closeModal()">Annuleren</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(type, id) {
    document.getElementById('reject_action').value = type === 'swap' ? 'process_swap' : 'process_timeoff';
    if (type === 'swap') {
        document.getElementById('reject_swap_id').value = id;
        document.getElementById('reject_timeoff_id').value = '';
    } else {
        document.getElementById('reject_timeoff_id').value = id;
        document.getElementById('reject_swap_id').value = '';
    }
    document.getElementById('reject-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('reject-modal').style.display = 'none';
}
</script>

<style>
.rp-admin-wrap { max-width: 1200px; }
.rp-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-empty-state {
    text-align: center;
    padding: 40px;
    color: #6b7280;
    font-style: italic;
}
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
    min-width: 400px;
}
.rp-modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
</style>
