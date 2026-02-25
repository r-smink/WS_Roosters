<?php
/**
 * Admin Reports Template
 */
?>
<div class="wrap">
    <h1>📊 Rapportages</h1>
    
    <!-- Report Filters -->
    <div class="rp-report-filters">
        <form method="GET" action="">
            <input type="hidden" name="page" value="rooster-planner-reports">
            
            <div class="rp-filter-row">
                <div class="rp-filter-group">
                    <label for="report_type">Rapport Type:</label>
                    <select name="report_type" id="report_type" onchange="this.form.submit()">
                        <option value="hours" <?php selected($report_type, 'hours'); ?>>📈 Uren per Medewerker</option>
                        <option value="sickness" <?php selected($report_type, 'sickness'); ?>>🤒 Ziekte per Medewerker</option>
                    </select>
                </div>
                
                <div class="rp-filter-group">
                    <label for="month">Maand:</label>
                    <input type="month" name="month" id="month" value="<?php echo esc_attr($month); ?>" onchange="this.form.submit()">
                </div>
                
                <?php if ($report_type === 'hours'): ?>
                <div class="rp-filter-group">
                    <label for="location_id">Locatie:</label>
                    <select name="location_id" id="location_id" onchange="this.form.submit()">
                        <option value="0">Alle locaties</option>
                        <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location->id; ?>" <?php selected($location_id, $location->id); ?>>
                            <?php echo esc_html($location->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="rp-filter-group">
                    <label for="employee_id">Medewerker:</label>
                    <select name="employee_id" id="employee_id" onchange="this.form.submit()">
                        <option value="0">Alle medewerkers</option>
                        <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo $employee->id; ?>" <?php selected($employee_id, $employee->id); ?>>
                            <?php echo esc_html($employee->display_name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="rp-filter-group rp-filter-actions">
                    <button type="submit" class="button button-primary">Rapport genereren</button>
                    <button type="button" class="button" onclick="exportReport()">📥 Exporteer CSV</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Report Summary -->
    <div class="rp-report-summary">
        <h2><?php echo $report_type === 'hours' ? '📈 Uren Rapport' : '🤒 Ziekte Rapport'; ?></h2>
        <p class="rp-report-period">Periode: <?php echo date('F Y', strtotime($month . '-01')); ?></p>
    </div>
    
    <!-- Report Table -->
    <div class="rp-report-table-wrapper">
        <?php if (empty($report_data)): ?>
        <div class="rp-no-data">
            <p>Geen data gevonden voor de geselecteerde periode en filters.</p>
        </div>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped rp-report-table" id="rp-report-table">
            <thead>
                <tr>
                    <?php if ($report_type === 'hours'): ?>
                    <th>Medewerker</th>
                    <th>Contracturen</th>
                    <th>Aantal Diensten</th>
                    <th>Gewerkte Uren</th>
                    <th>Verschil</th>
                    <th>Locaties</th>
                    <?php else: ?>
                    <th>Medewerker</th>
                    <th>Aantal Ziekmeldingen</th>
                    <th>Totaal Ziektedagen</th>
                    <th>Ziekteperiodes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <?php if ($report_type === 'hours'): ?>
                    <td><strong><?php echo esc_html($row->employee_name); ?></strong></td>
                    <td><?php echo $row->contract_hours ? $row->contract_hours . ' uur' : '-'; ?></td>
                    <td><?php echo $row->total_shifts; ?></td>
                    <td><?php echo $row->total_hours ? number_format($row->total_hours, 1) . ' uur' : '0 uur'; ?></td>
                    <td>
                        <?php if ($row->contract_hours > 0 && $row->total_hours): ?>
                            <?php $diff = $row->total_hours - $row->contract_hours; ?>
                            <span class="rp-hours-diff <?php echo $diff > 0 ? 'rp-over' : ($diff < 0 ? 'rp-under' : 'rp-exact'); ?>">
                                <?php echo ($diff > 0 ? '+' : '') . number_format($diff, 1) . ' uur'; ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row->locations ? esc_html($row->locations) : '-'; ?></td>
                    <?php else: ?>
                    <td><strong><?php echo esc_html($row->employee_name); ?></strong></td>
                    <td><?php echo $row->sickness_reports; ?></td>
                    <td><?php echo $row->total_days ? $row->total_days . ' dagen' : '0 dagen'; ?></td>
                    <td class="rp-periods-cell"><?php echo $row->periods ? esc_html($row->periods) : '-'; ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if ($report_type === 'hours'): ?>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>Totaal</strong></td>
                    <td><strong><?php echo array_sum(array_column($report_data, 'total_shifts')); ?> diensten</strong></td>
                    <td><strong><?php echo number_format(array_sum(array_column($report_data, 'total_hours')), 1); ?> uur</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
.rp-report-filters {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin: 20px 0;
}
.rp-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}
.rp-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.rp-filter-group label {
    font-weight: 600;
    color: #374151;
    font-size: 12px;
    text-transform: uppercase;
}
.rp-filter-group select,
.rp-filter-group input {
    min-width: 150px;
}
.rp-filter-actions {
    margin-left: auto;
    flex-direction: row;
    gap: 10px;
}
.rp-filter-actions button {
    margin: 0;
}
.rp-report-summary {
    margin: 20px 0;
}
.rp-report-summary h2 {
    margin: 0 0 5px 0;
}
.rp-report-period {
    color: #6b7280;
    margin: 0;
}
.rp-report-table-wrapper {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}
.rp-no-data {
    padding: 40px;
    text-align: center;
    color: #6b7280;
}
.rp-hours-diff {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
}
.rp-hours-diff.rp-over {
    background: #fee2e2;
    color: #dc2626;
}
.rp-hours-diff.rp-under {
    background: #dbeafe;
    color: #2563eb;
}
.rp-hours-diff.rp-exact {
    background: #d1fae5;
    color: #059669;
}
.rp-periods-cell {
    max-width: 300px;
    font-size: 12px;
    line-height: 1.5;
}
@media (max-width: 782px) {
    .rp-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    .rp-filter-actions {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>

<script>
function exportReport() {
    var table = document.getElementById('rp-report-table');
    if (!table) {
        alert('Geen data om te exporteren');
        return;
    }
    
    var csv = [];
    var rows = table.querySelectorAll('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        for (var j = 0; j < cols.length; j++) {
            var text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(';'));
    }
    
    var csvContent = '\uFEFF' + csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'rapport_<?php echo $report_type; ?>_<?php echo $month; ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
