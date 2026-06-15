<?php
$embedded = $embedded ?? false;
$operators_dropdown = $operators_dropdown ?? ["" => "-"];
$consultants_dropdown = $consultants_dropdown ?? ["" => "-"];

$operator_options = array_map(function ($id, $text) {
    return ["id" => (string) $id, "text" => $text];
}, array_keys($operators_dropdown), $operators_dropdown);

$consultant_options = array_map(function ($id, $text) {
    return ["id" => (string) $id, "text" => $text];
}, array_keys($consultants_dropdown), $consultants_dropdown);
?>

<?php if (!$embedded): ?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
<?php endif; ?>

<div class="green-inner-card">
    <div class="page-title clearfix green-crm-page-header"><h1>Comissões</h1></div>
    <div class="p20 green-crm-content-panel">
        <div class="row green-commission-summary mb15">
            <?php
            $summary_cards = [
                ["key" => "expected_amount_total", "label" => "Comissão prevista", "class" => "bg-primary", "icon" => "dollar-sign"],
                ["key" => "received_amount_total", "label" => "Comissão recebida", "class" => "bg-success", "icon" => "check-square"],
                ["key" => "open_amount_total", "label" => "Comissão em aberto", "class" => "bg-warning", "icon" => "clock"],
                ["key" => "overdue_amount_total", "label" => "Comissão vencida", "class" => "bg-danger", "icon" => "alert-triangle"],
                ["key" => "difference_amount_total", "label" => "Diferença esperada x recebida", "class" => "bg-info", "icon" => "activity"],
                ["key" => "bonus_expected_total", "label" => "Bônus previsto", "class" => "bg-primary", "icon" => "gift"],
                ["key" => "reversal_amount_total", "label" => "Estornos", "class" => "bg-danger", "icon" => "rotate-ccw"]
            ];
            foreach ($summary_cards as $card):
            ?>
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <div class="card dashboard-icon-widget green-crm-kpi-widget">
                        <div class="card-body green-crm-kpi-content">
                            <div class="widget-icon <?php echo $card["class"]; ?>"><i data-feather="<?php echo $card["icon"]; ?>" class="icon"></i></div>
                            <div class="widget-details">
                                <h1 data-green-commission-summary="<?php echo $card["key"]; ?>">R$ 0,00</h1>
                                <span><?php echo esc($card["label"]); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row mb15">
            <div class="col-md-3 mb10">
                <?php echo form_input(["id" => "green-commission-sale-code", "class" => "form-control green-commission-filter", "placeholder" => "Venda"]); ?>
            </div>
            <div class="col-md-3 mb10">
                <?php echo form_input(["id" => "green-commission-client", "class" => "form-control green-commission-filter", "placeholder" => "Cliente"]); ?>
            </div>
            <div class="col-md-3 mb10">
                <label>
                    <?php echo form_checkbox("only_overdue", "1", false, "id='green-commission-only-overdue' class='green-commission-filter'"); ?>
                    Somente vencidas
                </label>
            </div>
            <div class="col-md-3 mb10">
                <label>
                    <?php echo form_checkbox("only_divergent", "1", false, "id='green-commission-only-divergent' class='green-commission-filter'"); ?>
                    Somente divergentes
                </label>
            </div>
        </div>

        <div class="table-responsive green-table-wrap">
            <table id="green-commissions-table" class="display green-crm-table green-table-commissions" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<?php if (!$embedded): ?>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function(){
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-commissions-table")) {
        return;
    }

    var money = function (value) {
        var number = parseFloat(value || 0);
        return "R$ " + number.toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    };

    var commissionFilters = function () {
        return {
            sale_code: $("#green-commission-sale-code").val(),
            client_search: $("#green-commission-client").val(),
            only_overdue: $("#green-commission-only-overdue").is(":checked") ? 1 : 0,
            only_divergent: $("#green-commission-only-divergent").is(":checked") ? 1 : 0
        };
    };

    var reloadCommissionSummary = function (params) {
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/commissions_summary_data"); ?>",
            type: "POST",
            dataType: "json",
            data: params || $("#green-commissions-table").data("last_filter_params") || commissionFilters(),
            success: function (result) {
                if (!result.success) {
                    return;
                }
                $.each(result.data, function (key, value) {
                    $("[data-green-commission-summary='" + key + "']").text(money(value));
                });
            }
        });
    };

    var reloadCommissionsTable = function () {
        var params = commissionFilters();
        $("#green-commissions-table").data("last_filter_params", params);
        $("#green-commissions-table").appTable({reload: true, filterParams: params});
        reloadCommissionSummary();
    };

    $("#green-commissions-table").appTable({
        source: "<?php echo_uri("green_crm/commissions_list_data"); ?>",
        tableRefreshButton: true,
        filterParams: commissionFilters(),
        filterDropdown: [
            {name:"due_month", class:"w220", options:[{id:"", text:"Competência"}, {id:"1", text:"Janeiro"}, {id:"2", text:"Fevereiro"}, {id:"3", text:"Março"}, {id:"4", text:"Abril"}, {id:"5", text:"Maio"}, {id:"6", text:"Junho"}, {id:"7", text:"Julho"}, {id:"8", text:"Agosto"}, {id:"9", text:"Setembro"}, {id:"10", text:"Outubro"}, {id:"11", text:"Novembro"}, {id:"12", text:"Dezembro"}]},
            {name:"due_year", class:"w160", options:<?php $years = [["id" => "", "text" => "Ano"]]; for ($year = (int) date("Y") - 2; $year <= (int) date("Y") + 3; $year++) { $years[] = ["id" => (string) $year, "text" => (string) $year]; } echo json_encode($years); ?>},
            {name:"status", class:"w220", options:[{id:"", text:"Status"}, {id:"Previsto", text:"Previsto"}, {id:"A receber", text:"A receber"}, {id:"Recebido", text:"Recebido"}, {id:"Parcial", text:"Parcial"}, {id:"Cancelado", text:"Cancelado"}, {id:"Estornado", text:"Estornado"}]},
            {name:"commission_type", class:"w220", options:[{id:"", text:"Tipo"}, {id:"comissao", text:"Comissão"}, {id:"bonus", text:"Bônus"}, {id:"ajuste", text:"Ajuste"}, {id:"estorno", text:"Estorno"}]},
            {name:"operator_id", class:"w240", options:<?php echo json_encode($operator_options); ?>},
            {name:"consultant_id", class:"w240", options:<?php echo json_encode($consultant_options); ?>}
        ],
        columns: [
            {title:"Competência", "class":"all green-col-date"},
            {title:"Cliente", "class":"green-col-client"},
            {title:"Venda", "class":"green-col-code"},
            {title:"Operadora", "class":"green-col-operator"},
            {title:"Consultor", "class":"green-col-client"},
            {title:"Plano", "class":"green-col-plan"},
            {title:"Valor venda", "class":"text-end green-col-money"},
            {title:"Tipo", "class":"green-col-status"},
            {title:"Parcela", "class":"text-center green-col-small"},
            {title:"%", "class":"text-end green-col-small"},
            {title:"Esperado", "class":"text-end green-col-money"},
            {title:"Recebido", "class":"text-end green-col-money"},
            {title:"Diferença", "class":"text-end green-col-money"},
            {title:"Status", "class":"green-col-status"},
            {title:"Pagamento", "class":"green-col-date"},
            {title:"<i data-feather='menu' class='icon-16'></i>", "class":"all text-center option w120"}
        ],
        onInitComplete: function () {
            if (window.feather) {
                feather.replace();
            }
            reloadCommissionSummary($("#green-commissions-table").data("last_filter_params") || commissionFilters());
        },
        onRelaodCallback: function (table, params) {
            if (window.feather) {
                feather.replace();
            }
            $("#green-commissions-table").data("last_filter_params", params || commissionFilters());
            reloadCommissionSummary(params);
        }
    });

    $(".green-commission-filter").on("change keyup", delayAction(function () {
        reloadCommissionsTable();
    }, 300));

    $("body").on("click", ".green-cancel-commission", function(){
        appAjaxRequest({url:"<?php echo_uri("green_crm/cancel_commission"); ?>", type:"POST", dataType:"json", data:{id:$(this).data("id")}, success:function(r){r.success?appAlert.success(r.message):appAlert.error(r.message); reloadCommissionsTable();}});
    });
    if (window.feather) {
        feather.replace();
    }
});
</script>
