<?php
$model_info = $model_info ?? new \stdClass();
$expected = (float) ($model_info->expected_amount ?? 0);
$received = (float) ($model_info->received_amount ?? 0);
$open_amount = max(0, $expected - $received);
$difference = $received - $expected;
$money = function ($value) {
    return number_format((float) $value, 2, ",", ".");
};
?>

<?php echo form_open(get_uri("green_crm/save_commission_payment"), ["id" => "green-commission-payment-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
        <div class="mb15">
            <div>Esperado: R$ <?php echo $money($expected); ?></div>
            <div>Recebido: R$ <?php echo $money($received); ?></div>
            <div>Diferença atual: R$ <?php echo $money($difference); ?></div>
            <strong>Saldo: R$ <?php echo $money($open_amount); ?></strong>
        </div>
        <div class="form-group row"><label class="col-md-4">Valor desta baixa</label><div class="col-md-8"><?php echo form_input(["name"=>"received_amount", "value"=>$money($open_amount ?: $expected), "class"=>"form-control", "data-rule-required"=>true]); ?></div></div>
        <div class="form-group row"><label class="col-md-4">Pago em</label><div class="col-md-8"><?php echo form_input(["name"=>"paid_at", "type"=>"datetime-local", "value"=>date("Y-m-d\\TH:i"), "class"=>"form-control", "data-rule-required"=>true]); ?></div></div>
        <div class="form-group row"><label class="col-md-4">Forma</label><div class="col-md-8"><?php echo form_input(["name"=>"payment_method", "class"=>"form-control"]); ?></div></div>
        <div class="form-group row"><label class="col-md-4">Notas</label><div class="col-md-8"><?php echo form_textarea(["name"=>"notes", "class"=>"form-control", "rows"=>3]); ?></div></div>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal" title="Fechar"><span data-feather="x-circle" class="icon-16"></span> Fechar</button><button type="submit" class="btn btn-primary" title="Salvar baixa"><span data-feather="check-circle" class="icon-16"></span> Salvar baixa</button></div>
<?php echo form_close(); ?>
<script>
$(document).ready(function(){ $("#green-commission-payment-form").appForm({onSuccess:function(){ if($("#green-commissions-table").length){$("#green-commissions-table").appTable({reload:true});} }}); });
</script>
