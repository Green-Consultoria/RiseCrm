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

$green_perms = [
    "green_crm_view" => "Acessar o Green CRM",
    "green_crm_manage_leads" => "Gerenciar leads e tarefas",
    "green_crm_manage_sales" => "Gerenciar vendas",
    "green_crm_manage_commissions" => "Gerenciar comissões",
    "green_crm_manage_settings" => "Gerenciar configurações (operadoras, planos, status)",
    "green_crm_view_passwords" => "Ver banco de senhas",
    "green_crm_manage_passwords" => "Criar/editar senhas",
    "green_crm_reveal_passwords" => "Revelar/copiar senha"
];
?>
<li>
    <span data-feather="activity" class="icon-14 ml-20"></span>
    <h5>Green CRM:</h5>
    <?php foreach ($green_perms as $key => $label) { ?>
        <div>
            <?php echo form_checkbox($key, "1", get_array_value($permissions, $key) ? true : false, "id='$key' class='form-check-input'"); ?>
            <label for="<?php echo $key; ?>"><?php echo $label; ?></label>
        </div>
    <?php } ?>
</li>
