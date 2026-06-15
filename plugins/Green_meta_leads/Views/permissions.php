<?php
$permissions = isset($permissions) && is_array($permissions) ? $permissions : [];
if (!$permissions) {
    $role_id = service("uri")->getSegment(3);
    if ($role_id) {
        $db = db_connect();
        $roles_table = $db->prefixTable("roles");
        $role = $db->query("SELECT permissions FROM $roles_table WHERE id=" . (int) $role_id . " LIMIT 1")->getRow();
        $permissions = $role && $role->permissions ? @unserialize($role->permissions) : [];
        $permissions = is_array($permissions) ? $permissions : [];
    }
}
?>
<li>
    <span data-feather="facebook" class="icon-14 ml-20"></span>
    <h5>Leads Facebook:</h5>
    <div>
        <?php echo form_checkbox("green_meta_leads_view", "1", get_array_value($permissions, "green_meta_leads_view") ? true : false, "id='green_meta_leads_view' class='form-check-input'"); ?>
        <label for="green_meta_leads_view">Visualizar leads do Facebook</label>
    </div>
    <div>
        <?php echo form_checkbox("green_meta_leads_sync", "1", get_array_value($permissions, "green_meta_leads_sync") ? true : false, "id='green_meta_leads_sync' class='form-check-input'"); ?>
        <label for="green_meta_leads_sync">Executar sincronização</label>
    </div>
</li>
