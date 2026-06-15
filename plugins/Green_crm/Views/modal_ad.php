<?php
$type = $type ?? "campaign";
$model_info = $model_info ?? new \stdClass();
$campaigns_dropdown = $campaigns_dropdown ?? ["" => "- Campanha -"];
$adsets_dropdown = $adsets_dropdown ?? ["" => "- Conjunto -"];
$id = (int) ($model_info->id ?? 0);

$type_labels = ["campaign" => "Campanha", "adset" => "Conjunto", "ad" => "Anúncio/Criativo"];
$status_options = ["unknown" => "—", "active" => "Ativo", "paused" => "Pausado", "archived" => "Arquivado"];
?>

<?php echo form_open(get_uri("green_crm/save_ad"), ["id" => "green-ad-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="type" value="<?php echo $type; ?>">

        <div class="form-group row">
            <label class="col-md-3"><?php echo $type_labels[$type]; ?></label>
            <div class="col-md-9"><?php echo form_input(["name" => "name", "value" => $model_info->name ?? "", "class" => "form-control", "data-rule-required" => true, "data-msg-required" => app_lang("field_required")]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">ID externo (Meta)</label>
            <div class="col-md-5"><?php echo form_input(["name" => "external_id", "value" => $model_info->external_id ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-2">Status</label>
            <div class="col-md-2"><?php echo form_dropdown("status", $status_options, $model_info->status ?? "unknown", ["class" => "form-control select2"]); ?></div>
        </div>

        <?php if ($type === "adset" || $type === "ad") { ?>
            <div class="form-group row">
                <label class="col-md-3">Campanha</label>
                <div class="col-md-9"><?php echo form_dropdown("campaign_id", $campaigns_dropdown, $model_info->campaign_id ?? "", ["class" => "form-control select2"]); ?></div>
            </div>
        <?php } ?>
        <?php if ($type === "ad") { ?>
            <div class="form-group row">
                <label class="col-md-3">Conjunto</label>
                <div class="col-md-9"><?php echo form_dropdown("adset_id", $adsets_dropdown, $model_info->adset_id ?? "", ["class" => "form-control select2"]); ?></div>
            </div>
            <div class="form-group row">
                <label class="col-md-3">Thumb do criativo (URL)</label>
                <div class="col-md-9"><?php echo form_input(["name" => "creative_thumb_url", "value" => $model_info->creative_thumb_url ?? "", "class" => "form-control"]); ?></div>
            </div>
        <?php } ?>

        <h5 class="mt15">Métricas (preenchimento manual / futura API)</h5>
        <div class="form-group row">
            <label class="col-md-3">Investimento (R$)</label>
            <div class="col-md-3"><?php echo form_input(["name" => "spend", "value" => $model_info->spend ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-3">Leads</label>
            <div class="col-md-3"><?php echo form_input(["name" => "leads", "type" => "number", "value" => $model_info->leads ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Impressões</label>
            <div class="col-md-3"><?php echo form_input(["name" => "impressions", "type" => "number", "value" => $model_info->impressions ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-3">Alcance</label>
            <div class="col-md-3"><?php echo form_input(["name" => "reach", "type" => "number", "value" => $model_info->reach ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Cliques</label>
            <div class="col-md-3"><?php echo form_input(["name" => "clicks", "type" => "number", "value" => $model_info->clicks ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-3">CPL (R$)</label>
            <div class="col-md-3"><?php echo form_input(["name" => "cpl", "value" => $model_info->cpl ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">CTR (%)</label>
            <div class="col-md-3"><?php echo form_input(["name" => "ctr", "value" => $model_info->ctr ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-3">Vendas vinculadas</label>
            <div class="col-md-3"><?php echo form_input(["name" => "sales_count", "type" => "number", "value" => $model_info->sales_count ?? "", "class" => "form-control"]); ?></div>
        </div>
        <div class="form-group row">
            <label class="col-md-3">Comissão prevista (R$)</label>
            <div class="col-md-3"><?php echo form_input(["name" => "expected_commission", "value" => $model_info->expected_commission ?? "", "class" => "form-control"]); ?></div>
            <label class="col-md-3">ROI</label>
            <div class="col-md-3"><?php echo form_input(["name" => "roi", "value" => $model_info->roi ?? "", "class" => "form-control"]); ?></div>
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
    $("#green-ad-form .select2").select2();
    $("#green-ad-form").appForm({
        onSuccess: function () {
            $(".green-ad-table").each(function () {
                if ($.fn.DataTable.isDataTable("#" + this.id)) {
                    $("#" + this.id).appTable({reload: true});
                }
            });
        }
    });
});
</script>
