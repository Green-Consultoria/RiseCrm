<?php
$recent_sales = $recent_sales ?? [];
$money = function ($value) {
    return "R$ " . number_format((float) $value, 2, ",", ".");
};
$status_badge = function ($status) {
    $classes = [
        "Vendida" => "bg-success",
        "Implantacao pendente" => "bg-warning",
        "Implantada" => "bg-primary",
        "Cancelada" => "bg-danger",
        "Estornada" => "bg-danger"
    ];
    return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($status) . "</span>";
};
?>

<div class="card green-summary-panel">
    <div class="card-header">
        <h4>Vendas recentes</h4>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead>
                <tr>
                    <th>Venda</th>
                    <th>Cliente</th>
                    <th>Operadora</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!count($recent_sales)): ?>
                    <tr><td colspan="5" class="text-center text-off">Nenhuma venda recente.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent_sales as $sale): ?>
                    <tr>
                        <td><?php echo esc($sale->sale_code ?: ("SALE-" . $sale->id)); ?></td>
                        <td><?php echo esc($sale->client_name); ?></td>
                        <td><?php echo esc($sale->operator_name); ?></td>
                        <td><?php echo $money($sale->sale_value); ?></td>
                        <td><?php echo $status_badge($sale->status); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
