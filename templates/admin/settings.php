<div class="wrap rp-admin-wrap">
    <h1>Rooster Planner Instellingen</h1>
    
    <div class="rp-section">
        <h2>Algemene Instellingen</h2>
        <form method="post" action="options.php" class="rp-form">
            <?php settings_fields('rooster_planner_options'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="rp_deadline_day">Deadline dag</label></th>
                    <td>
                        <select id="rp_deadline_day" name="rooster_planner_options[deadline_day]">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected(get_option('rooster_planner_deadline_day', 15), $i); ?>>
                                <?php echo $i; ?>e van elke maand
                            </option>
                            <?php endfor; ?>
                        </select>
                        <p class="description">Dag waarop medewerkers hun beschikbaarheid moeten doorgeven voor de volgende maand.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_reminder_day">Herinnering dag</label></th>
                    <td>
                        <select id="rp_reminder_day" name="rooster_planner_options[reminder_day]">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected(get_option('rooster_planner_reminder_day', 14), $i); ?>>
                                <?php echo $i; ?>e van elke maand
                            </option>
                            <?php endfor; ?>
                        </select>
                        <p class="description">Dag waarop een herinnering wordt verstuurd voor de deadline.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_email_notifications">Email Notificaties</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="rp_email_notifications" name="rooster_planner_options[email_notifications]" value="1" <?php checked(get_option('rooster_planner_email_notifications', 1)); ?>>
                            Schakel email notificaties in
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_push_notifications">Push Notificaties</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="rp_push_notifications" name="rooster_planner_options[push_notifications]" value="1" <?php checked(get_option('rooster_planner_push_notifications', 1)); ?>>
                            Schakel push notificaties in (browser)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_enable_worked_hours">Gewerkte uren bijhouden</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="rp_enable_worked_hours" name="rooster_planner_enable_worked_hours" value="1" <?php checked(get_option('rooster_planner_enable_worked_hours', 0)); ?>>
                            Activeer het invullen van gewerkte uren per shift
                        </label>
                        <p class="description">Medewerkers en admins kunnen werkelijke start- en eindtijden invoeren.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Instellingen Opslaan</button>
            </p>
        </form>
    </div>
    
    <div class="rp-section">
        <h2>� PWA (Progressive Web App) Instellingen</h2>
        <form method="post" action="options.php" class="rp-form">
            <?php settings_fields('rooster_planner_pwa_options'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="rp_pwa_app_name">App Naam</label></th>
                    <td>
                        <input type="text" id="rp_pwa_app_name" name="rooster_planner_pwa_options[app_name]" value="<?php echo esc_attr(get_option('rooster_planner_pwa_app_name', 'Rooster Planner')); ?>" class="regular-text">
                        <p class="description">Naam die op het app icoon en in de browser wordt getoond.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_pwa_app_short_name">Korte Naam</label></th>
                    <td>
                        <input type="text" id="rp_pwa_app_short_name" name="rooster_planner_pwa_options[app_short_name]" value="<?php echo esc_attr(get_option('rooster_planner_pwa_app_short_name', 'Rooster')); ?>" class="regular-text">
                        <p class="description">Verkorte naam voor op het startscherm (max 12 tekens).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_pwa_theme_color">Thema Kleur</label></th>
                    <td>
                        <input type="color" id="rp_pwa_theme_color" name="rooster_planner_pwa_options[theme_color]" value="<?php echo esc_attr(get_option('rooster_planner_pwa_theme_color', '#4F46E5')); ?>">
                        <p class="description">Hoofdkleur voor de browser toolbar en splash screen.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_pwa_background_color">Achtergrond Kleur</label></th>
                    <td>
                        <input type="color" id="rp_pwa_background_color" name="rooster_planner_pwa_options[background_color]" value="<?php echo esc_attr(get_option('rooster_planner_pwa_background_color', '#ffffff')); ?>">
                        <p class="description">Achtergrondkleur voor splash screen.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_pwa_icon_192">App Icoon (192x192)</label></th>
                    <td>
                        <input type="url" id="rp_pwa_icon_192" name="rooster_planner_pwa_options[icon_192]" value="<?php echo esc_attr(get_option('rooster_planner_pwa_icon_192')); ?>" class="regular-text" placeholder="https://...">
                        <button type="button" class="button" onclick="rpMediaUploader('rp_pwa_icon_192')">Kies afbeelding</button>
                        <?php if (get_option('rooster_planner_pwa_icon_192')): ?>
                        <br><img src="<?php echo esc_url(get_option('rooster_planner_pwa_icon_192')); ?>" style="max-width:64px;margin-top:10px;">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="rp_pwa_icon_512">App Icoon (512x512)</label></th>
                    <td>
                        <input type="url" id="rp_pwa_icon_512" name="rooster_planner_pwa_options[icon_512]" value="<?php echo esc_attr(get_option('rooster_planner_pwa_icon_512')); ?>" class="regular-text" placeholder="https://...">
                        <button type="button" class="button" onclick="rpMediaUploader('rp_pwa_icon_512')">Kies afbeelding</button>
                        <?php if (get_option('rooster_planner_pwa_icon_512')): ?>
                        <br><img src="<?php echo esc_url(get_option('rooster_planner_pwa_icon_512')); ?>" style="max-width:64px;margin-top:10px;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">PWA Instellingen Opslaan</button>
            </p>
        </form>
    </div>
    
    <div class="rp-section">
        <h2>� Import & Data</h2>
        
        <!-- Demo Data Import -->
        <div class="rp-import-block">
            <h3>Demo Data Importeren</h3>
            <p>Importeer standaard locaties (Serva, Isselt) en shifts.</p>
            <?php 
            $demo_imported = get_option('rooster_planner_demo_data_imported');
            if ($demo_imported): 
            ?>
                <div class="notice notice-success inline">
                    <p>✅ Demo data is al geïmporteerd</p>
                </div>
            <?php else: ?>
                <button type="button" class="button button-primary" id="rp-import-demo-btn">
                    📦 Importeer Demo Data
                </button>
                <span id="rp-import-demo-status"></span>
            <?php endif; ?>
        </div>
        
        <hr>
        
        <!-- Employees CSV Import -->
        <div class="rp-import-block">
            <h3>👥 Medewerkers Importeren (CSV)</h3>
            <p>Upload een CSV bestand met medewerkers. Vereiste kolommen: <code>voornaam, achternaam, email, telefoon, locaties, is_admin</code></p>
            <p><small>Locaties: komma-gescheiden lijst. Is admin: ja/nee of 1/0</small></p>
            
            <form id="rp-import-employees-form" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv,.txt" required>
                <button type="submit" class="button">Importeren</button>
            </form>
            <div id="rp-import-employees-result"></div>
            
            <p style="margin-top: 10px;">
                <a href="<?php echo ROOSTER_PLANNER_PLUGIN_URL; ?>assets/sample-employees.csv" download class="button button-small">
                    ⬇️ Download Voorbeeld CSV
                </a>
            </p>
        </div>
        
        <hr>
        
        <!-- Shifts CSV Import -->
        <div class="rp-import-block">
            <h3>⏰ Shifts Importeren (CSV)</h3>
            <p>Upload een CSV bestand met shifts. Vereiste kolommen: <code>locatie, naam, start_tijd, eind_tijd, kleur</code></p>
            <p><small>Tijden: HH:MM formaat. Kleur: optioneel hex code (#RRGGBB)</small></p>
            
            <form id="rp-import-shifts-form" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv,.txt" required>
                <button type="submit" class="button">Importeren</button>
            </form>
            <div id="rp-import-shifts-result"></div>
            
            <p style="margin-top: 10px;">
                <a href="<?php echo ROOSTER_PLANNER_PLUGIN_URL; ?>assets/sample-shifts.csv" download class="button button-small">
                    ⬇️ Download Voorbeeld CSV
                </a>
            </p>
        </div>
    </div>
    
    <div class="rp-section">
        <h2>Systeem Informatie</h2>
        <table class="form-table">
            <tr>
                <th>Plugin Versie</th>
                <td><?php echo ROOSTER_PLANNER_VERSION; ?></td>
            </tr>
            <tr>
                <th>WordPress Versie</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th>Tijdzone</th>
                <td><?php echo wp_timezone_string(); ?></td>
            </tr>
            <tr>
                <th>Huidige Tijd</th>
                <td><?php echo current_time('d-m-Y H:i:s'); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="rp-section">
        <h2>Data Export</h2>
        <p>Exporteer roosterdata naar CSV formaat.</p>
        <div class="rp-export-buttons">
            <a href="<?php echo admin_url('admin.php?page=rooster-planner-settings&export=schedules'); ?>" class="button">
                📅 Roosters Exporteren
            </a>
            <a href="<?php echo admin_url('admin.php?page=rooster-planner-settings&export=employees'); ?>" class="button">
                👥 Medewerkers Exporteren
            </a>
            <a href="<?php echo admin_url('admin.php?page=rooster-planner-settings&export=availability'); ?>" class="button">
                ✅ Beschikbaarheid Exporteren
            </a>
        </div>
    </div>
    
    <div class="rp-section rp-danger-zone">
        <h2>Gevaren Zone</h2>
        <p>Deze acties kunnen niet ongedaan worden gemaakt!</p>
        <div class="rp-danger-buttons">
            <form method="post" onsubmit="return confirm('WEET JE HET ZEKER? Dit verwijdert ALLE roosterdata permanent!')">
                <?php wp_nonce_field('rp_reset_data'); ?>
                <input type="hidden" name="rp_reset_all_data" value="1">
                <button type="submit" class="button button-link-delete">
                    🗑️ ALLE Data Resetten
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.rp-admin-wrap { max-width: 800px; }
.rp-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.rp-danger-zone {
    border: 2px solid #dc2626;
}
.rp-danger-zone h2 {
    color: #dc2626;
}
.rp-danger-buttons {
    margin-top: 15px;
}
.rp-import-block {
    margin: 20px 0;
    padding: 15px;
    background: #f0f6fc;
    border-radius: 4px;
}
.rp-import-block h3 {
    margin-top: 0;
}
.rp-import-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
}
.rp-import-result.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.rp-import-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>

<script>
// WordPress Media Uploader for PWA icons
function rpMediaUploader(targetId) {
    var frame = wp.media({
        title: 'Selecteer of upload een icoon',
        button: { text: 'Gebruik dit icoon' },
        multiple: false,
        library: { type: 'image' }
    });
    
    frame.on('select', function() {
        var attachment = frame.state().get('selection').first().toJSON();
        document.getElementById(targetId).value = attachment.url;
        // Refresh preview if exists
        var preview = document.querySelector('#' + targetId).parentNode.querySelector('img');
        if (preview) {
            preview.src = attachment.url;
        } else {
            var img = document.createElement('img');
            img.src = attachment.url;
            img.style.cssText = 'max-width:64px;margin-top:10px;';
            document.getElementById(targetId).parentNode.appendChild(document.createElement('br'));
            document.getElementById(targetId).parentNode.appendChild(img);
        }
    });
    
    frame.open();
}

jQuery(document).ready(function($) {
    // Demo Data Import
    $('#rp-import-demo-btn').on('click', function() {
        var $btn = $(this);
        var $status = $('#rp-import-demo-status');
        
        $btn.prop('disabled', true).text('Bezig met importeren...');
        $status.text('');
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_import_demo_data',
                nonce: rpAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<span style="color:green">✅ ' + response.data.message + '</span>');
                    $btn.fadeOut();
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $status.html('<span style="color:red">❌ ' + response.data + '</span>');
                    $btn.prop('disabled', false).text('📦 Importeer Demo Data');
                }
            },
            error: function() {
                $status.html('<span style="color:red">❌ Er is een fout opgetreden</span>');
                $btn.prop('disabled', false).text('📦 Importeer Demo Data');
            }
        });
    });
    
    // Employees CSV Import
    $('#rp-import-employees-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $result = $('#rp-import-employees-result');
        var formData = new FormData(this);
        formData.append('action', 'rp_import_employees_csv');
        formData.append('nonce', rpAjax.nonce);
        
        $form.find('button').prop('disabled', true).text('Bezig met importeren...');
        $result.removeClass('success error').empty();
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $form.find('button').prop('disabled', false).text('Importeren');
                
                if (response.success) {
                    var html = '<div class="rp-import-result success">';
                    html += '<strong>✅ ' + response.data.message + '</strong>';
                    if (response.data.errors.length > 0) {
                        html += '<ul style="margin-top:10px;">';
                        response.data.errors.forEach(function(err) {
                            html += '<li>' + err + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    $result.html(html);
                    $form[0].reset();
                } else {
                    $result.html('<div class="rp-import-result error">❌ ' + response.data + '</div>');
                }
            },
            error: function() {
                $form.find('button').prop('disabled', false).text('Importeren');
                $result.html('<div class="rp-import-result error">❌ Er is een fout opgetreden</div>');
            }
        });
    });
    
    // Shifts CSV Import
    $('#rp-import-shifts-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $result = $('#rp-import-shifts-result');
        var formData = new FormData(this);
        formData.append('action', 'rp_import_shifts_csv');
        formData.append('nonce', rpAjax.nonce);
        
        $form.find('button').prop('disabled', true).text('Bezig met importeren...');
        $result.removeClass('success error').empty();
        
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $form.find('button').prop('disabled', false).text('Importeren');
                
                if (response.success) {
                    var html = '<div class="rp-import-result success">';
                    html += '<strong>✅ ' + response.data.message + '</strong>';
                    if (response.data.errors.length > 0) {
                        html += '<ul style="margin-top:10px;">';
                        response.data.errors.forEach(function(err) {
                            html += '<li>' + err + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    $result.html(html);
                    $form[0].reset();
                } else {
                    $result.html('<div class="rp-import-result error">❌ ' + response.data + '</div>');
                }
            },
            error: function() {
                $form.find('button').prop('disabled', false).text('Importeren');
                $result.html('<div class="rp-import-result error">❌ Er is een fout opgetreden</div>');
            }
        });
    });
});
</script>
