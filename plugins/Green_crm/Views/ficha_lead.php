<?php
$lead = $lead ?? null;
$client = $client ?? null;
$contacts = $contacts ?? [];
$lives = $lives ?? [];
$quotes = $quotes ?? [];
$sales = $sales ?? [];
$commissions = $commissions ?? [];
$interactions = $interactions ?? [];
$tasks = $tasks ?? [];
$audit_logs = $audit_logs ?? [];

$date = function ($value, $with_time = false) {
    if (!$value || $value === "0000-00-00" || $value === "0000-00-00 00:00:00") {
        return "-";
    }
    return date($with_time ? "d/m/Y H:i" : "d/m/Y", strtotime($value));
};

$money = function ($value) {
    return $value !== null && $value !== "" ? "R$ " . number_format((float) $value, 2, ",", ".") : "-";
};

$text = function ($value) {
    return $value !== null && $value !== "" ? esc($value) : "-";
};

$temperature_badge = function ($temperature) {
    $classes = [
        "quente" => "bg-danger",
        "morno" => "bg-warning",
        "frio" => "bg-info",
        "sem_classificacao" => "bg-secondary"
    ];
    $labels = [
        "quente" => "Quente",
        "morno" => "Morno",
        "frio" => "Frio",
        "sem_classificacao" => "Sem classificação"
    ];
    $key = $temperature ?: "sem_classificacao";
    return "<span class='badge " . ($classes[$key] ?? "bg-secondary") . "'>" . esc($labels[$key] ?? $key) . "</span>";
};

$status_badge = function ($status) {
    $classes = [
        "Vendido" => "bg-success",
        "Vendida" => "bg-success",
        "Qualificado" => "bg-success",
        "Novo" => "bg-info",
        "Primeiro contato" => "bg-info",
        "Proposta enviada" => "bg-primary",
        "Proposta aceita" => "bg-primary",
        "Perdido" => "bg-danger",
        "Cancelada" => "bg-danger",
        "Estornada" => "bg-danger",
        "Recebido" => "bg-success",
        "Parcial" => "bg-info",
        "A receber" => "bg-warning",
        "Previsto" => "bg-secondary",
        "Pendente" => "bg-warning",
        "Concluida" => "bg-success",
        "aberta" => "bg-warning",
        "em_andamento" => "bg-info",
        "concluida" => "bg-success",
        "cancelada" => "bg-secondary"
    ];
    return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($status ?: "-") . "</span>";
};

$latest_interaction = count($interactions) ? $interactions[0] : null;
$main_phone = $lead ? trim(($lead->phone_normalized ?? "") ?: ($lead->phone_original ?? "")) : "";
$main_email = $lead ? trim($lead->email ?? "") : "";
?>

<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page green-lead-profile-page">
    <div class="card">
        <?php if (!$lead): ?>
            <div class="card-body">
                <div class="alert alert-danger">Lead não encontrado.</div>
                <?php echo anchor(get_uri("green_crm/leads"), "<i data-feather='arrow-left' class='icon-16'></i> Voltar para leads", ["class" => "btn btn-default", "title" => "Voltar para leads"]); ?>
            </div>
        <?php else: ?>
            <div class="page-title clearfix green-crm-page-header">
                <h1><?php echo esc($lead->client_name); ?></h1>
                <div class="title-button-group green-crm-title-actions">
                    <?php echo anchor(get_uri("green_crm/leads"), "<i data-feather='arrow-left' class='icon-16'></i> Leads", ["class" => "btn btn-default", "title" => "Voltar para leads"]); ?>
                    <?php echo modal_anchor(get_uri("green_crm/lead_modal_form"), "<i data-feather='edit' class='icon-16'></i> Editar lead", ["class" => "btn btn-default", "data-post-id" => $lead->id, "title" => "Editar lead"]); ?>
                    <?php echo modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='file-text' class='icon-16'></i> Nova cotação", ["class" => "btn btn-default", "data-post-lead_id" => $lead->id, "title" => "Nova cotação"]); ?>
                    <?php echo modal_anchor(get_uri("green_crm/interaction_modal_form"), "<i data-feather='message-circle' class='icon-16'></i> Registrar interação", ["class" => "btn btn-default", "data-post-lead_id" => $lead->id, "title" => "Registrar interação"]); ?>
                    <?php echo modal_anchor(get_uri("green_crm/task_modal_form"), "<i data-feather='calendar' class='icon-16'></i> Criar tarefa", ["class" => "btn btn-default", "data-post-lead_id" => $lead->id, "title" => "Criar tarefa"]); ?>
                    <?php if ($lead->status_title !== "Vendido"): ?>
                        <?php echo modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i> Converter em venda", ["class" => "btn btn-primary", "data-post-lead_id" => $lead->id, "title" => "Converter em venda"]); ?>
                    <?php endif; ?>
                </div>
            </div>

            <ul class="nav nav-tabs title green-lead-tabs" role="tablist">
                <?php
                $tabs = [
                    "summary" => "Resumo",
                    "client" => "Dados do cliente",
                    "contacts" => "Contatos",
                    "lives" => "Vidas",
                    "needs" => "Necessidade / plano atual",
                    "quotes" => "Cotações",
                    "sales" => "Vendas",
                    "commissions" => "Comissões",
                    "history" => "Histórico",
                    "tasks" => "Tarefas",
                    "audit" => "Auditoria"
                ];
                foreach ($tabs as $key => $label):
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $key === "summary" ? "active" : ""; ?>" data-bs-toggle="tab" href="#green-lead-<?php echo $key; ?>" role="tab"><?php echo esc($label); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="green-lead-summary" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="green-lead-hero">
                            <div class="green-lead-hero-main">
                                <div>
                                    <div class="green-lead-title"><?php echo $text($lead->client_name ?? ""); ?></div>
                                    <div class="green-lead-subtitle">
                                        <span><i data-feather="user" class="icon-14"></i><?php echo $text($lead->owner_name ?? ""); ?></span>
                                        <span><i data-feather="phone" class="icon-14"></i><?php echo $text($main_phone); ?></span>
                                        <span><i data-feather="mail" class="icon-14"></i><?php echo $text($main_email); ?></span>
                                    </div>
                                </div>
                                <div class="green-lead-chip-row">
                                    <?php echo $status_badge($lead->status_title ?? ""); ?>
                                    <?php echo $temperature_badge($lead->temperature ?? ""); ?>
                                </div>
                            </div>

                            <div class="green-lead-stat-grid">
                                <div class="green-lead-stat">
                                    <span>Valor pago atual</span>
                                    <strong><?php echo $money($lead->current_paid_value ?? null); ?></strong>
                                </div>
                                <div class="green-lead-stat">
                                    <span>Valor proposta</span>
                                    <strong><?php echo $money($lead->proposed_value ?? null); ?></strong>
                                </div>
                                <div class="green-lead-stat">
                                    <span>Quantidade de vidas</span>
                                    <strong><?php echo $text($lead->lives_qty ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-stat">
                                    <span>Próximo follow-up</span>
                                    <strong><?php echo $date($lead->next_followup_at ?? "", true); ?></strong>
                                </div>
                            </div>
                        </div>

<?php
                        $month_names = ["", "Janeiro", "Fevereiro", "Marco", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
                        $renewal_month_label = ($lead->renewal_month ?? null) && isset($month_names[(int) $lead->renewal_month]) ? $month_names[(int) $lead->renewal_month] : ($lead->renewal_month ?? "");
                        $document_label = function_exists("green_format_document") ? green_format_document($lead->document_number ?? null, $lead->document_type ?? null) : ($lead->document_number ?? "-");
                        $client_code_label = $lead->client_code ?: ($lead->lead_code ?? "");
                        ?>
                        <div class="green-crm-section-panel green-lead-detail-panel">
                            <div class="green-crm-section-panel-title">
                                <h4>Informações capa</h4>
                            </div>
                            <div class="green-lead-detail-grid">
                                <div class="green-lead-field">
                                    <span>Cod Cliente</span>
                                    <strong><?php echo $text($client_code_label); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Nome / Empresa</span>
                                    <strong><?php echo $text($lead->client_name ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>CPF/CNPJ</span>
                                    <strong><?php echo esc($document_label); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Telefone principal</span>
                                    <strong><?php echo $text($main_phone); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Email</span>
                                    <strong><?php echo $text($main_email); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Status</span>
                                    <strong><?php echo $status_badge($lead->status_title ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Temperatura</span>
                                    <strong><?php echo $temperature_badge($lead->temperature ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Operadora</span>
                                    <strong><?php echo $text($lead->operator_name ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Valor pago</span>
                                    <strong><?php echo $money($lead->current_paid_value ?? null); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Valor proposta</span>
                                    <strong><?php echo $money($lead->proposed_value ?? null); ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="green-crm-section-panel green-lead-detail-panel">
                            <div class="green-crm-section-panel-title">
                                <h4>Informações filtradas</h4>
                            </div>
                            <div class="green-lead-detail-grid">
                                <div class="green-lead-field">
                                    <span>Operadora</span>
                                    <strong><?php echo $text($lead->operator_name ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Plano</span>
                                    <strong><?php echo $text($lead->current_plan_name ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Qtd de vidas</span>
                                    <strong><?php echo $text($lead->lives_qty ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Idades</span>
                                    <strong><?php echo $text($lead->ages_text ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Mês de reajuste</span>
                                    <strong><?php echo $text($renewal_month_label); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Região reside</span>
                                    <strong><?php echo $text($lead->region ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Hospital de preferência</span>
                                    <strong><?php echo $text($lead->preferred_hospital_text ?? ""); ?></strong>
                                </div>
                                <div class="green-lead-field">
                                    <span>Observações</span>
                                    <strong><?php echo $text($lead->notes ?? ""); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-client" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="row">
                            <div class="col-md-3"><strong>Cod Cliente:</strong> <?php echo $text($client->client_code ?? ($lead->client_code ?? "")); ?></div>
                            <div class="col-md-3"><strong>Tipo:</strong> <?php echo $text($client->client_type ?? ""); ?></div>
                            <div class="col-md-3"><strong>Nome:</strong> <?php echo $text($client->name ?? ""); ?></div>
                            <div class="col-md-3"><strong>Razão social:</strong> <?php echo $text($client->legal_name ?? ""); ?></div>
                        </div>
                        <div class="row mt15">
                            <div class="col-md-3"><strong>CPF/CNPJ:</strong> <?php echo esc(function_exists("green_format_document") ? green_format_document($client->document_number ?? null, $client->document_type ?? null) : ($client->document_number ?? "-")); ?></div>
                        </div>
                        <hr>
                        <p><strong>Status:</strong> <?php echo $text($client->status ?? ""); ?></p>
                        <p><strong>Observações:</strong><br><?php echo nl2br(esc($client->notes ?? "")); ?></p>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-contacts" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="clearfix mb15">
                            <?php echo modal_anchor(get_uri("green_crm/contact_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar contato", ["class" => "btn btn-primary", "title" => "Adicionar contato", "data-post-lead_id" => $lead->id]); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered green-crm-table">
                                <thead><tr><th>Nome</th><th>Cargo/função</th><th>Telefone</th><th>Email</th><th>Principal</th><th class="text-center">Ações</th></tr></thead>
                                <tbody>
                                <?php foreach ($contacts as $contact): ?>
                                    <tr>
                                        <td><?php echo $text($contact->name); ?></td>
                                        <td><?php echo $text($contact->role); ?></td>
                                        <td><?php echo $text($contact->phone_normalized ?: $contact->phone_original); ?></td>
                                        <td><?php echo $text($contact->email); ?></td>
                                        <td><?php echo (int) $contact->is_primary ? "Sim" : "Não"; ?></td>
                                        <td class="text-center">
                                            <?php echo modal_anchor(get_uri("green_crm/contact_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar contato", "data-post-id" => $contact->id, "data-post-lead_id" => $lead->id]); ?>
                                            <?php echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-delete-contact", "data-id" => $contact->id, "title" => "Remover contato"]); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!count($contacts)): ?><tr><td colspan="6" class="text-center text-off">Nenhum contato cadastrado.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-lives" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="clearfix mb15">
                            <?php echo modal_anchor(get_uri("green_crm/life_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Adicionar vida", ["class" => "btn btn-primary", "title" => "Adicionar vida", "data-post-lead_id" => $lead->id]); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered green-crm-table">
                                <thead><tr><th>Nome</th><th>Idade</th><th>Data nascimento</th><th>Parentesco</th><th class="text-center">Ações</th></tr></thead>
                                <tbody>
                                <?php foreach ($lives as $life): ?>
                                    <tr>
                                        <td><?php echo $text($life->name); ?></td>
                                        <td><?php echo $text($life->age); ?></td>
                                        <td><?php echo $date($life->birth_date); ?></td>
                                        <td><?php echo $text($life->relationship ?? ""); ?></td>
                                        <td class="text-center"><?php echo modal_anchor(get_uri("green_crm/life_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar vida", "data-post-id" => $life->id, "data-post-lead_id" => $lead->id]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!count($lives)): ?><tr><td colspan="5" class="text-center text-off">Nenhuma vida cadastrada.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-needs" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="row">
                            <div class="col-md-4"><strong>Operadora atual:</strong> <?php echo $text($lead->operator_name); ?></div>
                            <div class="col-md-4"><strong>Plano atual:</strong> <?php echo $text($lead->current_plan_name); ?></div>
                            <div class="col-md-4"><strong>Mês de reajuste:</strong> <?php echo $text($lead->renewal_month); ?></div>
                        </div>
                        <div class="row mt15">
                            <div class="col-md-4"><strong>Vidas:</strong> <?php echo $text($lead->lives_qty); ?></div>
                            <div class="col-md-4"><strong>Idades:</strong> <?php echo $text($lead->ages_text); ?></div>
                            <div class="col-md-4"><strong>Região:</strong> <?php echo $text($lead->region); ?></div>
                        </div>
                        <div class="row mt15">
                            <div class="col-md-4"><strong>Valor pago atual:</strong> <?php echo $money($lead->current_paid_value); ?></div>
                            <div class="col-md-4"><strong>Valor proposta:</strong> <?php echo $money($lead->proposed_value); ?></div>
                            <div class="col-md-4"><strong>Hospital preferido:</strong> <?php echo $text($lead->preferred_hospital_text); ?></div>
                        </div>
                        <hr>
                        <p><strong>Observações do lead:</strong><br><?php echo nl2br(esc($lead->notes ?? "")); ?></p>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-quotes" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="clearfix mb15">
                            <?php echo modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova cotação", ["class" => "btn btn-primary", "title" => "Nova cotação", "data-post-lead_id" => $lead->id]); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered green-crm-table">
                                <thead><tr><th>Código</th><th>Status</th><th>Validade</th><th>Opção selecionada</th><th class="text-center">Ações</th></tr></thead>
                                <tbody>
                                <?php foreach ($quotes as $quote): ?>
                                    <?php
                                    $selected_option = trim(($quote->selected_operator_name ? $quote->selected_operator_name . " - " : "") . ($quote->selected_registered_plan_name ?: $quote->selected_plan_name));
                                    if ($quote->selected_monthly_value) {
                                        $selected_option .= " (" . $money($quote->selected_monthly_value) . ")";
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $text($quote->quote_code ?: $quote->id); ?></td>
                                        <td><?php echo $status_badge($quote->status); ?></td>
                                        <td><?php echo $date($quote->valid_until); ?></td>
                                        <td><?php echo $text($selected_option); ?></td>
                                        <td class="text-center"><?php echo modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar cotação", "data-post-id" => $quote->id]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!count($quotes)): ?><tr><td colspan="5" class="text-center text-off">Nenhuma cotação cadastrada.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-sales" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="clearfix mb15">
                            <?php echo modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i> Converter em venda", ["class" => "btn btn-primary", "title" => "Converter em venda", "data-post-lead_id" => $lead->id]); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered green-crm-table">
                                <thead><tr><th>Código</th><th>Data venda</th><th>Operadora</th><th>Plano</th><th>Valor</th><th>Implantação</th><th>Fidelidade</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td><?php echo $text($sale->sale_code ?: $sale->id); ?></td>
                                        <td><?php echo $date($sale->sale_date); ?></td>
                                        <td><?php echo $text($sale->operator_name); ?></td>
                                        <td><?php echo $text($sale->plan_registered_name ?: $sale->plan_name); ?></td>
                                        <td><?php echo $money($sale->sale_value); ?></td>
                                        <td><?php echo $text($sale->implantation_status); ?></td>
                                        <td><?php echo $date($sale->fidelity_until); ?></td>
                                        <td><?php echo $status_badge($sale->status); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!count($sales)): ?><tr><td colspan="8" class="text-center text-off">Nenhuma venda vinculada.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-commissions" role="tabpanel">
                    <div class="p20 green-crm-content-panel table-responsive">
                        <table class="table table-bordered green-crm-table">
                            <thead><tr><th>Competencia</th><th>Venda</th><th>Tipo</th><th>Esperado</th><th>Recebido</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($commissions as $commission): ?>
                                <tr>
                                    <td><?php echo sprintf("%02d/%04d", (int) $commission->due_month, (int) $commission->due_year); ?></td>
                                    <td><?php echo $text($commission->sale_code); ?></td>
                                    <td><?php echo $text($commission->commission_type); ?></td>
                                    <td><?php echo $money($commission->expected_amount); ?></td>
                                    <td><?php echo $money($commission->received_amount); ?></td>
                                    <td><?php echo $status_badge($commission->status); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!count($commissions)): ?><tr><td colspan="6" class="text-center text-off">Nenhuma comissão vinculada.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-history" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="clearfix mb15">
                            <?php echo modal_anchor(get_uri("green_crm/interaction_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Registrar interação", ["class" => "btn btn-primary", "title" => "Registrar interação", "data-post-lead_id" => $lead->id]); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered green-crm-table">
                                <thead><tr><th>Data</th><th>Tipo</th><th>Assunto</th><th>Descricao</th><th>Usuario</th></tr></thead>
                                <tbody>
                                <?php foreach ($interactions as $item): ?>
                                    <tr>
                                        <td><?php echo $date($item->created_at, true); ?></td>
                                        <td><?php echo $text($item->interaction_type); ?></td>
                                        <td><?php echo $text($item->subject); ?></td>
                                        <td><?php echo nl2br(esc($item->description ?? "")); ?></td>
                                        <td><?php echo $text($item->user_name); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!count($interactions)): ?><tr><td colspan="5" class="text-center text-off">Nenhuma interação registrada.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-tasks" role="tabpanel">
                    <div class="p20 green-crm-content-panel">
                        <div class="clearfix mb15">
                            <?php echo modal_anchor(get_uri("green_crm/task_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Criar tarefa", ["class" => "btn btn-primary", "title" => "Criar tarefa", "data-post-lead_id" => $lead->id]); ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered green-crm-table">
                                <thead><tr><th>Título</th><th>Vencimento</th><th>Responsável</th><th>Status</th><th class="text-center">Ações</th></tr></thead>
                                <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo $text($task->title); ?></td>
                                        <td><?php echo $date($task->due_date, true); ?></td>
                                        <td><?php echo $text($task->responsible_name); ?></td>
                                        <td><?php echo $status_badge($task->status); ?></td>
                                        <td class="text-center">
                                            <?php if ($task->status !== "Concluida"): ?>
                                                <?php echo js_anchor("<i data-feather='check-circle' class='icon-16'></i> Concluir", ["class" => "btn btn-default btn-sm green-complete-task", "data-id" => $task->id, "title" => "Concluir tarefa"]); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!count($tasks)): ?><tr><td colspan="5" class="text-center text-off">Nenhuma tarefa cadastrada.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="green-lead-audit" role="tabpanel">
                    <div class="p20 green-crm-content-panel table-responsive">
                        <table class="table table-bordered green-crm-table">
                            <thead><tr><th>Data</th><th>Entidade</th><th>Acao</th><th>Antes</th><th>Depois</th><th>Usuario</th></tr></thead>
                            <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td><?php echo $date($log->created_at, true); ?></td>
                                    <td><?php echo $text($log->entity_type . " #" . $log->entity_id); ?></td>
                                    <td><?php echo $text($log->action); ?></td>
                                    <td><pre class="mb0"><?php echo esc($log->old_data); ?></pre></td>
                                    <td><pre class="mb0"><?php echo esc($log->new_data); ?></pre></td>
                                    <td><?php echo $text($log->user_name); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!count($audit_logs)): ?><tr><td colspan="6" class="text-center text-off">Nenhum registro de auditoria.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
    $("body").on("click", ".green-delete-contact", function () {
        var id = $(this).data("id");
        $(this).appConfirmation({
            title: "Remover contato?",
            btnConfirmLabel: "Remover",
            onConfirm: function () {
                appAjaxRequest({
                    url: "<?php echo_uri("green_crm/delete_contact"); ?>",
                    type: "POST",
                    dataType: "json",
                    data: {id: id},
                    success: function (result) {
                        result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                        if (result.success) {
                            location.reload();
                        }
                    }
                });
            }
        });
    });

    $("body").on("click", ".green-complete-task", function () {
        var id = $(this).data("id");
        appAjaxRequest({
            url: "<?php echo_uri("green_crm/complete_task"); ?>",
            type: "POST",
            dataType: "json",
            data: {id: id},
            success: function (result) {
                result.success ? appAlert.success(result.message) : appAlert.error(result.message);
                if (result.success) {
                    location.reload();
                }
            }
        });
    });

    if (window.feather) {
        feather.replace();
    }
});
</script>
