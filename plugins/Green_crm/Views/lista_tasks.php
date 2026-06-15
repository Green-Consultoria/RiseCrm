<?php
$members_dropdown = $members_dropdown ?? ["" => "-"];
$status_options = ["" => "Status", "aberta" => "Aberta", "em_andamento" => "Em andamento", "concluida" => "Concluída", "cancelada" => "Cancelada"];
$priority_options = ["" => "Prioridade", "baixa" => "Baixa", "media" => "Média", "alta" => "Alta", "urgente" => "Urgente"];
$due_options = ["" => "Vencimento", "overdue" => "Vencidas", "today" => "Para hoje", "upcoming" => "Próximas"];
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Tarefas e lembretes</h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("green_crm/task_general_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova tarefa", ["class" => "btn btn-primary", "title" => "Nova tarefa"]); ?>
            </div>
        </div>

        <div class="p20">
            <div class="row mb15">
                <div class="col-md-2 col-sm-6 mb10"><label>Responsável</label><?php echo form_dropdown("responsible_id", $members_dropdown, "", ["class" => "form-control", "id" => "f-responsible"]); ?></div>
                <div class="col-md-2 col-sm-6 mb10"><label>Status</label><?php echo form_dropdown("status", $status_options, "", ["class" => "form-control", "id" => "f-status"]); ?></div>
                <div class="col-md-2 col-sm-6 mb10"><label>Prioridade</label><?php echo form_dropdown("priority", $priority_options, "", ["class" => "form-control", "id" => "f-priority"]); ?></div>
                <div class="col-md-2 col-sm-6 mb10"><label>Vencimento</label><?php echo form_dropdown("due_filter", $due_options, "", ["class" => "form-control", "id" => "f-due"]); ?></div>
                <div class="col-md-2 col-sm-6 mb10"><label>Busca</label><?php echo form_input(["name" => "search", "class" => "form-control", "id" => "f-search"]); ?></div>
                <div class="col-md-2 col-sm-6 mb10 d-flex align-items-end">
                    <button type="button" class="btn btn-primary me-1" id="green-tasks-apply" title="Aplicar"><i data-feather="search" class="icon-16"></i></button>
                    <button type="button" class="btn btn-default" id="green-tasks-clear" title="Limpar"><i data-feather="x-circle" class="icon-16"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="green-tasks-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-tasks-table")) {
            return;
        }

        function greenTasksFilters() {
            return {
                responsible_id: $("#f-responsible").val(),
                status: $("#f-status").val(),
                priority: $("#f-priority").val(),
                due_filter: $("#f-due").val(),
                search: $("#f-search").val()
            };
        }

        function greenTasksApply() {
            var params = greenTasksFilters();
            if (window.InstanceCollection && window.InstanceCollection["green-tasks-table"]) {
                window.InstanceCollection["green-tasks-table"].filterParams = params;
            }
            $("#green-tasks-table").appTable({reload: true, filterParams: params});
        }

        $("#green-tasks-table").appTable({
            source: "<?php echo_uri("green_crm/tasks_list_data"); ?>",
            order: [[6, "asc"]],
            tableRefreshButton: true,
            columns: [
                {title: "Título", "class": "all"},
                {title: "Cliente"},
                {title: "Lead"},
                {title: "Prioridade", "class": "text-center"},
                {title: "Status", "class": "text-center"},
                {title: "Responsável"},
                {title: "Vencimento"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w120"}
            ],
            printColumns: [0,1,2,3,4,5,6],
            onInitComplete: function () { if (window.feather) feather.replace(); },
            onRelaodCallback: function () { if (window.feather) feather.replace(); }
        });

        $("#green-tasks-apply").on("click", greenTasksApply);
        $("#f-search").on("keydown", function (e) { if (e.which === 13) { e.preventDefault(); greenTasksApply(); } });
        $("#green-tasks-clear").on("click", function () {
            $("#f-responsible,#f-status,#f-priority,#f-due").prop("selectedIndex", 0);
            $("#f-search").val("");
            greenTasksApply();
        });

        $("body").on("click", ".green-task-complete", function () {
            var id = $(this).data("id");
            appAjaxRequest({
                url: "<?php echo_uri("green_crm/update_task_status"); ?>",
                type: "POST", dataType: "json", data: {id: id, status: "concluida"},
                success: function (result) {
                    result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                    $("#green-tasks-table").appTable({reload: true});
                }
            });
        });

        $("body").on("click", ".green-task-delete", function () {
            var id = $(this).data("id");
            $(this).appConfirmation({
                title: "Excluir tarefa?",
                btnConfirmLabel: "Excluir",
                onConfirm: function () {
                    appAjaxRequest({
                        url: "<?php echo_uri("green_crm/delete_task"); ?>",
                        type: "POST", dataType: "json", data: {id: id},
                        success: function (result) {
                            result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                            $("#green-tasks-table").appTable({reload: true});
                        }
                    });
                }
            });
        });

        if (window.feather) feather.replace();
    });
</script>
