<?php
$statuses = $statuses ?? [];
$leads_by_status = $leads_by_status ?? [];

$money = function ($value) {
    return $value !== null && $value !== "" ? "R$ " . number_format((float) $value, 2, ",", ".") : "-";
};

$date = function ($value) {
    if (!$value || $value === "0000-00-00" || $value === "0000-00-00 00:00:00") {
        return "-";
    }

    return date("d/m/Y", strtotime($value));
};

$temperature_label = function ($temperature) {
    $labels = [
        "quente" => "Quente",
        "morno" => "Morno",
        "frio" => "Frio",
        "sem_classificacao" => "Sem classificação"
    ];

    return $labels[$temperature ?: "sem_classificacao"] ?? $temperature;
};

$temperature_class = function ($temperature) {
    $classes = [
        "quente" => "bg-danger",
        "morno" => "bg-warning",
        "frio" => "bg-info",
        "sem_classificacao" => "bg-secondary"
    ];

    return $classes[$temperature ?: "sem_classificacao"] ?? "bg-secondary";
};

$status_key = function ($title) {
    return function_exists("green_ascii_key") ? green_ascii_key($title) : strtoupper(trim((string) $title));
};
?>

<style>
    #page-content.green-kanban-page {
        --green-kanban-bg: #0f1f16;
        --green-kanban-card: #18341f;
        --green-kanban-soft: #203b28;
        --green-kanban-border: rgba(247, 250, 246, 0.12);
        --green-kanban-border-strong: rgba(247, 250, 246, 0.2);
        --green-kanban-text: #f7faf6;
        --green-kanban-muted: #b8c9b3;
        --green-kanban-accent: #8bae5a;
        background: var(--green-kanban-bg);
        color: var(--green-kanban-text);
        min-height: calc(100vh - 70px);
    }

    .green-kanban-page > .card {
        background: var(--green-kanban-card) !important;
        border-color: var(--green-kanban-border) !important;
        color: var(--green-kanban-text);
        overflow: hidden;
    }

    .green-kanban-page .green-crm-page-header {
        background: var(--green-kanban-card) !important;
        border-color: var(--green-kanban-border) !important;
        color: var(--green-kanban-text);
        padding: 18px 20px;
    }

    .green-kanban-page .green-kanban-toolbar {
        align-items: center;
        display: flex;
        gap: 14px;
        justify-content: space-between;
    }

    .green-kanban-page .green-kanban-toolbar h1 {
        color: var(--green-kanban-text);
        display: block;
        float: none !important;
        font-size: 22px;
        line-height: 1.25;
        margin: 0;
    }

    .green-kanban-page .green-crm-page-subtitle {
        color: var(--green-kanban-muted) !important;
        margin-top: 4px;
    }

    .green-kanban-page .green-crm-title-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .green-kanban-page .green-kanban-board {
        align-items: stretch;
        display: flex;
        gap: 14px;
        min-height: calc(100vh - 210px);
        overflow-x: auto;
        overflow-y: hidden;
        padding: 18px 20px 24px;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
    }

    .green-kanban-page .green-kanban-column {
        background: var(--green-kanban-soft);
        border: 1px solid var(--green-kanban-border);
        border-radius: 8px;
        display: flex;
        flex: 0 0 310px;
        flex-direction: column;
        max-height: calc(100vh - 215px);
        min-width: 0;
        scroll-snap-align: start;
    }

    .green-kanban-page .green-kanban-column-header {
        align-items: center;
        border-bottom: 1px solid var(--green-kanban-border);
        color: var(--green-kanban-text);
        display: flex;
        font-weight: 700;
        justify-content: space-between;
        min-height: 50px;
        padding: 12px 14px;
    }

    .green-kanban-page .green-kanban-count {
        background: rgba(139, 174, 90, 0.2);
        border: 1px solid rgba(139, 174, 90, 0.25);
        border-radius: 999px;
        color: var(--green-kanban-text);
        font-size: 12px;
        line-height: 1;
        min-width: 28px;
        padding: 5px 8px;
        text-align: center;
    }

    .green-kanban-page .green-kanban-list {
        flex: 1;
        min-height: 140px;
        overflow-y: auto;
        padding: 12px;
    }

    .green-kanban-page .green-kanban-column.green-kanban-over {
        border-color: var(--green-kanban-accent);
        box-shadow: inset 0 0 0 2px rgba(139, 174, 90, 0.2);
    }

    .green-kanban-page .green-kanban-card {
        background: var(--green-kanban-card);
        border: 1px solid var(--green-kanban-border);
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14);
        color: var(--green-kanban-text);
        cursor: grab;
        margin-bottom: 10px;
        padding: 12px;
    }

    .green-kanban-page .green-kanban-card:active {
        cursor: grabbing;
    }

    .green-kanban-page .green-kanban-card.green-kanban-dragging {
        opacity: 0.55;
    }

    .green-kanban-page .green-kanban-card-title {
        color: var(--green-kanban-text);
        font-size: 14px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 8px;
        overflow-wrap: anywhere;
    }

    .green-kanban-page .green-kanban-meta {
        display: grid;
        font-size: 12px;
        gap: 6px;
        line-height: 1.25;
    }

    .green-kanban-page .green-kanban-meta-row {
        align-items: flex-start;
        display: flex;
        gap: 8px;
        justify-content: space-between;
    }

    .green-kanban-page .green-kanban-meta-row span {
        color: var(--green-kanban-muted);
        flex: 0 0 82px;
    }

    .green-kanban-page .green-kanban-meta-row strong {
        color: var(--green-kanban-text);
        font-weight: 600;
        min-width: 0;
        overflow-wrap: anywhere;
        text-align: right;
    }

    .green-kanban-page .green-kanban-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }

    .green-kanban-page .green-kanban-actions a {
        align-items: center;
        display: inline-flex;
        height: 32px;
        justify-content: center;
        min-width: 34px;
        padding: 5px 8px;
    }

    .green-kanban-page .green-kanban-empty {
        border: 1px dashed var(--green-kanban-border-strong);
        border-radius: 8px;
        color: var(--green-kanban-muted);
        padding: 16px;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        #page-content.green-kanban-page {
            padding: 10px !important;
        }

        .green-kanban-page .green-crm-page-header {
            padding: 14px;
        }

        .green-kanban-page .green-kanban-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .green-kanban-page .green-crm-title-actions,
        .green-kanban-page .green-crm-title-actions a {
            width: 100%;
        }

        .green-kanban-page .green-crm-title-actions a {
            justify-content: center;
        }

        .green-kanban-page .green-kanban-board {
            min-height: calc(100vh - 230px);
            padding: 14px;
            scroll-snap-type: x mandatory;
        }

        .green-kanban-page .green-kanban-column {
            flex-basis: calc(100vw - 58px);
            max-height: calc(100vh - 245px);
        }

        .green-kanban-page .green-kanban-meta-row {
            flex-direction: column;
            gap: 2px;
        }

        .green-kanban-page .green-kanban-meta-row span {
            flex: none;
        }

        .green-kanban-page .green-kanban-meta-row strong {
            text-align: left;
        }
    }
</style>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page green-kanban-page">
    <div class="card">
        <div class="page-title clearfix green-crm-page-header">
            <div class="green-kanban-toolbar">
                <div>
                    <h1>Funil</h1>
                    <div class="text-off green-crm-page-subtitle">Arraste os leads entre as etapas do funil comercial.</div>
                </div>
                <div class="green-crm-title-actions">
                    <?php echo modal_anchor(get_uri("green_crm/lead_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Novo lead", ["class" => "btn btn-primary", "title" => "Novo lead"]); ?>
                    <?php echo anchor(get_uri("green_crm/leads"), "<i data-feather='list' class='icon-16'></i> Lista", ["class" => "btn btn-default", "title" => "Ver lista de leads"]); ?>
                </div>
            </div>
        </div>

        <div class="green-kanban-board" id="green-kanban-board" role="list">
            <?php foreach ($statuses as $status): ?>
                <?php
                $status_id = (int) $status->id;
                $status_leads = $leads_by_status[$status_id] ?? [];
                $is_won = (int) ($status->is_won ?? 0) || $status_key($status->title) === "VENDIDO";
                $is_lost = (int) ($status->is_lost ?? 0) || $status_key($status->title) === "PERDIDO";
                ?>
                <section class="green-kanban-column"
                    role="listitem"
                    data-status-id="<?php echo $status_id; ?>"
                    data-status-title="<?php echo esc($status->title, "attr"); ?>"
                    data-is-won="<?php echo $is_won ? 1 : 0; ?>"
                    data-is-lost="<?php echo $is_lost ? 1 : 0; ?>">
                    <div class="green-kanban-column-header">
                        <span><?php echo esc($status->title); ?></span>
                        <span class="green-kanban-count"><?php echo count($status_leads); ?></span>
                    </div>
                    <div class="green-kanban-list" data-status-id="<?php echo $status_id; ?>">
                        <div class="green-kanban-empty <?php echo count($status_leads) ? "hide" : ""; ?>">Sem leads</div>
                        <?php foreach ($status_leads as $lead): ?>
                            <article class="green-kanban-card"
                                draggable="true"
                                data-lead-id="<?php echo (int) $lead->id; ?>"
                                data-current-status-id="<?php echo $status_id; ?>">
                                <div class="green-kanban-card-title"><?php echo esc($lead->client_name ?: ($lead->lead_code ?: "Lead #" . $lead->id)); ?></div>
                                <div class="mb10">
                                    <span class="badge <?php echo $temperature_class($lead->temperature); ?>"><?php echo esc($temperature_label($lead->temperature)); ?></span>
                                </div>
                                <div class="green-kanban-meta">
                                    <div class="green-kanban-meta-row"><span>Telefone</span><strong><?php echo esc($lead->phone_normalized ?: ($lead->phone_original ?: "-")); ?></strong></div>
                                    <div class="green-kanban-meta-row"><span>Operadora</span><strong><?php echo esc($lead->operator_name ?: "-"); ?></strong></div>
                                    <div class="green-kanban-meta-row"><span>Valor pago</span><strong><?php echo esc($money($lead->current_paid_value)); ?></strong></div>
                                    <div class="green-kanban-meta-row"><span>Proposta</span><strong><?php echo esc($money($lead->proposed_value)); ?></strong></div>
                                    <div class="green-kanban-meta-row"><span>Follow-up</span><strong><?php echo esc($date($lead->next_followup_at)); ?></strong></div>
                                    <div class="green-kanban-meta-row"><span>Responsável</span><strong><?php echo esc($lead->owner_name ?: "-"); ?></strong></div>
                                </div>
                                <div class="green-kanban-actions">
                                    <?php echo anchor(get_uri("green_crm/lead/" . (int) $lead->id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead"]); ?>
                                    <?php echo modal_anchor(get_uri("green_crm/interaction_modal_form"), "<i data-feather='message-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Registrar interação", "data-post-lead_id" => (int) $lead->id]); ?>
                                    <?php echo modal_anchor(get_uri("green_crm/task_modal_form"), "<i data-feather='calendar' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Criar tarefa", "data-post-lead_id" => (int) $lead->id]); ?>
                                    <?php echo modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='file-text' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Criar cotação", "data-post-lead_id" => (int) $lead->id]); ?>
                                    <?php if (!$is_won): ?>
                                        <?php echo modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Converter em venda", "data-post-lead_id" => (int) $lead->id]); ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    var draggedCard = null;
    var fromList = null;

    var refreshCounts = function () {
        $(".green-kanban-column").each(function () {
            var $column = $(this);
            var count = $column.find(".green-kanban-card").length;
            $column.find(".green-kanban-count").text(count);
            $column.find(".green-kanban-empty").toggleClass("hide", count > 0);
        });
    };

    var moveBack = function ($card, $list) {
        if ($card && $list && $list.length) {
            $list.append($card);
        }
        refreshCounts();
    };

    $("body").on("dragstart", ".green-kanban-card", function (event) {
        draggedCard = this;
        fromList = $(this).closest(".green-kanban-list");
        $(this).addClass("green-kanban-dragging");
        event.originalEvent.dataTransfer.effectAllowed = "move";
        event.originalEvent.dataTransfer.setData("text/plain", $(this).data("lead-id"));
    });

    $("body").on("dragend", ".green-kanban-card", function () {
        $(this).removeClass("green-kanban-dragging");
        $(".green-kanban-column").removeClass("green-kanban-over");
    });

    $("body").on("dragover", ".green-kanban-column", function (event) {
        event.preventDefault();
        $(this).addClass("green-kanban-over");
        event.originalEvent.dataTransfer.dropEffect = "move";
    });

    $("body").on("dragleave", ".green-kanban-column", function () {
        $(this).removeClass("green-kanban-over");
    });

    $("body").on("drop", ".green-kanban-column", function (event) {
        event.preventDefault();

        var $column = $(this);
        var $card = $(draggedCard);
        var $targetList = $column.find(".green-kanban-list");
        var leadId = $card.data("lead-id");
        var fromStatusId = String($card.data("current-status-id"));
        var toStatusId = String($column.data("status-id"));
        var lostReason = "";

        $column.removeClass("green-kanban-over");

        if (!$card.length || !leadId || fromStatusId === toStatusId) {
            return false;
        }

        if (Number($column.data("is-won")) === 1) {
            appAlert.error("Crie uma venda para marcar lead como Vendido.");
            return false;
        }

        if (Number($column.data("is-lost")) === 1) {
            lostReason = prompt("Motivo da perda");
            if (!lostReason || !lostReason.trim()) {
                appAlert.error("Informe o motivo da perda.");
                return false;
            }
            lostReason = lostReason.trim();
        }

        $targetList.append($card);
        refreshCounts();

        appAjaxRequest({
            url: "<?php echo_uri("green_crm/update_lead_status"); ?>",
            type: "POST",
            dataType: "json",
            data: {
                lead_id: leadId,
                status_id: toStatusId,
                lost_reason: lostReason
            },
            success: function (result) {
                if (result.success) {
                    $card.data("current-status-id", toStatusId);
                    appAlert.success(result.message);
                    refreshCounts();
                } else {
                    moveBack($card, fromList);
                    appAlert.error(result.message);
                }
            },
            error: function () {
                moveBack($card, fromList);
                appAlert.error("Nao foi possivel atualizar o status.");
            }
        });

        return false;
    });
});
</script>
