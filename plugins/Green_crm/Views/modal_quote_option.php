<?php
$model_info = $model_info ?? new \stdClass();
$operators_dropdown = $operators_dropdown ?? [];
$plans_dropdown = $plans_dropdown ?? [];

$format_money = function ($value) {
    return $value !== null && $value !== "" ? number_format((float) $value, 2, ",", ".") : "";
};
?>

<?php echo form_open(get_uri("green_crm/save_quote_option"), ["id" => "green-quote-option-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="option_id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
        <input type="hidden" name="quote_id" value="<?php echo (int) ($model_info->quote_id ?? 0); ?>">

        <div class="form-group row">
            <label class="col-md-2">Operadora</label>
            <div class="col-md-4">
                <?php echo form_dropdown("operator_id", $operators_dropdown, $model_info->operator_id ?? "", ["class" => "form-control select2"]); ?>
            </div>
            <label class="col-md-2">Plano</label>
            <div class="col-md-4">
                <?php echo form_dropdown("plan_id", $plans_dropdown, $model_info->plan_id ?? "", ["class" => "form-control select2"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-2">Plano livre</label>
            <div class="col-md-4">
                <?php echo form_input(["name" => "plan_name", "value" => $model_info->plan_name ?? "", "class" => "form-control"]); ?>
            </div>
            <label class="col-md-2">Valor mensal</label>
            <div class="col-md-2">
                <?php echo form_input(["name" => "monthly_value", "value" => $format_money($model_info->monthly_value ?? ""), "class" => "form-control"]); ?>
            </div>
            <label class="col-md-1">Vidas</label>
            <div class="col-md-1">
                <?php echo form_input(["name" => "lives_qty", "type" => "number", "value" => $model_info->lives_qty ?? "", "class" => "form-control"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-2">Acomodação</label>
            <div class="col-md-4">
                <?php echo form_input(["name" => "accommodation", "value" => $model_info->accommodation ?? ($model_info->plan_accommodation ?? ""), "class" => "form-control", "placeholder" => "Ex.: Enfermaria, Apartamento"]); ?>
            </div>
            <label class="col-md-2">Etiqueta</label>
            <div class="col-md-4">
                <?php echo form_input(["name" => "highlight_label", "value" => $model_info->highlight_label ?? "", "class" => "form-control", "placeholder" => "Melhor preço, Melhor rede, Mais equilibrado, Indicado"]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-2">Coparticipação</label>
            <div class="col-md-4">
                <?php echo form_checkbox("coparticipation", "1", (int) ($model_info->coparticipation ?? 0), "id='green-option-coparticipation'"); ?>
                <label for="green-option-coparticipation">Sim</label>
            </div>
            <label class="col-md-2">Hospital preferido</label>
            <div class="col-md-4">
                <?php echo form_checkbox("hospital_match", "1", (int) ($model_info->hospital_match ?? 0), "id='green-option-hospital-match'"); ?>
                <label for="green-option-hospital-match">Atende</label>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Rede/hospitais</label>
            <div class="col-md-9">
                <?php echo form_textarea(["name" => "network_notes", "value" => $model_info->network_notes ?? "", "class" => "form-control", "rows" => 3]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Pontos positivos</label>
            <div class="col-md-9">
                <?php echo form_textarea(["name" => "pros", "value" => $model_info->pros ?? "", "class" => "form-control", "rows" => 2]); ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3">Pontos negativos</label>
            <div class="col-md-9">
                <?php echo form_textarea(["name" => "cons", "value" => $model_info->cons ?? "", "class" => "form-control", "rows" => 2]); ?>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="<?php echo app_lang("close"); ?>"><span data-feather="x-circle" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <button type="submit" class="btn btn-primary" title="<?php echo app_lang("save"); ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
</div>
<?php echo form_close(); ?>

<script>
$(document).ready(function () {
    $(".select2").select2();
    $("#green-quote-option-form").appForm({
        onSuccess: function () {
            if ($("#green-quote-comparison-page").length) {
                location.reload();
            }
            if ($("#green-quotes-table").length) {
                $("#green-quotes-table").appTable({reload: true});
            }
        }
    });
});
</script>
