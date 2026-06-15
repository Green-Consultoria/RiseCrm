<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Anúncios — ROI por venda</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri("green_crm/ad_campaigns"), "Campanhas", ["class" => "btn btn-default"]); ?>
                <?php echo anchor(get_uri("green_crm/ads"), "Criativos", ["class" => "btn btn-default"]); ?>
            </div>
        </div>
        <div class="p20">
            <div class="text-off mb10">Vendas cujo lead veio de campanha/anúncio rastreado, com a comissão prevista somada. Base para o ROI consolidado da próxima etapa.</div>
            <div class="table-responsive">
                <table id="green-roi-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-roi-table")) return;
    $("#green-roi-table").appTable({
        source: "<?php echo_uri("green_crm/roi_list_data"); ?>",
        order: [[8, "desc"]],
        tableRefreshButton: true,
        columns: [
            {title: "Venda", "class": "all"},
            {title: "Cliente"},
            {title: "Operadora"},
            {title: "Plano"},
            {title: "Campanha (ID)"},
            {title: "Anúncio (ID)"},
            {title: "Valor venda", "class": "text-end"},
            {title: "Comissão prevista", "class": "text-end"},
            {title: "Data"}
        ],
        onInitComplete: function () { if (window.feather) feather.replace(); }
    });
    if (window.feather) feather.replace();
});
</script>
