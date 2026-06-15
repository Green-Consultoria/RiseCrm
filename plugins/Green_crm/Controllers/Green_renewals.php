<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_renewals extends Security_Controller
{
    public $Green_leads_model;
    public $Green_lead_statuses_model;
    public $Green_operators_model;
    public $Green_tasks_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_tasks_model = model("Green_crm\Models\Green_tasks_model");
        $this->Users_model = model("App\Models\Users_model");
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_renewals", [
            "months_dropdown" => $this->_months_dropdown(),
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "statuses_dropdown" => $this->_to_dropdown($this->Green_lead_statuses_model->get_details()->getResult(), "title", true),
            "consultants_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"])
        ]);
    }

    public function list_data()
    {
        $options = [
            "renewal_month" => $this->request->getPost("renewal_month"),
            "operator_id" => $this->request->getPost("operator_id"),
            "status_id" => $this->request->getPost("status_id"),
            "temperature" => $this->request->getPost("temperature"),
            "consultant_id" => $this->request->getPost("consultant_id"),
            "fidelity_days" => $this->request->getPost("fidelity_days"),
            "only_without_future_task" => $this->request->getPost("only_without_future_task"),
            "only_without_recent_contact" => $this->request->getPost("only_without_recent_contact"),
            "inactive_days" => $this->request->getPost("inactive_days")
        ];

        $rows = [];
        foreach ($this->Green_leads_model->get_renewal_rows($options)->getResult() as $row) {
            $rows[] = $this->_renewal_row($row);
        }

        echo json_encode(["data" => $rows]);
    }

    public function create_renewal_followup()
    {
        $lead_id = (int) $this->request->getPost("lead_id");
        $due_at = green_date_value($this->request->getPost("due_at"));

        if (!$lead_id || !$due_at) {
            echo json_encode(["success" => false, "message" => "Informe o lead e a data do follow-up."]);
            return;
        }

        $lead = $this->Green_leads_model->get_details(["id" => $lead_id])->getRow();
        if (!$lead) {
            echo json_encode(["success" => false, "message" => "Lead inválido."]);
            return;
        }

        $user_id = (int) $this->login_user->id;
        $responsible_id = (int) ($lead->owner_id ?: $user_id);
        $now = date("Y-m-d H:i:s");
        $data = [
            "lead_id" => $lead_id,
            "title" => "Follow-up de reajuste",
            "due_date" => $due_at . " 09:00:00",
            "responsible_id" => $responsible_id,
            "status" => "aberta",
            "priority" => "media",
            "notes" => "Criado pelo módulo Green Reajustes.",
            "created_by" => $user_id,
            "updated_by" => $user_id,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ];
        $task_id = $this->Green_tasks_model->ci_save($data);

        echo json_encode(["success" => true, "id" => $task_id, "message" => "Follow-up de reajuste criado."]);
    }

    private function _renewal_row($row)
    {
        $lead_id = (int) ($row->lead_id ?? 0);
        $client = esc($row->client_name);
        $warnings = [];
        if ($lead_id && (int) $row->no_future_task) {
            $warnings[] = "<span class='badge bg-warning'>Sem tarefa futura</span>";
        }
        if ($lead_id && (int) $row->no_recent_contact) {
            $warnings[] = "<span class='badge bg-danger'>Sem contato recente</span>";
        }
        if (count($warnings)) {
            $client .= "<div class='mt5'>" . implode(" ", $warnings) . "</div>";
        }

        return [
            $client,
            $this->_phone($row),
            $lead_id ? esc($row->lead_code ?: ("LEAD-" . $lead_id)) : "-",
            esc($row->operator_name ?: "-"),
            esc($row->plan_name ?: "-"),
            $this->_money($row->current_paid_value),
            $this->_money($row->proposed_value),
            $this->_month_label($row->renewal_month),
            $this->_date($row->fidelity_until),
            $this->_temperature_badge($row->temperature),
            $this->_status_badge($row->status_title),
            $this->_date($row->last_contact_at, true),
            $this->_date($row->next_followup_at, true),
            esc($row->owner_name ?: "-"),
            $this->_actions($row)
        ];
    }

    private function _actions($row)
    {
        $lead_id = (int) ($row->lead_id ?? 0);
        if (!$lead_id) {
            return "<span class='text-off'>Venda sem lead</span>";
        }

        $actions = anchor(get_uri("green_crm/lead/" . $lead_id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead"]);
        $actions .= js_anchor("<i data-feather='calendar' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-create-renewal-followup", "data-lead-id" => $lead_id, "title" => "Criar tarefa de follow-up"]);
        $actions .= modal_anchor(get_uri("green_crm/interaction_modal_form"), "<i data-feather='message-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Registrar interação", "data-post-lead_id" => $lead_id]);
        $actions .= modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='file-text' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Criar cotação", "data-post-lead_id" => $lead_id]);

        if (!in_array($row->status_title, ["Vendido", "Vendida"], true)) {
            $actions .= modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Converter em venda", "data-post-lead_id" => $lead_id]);
        }

        return $actions;
    }

    private function _months_dropdown()
    {
        return [
            "" => "Mês de reajuste",
            "1" => "Janeiro",
            "2" => "Fevereiro",
            "3" => "Março",
            "4" => "Abril",
            "5" => "Maio",
            "6" => "Junho",
            "7" => "Julho",
            "8" => "Agosto",
            "9" => "Setembro",
            "10" => "Outubro",
            "11" => "Novembro",
            "12" => "Dezembro"
        ];
    }

    private function _to_dropdown($rows, $field, $include_blank = false)
    {
        $result = $include_blank ? ["" => "-"] : [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->{$field};
        }
        return $result;
    }

    private function _phone($row)
    {
        return esc($row->phone_normalized ?: ($row->phone_original ?: "-"));
    }

    private function _money($value)
    {
        return $value !== null && $value !== "" ? "R$ " . number_format((float) $value, 2, ",", ".") : "-";
    }

    private function _date($value, $with_time = false)
    {
        if (!$value || $value === "0000-00-00" || $value === "0000-00-00 00:00:00") {
            return "-";
        }

        return date($with_time ? "d/m/Y H:i" : "d/m/Y", strtotime($value));
    }

    private function _month_label($month)
    {
        $months = $this->_months_dropdown();
        return $month && isset($months[(string) (int) $month]) ? $months[(string) (int) $month] : "-";
    }

    private function _temperature_badge($temperature)
    {
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
    }

    private function _status_badge($status)
    {
        if (!$status) {
            return "-";
        }

        $classes = [
            "Vendido" => "bg-success",
            "Vendida" => "bg-success",
            "Implantada" => "bg-primary",
            "Qualificado" => "bg-success",
            "Novo" => "bg-info",
            "Proposta aceita" => "bg-primary",
            "Perdido" => "bg-danger",
            "Cancelada" => "bg-danger",
            "Estornada" => "bg-danger"
        ];

        return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($status) . "</span>";
    }
}
