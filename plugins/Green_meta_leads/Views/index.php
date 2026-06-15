<?php
$summary = $summary ?? ["total" => 0, "created" => 0, "linked" => 0, "duplicated" => 0, "errors" => 0, "pending" => 0];
$campaigns_dropdown = $campaigns_dropdown ?? [];
$forms_dropdown = $forms_dropdown ?? [];

$campaign_options = [["id" => "", "text" => "Campanha"]];
foreach ($campaigns_dropdown as $c) {
    $campaign_options[] = ["id" => $c->campaign_name, "text" => $c->campaign_name];
}
$form_options = [["id" => "", "text" => "Formulario"]];
foreach ($forms_dropdown as $f) {
    $form_options[] = ["id" => $f->facebook_form_id, "text" => $f->form_name ?: $f->facebook_form_id];
}

$cards = [
    ["label" => "Total captados", "value" => (int) $summary["total"], "icon" => "facebook", "class" => "bg-primary"],
    ["label" => "Criados no CRM", "value" => (int) $summary["created"], "icon" => "user-plus", "class" => "bg-success"],
    ["label" => "Vinculados", "value" => (int) $summary["linked"], "icon" => "link", "class" => "bg-info"],
    ["label" => "Duplicados (ult. sync)", "value" => (int) $summary["duplicated"], "icon" => "copy", "class" => "bg-secondary"],
    ["label" => "Erros", "value" => (int) $summary["errors"], "icon" => "alert-triangle", "class" => "bg-danger"]
];
?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="green-inner-card">
            <div class="page-title clearfix green-crm-page-header">
                <h1>Trafego Pago / Facebook Leads</h1>
                <div class="title-button-group green-crm-title-actions">
                    <?php if ($is_admin): ?>
                        <a href="<?php echo_uri("green_meta_leads/settings"); ?>" class="btn btn-default" title="Configuracoes da integracao">
                            <i data-feather="settings" class="icon-16"></i> Configurações
                        </a>
                    <?php endif; ?>
                    <?php if ($can_sync): ?>
                        <button id="green-meta-sync-btn" class="btn btn-primary" title="Sincronizar com o Facebook Lead Ads">
                            <i data-feather="refresh-cw" class="icon-16"></i> Sincronizar com Facebook
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p20 green-crm-content-panel">
                <?php if (!$token_configured): ?>
                    <div class="alert alert-warning">
                        Token de acesso do Facebook ainda não configurado.
                        <?php if ($is_admin): ?>
                            Acesse <a href="<?php echo_uri("green_meta_leads/settings"); ?>">Configurações</a> para definir o Page Access Token e os Form IDs.
                        <?php else: ?>
                            Solicite a um administrador que configure a integração.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="row green-crm-kpi-list">
                    <?php foreach ($cards as $card): ?>
                        <div class="col-xl-2 col-md-4 col-sm-6">
                            <div class="card dashboard-icon-widget green-crm-kpi-widget">
                                <div class="card-body green-crm-kpi-content">
                                    <div class="widget-icon <?php echo $card["class"]; ?>">
                                        <i data-feather="<?php echo $card["icon"]; ?>" class="icon"></i>
                                    </div>
                                    <div class="widget-details">
                                        <h1><?php echo esc((string) $card["value"]); ?></h1>
                                        <span><?php echo esc($card["label"]); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card dashboard-icon-widget green-crm-kpi-widget">
                            <div class="card-body green-crm-kpi-content">
                                <div class="widget-icon bg-warning">
                                    <i data-feather="clock" class="icon"></i>
                                </div>
                                <div class="widget-details">
                                    <h1 style="font-size:18px;"><?php echo $last_run && $last_run->finished_at ? esc(format_to_datetime($last_run->finished_at)) : "-"; ?></h1>
                                    <span>Última sincronização<?php echo $last_run ? " (" . esc($last_run->status) . ")" : ""; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-off mb10">Leads captados pelo Facebook Lead Ads e enviados automaticamente ao pipeline de Leads do Green CRM. Esta tela mostra a origem de trafego pago e o status de cada lead no funil.</div>
                <div class="table-responsive green-table-wrap">
                    <table id="green-meta-leads-table" class="display green-crm-table" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if (!($.fn.DataTable && $.fn.DataTable.isDataTable("#green-meta-leads-table"))) {
            $("#green-meta-leads-table").appTable({
                source: "<?php echo_uri("green_meta_leads/list_data"); ?>",
                order: [[0, "desc"]],
                tableRefreshButton: true,
                search: {name: "search", show: true},
                rangeDatepicker: [{
                    startDate: {name: "captured_from", value: ""},
                    endDate: {name: "captured_to", value: ""},
                    showClearButton: true,
                    label: "Periodo de captura"
                }],
                filterDropdown: [
                    {name: "process_status", class: "w200", options: [
                        {id: "", text: "Status do processamento"},
                        {id: "created", text: "Criado"},
                        {id: "linked", text: "Vinculado"},
                        {id: "error", text: "Erro"},
                        {id: "pending", text: "Pendente"}
                    ]},
                    {name: "campaign_name", class: "w220", options: <?php echo json_encode($campaign_options); ?>},
                    {name: "facebook_form_id", class: "w220", options: <?php echo json_encode($form_options); ?>}
                ],
                columns: [
                    {title: "Captura", "class": "all"},
                    {title: "Nome", "class": "all"},
                    {title: "Telefone"},
                    {title: "Email"},
                    {title: "Regiao"},
                    {title: "Campanha"},
                    {title: "Formulario"},
                    {title: "Status"},
                    {title: "Lead Green"},
                    {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
                ],
                printColumns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
                xlsColumns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
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
        }

        $("#green-meta-sync-btn").click(function () {
            var $btn = $(this);
            $btn.prop("disabled", true).html("<i data-feather='loader' class='icon-16'></i> Sincronizando...");
            if (window.feather) {
                feather.replace();
            }

            appAjaxRequest({
                url: "<?php echo_uri("green_meta_leads/sync"); ?>",
                type: "POST",
                dataType: "json",
                data: {},
                success: function (result) {
                    result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                    $("#green-meta-leads-table").appTable({reload: true});
                },
                complete: function () {
                    $btn.prop("disabled", false).html("<i data-feather='refresh-cw' class='icon-16'></i> Sincronizar com Facebook");
                    if (window.feather) {
                        feather.replace();
                    }
                }
            });
        });

        $("body").on("click", ".green-meta-reprocess", function () {
            var id = $(this).data("id");
            var $btn = $(this);
            $btn.addClass("disabled");
            appAjaxRequest({
                url: "<?php echo_uri("green_meta_leads/reprocess"); ?>",
                type: "POST",
                dataType: "json",
                data: {id: id},
                success: function (result) {
                    result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                    $("#green-meta-leads-table").appTable({reload: true});
                },
                complete: function () {
                    $btn.removeClass("disabled");
                    if (window.feather) {
                        feather.replace();
                    }
                }
            });
        });

        if (window.feather) {
            feather.replace();
        }
    });
</script>
