<header class="rp-header">
    <h1>👤 Mijn Profiel</h1>
    <div class="rp-header-actions">
        <a href="<?php echo home_url('/medewerker-dashboard/'); ?>" class="rp-btn rp-btn-secondary">
            ← Terug
        </a>
    </div>
</header>
    
    <div class="rp-profile-grid">
        <!-- Personal Info -->
        <div class="rp-card">
            <h2>Persoonlijke Informatie</h2>
            <div class="rp-profile-field">
                <label>Naam</label>
                <p><?php echo esc_html($user->display_name); ?></p>
            </div>
            <div class="rp-profile-field">
                <label>Email</label>
                <p><?php echo esc_html($user->user_email); ?></p>
            </div>
            <div class="rp-profile-field">
                <label>Telefoon</label>
                <p><?php echo $employee->phone ? esc_html($employee->phone) : '<span class="rp-missing">Niet ingevuld</span>'; ?></p>
            </div>
            <div class="rp-profile-field">
                <label>Rol</label>
                <p><?php echo $employee->is_admin ? '👑 Administrator' : '👤 Medewerker'; ?></p>
            </div>
            <a href="<?php echo wp_lostpassword_url(); ?>" class="rp-btn rp-btn-secondary">
                🔑 Wachtwoord Wijzigen
            </a>
        </div>
        
        <!-- Preferences -->
        <div class="rp-card">
            <h2>Voorkeuren</h2>
            <div class="rp-preference-field">
                <label>Thema</label>
                <div class="rp-theme-toggle">
                    <button type="button" class="rp-btn <?php echo $employee->theme_preference === 'light' ? 'rp-btn-primary' : 'rp-btn-secondary'; ?>" onclick="setTheme('light')">
                        ☀️ Licht
                    </button>
                    <button type="button" class="rp-btn <?php echo $employee->theme_preference === 'dark' ? 'rp-btn-primary' : 'rp-btn-secondary'; ?>" onclick="setTheme('dark')">
                        🌙 Donker
                    </button>
                </div>
                <p class="rp-description">Kies je voorkeursthema voor de app.</p>
            </div>
        </div>
        
        <!-- Locations -->
        <div class="rp-card">
            <h2>Mijn Locaties</h2>
            <?php if (empty($employee_locations)): ?>
            <p class="rp-empty">Je bent nog niet toegewezen aan een locatie.</p>
            <?php else: ?>
            <div class="rp-location-list">
                <?php foreach ($employee_locations as $loc): ?>
                <div class="rp-location-item">
                    <span class="rp-icon">📍</span>
                    <span><?php echo esc_html($loc->name); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistics -->
        <div class="rp-card rp-full-width">
            <h2>Statistieken Deze Maand</h2>
            <?php
            global $wpdb;
            $current_month = current_time('Y-m');
            $shifts_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rp_schedules 
                WHERE employee_id = %d AND work_date LIKE %s AND status != 'cancelled'",
                $employee->id, $current_month . '%'
            ));
            $hours_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)), 0) 
                FROM {$wpdb->prefix}rp_schedules 
                WHERE employee_id = %d AND work_date LIKE %s AND status != 'cancelled'",
                $employee->id, $current_month . '%'
            ));
            $swaps_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rp_shift_swaps 
                WHERE requester_id = %d AND status = 'completed'",
                $employee->id
            ));
            ?>
            <div class="rp-stats-grid">
                <div class="rp-stat">
                    <span class="rp-stat-value"><?php echo $shifts_count; ?></span>
                    <span class="rp-stat-label">Diensten</span>
                </div>
                <div class="rp-stat">
                    <span class="rp-stat-value"><?php echo round($hours_count); ?></span>
                    <span class="rp-stat-label">Uren</span>
                </div>
                <div class="rp-stat">
                    <span class="rp-stat-value"><?php echo $swaps_count; ?></span>
                    <span class="rp-stat-label">Ruilingen</span>
                </div>
            </div>
        </div>
        
        <!-- Export -->
        <div class="rp-card rp-full-width">
            <h2>Mijn Rooster Exporteren</h2>
            <p>Download je rooster als ICS bestand om te importeren in je eigen agenda (Google Calendar, Outlook, etc).</p>
            <a href="?export=ics" class="rp-btn rp-btn-primary">
                📥 Download ICS Bestand
            </a>
        </div>
    </div>

<script>
function setTheme(theme) {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_save_theme_preference',
            nonce: rpAjax.nonce,
            theme: theme
        },
        success: function(response) {
            if (response.success) {
                // Update button styles
                document.querySelectorAll('.rp-theme-toggle .rp-btn').forEach(function(btn) {
                    btn.classList.remove('rp-btn-primary');
                    btn.classList.add('rp-btn-secondary');
                });
                event.target.classList.remove('rp-btn-secondary');
                event.target.classList.add('rp-btn-primary');
                
                // Apply theme immediately
                document.querySelector('.rp-container').classList.toggle('rp-dark-theme', theme === 'dark');
                
                // Store in localStorage for other pages
                localStorage.setItem('rp_theme', theme);
            }
        }
    });
}

// Apply stored theme on page load
document.addEventListener('DOMContentLoaded', function() {
    var storedTheme = localStorage.getItem('rp_theme');
    if (storedTheme) {
        document.querySelector('.rp-container').classList.toggle('rp-dark-theme', storedTheme === 'dark');
    }
});
</script>

<style>
.rp-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.rp-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
.rp-btn { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; }
.rp-btn-primary { background: #4F46E5; color: #fff; }
.rp-btn-secondary { background: #f3f4f6; color: #374151; }
.rp-profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.rp-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-card h2 { margin: 0 0 20px; font-size: 18px; color: #1f2937; }
.rp-full-width { grid-column: 1 / -1; }
.rp-profile-field { margin-bottom: 15px; }
.rp-profile-field label { display: block; font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px; }
.rp-profile-field p { margin: 0; font-size: 16px; color: #1f2937; font-weight: 500; }
.rp-preference-field { margin-bottom: 15px; }
.rp-preference-field label { display: block; font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 8px; }
.rp-theme-toggle { display: flex; gap: 10px; }
.rp-description { font-size: 13px; color: #6b7280; margin-top: 8px; }
.rp-missing { color: #9ca3af; font-style: italic; }
.rp-location-list { display: flex; flex-direction: column; gap: 10px; }
.rp-location-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f9fafb; border-radius: 6px; }
.rp-empty { color: #9ca3af; font-style: italic; }
.rp-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.rp-stat { text-align: center; padding: 20px; background: #f9fafb; border-radius: 8px; }
.rp-stat-value { display: block; font-size: 32px; font-weight: 700; color: #4F46E5; }
.rp-stat-label { display: block; margin-top: 5px; color: #6b7280; font-size: 14px; }
@media (max-width: 600px) {
    .rp-profile-grid { grid-template-columns: 1fr; }
    .rp-stats-grid { grid-template-columns: 1fr; }
}
</style>
