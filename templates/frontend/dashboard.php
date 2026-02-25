<header class="rp-header">
    <h1>Welkom <?php echo esc_html(wp_get_current_user()->display_name); ?>!</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-rooster/'); ?>" class="rp-btn rp-btn-primary">
            📅 Bekijk Rooster
        </a>
        <a href="<?php echo home_url('/medewerker-beschikbaarheid/'); ?>" class="rp-btn rp-btn-secondary">
            ✅ Beschikbaarheid Doorgeven
        </a>
        <a href="<?php echo home_url('/medewerker-ziekmelden/'); ?>" class="rp-btn rp-btn-secondary">
            🤒 Ziekmelden
        </a>
        <a href="<?php echo home_url('/medewerker-verlof/'); ?>" class="rp-btn rp-btn-primary" style="background: #10B981;">
            🏖️ Verlof Aanvragen
        </a>
    </div>
</header>
    
    <div class="rp-dashboard-grid">
        <!-- Upcoming Shifts -->
        <section class="rp-card rp-upcoming-shifts">
            <h2>🏃 Komende Diensten</h2>
            <?php if (empty($upcoming_shifts)): ?>
            <p class="rp-empty">Geen komende diensten gepland.</p>
            <?php else: ?>
            <div class="rp-shift-list">
                <?php foreach ($upcoming_shifts as $shift): ?>
                <div class="rp-shift-item">
                    <div class="rp-shift-date">
                        <span class="rp-day"><?php echo date('d', strtotime($shift->work_date)); ?></span>
                        <span class="rp-month"><?php echo date('M', strtotime($shift->work_date)); ?></span>
                    </div>
                    <div class="rp-shift-info">
                        <h4><?php echo esc_html($shift->shift_name); ?></h4>
                        <p class="rp-shift-time">🕐 <?php echo substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5); ?></p>
                        <p class="rp-shift-location">📍 <?php echo esc_html($shift->location_name); ?></p>
                    </div>
                    <div class="rp-shift-actions">
                        <a href="<?php echo home_url('/medewerker-ruilen/?action=swap&schedule=' . $shift->id); ?>" class="rp-btn rp-btn-small">
                            Ruilen
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Notifications & Reminders -->
        <section class="rp-card rp-notifications">
            <h2>🔔 Notificaties</h2>
            
            <?php if ($deadline_passed && !$has_submitted): ?>
            <div class="rp-alert rp-alert-warning">
                <strong>Deadline voorbij!</strong>
                <p>Je hebt nog geen beschikbaarheid doorgegeven voor <?php echo date('F Y', strtotime('+1 month')); ?>.</p>
                <a href="<?php echo home_url('/medewerker-beschikbaarheid/'); ?>" class="rp-btn rp-btn-small rp-btn-primary">
                    Nu invullen
                </a>
            </div>
            <?php elseif (!$has_submitted): ?>
            <div class="rp-alert rp-alert-info">
                <strong>Herinnering</strong>
                <p>Vergeet niet je beschikbaarheid door te geven voor <?php echo date('F Y', strtotime('+1 month')); ?> vóór de 15e.</p>
                <a href="<?php echo home_url('/medewerker-beschikbaarheid/'); ?>" class="rp-btn rp-btn-small rp-btn-primary">
                    Invullen
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($pending_swaps)): ?>
            <div class="rp-alert rp-alert-info">
                <strong>Ruilverzoeken</strong>
                <p>Je hebt <?php echo count($pending_swaps); ?> openstaande ruilverzoek(en) voor jou.</p>
                <a href="<?php echo home_url('/medewerker-ruilen/'); ?>" class="rp-btn rp-btn-small rp-btn-primary">
                    Bekijken
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($unread_count > 0): ?>
            <div class="rp-alert rp-alert-info">
                <strong>Ongelezen berichten</strong>
                <p>Je hebt <?php echo $unread_count; ?> nieuwe notificatie(s).</p>
                <a href="<?php echo home_url('/medewerker-berichten/'); ?>" class="rp-btn rp-btn-small rp-btn-primary">
                    Bekijk Berichten
                </a>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Quick Actions -->
        <section class="rp-card rp-quick-links">
            <h2>⚡ Snelle Acties</h2>
            <div class="rp-action-grid">
                <a href="<?php echo home_url('/medewerker-rooster/'); ?>" class="rp-action-item">
                    <span class="rp-icon">📅</span>
                    <span>Mijn Rooster</span>
                </a>
                <a href="<?php echo home_url('/medewerker-beschikbaarheid/'); ?>" class="rp-action-item">
                    <span class="rp-icon">✅</span>
                    <span>Beschikbaarheid</span>
                </a>
                <a href="<?php echo home_url('/medewerker-ruilen/'); ?>" class="rp-action-item">
                    <span class="rp-icon">🔄</span>
                    <span>Ruilen</span>
                </a>
                <a href="<?php echo home_url('/medewerker-berichten/'); ?>" class="rp-action-item">
                    <span class="rp-icon">📨</span>
                    <span>Berichten</span>
                </a>
                <a href="<?php echo home_url('/medewerker-chat/'); ?>" class="rp-action-item">
                    <span class="rp-icon">💬</span>
                    <span>Chat</span>
                </a>
                <a href="<?php echo home_url('/medewerker-ziekmelden/'); ?>" class="rp-action-item rp-action-urgent">
                    <span class="rp-icon">🤒</span>
                    <span>Ziekmelden</span>
                </a>
                <a href="<?php echo home_url('/medewerker-verlof/'); ?>" class="rp-action-item" style="background: #d1fae5;">
                    <span class="rp-icon">🏖️</span>
                    <span>Verlof Aanvragen</span>
                </a>
                <a href="<?php echo home_url('/medewerker-profiel/'); ?>" class="rp-action-item">
                    <span class="rp-icon">👤</span>
                    <span>Mijn Profiel</span>
                </a>
            </div>
        </section>
        
        <!-- Team Chat Preview -->
        <section class="rp-card rp-chat-preview">
            <h2>💬 Team Chat</h2>
            <p class="rp-chat-hint">Blijf op de hoogte van belangrijke mededelingen.</p>
            <a href="<?php echo home_url('/medewerker-chat/'); ?>" class="rp-btn rp-btn-full rp-btn-secondary">
                Open Chat
            </a>
        </section>
    </div>
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
    margin-bottom: 30px;
}
.rp-header h1 {
    margin: 0;
    color: #1f2937;
}
.rp-header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.rp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
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
.rp-btn-small {
    padding: 6px 12px;
    font-size: 13px;
}
.rp-btn-full {
    width: 100%;
}
.rp-dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 768px) {
    .rp-dashboard-grid {
        grid-template-columns: 1fr;
    }
    .rp-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
.rp-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.rp-card h2 {
    margin: 0 0 15px;
    font-size: 18px;
    color: #1f2937;
}
.rp-empty {
    color: #9ca3af;
    font-style: italic;
    padding: 20px 0;
}
.rp-shift-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.rp-shift-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 3px solid #4F46E5;
}
.rp-shift-date {
    text-align: center;
    min-width: 50px;
}
.rp-day {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #4F46E5;
}
.rp-month {
    font-size: 12px;
    text-transform: uppercase;
    color: #6b7280;
}
.rp-shift-info {
    flex: 1;
}
.rp-shift-info h4 {
    margin: 0 0 5px;
    font-size: 16px;
}
.rp-shift-time, .rp-shift-location {
    margin: 3px 0;
    font-size: 14px;
    color: #6b7280;
}
.rp-alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.rp-alert-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
}
.rp-alert-info {
    background: #dbeafe;
    border: 1px solid #3b82f6;
}
.rp-alert strong {
    display: block;
    margin-bottom: 5px;
}
.rp-action-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
.rp-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
}
.rp-action-item:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
}
.rp-action-urgent {
    background: #fef2f2;
    color: #dc2626;
}
.rp-action-urgent:hover {
    background: #fee2e2;
}
.rp-icon {
    font-size: 24px;
    margin-bottom: 5px;
}
.rp-chat-hint {
    color: #6b7280;
    font-size: 14px;
    margin: 0 0 15px;
}
</style>
