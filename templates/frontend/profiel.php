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
            <form id="rp-profile-form">
                <div class="rp-profile-field">
                    <label>Naam</label>
                    <p><?php echo esc_html($user->display_name); ?></p>
                </div>
                <div class="rp-profile-field rp-editable-field">
                    <label for="rp-profile-email">Email</label>
                    <div class="rp-field-display" id="rp-email-display">
                        <p><?php echo esc_html($user->user_email); ?></p>
                        <button type="button" class="rp-edit-btn" onclick="toggleEditField('email')" title="Bewerken">✏️</button>
                    </div>
                    <div class="rp-field-edit" id="rp-email-edit" style="display:none;">
                        <input type="email" id="rp-profile-email" value="<?php echo esc_attr($user->user_email); ?>" placeholder="je@email.nl">
                        <button type="button" class="rp-save-field-btn" onclick="toggleEditField('email')">Annuleer</button>
                    </div>
                </div>
                <div class="rp-profile-field rp-editable-field">
                    <label for="rp-profile-phone">Telefoon</label>
                    <div class="rp-field-display" id="rp-phone-display">
                        <p><?php echo $employee->phone ? esc_html($employee->phone) : '<span class="rp-missing">Niet ingevuld</span>'; ?></p>
                        <button type="button" class="rp-edit-btn" onclick="toggleEditField('phone')" title="Bewerken">✏️</button>
                    </div>
                    <div class="rp-field-edit" id="rp-phone-edit" style="display:none;">
                        <input type="tel" id="rp-profile-phone" value="<?php echo esc_attr($employee->phone); ?>" placeholder="06-12345678">
                        <button type="button" class="rp-save-field-btn" onclick="toggleEditField('phone')">Annuleer</button>
                    </div>
                </div>
                <div class="rp-profile-field">
                    <label>Functie</label>
                    <p><?php echo $employee->job_role ? esc_html($employee->job_role) : '<span class="rp-missing">Niet ingevuld</span>'; ?></p>
                </div>
                <div class="rp-profile-field">
                    <label>Contracturen</label>
                    <p><?php echo $employee->contract_hours ? esc_html($employee->contract_hours) . ' uur/week' : '<span class="rp-missing">Niet ingevuld</span>'; ?></p>
                </div>
                <div class="rp-profile-field">
                    <label>Rol</label>
                    <p><?php echo $employee->is_admin ? '👑 Administrator' : '👤 Medewerker'; ?></p>
                </div>
                <div class="rp-profile-actions">
                    <button type="submit" class="rp-btn rp-btn-primary" id="rp-save-profile-btn" style="display:none;">
                        Opslaan
                    </button>
                    <span id="rp-profile-feedback" class="rp-profile-feedback"></span>
                </div>
            </form>
            <a href="<?php echo wp_lostpassword_url(); ?>" class="rp-btn rp-btn-secondary" style="margin-top: 15px;">
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
                <div class="rp-checkbox-field">
                    <label class="rp-checkbox-label">
                        <input type="checkbox" id="rp-email-notifications" <?php checked($employee->email_notifications, 1); ?> onchange="toggleEmailNotifications(this)">
                        <span>Ontvang email notificaties voor diensten, ruilverzoeken en belangrijke mededelingen</span>
                    </label>
                </div>
                <p class="rp-description">Schakel in om email notificaties te ontvangen.</p>
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
            <h2>📅 iCal Feed - Altijd Synchroon</h2>
            <p>Gebruik deze unieke link om je rooster te synchroniseren met je persoonlijke agenda (Google Calendar, Outlook, Apple Calendar).</p>
            <p class="rp-ical-info">Deze link blijft altijd actief en synchroniseert automatisch wanneer een nieuwe maand definitief wordt gemaakt.</p>
            
            <?php
            use RoosterPlanner\ICalExport;
            $ical_url = ICalExport::get_ical_url($employee->id);
            ?>
            
            <div class="rp-ical-url-container">
                <input type="text" id="ical-url" value="<?php echo esc_url($ical_url); ?>" readonly class="rp-ical-input">
                <button type="button" class="rp-btn rp-btn-secondary" onclick="copyIcalUrl()">
                    📋 Kopieer Link
                </button>
            </div>
            
            <div class="rp-ical-instructions">
                <h4>📱 Hoe te gebruiken:</h4>
                <ul>
                    <li><strong>Google Calendar:</strong> Instellingen → Agenda's toevoegen → Per URL toevoegen</li>
                    <li><strong>Outlook:</strong> Agenda toevoegen → Abonneren op internetagenda</li>
                    <li><strong>iPhone/iPad:</strong> Instellingen → Agenda's → Accounts → Agenda toevoegen → Abonneren op agenda</li>
                </ul>
            </div>
            
            <div class="rp-ical-actions">
                <a href="<?php echo esc_url($ical_url); ?>" class="rp-btn rp-btn-primary" download>
                    📥 Download ICS Bestand
                </a>
                <button type="button" class="rp-btn rp-btn-secondary" onclick="regenerateIcalToken()">
                    🔄 Nieuwe Link Genereren
                </button>
            </div>
        </div>
    </div>

<script>
function toggleEditField(field) {
    var display = document.getElementById('rp-' + field + '-display');
    var edit = document.getElementById('rp-' + field + '-edit');
    var isEditing = edit.style.display !== 'none';
    
    display.style.display = isEditing ? 'flex' : 'none';
    edit.style.display = isEditing ? 'none' : 'flex';
    
    if (!isEditing) {
        edit.querySelector('input').focus();
    }
    
    // Show/hide save button based on any field being edited
    var anyEditing = document.querySelectorAll('.rp-field-edit[style*="display: flex"], .rp-field-edit:not([style*="display: none"])');
    var visibleEdits = Array.from(document.querySelectorAll('.rp-field-edit')).filter(function(el) { return el.style.display !== 'none'; });
    document.getElementById('rp-save-profile-btn').style.display = visibleEdits.length > 0 ? 'inline-flex' : 'none';
}

jQuery('#rp-profile-form').on('submit', function(e) {
    e.preventDefault();
    
    var btn = document.getElementById('rp-save-profile-btn');
    var feedback = document.getElementById('rp-profile-feedback');
    btn.disabled = true;
    btn.textContent = 'Opslaan...';
    feedback.textContent = '';
    
    var data = {
        action: 'rp_save_profile',
        nonce: rpAjax.nonce
    };
    
    var emailEdit = document.getElementById('rp-email-edit');
    if (emailEdit.style.display !== 'none') {
        data.email = document.getElementById('rp-profile-email').value;
    }
    var phoneEdit = document.getElementById('rp-phone-edit');
    if (phoneEdit.style.display !== 'none') {
        data.phone = document.getElementById('rp-profile-phone').value;
    }
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: data,
        success: function(response) {
            btn.disabled = false;
            btn.textContent = 'Opslaan';
            if (response.success) {
                feedback.textContent = '✓ Profiel bijgewerkt!';
                feedback.style.color = '#059669';
                // Update display values
                if (data.email) {
                    document.querySelector('#rp-email-display p').textContent = data.email;
                    toggleEditField('email');
                }
                if (data.phone !== undefined) {
                    var phoneP = document.querySelector('#rp-phone-display p');
                    phoneP.innerHTML = data.phone || '<span class="rp-missing">Niet ingevuld</span>';
                    toggleEditField('phone');
                }
                setTimeout(function() { feedback.textContent = ''; }, 3000);
            } else {
                feedback.textContent = response.data || 'Er is een fout opgetreden.';
                feedback.style.color = '#dc2626';
            }
        },
        error: function() {
            btn.disabled = false;
            btn.textContent = 'Opslaan';
            feedback.textContent = 'Er is een fout opgetreden.';
            feedback.style.color = '#dc2626';
        }
    });
});

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

function copyIcalUrl() {
    var input = document.getElementById('ical-url');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = document.querySelector('button[onclick="copyIcalUrl()"]');
        var originalText = btn.textContent;
        btn.textContent = '✅ Gekopieerd!';
        setTimeout(function() {
            btn.textContent = originalText;
        }, 2000);
    });
}

function regenerateIcalToken() {
    if (!confirm('Weet je zeker dat je een nieuwe iCal link wilt genereren?\n\nDe oude link zal niet meer werken en je moet de nieuwe link toevoegen aan je agenda.')) {
        return;
    }
    
    jQuery.ajax({
        url: rpAjax.ajaxUrl,
        type: 'POST',
        data: {
            action: 'rp_regenerate_ical_token',
            nonce: rpAjax.nonce
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('ical-url').value = response.data.url;
                alert('Nieuwe iCal link gegenereerd!\n\nVergeet niet de nieuwe link toe te voegen aan je agenda.');
            } else {
                alert('Fout bij genereren van nieuwe link: ' + response.data);
            }
        },
        error: function() {
            alert('Er is een fout opgetreden.');
        }
    });
}
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
.rp-field-display { display: flex; align-items: center; gap: 8px; }
.rp-field-display p { margin: 0; flex: 1; }
.rp-edit-btn { background: none; border: none; cursor: pointer; font-size: 14px; padding: 4px 8px; border-radius: 4px; opacity: 0.6; transition: opacity 0.2s; }
.rp-edit-btn:hover { opacity: 1; background: #f3f4f6; }
.rp-field-edit { display: flex; gap: 8px; align-items: center; }
.rp-field-edit input { flex: 1; padding: 8px 12px; border: 2px solid #4F46E5; border-radius: 6px; font-size: 14px; outline: none; }
.rp-save-field-btn { background: #f3f4f6; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; color: #6b7280; white-space: nowrap; }
.rp-save-field-btn:hover { background: #e5e7eb; }
.rp-profile-actions { margin-top: 15px; display: flex; align-items: center; gap: 12px; }
.rp-profile-feedback { font-size: 13px; font-weight: 500; }
.rp-location-list { display: flex; flex-direction: column; gap: 10px; }
.rp-location-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f9fafb; border-radius: 6px; }
.rp-empty { color: #9ca3af; font-style: italic; }
.rp-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.rp-stat { text-align: center; padding: 20px; background: #f9fafb; border-radius: 8px; }
.rp-stat-value { display: block; font-size: 32px; font-weight: 700; color: #4F46E5; }
.rp-stat-label { display: block; margin-top: 5px; color: #6b7280; font-size: 14px; }

/* Toggle Switch Styles */
.rp-toggle-label { display: flex; align-items: center; cursor: pointer; gap: 12px; flex-wrap: nowrap; }
.rp-toggle-label input[type="checkbox"] { display: none; }
.rp-toggle-slider { position: relative; width: 44px; height: 24px; background: #d1d5db; border-radius: 12px; transition: all 0.3s; flex-shrink: 0; flex-grow: 0; }
.rp-toggle-slider::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: all 0.3s; }
.rp-toggle-label input[type="checkbox"]:checked + .rp-toggle-slider { background: #4F46E5; }
.rp-toggle-label input[type="checkbox"]:checked + .rp-toggle-slider::after { transform: translateX(20px); }
.rp-toggle-text { font-size: 14px; color: #374151; white-space: nowrap; }

/* Checkbox Styles */
.rp-checkbox-field { margin-top: 8px; }
.rp-checkbox-label { display: flex !important; align-items: flex-start !important; cursor: pointer; gap: 10px; flex-direction: row !important; }
.rp-checkbox-label input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; margin-top: 2px; flex-shrink: 0; }
.rp-checkbox-label span { font-size: 14px; color: #374151; text-transform: none !important; font-weight: 400 !important; line-height: 1.5; }

/* PWA Notifications */
.rp-pwa-notifications { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.rp-pwa-status { font-size: 13px; }
@media (max-width: 600px) {
    .rp-profile-grid { grid-template-columns: 1fr; }
    .rp-stats-grid { grid-template-columns: 1fr; }
    .rp-ical-url-container { flex-direction: column; }
    .rp-ical-actions { flex-direction: column; }
}

/* iCal Section Styles */
.rp-ical-info {
    background: #dbeafe;
    padding: 12px;
    border-radius: 6px;
    font-size: 13px;
    color: #1e40af;
    margin: 10px 0;
}
.rp-ical-url-container {
    display: flex;
    gap: 10px;
    margin: 15px 0;
}
.rp-ical-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    background: #f9fafb;
}
.rp-ical-instructions {
    background: #f9fafb;
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
}
.rp-ical-instructions h4 {
    margin: 0 0 10px 0;
    color: #374151;
}
.rp-ical-instructions ul {
    margin: 0;
    padding-left: 20px;
}
.rp-ical-instructions li {
    margin: 5px 0;
    font-size: 13px;
    color: #6b7280;
}
.rp-ical-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Dark theme support for iCal section */
.rp-container.rp-dark-theme .rp-ical-info {
    background: #1e3a8a;
    color: #93c5fd;
}
.rp-container.rp-dark-theme .rp-ical-input {
    background: #374151;
    border-color: #4b5563;
    color: #f9fafb;
}
.rp-container.rp-dark-theme .rp-ical-instructions {
    background: #374151;
}
.rp-container.rp-dark-theme .rp-ical-instructions h4 {
    color: #f9fafb;
}
.rp-container.rp-dark-theme .rp-ical-instructions li {
    color: #d1d5db;
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
