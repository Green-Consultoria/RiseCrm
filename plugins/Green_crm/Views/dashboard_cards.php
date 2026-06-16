<?php
$dashboard = $dashboard ?? [];
$money = function ($value) {
    return "R$ " . number_format((float) $value, 2, ",", ".");
};
$percent = function ($value) {
    return number_format((float) $value, 2, ",", ".") . "%";
};
$cards = [
    ["label" => "Leads no período", "value" => (int) ($dashboard["total_leads"] ?? 0), "icon" => "users", "class" => "bg-primary"],
    ["label" => "Leads novos", "value" => (int) ($dashboard["leads_novos"] ?? 0), "icon" => "user-plus", "class" => "bg-info"],
    ["label" => "Leads quentes", "value" => (int) ($dashboard["leads_quentes"] ?? 0), "icon" => "thermometer", "class" => "bg-danger"],
    ["label" => "Leads sem contato", "value" => (int) ($dashboard["leads_sem_contato"] ?? 0), "icon" => "phone-off", "class" => "bg-warning"],
    ["label" => "Leads qualificados", "value" => (int) ($dashboard["leads_qualificados"] ?? 0), "icon" => "check-circle", "class" => "bg-success"],
    ["label" => "Tarefas para hoje", "value" => (int) ($dashboard["tasks_today"] ?? 0), "icon" => "calendar", "class" => "bg-info"],
    ["label" => "Meta Ads processados", "value" => (int) ($dashboard["meta_processed"] ?? 0), "icon" => "facebook", "class" => "bg-primary"],
    ["label" => "Meta Ads com erro", "value" => (int) ($dashboard["meta_errors"] ?? 0), "icon" => "alert-octagon", "class" => "bg-danger"],
    ["label" => "Propostas enviadas", "value" => (int) ($dashboard["proposals_sent"] ?? 0), "icon" => "file-text", "class" => "bg-info"],
    ["label" => "Vendas fechadas", "value" => (int) ($dashboard["total_sales"] ?? 0), "icon" => "shopping-cart", "class" => "bg-success"],
    ["label" => "Taxa lead para venda", "value" => $percent($dashboard["conversion_rate"] ?? 0), "icon" => "percent", "class" => "bg-info"],
    ["label" => "Valor vendido", "value" => $money($dashboard["total_sale_value"] ?? 0), "icon" => "trending-up", "class" => "bg-success"],
    ["label" => "Ticket medio", "value" => $money($dashboard["average_ticket"] ?? 0), "icon" => "credit-card", "class" => "bg-info"],
    ["label" => "Comissao prevista", "value" => $money($dashboard["commission_expected"] ?? 0), "icon" => "dollar-sign", "class" => "bg-primary"],
    ["label" => "Comissao recebida", "value" => $money($dashboard["commission_received"] ?? 0), "icon" => "check-square", "class" => "bg-success"],
    ["label" => "Comissao em aberto", "value" => $money($dashboard["commission_open"] ?? 0), "icon" => "clock", "class" => "bg-warning"],
    ["label" => "Comissao vencida", "value" => $money($dashboard["commission_overdue"] ?? 0), "icon" => "alert-triangle", "class" => "bg-danger"],
    ["label" => "Diferença previsto x recebido", "value" => $money($dashboard["commission_difference"] ?? 0), "icon" => "activity", "class" => "bg-info"],
    ["label" => "Implantações pendentes", "value" => (int) ($dashboard["pending_implantations"] ?? 0), "icon" => "alert-circle", "class" => "bg-warning"],
    ["label" => "Reajustes próximos", "value" => (int) ($dashboard["upcoming_renewals"] ?? 0), "icon" => "refresh-cw", "class" => "bg-info"],
    ["label" => "Tarefas atrasadas", "value" => (int) ($dashboard["overdue_tasks"] ?? 0), "icon" => "alert-triangle", "class" => "bg-danger"]
];
?>

<div class="row green-crm-kpi-list">
    <?php foreach ($cards as $card): ?>
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card dashboard-icon-widget green-crm-kpi-widget">
                <div class="card-body green-crm-kpi-content">
                    <div class="widget-icon <?php echo $card["class"]; ?>"><i data-feather="<?php echo $card["icon"]; ?>" class="icon"></i></div>
                    <div class="widget-details">
                        <h1><?php echo esc((string) $card["value"]); ?></h1>
                        <span><?php echo esc($card["label"]); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
