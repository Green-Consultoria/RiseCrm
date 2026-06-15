<?php echo form_open(get_uri("green_crm/save_lead"), ["id" => "green-lead-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>" />
        <input type="hidden" name="client_id" value="<?php echo (int) ($model_info->client_id ?? 0); ?>" />

        <h5>Dados do cliente</h5>
        <div class="form-group row">
            <label class="col-md-3">Cod Cliente</label>
            <div class="col-md-9"><?php echo form_input(["name" => "client_code", "value" => $model_info->client_code ?? "", "class" => "form-control", "placeholder" => "Gerado automaticamente (CLI-AAAA-000000)"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Tipo</label>
            <div class="col-md-3"><?php echo form_dropdown("client_type", ["NAO_INFORMADO" => "Nao informado", "PF" => "PF", "PJ" => "PJ"], $model_info->client_type ?? "NAO_INFORMADO", ["class" => "form-control"]); ?></div>
            <label class="col-md-2">CPF/CNPJ</label>
            <div class="col-md-4"><?php echo form_input(["name" => "document_number", "value" => $model_info->document_number ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Nome</label>
            <div class="col-md-9"><?php echo form_input(["name" => "name", "value" => $model_info->client_name ?? $model_info->name ?? "", "class" => "form-control", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Razao social</label>
            <div class="col-md-9"><?php echo form_input(["name" => "legal_name", "value" => $model_info->legal_name ?? "", "class" => "form-control"]); ?></div>
        </div>

        <h5>Contatos</h5>
        <div class="form-group row">
            <label class="col-md-3">WhatsApp/Telefone</label>
            <div class="col-md-4"><?php echo form_input(["name" => "phone", "value" => $model_info->phone_original ?? $model_info->phone_normalized ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-1">Email</label>
            <div class="col-md-4"><?php echo form_input(["name" => "email", "value" => $model_info->email ?? "", "class" => "form-control"]); ?></div>
        </div>

        <h5>Comercial</h5>
        <div class="form-group row">
            <label class="col-md-2">Origem</label>
            <div class="col-md-3"><?php echo form_dropdown("source_id", $sources_dropdown, $model_info->source_id ?? 1, ["class" => "form-control"]); ?></div>
            <label class="col-md-2">ID origem</label>
            <div class="col-md-2"><?php echo form_input(["name" => "source_lead_id", "value" => $model_info->source_lead_id ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-1">Status</label>
            <div class="col-md-2"><?php echo form_dropdown("status_id", $statuses_dropdown, $model_info->status_id ?? ($default_status_id ?? ""), ["class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Temperatura</label>
            <div class="col-md-3"><?php echo form_dropdown("temperature", ["sem_classificacao" => "Sem classificacao", "quente" => "Quente", "morno" => "Morno", "frio" => "Frio"], $model_info->temperature ?? "sem_classificacao", ["class" => "form-control"]); ?></div>
        </div>

        <h5>Plano atual / necessidade</h5>
        <div class="form-group row">
            <label class="col-md-2">Operadora atual</label>
            <div class="col-md-3"><?php echo form_dropdown("current_operator_id", $operators_dropdown, $model_info->current_operator_id ?? "", ["class" => "form-control"]); ?></div>
            <label class="col-md-2">Plano atual</label>
            <div class="col-md-3"><?php echo form_input(["name" => "current_plan_name", "value" => $model_info->current_plan_name ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Tipo de plano desejado</label>
            <div class="col-md-10"><?php echo form_input(["name" => "desired_plan_type", "value" => $model_info->desired_plan_type ?? "", "class" => "form-control", "placeholder" => "Ex.: Individual, Familiar, Empresarial, Adesao..."]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Qtd vidas</label>
            <div class="col-md-2"><?php echo form_input(["name" => "lives_qty", "type" => "number", "value" => $model_info->lives_qty ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-1">Idades</label>
            <div class="col-md-3"><?php echo form_input(["name" => "ages_text", "value" => $model_info->ages_text ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-2">Mes reajuste</label>
            <div class="col-md-2"><?php echo form_input(["name" => "renewal_month", "type" => "number", "min" => 1, "max" => 12, "value" => $model_info->renewal_month ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Valor pago</label>
            <div class="col-md-2"><?php echo form_input(["name" => "current_paid_value", "value" => $model_info->current_paid_value ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-2">Valor proposta</label>
            <div class="col-md-2"><?php echo form_input(["name" => "proposed_value", "value" => $model_info->proposed_value ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-1">Regiao</label>
            <div class="col-md-3"><?php echo form_input(["name" => "region", "value" => $model_info->region ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Hospital de preferencia</label>
            <div class="col-md-9"><?php echo form_input(["name" => "preferred_hospital_text", "value" => $model_info->preferred_hospital_text ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Observacoes</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "notes", "value" => $model_info->notes ?? "", "class" => "form-control", "rows" => 3]); ?></div>
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
        $("#green-lead-form").appForm({
            onSuccess: function (result) {
                if ($("#green-leads-table").length) {
                    $("#green-leads-table").appTable({reload: true});
                }
            }
        });
    });
</script>
