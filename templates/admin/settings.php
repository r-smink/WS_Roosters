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
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Instellingen Opslaan</button>
            </p>
        </form>
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
</style>
