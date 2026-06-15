<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_settings extends Security_Controller
{
    public $Green_operators_model;
    public $Green_plans_model;
    public $Green_lead_statuses_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_plans_model = model("Green_crm\Models\Green_plans_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
    }

    public function operators()
    {
        return $this->template->render("Green_crm\Views\settings_operators", ["active_settings_tab" => "operators"]);
    }

    public function operators_list_data()
    {
        $rows = [];
        foreach ($this->Green_operators_model->get_details()->getResult() as $data) {
            $rows[] = [$data->name, $data->normalized_name, $data->status, modal_anchor(get_uri("green_crm/operator_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "data-post-id" => $data->id, "title" => "Editar"])];
        }
        echo json_encode(["data" => $rows]);
    }

    public function operator_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        return $this->template->view("Green_crm\Views\modal_operator", ["model_info" => $id ? $this->Green_operators_model->get_one($id) : new \stdClass()]);
    }

    public function save_operator()
    {
        $this->validate_submitted_data(["name" => "required"]);
        $id = (int) $this->request->getPost("id");
        $name = green_normalize_operator($this->request->getPost("name"));
        $operator_data = ["name" => $name, "normalized_name" => green_ascii_key($name), "aliases" => $this->request->getPost("aliases"), "status" => $this->request->getPost("status") ?: "Ativo", "deleted" => 0];
        $save_id = $this->Green_operators_model->ci_save($operator_data, $id);
        echo json_encode(["success" => true, "id" => $save_id, "message" => "Operadora salva."]);
    }

    public function plans()
    {
        return $this->template->render("Green_crm\Views\settings_plans", ["active_settings_tab" => "plans"]);
    }

    public function plans_list_data()
    {
        $rows = [];
        foreach ($this->Green_plans_model->get_details()->getResult() as $data) {
            $rows[] = [$data->name, $data->normalized_name, $data->status, modal_anchor(get_uri("green_crm/plan_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "data-post-id" => $data->id, "title" => "Editar"])];
        }
        echo json_encode(["data" => $rows]);
    }

    public function plan_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        return $this->template->view("Green_crm\Views\modal_plan", ["model_info" => $id ? $this->Green_plans_model->get_one($id) : new \stdClass()]);
    }

    public function save_plan()
    {
        $this->validate_submitted_data(["name" => "required"]);
        $id = (int) $this->request->getPost("id");
        $name = trim((string) $this->request->getPost("name"));
        $plan_data = ["name" => green_normalize_plan($name), "normalized_name" => green_ascii_key(green_normalize_plan($name)), "plan_name" => $name, "status" => $this->request->getPost("status") ?: "Ativo", "deleted" => 0];
        $save_id = $this->Green_plans_model->ci_save($plan_data, $id);
        echo json_encode(["success" => true, "id" => $save_id, "message" => "Plano salvo."]);
    }

    public function statuses()
    {
        return $this->template->render("Green_crm\Views\settings_statuses", ["active_settings_tab" => "statuses"]);
    }

    public function statuses_list_data()
    {
        $rows = [];
        foreach ($this->Green_lead_statuses_model->get_details()->getResult() as $data) {
            $rows[] = [$data->sort, $data->title, $data->is_final, $data->is_won, $data->is_lost, modal_anchor(get_uri("green_crm/status_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "data-post-id" => $data->id, "title" => "Editar"])];
        }
        echo json_encode(["data" => $rows]);
    }

    public function status_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        return $this->template->view("Green_crm\Views\modal_status", ["model_info" => $id ? $this->Green_lead_statuses_model->get_one($id) : new \stdClass()]);
    }

    public function save_status()
    {
        $this->validate_submitted_data(["title" => "required"]);
        $id = (int) $this->request->getPost("id");
        $status_data = [
            "title" => trim((string) $this->request->getPost("title")),
            "sort" => (int) $this->request->getPost("sort"),
            "is_final" => (int) $this->request->getPost("is_final"),
            "is_won" => (int) $this->request->getPost("is_won"),
            "is_lost" => (int) $this->request->getPost("is_lost"),
            "deleted" => 0
        ];
        $save_id = $this->Green_lead_statuses_model->ci_save($status_data, $id);
        echo json_encode(["success" => true, "id" => $save_id, "message" => "Status salvo."]);
    }
}
