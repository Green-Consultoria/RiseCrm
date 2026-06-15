<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_tasks extends Security_Controller
{
    public $Green_tasks_model;
    public $Green_leads_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_tasks_model = model("Green_crm\Models\Green_tasks_model");
        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Users_model = model("App\Models\Users_model");

        if (!green_can($this->login_user, "green_crm_view")) {
            app_redirect("forbidden");
        }
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_tasks", [
            "members_dropdown" => $this->_members_dropdown()
        ]);
    }

    public function list_data()
    {
        $options = [
            "responsible_id" => $this->request->getPost("responsible_id"),
            "status" => $this->request->getPost("status"),
            "priority" => $this->request->getPost("priority"),
            "due_filter" => $this->request->getPost("due_filter"),
            "search" => $this->request->getPost("search")
        ];

        $rows = [];
        foreach ($this->Green_tasks_model->get_general($options)->getResult() as $task) {
            $rows[] = $this->_row($task);
        }

        echo json_encode(["data" => $rows]);
    }

    public function modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $model_info = $id ? $this->Green_tasks_model->get_details(["id" => $id])->getRow() : new \stdClass();

        return $this->template->view("Green_crm\Views\modal_task", [
            "model_info" => $model_info ?: new \stdClass(),
            "lead_id" => (int) ($model_info->lead_id ?? 0),
            "members_dropdown" => $this->_members_dropdown(),
            "current_user_id" => (int) $this->login_user->id,
            "leads_dropdown" => $this->Green_leads_model->get_dropdown(true),
            "form_action" => "green_crm/save_general_task",
            "reload_on_success" => false
        ]);
    }

    public function save_general_task()
    {
        $title = trim((string) $this->request->getPost("title"));
        if (!$title) {
            echo json_encode(["success" => false, "message" => "Informe o titulo da tarefa."]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        $lead_id = (int) $this->request->getPost("lead_id") ?: null;
        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;

        $client_id = null;
        if ($lead_id) {
            $lead = $this->Green_leads_model->get_details(["id" => $lead_id])->getRow();
            $client_id = $lead->client_id ?? null;
        }

        $old = $id ? $this->Green_tasks_model->get_details(["id" => $id])->getRow() : null;

        $data = [
            "lead_id" => $lead_id,
            "client_id" => $client_id,
            "title" => $title,
            "description" => trim((string) $this->request->getPost("description")) ?: null,
            "due_date" => green_date_value($this->request->getPost("due_date")) ? $this->_datetime($this->request->getPost("due_date")) : null,
            "responsible_id" => (int) $this->request->getPost("responsible_id") ?: $user_id,
            "priority" => $this->_valid_priority($this->request->getPost("priority")),
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "updated_by" => $user_id,
            "updated_at" => $now,
            "deleted" => 0
        ];

        if ($id) {
            $data["status"] = $this->_valid_status($this->request->getPost("status"));
        } else {
            $data["status"] = "aberta";
            $data["created_by"] = $user_id;
            $data["created_at"] = $now;
        }

        $task_id = $this->Green_tasks_model->ci_save($data, $id);

        if (function_exists("green_audit") && $lead_id) {
            if ($id) {
                $action = ($old && $old->status !== $data["status"]) ? "task_status_changed" : "task_updated";
                green_audit("lead", $lead_id, $action, $old ? ["status" => $old->status, "title" => $old->title] : null, ["status" => $data["status"], "title" => $title], $user_id);
            } else {
                green_audit("lead", $lead_id, "task_created", null, ["task_id" => $task_id, "title" => $title], $user_id);
            }
        }

        echo json_encode(["success" => true, "id" => $task_id, "message" => "Tarefa salva."]);
    }

    public function update_status()
    {
        $id = (int) $this->request->getPost("id");
        $status = $this->_valid_status($this->request->getPost("status"));
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Tarefa invalida."]);
            return;
        }

        $task = $this->Green_tasks_model->get_details(["id" => $id])->getRow();
        $status_data = [
            "status" => $status,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_tasks_model->ci_save($status_data, $id);

        if ($task && function_exists("green_audit") && $task->lead_id) {
            green_audit("lead", (int) $task->lead_id, "task_status_changed", ["status" => $task->status], ["status" => $status], (int) $this->login_user->id);
        }

        echo json_encode(["success" => true, "message" => "Status atualizado."]);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Tarefa invalida."]);
            return;
        }

        $delete_data = [
            "deleted" => 1,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_tasks_model->ci_save($delete_data, $id);

        echo json_encode(["success" => true, "message" => "Tarefa removida."]);
    }

    private function _row($task)
    {
        $priorities = ["baixa" => ["Baixa", "bg-secondary"], "media" => ["Média", "bg-info"], "alta" => ["Alta", "bg-warning"], "urgente" => ["Urgente", "bg-danger"]];
        $statuses = ["aberta" => ["Aberta", "bg-warning"], "em_andamento" => ["Em andamento", "bg-info"], "concluida" => ["Concluída", "bg-success"], "cancelada" => ["Cancelada", "bg-secondary"]];

        $p = $priorities[$task->priority] ?? ["-", "bg-secondary"];
        $s = $statuses[$task->status] ?? [$task->status, "bg-secondary"];

        $due = "-";
        if (!empty($task->due_date) && $task->due_date !== "0000-00-00 00:00:00") {
            $is_overdue = strtotime($task->due_date) < time() && in_array($task->status, ["aberta", "em_andamento"], true);
            $due = "<span class='" . ($is_overdue ? "text-danger" : "") . "'>" . format_to_datetime($task->due_date) . "</span>";
        }

        $lead_link = "-";
        if ($task->lead_id) {
            $lead_link = anchor(get_uri("green_crm/lead/" . (int) $task->lead_id), esc($task->lead_code ?: ("GREEN-" . $task->lead_id)), ["title" => "Abrir ficha"]);
        }

        $actions = modal_anchor(get_uri("green_crm/task_general_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $task->id]);
        if (in_array($task->status, ["aberta", "em_andamento"], true)) {
            $actions .= js_anchor("<i data-feather='check' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-task-complete", "data-id" => $task->id, "title" => "Concluir"]);
        }
        $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-task-delete", "data-id" => $task->id, "title" => "Excluir"]);

        return [
            esc($task->title),
            $task->client_name ? esc($task->client_name) : "-",
            $lead_link,
            "<span class='badge " . $p[1] . "'>" . $p[0] . "</span>",
            "<span class='badge " . $s[1] . "'>" . $s[0] . "</span>",
            esc($task->responsible_name ?: "-"),
            $due,
            $actions
        ];
    }

    private function _members_dropdown()
    {
        return $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"]);
    }

    private function _valid_priority($priority)
    {
        return in_array($priority, ["baixa", "media", "alta", "urgente"], true) ? $priority : "media";
    }

    private function _valid_status($status)
    {
        return in_array($status, ["aberta", "em_andamento", "concluida", "cancelada"], true) ? $status : "aberta";
    }

    private function _datetime($value)
    {
        $value = trim((string) $value);
        if (preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $value)) {
            return str_replace("T", " ", $value) . ":00";
        }
        $date = green_date_value($value);
        return $date ? $date . " 09:00:00" : null;
    }
}
