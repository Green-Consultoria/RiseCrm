<?php
$pending_commissions = $pending_commissions ?? [];
$money = function ($value) {
    return "R$ " . number_format((float) $value, 2, ",", ".");
};
$status_badge = function ($status) {
    $classes = [
        "Previsto" => "bg-secondary",
        "A receber" => "bg-warning",
        "Parcial" => "bg-info"
    ];
    return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($status) . "</span>";
};
?>

<div class="card green-summary-panel">
    <div class="card-header">
        <h4>Comissoes a receber</h4>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead>
                <tr>
                    <th>Competencia</th>
                    <th>Cliente</th>
                    <th>Venda</th>
                    <th>Esperado</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!count($pending_commissions)): ?>
                    <tr><td colspan="5" class="text-center text-off">Nenhuma comissao em aberto.</td></tr>
                <?php endif; ?>
                <?php foreach ($pending_commissions as $commission): ?>
                    <tr>
                        <td><?php echo sprintf("%02d/%04d", (int) $commission->due_month, (int) $commission->due_year); ?></td>
                        <td><?php echo esc($commission->client_name); ?></td>
                        <td><?php echo esc($commission->sale_code); ?></td>
                        <td><?php echo $money($commission->expected_amount); ?></td>
                        <td><?php echo $status_badge($commission->status); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
