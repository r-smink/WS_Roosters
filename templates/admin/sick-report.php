<div class="wrap rp-admin-wrap">
    <h1>Verzuim - Ziekmelding door Teamleider</h1>
    
    <div class="rp-section">
        <h2>Medewerker Ziek Melden</h2>
        <p>Selecteer een medewerker en dienst(en) om ziek te melden. Er wordt automatisch een verzoek voor vervanging verstuurd.</p>
        
        <form id="admin-sick-report-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('rp_admin_action'); ?>
            <input type="hidden" name="action" value="rp_admin_sick_report">
            
            <table class="form-table">
                <tr>
                    <th><label for="sick_employee">Medewerker</label></th>
                    <td>
                        <select id="sick_employee" name="employee_id" required onchange="loadEmployeeShifts(this.value)">
                            <option value="">-- Selecteer medewerker --</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp->id; ?>" <?php selected($selected_employee, $emp->id); ?>>
                                <?php echo esc_html($emp->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th>Komende Diensten</th>
                    <td>
                        <div id="shifts-container">
                            <?php if ($selected_employee && !empty($upcoming_shifts)): ?>
                            <div class="rp-shift-list">
                                <?php foreach ($upcoming_shifts as $shift): ?>
                                <label class="rp-shift-item">
                                    <input type="checkbox" name="schedule_ids[]" value="<?php echo $shift->id; ?>">
                                    <span class="rp-shift-info">
                                        <strong><?php echo date('d-m-Y', strtotime($shift->work_date)); ?></strong> - 
                                        <?php echo esc_html($shift->shift_name); ?> 
                                        (<?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?>)
                                        <span class="rp-location-badge"><?php echo esc_html($shift->location_name); ?></span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php elseif ($selected_employee): ?>
                            <p class="description">Geen komende diensten gevonden voor deze medewerker.</p>
                            <?php else: ?>
                            <p class="description">Selecteer eerst een medewerker.</p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="sick_notes">Notities</label></th>
                    <td>
                        <textarea id="sick_notes" name="notes" rows="3" class="regular-text" placeholder="Bijv. verwachte hersteltijd, bijzonderheden..."></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th>Notificaties</th>
                    <td>
                        <label>
                            <input type="checkbox" name="send_push_notifications" value="1" checked>
                            Stuur push notificaties naar alle medewerkers voor vervanging
                        </label>
                        <p class="description">Wanneer aangevinkt, ontvangen alle medewerkers een notificatie dat er een vervanging nodig is.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary" id="submit-sick-report" <?php echo empty($upcoming_shifts) ? 'disabled' : ''; ?>>
                    ⚠️ Ziekmelding Indienen
                </button>
            </p>
        </form>
    </div>
    
    <div class="rp-section rp-history-section">
        <h2>Recente Ziekmeldingen</h2>
        <?php
        global $wpdb;
        $recent_sick_reports = $wpdb->get_results("SELECT t.*, u.display_name as employee_name, u2.display_name as reported_by_name
            FROM {$wpdb->prefix}rp_timeoff t
            LEFT JOIN {$wpdb->prefix}rp_employees e ON t.employee_id = e.id
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
            WHERE t.type = 'sick'
            ORDER BY t.created_at DESC
            LIMIT 20");
        ?>
        
        <?php if ($recent_sick_reports): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Medewerker</th>
                    <th>Periode</th>
                    <th>Notities</th>
                    <th>Gemeld door</th>
                    <th>Datum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_sick_reports as $report): ?>
                <tr>
                    <td><?php echo esc_html($report->employee_name); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($report->start_date)); ?> t/m <?php echo date('d-m-Y', strtotime($report->end_date)); ?></td>
                    <td><?php echo esc_html($report->notes); ?></td>
                    <td><?php echo esc_html($report->reported_by_name ?: 'Systeem'); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($report->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Geen recente ziekmeldingen gevonden.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.rp-admin-wrap { max-width: 1000px; }
.rp-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-shift-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background: #f9f9f9;
}
.rp-shift-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    margin: 5px 0;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}
.rp-shift-item:hover {
    background: #f0f0f0;
}
.rp-shift-item input[type="checkbox"] {
    margin: 0;
}
.rp-shift-info {
    flex: 1;
}
.rp-location-badge {
    display: inline-block;
    background: #e0e0e0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-left: 8px;
}
.rp-history-section {
    margin-top: 30px;
}
#submit-sick-report:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<script>
function loadEmployeeShifts(employeeId) {
    if (!employeeId) {
        document.getElementById('shifts-container').innerHTML = '<p class="description">Selecteer eerst een medewerker.</p>';
        document.getElementById('submit-sick-report').disabled = true;
        return;
    }
    
    // Reload page with employee selected
    window.location.href = '<?php echo admin_url('admin.php?page=rooster-planner-sick-report'); ?>&employee_id=' + employeeId;
}
</script>
