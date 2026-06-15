<?php
$model_info = $model_info ?? new \stdClass();
$task_id = (int) ($model_info->id ?? 0);
$lead_id = (int) ($lead_id ?? ($model_info->lead_id ?? 0));
$members_dropdown = $members_dropdown ?? [];
$current_user_id = (int) ($current_user_id ?? 0);
$form_action = $form_action ?? "green_crm/save_task";
$leads_dropdown = $leads_dropdown ?? null; // se fornecido, mostra seletor de lead
$reload_on_success = $reload_on_success ?? true;

$priorities = ["baixa" => "Baixa", "media" => "Média", "alta" => "Alta", "urgente" => "Urgente"];
$statuses = ["aberta" => "Aberta", "em_andamento" => "Em andamento", "concluida" => "Concluída", "cancelada" => "Cancelada"];

$selected_priority = $model_info->priority ?? "media";
$selected_status = $model_info->status ?? "aberta";
$selected_responsible = (int) ($model_info->responsible_id ?? $current_user_id);
$due_date_value = "";
if (!empty($model_info->due_date) && $model_info->due_date !== "0000-00-00 00:00:00") {
    $due_date_value = date("Y-m-d\TH:i", strtotime($model_info->due_date));
}
?>

<?php echo form_open(get_uri($form_action), ["id" => "green-task-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $task_id; ?>">
        <?php if ($leads_dropdown !== null) { ?>
            <div class="form-group row">
                <label class="col-md-3">Lead</label>
                <div class="col-md-9"><?php echo form_dropdown("lead_id", $leads_dropdown, $lead_id, ["class" => "form-control select2", "id" => "green-task-lead"]); ?></div>
            </div>
        <?php } else { ?>
            <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">
        <?php } ?>
        <div class="form-group row">
            <label class="col-md-3">Título</label>
            <div class="col-md-9"><?php echo form_input(["name" => "title", "value" => $model_info->title ?? "", "class" => "form-control", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Descrição</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "description", "value" => $model_info->description ?? "", "class" => "form-control", "rows" => 2]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Vencimento</label>
            <div class="col-md-3"><?php echo form_input(["name" => "due_date", "value" => $due_date_value, "type" => "datetime-local", "class" => "form-control"]); ?></div>
            <label class="col-md-2">Prioridade</label>
            <div class="col-md-4"><?php echo form_dropdown("priority", $priorities, $selected_priority, ["class" => "form-control select2"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Responsável</label>
            <div class="col-md-4"><?php echo form_dropdown("responsible_id", $members_dropdown, $selected_responsible, ["class" => "form-control select2"]); ?></div>
            <?php if ($task_id) { ?>
                <label class="col-md-2">Status</label>
                <div class="col-md-3"><?php echo form_dropdown("status", $statuses, $selected_status, ["class" => "form-control select2"]); ?></div>
            <?php } ?>
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
    $("#green-task-form .select2").select2();
    $("#green-task-form").appForm({
        onSuccess: function (result) {
            <?php if ($reload_on_success) { ?>
            location.reload();
            <?php } else { ?>
            if (typeof $.fn.appTable !== "undefined" && $("#green-tasks-table").length) {
                $("#green-tasks-table").appTable({ reload: true });
            } else {
                location.reload();
            }
            <?php } ?>
        }
    });
});
</script>
