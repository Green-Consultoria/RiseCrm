<?php
$can_manage = $can_manage ?? false;
$version = $version ?? null;
?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="green-inner-card">
            <div class="page-title clearfix green-crm-page-header">
                <h1>
                    <?php if ($version): ?>
                        <a href="<?php echo get_uri("green_crm/commission_grade/" . (int) $version->grade_id); ?>" class="text-muted"><i data-feather="arrow-left" class="icon-16"></i></a>
                    <?php endif; ?>
                    Regras: <?php echo esc($version->grade_name ?? "?"); ?> / <?php echo esc($version->version_name ?? "?"); ?>
                </h1>
                <?php if ($can_manage && $version): ?>
                    <div class="title-button-group green-crm-title-actions">
                        <?php echo modal_anchor(get_uri("green_crm/commission_rule_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova regra", ["class" => "btn btn-primary", "title" => "Nova regra", "data-post-grade_version_id" => $version->id]); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p20 green-crm-content-panel">
                <?php if (!$version): ?>
                    <div class="alert alert-warning">Versão não encontrada.</div>
                <?php else: ?>
                    <div class="text-off mb10">
                        Vigência: <?php echo $version->valid_from ? date("d/m/Y", strtotime($version->valid_from)) : "-"; ?>
                        até <?php echo $version->valid_until ? date("d/m/Y", strtotime($version->valid_until)) : "atual"; ?>.
                        <?php if (!empty($version->notes)): ?> &middot; <?php echo esc($version->notes); ?><?php endif; ?>
                    </div>
                    <div class="table-responsive green-table-wrap">
                        <table id="green-rules-table" class="display green-crm-table" cellspacing="0" width="100%"></table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($version): ?>
<script>
$(document).ready(function () {
    var versionId = <?php echo (int) $version->id; ?>;
    $("#green-rules-table").appTable({
        source: "<?php echo_uri("green_crm/commission_rules_list_data"); ?>",
        filterParams: {grade_version_id: versionId},
        tableRefreshButton: true,
        columns: [
            {title: "Operadora"},
            {title: "Produto/plano"},
            {title: "Tipo"},
            {title: "Faixa de vidas"},
            {title: "Comissão total", "class": "text-end"},
            {title: "Distribuição", "class": "text-center"},
            {title: "Status"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w120"}
        ],
        onInitComplete: function () { if (window.feather) { feather.replace(); } },
        onRelaodCallback: function () { if (window.feather) { feather.replace(); } }
    });

    $("body").on("click", ".green-inactivate-rule", function () {
        if (!confirm("Inativar esta regra?")) { return; }
        appAjaxRequest({url: "<?php echo_uri("green_crm/inactivate_commission_rule"); ?>", type: "POST", dataType: "json", data: {id: $(this).data("id")}, success: function (r) { r.success ? appAlert.success(r.message) : appAlert.error(r.message); $("#green-rules-table").appTable({reload: true}); }});
    });

    if (window.feather) { feather.replace(); }
});
</script>
<?php endif; ?>
