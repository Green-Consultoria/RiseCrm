<?php
$sale = $sale ?? null;
$checklist_items = $checklist_items ?? [];
$progress = $progress ?? (object) ["completed_items" => 0, "total_items" => 9];
$sale_id = (int) ($sale->id ?? 0);
$completed = (int) ($progress->completed_items ?? 0);
$total = (int) ($progress->total_items ?? 9);

$status_options = [
    "pendente" => "Pendente",
    "concluido" => "Concluído",
    "nao_aplica" => "Não se aplica"
];

$format_date_time = function ($value) {
    if (!$value || $value === "0000-00-00 00:00:00") {
        return "-";
    }

    return date("d/m/Y H:i", strtotime($value));
};
?>

<div class="green-implantation-checklist" data-sale-id="<?php echo $sale_id; ?>">
    <div class="d-flex justify-content-between align-items-center mb10">
        <strong>Checklist de implantação</strong>
        <span class="badge bg-info"><?php echo $completed . "/" . $total; ?> concluído</span>
    </div>

    <?php if (!$sale): ?>
        <div class="alert alert-warning">Venda não encontrada.</div>
    <?php elseif (!count($checklist_items)): ?>
        <div class="alert alert-warning">Checklist ainda não gerado para esta venda.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered mb0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="w180">Status</th>
                        <th>Nota</th>
                        <th class="w200">Conclusão</th>
                        <th class="text-center w100">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checklist_items as $item): ?>
                        <tr data-item-id="<?php echo (int) $item->id; ?>">
                            <td>
                                <strong><?php echo esc($item->title); ?></strong>
                                <div class="text-off"><?php echo esc($item->item_key); ?></div>
                            </td>
                            <td>
                                <?php echo form_dropdown("", $status_options, $item->status, ["class" => "form-control green-implantation-status"]); ?>
                            </td>
                            <td>
                                <?php echo form_textarea(["class" => "form-control green-implantation-notes", "rows" => 2, "value" => $item->notes ?? ""]); ?>
                            </td>
                            <td>
                                <?php if ($item->status === "concluido"): ?>
                                    <?php echo $format_date_time($item->completed_at); ?><br>
                                    <span class="text-off"><?php echo esc($item->completed_by_name ?: "-"); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php echo js_anchor("<i data-feather='check-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-save-implantation-item", "data-id" => $item->id, "title" => "Salvar item"]); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
