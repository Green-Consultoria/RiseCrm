<?php $lead_id = (int) ($lead_id ?? 0); ?>

<?php echo form_open(get_uri("green_crm/save_interaction"), ["id" => "green-interaction-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">
        <div class="form-group row">
            <label class="col-md-3">Tipo</label>
            <div class="col-md-9"><?php echo form_input(["name" => "interaction_type", "value" => "manual", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Assunto</label>
            <div class="col-md-9"><?php echo form_input(["name" => "subject", "class" => "form-control", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Descricao</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "description", "class" => "form-control", "rows" => 4]); ?></div>
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
    $("#green-interaction-form").appForm({
        onSuccess: function () {
            location.reload();
        }
    });
});
</script>
