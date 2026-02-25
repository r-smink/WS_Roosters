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
        
        <!-- Bulk Actions Toolbar -->
        <div class="rp-bulk-actions">
            <label><input type="checkbox" id="select-all-employees"> Alles selecteren</label>
            <select id="bulk-action-select">
                <option value="">-- Bulk actie --</option>
                <option value="activate">Activeren</option>
                <option value="deactivate">Deactiveren</option>
                <option value="make_admin">Admin maken</option>
                <option value="remove_admin">Admin verwijderen</option>
                <option value="make_fixed">Vaste medewerker maken</option>
                <option value="remove_fixed">Vaste medewerker verwijderen</option>
            </select>
            <button type="button" class="button" id="apply-bulk-action" disabled>Toepassen</button>
            <span id="bulk-selected-count" style="margin-left:10px;color:#666;"></span>
        </div>
        
        <table class="wp-list-table widefat fixed striped" id="employees-table">
            <thead>
                <tr>
                    <th class="column-cb"><input type="checkbox" id="cb-select-all-1"></th>
                    <th>Naam</th>
                    <th>Email</th>
                    <th>Telefoon</th>
                    <th>Locaties</th>
                    <th>Contracturen</th>
                    <th>Functie</th>
                    <th>Rol</th>
                    <th>Status</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                <tr class="<?php echo $employee->is_active ? '' : 'rp-inactive'; ?>" data-employee-id="<?php echo $employee->id; ?>">
                    <td><input type="checkbox" class="employee-checkbox" value="<?php echo $employee->id; ?>"></td>
                    <td><?php echo esc_html($employee->display_name); ?></td>
                    <td><?php echo esc_html($employee->user_email); ?></td>
                    <td><?php echo esc_html($employee->phone); ?></td>
                    <td><?php echo $employee->locations ?: 'Geen'; ?></td>
                    <td><?php echo $employee->contract_hours ? esc_html($employee->contract_hours) . ' uur' : '-'; ?></td>
                    <td><?php echo $employee->job_role ? esc_html($employee->job_role) : '-'; ?></td>
                    <td><?php echo $employee->is_admin ? 'Admin' : 'Medewerker'; ?><?php echo $employee->is_fixed ? ' <span title="Vaste medewerker">⭐</span>' : ''; ?></td>
                    <td><?php echo $employee->is_active ? 'Actief' : 'Inactief'; ?></td>
                    <td>
                        <button type="button" class="button" onclick="editEmployee(<?php echo $employee->id; ?>, '<?php echo esc_js($employee->phone); ?>', <?php echo $employee->is_admin; ?>, <?php echo $employee->is_fixed; ?>, <?php echo intval($employee->contract_hours); ?>, '<?php echo esc_js($employee->job_role); ?>')" <?php echo $employee->is_active ? '' : 'disabled'; ?>>Bewerken</button>
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
                    <th><label for="contract_hours">Contracturen per week</label></th>
                    <td><input type="number" name="contract_hours" id="contract_hours" class="regular-text" min="0" max="168" placeholder="bijv. 32"> uur</td>
                </tr>
                <tr>
                    <th><label for="job_role">Functie/Rol</label></th>
                    <td><input type="text" name="job_role" id="job_role" class="regular-text" placeholder="bijv. Kassa, Bakery, Teamleider"></td>
                </tr>
                <tr>
                    <th><label for="is_admin">Is Admin</label></th>
                    <td>
                        <input type="checkbox" name="is_admin" id="is_admin" value="1">
                        <label for="is_admin">Medewerker kan roosters beheren</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="is_fixed">Vaste Medewerker</label></th>
                    <td>
                        <input type="checkbox" name="is_fixed" id="is_fixed" value="1">
                        <label for="is_fixed">Vaste medewerker (geen beschikbaarheid nodig, alleen voor vrij vragen)</label>
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
                <th><label for="edit_contract_hours">Contracturen per week</label></th>
                <td><input type="number" name="contract_hours" id="edit_contract_hours" class="regular-text" min="0" max="168"> uur</td>
            </tr>
            <tr>
                <th><label for="edit_job_role">Functie/Rol</label></th>
                <td><input type="text" name="job_role" id="edit_job_role" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="edit_is_admin">Is Admin</label></th>
                <td>
                    <input type="checkbox" name="is_admin" id="edit_is_admin" value="1">
                    <label for="edit_is_admin">Medewerker kan roosters beheren</label>
                </td>
            </tr>
            <tr>
                <th><label for="edit_is_fixed">Vaste Medewerker</label></th>
                <td>
                    <input type="checkbox" name="is_fixed" id="edit_is_fixed" value="1">
                    <label for="edit_is_fixed">Vaste medewerker (geen beschikbaarheid nodig, alleen voor vrij vragen)</label>
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
function editEmployee(id, phone, isAdmin, isFixed, contractHours, jobRole) {
    document.getElementById('edit_employee_id').value = id;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_is_admin').checked = isAdmin == 1;
    document.getElementById('edit_is_fixed').checked = isFixed == 1;
    document.getElementById('edit_contract_hours').value = contractHours || '';
    document.getElementById('edit_job_role').value = jobRole || '';
    document.getElementById('edit-employee-modal').style.display = 'block';
}

// Bulk Edit Functionality
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#select-all-employees, #cb-select-all-1').on('change', function() {
        var checked = $(this).is(':checked');
        $('.employee-checkbox').prop('checked', checked);
        updateBulkButton();
    });
    
    // Individual checkboxes
    $('.employee-checkbox').on('change', function() {
        updateBulkButton();
    });
    
    // Update bulk button state
    function updateBulkButton() {
        var selected = $('.employee-checkbox:checked').length;
        $('#apply-bulk-action').prop('disabled', selected === 0);
        $('#bulk-selected-count').text(selected > 0 ? selected + ' geselecteerd' : '');
    }
    
    // Apply bulk action
    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-action-select').val();
        if (!action) {
            alert('Selecteer een bulk actie');
            return;
        }
        
        var selectedIds = [];
        $('.employee-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Selecteer minstens één medewerker');
            return;
        }
        
        var confirmMessage = 'Weet je zeker dat je deze actie wilt toepassen op ' + selectedIds.length + ' medewerker(s)?';
        if (!confirm(confirmMessage)) return;
        
        // Build updates object
        var updates = {};
        selectedIds.forEach(function(id) {
            updates[id] = {};
            if (action === 'activate') updates[id].is_active = 1;
            if (action === 'deactivate') updates[id].is_active = 0;
            if (action === 'make_admin') updates[id].is_admin = 1;
            if (action === 'remove_admin') updates[id].is_admin = 0;
            if (action === 'make_fixed') updates[id].is_fixed = 1;
            if (action === 'remove_fixed') updates[id].is_fixed = 0;
        });
        
        // Send AJAX request
        $.ajax({
            url: rpAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'rp_bulk_update_employees',
                nonce: rpAjax.nonce,
                updates: JSON.stringify(updates)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Fout: ' + response.data);
                }
            },
            error: function() {
                alert('Er is een fout opgetreden');
            }
        });
    });
});
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
.rp-bulk-actions {
    background: #f6f7f7;
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.rp-bulk-actions select {
    min-width: 150px;
}
.column-cb {
    width: 30px;
    text-align: center;
}
</style>
