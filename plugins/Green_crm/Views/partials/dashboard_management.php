<?php
$blocks = $dashboard_blocks ?? [];
$money = function ($value) {
    return "R$ " . number_format((float) $value, 2, ",", ".");
};
$date = function ($value, $with_time = false) {
    if (!$value || $value === "0000-00-00" || $value === "0000-00-00 00:00:00") {
        return "-";
    }

    return date($with_time ? "d/m/Y H:i" : "d/m/Y", strtotime($value));
};
$month = function ($month, $year) {
    return sprintf("%02d/%04d", (int) $month, (int) $year);
};
$panel_open = function ($title, $icon = "bar-chart-2") {
    return "<div class='green-crm-section-panel'><div class='green-crm-section-panel-title'><h4><i data-feather='" . esc($icon, "attr") . "' class='icon-16'></i> " . esc($title) . "</h4></div>";
};
$panel_close = "</div>";
$empty = function ($colspan, $message) {
    return "<tr><td colspan='" . (int) $colspan . "' class='text-center text-off'>" . esc($message) . "</td></tr>";
};
?>

<div class="row mt15">
    <div class="col-lg-6">
        <?php echo $panel_open("Leads por status", "columns"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Status</th><th class="text-end">Leads</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["funnel_by_status"])): ?>
                            <?php echo $empty(2, "Nenhum lead no periodo."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["funnel_by_status"] ?? []) as $row): ?>
                            <tr><td data-green-label="Status"><?php echo esc($row->label); ?></td><td data-green-label="Leads" class="text-end"><?php echo (int) $row->total; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Leads por origem", "share-2"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Origem</th><th class="text-end">Leads</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["leads_by_source"])): ?>
                            <?php echo $empty(2, "Nenhum lead por origem."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["leads_by_source"] ?? []) as $row): ?>
                            <tr><td data-green-label="Origem"><?php echo esc($row->label); ?></td><td data-green-label="Leads" class="text-end"><?php echo (int) $row->total; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Vendas por operadora", "shopping-cart"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Operadora</th><th class="text-end">Vendas</th><th class="text-end">Valor</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["sales_by_operator"])): ?>
                            <?php echo $empty(3, "Nenhuma venda por operadora."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["sales_by_operator"] ?? []) as $row): ?>
                            <tr><td data-green-label="Operadora"><?php echo esc($row->label); ?></td><td data-green-label="Vendas" class="text-end"><?php echo (int) $row->total_sales; ?></td><td data-green-label="Valor" class="text-end"><?php echo $money($row->total_value); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Comissão por competência", "dollar-sign"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Competencia</th><th class="text-end">Prevista</th><th class="text-end">Recebida</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["commission_by_competence"])): ?>
                            <?php echo $empty(3, "Nenhuma comissao no filtro."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["commission_by_competence"] ?? []) as $row): ?>
                            <tr><td data-green-label="Competencia"><?php echo $month($row->due_month, $row->due_year); ?></td><td data-green-label="Prevista" class="text-end"><?php echo $money($row->expected_amount); ?></td><td data-green-label="Recebida" class="text-end"><?php echo $money($row->received_amount); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Comissões por parceiro/grade", "award"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Grade</th><th>Parceiro</th><th class="text-end">Prevista</th><th class="text-end">Recebida</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["commission_by_partner"])): ?>
                            <?php echo $empty(4, "Nenhuma comissao por parceiro."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["commission_by_partner"] ?? []) as $row): ?>
                            <tr><td data-green-label="Grade"><?php echo esc($row->label); ?></td><td data-green-label="Parceiro"><?php echo esc($row->partner_name ?: "-"); ?></td><td data-green-label="Prevista" class="text-end"><?php echo $money($row->expected_amount); ?></td><td data-green-label="Recebida" class="text-end"><?php echo $money($row->received_amount); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Comissões por operadora", "briefcase"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Operadora</th><th class="text-end">Prevista</th><th class="text-end">Recebida</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["commission_by_operator"])): ?>
                            <?php echo $empty(3, "Nenhuma comissao por operadora."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["commission_by_operator"] ?? []) as $row): ?>
                            <tr><td data-green-label="Operadora"><?php echo esc($row->label); ?></td><td data-green-label="Prevista" class="text-end"><?php echo $money($row->expected_amount); ?></td><td data-green-label="Recebida" class="text-end"><?php echo $money($row->received_amount); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Implantações pendentes", "clipboard"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Venda</th><th>Cliente</th><th>Operadora</th><th>Progresso</th><th class="text-center">Acao</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["pending_implantations"])): ?>
                            <?php echo $empty(5, "Nenhuma implantacao pendente."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["pending_implantations"] ?? []) as $row): ?>
                            <tr>
                                <td data-green-label="Venda"><?php echo esc($row->sale_code ?: ("SALE-" . $row->id)); ?></td>
                                <td data-green-label="Cliente"><?php echo esc($row->client_name); ?></td>
                                <td data-green-label="Operadora"><?php echo esc($row->operator_name ?: "-"); ?></td>
                                <td data-green-label="Progresso"><?php echo (int) $row->completed_items . "/" . (int) $row->total_items; ?></td>
                                <td data-green-label="Acao" class="text-center"><?php echo modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir venda", "data-post-id" => $row->id]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-6">
        <?php echo $panel_open("Reajustes próximos", "refresh-cw"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Cliente</th><th>Operadora</th><th>Fidelidade</th><th>Follow-up</th><th class="text-center">Acao</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["upcoming_renewals"])): ?>
                            <?php echo $empty(5, "Nenhum reajuste proximo."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["upcoming_renewals"] ?? []) as $row): ?>
                            <tr>
                                <td data-green-label="Cliente"><?php echo esc($row->client_name); ?></td>
                                <td data-green-label="Operadora"><?php echo esc($row->operator_name ?: "-"); ?></td>
                                <td data-green-label="Fidelidade"><?php echo $date($row->fidelity_until); ?></td>
                                <td data-green-label="Follow-up"><?php echo $date($row->next_followup_at, true); ?></td>
                                <td data-green-label="Acao" class="text-center"><?php echo $row->lead_id ? anchor(get_uri("green_crm/lead/" . $row->lead_id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead"]) : "<span class='text-off'>-</span>"; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>

    <div class="col-lg-12">
        <?php echo $panel_open("Tarefas atrasadas", "alert-triangle"); ?>
            <div class="table-responsive">
                <table class="table table-hover mb0 green-crm-table">
                    <thead><tr><th>Tarefa</th><th>Cliente</th><th>Vencimento</th><th>Responsavel</th><th class="text-center">Acao</th></tr></thead>
                    <tbody>
                        <?php if (empty($blocks["overdue_tasks"])): ?>
                            <?php echo $empty(5, "Nenhuma tarefa atrasada."); ?>
                        <?php endif; ?>
                        <?php foreach (($blocks["overdue_tasks"] ?? []) as $row): ?>
                            <tr>
                                <td data-green-label="Tarefa"><?php echo esc($row->title); ?></td>
                                <td data-green-label="Cliente"><?php echo esc($row->client_name); ?></td>
                                <td data-green-label="Vencimento"><?php echo $date($row->due_date, true); ?></td>
                                <td data-green-label="Responsavel"><?php echo esc($row->responsible_name ?: "-"); ?></td>
                                <td data-green-label="Acao" class="text-center"><?php echo anchor(get_uri("green_crm/lead/" . $row->lead_id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead"]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php echo $panel_close; ?>
    </div>
</div>
