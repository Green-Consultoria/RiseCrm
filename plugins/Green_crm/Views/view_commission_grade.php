<?php
$can_manage = $can_manage ?? false;
$grade = $grade ?? null;
?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="green-inner-card">
            <div class="page-title clearfix green-crm-page-header">
                <h1>
                    <a href="<?php echo get_uri("green_crm/commission_grades"); ?>" class="text-muted"><i data-feather="arrow-left" class="icon-16"></i></a>
                    Grade: <?php echo esc($grade->name ?? "?"); ?>
                    <?php if ($grade && $grade->partner_name): ?><small class="text-muted">(<?php echo esc($grade->partner_name); ?>)</small><?php endif; ?>
                </h1>
                <?php if ($can_manage && $grade): ?>
                    <div class="title-button-group green-crm-title-actions">
                        <?php echo modal_anchor(get_uri("green_crm/commission_version_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova versão", ["class" => "btn btn-primary", "title" => "Nova versão", "data-post-grade_id" => $grade->id]); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p20 green-crm-content-panel">
                <?php if (!$grade): ?>
                    <div class="alert alert-warning">Grade não encontrada.</div>
                <?php else: ?>
                    <div class="text-off mb10">Versões da grade. Cada venda usa a versão vigente na data da venda e a congela; alterar/criar versões não muda vendas antigas.</div>
                    <div class="table-responsive green-table-wrap">
                        <table id="green-versions-table" class="display green-crm-table" cellspacing="0" width="100%"></table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($grade): ?>
<script>
$(document).ready(function () {
    var gradeId = <?php echo (int) $grade->id; ?>;
    $("#green-versions-table").appTable({
        source: "<?php echo_uri("green_crm/commission_versions_list_data"); ?>",
        filterParams: {grade_id: gradeId},
        tableRefreshButton: true,
        columns: [
            {title: "Versão"},
            {title: "Vigência início"},
            {title: "Vigência fim"},
            {title: "Status"},
            {title: "Regras", "class": "text-center"},
            {title: "Vendas", "class": "text-center"},
            {title: "Arquivo/origem"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w200"}
        ],
        onInitComplete: function () { if (window.feather) { feather.replace(); } },
        onRelaodCallback: function () { if (window.feather) { feather.replace(); } }
    });

    $("body").on("click", ".green-duplicate-version", function () {
        var name = prompt("Nome da nova versão (cópia):");
        if (name === null || name.trim() === "") { return; }
        appAjaxRequest({url: "<?php echo_uri("green_crm/duplicate_commission_version"); ?>", type: "POST", dataType: "json", data: {id: $(this).data("id"), version_name: name}, success: function (r) { r.success ? appAlert.success(r.message) : appAlert.error(r.message); $("#green-versions-table").appTable({reload: true}); }});
    });

    $("body").on("click", ".green-inactivate-version", function () {
        if (!confirm("Inativar esta versão?")) { return; }
        appAjaxRequest({url: "<?php echo_uri("green_crm/inactivate_commission_version"); ?>", type: "POST", dataType: "json", data: {id: $(this).data("id")}, success: function (r) { r.success ? appAlert.success(r.message) : appAlert.error(r.message); $("#green-versions-table").appTable({reload: true}); }});
    });

    if (window.feather) { feather.replace(); }
});
</script>
<?php endif; ?>
