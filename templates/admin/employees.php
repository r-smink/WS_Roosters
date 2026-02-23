<div class="wrap rp-admin-wrap">
    <h1>Medewerkers Beheren</h1>
    
    <?php if (isset($_GET['msg'])): ?>
    <div class="rp-notice rp-notice-success">
        <?php
        $messages = [
            'added' => 'Medewerker toegevoegd.',
            'updated' => 'Medewerker bijgewerkt.',
            'toggled' => 'Status gewijzigd.'
        ];
        echo $messages[$_GET['msg']] ?? 'Bewerking voltooid.';
        ?>
    </div>
    <?php endif; ?>
    
    <div class="rp-section">
        <h2>Medewerkers</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Email</th>
                    <th>Telefoon</th>
                    <th>Locaties</th>
                    <th>Rol</th>
                    <th>Status</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                <tr class="<?php echo $employee->is_active ? '' : 'rp-inactive'; ?>">
                    <td><?php echo esc_html($employee->display_name); ?></td>
                    <td><?php echo esc_html($employee->user_email); ?></td>
                    <td><?php echo esc_html($employee->phone); ?></td>
                    <td><?php echo esc_html($employee->locations ?: 'Geen'); ?></td>
                    <td><?php echo $employee->is_admin ? 'Admin' : 'Medewerker'; ?></td>
                    <td><?php echo $employee->is_active ? 'Actief' : 'Inactief'; ?></td>
                    <td>
                        <button type="button" class="button" onclick="editEmployee(<?php echo $employee->id; ?>, '<?php echo esc_js($employee->phone); ?>', <?php echo $employee->is_admin; ?>)" <?php echo $employee->is_active ? '' : 'disabled'; ?>>Bewerken</button>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('rp_admin_action'); ?>
                            <input type="hidden" name="rp_action" value="toggle_employee">
                            <input type="hidden" name="employee_id" value="<?php echo $employee->id; ?>">
                            <button type="submit" class="button">
                                <?php echo $employee->is_active ? 'Deactiveren' : 'Activeren'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="rp-section">
        <h2>Medewerker Toevoegen</h2>
        <form method="post" class="rp-form">
            <?php wp_nonce_field('rp_admin_action'); ?>
            <input type="hidden" name="rp_action" value="add_employee">
            <table class="form-table">
                <tr>
                    <th><label for="user_id">WordPress Gebruiker *</label></th>
                    <td>
                        <select name="user_id" id="user_id" required>
                            <option value="">-- Selecteer gebruiker --</option>
                            <?php foreach ($wp_users as $user): ?>
                            <option value="<?php echo $user->ID; ?>">
                                <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Alleen gebruikers zonder roosterprofiel worden getoond.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="phone">Telefoonnummer</label></th>
                    <td><input type="tel" name="phone" id="phone" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="is_admin">Is Admin</label></th>
                    <td>
                        <input type="checkbox" name="is_admin" id="is_admin" value="1">
                        <label for="is_admin">Medewerker kan roosters beheren</label>
                    </td>
                </tr>
                <tr>
                    <th><label>Locaties</label></th>
                    <td>
                        <?php foreach ($locations as $location): ?>
                        <label style="display:block;margin:5px 0;">
                            <input type="checkbox" name="employee_locations[]" value="<?php echo $location->id; ?>">
                            <?php echo esc_html($location->name); ?>
                        </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Medewerker Toevoegen</button>
            </p>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-employee-modal" style="display:none;">
    <form method="post">
        <?php wp_nonce_field('rp_admin_action'); ?>
        <input type="hidden" name="rp_action" value="edit_employee">
        <input type="hidden" name="employee_id" id="edit_employee_id">
        <table class="form-table">
            <tr>
                <th><label for="edit_phone">Telefoonnummer</label></th>
                <td><input type="tel" name="phone" id="edit_phone" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="edit_is_admin">Is Admin</label></th>
                <td>
                    <input type="checkbox" name="is_admin" id="edit_is_admin" value="1">
                    <label for="edit_is_admin">Medewerker kan roosters beheren</label>
                </td>
            </tr>
            <tr>
                <th><label>Locaties</label></th>
                <td>
                    <?php foreach ($locations as $location): ?>
                    <label style="display:block;margin:5px 0;">
                        <input type="checkbox" name="employee_locations[]" value="<?php echo $location->id; ?>" id="loc_<?php echo $location->id; ?>">
                        <?php echo esc_html($location->name); ?>
                    </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary">Opslaan</button>
            <button type="button" class="button" onclick="document.getElementById('edit-employee-modal').style.display='none'">Annuleren</button>
        </p>
    </form>
</div>

<script>
function editEmployee(id, phone, isAdmin) {
    document.getElementById('edit_employee_id').value = id;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_is_admin').checked = isAdmin == 1;
    document.getElementById('edit-employee-modal').style.display = 'block';
}
</script>

<style>
.rp-admin-wrap { max-width: 1200px; }
.rp-notice-success {
    background: #d4edda;
    border-left: 4px solid #28a745;
    padding: 12px 15px;
    margin: 15px 0;
}
.rp-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.rp-inactive { opacity: 0.6; }
#edit-employee-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    z-index: 1000;
    min-width: 500px;
}
</style>
