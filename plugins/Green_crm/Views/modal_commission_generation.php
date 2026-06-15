<?php
$sale = $sale ?? new \stdClass();
$installments = $installments ?? [];
$sale_value = (float) ($sale->sale_value ?? 0);
$base_date = !empty($sale->sale_date) ? new \DateTime($sale->sale_date) : new \DateTime();
$default_rates = [1, 1, 0.8];
$month_options = [
    1 => "Janeiro",
    2 => "Fevereiro",
    3 => "Março",
    4 => "Abril",
    5 => "Maio",
    6 => "Junho",
    7 => "Julho",
    8 => "Agosto",
    9 => "Setembro",
    10 => "Outubro",
    11 => "Novembro",
    12 => "Dezembro"
];
$current_year = (int) date("Y");
$type_options = ["comissao" => "Comissão", "bonus" => "Bônus", "ajuste" => "Ajuste", "estorno" => "Estorno"];
$money = function ($value) {
    return number_format((float) $value, 2, ",", ".");
};
?>

<?php echo form_open(get_uri("green_crm/generate_commissions"), ["id" => "green-commission-generation-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="sale_id" value="<?php echo (int) ($sale->id ?? 0); ?>">
        <input type="hidden" id="green-commission-sale-value" value="<?php echo $sale_value; ?>">

        <div class="mb15">
            <strong><?php echo esc($sale->sale_code ?: ("SALE-" . ($sale->id ?? ""))); ?></strong>
            <span class="text-muted"> - <?php echo esc($sale->client_name ?? ""); ?></span>
            <div>Valor da venda: R$ <?php echo $money($sale_value); ?></div>
        </div>

        <?php if (count($installments)): ?>
            <div class="alert alert-warning">
                Parcelas previstas ou a receber desta venda serão canceladas por status ao gerar uma nova agenda. Parcelas recebidas ou parciais não serão apagadas.
            </div>
        <?php endif; ?>

        <div class="row mb15">
            <label class="col-md-3">Multiplicador total</label>
            <div class="col-md-3">
                <?php echo form_input(["id" => "green-commission-multiplier", "class" => "form-control", "value" => "2,8"]); ?>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-default" id="green-generate-standard-commissions" title="Gerar padrão por multiplicador">
                    <i data-feather="repeat" class="icon-16"></i> Gerar padrão por multiplicador
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="green-commission-schedule-table">
                <thead>
                    <tr>
                        <th class="w80">Parcela</th>
                        <th>Competência</th>
                        <th class="w110">Ano</th>
                        <th class="w130">Percentual</th>
                        <th class="w150">Valor esperado</th>
                        <th>Tipo</th>
                        <th>Observação</th>
                        <th class="text-center w60"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($default_rates as $index => $rate): ?>
                        <?php
                        $due_date = clone $base_date;
                        $due_date->modify("+{$index} month");
                        ?>
                        <tr class="green-commission-row">
                            <td><?php echo form_input(["name" => "installment_no[]", "type" => "number", "value" => $index + 1, "class" => "form-control green-commission-installment-no", "min" => 1]); ?></td>
                            <td><?php echo form_dropdown("due_month[]", $month_options, (int) $due_date->format("m"), ["class" => "form-control green-commission-month"]); ?></td>
                            <td><?php echo form_input(["name" => "due_year[]", "type" => "number", "value" => $due_date->format("Y"), "class" => "form-control green-commission-year", "min" => $current_year - 2, "max" => $current_year + 5]); ?></td>
                            <td><?php echo form_input(["name" => "commission_rate[]", "value" => $rate, "class" => "form-control green-commission-rate"]); ?></td>
                            <td><?php echo form_input(["name" => "expected_amount[]", "value" => $money($sale_value * $rate), "class" => "form-control green-commission-expected"]); ?></td>
                            <td><?php echo form_dropdown("commission_type[]", $type_options, "comissao", ["class" => "form-control green-commission-type"]); ?></td>
                            <td><?php echo form_input(["name" => "notes[]", "class" => "form-control green-commission-notes"]); ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-default btn-sm green-remove-commission-row" title="Remover parcela"><i data-feather="x-circle" class="icon-16"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-default btn-sm" id="green-add-commission-row" title="Adicionar parcela">
            <i data-feather="plus-circle" class="icon-16"></i> Adicionar parcela
        </button>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal" title="Fechar"><span data-feather="x-circle" class="icon-16"></span> Fechar</button>
    <button type="submit" class="btn btn-primary" title="Gerar comissões"><span data-feather="check-circle" class="icon-16"></span> Gerar comissões</button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
$(document).ready(function () {
    var saleValue = parseFloat($("#green-commission-sale-value").val()) || 0;
    var baseMonth = <?php echo (int) $base_date->format("m"); ?>;
    var baseYear = <?php echo (int) $base_date->format("Y"); ?>;

    var money = function (value) {
        return (value || 0).toFixed(2).replace(".", ",");
    };

    var parseNumber = function (value) {
        value = (value || "").toString().replace("R$", "").replace(/\s/g, "");
        if (value.indexOf(",") !== -1 && value.indexOf(".") !== -1) {
            value = value.replace(/\./g, "").replace(",", ".");
        } else {
            value = value.replace(",", ".");
        }
        var number = parseFloat(value);
        return isNaN(number) ? 0 : number;
    };

    var addMonths = function (offset) {
        var month = baseMonth + offset;
        var year = baseYear;
        while (month > 12) {
            month -= 12;
            year++;
        }
        return {month: month, year: year};
    };

    var refreshRow = function (row) {
        var rate = parseNumber(row.find(".green-commission-rate").val());
        if (row.find(".green-commission-type").val() === "comissao") {
            row.find(".green-commission-expected").val(money(saleValue * rate));
        }
    };

    var bindRow = function (row) {
        row.find(".green-commission-rate, .green-commission-type").off("input.greenCommission change.greenCommission").on("input.greenCommission change.greenCommission", function () {
            refreshRow(row);
        });
    };

    var appendRow = function (index, rate, type, notes) {
        var row = $("#green-commission-schedule-table tbody tr:first").clone();
        var due = addMonths(index);
        row.find(".green-commission-installment-no").val(index + 1);
        row.find(".green-commission-month").val(due.month);
        row.find(".green-commission-year").val(due.year);
        row.find(".green-commission-rate").val(rate);
        row.find(".green-commission-expected").val(money(saleValue * parseNumber(rate)));
        row.find(".green-commission-type").val(type || "comissao");
        row.find(".green-commission-notes").val(notes || "");
        $("#green-commission-schedule-table tbody").append(row);
        bindRow(row);
    };

    var ratesFromMultiplier = function (multiplier) {
        var remaining = Math.max(0, parseNumber(multiplier));
        var rates = [];
        while (remaining > 0.0001 && rates.length < 2) {
            var firstChunk = Math.min(1, remaining);
            rates.push(firstChunk);
            remaining = Math.round((remaining - firstChunk) * 100) / 100;
        }

        if (remaining > 1) {
            while (remaining > 0.0001) {
                var chunk = remaining > 0.5 ? 0.5 : remaining;
                rates.push(Math.round(chunk * 100) / 100);
                remaining = Math.round((remaining - chunk) * 100) / 100;
            }
        } else if (remaining > 0.0001) {
            rates.push(Math.round(remaining * 100) / 100);
        }

        return rates;
    };

    $("#green-commission-schedule-table tbody tr").each(function () {
        bindRow($(this));
    });

    $("#green-add-commission-row").on("click", function () {
        appendRow($("#green-commission-schedule-table tbody tr").length, 1, "comissao", "");
        if (window.feather) {
            feather.replace();
        }
    });

    $("#green-generate-standard-commissions").on("click", function () {
        var rates = ratesFromMultiplier($("#green-commission-multiplier").val());
        var tbody = $("#green-commission-schedule-table tbody");
        tbody.empty();
        rates.forEach(function (rate, index) {
            appendRow(index, rate, "comissao", "");
        });
        if (window.feather) {
            feather.replace();
        }
    });

    $("body").on("click", ".green-remove-commission-row", function () {
        if ($("#green-commission-schedule-table tbody tr").length > 1) {
            $(this).closest("tr").remove();
        }
    });

    $("#green-commission-generation-form").appForm({
        onSuccess: function () {
            if ($("#green-sales-table").length) {
                $("#green-sales-table").appTable({reload: true});
            }
            if ($("#green-commissions-table").length) {
                $("#green-commissions-table").appTable({reload: true});
            }
        }
    });
});
</script>
