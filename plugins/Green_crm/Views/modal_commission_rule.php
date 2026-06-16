<?php
$model_info = $model_info ?? new \stdClass();
$installments = $installments ?? [];
$operators_dropdown = $operators_dropdown ?? ["" => "-"];
$plans_dropdown = $plans_dropdown ?? ["" => "-"];

// Linha-modelo quando ainda nao ha parcelas cadastradas.
if (!count($installments)) {
    $installments = [(object) ["installment_no" => 1, "installment_label" => "1ª", "commission_rate" => "", "due_offset_months" => 0, "notes" => ""]];
}
?>
<?php echo form_open(get_uri("green_crm/save_commission_rule"), ["id" => "green-rule-form", "class" => "general-form"]); ?>
<div class="modal-body clearfix"><div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
    <input type="hidden" name="grade_version_id" value="<?php echo (int) ($model_info->grade_version_id ?? 0); ?>">

    <div class="form-group row">
        <label class="col-md-3">Operadora (cadastrada)</label>
        <div class="col-md-9"><?php echo form_dropdown("operator_id", $operators_dropdown, $model_info->operator_id ?? "", ["class" => "form-control"]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Operadora (texto livre)</label>
        <div class="col-md-9"><?php echo form_input(["name" => "operator_name_text", "value" => $model_info->operator_name_text ?? "", "class" => "form-control", "placeholder" => "Use quando a operadora ainda não está cadastrada"]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Plano (cadastrado)</label>
        <div class="col-md-9"><?php echo form_dropdown("plan_id", $plans_dropdown, $model_info->plan_id ?? "", ["class" => "form-control"]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Produto/linha</label>
        <div class="col-md-9"><?php echo form_input(["name" => "product_name", "value" => $model_info->product_name ?? "", "class" => "form-control", "placeholder" => "Ex.: Individual, Sênior, PME, Odonto"]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Tipo de produto</label>
        <div class="col-md-9"><?php echo form_input(["name" => "product_type", "value" => $model_info->product_type ?? "", "class" => "form-control", "placeholder" => "Ex.: Saúde, Odonto, PME, Adesão"]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Faixa de vidas</label>
        <div class="col-md-9"><?php echo form_input(["name" => "lives_range_text", "value" => $model_info->lives_range_text ?? "", "class" => "form-control", "placeholder" => "Ex.: 2 a 29 vidas"]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Comissão total (multiplicador)</label>
        <div class="col-md-9">
            <?php echo form_input(["name" => "total_multiplier", "value" => $model_info->total_multiplier ?? "", "class" => "form-control", "placeholder" => "Ex.: 3,5 para 350% (deixe em branco para somar das parcelas)"]); ?>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Observações</label>
        <div class="col-md-9"><?php echo form_textarea(["name" => "notes", "value" => $model_info->notes ?? "", "class" => "form-control", "rows" => 2]); ?></div>
    </div>
    <div class="form-group row">
        <label class="col-md-3">Status</label>
        <div class="col-md-9"><?php echo form_dropdown("status", ["Ativo" => "Ativo", "Inativo" => "Inativo"], $model_info->status ?? "Ativo", ["class" => "form-control"]); ?></div>
    </div>

    <hr>
    <h5>Distribuição das parcelas</h5>
    <div class="text-off mb10">Use multiplicador decimal: 100% = 1,0 &middot; 75% = 0,75 &middot; 50% = 0,5. "Offset (meses)" = quantos meses após a venda a parcela vence.</div>
    <div class="table-responsive">
        <table class="table table-bordered" id="green-rule-installments-table">
            <thead>
                <tr>
                    <th class="w80">Nº</th>
                    <th>Label</th>
                    <th class="w130">Percentual</th>
                    <th class="w120">Offset (meses)</th>
                    <th>Observação</th>
                    <th class="text-center w60"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($installments as $inst): ?>
                    <tr class="green-rule-row">
                        <td><?php echo form_input(["name" => "installment_no[]", "type" => "number", "value" => $inst->installment_no ?? 1, "class" => "form-control green-rule-no", "min" => 1]); ?></td>
                        <td><?php echo form_input(["name" => "installment_label[]", "value" => $inst->installment_label ?? "", "class" => "form-control green-rule-label", "placeholder" => "Ex.: 1ª, 13ª, 25ª"]); ?></td>
                        <td><?php echo form_input(["name" => "commission_rate[]", "value" => $inst->commission_rate ?? "", "class" => "form-control green-rule-rate", "placeholder" => "1,0"]); ?></td>
                        <td><?php echo form_input(["name" => "due_offset_months[]", "type" => "number", "value" => $inst->due_offset_months ?? 0, "class" => "form-control green-rule-offset", "min" => 0]); ?></td>
                        <td><?php echo form_input(["name" => "installment_notes[]", "value" => $inst->notes ?? "", "class" => "form-control green-rule-notes"]); ?></td>
                        <td class="text-center"><button type="button" class="btn btn-default btn-sm green-remove-rule-row" title="Remover"><i data-feather="x-circle" class="icon-16"></i></button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-default btn-sm" id="green-add-rule-row" title="Adicionar parcela"><i data-feather="plus-circle" class="icon-16"></i> Adicionar parcela</button>
</div></div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x-circle" class="icon-16"></span> Fechar</button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> Salvar regra</button>
</div>
<?php echo form_close(); ?>
<script>
$(document).ready(function () {
    $("#green-add-rule-row").on("click", function () {
        var row = $("#green-rule-installments-table tbody tr:first").clone();
        var nextNo = $("#green-rule-installments-table tbody tr").length + 1;
        row.find(".green-rule-no").val(nextNo);
        row.find(".green-rule-label").val(nextNo + "ª");
        row.find(".green-rule-rate").val("");
        row.find(".green-rule-offset").val(nextNo - 1);
        row.find(".green-rule-notes").val("");
        $("#green-rule-installments-table tbody").append(row);
        if (window.feather) { feather.replace(); }
    });

    $("body").on("click", ".green-remove-rule-row", function () {
        if ($("#green-rule-installments-table tbody tr").length > 1) {
            $(this).closest("tr").remove();
        }
    });

    $("#green-rule-form").appForm({
        onSuccess: function () {
            if ($("#green-rules-table").length) { $("#green-rules-table").appTable({reload: true}); }
        }
    });
});
</script>
