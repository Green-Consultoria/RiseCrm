<?php echo form_open(get_uri("green_crm/save_operator"), ["id"=>"green-operator-form", "class"=>"general-form"]); ?>
<div class="modal-body clearfix"><div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
    <div class="form-group row"><label class="col-md-3">Nome</label><div class="col-md-9"><?php echo form_input(["name"=>"name", "value"=>$model_info->name ?? "", "class"=>"form-control", "data-rule-required"=>true]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Aliases</label><div class="col-md-9"><?php echo form_input(["name"=>"aliases", "value"=>$model_info->aliases ?? "", "class"=>"form-control"]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Status</label><div class="col-md-9"><?php echo form_dropdown("status", ["Ativo"=>"Ativo","Inativo"=>"Inativo"], $model_info->status ?? "Ativo", ["class"=>"form-control"]); ?></div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal" title="Fechar"><span data-feather="x-circle" class="icon-16"></span> Fechar</button><button type="submit" class="btn btn-primary" title="Salvar"><span data-feather="check-circle" class="icon-16"></span> Salvar</button></div>
<?php echo form_close(); ?>
<script>$(document).ready(function(){ $("#green-operator-form").appForm({onSuccess:function(){ if($("#green-operators-table").length){$("#green-operators-table").appTable({reload:true});} }}); });</script>
