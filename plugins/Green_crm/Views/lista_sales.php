<?php $embedded = $embedded ?? false; ?>

<?php if (!$embedded): ?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
<?php endif; ?>

<div class="green-inner-card">
    <div class="page-title clearfix green-crm-page-header">
        <h1>Vendas</h1>
        <div class="title-button-group green-crm-title-actions">
            <?php echo modal_anchor(get_uri("green_crm/import_modal_form"), "<i data-feather='upload' class='icon-16'></i> Importar vendidos", ["class" => "btn btn-default", "title" => "Importar Excel CRM Vendidos"]); ?>
            <?php echo modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova venda", ["class" => "btn btn-primary", "title" => "Nova venda"]); ?>
        </div>
    </div>

    <div class="p20 green-crm-content-panel">
        <div class="text-off mb10">Vendas fechadas, implantação e geração de comissões por contrato.</div>
        <div class="table-responsive green-table-wrap">
            <table id="green-sales-table" class="display green-crm-table green-table-sales" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<?php if (!$embedded): ?>
    </div>
</div>
<?php endif; ?>

<script type="text/javascript">
$(document).ready(function () {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-sales-table")) {
        return;
    }

    var reloadSalesTable = function () {
        $("#green-sales-table").appTable({reload: true});
    };

    $("#green-sales-table").appTable({
        source: "<?php echo_uri("green_crm/sales_list_data"); ?>",
        tableRefreshButton: true,
        rangeDatepicker: [{startDate: {name: "date_from", value: ""}, endDate: {name: "date_to", value: ""}, showClearButton: true, label: "Periodo", ranges: ['this_month', 'last_month', 'last_30_days', 'last_7_days']}],
        filterDropdown: [
            {name: "operator_id", class: "w240", options: <?php echo json_encode(array_map(function ($id, $text) { return ["id" => $id, "text" => $text]; }, array_keys($operators_dropdown), $operators_dropdown)); ?>},
            {name: "status", class: "w220", options: [{id:"", text:"Status"}, {id:"Vendida", text:"Vendida"}, {id:"Implantacao pendente", text:"Implantação pendente"}, {id:"Implantada", text:"Implantada"}, {id:"Cancelada", text:"Cancelada"}, {id:"Estornada", text:"Estornada"}]},
            {name: "implantation_status", class: "w240", options: [{id:"", text:"Implantação"}, {id:"nao_iniciada", text:"Não iniciada"}, {id:"pendente", text:"Pendente"}, {id:"em_andamento", text:"Em andamento"}, {id:"implantada", text:"Implantada"}, {id:"cancelada", text:"Cancelada"}]}
        ],
        columns: [
            {title: "Código", "class": "all green-col-code"},
            {title: "Cliente", "class": "all green-col-client"},
            {title: "Lead", "class": "green-col-lead"},
            {title: "Operadora", "class": "green-col-operator"},
            {title: "Plano", "class": "green-col-plan"},
            {title: "Data venda", "class": "green-col-date"},
            {title: "Valor venda", "class": "text-end green-col-money"},
            {title: "Data implantação", "class": "green-col-date"},
            {title: "Fidelidade", "class": "green-col-date"},
            {title: "Status", "class": "green-col-status"},
            {title: "Implantação", "class": "green-col-status"},
            {title: "Checklist", "class": "green-col-status"},
            {title: "Contrato", "class": "green-col-contract"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w250"}
        ],
        printColumns: [0,1,2,3,4,5,6,7,8,9,10,11,12],
        xlsColumns: [0,1,2,3,4,5,6,7,8,9,10,11,12],
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

    $("body").on("click", ".green-cancel-sale", function () {
        var id = $(this).data("id");
        $(this).appConfirmation({
            title: "Cancelar venda?",
            btnConfirmLabel: "Cancelar venda",
            onConfirm: function () {
                appAjaxRequest({
                    url: "<?php echo_uri("green_crm/cancel_sale"); ?>",
                    type: "POST",
                    dataType: "json",
                    data: {id: id},
                    success: function (result) {
                        result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                        reloadSalesTable();
                    }
                });
            }
        });
    });
    if (window.feather) {
        feather.replace();
    }
});
</script>
