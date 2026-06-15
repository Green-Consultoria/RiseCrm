<?php
$status_options = ["" => "Status", "active" => "Ativo", "paused" => "Pausado", "archived" => "Arquivado", "unknown" => "—"];
?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Anúncios — Campanhas</h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("green_crm/ad_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova campanha", ["class" => "btn btn-primary", "title" => "Nova campanha", "data-post-type" => "campaign"]); ?>
                <?php echo anchor(get_uri("green_crm/ad_sets"), "Conjuntos", ["class" => "btn btn-default"]); ?>
                <?php echo anchor(get_uri("green_crm/ads"), "Criativos", ["class" => "btn btn-default"]); ?>
                <?php echo anchor(get_uri("green_crm/roi"), "ROI por venda", ["class" => "btn btn-default"]); ?>
            </div>
        </div>
        <div class="p20">
            <div class="row mb15">
                <div class="col-md-3 col-sm-6 mb10"><label>Status</label><?php echo form_dropdown("status", $status_options, "", ["class" => "form-control", "id" => "f-status"]); ?></div>
                <div class="col-md-4 col-sm-8 mb10"><label>Busca</label><?php echo form_input(["name" => "search", "class" => "form-control", "id" => "f-search"]); ?></div>
                <div class="col-md-2 col-sm-4 mb10 d-flex align-items-end">
                    <button type="button" class="btn btn-primary me-1" id="green-apply" title="Aplicar"><i data-feather="search" class="icon-16"></i></button>
                    <button type="button" class="btn btn-default" id="green-clear" title="Limpar"><i data-feather="x-circle" class="icon-16"></i></button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="green-campaigns-table" class="display green-ad-table" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-campaigns-table")) return;
    function filters() { return {status: $("#f-status").val(), search: $("#f-search").val()}; }
    function apply() {
        var p = filters();
        if (window.InstanceCollection && window.InstanceCollection["green-campaigns-table"]) window.InstanceCollection["green-campaigns-table"].filterParams = p;
        $("#green-campaigns-table").appTable({reload: true, filterParams: p});
    }
    $("#green-campaigns-table").appTable({
        source: "<?php echo_uri("green_crm/campaigns_list_data"); ?>",
        order: [[0, "asc"]],
        tableRefreshButton: true,
        columns: [
            {title: "Campanha", "class": "all"},
            {title: "ID externo"},
            {title: "Status", "class": "text-center"},
            {title: "Investimento", "class": "text-end"},
            {title: "Leads", "class": "text-center"},
            {title: "CPL", "class": "text-end"},
            {title: "Vendas", "class": "text-center"},
            {title: "Comissão prevista", "class": "text-end"},
            {title: "ROI", "class": "text-center"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w100"}
        ],
        onInitComplete: function () { if (window.feather) feather.replace(); },
        onRelaodCallback: function () { if (window.feather) feather.replace(); }
    });
    $("#green-apply").on("click", apply);
    $("#f-search").on("keydown", function (e) { if (e.which === 13) { e.preventDefault(); apply(); } });
    $("#green-clear").on("click", function () { $("#f-status").prop("selectedIndex", 0); $("#f-search").val(""); apply(); });
    $("body").on("click", ".green-ad-delete", function () {
        var id = $(this).data("id"), type = $(this).data("type");
        $(this).appConfirmation({title: "Excluir registro?", btnConfirmLabel: "Excluir", onConfirm: function () {
            appAjaxRequest({url: "<?php echo_uri("green_crm/delete_ad"); ?>", type: "POST", dataType: "json", data: {id: id, type: type}, success: function (r) {
                r.success ? appAlert.success(r.message) : appAlert.error(r.message);
                $("#green-campaigns-table").appTable({reload: true});
            }});
        }});
    });
    if (window.feather) feather.replace();
});
</script>
