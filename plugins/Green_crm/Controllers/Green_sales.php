<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_sales extends Security_Controller
{
    public $Green_sales_model;
    public $Green_leads_model;
    public $Green_clients_model;
    public $Green_client_contacts_model;
    public $Green_operators_model;
    public $Green_plans_model;
    public $Green_lead_statuses_model;
    public $Green_interactions_model;
    public $Green_sale_implantation_checklist_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_sales_model = model("Green_crm\Models\Green_sales_model");
        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Green_clients_model = model("Green_crm\Models\Green_clients_model");
        $this->Green_client_contacts_model = model("Green_crm\Models\Green_client_contacts_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_plans_model = model("Green_crm\Models\Green_plans_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
        $this->Green_interactions_model = model("Green_crm\Models\Green_interactions_model");
        $this->Green_sale_implantation_checklist_model = model("Green_crm\Models\Green_sale_implantation_checklist_model");
        $this->Users_model = model("App\Models\Users_model");
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_sales", $this->_dropdown_view_data());
    }

    public function list_data()
    {
        $options = [
            "date_from" => $this->date_value($this->request->getPost("date_from")),
            "date_to" => $this->date_value($this->request->getPost("date_to")),
            "operator_id" => $this->request->getPost("operator_id"),
            "plan_id" => $this->request->getPost("plan_id"),
            "status" => $this->request->getPost("status"),
            "implantation_status" => $this->request->getPost("implantation_status"),
            "consultant_id" => $this->request->getPost("consultant_id"),
            "search" => $this->request->getPost("search")
        ];

        $rows = [];
        foreach ($this->Green_sales_model->get_details($options)->getResult() as $sale) {
            $rows[] = $this->_sale_row($sale);
        }

        echo json_encode(["data" => $rows]);
    }

    public function modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $lead_id = (int) $this->request->getPost("lead_id");
        $model_info = $id ? $this->Green_sales_model->get_details(["id" => $id])->getRow() : new \stdClass();

        if (!$id && $lead_id) {
            $model_info = $this->_model_from_lead($lead_id);
            if (!$model_info) {
                echo "Lead invalido para conversao.";
                return;
            }
        }

        $linked_lead_id = (int) ($model_info->lead_id ?? $lead_id);
        $client_id = (int) ($model_info->client_id ?? 0);
        $checklist_data = $id ? $this->_implantation_checklist_view_data($id) : [
            "sale" => $model_info ?: new \stdClass(),
            "checklist_items" => [],
            "progress" => (object) ["completed_items" => 0, "total_items" => $this->Green_sale_implantation_checklist_model->default_items_count(), "pending_items" => $this->Green_sale_implantation_checklist_model->default_items_count()]
        ];

        return $this->template->view("Green_crm\Views\modal_sale", array_merge($this->_dropdown_view_data(), [
            "model_info" => $model_info ?: new \stdClass(),
            "lead_info" => $linked_lead_id ? $this->Green_leads_model->get_details(["id" => $linked_lead_id])->getRow() : null,
            "client_info" => $client_id ? $this->Green_clients_model->get_details(["id" => $client_id])->getRow() : null,
            "implantation_checklist_data" => $checklist_data
        ]));
    }

    public function search_leads()
    {
        $q = trim((string) ($this->request->getPost("q") ?: $this->request->getGet("q")));
        $rows = [];

        foreach ($this->Green_leads_model->get_details(["search" => $q])->getResult() as $lead) {
            $label = trim(($lead->lead_code ?: "LEAD-" . $lead->id) . " - " . $lead->client_name);
            $phone = $lead->phone_normalized ?: $lead->phone_original;
            if ($phone) {
                $label .= " - " . $phone;
            }

            $rows[] = [
                "id" => (int) $lead->id,
                "text" => $label,
                "client_id" => (int) $lead->client_id,
                "client_name" => $lead->client_name,
                "phone" => $phone,
                "current_operator_id" => (int) $lead->current_operator_id,
                "current_operator" => $lead->operator_name,
                "current_plan" => $lead->current_plan_name,
                "current_paid_value" => $lead->current_paid_value,
                "proposed_value" => $lead->proposed_value
            ];
        }

        echo json_encode($rows);
    }

    public function search_clients()
    {
        $q = trim((string) ($this->request->getPost("q") ?: $this->request->getGet("q")));
        $rows = [];

        foreach ($this->Green_clients_model->get_details(["search" => $q])->getResult() as $client) {
            $phone = $client->phone_normalized ?: $client->phone_original;
            $label_parts = [$client->name];
            if ($client->document_number) {
                $label_parts[] = $client->document_number;
            }
            if ($phone) {
                $label_parts[] = $phone;
            }

            $rows[] = [
                "id" => (int) $client->id,
                "text" => implode(" - ", $label_parts),
                "document_number" => $client->document_number,
                "phone" => $phone,
                "email" => $client->email
            ];
        }

        echo json_encode($rows);
    }

    public function save()
    {
        $this->validate_submitted_data([
            "sale_date" => "required",
            "sale_value" => "required",
            "operator_id" => "required|numeric"
        ]);

        $id = (int) $this->request->getPost("id");
        $sale_date = $this->date_value($this->request->getPost("sale_date"));
        $implantation_date = $this->date_value($this->request->getPost("implantation_date"));
        $fidelity_until = $this->date_value($this->request->getPost("fidelity_until"));
        $sale_value = $this->money_to_float($this->request->getPost("sale_value"));
        $plan_id = (int) $this->request->getPost("plan_id");
        $plan_name = trim((string) $this->request->getPost("plan_name"));
        $implantation_status = $this->_valid_implantation_status($this->request->getPost("implantation_status"));
        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;
        $client_id = (int) $this->request->getPost("client_id");

        if (!$client_id) {
            $client_id = $this->_create_quick_client($user_id, $now);
        }

        if (!$client_id) {
            echo json_encode(["success" => false, "message" => "Informe um cliente existente ou crie um cliente rapido."]);
            return;
        }

        if (!$sale_date) {
            echo json_encode(["success" => false, "message" => "Data da venda invalida."]);
            return;
        }

        if (!$sale_value || $sale_value <= 0) {
            echo json_encode(["success" => false, "message" => "Valor da venda deve ser maior que zero."]);
            return;
        }

        if ($implantation_status === "implantada" && !$implantation_date) {
            echo json_encode(["success" => false, "message" => "Informe a data de implantacao."]);
            return;
        }

        if ($implantation_date && $implantation_date < $sale_date) {
            echo json_encode(["success" => false, "message" => "A data de implantacao nao pode ser anterior a data da venda."]);
            return;
        }

        if ($fidelity_until && $fidelity_until <= $sale_date) {
            echo json_encode(["success" => false, "message" => "A fidelidade deve ser posterior a data da venda."]);
            return;
        }

        if (!$plan_name && $plan_id) {
            $plan = $this->Green_plans_model->get_one($plan_id);
            $plan_name = $plan->name ?? "";
        }

        $notes = trim((string) $this->request->getPost("notes"));
        $notes = $this->_notes_with_manual_commission_fields($notes);

        $sale_data = [
            "lead_id" => (int) $this->request->getPost("lead_id") ?: null,
            "client_id" => $client_id,
            "operator_id" => (int) $this->request->getPost("operator_id"),
            "plan_id" => $plan_id ?: null,
            "plan_name" => $plan_name ?: null,
            "sale_date" => $sale_date,
            "sale_value" => $sale_value,
            "implantation_date" => $implantation_date,
            "fidelity_until" => $fidelity_until,
            "contract_number" => trim((string) $this->request->getPost("contract_number")) ?: null,
            "consultant_id" => (int) $this->request->getPost("consultant_id") ?: $user_id,
            "status" => $this->_valid_status($this->request->getPost("status")),
            "implantation_status" => $implantation_status,
            "notes" => $notes ?: null,
            "updated_by" => $user_id,
            "updated_at" => $now,
            "deleted" => 0
        ];

        if (!$id) {
            $sale_data["created_by"] = $user_id;
            $sale_data["created_at"] = $now;
        }

        $save_id = $this->Green_sales_model->ci_save($sale_data, $id);
        if (!$id) {
            $sale_code_data = ["sale_code" => sprintf("SALE-%s-%06d", date("Y"), $save_id)];
            $this->Green_sales_model->ci_save($sale_code_data, $save_id);
        }

        if ($save_id) {
            $this->Green_sale_implantation_checklist_model->ensure_default_items($save_id, $user_id);
        }

        $lead_id = (int) ($sale_data["lead_id"] ?? 0);
        if ($lead_id) {
            $this->_mark_lead_as_sold($lead_id, $user_id);

            if (!$id) {
                $this->Green_interactions_model->add_system_interaction(
                    $lead_id,
                    "Venda criada",
                    "Lead convertido em venda.",
                    $user_id
                );
            }
        }

        echo json_encode(["success" => true, "id" => $save_id, "message" => "Venda salva com sucesso."]);
    }

    public function sale_implantation_checklist($sale_id)
    {
        return view('Green_crm\Views\implantation_checklist', $this->_implantation_checklist_view_data((int) $sale_id));
    }

    public function update_implantation_item()
    {
        $id = (int) $this->request->getPost("id");
        $status = $this->request->getPost("status");
        $notes = $this->request->getPost("notes");
        $user_id = (int) $this->login_user->id;

        $item = $this->Green_sale_implantation_checklist_model->get_details(["id" => $id])->getRow();
        if (!$item) {
            echo json_encode(["success" => false, "message" => "Item de implantação inválido."]);
            return;
        }

        if (!$this->Green_sale_implantation_checklist_model->valid_status($status)) {
            echo json_encode(["success" => false, "message" => "Status de implantação inválido."]);
            return;
        }

        $this->Green_sale_implantation_checklist_model->update_item($id, $status, $notes, $user_id);
        $this->_sync_sale_implantation_status((int) $item->sale_id, $user_id);

        $view_data = $this->_implantation_checklist_view_data((int) $item->sale_id);
        echo json_encode([
            "success" => true,
            "message" => "Checklist atualizado.",
            "checklist_html" => view('Green_crm\Views\implantation_checklist', $view_data),
            "progress_text" => $this->_implantation_progress_text($view_data["progress"])
        ]);
    }

    public function cancel()
    {
        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Venda invalida."]);
            return;
        }

        $data = [
            "status" => "Cancelada",
            "implantation_status" => "cancelada",
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_sales_model->ci_save($data, $id);

        echo json_encode(["success" => true, "message" => "Venda cancelada com sucesso."]);
    }

    public function convert_lead_to_sale()
    {
        $lead_id = (int) $this->request->getPost("lead_id");
        $model_info = $this->_model_from_lead($lead_id);

        if (!$model_info) {
            echo "Lead invalido para conversao.";
            return;
        }

        $lead_info = $this->Green_leads_model->get_details(["id" => $lead_id])->getRow();

        return $this->template->view("Green_crm\Views\modal_sale", array_merge($this->_dropdown_view_data(), [
            "model_info" => $model_info,
            "lead_info" => $lead_info,
            "client_info" => $lead_info && $lead_info->client_id ? $this->Green_clients_model->get_details(["id" => $lead_info->client_id])->getRow() : null
        ]));
    }

    private function _model_from_lead($lead_id)
    {
        if (!$lead_id) {
            return null;
        }

        $lead = $this->Green_leads_model->get_details(["id" => $lead_id])->getRow();
        if (!$lead || !$lead->client_id) {
            return null;
        }

        $model_info = new \stdClass();
        $model_info->lead_id = $lead_id;
        $model_info->client_id = $lead->client_id;
        $model_info->operator_id = $lead->current_operator_id;
        $model_info->plan_name = $lead->current_plan_name;
        $model_info->sale_value = $lead->proposed_value ?: $lead->current_paid_value;
        $model_info->sale_date = date("Y-m-d");
        $model_info->status = "Vendida";
        $model_info->implantation_status = "pendente";

        return $model_info;
    }

    private function _sale_row($data)
    {
        if (!$data) {
            return [];
        }

        $actions = modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar venda", "data-post-id" => $data->id]);
        $actions .= modal_anchor(get_uri("green_crm/commission_generation_modal_form"), "<i data-feather='dollar-sign' class='icon-16'></i> Gerar comiss&otilde;es", ["class" => "btn btn-default btn-sm", "title" => "Gerar comissoes", "data-post-sale_id" => $data->id]);
        $actions .= anchor(get_uri("green_crm/commissions"), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Ver comissões"]);
        if ($data->status !== "Cancelada") {
            $actions .= js_anchor("<i data-feather='x-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-cancel-sale", "data-id" => $data->id, "title" => "Cancelar venda"]);
        }

        return [
            $data->sale_code ?: ("SALE-" . $data->id),
            $data->client_name,
            $data->lead_code,
            $data->operator_name,
            $data->plan_registered_name ?: $data->plan_name,
            $this->format_date($data->sale_date),
            "R$ " . number_format((float) $data->sale_value, 2, ",", "."),
            $this->format_date($data->implantation_date),
            $this->format_date($data->fidelity_until),
            $this->_status_badge($data->status),
            $this->_implantation_status_badge($data->implantation_status),
            $this->_implantation_progress_text((object) [
                "completed_items" => (int) ($data->implantation_checklist_completed ?? 0),
                "total_items" => (int) ($data->implantation_checklist_total ?? $this->Green_sale_implantation_checklist_model->default_items_count())
            ]),
            $data->contract_number,
            $actions
        ];
    }

    private function _implantation_checklist_view_data($sale_id)
    {
        $sale_id = (int) $sale_id;
        $sale = $sale_id ? $this->Green_sales_model->get_details(["id" => $sale_id])->getRow() : null;
        if ($sale) {
            $this->Green_sale_implantation_checklist_model->ensure_default_items($sale_id, (int) $this->login_user->id);
        }

        return [
            "sale" => $sale,
            "checklist_items" => $sale ? $this->Green_sale_implantation_checklist_model->get_details(["sale_id" => $sale_id])->getResult() : [],
            "progress" => $this->Green_sale_implantation_checklist_model->get_progress($sale_id)
        ];
    }

    private function _sync_sale_implantation_status($sale_id, $user_id)
    {
        $sale = $this->Green_sales_model->get_one($sale_id);
        if (!$sale || empty($sale->id) || in_array($sale->status, ["Cancelada", "Estornada"], true)) {
            return;
        }

        $items = $this->Green_sale_implantation_checklist_model->get_details(["sale_id" => $sale_id])->getResult();
        $final_completed = false;
        $has_started = false;

        foreach ($items as $item) {
            if ($item->status !== "pendente") {
                $has_started = true;
            }

            if ($item->item_key === "implantacao_concluida" && $item->status === "concluido") {
                $final_completed = true;
            }
        }

        $data = [
            "updated_by" => (int) $user_id,
            "updated_at" => date("Y-m-d H:i:s")
        ];

        if ($final_completed) {
            $data["status"] = "Implantada";
            $data["implantation_status"] = "implantada";
            if (empty($sale->implantation_date) || $sale->implantation_date === "0000-00-00") {
                $data["implantation_date"] = date("Y-m-d");
            }
        } else {
            $data["implantation_status"] = $has_started ? "em_andamento" : "pendente";
            if ($sale->status === "Implantada") {
                $data["status"] = "Implantacao pendente";
            }
        }

        $this->Green_sales_model->ci_save($data, $sale_id);
    }

    private function _implantation_progress_text($progress)
    {
        $completed = (int) ($progress->completed_items ?? 0);
        $total = (int) ($progress->total_items ?? $this->Green_sale_implantation_checklist_model->default_items_count());
        if ($total <= 0) {
            $total = $this->Green_sale_implantation_checklist_model->default_items_count();
        }

        return $completed . "/" . $total . " concluído";
    }

    private function _status_badge($status)
    {
        $classes = [
            "Vendida" => "bg-success",
            "Implantacao pendente" => "bg-warning",
            "Implantada" => "bg-success",
            "Cancelada" => "bg-danger",
            "Estornada" => "bg-danger"
        ];
        $class = $classes[$status] ?? "bg-secondary";

        return "<span class='badge $class'>" . esc($status) . "</span>";
    }

    private function _implantation_status_badge($status)
    {
        $labels = [
            "nao_iniciada" => "Não iniciada",
            "pendente" => "Pendente",
            "em_andamento" => "Em andamento",
            "implantada" => "Implantada",
            "cancelada" => "Cancelada"
        ];
        $classes = [
            "nao_iniciada" => "bg-secondary",
            "pendente" => "bg-warning",
            "em_andamento" => "bg-info",
            "implantada" => "bg-success",
            "cancelada" => "bg-danger"
        ];

        return "<span class='badge " . ($classes[$status] ?? "bg-secondary") . "'>" . esc($labels[$status] ?? ($status ?: "-")) . "</span>";
    }

    private function _dropdown_view_data()
    {
        return [
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "plans_dropdown" => $this->_plans_dropdown(),
            "consultants_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"]),
            "current_user_id" => (int) $this->login_user->id
        ];
    }

    private function _create_quick_client($user_id, $now)
    {
        helper("green");

        $name = trim((string) $this->request->getPost("quick_client_name"));
        if (!$name) {
            return 0;
        }

        $document = green_normalize_document($this->request->getPost("quick_client_document"));
        $phone_original = trim((string) $this->request->getPost("quick_client_phone"));
        $phone_normalized = green_normalize_phone($phone_original);
        $email = green_normalize_email($this->request->getPost("quick_client_email"));

        $existing_client = null;
        if ($document["document_number"]) {
            $existing_client = $this->Green_clients_model->find_by_document($document["document_type"], $document["document_number"]);
        }
        if (!$existing_client && $phone_normalized) {
            $existing_client = $this->Green_clients_model->find_by_phone($phone_normalized);
        }
        if (!$existing_client && $email) {
            $existing_client = $this->Green_clients_model->find_by_email($email);
        }

        if ($existing_client && !empty($existing_client->id)) {
            return (int) $existing_client->id;
        }

        $client_data = [
            "client_type" => $document["document_type"] === "CNPJ" ? "PJ" : ($document["document_type"] === "CPF" ? "PF" : "NAO_INFORMADO"),
            "name" => $name,
            "document_type" => $document["document_type"],
            "document_number" => $document["document_number"] ?: null,
            "status" => "Ativo",
            "created_by" => $user_id,
            "updated_by" => $user_id,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ];
        $client_id = $this->Green_clients_model->save_client($client_data);

        if ($phone_normalized || $email) {
            $contact_data = [
                "client_id" => $client_id,
                "phone_original" => $phone_original ?: null,
                "phone_normalized" => $phone_normalized,
                "email" => $email,
                "is_primary" => 1,
                "created_by" => $user_id,
                "updated_by" => $user_id,
                "created_at" => $now,
                "updated_at" => $now,
                "deleted" => 0
            ];
            $this->Green_client_contacts_model->ci_save($contact_data);
        }

        return (int) $client_id;
    }

    private function _notes_with_manual_commission_fields($notes)
    {
        $lines = [];
        $multiplier = trim((string) $this->request->getPost("total_commission_multiplier"));
        $bonus = trim((string) $this->request->getPost("bonus_amount"));

        if ($multiplier !== "") {
            $lines[] = "Multiplicador de comissao total informado: " . $multiplier;
        }
        if ($bonus !== "") {
            $lines[] = "Bonus informado: " . $bonus;
        }

        if (!count($lines)) {
            return $notes;
        }

        return trim($notes . "\n\n" . implode("\n", $lines));
    }

    private function _plans_dropdown()
    {
        $result = ["" => "-"];
        foreach ($this->Green_plans_model->get_details(["status" => "Ativo"])->getResult() as $plan) {
            $result[$plan->id] = trim(($plan->operator_name ? $plan->operator_name . " - " : "") . $plan->name);
        }
        return $result;
    }

    private function _to_dropdown($rows, $field, $include_blank = false)
    {
        $result = $include_blank ? ["" => "-"] : [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->{$field};
        }
        return $result;
    }

    private function _mark_lead_as_sold($lead_id, $user_id)
    {
        $status_id = $this->Green_lead_statuses_model->get_id_by_title("Vendido");
        if (!$status_id) {
            return;
        }

        $lead_data = [
            "status_id" => $status_id,
            "updated_by" => $user_id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_leads_model->ci_save($lead_data, $lead_id);
    }

    private function _valid_status($status)
    {
        return in_array($status, ["Vendida", "Implantacao pendente", "Implantada", "Cancelada", "Estornada"], true) ? $status : "Vendida";
    }

    private function _valid_implantation_status($status)
    {
        return in_array($status, ["nao_iniciada", "pendente", "em_andamento", "implantada", "cancelada"], true) ? $status : "nao_iniciada";
    }

    private function money_to_float($value)
    {
        $value = trim((string) $value);
        if ($value === "") {
            return null;
        }

        $value = str_replace(["R$", " "], "", $value);
        if (strpos($value, ",") !== false && strpos($value, ".") !== false) {
            $value = str_replace(".", "", $value);
            $value = str_replace(",", ".", $value);
        } elseif (strpos($value, ",") !== false) {
            $value = str_replace(",", ".", $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function date_value($value)
    {
        if ($value === null || $value === "") {
            return null;
        }

        $value = trim((string) $value);
        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
            return $value;
        }

        if (preg_match("/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/", $value, $matches)) {
            return sprintf("%04d-%02d-%02d", (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date("Y-m-d", $timestamp) : null;
    }

    private function format_date($value)
    {
        if (!$value || $value === "0000-00-00") {
            return "";
        }

        return date("d/m/Y", strtotime($value));
    }
}
