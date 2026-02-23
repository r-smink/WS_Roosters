<div class="wrap rp-admin-wrap">
    <h1>Rooster Planner Dashboard</h1>
    
    <div class="rp-dashboard-stats">
        <div class="rp-stat-card">
            <div class="rp-stat-icon">👥</div>
            <div class="rp-stat-content">
                <h3><?php echo $total_employees; ?></h3>
                <p>Actieve Medewerkers</p>
            </div>
        </div>
        <div class="rp-stat-card">
            <div class="rp-stat-icon">🔄</div>
            <div class="rp-stat-content">
                <h3><?php echo $pending_swaps; ?></h3>
                <p>Openstaande Ruilingen</p>
            </div>
        </div>
        <div class="rp-stat-card">
            <div class="rp-stat-icon">🏖️</div>
            <div class="rp-stat-content">
                <h3><?php echo $pending_timeoff; ?></h3>
                <p>Verlofverzoeken</p>
            </div>
        </div>
        <div class="rp-stat-card">
            <div class="rp-stat-icon">📅</div>
            <div class="rp-stat-content">
                <h3><?php echo $today_schedules; ?></h3>
                <p>Diensten Vandaag</p>
            </div>
        </div>
    </div>
    
    <div class="rp-dashboard-grid">
        <div class="rp-dashboard-card">
            <h2>Snelle Acties</h2>
            <div class="rp-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=rooster-planner-schedules'); ?>" class="rp-btn rp-btn-primary">
                    📅 Rooster Plannen
                </a>
                <a href="<?php echo admin_url('admin.php?page=rooster-planner-swaps'); ?>" class="rp-btn rp-btn-secondary">
                    🔄 Ruilingen Beheren
                </a>
                <a href="<?php echo admin_url('admin.php?page=rooster-planner-employees'); ?>" class="rp-btn rp-btn-secondary">
                    👥 Medewerkers Beheren
                </a>
                <a href="<?php echo admin_url('admin.php?page=rooster-planner-chat'); ?>" class="rp-btn rp-btn-secondary">
                    💬 Chat Bericht
                </a>
            </div>
        </div>
        
        <div class="rp-dashboard-card">
            <h2>Beschikbaarheid Deze Maand</h2>
            <?php
            global $wpdb;
            $next_month = date('Y-m', strtotime('+1 month'));
            $availability_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT employee_id) FROM {$wpdb->prefix}rp_availability WHERE work_date LIKE %s",
                $next_month . '%'
            ));
            $total_emp = $total_employees > 0 ? $total_employees : 1;
            $percentage = round(($availability_count / $total_emp) * 100);
            ?>
            <div class="rp-progress-circle">
                <div class="rp-progress-value"><?php echo $percentage; ?>%</div>
                <div class="rp-progress-label">medewerkers hebben beschikbaarheid doorgegeven voor <?php echo date('F Y', strtotime('+1 month')); ?></div>
            </div>
            <p class="rp-deadline-notice">
                Deadline: 15 <?php echo date('F Y', strtotime('+1 month')); ?>
            </p>
        </div>
    </div>
</div>

<style>
.rp-admin-wrap {
    max-width: 1200px;
}
.rp-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 20px 0;
}
.rp-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}
.rp-stat-icon {
    font-size: 2em;
}
.rp-stat-content h3 {
    margin: 0;
    font-size: 2em;
    color: #4F46E5;
}
.rp-stat-content p {
    margin: 5px 0 0;
    color: #666;
}
.rp-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}
.rp-dashboard-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-dashboard-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.rp-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.rp-btn {
    display: inline-block;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}
.rp-btn-primary {
    background: #4F46E5;
    color: #fff;
}
.rp-btn-primary:hover {
    background: #4338CA;
}
.rp-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}
.rp-btn-secondary:hover {
    background: #e5e7eb;
}
.rp-progress-circle {
    text-align: center;
    padding: 20px;
}
.rp-progress-value {
    font-size: 3em;
    font-weight: bold;
    color: #4F46E5;
}
.rp-progress-label {
    color: #666;
    margin-top: 10px;
}
.rp-deadline-notice {
    text-align: center;
    color: #dc2626;
    font-weight: 500;
}
@media (max-width: 782px) {
    .rp-dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .rp-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>
