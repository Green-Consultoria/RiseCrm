<?php echo form_open(get_uri("green_crm/save_quote"), ["id" => "green-quote-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
        <div class="form-group row">
            <label class="col-md-3">Lead ID</label>
            <div class="col-md-3"><?php echo form_input(["name" => "lead_id", "type" => "number", "value" => $model_info->lead_id ?? "", "class" => "form-control", "data-rule-required" => true]); ?></div>
            <label class="col-md-2">Status</label>
            <div class="col-md-4"><?php echo form_dropdown("status", ["Rascunho"=>"Rascunho","Enviada"=>"Enviada","Aceita"=>"Aceita","Recusada"=>"Recusada","Vencida"=>"Vencida","Cancelada"=>"Cancelada"], $model_info->status ?? "Rascunho", ["class"=>"form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Valida ate</label>
            <div class="col-md-3"><?php echo form_input(["name" => "valid_until", "type" => "date", "value" => $model_info->valid_until ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Observações</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "notes", "value" => $model_info->notes ?? "", "class" => "form-control", "rows" => 2]); ?></div>
        </div>
        <hr>
        <h5>Opção inicial da cotação</h5>
        <div class="form-group row">
            <label class="col-md-2">Operadora</label>
            <div class="col-md-4"><?php echo form_dropdown("operator_id", $operators_dropdown, "", ["class" => "form-control"]); ?></div>
            <label class="col-md-2">Plano</label>
            <div class="col-md-4"><?php echo form_dropdown("plan_id", $plans_dropdown, "", ["class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Plano texto</label>
            <div class="col-md-4"><?php echo form_input(["name" => "plan_name", "class" => "form-control"]); ?></div>
            <label class="col-md-2">Valor mensal</label>
            <div class="col-md-2"><?php echo form_input(["name" => "monthly_value", "class" => "form-control"]); ?></div>
            <label class="col-md-1">Vidas</label>
            <div class="col-md-1"><?php echo form_input(["name" => "lives_qty", "type" => "number", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Tipo de produto</label>
            <div class="col-md-4"><?php echo form_input(["name" => "product_type", "class" => "form-control", "placeholder" => "Ex.: Saúde, Odonto, PME"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Acomodação</label>
            <div class="col-md-4"><?php echo form_input(["name" => "accommodation", "class" => "form-control", "placeholder" => "Ex.: Enfermaria, Apartamento"]); ?></div>
            <label class="col-md-2">Etiqueta</label>
            <div class="col-md-4"><?php echo form_input(["name" => "highlight_label", "class" => "form-control", "placeholder" => "Melhor preço, Melhor rede, Mais equilibrado, Indicado"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-2">Coparticipação</label>
            <div class="col-md-4"><?php echo form_checkbox("coparticipation", "1", false, "id='green-quote-coparticipation'"); ?> <label for="green-quote-coparticipation">Sim</label></div>
            <label class="col-md-2">Hospital preferido</label>
            <div class="col-md-4"><?php echo form_checkbox("hospital_match", "1", false, "id='green-quote-hospital-match'"); ?> <label for="green-quote-hospital-match">Atende</label></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Rede/hospitais</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "network_notes", "class" => "form-control", "rows" => 3]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Hospital de preferência</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "preferred_hospital_notes", "class" => "form-control", "rows" => 2, "placeholder" => "Hospitais/rede de preferência do cliente"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Pontos positivos</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "pros", "class" => "form-control", "rows" => 2]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Pontos negativos</label>
            <div class="col-md-9"><?php echo form_textarea(["name" => "cons", "class" => "form-control", "rows" => 2]); ?></div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="<?php echo app_lang("close"); ?>"><span data-feather="x-circle" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <button type="submit" class="btn btn-primary" title="<?php echo app_lang("save"); ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
</div>
<?php echo form_close(); ?>
<script>
$(document).ready(function(){
    var isNewQuote = <?php echo empty($model_info->id) ? "true" : "false"; ?>;
    $("#green-quote-form").appForm({
        onSuccess:function(result){
            if (isNewQuote && result.id) {
                window.location.href = "<?php echo get_uri("green_crm/quote"); ?>/" + result.id;
                return;
            }
            if($("#green-quotes-table").length){
                $("#green-quotes-table").appTable({reload:true});
            }
            if ($("#green-quote-comparison-page").length) {
                location.reload();
            }
        }
    });
});
</script>
