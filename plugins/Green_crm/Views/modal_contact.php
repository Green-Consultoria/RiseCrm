<?php
$model_info = $model_info ?? new \stdClass();
$lead_id = (int) ($lead_id ?? 0);
$client_id = (int) ($client_id ?? 0);
?>

<?php echo form_open(get_uri("green_crm/save_contact"), ["id" => "green-contact-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
        <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">
        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

        <div class="form-group row">
            <label class="col-md-3">Nome</label>
            <div class="col-md-9"><?php echo form_input(["name" => "name", "value" => $model_info->name ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Cargo/função</label>
            <div class="col-md-9"><?php echo form_input(["name" => "role", "value" => $model_info->role ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Telefone</label>
            <div class="col-md-4"><?php echo form_input(["name" => "phone", "value" => $model_info->phone_original ?? $model_info->phone_normalized ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-1">Email</label>
            <div class="col-md-4"><?php echo form_input(["name" => "email", "value" => $model_info->email ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Principal</label>
            <div class="col-md-9">
                <?php echo form_checkbox("is_primary", "1", (int) ($model_info->is_primary ?? 0) === 1, "class='form-check-input'"); ?>
            </div>
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
    $("#green-contact-form").appForm({
        onSuccess: function () {
            location.reload();
        }
    });
});
</script>
