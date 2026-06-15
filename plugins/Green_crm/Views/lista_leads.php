<?php $embedded = $embedded ?? false; ?>

<?php if (!$embedded): ?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
<?php endif; ?>

<div class="green-inner-card">
    <div class="page-title clearfix green-crm-page-header">
        <h1>Clientes / Capa comercial</h1>
        <div class="title-button-group green-crm-title-actions">
            <?php echo modal_anchor(get_uri("green_crm/lead_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Novo cliente", ["class" => "btn btn-primary", "title" => "Novo cliente"]); ?>
        </div>
    </div>

    <div class="p20 green-crm-content-panel">
        <div class="text-off mb10">Capa de atendimento com os dados principais do cliente e filtros por coluna. Use "Filtros da capa" para buscar por cada campo apresentado.</div>

        <?php
        $month_names = ["", "Janeiro", "Fevereiro", "Marco", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        $renewal_options = ["" => "Mes de reajuste"];
        for ($m = 1; $m <= 12; $m++) {
            $renewal_options[$m] = $month_names[$m];
        }
        $temperature_options = ["" => "Temperatura", "quente" => "Quente", "morno" => "Morno", "frio" => "Frio", "sem_classificacao" => "Sem classificacao"];
        ?>

        <div class="accordion mb15" id="green-capa-accordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="green-capa-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#green-capa-filters-body" aria-expanded="false" aria-controls="green-capa-filters-body">
                        <i data-feather="filter" class="icon-16"></i> &nbsp;Filtros da capa
                    </button>
                </h2>
                <div id="green-capa-filters-body" class="accordion-collapse collapse" aria-labelledby="green-capa-heading" data-bs-parent="#green-capa-accordion">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb10"><label>Cod Cliente</label><?php echo form_input(["name" => "client_code", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Nome</label><?php echo form_input(["name" => "client_name", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>CPF/CNPJ</label><?php echo form_input(["name" => "document_number", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Telefone</label><?php echo form_input(["name" => "phone", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Email</label><?php echo form_input(["name" => "email", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Status</label><?php echo form_dropdown("status_id", $statuses_dropdown, "", ["class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Temperatura</label><?php echo form_dropdown("temperature", $temperature_options, "", ["class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Origem</label><?php echo form_dropdown("source_id", $sources_dropdown, "", ["class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Operadora</label><?php echo form_dropdown("operator_id", $operators_dropdown, "", ["class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Plano</label><?php echo form_input(["name" => "plan", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Qtd de vidas</label><?php echo form_input(["name" => "lives_qty", "type" => "number", "min" => 0, "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Idades</label><?php echo form_input(["name" => "ages", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Mes de reajuste</label><?php echo form_dropdown("renewal_month", $renewal_options, "", ["class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Valor pago (min)</label><?php echo form_input(["name" => "current_paid_min", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Valor pago (max)</label><?php echo form_input(["name" => "current_paid_max", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Valor proposta (min)</label><?php echo form_input(["name" => "proposed_min", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Valor proposta (max)</label><?php echo form_input(["name" => "proposed_max", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Regiao</label><?php echo form_input(["name" => "region", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Hospital de preferencia</label><?php echo form_input(["name" => "hospital", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Observacoes</label><?php echo form_input(["name" => "notes", "class" => "form-control"]); ?></div>
                            <div class="col-md-3 col-sm-6 mb10"><label>Busca geral</label><?php echo form_input(["name" => "search", "class" => "form-control"]); ?></div>
                        </div>
                        <div class="mt10">
                            <button type="button" class="btn btn-primary" id="green-capa-apply" title="Aplicar filtros"><i data-feather="search" class="icon-16"></i> Aplicar filtros</button>
                            <button type="button" class="btn btn-default" id="green-capa-clear" title="Limpar filtros"><i data-feather="x-circle" class="icon-16"></i> Limpar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive green-table-wrap">
            <table id="green-leads-table" class="display green-crm-table green-table-leads" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<?php if (!$embedded): ?>
    </div>
</div>
<?php endif; ?>

<script type="text/javascript">
    $(document).ready(function () {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-leads-table")) {
            return;
        }

        function greenCapaCollectFilters() {
            var params = {};
            $("#green-capa-filters-body").find("input[name], select[name]").each(function () {
                var name = $(this).attr("name");
                if (name) {
                    params[name] = $(this).val();
                }
            });
            return params;
        }

        // appTable's ajax reads filterParams from the stored instance settings
        // (window.InstanceCollection[tableId]), so we update it there before reload.
        function greenCapaApplyFilters(params) {
            if (window.InstanceCollection && window.InstanceCollection["green-leads-table"]) {
                window.InstanceCollection["green-leads-table"].filterParams = params;
            }
            $("#green-leads-table").appTable({reload: true, filterParams: params});
        }

        $("#green-leads-table").appTable({
            source: "<?php echo_uri("green_crm/leads_list_data"); ?>",
            order: [[0, "desc"]],
            tableRefreshButton: true,
            columns: [
                {title: "Cod Cliente", "class": "all green-col-code"},
                {title: "Cliente", "class": "all green-col-client"},
                {title: "CPF/CNPJ", "class": "green-col-document"},
                {title: "Telefone", "class": "green-col-phone"},
                {title: "Email", "class": "green-col-email"},
                {title: "Status", "class": "green-col-status"},
                {title: "Temperatura", "class": "green-col-temperature"},
                {title: "Operadora", "class": "green-col-operator"},
                {title: "Plano", "class": "green-col-plan"},
                {title: "Vidas", "class": "text-center green-col-small"},
                {title: "Valor pago", "class": "text-end green-col-money"},
                {title: "Valor proposta", "class": "text-end green-col-money"},
                {title: "Reajuste", "class": "text-center green-col-small"},
                {title: "Regiao", "class": "green-col-region"},
                {title: "Hospital", "class": "green-col-hospital"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w170"}
            ],
            printColumns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14],
            xlsColumns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14],
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

        $("#green-capa-apply").on("click", function () {
            greenCapaApplyFilters(greenCapaCollectFilters());
        });

        $("#green-capa-filters-body").on("keydown", "input[name]", function (e) {
            if (e.which === 13) {
                e.preventDefault();
                greenCapaApplyFilters(greenCapaCollectFilters());
            }
        });

        $("#green-capa-clear").on("click", function () {
            $("#green-capa-filters-body").find("input[name]").val("");
            $("#green-capa-filters-body").find("select[name]").prop("selectedIndex", 0);
            greenCapaApplyFilters({});
        });

        $("body").on("click", ".green-delete-lead", function () {
            var id = $(this).data("id");
            $(this).appConfirmation({
                title: "Excluir cliente?",
                btnConfirmLabel: "Excluir",
                onConfirm: function () {
                    appAjaxRequest({
                        url: "<?php echo_uri("green_crm/delete_lead"); ?>",
                        type: "POST",
                        dataType: "json",
                        data: {id: id},
                        success: function (result) {
                            result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                            $("#green-leads-table").appTable({reload: true});
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
