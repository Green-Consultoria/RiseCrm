<?php
$rows = $rows ?? [];
$has_errors = !empty($has_errors);
$money = function ($value) {
    return $value === null ? "-" : number_format((float) $value, 2, ",", ".");
};
$rate = function ($value) {
    return $value === null ? "-" : rtrim(rtrim(number_format((float) $value, 4, ",", "."), "0"), ",");
};
$date = function ($value) {
    return $value ? date("d/m/Y", strtotime($value)) : "-";
};
?>

<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php if ($has_errors): ?>
            <div class="alert alert-danger">
                Existem erros bloqueantes. Corrija a planilha e gere um novo preview antes de confirmar.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Nenhum dado foi gravado ainda. A importacao so cria registros apos a confirmacao.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Linha</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Operadora original</th>
                        <th>Operadora normalizada</th>
                        <th>Plano original</th>
                        <th>Plano normalizado</th>
                        <th>Valor venda</th>
                        <th>Data venda</th>
                        <th>Implantacao</th>
                        <th>Fidelidade</th>
                        <th>Comissao total</th>
                        <th>Bonus</th>
                        <th>Total legado</th>
                        <th>Parcelas detectadas</th>
                        <th>Total esperado calculado</th>
                        <th>Total legado informado</th>
                        <th>Diferenca</th>
                        <th>Acao prevista</th>
                        <th>Erros</th>
                        <th>Avisos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $row_class = $row["errors"] ? "table-danger" : ($row["warnings"] ? "table-warning" : "");
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo (int) $row["line"]; ?></td>
                            <td><?php echo esc($row["client"]); ?></td>
                            <td><?php echo esc($row["document"]["document_number"] ?: "-"); ?></td>
                            <td><?php echo esc($row["operator_original"] ?: "-"); ?></td>
                            <td><?php echo esc($row["operator_normalized"] ?: "-"); ?></td>
                            <td><?php echo esc($row["plan_original"] ?: "-"); ?></td>
                            <td><?php echo esc($row["plan_normalized"] ?: "-"); ?></td>
                            <td class="text-end"><?php echo $money($row["sale_value"]); ?></td>
                            <td><?php echo $date($row["sale_date"]); ?></td>
                            <td><?php echo $date($row["implantation_date"]); ?></td>
                            <td><?php echo $date($row["fidelity_until"]); ?></td>
                            <td class="text-end"><?php echo $rate($row["commission_total"]); ?></td>
                            <td class="text-end"><?php echo $money($row["bonus_amount"]); ?></td>
                            <td class="text-end"><?php echo $money($row["legacy_total"]); ?></td>
                            <td>
                                <strong><?php echo count($row["schedule"]); ?></strong>
                                <?php if (!empty($row["schedule"])): ?>
                                    <div class="small text-off">
                                        <?php foreach ($row["schedule"] as $item): ?>
                                            <div>
                                                <?php echo esc($item["legacy_month_name"] ?: $item["commission_type"]); ?>:
                                                <?php echo esc($item["commission_type"]); ?>,
                                                perc. <?php echo $rate($item["commission_rate"]); ?>,
                                                esperado <?php echo $money($item["expected_amount"]); ?>,
                                                legado <?php echo $money($item["legacy_amount"]); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?php echo $money($row["total_expected_calculated"]); ?></td>
                            <td class="text-end"><?php echo $money($row["total_legacy_informed"]); ?></td>
                            <td class="text-end"><?php echo $money($row["difference"]); ?></td>
                            <td><?php echo esc($row["action"]); ?></td>
                            <td><?php echo esc(implode("; ", $row["errors"])); ?></td>
                            <td><?php echo esc(implode("; ", $row["warnings"])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="Cancelar"><span data-feather="x-circle" class="icon-16"></span> Cancelar</button>
    <button type="button" id="green-confirm-import" class="btn btn-primary" title="Confirmar importação" <?php echo $has_errors ? "disabled" : ""; ?>>
        <span data-feather="check-circle" class="icon-16"></span> Confirmar importacao
    </button>
</div>
<script>
$(document).ready(function(){
    $("#green-confirm-import").on("click", function(){
        if ($(this).prop("disabled")) {
            return;
        }

        appLoader.show({container: ".modal-dialog"});
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/confirm_import"); ?>",
            type: "POST",
            dataType: "json",
            success: function(result){
                appLoader.hide();
                if(result.report_html){ $(".modal-content").html(result.report_html); feather.replace(); }
                result.success ? appAlert.success(result.message) : appAlert.error(result.message);
            },
            error: function(){ appLoader.hide(); appAlert.error(AppLanugage.somethingWentWrong); }
        });
    });
});
</script>
