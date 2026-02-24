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
            
            <?php $dark_theme_enabled = get_option('rooster_planner_enable_dark_theme', 1); ?>
            <?php if ($dark_theme_enabled): ?>
            <div class="rp-preference-field">
                <label>Thema</label>
                <div class="rp-theme-toggle">
                    <button type="button" class="rp-btn <?php echo $employee->theme_preference === 'light' ? 'rp-btn-primary' : 'rp-btn-secondary'; ?>" onclick="setTheme('light', this)">
                        ☀️ Licht
                    </button>
                    <button type="button" class="rp-btn <?php echo $employee->theme_preference === 'dark' ? 'rp-btn-primary' : 'rp-btn-secondary'; ?>" onclick="setTheme('dark', this)">
                        🌙 Donker
                    </button>
                </div>
                <p class="rp-description">Kies je voorkeursthema voor de app.</p>
            </div>
            <?php endif; ?>
            
            <div class="rp-preference-field">
                <label>Email Notificaties</label>
                <div class="rp-notification-toggle">
                    <label class="rp-toggle-label">
                        <input type="checkbox" id="rp-email-notifications" <?php checked($employee->email_notifications, 1); ?> onchange="toggleEmailNotifications(this)">
                        <span class="rp-toggle-slider"></span>
                        <span class="rp-toggle-text">Ontvang email notificaties</span>
                    </label>
                </div>
                <p class="rp-description">Schakel in om email notificaties te ontvangen voor diensten, ruilverzoeken en belangrijke mededelingen.</p>
            </div>
            
            <div class="rp-preference-field">
                <label>Push Notificaties</label>
                <div class="rp-pwa-notifications">
                    <button type="button" class="rp-btn rp-btn-secondary" id="rp-enable-pwa-btn" onclick="enablePWANotifications()">
                        🔔 PWA Notificaties Activeren
                    </button>
                    <span id="rp-pwa-status" class="rp-pwa-status"></span>
                </div>
                <p class="rp-description">Activeer push notificaties om meldingen te ontvangen zelfs wanneer de app gesloten is.</p>
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
function setTheme(theme, btn) {
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
                document.querySelectorAll('.rp-theme-toggle .rp-btn').forEach(function(b) {
                    b.classList.remove('rp-btn-primary');
                    b.classList.add('rp-btn-secondary');
                });
                btn.classList.remove('rp-btn-secondary');
                btn.classList.add('rp-btn-primary');
                
                // Apply theme immediately
                document.querySelector('.rp-container').classList.toggle('rp-dark-theme', theme === 'dark');
                
                // Store in localStorage for other pages
                localStorage.setItem('rp_theme', theme);
            }
        }
    });
}

function toggleEmailNotifications(checkbox) {
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_save_email_preference',
            nonce: rpAjax.nonce,
            enabled: checkbox.checked ? 1 : 0
        },
        success: function(response) {
            if (response.success) {
                // Show success feedback
                var statusText = document.createElement('span');
                statusText.className = 'rp-save-feedback';
                statusText.textContent = checkbox.checked ? '✓ Email notificaties ingeschakeld' : '✓ Email notificaties uitgeschakeld';
                statusText.style.cssText = 'color: #059669; margin-left: 10px; font-size: 12px;';
                
                var container = checkbox.closest('.rp-notification-toggle');
                var existing = container.querySelector('.rp-save-feedback');
                if (existing) existing.remove();
                
                container.appendChild(statusText);
                setTimeout(function() {
                    if (statusText.parentNode) statusText.remove();
                }, 3000);
            }
        }
    });
}

function enablePWANotifications() {
    var btn = document.getElementById('rp-enable-pwa-btn');
    var status = document.getElementById('rp-pwa-status');
    
    if (!('Notification' in window)) {
        status.textContent = '❌ Notificaties niet ondersteund in deze browser';
        status.style.color = '#dc2626';
        return;
    }
    
    if (Notification.permission === 'granted') {
        status.textContent = '✅ Push notificaties zijn al geactiveerd';
        status.style.color = '#059669';
        btn.textContent = '🔔 Notificaties Geactiveerd';
        btn.classList.remove('rp-btn-secondary');
        btn.classList.add('rp-btn-success');
        btn.disabled = true;
        return;
    }
    
    if (Notification.permission === 'denied') {
        status.innerHTML = '⚠️ Notificaties zijn geblokkeerd. <a href="#" onclick="showNotificationHelp(); return false;">Hoe inschakelen?</a>';
        status.style.color = '#f59e0b';
        return;
    }
    
    btn.textContent = '📤 Bezig met activeren...';
    btn.disabled = true;
    
    Notification.requestPermission().then(function(permission) {
        btn.disabled = false;
        
        if (permission === 'granted') {
            status.textContent = '✅ Push notificaties zijn nu geactiveerd!';
            status.style.color = '#059669';
            btn.textContent = '🔔 Notificaties Geactiveerd';
            btn.classList.remove('rp-btn-secondary');
            btn.classList.add('rp-btn-success');
            btn.disabled = true;
            
            // Send to server for tracking
            jQuery.ajax({
                url: rpAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rp_save_push_preference',
                    nonce: rpAjax.nonce,
                    enabled: 1
                }
            });
        } else if (permission === 'denied') {
            status.textContent = '❌ Toestemming geweigerd. Controleer je browser instellingen.';
            status.style.color = '#dc2626';
            btn.textContent = '🔔 PWA Notificaties Activeren';
        } else {
            status.textContent = '⏳ Wachtend op toestemming...';
            status.style.color = '#6b7280';
            btn.textContent = '🔔 PWA Notificaties Activeren';
        }
    });
}

function showNotificationHelp() {
    alert('Om notificaties in te schakelen:\n\n1. Klik op het slotje 🔒 naast de URL in je browser\n2. Zoek naar "Notificaties" of "Meldingen"\n3. Verander de instelling naar "Toestaan"\n4. Herlaad deze pagina en probeer opnieuw');
}

// Check current notification status on page load
document.addEventListener('DOMContentLoaded', function() {
    if ('Notification' in window) {
        var btn = document.getElementById('rp-enable-pwa-btn');
        var status = document.getElementById('rp-pwa-status');
        
        if (Notification.permission === 'granted') {
            status.textContent = '✅ Actief';
            status.style.color = '#059669';
            btn.textContent = '🔔 Notificaties Geactiveerd';
            btn.classList.remove('rp-btn-secondary');
            btn.classList.add('rp-btn-success');
            btn.disabled = true;
        } else if (Notification.permission === 'denied') {
            status.textContent = '⚠️ Geblokkeerd';
            status.style.color = '#f59e0b';
        }
    }
});

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
.rp-btn-success { background: #10B981; color: #fff; }
.rp-profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.rp-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.rp-card h2 { margin: 0 0 20px; font-size: 18px; color: #1f2937; }
.rp-full-width { grid-column: 1 / -1; }
.rp-profile-field { margin-bottom: 15px; }
.rp-profile-field label { display: block; font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px; }
.rp-profile-field p { margin: 0; font-size: 16px; color: #1f2937; font-weight: 500; }
.rp-preference-field { margin-bottom: 20px; }
.rp-preference-field label { display: block; font-size: 12px; color: #9ca3af; text-transform: uppercase; margin-bottom: 8px; }
.rp-theme-toggle { display: flex; gap: 10px; }
.rp-description { font-size: 13px; color: #6b7280; margin-top: 8px; margin-bottom: 0; }
.rp-missing { color: #9ca3af; font-style: italic; }
.rp-location-list { display: flex; flex-direction: column; gap: 10px; }
.rp-location-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f9fafb; border-radius: 6px; }
.rp-empty { color: #9ca3af; font-style: italic; }
.rp-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.rp-stat { text-align: center; padding: 20px; background: #f9fafb; border-radius: 8px; }
.rp-stat-value { display: block; font-size: 32px; font-weight: 700; color: #4F46E5; }
.rp-stat-label { display: block; margin-top: 5px; color: #6b7280; font-size: 14px; }

/* Toggle Switch Styles */
.rp-toggle-label { display: flex; align-items: center; cursor: pointer; gap: 10px; }
.rp-toggle-label input[type="checkbox"] { display: none; }
.rp-toggle-slider { position: relative; width: 44px; height: 24px; background: #d1d5db; border-radius: 12px; transition: all 0.3s; flex-shrink: 0; }
.rp-toggle-slider::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: all 0.3s; }
.rp-toggle-label input[type="checkbox"]:checked + .rp-toggle-slider { background: #4F46E5; }
.rp-toggle-label input[type="checkbox"]:checked + .rp-toggle-slider::after { transform: translateX(20px); }
.rp-toggle-text { font-size: 14px; color: #374151; }

/* PWA Notifications */
.rp-pwa-notifications { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.rp-pwa-status { font-size: 13px; }
@media (max-width: 600px) {
    .rp-profile-grid { grid-template-columns: 1fr; }
    .rp-stats-grid { grid-template-columns: 1fr; }
}

/* Dark theme support for profiel page */
.rp-container.rp-dark-theme .rp-profile-field label,
.rp-container.rp-dark-theme .rp-preference-field label {
    color: var(--rp-text-muted);
}
.rp-container.rp-dark-theme .rp-profile-field p {
    color: var(--rp-text);
}
.rp-container.rp-dark-theme .rp-toggle-text {
    color: var(--rp-text);
}
.rp-container.rp-dark-theme .rp-toggle-slider {
    background: #4b5563;
}
.rp-container.rp-dark-theme .rp-toggle-label input[type="checkbox"]:checked + .rp-toggle-slider {
    background: #818cf8;
}
.rp-container.rp-dark-theme .rp-toggle-label input[type="checkbox"]:checked + .rp-toggle-slider::after {
    background: #fff;
}
.rp-container.rp-dark-theme .rp-pwa-status {
    color: var(--rp-text);
}
</style>
