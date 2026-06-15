<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="green-inner-card">
            <div class="page-title clearfix green-crm-page-header">
                <h1>Configurações &mdash; Leads Facebook</h1>
                <div class="title-button-group green-crm-title-actions">
                    <a href="<?php echo_uri("green_meta_leads"); ?>" class="btn btn-default">
                        <i data-feather="arrow-left" class="icon-16"></i> Voltar
                    </a>
                </div>
            </div>

            <div class="p20 green-crm-content-panel">
                <div class="text-off mb15">
                    Configure as credenciais da Graph API do Meta. Os leads sincronizados são criados como Leads no Green CRM com origem
                    <strong>Facebook Lead Ads</strong>.
                </div>

                <?php echo form_open(get_uri("green_meta_leads/save_settings"), ["id" => "green-meta-settings-form", "class" => "general-form", "role" => "form"]); ?>
                <div class="form-group row mb15">
                    <label class="col-md-3 col-form-label">Page Access Token</label>
                    <div class="col-md-9">
                        <?php echo form_password([
                            "name" => "green_meta_page_access_token",
                            "class" => "form-control",
                            "autocomplete" => "new-password",
                            "placeholder" => $token_configured ? "•••••••• (configurado — preencha apenas para alterar)" : "Cole o Page Access Token aqui"
                        ]); ?>
                        <small class="text-off">Token de acesso da Página com permissões <em>leads_retrieval</em> / <em>pages_manage_ads</em>.</small>
                    </div>
                </div>

                <div class="form-group row mb15">
                    <label class="col-md-3 col-form-label">Versão da Graph API</label>
                    <div class="col-md-3">
                        <?php echo form_input(["name" => "green_meta_graph_version", "value" => $graph_version, "class" => "form-control"]); ?>
                    </div>
                </div>

                <div class="form-group row mb15">
                    <label class="col-md-3 col-form-label">Form IDs</label>
                    <div class="col-md-9">
                        <?php echo form_input(["name" => "green_meta_form_ids", "value" => $form_ids, "class" => "form-control", "placeholder" => "1234567890, 0987654321"]); ?>
                        <small class="text-off">IDs dos formulários de Lead Ads, separados por vírgula.</small>
                    </div>
                </div>

                <div class="form-group row mb15">
                    <label class="col-md-3 col-form-label">Janela de busca (dias)</label>
                    <div class="col-md-3">
                        <?php echo form_input(["name" => "green_meta_since_days", "value" => $since_days, "class" => "form-control", "type" => "number", "min" => "1"]); ?>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <span data-feather="check-circle" class="icon-16"></span> Salvar configurações
                        </button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#green-meta-settings-form").on("submit", function (e) {
            e.preventDefault();
            var $form = $(this);
            appAjaxRequest({
                url: $form.attr("action"),
                type: "POST",
                dataType: "json",
                data: $form.serialize(),
                success: function (result) {
                    result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                }
            });
        });

        if (window.feather) {
            feather.replace();
        }
    });
</script>
