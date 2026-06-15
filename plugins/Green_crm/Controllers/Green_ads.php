<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_ads extends Security_Controller
{
    public $Green_ad_campaigns_model;
    public $Green_ad_sets_model;
    public $Green_ads_model;

    private $valid_status = ["active", "paused", "archived", "unknown"];

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_ad_campaigns_model = model("Green_crm\Models\Green_ad_campaigns_model");
        $this->Green_ad_sets_model = model("Green_crm\Models\Green_ad_sets_model");
        $this->Green_ads_model = model("Green_crm\Models\Green_ads_model");

        if (!green_can($this->login_user, "green_crm_view")) {
            app_redirect("forbidden");
        }
    }

    // ---- Campanhas ----
    public function index()
    {
        return $this->campaigns();
    }

    public function campaigns()
    {
        return $this->template->render("Green_crm\Views\lista_ad_campaigns");
    }

    public function campaigns_list_data()
    {
        $rows = [];
        $options = ["status" => $this->request->getPost("status"), "search" => $this->request->getPost("search")];
        foreach ($this->Green_ad_campaigns_model->get_details($options)->getResult() as $row) {
            $rows[] = [
                esc($row->name),
                $row->external_id ? esc($row->external_id) : "-",
                $this->_status_badge($row->status),
                $this->_money($row->spend),
                (int) $row->leads,
                $this->_money($row->cpl),
                (int) $row->sales_count,
                $this->_money($row->expected_commission),
                $this->_roi($row->roi),
                $this->_actions("campaign", $row->id)
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    // ---- Conjuntos ----
    public function ad_sets()
    {
        return $this->template->render("Green_crm\Views\lista_ad_sets", [
            "campaigns_dropdown" => $this->Green_ad_campaigns_model->get_dropdown(true)
        ]);
    }

    public function ad_sets_list_data()
    {
        $rows = [];
        $options = [
            "campaign_id" => $this->request->getPost("campaign_id"),
            "status" => $this->request->getPost("status"),
            "search" => $this->request->getPost("search")
        ];
        foreach ($this->Green_ad_sets_model->get_details($options)->getResult() as $row) {
            $rows[] = [
                esc($row->name),
                $row->campaign_name ? esc($row->campaign_name) : "-",
                $row->external_id ? esc($row->external_id) : "-",
                $this->_status_badge($row->status),
                $this->_money($row->spend),
                (int) $row->leads,
                $this->_money($row->cpl),
                (int) $row->sales_count,
                $this->_actions("adset", $row->id)
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    // ---- Criativos / Anuncios ----
    public function ads()
    {
        return $this->template->render("Green_crm\Views\lista_ads", [
            "campaigns_dropdown" => $this->Green_ad_campaigns_model->get_dropdown(true)
        ]);
    }

    public function ads_list_data()
    {
        $rows = [];
        $options = [
            "campaign_id" => $this->request->getPost("campaign_id"),
            "adset_id" => $this->request->getPost("adset_id"),
            "status" => $this->request->getPost("status"),
            "search" => $this->request->getPost("search")
        ];
        foreach ($this->Green_ads_model->get_details($options)->getResult() as $row) {
            $rows[] = [
                esc($row->name),
                $row->campaign_name ? esc($row->campaign_name) : "-",
                $row->adset_name ? esc($row->adset_name) : "-",
                $row->external_id ? esc($row->external_id) : "-",
                $this->_status_badge($row->status),
                $this->_money($row->spend),
                (int) $row->leads,
                (int) $row->sales_count,
                $this->_money($row->expected_commission),
                $this->_actions("ad", $row->id)
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    // ---- ROI por venda ----
    public function roi()
    {
        return $this->template->render("Green_crm\Views\roi_por_venda");
    }

    public function roi_list_data()
    {
        $rows = [];
        foreach ($this->Green_ads_model->get_roi_by_sale()->getResult() as $row) {
            $rows[] = [
                $row->sale_code ?: ("#" . $row->sale_id),
                esc($row->client_name),
                $row->operator_name ? esc($row->operator_name) : "-",
                $row->plan_name ? esc($row->plan_name) : "-",
                $row->campaign_id ? esc($row->campaign_id) : "-",
                $row->ad_id ? esc($row->ad_id) : "-",
                $this->_money($row->sale_value),
                $this->_money($row->expected_commission),
                $row->sale_date ? format_to_date($row->sale_date) : "-"
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    // ---- CRUD compartilhado ----
    public function ad_modal_form()
    {
        if (!green_can($this->login_user, "green_crm_manage_settings")) {
            app_redirect("forbidden");
        }

        $type = $this->_valid_type($this->request->getPost("type"));
        $id = (int) $this->request->getPost("id");
        $model_info = new \stdClass();
        if ($id) {
            $model_info = $this->_model($type)->get_details(["id" => $id])->getRow() ?: new \stdClass();
        }

        return $this->template->view("Green_crm\Views\modal_ad", [
            "type" => $type,
            "model_info" => $model_info,
            "campaigns_dropdown" => $this->Green_ad_campaigns_model->get_dropdown(true),
            "adsets_dropdown" => $this->Green_ad_sets_model->get_dropdown(true)
        ]);
    }

    public function save_ad()
    {
        if (!green_can($this->login_user, "green_crm_manage_settings")) {
            echo json_encode(["success" => false, "message" => "Sem permissão."]);
            return;
        }

        $type = $this->_valid_type($this->request->getPost("type"));
        $name = trim((string) $this->request->getPost("name"));
        if (!$name) {
            echo json_encode(["success" => false, "message" => "Informe o nome."]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;

        $data = [
            "name" => $name,
            "external_id" => trim((string) $this->request->getPost("external_id")) ?: null,
            "status" => in_array($this->request->getPost("status"), $this->valid_status, true) ? $this->request->getPost("status") : "unknown",
            "spend" => green_money_to_float($this->request->getPost("spend")),
            "impressions" => $this->_int($this->request->getPost("impressions")),
            "reach" => $this->_int($this->request->getPost("reach")),
            "clicks" => $this->_int($this->request->getPost("clicks")),
            "leads" => $this->_int($this->request->getPost("leads")),
            "cpl" => green_money_to_float($this->request->getPost("cpl")),
            "ctr" => $this->_float($this->request->getPost("ctr")),
            "sales_count" => $this->_int($this->request->getPost("sales_count")),
            "expected_commission" => green_money_to_float($this->request->getPost("expected_commission")),
            "roi" => $this->_float($this->request->getPost("roi")),
            "updated_by" => $user_id,
            "updated_at" => $now,
            "deleted" => 0
        ];

        if ($type === "adset" || $type === "ad") {
            $data["campaign_id"] = (int) $this->request->getPost("campaign_id") ?: null;
        }
        if ($type === "ad") {
            $data["adset_id"] = (int) $this->request->getPost("adset_id") ?: null;
            $data["creative_thumb_url"] = trim((string) $this->request->getPost("creative_thumb_url")) ?: null;
        }

        if (!$id) {
            $data["created_by"] = $user_id;
            $data["created_at"] = $now;
        }

        $saved_id = $this->_model($type)->ci_save($data, $id);
        echo json_encode(["success" => true, "id" => $saved_id, "message" => "Registro salvo."]);
    }

    public function delete_ad()
    {
        if (!green_can($this->login_user, "green_crm_manage_settings")) {
            echo json_encode(["success" => false, "message" => "Sem permissão."]);
            return;
        }

        $type = $this->_valid_type($this->request->getPost("type"));
        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Registro inválido."]);
            return;
        }

        $delete_data = [
            "deleted" => 1,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->_model($type)->ci_save($delete_data, $id);

        echo json_encode(["success" => true, "message" => "Registro removido."]);
    }

    private function _model($type)
    {
        if ($type === "campaign") {
            return $this->Green_ad_campaigns_model;
        }
        if ($type === "adset") {
            return $this->Green_ad_sets_model;
        }
        return $this->Green_ads_model;
    }

    private function _valid_type($type)
    {
        return in_array($type, ["campaign", "adset", "ad"], true) ? $type : "campaign";
    }

    private function _actions($type, $id)
    {
        $actions = "";
        if (green_can($this->login_user, "green_crm_manage_settings")) {
            $actions .= modal_anchor(get_uri("green_crm/ad_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $id, "data-post-type" => $type]);
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-ad-delete", "data-id" => $id, "data-type" => $type, "title" => "Excluir"]);
        }
        return $actions ?: "-";
    }

    private function _status_badge($status)
    {
        $map = ["active" => ["Ativo", "bg-success"], "paused" => ["Pausado", "bg-warning"], "archived" => ["Arquivado", "bg-secondary"], "unknown" => ["—", "bg-light text-dark"]];
        $s = $map[$status] ?? ["—", "bg-secondary"];
        return "<span class='badge " . $s[1] . "'>" . $s[0] . "</span>";
    }

    private function _money($value)
    {
        if ($value === null || $value === "") {
            return "-";
        }
        return "R$ " . number_format((float) $value, 2, ",", ".");
    }

    private function _roi($value)
    {
        if ($value === null || $value === "") {
            return "-";
        }
        return number_format((float) $value, 2, ",", ".") . "x";
    }

    private function _int($value)
    {
        $value = trim((string) $value);
        return $value === "" ? null : (int) preg_replace("/\D+/", "", $value);
    }

    private function _float($value)
    {
        $value = trim((string) $value);
        if ($value === "") {
            return null;
        }
        $value = str_replace(",", ".", $value);
        return is_numeric($value) ? (float) $value : null;
    }
}
