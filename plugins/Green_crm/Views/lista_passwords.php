<?php
$members_dropdown = $members_dropdown ?? ["" => "-"];
$categories_dropdown = $categories_dropdown ?? ["" => "Categoria"];
$can_manage = $can_manage ?? false;
$can_reveal = $can_reveal ?? false;
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Banco de senhas</h1>
            <div class="title-button-group">
                <?php if ($can_manage) { ?>
                    <?php echo modal_anchor(get_uri("green_crm/password_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Novo acesso", ["class" => "btn btn-primary", "title" => "Novo acesso"]); ?>
                <?php } ?>
            </div>
        </div>

        <div class="p20">
            <div class="text-off mb10">Os acessos são armazenados criptografados. A senha nunca aparece na listagem; use o botão revelar (gera auditoria).</div>
            <div class="row mb15">
                <div class="col-md-3 col-sm-6 mb10"><label>Categoria</label><?php echo form_dropdown("category", $categories_dropdown, "", ["class" => "form-control", "id" => "f-category"]); ?></div>
                <div class="col-md-3 col-sm-6 mb10"><label>Responsável</label><?php echo form_dropdown("owner_user_id", $members_dropdown, "", ["class" => "form-control", "id" => "f-owner"]); ?></div>
                <div class="col-md-4 col-sm-8 mb10"><label>Busca</label><?php echo form_input(["name" => "search", "class" => "form-control", "id" => "f-search"]); ?></div>
                <div class="col-md-2 col-sm-4 mb10 d-flex align-items-end">
                    <button type="button" class="btn btn-primary me-1" id="green-vault-apply" title="Aplicar"><i data-feather="search" class="icon-16"></i></button>
                    <button type="button" class="btn btn-default" id="green-vault-clear" title="Limpar"><i data-feather="x-circle" class="icon-16"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="green-vault-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable("#green-vault-table")) {
            return;
        }

        function greenVaultFilters() {
            return {
                category: $("#f-category").val(),
                owner_user_id: $("#f-owner").val(),
                search: $("#f-search").val()
            };
        }

        function greenVaultApply() {
            var params = greenVaultFilters();
            if (window.InstanceCollection && window.InstanceCollection["green-vault-table"]) {
                window.InstanceCollection["green-vault-table"].filterParams = params;
            }
            $("#green-vault-table").appTable({reload: true, filterParams: params});
        }

        $("#green-vault-table").appTable({
            source: "<?php echo_uri("green_crm/passwords_list_data"); ?>",
            order: [[0, "asc"]],
            tableRefreshButton: true,
            columns: [
                {title: "Acesso", "class": "all"},
                {title: "Categoria"},
                {title: "Sistema/URL"},
                {title: "Login"},
                {title: "Senha", "class": "text-center"},
                {title: "Responsável"},
                {title: "Atualizado"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "all text-center option w120"}
            ],
            printColumns: [0,1,2,3,5,6],
            onInitComplete: function () { if (window.feather) feather.replace(); },
            onRelaodCallback: function () { if (window.feather) feather.replace(); }
        });

        $("#green-vault-apply").on("click", greenVaultApply);
        $("#f-search").on("keydown", function (e) { if (e.which === 13) { e.preventDefault(); greenVaultApply(); } });
        $("#green-vault-clear").on("click", function () {
            $("#f-category,#f-owner").prop("selectedIndex", 0);
            $("#f-search").val("");
            greenVaultApply();
        });

        $("body").on("click", ".green-reveal-password", function () {
            var id = $(this).data("id");
            appAjaxRequest({
                url: "<?php echo_uri("green_crm/reveal_password"); ?>",
                type: "POST", dataType: "json", data: {id: id},
                success: function (result) {
                    if (!result.success) { appAlert.error(result.message); return; }
                    var pwd = result.password;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(pwd).then(function () {
                            appAlert.success("Senha copiada para a área de transferência.");
                            appAjaxRequest({url: "<?php echo_uri("green_crm/log_copy_password"); ?>", type: "POST", dataType: "json", data: {id: id}});
                        });
                    }
                    appAlert.info("Senha: " + pwd, {duration: 10000});
                }
            });
        });

        <?php if ($can_manage) { ?>
        $("body").on("click", ".green-delete-password", function () {
            var id = $(this).data("id");
            $(this).appConfirmation({
                title: "Excluir acesso?",
                btnConfirmLabel: "Excluir",
                onConfirm: function () {
                    appAjaxRequest({
                        url: "<?php echo_uri("green_crm/delete_password"); ?>",
                        type: "POST", dataType: "json", data: {id: id},
                        success: function (result) {
                            result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                            $("#green-vault-table").appTable({reload: true});
                        }
                    });
                }
            });
        });
        <?php } ?>

        if (window.feather) feather.replace();
    });
</script>
