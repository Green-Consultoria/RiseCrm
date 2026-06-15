<?php
$model_info = $model_info ?? new \stdClass();
$lead_info = $lead_info ?? null;
$client_info = $client_info ?? null;
$current_user_id = $current_user_id ?? 0;
$implantation_checklist_data = $implantation_checklist_data ?? null;

$format_date = function ($value) {
    if (!$value || $value === "0000-00-00") {
        return "";
    }
    return date("d/m/Y", strtotime($value));
};

$format_money = function ($value) {
    return $value !== null && $value !== "" ? number_format((float) $value, 2, ",", ".") : "";
};

$lead_text = "";
if ($lead_info) {
    $lead_text = trim(($lead_info->lead_code ?: ("LEAD-" . $lead_info->id)) . " - " . $lead_info->client_name);
    $lead_phone = $lead_info->phone_normalized ?: $lead_info->phone_original;
    if ($lead_phone) {
        $lead_text .= " - " . $lead_phone;
    }
}

$client_text = "";
if ($client_info) {
    $client_text = $client_info->name;
    if (!empty($client_info->document_number)) {
        $client_text .= " - " . $client_info->document_number;
    }
    $client_phone = $client_info->phone_normalized ?: ($client_info->phone_original ?? "");
    if ($client_phone) {
        $client_text .= " - " . $client_phone;
    }
}

$selected_lead = $lead_info ? [
    "id" => (int) $lead_info->id,
    "text" => $lead_text,
    "client_id" => (int) $lead_info->client_id,
    "client_name" => $lead_info->client_name,
    "phone" => $lead_info->phone_normalized ?: $lead_info->phone_original,
    "current_operator_id" => (int) $lead_info->current_operator_id,
    "current_operator" => $lead_info->operator_name,
    "current_plan" => $lead_info->current_plan_name,
    "current_paid_value" => $lead_info->current_paid_value,
    "proposed_value" => $lead_info->proposed_value
] : null;

$selected_client = $client_info ? [
    "id" => (int) $client_info->id,
    "text" => $client_text,
    "document_number" => $client_info->document_number,
    "phone" => $client_info->phone_normalized ?: ($client_info->phone_original ?? ""),
    "email" => $client_info->email ?? ""
] : null;
?>

<?php echo form_open(get_uri("green_crm/save_sale"), ["id" => "green-sale-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">

        <div class="form-group row">
            <label class="col-md-3">Buscar lead</label>
            <div class="col-md-7">
                <input type="hidden" id="green-sale-lead-id" name="lead_id" value="<?php echo (int) ($model_info->lead_id ?? 0); ?>" style="width: 100%;">
            </div>
            <div class="col-md-2">
                <button type="button" id="green-use-lead-data" class="btn btn-default w100p" title="Usar dados do lead" <?php echo $selected_lead ? "" : "disabled"; ?>><span data-feather="check-circle" class="icon-16"></span> Usar dados</button>
            </div>
        </div>

        <div id="green-selected-lead-summary" class="form-group row <?php echo $selected_lead ? "" : "hide"; ?>">
            <div class="col-md-9 offset-md-3">
                <div class="text-off"><?php echo esc($lead_text); ?></div>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Buscar cliente</label>
            <div class="col-md-9">
                <input type="hidden" id="green-sale-client-id" name="client_id" value="<?php echo (int) ($model_info->client_id ?? 0); ?>" style="width: 100%;">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Cliente rapido</label>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4 mb10">
                        <?php echo form_input(["name" => "quick_client_name", "id" => "green-quick-client-name", "class" => "form-control", "placeholder" => "Nome"]); ?>
                    </div>
                    <div class="col-md-3 mb10">
                        <?php echo form_input(["name" => "quick_client_phone", "id" => "green-quick-client-phone", "class" => "form-control", "placeholder" => "Telefone"]); ?>
                    </div>
                    <div class="col-md-3 mb10">
                        <?php echo form_input(["name" => "quick_client_email", "id" => "green-quick-client-email", "class" => "form-control", "placeholder" => "Email"]); ?>
                    </div>
                    <div class="col-md-2 mb10">
                        <?php echo form_input(["name" => "quick_client_document", "id" => "green-quick-client-document", "class" => "form-control", "placeholder" => "CPF/CNPJ"]); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Operadora</label>
            <div class="col-md-4">
                <?php echo form_dropdown("operator_id", $operators_dropdown, $model_info->operator_id ?? "", ["id" => "green-sale-operator-id", "class" => "form-control select2", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?>
            </div>
            <label class="col-md-2">Consultor</label>
            <div class="col-md-3">
                <?php echo form_dropdown("consultant_id", $consultants_dropdown, $model_info->consultant_id ?? $current_user_id, ["id" => "green-sale-consultant-id", "class" => "form-control select2"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Plano</label>
            <div class="col-md-9">
                <?php echo form_dropdown("plan_id", $plans_dropdown, $model_info->plan_id ?? "", ["id" => "green-sale-plan-id", "class" => "form-control select2"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Plano livre</label>
            <div class="col-md-9">
                <?php echo form_input(["name" => "plan_name", "id" => "green-sale-plan-name", "value" => $model_info->plan_name ?? "", "class" => "form-control", "placeholder" => "Use se o plano nao estiver cadastrado"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Data da venda</label>
            <div class="col-md-3">
                <?php echo form_input(["name" => "sale_date", "value" => $format_date($model_info->sale_date ?? date("Y-m-d")), "class" => "form-control", "placeholder" => "dd/mm/aaaa", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?>
            </div>
            <label class="col-md-3">Valor da venda</label>
            <div class="col-md-3">
                <?php echo form_input(["name" => "sale_value", "id" => "green-sale-value", "value" => $format_money($model_info->sale_value ?? ""), "class" => "form-control", "placeholder" => "R$ 0,00", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Data de implantacao</label>
            <div class="col-md-3">
                <?php echo form_input(["name" => "implantation_date", "value" => $format_date($model_info->implantation_date ?? ""), "class" => "form-control", "placeholder" => "dd/mm/aaaa"]); ?>
            </div>
            <label class="col-md-3">Fidelidade ate</label>
            <div class="col-md-3">
                <?php echo form_input(["name" => "fidelity_until", "value" => $format_date($model_info->fidelity_until ?? ""), "class" => "form-control", "placeholder" => "dd/mm/aaaa"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Contrato/proposta</label>
            <div class="col-md-9">
                <?php echo form_input(["name" => "contract_number", "value" => $model_info->contract_number ?? "", "class" => "form-control"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Multiplicador comissao total</label>
            <div class="col-md-3">
                <?php echo form_input(["name" => "total_commission_multiplier", "class" => "form-control", "placeholder" => "Ex.: 2,8"]); ?>
            </div>
            <label class="col-md-3">Bonus</label>
            <div class="col-md-3">
                <?php echo form_input(["name" => "bonus_amount", "class" => "form-control", "placeholder" => "R$ 0,00"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Status</label>
            <div class="col-md-4">
                <?php echo form_dropdown("status", ["Vendida" => "Vendida", "Implantacao pendente" => "Implantacao pendente", "Implantada" => "Implantada", "Cancelada" => "Cancelada", "Estornada" => "Estornada"], $model_info->status ?? "Vendida", ["class" => "form-control select2"]); ?>
            </div>
            <label class="col-md-2">Implantacao</label>
            <div class="col-md-3">
                <?php echo form_dropdown("implantation_status", ["nao_iniciada" => "Nao iniciada", "pendente" => "Pendente", "em_andamento" => "Em andamento", "implantada" => "Implantada", "cancelada" => "Cancelada"], $model_info->implantation_status ?? "nao_iniciada", ["class" => "form-control select2"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Observacoes</label>
            <div class="col-md-9">
                <?php echo form_textarea(["name" => "notes", "value" => $model_info->notes ?? "", "class" => "form-control", "rows" => 3]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Checklist implantação</label>
            <div class="col-md-9">
                <?php if (!empty($model_info->id)): ?>
                    <div id="green-sale-implantation-checklist-container">
                        <?php echo view('Green_crm\Views\implantation_checklist', $implantation_checklist_data ?: []); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb0">O checklist de implantação será gerado ao salvar a venda.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="<?php echo app_lang("close"); ?>"><span data-feather="x-circle" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <button type="submit" class="btn btn-primary" title="<?php echo app_lang("save"); ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
$(document).ready(function () {
    var selectedLead = <?php echo json_encode($selected_lead); ?>;
    var selectedClient = <?php echo json_encode($selected_client); ?>;
    var $lead = $("#green-sale-lead-id");
    var $client = $("#green-sale-client-id");

    var money = function (value) {
        if (value === null || value === "" || typeof value === "undefined") {
            return "";
        }
        var number = parseFloat(value);
        if (!number) {
            return "";
        }
        return number.toFixed(2).replace(".", ",");
    };

    var select2Ajax = function (url, initialData) {
        return {
            placeholder: "-",
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: url,
                type: "POST",
                dataType: "json",
                quietMillis: 250,
                data: function (term) {
                    return {q: term};
                },
                results: function (data) {
                    return {results: data.results || data};
                }
            },
            initSelection: function (element, callback) {
                if (initialData && element.val()) {
                    callback(initialData);
                }
            },
            formatResult: function (item) {
                return item.text;
            },
            formatSelection: function (item) {
                return item.text;
            }
        };
    };

    var applyLeadData = function () {
        if (!selectedLead) {
            return;
        }

        if (selectedLead.client_id) {
            var clientText = selectedLead.client_name || ("Cliente #" + selectedLead.client_id);
            if (selectedLead.phone) {
                clientText += " - " + selectedLead.phone;
            }
            $client.val(selectedLead.client_id).select2("data", {id: selectedLead.client_id, text: clientText});
            $("#green-quick-client-name, #green-quick-client-phone, #green-quick-client-email, #green-quick-client-document").val("");
        }

        if (selectedLead.current_operator_id) {
            $("#green-sale-operator-id").select2("val", selectedLead.current_operator_id);
        }

        if (selectedLead.current_plan) {
            $("#green-sale-plan-name").val(selectedLead.current_plan);
        }

        var suggestedValue = selectedLead.proposed_value || selectedLead.current_paid_value;
        if (suggestedValue) {
            $("#green-sale-value").val(money(suggestedValue));
        }
    };

    $(".select2").select2();

    $lead.select2(select2Ajax("<?php echo get_uri("green_crm/search_leads"); ?>", selectedLead)).change(function (e) {
        selectedLead = e.added || null;
        $("#green-use-lead-data").prop("disabled", !selectedLead);
        $("#green-selected-lead-summary").toggleClass("hide", !selectedLead).find(".text-off").text(selectedLead ? selectedLead.text : "");
        applyLeadData();
    });

    $client.select2(select2Ajax("<?php echo get_uri("green_crm/search_clients"); ?>", selectedClient)).change(function (e) {
        selectedClient = e.added || null;
        if (selectedClient) {
            $("#green-quick-client-name, #green-quick-client-phone, #green-quick-client-email, #green-quick-client-document").val("");
        }
    });

    if (selectedLead) {
        $("#green-use-lead-data").prop("disabled", false);
    }

    $("#green-use-lead-data").on("click", applyLeadData);

    $("#green-sale-form").appForm({
        onSuccess: function () {
            if ($("#green-sales-table").length) {
                $("#green-sales-table").appTable({reload: true});
            }
            if ($("#green-leads-table").length) {
                $("#green-leads-table").appTable({reload: true});
            }
        }
    });

    $("body").off("click.greenImplantation").on("click.greenImplantation", ".green-save-implantation-item", function () {
        var $button = $(this);
        var $row = $button.closest("tr");

        appAjaxRequest({
            url: "<?php echo get_uri("green_crm/update_implantation_item"); ?>",
            type: "POST",
            dataType: "json",
            data: {
                id: $button.data("id"),
                status: $row.find(".green-implantation-status").val(),
                notes: $row.find(".green-implantation-notes").val()
            },
            success: function (result) {
                if (result.success) {
                    appAlert.success(result.message);
                    $("#green-sale-implantation-checklist-container").html(result.checklist_html);
                    if ($("#green-sales-table").length) {
                        $("#green-sales-table").appTable({reload: true});
                    }
                    if (window.feather) {
                        feather.replace();
                    }
                } else {
                    appAlert.error(result.message);
                }
            }
        });
    });
});
</script>
