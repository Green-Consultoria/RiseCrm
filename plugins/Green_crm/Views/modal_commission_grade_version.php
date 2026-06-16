<?php
$format_date = function ($value) {
    return $value && $value !== "0000-00-00" ? date("Y-m-d", strtotime($value)) : "";
};
?>
<?php echo form_open(get_uri("green_crm/save_commission_version"), ["id" => "green-version-form", "class" => "general-form"]); ?>
<div class="modal-body clearfix"><div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
    <input type="hidden" name="grade_id" value="<?php echo (int) ($model_info->grade_id ?? 0); ?>">
    <div class="form-group row"><label class="col-md-3">Nome da versão</label><div class="col-md-9"><?php echo form_input(["name" => "version_name", "value" => $model_info->version_name ?? "", "class" => "form-control", "data-rule-required" => true, "placeholder" => "Ex.: Junho 2026 - 03062026"]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Vigência início</label><div class="col-md-9"><?php echo form_input(["name" => "valid_from", "value" => $format_date($model_info->valid_from ?? ""), "class" => "form-control", "type" => "date"]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Vigência fim</label><div class="col-md-9"><?php echo form_input(["name" => "valid_until", "value" => $format_date($model_info->valid_until ?? ""), "class" => "form-control", "type" => "date"]); ?><small class="text-muted">Deixe em branco se ainda vigente.</small></div></div>
    <div class="form-group row"><label class="col-md-3">Arquivo/origem</label><div class="col-md-9"><?php echo form_input(["name" => "source_file_name", "value" => $model_info->source_file_name ?? "", "class" => "form-control", "placeholder" => "Nome do PDF/planilha de origem"]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Observações</label><div class="col-md-9"><?php echo form_textarea(["name" => "notes", "value" => $model_info->notes ?? "", "class" => "form-control", "rows" => 2]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Status</label><div class="col-md-9"><?php echo form_dropdown("status", ["Ativa" => "Ativa", "Inativa" => "Inativa"], $model_info->status ?? "Ativa", ["class" => "form-control"]); ?></div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x-circle" class="icon-16"></span> Fechar</button><button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Salvar</button></div>
<?php echo form_close(); ?>
<script>$(document).ready(function () { $("#green-version-form").appForm({onSuccess: function () { if ($("#green-versions-table").length) { $("#green-versions-table").appTable({reload: true}); } }}); });</script>
