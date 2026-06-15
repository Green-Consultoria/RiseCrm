<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_leads extends Security_Controller
{
    public $Green_clients_model;
    public $Green_client_contacts_model;
    public $Green_leads_model;
    public $Green_operators_model;
    public $Green_lead_statuses_model;
    public $Green_sources_model;
    public $Green_lead_lives_model;
    public $Green_quotes_model;
    public $Green_sales_model;
    public $Green_commission_installments_model;
    public $Green_interactions_model;
    public $Green_tasks_model;
    public $Green_audit_logs_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_clients_model = model("Green_crm\Models\Green_clients_model");
        $this->Green_client_contacts_model = model("Green_crm\Models\Green_client_contacts_model");
        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
        $this->Green_sources_model = model("Green_crm\Models\Green_sources_model");
        $this->Green_lead_lives_model = model("Green_crm\Models\Green_lead_lives_model");
        $this->Green_quotes_model = model("Green_crm\Models\Green_quotes_model");
        $this->Green_sales_model = model("Green_crm\Models\Green_sales_model");
        $this->Green_commission_installments_model = model("Green_crm\Models\Green_commission_installments_model");
        $this->Green_interactions_model = model("Green_crm\Models\Green_interactions_model");
        $this->Green_tasks_model = model("Green_crm\Models\Green_tasks_model");
        $this->Green_audit_logs_model = model("Green_crm\Models\Green_audit_logs_model");
        $this->Users_model = model("App\Models\Users_model");
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_leads", $this->_dropdown_view_data());
    }

    public function list_data()
    {
        $filter_fields = [
            "client_code", "client_name", "document_number", "phone", "email",
            "status_id", "temperature", "source_id", "operator_id",
            "plan", "lives_qty", "ages", "renewal_month",
            "current_paid_min", "current_paid_max", "proposed_min", "proposed_max",
            "region", "hospital", "notes", "search"
        ];
        $options = [];
        foreach ($filter_fields as $field) {
            $options[$field] = $this->request->getPost($field);
        }

        $rows = [];
        foreach ($this->Green_leads_model->get_details($options)->getResult() as $lead) {
            $rows[] = $this->_lead_row($lead);
        }

        echo json_encode(["data" => $rows]);
    }

    public function modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $model_info = $id ? $this->Green_leads_model->get_details(["id" => $id])->getRow() : new \stdClass();
        $view_data = $this->_dropdown_view_data();
        $view_data["model_info"] = $model_info ?: new \stdClass();
        $view_data["default_status_id"] = $this->_default_status_id();

        return $this->template->view("Green_crm\Views\modal_lead", $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(["name" => "required"]);

        $id = (int) $this->request->getPost("id");
        $old_lead = $id ? $this->Green_leads_model->get_details(["id" => $id])->getRow() : null;
        $name = trim((string) $this->request->getPost("name"));
        $document = $this->normalize_document($this->request->getPost("document_number"));
        $phone_original = trim((string) $this->request->getPost("phone"));
        $phone_normalized = $this->normalize_phone($phone_original);
        $email = $this->normalize_email($this->request->getPost("email"));
        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;

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

        $client_id = (int) ($this->request->getPost("client_id") ?: ($existing_client->id ?? 0));
        $client_code = trim((string) $this->request->getPost("client_code"));
        $client_data = [
            "client_type" => $this->_client_type($this->request->getPost("client_type")),
            "name" => $name,
            "legal_name" => trim((string) $this->request->getPost("legal_name")) ?: null,
            "document_type" => $document["document_type"],
            "document_number" => $document["document_number"] ?: null,
            "status" => "Ativo",
            "notes" => null,
            "updated_by" => $user_id,
            "updated_at" => $now,
            "deleted" => 0
        ];
        if ($client_code !== "") {
            $client_data["client_code"] = $client_code;
        }

        if (!$client_id) {
            $client_data["created_by"] = $user_id;
            $client_data["created_at"] = $now;
        }

        $client_id = $this->Green_clients_model->save_client($client_data, $client_id);

        // Gera Cod Cliente automatico (CLI-AAAA-000000) quando nao informado
        $saved_client = $this->Green_clients_model->get_details(["id" => $client_id])->getRow();
        if ($saved_client && empty($saved_client->client_code)) {
            $this->Green_clients_model->save_client([
                "client_code" => sprintf("CLI-%s-%06d", date("Y"), $client_id)
            ], $client_id);
        }

        if ($phone_normalized || $email) {
            $contact = $this->Green_client_contacts_model->get_details(["client_id" => $client_id])->getRow();
            $contact_data = [
                "client_id" => $client_id,
                "phone_original" => $phone_original ?: null,
                "phone_normalized" => $phone_normalized,
                "email" => $email,
                "is_primary" => 1,
                "updated_by" => $user_id,
                "updated_at" => $now,
                "deleted" => 0
            ];

            if (empty($contact->id)) {
                $contact_data["created_by"] = $user_id;
                $contact_data["created_at"] = $now;
            }

            $this->Green_client_contacts_model->ci_save($contact_data, $contact->id ?? 0);
        }

        $status_id = (int) $this->request->getPost("status_id") ?: $this->_default_status_id();
        $temperature = $this->_valid_temperature($this->request->getPost("temperature"));
        $posted_owner = (int) $this->request->getPost("owner_id");
        $owner_id = $posted_owner ?: (int) ($old_lead->owner_id ?? $user_id);

        $status_changed = $old_lead && (int) $old_lead->status_id !== $status_id;
        $temp_changed = $old_lead && (string) $old_lead->temperature !== (string) $temperature;
        $owner_changed = $old_lead && (int) $old_lead->owner_id !== $owner_id;

        $lead_data = [
            "client_id" => $client_id,
            "source_id" => (int) $this->request->getPost("source_id") ?: 1,
            "source_lead_id" => trim((string) $this->request->getPost("source_lead_id")) ?: null,
            "status_id" => $status_id,
            "temperature" => $temperature,
            "owner_id" => $owner_id,
            "current_operator_id" => (int) $this->request->getPost("current_operator_id") ?: null,
            "current_plan_name" => trim((string) $this->request->getPost("current_plan_name")) ?: null,
            "desired_plan_type" => trim((string) $this->request->getPost("desired_plan_type")) ?: null,
            "lives_qty" => (int) $this->request->getPost("lives_qty") ?: null,
            "ages_text" => trim((string) $this->request->getPost("ages_text")) ?: null,
            "renewal_month" => $this->_month($this->request->getPost("renewal_month")),
            "current_paid_value" => $this->money_to_float($this->request->getPost("current_paid_value")),
            "proposed_value" => $this->money_to_float($this->request->getPost("proposed_value")),
            "region" => trim((string) $this->request->getPost("region")) ?: null,
            "preferred_hospital_text" => trim((string) $this->request->getPost("preferred_hospital_text")) ?: null,
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "next_followup_at" => $this->date_value($this->request->getPost("next_followup_at")),
            "updated_by" => $user_id,
            "updated_at" => $now,
            "deleted" => 0
        ];

        if (!$id || $status_changed) {
            $lead_data["status_changed_at"] = $now;
        }
        if (!$id || $temp_changed) {
            $lead_data["temperature_changed_at"] = $now;
        }

        if (!$id) {
            $lead_data["created_by"] = $user_id;
            $lead_data["created_at"] = $now;
        }

        $lead_id = $this->Green_leads_model->ci_save($lead_data, $id);
        if (!$id) {
            $lead_code_data = ["lead_code" => sprintf("GREEN-%06d", $lead_id)];
            $this->Green_leads_model->ci_save($lead_code_data, $lead_id);
        }

        $this->_audit_lead_save($lead_id, $old_lead, $lead_data, $status_changed, $temp_changed, $owner_changed, $user_id, !$id);

        $row = $this->_lead_row($this->Green_leads_model->get_details(["id" => $lead_id])->getRow());
        echo json_encode(["success" => true, "data" => $row, "id" => $lead_id, "message" => "Lead salvo com sucesso."]);
    }

    private function _audit_lead_save($lead_id, $old_lead, $lead_data, $status_changed, $temp_changed, $owner_changed, $user_id, $is_new)
    {
        if (!function_exists("green_audit")) {
            return;
        }

        if ($is_new) {
            green_audit("lead", $lead_id, "created", null, [
                "lead_code" => sprintf("GREEN-%06d", $lead_id),
                "status_id" => $lead_data["status_id"],
                "temperature" => $lead_data["temperature"]
            ], $user_id);
            return;
        }

        if ($status_changed) {
            green_audit("lead", $lead_id, "status_changed", ["status_id" => (int) $old_lead->status_id], ["status_id" => (int) $lead_data["status_id"]], $user_id);
        }
        if ($temp_changed) {
            green_audit("lead", $lead_id, "temperature_changed", ["temperature" => $old_lead->temperature], ["temperature" => $lead_data["temperature"]], $user_id);
        }
        if ($owner_changed) {
            green_audit("lead", $lead_id, "owner_changed", ["owner_id" => (int) $old_lead->owner_id], ["owner_id" => (int) $lead_data["owner_id"]], $user_id);
        }

        //demais campos principais
        $tracked = ["current_operator_id", "current_plan_name", "desired_plan_type", "lives_qty", "renewal_month", "current_paid_value", "proposed_value", "region", "preferred_hospital_text", "notes"];
        $old_main = [];
        $new_main = [];
        foreach ($tracked as $field) {
            $old_main[$field] = $old_lead->{$field} ?? null;
            $new_main[$field] = $lead_data[$field] ?? null;
        }
        green_log_changes("lead", $lead_id, $old_main, $new_main, $user_id, "updated");
    }

    public function delete()
    {
        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Lead invalido."]);
            return;
        }

        $delete_data = [
            "deleted" => 1,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_leads_model->ci_save($delete_data, $id);

        if (function_exists("green_audit")) {
            green_audit("lead", $id, "deleted", null, null, (int) $this->login_user->id);
        }

        echo json_encode(["success" => true, "message" => "Lead excluido."]);
    }

    public function view($id)
    {
        $id = (int) $id;
        $lead = $this->Green_leads_model->get_details(["id" => $id])->getRow();
        if (!$lead) {
            return $this->template->render('Green_crm\Views\ficha_lead', ["lead" => null]);
        }

        $client = $this->Green_clients_model->get_details(["id" => $lead->client_id])->getRow();
        $contacts = $this->Green_client_contacts_model->get_details(["client_id" => $lead->client_id])->getResult();
        $lives = $this->Green_lead_lives_model->get_details(["lead_id" => $id])->getResult();
        $quotes = $this->Green_quotes_model->get_details(["lead_id" => $id])->getResult();
        $sales = $this->Green_sales_model->get_details(["lead_id" => $id])->getResult();
        $commissions = $this->Green_commission_installments_model->get_by_lead($id)->getResult();
        $interactions = $this->Green_interactions_model->get_details(["lead_id" => $id])->getResult();
        $tasks = $this->Green_tasks_model->get_details(["lead_id" => $id])->getResult();
        $audit_logs = $this->Green_audit_logs_model->get_for_lead_and_client($id, (int) $lead->client_id)->getResult();

        return $this->template->render('Green_crm\Views\ficha_lead', [
            "lead" => $lead,
            "client" => $client,
            "contacts" => $contacts,
            "lives" => $lives,
            "quotes" => $quotes,
            "sales" => $sales,
            "commissions" => $commissions,
            "interactions" => $interactions,
            "tasks" => $tasks,
            "audit_logs" => $audit_logs
        ]);
    }

    public function contact_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $lead_id = (int) $this->request->getPost("lead_id");
        $contact = $id ? $this->Green_client_contacts_model->get_details(["id" => $id])->getRow() : new \stdClass();
        $lead = $lead_id ? $this->Green_leads_model->get_details(["id" => $lead_id])->getRow() : null;

        return $this->template->view('Green_crm\Views\modal_contact', [
            "model_info" => $contact ?: new \stdClass(),
            "lead_id" => $lead_id,
            "client_id" => (int) ($contact->client_id ?? ($lead->client_id ?? 0))
        ]);
    }

    public function save_contact()
    {
        $client_id = (int) $this->request->getPost("client_id");
        if (!$client_id) {
            echo json_encode(["success" => false, "message" => "Cliente invalido."]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        $now = date("Y-m-d H:i:s");
        $data = [
            "client_id" => $client_id,
            "name" => trim((string) $this->request->getPost("name")) ?: null,
            "role" => trim((string) $this->request->getPost("role")) ?: null,
            "phone_original" => trim((string) $this->request->getPost("phone")) ?: null,
            "phone_normalized" => green_normalize_phone($this->request->getPost("phone")),
            "email" => green_normalize_email($this->request->getPost("email")),
            "is_primary" => (int) $this->request->getPost("is_primary") ? 1 : 0,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => $now,
            "deleted" => 0
        ];
        if (!$id) {
            $data["created_by"] = (int) $this->login_user->id;
            $data["created_at"] = $now;
        }

        $this->Green_client_contacts_model->ci_save($data, $id);
        echo json_encode(["success" => true, "message" => "Contato salvo."]);
    }

    public function delete_contact()
    {
        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Contato invalido."]);
            return;
        }

        $data = ["deleted" => 1, "updated_by" => (int) $this->login_user->id, "updated_at" => date("Y-m-d H:i:s")];
        $this->Green_client_contacts_model->ci_save($data, $id);
        echo json_encode(["success" => true, "message" => "Contato removido."]);
    }

    public function life_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $lead_id = (int) $this->request->getPost("lead_id");
        return $this->template->view('Green_crm\Views\modal_life', [
            "model_info" => $id ? $this->Green_lead_lives_model->get_details(["id" => $id])->getRow() : new \stdClass(),
            "lead_id" => $lead_id
        ]);
    }

    public function save_life()
    {
        $lead_id = (int) $this->request->getPost("lead_id");
        if (!$lead_id) {
            echo json_encode(["success" => false, "message" => "Lead invalido."]);
            return;
        }

        $birth_date = $this->date_value($this->request->getPost("birth_date"));
        $data = [
            "lead_id" => $lead_id,
            "name" => trim((string) $this->request->getPost("name")) ?: null,
            "age" => (int) $this->request->getPost("age") ?: null,
            "birth_date" => $birth_date,
            "relationship" => trim((string) $this->request->getPost("relationship")) ?: null,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s"),
            "deleted" => 0
        ];

        $id = (int) $this->request->getPost("id");
        if (!$id) {
            $data["created_by"] = (int) $this->login_user->id;
            $data["created_at"] = date("Y-m-d H:i:s");
        }

        $this->Green_lead_lives_model->ci_save($data, $id);
        echo json_encode(["success" => true, "message" => "Vida salva."]);
    }

    public function interaction_modal_form()
    {
        return $this->template->view('Green_crm\Views\modal_interaction', [
            "lead_id" => (int) $this->request->getPost("lead_id")
        ]);
    }

    public function save_interaction()
    {
        $lead_id = (int) $this->request->getPost("lead_id");
        $subject = trim((string) $this->request->getPost("subject"));
        if (!$lead_id || !$subject) {
            echo json_encode(["success" => false, "message" => "Informe o lead e o assunto."]);
            return;
        }

        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;
        $data = [
            "lead_id" => $lead_id,
            "interaction_type" => trim((string) $this->request->getPost("interaction_type")) ?: "manual",
            "subject" => $subject,
            "description" => trim((string) $this->request->getPost("description")) ?: null,
            "created_by" => $user_id,
            "updated_by" => $user_id,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ];
        $interaction_id = $this->Green_interactions_model->ci_save($data);

        // Atualiza a data do ultimo contato/interacao no lead
        $last_interaction_data = ["last_interaction_at" => $now];
        $this->Green_leads_model->ci_save($last_interaction_data, $lead_id);

        if (function_exists("green_audit")) {
            green_audit("lead", $lead_id, "interaction_added", null, ["subject" => $subject], $user_id);
        }

        echo json_encode(["success" => true, "id" => $interaction_id, "message" => "Interação registrada."]);
    }

    public function task_modal_form()
    {
        return $this->template->view('Green_crm\Views\modal_task', [
            "lead_id" => (int) $this->request->getPost("lead_id"),
            "members_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"]),
            "current_user_id" => (int) $this->login_user->id,
            "model_info" => new \stdClass()
        ]);
    }

    public function save_task()
    {
        $lead_id = (int) $this->request->getPost("lead_id");
        $title = trim((string) $this->request->getPost("title"));
        if (!$lead_id || !$title) {
            echo json_encode(["success" => false, "message" => "Informe o lead e o titulo."]);
            return;
        }

        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;
        $lead = $this->Green_leads_model->get_details(["id" => $lead_id])->getRow();

        $data = [
            "lead_id" => $lead_id,
            "client_id" => $lead->client_id ?? null,
            "title" => $title,
            "description" => trim((string) $this->request->getPost("description")) ?: null,
            "due_date" => $this->date_value($this->request->getPost("due_date")),
            "responsible_id" => (int) $this->request->getPost("responsible_id") ?: $user_id,
            "priority" => $this->_valid_priority($this->request->getPost("priority")),
            "status" => "aberta",
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "created_by" => $user_id,
            "updated_by" => $user_id,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ];
        $task_id = $this->Green_tasks_model->ci_save($data);

        if (function_exists("green_audit")) {
            green_audit("lead", $lead_id, "task_created", null, ["task_id" => $task_id, "title" => $title], $user_id);
        }

        echo json_encode(["success" => true, "id" => $task_id, "message" => "Tarefa criada."]);
    }

    public function complete_task()
    {
        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Tarefa invalida."]);
            return;
        }

        $task = $this->Green_tasks_model->get_details(["id" => $id])->getRow();
        $data = ["status" => "concluida", "updated_by" => (int) $this->login_user->id, "updated_at" => date("Y-m-d H:i:s")];
        $this->Green_tasks_model->ci_save($data, $id);

        if ($task && function_exists("green_audit")) {
            green_audit("lead", (int) $task->lead_id, "task_completed", ["status" => $task->status], ["status" => "concluida"], (int) $this->login_user->id);
        }

        echo json_encode(["success" => true, "message" => "Tarefa concluida."]);
    }

    private function _valid_priority($priority)
    {
        return in_array($priority, ["baixa", "media", "alta", "urgente"], true) ? $priority : "media";
    }

    private function _lead_row($data)
    {
        if (!$data) {
            return [];
        }

        $actions = anchor(get_uri("green_crm/lead/" . $data->id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir ficha"]);
        $actions .= modal_anchor(get_uri("green_crm/lead_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar lead", "data-post-id" => $data->id]);
        $actions .= modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='file-text' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Criar cotação", "data-post-lead_id" => $data->id]);
        if ($data->status_title !== "Vendido") {
            $actions .= modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Converter em venda", "data-post-lead_id" => $data->id]);
        }
        $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-delete-lead", "data-id" => $data->id, "title" => "Excluir"]);

        return [
            $data->client_code ?: ($data->lead_code ?: ("LEAD-" . $data->id)),
            $data->client_name,
            green_format_document($data->document_number ?? null, $data->document_type ?? null),
            $data->phone_normalized ?: $data->phone_original,
            $data->email,
            $this->_status_badge($data->status_title ?: "Novo"),
            $this->_temperature_badge($data->temperature),
            $data->operator_name,
            $data->current_plan_name,
            $data->lives_qty,
            number_format((float) $data->current_paid_value, 2, ",", "."),
            number_format((float) $data->proposed_value, 2, ",", "."),
            $data->renewal_month,
            $data->region,
            $data->preferred_hospital_text,
            $actions
        ];
    }

    private function _status_badge($status)
    {
        $class = "bg-secondary";
        if (in_array($status, ["Vendido", "Qualificado"], true)) {
            $class = "bg-success";
        } elseif (in_array($status, ["Novo", "Primeiro contato"], true)) {
            $class = "bg-info";
        } elseif ($status === "Perdido") {
            $class = "bg-danger";
        } elseif ($status === "Proposta enviada") {
            $class = "bg-primary";
        }

        return "<span class='badge $class'>" . esc($status) . "</span>";
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
        $class = $classes[$key] ?? "bg-secondary";

        return "<span class='badge $class'>" . esc($labels[$key] ?? $key) . "</span>";
    }

    private function _dropdown_view_data()
    {
        return [
            "statuses_dropdown" => $this->_to_dropdown($this->Green_lead_statuses_model->get_details()->getResult(), "title", true),
            "sources_dropdown" => $this->_to_dropdown($this->Green_sources_model->get_details()->getResult(), "title", true),
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true)
        ];
    }

    private function _default_status_id()
    {
        return $this->Green_lead_statuses_model->get_id_by_title("Novo") ?: null;
    }

    private function _to_dropdown($rows, $field, $include_blank = false)
    {
        $result = $include_blank ? ["" => "-"] : [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->{$field};
        }
        return $result;
    }

    private function _client_type($type)
    {
        return in_array($type, ["PF", "PJ", "NAO_INFORMADO"], true) ? $type : "NAO_INFORMADO";
    }

    private function _valid_temperature($temperature)
    {
        return in_array($temperature, ["quente", "morno", "frio", "sem_classificacao"], true) ? $temperature : "sem_classificacao";
    }

    private function _month($value)
    {
        $value = (int) $value;
        return ($value >= 1 && $value <= 12) ? $value : null;
    }

    private function normalize_phone($value)
    {
        $digits = preg_replace("/\D+/", "", (string) $value) ?: "";
        if (!$digits) {
            return null;
        }

        $length = strlen($digits);
        if (strpos($digits, "55") === 0 && ($length === 12 || $length === 13)) {
            return $digits;
        }

        if ($length === 10 || $length === 11) {
            return "55" . $digits;
        }

        return $digits;
    }

    private function normalize_document($value)
    {
        $digits = preg_replace("/\D+/", "", (string) $value) ?: "";
        $type = "NAO_INFORMADO";

        if (strlen($digits) === 11) {
            $type = "CPF";
        } elseif (strlen($digits) === 14) {
            $type = "CNPJ";
        }

        return ["document_type" => $type, "document_number" => $digits];
    }

    private function normalize_email($value)
    {
        $email = strtolower(trim((string) $value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
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

        if (preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $value)) {
            return str_replace("T", " ", $value) . ":00";
        }

        if (preg_match("/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/", $value, $matches)) {
            return sprintf("%04d-%02d-%02d", (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date("Y-m-d", $timestamp) : null;
    }
}
