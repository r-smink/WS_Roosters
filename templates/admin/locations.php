<div class="wrap rp-admin-wrap">
    <h1>Locaties & Shifts Beheren</h1>
    
    <?php if (isset($_GET['msg'])): ?>
    <div class="rp-notice rp-notice-success">
        <?php
        $messages = [
            'added' => 'Locatie toegevoegd.',
            'updated' => 'Locatie bijgewerkt.',
            'deleted' => 'Locatie verwijderd.',
            'shift_added' => 'Shift toegevoegd.',
            'shift_updated' => 'Shift bijgewerkt.',
            'shift_deleted' => 'Shift verwijderd.'
        ];
        echo $messages[$_GET['msg']] ?? 'Bewerking voltooid.';
        ?>
    </div>
    <?php endif; ?>
    
    <div class="rp-section">
        <h2>Locaties</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Adres</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $location): ?>
                <tr>
                    <td><?php echo esc_html($location->name); ?></td>
                    <td><?php echo esc_html($location->address); ?></td>
                    <td>
                        <button type="button" class="button" onclick="editLocation(<?php echo $location->id; ?>, '<?php echo esc_js($location->name); ?>', '<?php echo esc_js($location->address); ?>')">Bewerken</button>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="delete_location">
                            <input type="hidden" name="location_id" value="<?php echo $location->id; ?>">
                            <button type="submit" class="button button-link-delete" onclick="return confirm('Weet je het zeker?')">Verwijderen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Nieuwe Locatie Toevoegen</h3>
        <form method="post" class="rp-form">
            <?php wp_nonce_field('rp_admin_action'); ?>
            <input type="hidden" name="rp_action" value="add_location">
            <table class="form-table">
                <tr>
                    <th><label for="location_name">Naam</label></th>
                    <td><input type="text" name="location_name" id="location_name" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="location_address">Adres</label></th>
                    <td><textarea name="location_address" id="location_address" rows="3" class="regular-text"></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Locatie Toevoegen</button>
            </p>
        </form>
    </div>
    
    <div class="rp-section">
        <h2>Shifts</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Locatie</th>
                    <th>Naam</th>
                    <th>Starttijd</th>
                    <th>Eindtijd</th>
                    <th>Kleur</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shifts as $shift): ?>
                <tr>
                    <td><?php echo esc_html($shift->location_name); ?></td>
                    <td><?php echo esc_html($shift->name); ?></td>
                    <td><?php echo substr($shift->start_time, 0, 5); ?></td>
                    <td><?php echo substr($shift->end_time, 0, 5); ?></td>
                    <td><span style="display:inline-block;width:20px;height:20px;background:<?php echo esc_attr($shift->color); ?>;border-radius:3px;"></span></td>
                    <td>
                        <button type="button" class="button" onclick="editShift(<?php echo $shift->id; ?>, <?php echo $shift->location_id; ?>, '<?php echo esc_js($shift->name); ?>', '<?php echo $shift->start_time; ?>', '<?php echo $shift->end_time; ?>', '<?php echo $shift->color; ?>')">Bewerken</button>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="delete_shift">
                            <input type="hidden" name="shift_id" value="<?php echo $shift->id; ?>">
                            <button type="submit" class="button button-link-delete" onclick="return confirm('Weet je het zeker?')">Verwijderen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Nieuwe Shift Toevoegen</h3>
        <form method="post" class="rp-form">
            <?php wp_nonce_field('rp_admin_action'); ?>
            <input type="hidden" name="rp_action" value="add_shift">
            <table class="form-table">
                <tr>
                    <th><label for="shift_location">Locatie</label></th>
                    <td>
                        <select name="shift_location" id="shift_location" required>
                            <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location->id; ?>"><?php echo esc_html($location->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="shift_name">Naam</label></th>
                    <td><input type="text" name="shift_name" id="shift_name" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="start_time">Starttijd</label></th>
                    <td><input type="time" name="start_time" id="start_time" required></td>
                </tr>
                <tr>
                    <th><label for="end_time">Eindtijd</label></th>
                    <td><input type="time" name="end_time" id="end_time" required></td>
                </tr>
                <tr>
                    <th><label for="shift_color">Kleur</label></th>
                    <td><input type="color" name="shift_color" id="shift_color" value="#4F46E5"></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Shift Toevoegen</button>
            </p>
        </form>
    </div>
</div>

<!-- Edit Modals -->
<div id="edit-location-modal" style="display:none;">
    <form method="post">
        <?php wp_nonce_field('rp_admin_action'); ?>
        <input type="hidden" name="rp_action" value="edit_location">
        <input type="hidden" name="location_id" id="edit_location_id">
        <p>
            <label>Naam:</label>
            <input type="text" name="location_name" id="edit_location_name" required class="regular-text">
        </p>
        <p>
            <label>Adres:</label>
            <textarea name="location_address" id="edit_location_address" rows="3" class="regular-text"></textarea>
        </p>
        <p>
            <button type="submit" class="button button-primary">Opslaan</button>
            <button type="button" class="button" onclick="closeModal('edit-location-modal')">Annuleren</button>
        </p>
    </form>
</div>

<div id="edit-shift-modal" style="display:none;">
    <form method="post">
        <?php wp_nonce_field('rp_admin_action'); ?>
        <input type="hidden" name="rp_action" value="edit_shift">
        <input type="hidden" name="shift_id" id="edit_shift_id">
        <p>
            <label>Locatie:</label>
            <select name="shift_location" id="edit_shift_location" required>
                <?php foreach ($locations as $location): ?>
                <option value="<?php echo $location->id; ?>"><?php echo esc_html($location->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label>Naam:</label>
            <input type="text" name="shift_name" id="edit_shift_name" required class="regular-text">
        </p>
        <p>
            <label>Starttijd:</label>
            <input type="time" name="start_time" id="edit_start_time" required>
        </p>
        <p>
            <label>Eindtijd:</label>
            <input type="time" name="end_time" id="edit_end_time" required>
        </p>
        <p>
            <label>Kleur:</label>
            <input type="color" name="shift_color" id="edit_shift_color">
        </p>
        <p>
            <button type="submit" class="button button-primary">Opslaan</button>
            <button type="button" class="button" onclick="closeModal('edit-shift-modal')">Annuleren</button>
        </p>
    </form>
</div>

<script>
function editLocation(id, name, address) {
    document.getElementById('edit_location_id').value = id;
    document.getElementById('edit_location_name').value = name;
    document.getElementById('edit_location_address').value = address;
    document.getElementById('edit-location-modal').style.display = 'block';
}

function editShift(id, locationId, name, startTime, endTime, color) {
    document.getElementById('edit_shift_id').value = id;
    document.getElementById('edit_shift_location').value = locationId;
    document.getElementById('edit_shift_name').value = name;
    document.getElementById('edit_start_time').value = startTime;
    document.getElementById('edit_end_time').value = endTime;
    document.getElementById('edit_shift_color').value = color;
    document.getElementById('edit-shift-modal').style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
</script>

<style>
.rp-admin-wrap {
    max-width: 1200px;
}
.rp-notice {
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 4px;
}
.rp-notice-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}
.rp-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #eee;
}
.rp-section h3 {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
#edit-location-modal, #edit-shift-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    z-index: 1000;
    min-width: 400px;
}
#edit-location-modal p, #edit-shift-modal p {
    margin: 15px 0;
}
#edit-location-modal label, #edit-shift-modal label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
</style>
