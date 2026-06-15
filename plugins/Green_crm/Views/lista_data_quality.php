<?php
$summary = $summary ?? (object) [];
$pending_days = (int) ($pending_days ?? 30);
$cards = [
    ["label" => "Clientes incompletos", "value" => (int) ($summary->clients ?? 0), "class" => "bg-info", "icon" => "users"],
    ["label" => "Leads incompletos", "value" => (int) ($summary->leads ?? 0), "class" => "bg-primary", "icon" => "target"],
    ["label" => "Vendas com pendencia", "value" => (int) ($summary->sales ?? 0), "class" => "bg-warning", "icon" => "shopping-cart"],
    ["label" => "Comissoes divergentes", "value" => (int) ($summary->commissions ?? 0), "class" => "bg-danger", "icon" => "dollar-sign"]
];
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix green-crm-page-header">
            <h1>Dados Pendentes</h1>
        </div>

        <div class="p20 green-crm-content-panel">
            <div class="row">
                <?php foreach ($cards as $card): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card dashboard-icon-widget green-crm-kpi-widget">
                            <div class="card-body green-crm-kpi-content">
                                <div class="widget-icon <?php echo $card["class"]; ?>">
                                    <i data-feather="<?php echo $card["icon"]; ?>" class="icon"></i>
                                </div>
                                <div class="widget-details">
                                    <h1><?php echo (int) $card["value"]; ?></h1>
                                    <span><?php echo esc($card["label"]); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-off mb15">
                Implantacao pendente antiga: mais de <?php echo $pending_days; ?> dias.
            </div>

            <div class="table-responsive green-table-wrap">
                <table id="green-data-quality-table" class="display green-crm-table green-table-data-quality" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $("#green-data-quality-table").appTable({
        source: "<?php echo_uri("green_crm/data_quality_list_data"); ?>",
        tableRefreshButton: true,
        filterParams: {
            pending_days: <?php echo $pending_days; ?>
        },
        columns: [
            {title: "Tipo", "class": "all green-col-status"},
            {title: "Registro", "class": "all green-col-code"},
            {title: "Cliente", "class": "all green-col-client"},
            {title: "Problema", "class": "green-col-note"},
            {title: "Gravidade", "class": "green-col-status"},
            {title: "Acao sugerida", "class": "green-col-note"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w120"}
        ],
        printColumns: [0,1,2,3,4,5],
        xlsColumns: [0,1,2,3,4,5],
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

    if (window.feather) {
        feather.replace();
    }
});
</script>
