<?php echo form_open(get_uri("green_crm/import_preview"), ["id" => "green-import-form", "class" => "general-form", "role" => "form", "enctype" => "multipart/form-data"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group row">
            <label class="col-md-3">Arquivo</label>
            <div class="col-md-9">
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" data-rule-required="true" data-msg-required="<?php echo esc(app_lang("field_required"), "attr"); ?>">
                <span class="help-block">Gera preview antes de gravar. O Excel CRM Vendidos e lido por posicao de coluna, incluindo Comissao % repetida.</span>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="<?php echo app_lang("close"); ?>"><span data-feather="x-circle" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <button type="submit" class="btn btn-primary" title="Gerar preview"><span data-feather="search" class="icon-16"></span> Gerar preview</button>
</div>
<?php echo form_close(); ?>
<script>
$(document).ready(function(){
    $("#green-import-form").appForm({
        onSuccess:function(result){
            if(result.preview_html){ $(".modal-content").html(result.preview_html); feather.replace(); }
        }
    });
});
</script>
