<?php echo form_open(get_uri("green_crm/save_status"), ["id"=>"green-status-form", "class"=>"general-form"]); ?>
<div class="modal-body clearfix"><div class="container-fluid">
    <input type="hidden" name="id" value="<?php echo (int) ($model_info->id ?? 0); ?>">
    <div class="form-group row"><label class="col-md-3">Titulo</label><div class="col-md-9"><?php echo form_input(["name"=>"title", "value"=>$model_info->title ?? "", "class"=>"form-control", "data-rule-required"=>true]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Ordem</label><div class="col-md-3"><?php echo form_input(["name"=>"sort", "type"=>"number", "value"=>$model_info->sort ?? 0, "class"=>"form-control"]); ?></div></div>
    <div class="form-group row"><label class="col-md-3">Flags</label><div class="col-md-9">
        <label class="mr15"><?php echo form_checkbox("is_final", "1", !empty($model_info->is_final)); ?> Final</label>
        <label class="mr15"><?php echo form_checkbox("is_won", "1", !empty($model_info->is_won)); ?> Ganho</label>
        <label><?php echo form_checkbox("is_lost", "1", !empty($model_info->is_lost)); ?> Perdido</label>
    </div></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal" title="Fechar"><span data-feather="x-circle" class="icon-16"></span> Fechar</button><button type="submit" class="btn btn-primary" title="Salvar"><span data-feather="check-circle" class="icon-16"></span> Salvar</button></div>
<?php echo form_close(); ?>
<script>$(document).ready(function(){ $("#green-status-form").appForm({onSuccess:function(){ if($("#green-statuses-table").length){$("#green-statuses-table").appTable({reload:true});} }}); });</script>
