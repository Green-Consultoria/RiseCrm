<?php

namespace Green_meta_leads\Controllers;

use App\Controllers\Security_Controller;

class Green_meta_leads extends Security_Controller
{
    public $Green_meta_raw_leads_model;
    public $Green_meta_sync_runs_model;
    public $Green_leads_model;
    public $Settings_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_meta_leads_install_or_update")) {
            green_meta_leads_install_or_update();
        }

        $this->Green_meta_raw_leads_model = model("Green_meta_leads\Models\Green_meta_raw_leads_model");
        $this->Green_meta_sync_runs_model = model("Green_meta_leads\Models\Green_meta_sync_runs_model");
        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Settings_model = model("App\Models\Settings_model");
    }

    public function index()
    {
        $this->_require("view");

        $view_data["sync_runs"] = $this->Green_meta_sync_runs_model->recent(8);
        $view_data["last_run"] = $view_data["sync_runs"][0] ?? null;
        $view_data["can_sync"] = $this->_can("sync");
        $view_data["is_admin"] = (bool) $this->login_user->is_admin;
        $view_data["token_configured"] = trim((string) $this->Settings_model->get_setting("green_meta_page_access_token")) !== "";

        $counts = $this->Green_meta_raw_leads_model->summary_counts();
        $view_data["summary"] = [
            "total" => (int) ($counts->total ?? 0),
            "created" => (int) ($counts->created ?? 0),
            "linked" => (int) ($counts->linked ?? 0),
            "errors" => (int) ($counts->errors ?? 0),
            "pending" => (int) ($counts->pending ?? 0),
            "duplicated" => (int) ($view_data["last_run"]->duplicate_updates ?? 0)
        ];
        $view_data["campaigns_dropdown"] = $this->Green_meta_raw_leads_model->distinct_campaigns();
        $view_data["forms_dropdown"] = $this->Green_meta_raw_leads_model->distinct_forms();

        return $this->template->render("Green_meta_leads\Views\index", $view_data);
    }

    public function list_data()
    {
        $this->_require("view");

        $options = [
            "process_status" => $this->request->getPost("process_status"),
            "facebook_form_id" => $this->request->getPost("facebook_form_id"),
            "campaign_name" => $this->request->getPost("campaign_name"),
            "captured_from" => $this->request->getPost("captured_from"),
            "captured_to" => $this->request->getPost("captured_to"),
            "search" => $this->request->getPost("search")
        ];

        $rows = [];
        foreach ($this->Green_meta_raw_leads_model->get_details($options)->getResult() as $data) {
            $rows[] = $this->_row($data);
        }

        echo json_encode(["data" => $rows]);
    }

    public function lead_modal_form()
    {
        $this->_require("view");

        $id = (int) $this->request->getPost("id");
        validate_numeric_value($id);

        $lead = $this->Green_meta_raw_leads_model->get_one($id);
        if (!$lead || empty($lead->id)) {
            show_404();
        }

        $green_lead = null;
        if (!empty($lead->green_lead_id)) {
            $green_lead = $this->Green_leads_model->get_details(["id" => (int) $lead->green_lead_id])->getRow();
        }

        return $this->template->view("Green_meta_leads\Views\modal_lead", [
            "lead" => $lead,
            "green_lead" => $green_lead
        ]);
    }

    public function sync()
    {
        if (!$this->_can("sync")) {
            echo json_encode(["success" => false, "message" => "Você não tem permissão para sincronizar."]);
            return;
        }

        $service = new \Green_meta_leads\Libraries\Meta_lead_sync_service((int) $this->login_user->id);
        $result = $service->run();

        echo json_encode([
            "success" => (bool) $result["success"],
            "message" => ($result["success"] ? "Sincronização concluída. " : "Sincronização falhou. ") . $result["message"],
            "details" => $result
        ]);
    }

    public function reprocess($raw_id = 0)
    {
        if (!$this->_can("sync")) {
            echo json_encode(["success" => false, "message" => "Você não tem permissão para reprocessar."]);
            return;
        }

        $raw_id = (int) $raw_id ?: (int) $this->request->getPost("id");
        if (!$raw_id) {
            echo json_encode(["success" => false, "message" => "Lead inválido."]);
            return;
        }

        $service = new \Green_meta_leads\Libraries\Meta_lead_sync_service((int) $this->login_user->id);
        $result = $service->reprocess_raw($raw_id);

        echo json_encode([
            "success" => (bool) $result["success"],
            "message" => $result["message"]
        ]);
    }

    public function settings()
    {
        $this->access_only_admin();

        $view_data["graph_version"] = trim((string) $this->Settings_model->get_setting("green_meta_graph_version")) ?: "v23.0";
        $view_data["form_ids"] = trim((string) $this->Settings_model->get_setting("green_meta_form_ids"));
        $view_data["since_days"] = (int) ($this->Settings_model->get_setting("green_meta_since_days") ?: 30);
        $view_data["token_configured"] = trim((string) $this->Settings_model->get_setting("green_meta_page_access_token")) !== "";

        return $this->template->render("Green_meta_leads\Views\settings", $view_data);
    }

    public function save_settings()
    {
        $this->access_only_admin();

        $token = trim((string) $this->request->getPost("green_meta_page_access_token"));
        //only overwrite the stored token when a new value is provided (blank submit keeps the current one)
        if ($token !== "") {
            $this->_save_setting("green_meta_page_access_token", $token);
        }

        $this->_save_setting("green_meta_graph_version", trim((string) $this->request->getPost("green_meta_graph_version")) ?: "v23.0");
        $this->_save_setting("green_meta_form_ids", trim((string) $this->request->getPost("green_meta_form_ids")));
        $this->_save_setting("green_meta_since_days", (string) ((int) $this->request->getPost("green_meta_since_days") ?: 30));

        echo json_encode(["success" => true, "message" => "Configurações salvas."]);
    }

    private function _save_setting($key, $value)
    {
        $this->Settings_model->save_setting($key, $value, "app");

        //keep the in-memory settings array consistent for the rest of this request
        $rise = config("Rise");
        if ($rise && isset($rise->app_settings_array) && is_array($rise->app_settings_array)) {
            $rise->app_settings_array[$key] = $value;
        }
    }

    private function _row($data)
    {
        $captured = $data->facebook_created_time ?: $data->imported_at;
        $phone = $data->phone_original ?: $data->phone_normalized;
        $name = $data->full_name ?: "Sem nome";

        $actions = modal_anchor(get_uri("green_meta_leads/lead_modal_form"), "<i data-feather='eye' class='icon-16'></i>", ["class" => "action-option", "title" => "Detalhes", "data-post-id" => $data->id]);
        if ($data->phone_normalized) {
            $actions .= anchor("https://wa.me/" . $data->phone_normalized, "<i data-feather='message-circle' class='icon-16'></i>", ["class" => "action-option", "title" => "Abrir WhatsApp", "target" => "_blank", "rel" => "noopener"]);
        }
        if ($this->_can("sync") && ($data->process_status === "error" || empty($data->green_lead_id))) {
            $actions .= js_anchor("<i data-feather='refresh-cw' class='icon-16'></i>", ["class" => "action-option green-meta-reprocess", "title" => "Reprocessar lead", "data-id" => $data->id]);
        }

        $status_badges = [
            "created" => "bg-success",
            "linked" => "bg-info",
            "duplicate" => "bg-secondary",
            "error" => "bg-danger",
            "pending" => "bg-warning"
        ];
        $badge = get_array_value($status_badges, $data->process_status) ?: "bg-secondary";

        if (!empty($data->green_lead_id)) {
            $green_link = anchor(get_uri("green_crm/lead/" . (int) $data->green_lead_id), "Lead #" . (int) $data->green_lead_id, ["target" => "_blank", "rel" => "noopener"]);
        } elseif ($data->process_message) {
            $green_link = "<span class='text-danger' title='" . esc($data->process_message) . "'>falha</span>";
        } else {
            $green_link = "-";
        }

        return [
            $captured ? format_to_datetime($captured) : "-",
            modal_anchor(get_uri("green_meta_leads/lead_modal_form"), esc($name), ["title" => "Detalhes", "data-post-id" => $data->id]),
            $phone ?: "-",
            $data->email ?: "-",
            $data->region ?: "-",
            $data->campaign_name ?: "-",
            $data->form_name ?: ($data->facebook_form_id ?: "-"),
            "<span class='badge " . $badge . "'>" . esc($data->process_status) . "</span>",
            $green_link,
            $actions
        ];
    }

    private function _can($permission)
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        if ($permission === "view" && $this->login_user->user_type === "staff") {
            return true;
        }

        return (bool) get_array_value($this->login_user->permissions, "green_meta_leads_" . $permission);
    }

    private function _require($permission)
    {
        if (!$this->_can($permission)) {
            app_redirect("forbidden");
        }
    }
}
