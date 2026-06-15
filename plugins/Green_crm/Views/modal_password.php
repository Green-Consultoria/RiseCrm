<?php
$model_info = $model_info ?? new \stdClass();
$vault_id = (int) ($model_info->id ?? 0);
$members_dropdown = $members_dropdown ?? ["" => "-"];
$current_user_id = (int) ($current_user_id ?? 0);
$has_password = $has_password ?? false;
$selected_owner = (int) ($model_info->owner_user_id ?? $current_user_id);
$scope = $model_info->visibility_scope ?? "team";
?>

<?php echo form_open(get_uri("green_crm/save_password"), ["id" => "green-password-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $vault_id; ?>">
        <div class="form-group row">
            <label class="col-md-3">Nome do acesso</label>
            <div class="col-md-9"><?php echo form_input(["name" => "title", "value" => $model_info->title ?? "", "class" => "form-control", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Categoria</label>
            <div class="col-md-4"><?php echo form_input(["name" => "category", "value" => $model_info->category ?? "", "class" => "form-control", "placeholder" => "Ex.: Operadora, Portal, Ferramenta"]); ?></div>
            <label class="col-md-2">Responsável</label>
            <div class="col-md-3"><?php echo form_dropdown("owner_user_id", $members_dropdown, $selected_owner, ["class" => "form-control select2"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Sistema / URL</label>
            <div class="col-md-9"><?php echo form_input(["name" => "system_url", "value" => $model_info->system_url ?? "", "class" => "form-control", "placeholder" => "https://..."]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Usuário / Login</label>
            <div class="col-md-9"><?php echo form_input(["name" => "login_username", "value" => $model_info->login_username ?? "", "class" => "form-control", "autocomplete" => "off"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Senha</label>
            <div class="col-md-9">
                <?php echo form_password(["name" => "password", "class" => "form-control", "autocomplete" => "new-password", "placeholder" => $has_password ? "Deixe em branco para manter a senha atual" : "Digite a senha"]); ?>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Visibilidade</label>
            <div class="col-md-4"><?php echo form_dropdown("visibility_scope", ["team" => "Equipe", "private" => "Privado"], $scope, ["class" => "form-control select2"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Observações</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "notes", "value" => $model_info->notes ?? "", "class" => "form-control", "rows" => 2]); ?></div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="<?php echo app_lang("close"); ?>"><span data-feather="x-circle" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <button type="submit" class="btn btn-primary" title="<?php echo app_lang("save"); ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
$(document).ready(function () {
    $("#green-password-form .select2").select2();
    $("#green-password-form").appForm({
        onSuccess: function () {
            if ($("#green-vault-table").length) {
                $("#green-vault-table").appTable({reload: true});
            }
        }
    });
});
</script>
