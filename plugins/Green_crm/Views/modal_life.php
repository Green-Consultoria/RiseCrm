<?php
$model_info = $model_info ?? new \stdClass();
$lead_id = (int) ($lead_id ?? 0);
$birth_date = !empty($model_info->birth_date) && $model_info->birth_date !== "0000-00-00" ? $model_info->birth_date : "";
?>

<?php echo form_open(get_uri("green_crm/save_life"), ["id" => "green-life-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
        <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">

        <div class="form-group row">
            <label class="col-md-3">Nome</label>
            <div class="col-md-9"><?php echo form_input(["name" => "name", "value" => $model_info->name ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Idade</label>
            <div class="col-md-3"><?php echo form_input(["name" => "age", "type" => "number", "value" => $model_info->age ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-3">Nascimento</label>
            <div class="col-md-3"><?php echo form_input(["name" => "birth_date", "type" => "date", "value" => $birth_date, "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Parentesco</label>
            <div class="col-md-9"><?php echo form_input(["name" => "relationship", "value" => $model_info->relationship ?? "", "class" => "form-control"]); ?></div>
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
    $("#green-life-form").appForm({
        onSuccess: function () {
            location.reload();
        }
    });
});
</script>
