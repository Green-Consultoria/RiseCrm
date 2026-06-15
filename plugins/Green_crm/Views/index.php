<?php
$dashboard = $dashboard ?? [];
$dashboard_blocks = $dashboard_blocks ?? [];
$green_request = service("request");
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix green-crm-page-header">
            <h1>Green Dashboard</h1>
            <div class="title-button-group green-crm-title-actions">
                <?php echo anchor(get_uri("green_crm/leads"), "<i data-feather='users' class='icon-16'></i> Abrir Clientes / Capa", ["class" => "btn btn-primary", "title" => "Abrir Clientes / Capa"]); ?>
                <?php echo modal_anchor(get_uri("green_crm/lead_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Novo cliente", ["class" => "btn btn-default", "title" => "Novo cliente"]); ?>
                <?php echo modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i> Nova venda", ["class" => "btn btn-default", "title" => "Nova venda"]); ?>
                <?php echo modal_anchor(get_uri("green_crm/import_modal_form"), "<i data-feather='upload' class='icon-16'></i> Importar vendidos", ["class" => "btn btn-default", "title" => "Importar Excel CRM Vendidos"]); ?>
                <?php echo anchor(get_uri("green_crm/renewals"), "<i data-feather='refresh-cw' class='icon-16'></i> Ver reajustes", ["class" => "btn btn-default", "title" => "Ver reajustes"]); ?>
                <?php if (function_exists("green_meta_leads_install_or_update")): ?>
                    <?php echo anchor(get_uri("green_meta_leads"), "<i data-feather='facebook' class='icon-16'></i> Ver leads Facebook", ["class" => "btn btn-default", "title" => "Ver leads do Facebook / Trafego Pago"]); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="green-crm-filter-panel p20">
            <div class="row align-items-end">
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-date-from">Período início</label>
                    <?php echo form_input(["id" => "green-filter-date-from", "name" => "date_from", "class" => "form-control", "type" => "date", "value" => $green_request->getGet("date_from")]); ?>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-date-to">Período fim</label>
                    <?php echo form_input(["id" => "green-filter-date-to", "name" => "date_to", "class" => "form-control", "type" => "date", "value" => $green_request->getGet("date_to")]); ?>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-source">Origem</label>
                    <?php echo form_dropdown("source_id", $sources_dropdown ?? ["" => "-"], $green_request->getGet("source_id"), ["id" => "green-filter-source", "class" => "form-control"]); ?>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-consultant">Consultor</label>
                    <?php echo form_dropdown("consultant_id", $consultants_dropdown ?? ["" => "-"], $green_request->getGet("consultant_id"), ["id" => "green-filter-consultant", "class" => "form-control"]); ?>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-operator">Operadora</label>
                    <?php echo form_dropdown("operator_id", $operators_dropdown ?? ["" => "-"], $green_request->getGet("operator_id"), ["id" => "green-filter-operator", "class" => "form-control"]); ?>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-status">Status</label>
                    <?php echo form_dropdown("status_id", $statuses_dropdown ?? ["" => "-"], $green_request->getGet("status_id"), ["id" => "green-filter-status", "class" => "form-control"]); ?>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="green-filter-temperature">Temperatura</label>
                    <?php echo form_dropdown("temperature", ["" => "-", "quente" => "Quente", "morno" => "Morno", "frio" => "Frio", "sem_classificacao" => "Sem classificação"], $green_request->getGet("temperature"), ["id" => "green-filter-temperature", "class" => "form-control"]); ?>
                </div>
            </div>
            <div class="green-crm-filter-actions mt10">
                <button type="button" id="green-apply-dashboard-filters" class="btn btn-default" title="Aplicar filtros"><i data-feather="filter" class="icon-16"></i> Aplicar filtros</button>
                <a href="<?php echo get_uri("green_crm"); ?>" class="btn btn-default" title="Limpar filtros"><i data-feather="x-circle" class="icon-16"></i> Limpar</a>
            </div>
        </div>

        <div class="p20 green-crm-content-panel">
            <?php echo view('Green_crm\Views\dashboard_cards', ["dashboard" => $dashboard]); ?>
            <?php echo view('Green_crm\Views\partials\dashboard_management', ["dashboard_blocks" => $dashboard_blocks]); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#green-filter-source, #green-filter-consultant, #green-filter-operator, #green-filter-status, #green-filter-temperature").select2({
            allowClear: true,
            width: "100%"
        });

        $("#green-apply-dashboard-filters").on("click", function () {
            var url = new URL("<?php echo get_uri("green_crm"); ?>");
            [
                ["date_from", "#green-filter-date-from"],
                ["date_to", "#green-filter-date-to"],
                ["source_id", "#green-filter-source"],
                ["consultant_id", "#green-filter-consultant"],
                ["operator_id", "#green-filter-operator"],
                ["status_id", "#green-filter-status"],
                ["temperature", "#green-filter-temperature"]
            ].forEach(function (item) {
                var value = $(item[1]).val();
                if (value) {
                    url.searchParams.set(item[0], value);
                }
            });
            window.location.href = url.toString();
        });

        if (window.feather) {
            feather.replace();
        }
    });
</script>
