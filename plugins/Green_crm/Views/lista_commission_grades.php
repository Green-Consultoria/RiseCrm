<?php $can_manage = $can_manage ?? false; ?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="green-inner-card">
            <div class="page-title clearfix green-crm-page-header">
                <h1>Grades de comissão</h1>
                <?php if ($can_manage): ?>
                    <div class="title-button-group green-crm-title-actions">
                        <?php echo modal_anchor(get_uri("green_crm/commission_grade_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova grade", ["class" => "btn btn-primary", "title" => "Nova grade"]); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p20 green-crm-content-panel">
                <div class="text-off mb10">Grades/tabelas de comissão por parceiro (ex.: Ramed, Serra). Cada grade tem versões com vigência; vendas congelam a versão usada.</div>
                <div class="table-responsive green-table-wrap">
                    <table id="green-grades-table" class="display green-crm-table" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $("#green-grades-table").appTable({
        source: "<?php echo_uri("green_crm/commission_grades_list_data"); ?>",
        tableRefreshButton: true,
        columns: [
            {title: "Nome"},
            {title: "Parceiro"},
            {title: "Status"},
            {title: "Versões", "class": "text-center"},
            {title: "Atualizado em"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w160"}
        ],
        onInitComplete: function () { if (window.feather) { feather.replace(); } },
        onRelaodCallback: function () { if (window.feather) { feather.replace(); } }
    });

    $("body").on("click", ".green-inactivate-grade", function () {
        if (!confirm("Inativar esta grade?")) { return; }
        appAjaxRequest({url: "<?php echo_uri("green_crm/inactivate_commission_grade"); ?>", type: "POST", dataType: "json", data: {id: $(this).data("id")}, success: function (r) { r.success ? appAlert.success(r.message) : appAlert.error(r.message); $("#green-grades-table").appTable({reload: true}); }});
    });

    if (window.feather) { feather.replace(); }
});
</script>
