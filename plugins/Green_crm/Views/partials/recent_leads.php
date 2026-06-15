<?php
$recent_leads = $recent_leads ?? [];
$temperature_badge = function ($temperature) {
    $classes = ["quente" => "bg-danger", "morno" => "bg-warning", "frio" => "bg-info", "sem_classificacao" => "bg-secondary"];
    $labels = ["quente" => "Quente", "morno" => "Morno", "frio" => "Frio", "sem_classificacao" => "Sem classificacao"];
    $key = $temperature ?: "sem_classificacao";
    return "<span class='badge " . ($classes[$key] ?? "bg-secondary") . "'>" . esc($labels[$key] ?? $key) . "</span>";
};
?>

<div class="card green-summary-panel">
    <div class="card-header">
        <h4>Leads recentes</h4>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Temperatura</th>
                    <th>Operadora</th>
                    <th class="text-center">Acao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!count($recent_leads)): ?>
                    <tr><td colspan="6" class="text-center text-off">Nenhum lead recente.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent_leads as $lead): ?>
                    <tr>
                        <td><?php echo esc($lead->lead_code ?: ("LEAD-" . $lead->id)); ?></td>
                        <td><?php echo esc($lead->client_name); ?></td>
                        <td><span class="badge bg-secondary"><?php echo esc($lead->status_title ?: "Novo"); ?></span></td>
                        <td><?php echo $temperature_badge($lead->temperature); ?></td>
                        <td><?php echo esc($lead->operator_name); ?></td>
                        <td class="text-center"><?php echo modal_anchor(get_uri("green_crm/lead_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar lead", "data-post-id" => $lead->id]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
