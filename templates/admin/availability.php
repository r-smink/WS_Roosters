<div class="wrap rp-admin-wrap">
    <h1>Beschikbaarheid Overzicht</h1>
    
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
        </div>
    </div>
    
    <div class="rp-section">
        <h2>Beschikbaarheid Overzicht - <?php echo date('F Y', strtotime($current_month . '-01')); ?></h2>
        
        <div class="rp-availability-table-wrapper">
            <table class="wp-list-table widefat fixed striped rp-availability-table">
                <thead>
                    <tr>
                        <th class="rp-sticky-col">Medewerker</th>
                        <?php
                        $days_in_month = date('t', strtotime($current_month . '-01'));
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $date = $current_month . '-' . sprintf('%02d', $day);
                            $weekday = date('D', strtotime($date));
                            $is_weekend = in_array($weekday, ['Sat', 'Sun']);
                            echo '<th class="rp-day-col ' . ($is_weekend ? 'rp-weekend' : '') . '">' . $day . '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): 
                        // Get availability for this employee
                        $emp_availability = array_filter($availability, function($a) use ($employee) {
                            return $a->employee_id == $employee->id;
                        });
                        $emp_avail_by_date = [];
                        foreach ($emp_availability as $a) {
                            $emp_avail_by_date[$a->work_date] = $a;
                        }
                    ?>
                    <tr>
                        <td class="rp-sticky-col">
                            <strong><?php echo esc_html($employee->display_name); ?></strong>
                        </td>
                        <?php
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $date = $current_month . '-' . sprintf('%02d', $day);
                            $avail = $emp_avail_by_date[$date] ?? null;
                            
                            if ($avail) {
                                $status_class = $avail->is_available ? 'rp-available' : 'rp-unavailable';
                                $tooltip = $avail->shift_name ?: 'Geen voorkeur';
                                if ($avail->notes) {
                                    $tooltip .= ' - ' . $avail->notes;
                                }
                                echo '<td class="rp-day-col ' . $status_class . '" title="' . esc_attr($tooltip) . '">';
                                echo $avail->is_available ? '✓' : '✗';
                                echo '</td>';
                            } else {
                                echo '<td class="rp-day-col rp-no-data">-</td>';
                            }
                        }
                        ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="rp-legend">
            <span class="rp-legend-item"><span class="rp-available">✓</span> Beschikbaar</span>
            <span class="rp-legend-item"><span class="rp-unavailable">✗</span> Niet beschikbaar</span>
            <span class="rp-legend-item"><span class="rp-no-data">-</span> Geen data</span>
        </div>
    </div>
    
    <div class="rp-section">
        <h2>Deadline Status</h2>
        <?php
        $deadline = date('Y-m-15', strtotime($current_month . '-01'));
        $today = current_time('Y-m-d');
        $days_left = ceil((strtotime($deadline) - strtotime($today)) / 86400);
        
        $total_emp = count($employees);
        $submitted = count(array_unique(array_column($availability, 'employee_id')));
        $percentage = $total_emp > 0 ? round(($submitted / $total_emp) * 100) : 0;
        ?>
        <div class="rp-deadline-status">
            <div class="rp-progress-bar">
                <div class="rp-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <p>
                <strong><?php echo $submitted; ?></strong> van <strong><?php echo $total_emp; ?></strong> medewerkers hebben beschikbaarheid doorgegeven 
                (<?php echo $percentage; ?>%)
            </p>
            <p class="rp-deadline-text">
                <?php if ($days_left > 0): ?>
                    Deadline over <strong><?php echo $days_left; ?></strong> dagen (<?php echo date('d-m-Y', strtotime($deadline)); ?>)
                <?php elseif ($days_left == 0): ?>
                    <strong>Deadline is vandaag!</strong>
                <?php else: ?>
                    Deadline was <strong><?php echo abs($days_left); ?></strong> dagen geleden
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<script>
function changeLocation(loc) {
    window.location.href = '<?php echo admin_url('admin.php?page=rooster-planner-availability'); ?>&location=' + loc + '&month=' + document.getElementById('month-filter').value;
}

function changeMonth(month) {
    window.location.href = '<?php echo admin_url('admin.php?page=rooster-planner-availability'); ?>&location=' + document.getElementById('location-filter').value + '&month=' + month;
}
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
.rp-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-availability-table-wrapper {
    overflow-x: auto;
    max-height: 600px;
}
.rp-availability-table {
    min-width: 100%;
    border-collapse: separate;
    border-spacing: 1px;
}
.rp-availability-table th,
.rp-availability-table td {
    padding: 8px 4px;
    text-align: center;
    min-width: 35px;
}
.rp-sticky-col {
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 10;
    min-width: 150px;
    text-align: left;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}
.rp-day-col {
    font-size: 12px;
}
.rp-weekend {
    background: #f3f4f6;
}
.rp-available {
    background: #d4edda;
    color: #155724;
    font-weight: bold;
}
.rp-unavailable {
    background: #f8d7da;
    color: #721c24;
    font-weight: bold;
}
.rp-no-data {
    background: #f8f9fa;
    color: #6c757d;
}
.rp-legend {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}
.rp-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.rp-legend-item span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 3px;
}
.rp-deadline-status {
    max-width: 600px;
}
.rp-progress-bar {
    height: 20px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 15px;
}
.rp-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4F46E5, #10B981);
    transition: width 0.5s ease;
}
.rp-deadline-text {
    color: #dc2626;
    font-weight: 500;
}
</style>
