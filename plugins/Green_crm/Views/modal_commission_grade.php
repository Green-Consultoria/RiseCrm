<?php echo form_open(get_uri("green_crm/save_commission_grade"), ["id" => "green-grade-form", "class" => "general-form"]); ?>
<div class="modal-body clearfix"><div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
    <div class="form-group row"><label class="col-md-3">Nome</label><div class="col-md-9"><?php echo form_input(["name" => "name", "value" => $model_info->name ?? "", "class" => "form-control", "data-rule-required" => true]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Parceiro</label><div class="col-md-9"><?php echo form_input(["name" => "partner_name", "value" => $model_info->partner_name ?? "", "class" => "form-control", "placeholder" => "Ex.: Ramed, Serra"]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Descrição</label><div class="col-md-9"><?php echo form_textarea(["name" => "description", "value" => $model_info->description ?? "", "class" => "form-control", "rows" => 2]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Status</label><div class="col-md-9"><?php echo form_dropdown("status", ["Ativa" => "Ativa", "Inativa" => "Inativa"], $model_info->status ?? "Ativa", ["class" => "form-control"]); ?></div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x-circle" class="icon-16"></span> Fechar</button><button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Salvar</button></div>
<?php echo form_close(); ?>
<script>$(document).ready(function () { $("#green-grade-form").appForm({onSuccess: function () { if ($("#green-grades-table").length) { $("#green-grades-table").appTable({reload: true}); } }}); });</script>
