<?php
$quote = $quote ?? null;
$lead = $lead ?? null;
$options = $options ?? [];

$money = function ($value) {
    return $value !== null && $value !== "" ? "R$ " . number_format((float) $value, 2, ",", ".") : "-";
};

$percent = function ($value) {
    return $value !== null && $value !== "" ? number_format((float) $value, 2, ",", ".") . "%" : "-";
};

$date = function ($value) {
    if (!$value || $value === "0000-00-00") {
        return "-";
    }
    return date("d/m/Y", strtotime($value));
};

$text = function ($value) {
    return $value !== null && $value !== "" ? esc($value) : "-";
};

$status_badge = function ($status) {
    $classes = [
        "Rascunho" => "bg-secondary",
        "Enviada" => "bg-primary",
        "Aceita" => "bg-success",
        "Recusada" => "bg-danger",
        "Vencida" => "bg-warning",
        "Cancelada" => "bg-danger"
    ];

    return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($status ?: "-") . "</span>";
};
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div id="green-quote-comparison-page" class="card">
        <?php if (!$quote): ?>
            <div class="card-body">
                <div class="alert alert-danger">Cotação não encontrada.</div>
                <?php echo anchor(get_uri("green_crm/quotes"), "<i data-feather='arrow-left' class='icon-16'></i> Voltar para cotações", ["class" => "btn btn-default", "title" => "Voltar para cotações"]); ?>
            </div>
        <?php else: ?>
            <div class="page-title clearfix green-crm-page-header">
                <h1><?php echo esc($quote->quote_code ?: ("Cotação #" . $quote->id)); ?></h1>
                <div class="title-button-group green-crm-title-actions">
                    <?php echo anchor(get_uri("green_crm/quotes"), "<i data-feather='arrow-left' class='icon-16'></i> Cotações", ["class" => "btn btn-default", "title" => "Voltar para cotações"]); ?>
                    <?php echo modal_anchor(get_uri("green_crm/quote_option_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar opção", ["class" => "btn btn-primary", "title" => "Adicionar opção", "data-post-quote_id" => $quote->id]); ?>
                    <?php echo js_anchor("<i data-feather='send' class='icon-16'></i> Marcar enviada", ["class" => "btn btn-default green-send-quote-detail", "data-id" => $quote->id, "title" => "Marcar cotação como enviada"]); ?>
                    <?php echo js_anchor("<i data-feather='check-circle' class='icon-16'></i> Marcar aceita", ["class" => "btn btn-default green-accept-quote-detail", "data-id" => $quote->id, "title" => "Marcar cotação como aceita"]); ?>
                    <?php echo js_anchor("<i data-feather='shopping-cart' class='icon-16'></i> Converter opção selecionada em venda", ["class" => "btn btn-success green-convert-selected-quote", "data-id" => $quote->id, "title" => "Converter opção selecionada em venda"]); ?>
                </div>
            </div>

            <div class="p20 green-crm-content-panel border-bottom">
                <div class="row">
                    <div class="col-md-3"><strong>Cliente</strong><br><?php echo $text($quote->client_name); ?></div>
                    <div class="col-md-2"><strong>Lead</strong><br><?php echo anchor(get_uri("green_crm/lead/" . $quote->lead_id), esc($quote->lead_code ?: ("LEAD-" . $quote->lead_id))); ?></div>
                    <div class="col-md-2"><strong>Status</strong><br><?php echo $status_badge($quote->status); ?></div>
                    <div class="col-md-2"><strong>Válida até</strong><br><?php echo $date($quote->valid_until); ?></div>
                    <div class="col-md-3"><strong>Valor pago atual</strong><br><?php echo $money($quote->current_paid_value); ?></div>
                </div>
            </div>

            <div class="p20 green-crm-content-panel">
                <?php if (!count($options)): ?>
                    <div class="alert alert-warning">Nenhuma opção cadastrada para comparar.</div>
                <?php else: ?>
                    <div class="green-quote-option-grid">
                        <?php foreach ($options as $option): ?>
                            <?php
                            $plan_name = $option->plan_registered_name ?: $option->plan_name;
                            $accommodation = $option->accommodation ?: $option->plan_accommodation;
                            ?>
                            <div class="green-quote-option-card p15 <?php echo (int) $option->is_selected ? "is-selected" : ""; ?>">
                                <div class="d-flex justify-content-between align-items-start mb10">
                                    <div>
                                        <h4 class="mb5"><?php echo $text($option->operator_name); ?></h4>
                                        <div><?php echo $text($plan_name); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <?php if ((int) $option->is_selected): ?>
                                            <span class="badge bg-success">Selecionada</span>
                                        <?php endif; ?>
                                        <?php if ($option->highlight_label): ?>
                                            <span class="badge bg-info"><?php echo esc($option->highlight_label); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="green-option-row"><span>Valor mensal</span><strong><?php echo $money($option->monthly_value); ?></strong></div>
                                <div class="green-option-row"><span>Economia</span><strong><?php echo $money($option->economy_amount); ?></strong></div>
                                <div class="green-option-row"><span>% economia</span><strong><?php echo $percent($option->economy_percent); ?></strong></div>
                                <div class="green-option-row"><span>Coparticipação</span><strong><?php echo (int) $option->coparticipation ? "Sim" : "Não"; ?></strong></div>
                                <div class="green-option-row"><span>Acomodação</span><strong><?php echo $text($accommodation); ?></strong></div>
                                <div class="green-option-row"><span>Hospital preferido</span><strong><?php echo (int) $option->hospital_match ? "Atende" : "Não informado"; ?></strong></div>

                                <div class="mt10">
                                    <strong>Rede/hospitais</strong>
                                    <div><?php echo nl2br(esc($option->network_notes ?: "-")); ?></div>
                                </div>
                                <div class="mt10">
                                    <strong>Pontos positivos</strong>
                                    <div><?php echo nl2br(esc($option->pros ?: "-")); ?></div>
                                </div>
                                <div class="mt10 mb15">
                                    <strong>Pontos negativos</strong>
                                    <div><?php echo nl2br(esc($option->cons ?: "-")); ?></div>
                                </div>

                                <div class="green-quote-option-actions">
                                    <?php echo js_anchor("<i data-feather='check-circle' class='icon-16'></i> Selecionar", ["class" => "btn btn-default btn-sm green-select-quote-option", "data-id" => $option->id, "title" => "Marcar opção como selecionada"]); ?>
                                    <?php echo modal_anchor(get_uri("green_crm/quote_option_modal_form"), "<i data-feather='edit' class='icon-16'></i> Editar", ["class" => "btn btn-default btn-sm", "title" => "Editar opção", "data-post-id" => $option->id, "data-post-quote_id" => $quote->id]); ?>
                                    <?php echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-delete-quote-option", "data-id" => $option->id, "title" => "Excluir opção"]); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function () {
    var reloadPage = function () {
        location.reload();
    };

    $("body").on("click", ".green-select-quote-option", function () {
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/select_quote_option"); ?>",
            type: "POST",
            dataType: "json",
            data: {id: $(this).data("id")},
            success: function (result) {
                result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                if (result.success) {
                    reloadPage();
                }
            }
        });
    });

    $("body").on("click", ".green-delete-quote-option", function () {
        var id = $(this).data("id");
        $(this).appConfirmation({
            title: "Excluir opção?",
            btnConfirmLabel: "Excluir",
            onConfirm: function () {
                appAjaxRequest({
                    url: "<?php echo_uri("green_crm/delete_quote_option"); ?>",
                    type: "POST",
                    dataType: "json",
                    data: {id: id},
                    success: function (result) {
                        result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                        if (result.success) {
                            reloadPage();
                        }
                    }
                });
            }
        });
    });

    $("body").on("click", ".green-send-quote-detail", function () {
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/send_quote"); ?>",
            type: "POST",
            dataType: "json",
            data: {id: $(this).data("id")},
            success: function (result) {
                result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                if (result.success) {
                    reloadPage();
                }
            }
        });
    });

    $("body").on("click", ".green-accept-quote-detail", function () {
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/accept_quote"); ?>",
            type: "POST",
            dataType: "json",
            data: {id: $(this).data("id")},
            success: function (result) {
                result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                if (result.success) {
                    reloadPage();
                }
            }
        });
    });

    $("body").on("click", ".green-convert-selected-quote", function () {
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/convert_selected_quote_option_to_sale"); ?>",
            type: "POST",
            dataType: "json",
            data: {id: $(this).data("id")},
            success: function (result) {
                result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                if (result.success) {
                    window.location.href = "<?php echo get_uri("green_crm/sales"); ?>";
                }
            }
        });
    });

    if (window.feather) {
        feather.replace();
    }
});
</script>
