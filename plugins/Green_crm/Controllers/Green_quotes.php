<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_quotes extends Security_Controller
{
    public $Green_quotes_model;
    public $Green_quote_options_model;
    public $Green_leads_model;
    public $Green_operators_model;
    public $Green_plans_model;
    public $Green_lead_statuses_model;
    public $Green_interactions_model;
    public $Green_sales_model;
    public $Green_sale_implantation_checklist_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_quotes_model = model("Green_crm\Models\Green_quotes_model");
        $this->Green_quote_options_model = model("Green_crm\Models\Green_quote_options_model");
        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_plans_model = model("Green_crm\Models\Green_plans_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
        $this->Green_interactions_model = model("Green_crm\Models\Green_interactions_model");
        $this->Green_sales_model = model("Green_crm\Models\Green_sales_model");
        $this->Green_sale_implantation_checklist_model = model("Green_crm\Models\Green_sale_implantation_checklist_model");
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_quotes");
    }

    public function view($id)
    {
        $quote = $this->Green_quotes_model->get_details(["id" => (int) $id])->getRow();
        if (!$quote) {
            return $this->template->render("Green_crm\Views\quote_comparison", [
                "quote" => null,
                "lead" => null,
                "options" => []
            ]);
        }

        return $this->template->render("Green_crm\Views\quote_comparison", [
            "quote" => $quote,
            "lead" => $this->Green_leads_model->get_details(["id" => $quote->lead_id])->getRow(),
            "options" => $this->Green_quote_options_model->get_details(["quote_id" => $quote->id])->getResult()
        ]);
    }

    public function list_data()
    {
        $rows = [];
        foreach ($this->Green_quotes_model->get_details(["status" => $this->request->getPost("status")])->getResult() as $data) {
            $rows[] = $this->_quote_row($data);
        }
        echo json_encode(["data" => $rows]);
    }

    public function modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $lead_id = (int) $this->request->getPost("lead_id");
        $model_info = $id ? $this->Green_quotes_model->get_one($id) : new \stdClass();
        if (!$id && $lead_id) {
            $model_info->lead_id = $lead_id;
        }
        return $this->template->view("Green_crm\Views\modal_quote", [
            "model_info" => $model_info,
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "plans_dropdown" => $this->_to_dropdown($this->Green_plans_model->get_details()->getResult(), "name", true)
        ]);
    }

    public function option_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $quote_id = (int) $this->request->getPost("quote_id");
        $model_info = $id ? $this->Green_quote_options_model->get_details(["id" => $id])->getRow() : new \stdClass();
        if (!$id) {
            $model_info->quote_id = $quote_id;
        }

        return $this->template->view("Green_crm\Views\modal_quote_option", [
            "model_info" => $model_info ?: new \stdClass(),
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "plans_dropdown" => $this->_to_dropdown($this->Green_plans_model->get_details()->getResult(), "name", true)
        ]);
    }

    public function save()
    {
        $this->validate_submitted_data(["lead_id" => "required|numeric"]);
        $id = (int) $this->request->getPost("id");
        $lead_id = (int) $this->request->getPost("lead_id");
        $lead = $lead_id ? $this->Green_leads_model->get_details(["id" => $lead_id])->getRow() : null;
        $quote_data = [
            "lead_id" => $lead_id,
            "client_id" => $lead && $lead->client_id ? (int) $lead->client_id : null,
            "status" => $this->request->getPost("status") ?: "Rascunho",
            "valid_until" => green_date_value($this->request->getPost("valid_until")),
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "updated_by" => $this->login_user->id
        ];
        $save_id = $this->Green_quotes_model->ci_save($quote_data, $id);
        if (!$id) {
            $quote_code_data = ["quote_code" => sprintf("QUOTE-%s-%06d", date("Y"), $save_id), "created_by" => $this->login_user->id];
            $this->Green_quotes_model->ci_save($quote_code_data, $save_id);
        }
        if ($this->request->getPost("operator_id") || $this->request->getPost("plan_id") || $this->request->getPost("plan_name") || $this->request->getPost("monthly_value")) {
            $this->_save_option_from_post($save_id);
        }
        echo json_encode(["success" => true, "id" => $save_id, "data" => $this->_quote_row($this->Green_quotes_model->get_details(["id" => $save_id])->getRow()), "message" => "Cotacao salva."]);
    }

    public function save_option()
    {
        $this->validate_submitted_data(["quote_id" => "required|numeric"]);
        $id = $this->_save_option_from_post((int) $this->request->getPost("quote_id"));
        echo json_encode(["success" => true, "id" => $id, "message" => "Opcao salva."]);
    }

    public function delete_option()
    {
        $id = (int) $this->request->getPost("id");
        $option = $this->Green_quote_options_model->get_one($id);
        if (!$option || empty($option->id)) {
            echo json_encode(["success" => false, "message" => "Opcao invalida."]);
            return;
        }

        $data = ["deleted" => 1, "updated_by" => (int) $this->login_user->id, "updated_at" => date("Y-m-d H:i:s")];
        $this->Green_quote_options_model->ci_save($data, $id);
        if ((int) $option->is_selected) {
            $quote_data = ["selected_option_id" => null];
            $this->Green_quotes_model->ci_save($quote_data, $option->quote_id);
        }

        echo json_encode(["success" => true, "message" => "Opcao excluida."]);
    }

    public function select_option()
    {
        $id = (int) $this->request->getPost("id");
        if (!$this->_select_option($id)) {
            echo json_encode(["success" => false, "message" => "Opcao invalida."]);
            return;
        }
        echo json_encode(["success" => true, "message" => "Opcao selecionada."]);
    }

    public function mark_as_sent()
    {
        $id = (int) $this->request->getPost("id");
        $quote_data = ["status" => "Enviada"];
        $this->Green_quotes_model->ci_save($quote_data, $id);
        echo json_encode(["success" => true, "message" => "Proposta marcada como enviada."]);
    }

    public function accept()
    {
        $id = (int) $this->request->getPost("id");
        $option_id = (int) $this->request->getPost("option_id");

        $quote = $this->Green_quotes_model->get_one($id);
        if (!$quote || empty($quote->id)) {
            echo json_encode(["success" => false, "message" => "Cotacao invalida."]);
            return;
        }

        $option_id = $this->_resolve_option_id($quote, $option_id);
        if (!$option_id || !$this->_select_option($option_id, $id)) {
            echo json_encode(["success" => false, "message" => "Opcao invalida."]);
            return;
        }

        $quote_data = ["status" => "Aceita"];
        $this->Green_quotes_model->ci_save($quote_data, $id);
        if ($quote && $quote->lead_id) {
            $status_id = $this->_accepted_quote_status_id();
            if ($status_id) {
                $lead_data = [
                    "status_id" => $status_id,
                    "updated_by" => (int) $this->login_user->id,
                    "updated_at" => date("Y-m-d H:i:s")
                ];
                $this->Green_leads_model->ci_save($lead_data, $quote->lead_id);
            }

            $this->Green_interactions_model->add_system_interaction(
                $quote->lead_id,
                "Cotação aceita",
                "Cotação marcada como aceita. A venda ainda precisa ser criada.",
                $this->login_user->id
            );
        }
        echo json_encode(["success" => true, "message" => "Cotação aceita. Converta a opção selecionada em venda."]);
    }

    public function convert_selected_to_sale()
    {
        $id = (int) $this->request->getPost("id");
        $quote = $this->Green_quotes_model->get_details(["id" => $id])->getRow();
        if (!$quote || empty($quote->id)) {
            echo json_encode(["success" => false, "message" => "Cotacao invalida."]);
            return;
        }

        if ($quote->status !== "Aceita") {
            echo json_encode(["success" => false, "message" => "Aceite a cotacao antes de converter em venda."]);
            return;
        }

        $option_id = $this->_resolve_option_id($quote, 0);
        $option = $option_id ? $this->Green_quote_options_model->get_details(["id" => $option_id])->getRow() : null;
        $lead = $this->Green_leads_model->get_details(["id" => $quote->lead_id])->getRow();
        if (!$option || !$lead || !$lead->client_id || !$option->monthly_value) {
            echo json_encode(["success" => false, "message" => "Opcao selecionada incompleta para venda."]);
            return;
        }

        $user_id = (int) $this->login_user->id;
        $now = date("Y-m-d H:i:s");
        $sale_data = [
            "lead_id" => (int) $quote->lead_id,
            "quote_id" => (int) $quote->id,
            "quote_option_id" => (int) $option->id,
            "client_id" => (int) $lead->client_id,
            "operator_id" => (int) $option->operator_id ?: null,
            "plan_id" => (int) $option->plan_id ?: null,
            "plan_name" => ($option->plan_registered_name ?: $option->plan_name) ?: null,
            "sale_date" => date("Y-m-d"),
            "sale_value" => (float) $option->monthly_value,
            "consultant_id" => (int) ($lead->owner_id ?: $user_id),
            "status" => "Vendida",
            "implantation_status" => "pendente",
            "notes" => "Venda gerada a partir da cotacao " . ($quote->quote_code ?: ("#" . $quote->id)) . ".",
            "created_by" => $user_id,
            "updated_by" => $user_id,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ];
        $sale_id = $this->Green_sales_model->ci_save($sale_data);
        $sale_code_data = ["sale_code" => sprintf("SALE-%s-%06d", date("Y"), $sale_id)];
        $this->Green_sales_model->ci_save($sale_code_data, $sale_id);
        $this->Green_sale_implantation_checklist_model->ensure_default_items($sale_id, $user_id);
        $this->_mark_lead_as_sold((int) $quote->lead_id, $user_id);
        $this->Green_interactions_model->add_system_interaction((int) $quote->lead_id, "Venda criada", "Lead convertido em venda.", $user_id);
        green_audit("quote", (int) $quote->id, "quote_converted_to_sale", null, ["sale_id" => $sale_id, "quote_option_id" => (int) $option->id], $user_id);

        echo json_encode(["success" => true, "id" => $sale_id, "message" => "Venda criada a partir da opcao selecionada. Abra a venda para selecionar a grade e gerar a comissão."]);
    }

    private function _save_option_from_post($quote_id)
    {
        $quote = $this->Green_quotes_model->get_details(["id" => $quote_id])->getRow();
        $lead = $quote ? $this->Green_leads_model->get_details(["id" => $quote->lead_id])->getRow() : null;
        $operator_id = (int) $this->request->getPost("operator_id") ?: null;
        $plan_id = (int) $this->request->getPost("plan_id") ?: null;
        $plan = $plan_id ? $this->Green_plans_model->get_details(["id" => $plan_id])->getRow() : null;
        $plan_name = trim((string) $this->request->getPost("plan_name"));
        $monthly_value = green_money_to_float($this->request->getPost("monthly_value"));
        $current_paid_value = $lead && $lead->current_paid_value !== null && $lead->current_paid_value !== "" ? (float) $lead->current_paid_value : null;
        $economy_amount = null;
        $economy_percent = null;
        if ($current_paid_value && $monthly_value !== null) {
            $economy_amount = $current_paid_value - (float) $monthly_value;
            $economy_percent = $current_paid_value > 0 ? ($economy_amount / $current_paid_value) * 100 : null;
        }

        $id = (int) $this->request->getPost("option_id");
        $now = date("Y-m-d H:i:s");
        $option_data = [
            "quote_id" => $quote_id,
            "operator_id" => $operator_id,
            "plan_id" => $plan_id,
            "plan_name" => $plan_name ?: null,
            "product_type" => trim((string) $this->request->getPost("product_type")) ?: ($plan->product_type ?? null),
            "monthly_value" => $monthly_value,
            "lives_qty" => (int) $this->request->getPost("lives_qty") ?: null,
            "accommodation" => trim((string) $this->request->getPost("accommodation")) ?: ($plan->accommodation ?? null),
            "coparticipation" => (int) $this->request->getPost("coparticipation") ? 1 : (int) ($plan->coparticipation ?? 0),
            "economy_amount" => $economy_amount,
            "economy_percent" => $economy_percent,
            "hospital_match" => (int) $this->request->getPost("hospital_match") ? 1 : 0,
            "highlight_label" => trim((string) $this->request->getPost("highlight_label")) ?: null,
            "network_notes" => trim((string) $this->request->getPost("network_notes")) ?: null,
            "preferred_hospital_notes" => trim((string) $this->request->getPost("preferred_hospital_notes")) ?: null,
            "pros" => trim((string) $this->request->getPost("pros")) ?: null,
            "cons" => trim((string) $this->request->getPost("cons")) ?: null,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => $now,
            "deleted" => 0
        ];
        if (!$id) {
            $option_data["created_by"] = (int) $this->login_user->id;
            $option_data["created_at"] = $now;
        }

        return $this->Green_quote_options_model->ci_save($option_data, $id);
    }

    private function _select_option($id, $quote_id = 0)
    {
        $option = $this->Green_quote_options_model->get_one($id);
        if (!$option || !$option->id) {
            return false;
        }
        if ($quote_id && (int) $option->quote_id !== (int) $quote_id) {
            return false;
        }

        $this->Green_quote_options_model->db->query("UPDATE " . $this->Green_quote_options_model->db->prefixTable("green_quote_options") . " SET is_selected=0 WHERE quote_id=" . (int) $option->quote_id);
        $option_data = ["is_selected" => 1];
        $this->Green_quote_options_model->ci_save($option_data, $id);
        $quote_data = ["selected_option_id" => $id];
        $this->Green_quotes_model->ci_save($quote_data, $option->quote_id);

        return true;
    }

    private function _resolve_option_id($quote, $posted_option_id = 0)
    {
        if ($posted_option_id) {
            return (int) $posted_option_id;
        }

        if (!empty($quote->selected_option_id)) {
            return (int) $quote->selected_option_id;
        }

        $option = $this->Green_quote_options_model->get_details(["quote_id" => $quote->id])->getRow();
        return $option && !empty($option->id) ? (int) $option->id : 0;
    }

    private function _accepted_quote_status_id()
    {
        return $this->Green_lead_statuses_model->get_id_by_title("Proposta aceita")
            ?: $this->Green_lead_statuses_model->get_id_by_title("Negociacao");
    }

    private function _mark_lead_as_sold($lead_id, $user_id)
    {
        $status_id = $this->Green_lead_statuses_model->get_id_by_title("Vendido");
        if (!$status_id) {
            return;
        }

        $lead_data = [
            "status_id" => $status_id,
            "updated_by" => (int) $user_id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_leads_model->ci_save($lead_data, $lead_id);
    }

    private function _quote_row($data)
    {
        if (!$data) {
            return [];
        }
        $actions = anchor(get_uri("green_crm/quote/" . $data->id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir comparador"])
            . modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $data->id])
            . js_anchor("<i data-feather='send' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-send-quote", "data-id" => $data->id, "title" => "Enviar"])
            . js_anchor("<i data-feather='check-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-accept-quote", "data-id" => $data->id, "title" => "Aceitar"]);
        if ($data->status === "Aceita") {
            $actions .= js_anchor("<i data-feather='shopping-cart' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-convert-selected-quote-list", "data-id" => $data->id, "title" => "Converter opção selecionada em venda"]);
        }

        return [$data->quote_code ?: $data->id, $data->client_name, $data->lead_code, $this->_quote_status_badge($data->status), $data->valid_until, $actions];
    }

    private function _quote_status_badge($status)
    {
        $classes = [
            "Rascunho" => "bg-secondary",
            "Enviada" => "bg-primary",
            "Aceita" => "bg-success",
            "Recusada" => "bg-danger",
            "Vencida" => "bg-warning",
            "Cancelada" => "bg-danger"
        ];

        return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($status ?: "-") . "</span>";
    }

    private function _to_dropdown($rows, $field, $include_blank = false)
    {
        $result = $include_blank ? ["" => "-"] : [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->{$field};
        }
        return $result;
    }
}
