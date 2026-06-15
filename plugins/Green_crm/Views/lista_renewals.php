<?php
$months_dropdown = $months_dropdown ?? [];
$operators_dropdown = $operators_dropdown ?? [];
$statuses_dropdown = $statuses_dropdown ?? [];
$consultants_dropdown = $consultants_dropdown ?? [];

$temperature_dropdown = [
    "" => "Temperatura",
    "quente" => "Quente",
    "morno" => "Morno",
    "frio" => "Frio",
    "sem_classificacao" => "Sem classificação"
];

$fidelity_dropdown = [
    "30" => "Fidelidade: próximos 30 dias",
    "60" => "Fidelidade: próximos 60 dias",
    "90" => "Fidelidade: próximos 90 dias",
    "120" => "Fidelidade: próximos 120 dias",
    "180" => "Fidelidade: próximos 180 dias"
];
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix green-crm-page-header">
            <h1>Reajustes</h1>
        </div>

        <div class="p20 green-crm-content-panel">
            <div class="text-off mb15">Leads e clientes com oportunidade de contato por reajuste ou fidelidade.</div>
            <div class="row mb15">
                <div class="col-md-2 mb10">
                    <?php echo form_dropdown("renewal_month", $months_dropdown, "", ["id" => "green-renewal-month", "class" => "form-control select2 green-renewals-filter"]); ?>
                </div>
                <div class="col-md-2 mb10">
                    <?php echo form_dropdown("operator_id", $operators_dropdown, "", ["id" => "green-renewal-operator", "class" => "form-control select2 green-renewals-filter"]); ?>
                </div>
                <div class="col-md-2 mb10">
                    <?php echo form_dropdown("status_id", $statuses_dropdown, "", ["id" => "green-renewal-status", "class" => "form-control select2 green-renewals-filter"]); ?>
                </div>
                <div class="col-md-2 mb10">
                    <?php echo form_dropdown("temperature", $temperature_dropdown, "", ["id" => "green-renewal-temperature", "class" => "form-control select2 green-renewals-filter"]); ?>
                </div>
                <div class="col-md-2 mb10">
                    <?php echo form_dropdown("consultant_id", $consultants_dropdown, "", ["id" => "green-renewal-consultant", "class" => "form-control select2 green-renewals-filter"]); ?>
                </div>
                <div class="col-md-2 mb10">
                    <?php echo form_dropdown("fidelity_days", $fidelity_dropdown, "90", ["id" => "green-renewal-fidelity-days", "class" => "form-control select2 green-renewals-filter"]); ?>
                </div>
            </div>

            <div class="row mb15">
                <div class="col-md-3 mb10">
                    <label class="d-block">
                        <?php echo form_checkbox("only_without_future_task", "1", false, "id='green-renewal-no-future-task' class='green-renewals-filter'"); ?>
                        Somente sem tarefa futura
                    </label>
                </div>
                <div class="col-md-4 mb10">
                    <label class="d-block">
                        <?php echo form_checkbox("only_without_recent_contact", "1", false, "id='green-renewal-no-recent-contact' class='green-renewals-filter'"); ?>
                        Somente sem contato recente
                    </label>
                </div>
                <div class="col-md-2 mb10">
                    <?php echo form_input(["id" => "green-renewal-inactive-days", "class" => "form-control green-renewals-filter", "value" => "30", "type" => "number", "min" => "1", "max" => "365", "placeholder" => "Dias sem contato"]); ?>
                </div>
            </div>

            <div class="table-responsive green-table-wrap">
                <table id="green-renewals-table" class="display green-crm-table green-table-renewals" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var renewalFilters = function () {
        return {
            renewal_month: $("#green-renewal-month").val(),
            operator_id: $("#green-renewal-operator").val(),
            status_id: $("#green-renewal-status").val(),
            temperature: $("#green-renewal-temperature").val(),
            consultant_id: $("#green-renewal-consultant").val(),
            fidelity_days: $("#green-renewal-fidelity-days").val(),
            only_without_future_task: $("#green-renewal-no-future-task").is(":checked") ? 1 : 0,
            only_without_recent_contact: $("#green-renewal-no-recent-contact").is(":checked") ? 1 : 0,
            inactive_days: $("#green-renewal-inactive-days").val()
        };
    };

    var reloadRenewalsTable = function () {
        $("#green-renewals-table").appTable({reload: true, filterParams: renewalFilters()});
    };

    $(".select2").select2();

    $("#green-renewals-table").appTable({
        source: "<?php echo_uri("green_crm/renewals_list_data"); ?>",
        tableRefreshButton: true,
        filterParams: renewalFilters(),
        columns: [
            {title: "Cliente", "class": "all green-col-client"},
            {title: "Telefone", "class": "green-col-phone"},
            {title: "Lead", "class": "green-col-lead"},
            {title: "Operadora atual", "class": "green-col-operator"},
            {title: "Plano atual", "class": "green-col-plan"},
            {title: "Valor pago atual", "class": "text-end green-col-money"},
            {title: "Valor proposta", "class": "text-end green-col-money"},
            {title: "Mês reajuste", "class": "text-center green-col-small"},
            {title: "Fidelidade até", "class": "green-col-date"},
            {title: "Temperatura", "class": "green-col-temperature"},
            {title: "Status", "class": "green-col-status"},
            {title: "Último contato", "class": "green-col-date"},
            {title: "Próximo follow-up", "class": "green-col-date"},
            {title: "Responsável", "class": "green-col-client"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w250"}
        ],
        printColumns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13],
        xlsColumns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13],
        onInitComplete: function () {
            if (window.feather) {
                feather.replace();
            }
        },
        onRelaodCallback: function () {
            if (window.feather) {
                feather.replace();
            }
        }
    });

    $(".green-renewals-filter").on("change keyup", delayAction(function () {
        reloadRenewalsTable();
    }, 300));

    $("body").on("click", ".green-create-renewal-followup", function () {
        var leadId = $(this).data("lead-id");
        var defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 7);
        var day = String(defaultDate.getDate()).padStart(2, "0");
        var month = String(defaultDate.getMonth() + 1).padStart(2, "0");
        var year = defaultDate.getFullYear();
        var dueAt = prompt("Data do follow-up de reajuste (dd/mm/aaaa)", day + "/" + month + "/" + year);

        if (!dueAt) {
            return false;
        }

        appAjaxRequest({
            url: "<?php echo_uri("green_crm/create_renewal_followup"); ?>",
            type: "POST",
            dataType: "json",
            data: {lead_id: leadId, due_at: dueAt},
            success: function (result) {
                if (result.success) {
                    appAlert.success(result.message);
                    reloadRenewalsTable();
                } else {
                    appAlert.error(result.message);
                }
            }
        });

        if (window.feather) {
            feather.replace();
        }

        return false;
    });
});
</script>
