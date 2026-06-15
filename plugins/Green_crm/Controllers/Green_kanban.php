<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_kanban extends Security_Controller
{
    public $Green_leads_model;
    public $Green_lead_statuses_model;
    public $Green_interactions_model;

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
        $this->Green_interactions_model = model("Green_crm\Models\Green_interactions_model");
    }

    public function index()
    {
        $statuses = $this->Green_lead_statuses_model->get_details()->getResult();
        $leads_by_status = [];

        foreach ($statuses as $status) {
            $leads_by_status[(int) $status->id] = [];
        }

        foreach ($this->Green_leads_model->get_details()->getResult() as $lead) {
            $status_id = (int) ($lead->status_id ?? 0);
            if (!isset($leads_by_status[$status_id])) {
                continue;
            }

            $leads_by_status[$status_id][] = $lead;
        }

        return $this->template->render("Green_crm\Views\kanban", [
            "statuses" => $statuses,
            "leads_by_status" => $leads_by_status
        ]);
    }

    public function update_lead_status()
    {
        $lead_id = (int) $this->request->getPost("lead_id");
        $status_id = (int) $this->request->getPost("status_id");
        $lost_reason = $this->_limit_lost_reason(trim((string) $this->request->getPost("lost_reason")));

        if (!$lead_id || !$status_id) {
            echo json_encode(["success" => false, "message" => "Lead ou status invalido."]);
            return;
        }

        $lead = $this->Green_leads_model->get_details(["id" => $lead_id])->getRow();
        $new_status = $this->Green_lead_statuses_model->get_details(["id" => $status_id])->getRow();

        if (!$lead || !$new_status) {
            echo json_encode(["success" => false, "message" => "Lead ou status nao encontrado."]);
            return;
        }

        $old_status_id = (int) ($lead->status_id ?? 0);
        $old_status_title = $lead->status_title ?: "Sem status";
        $new_status_title = $new_status->title ?: "Sem status";

        if ($this->_is_won_status($new_status)) {
            echo json_encode([
                "success" => false,
                "blocked" => true,
                "restore_status_id" => $old_status_id,
                "message" => "Lead so pode virar Vendido quando uma venda for criada. Use a acao Converter em venda."
            ]);
            return;
        }

        if ($this->_is_lost_status($new_status) && $lost_reason === "") {
            echo json_encode([
                "success" => false,
                "require_lost_reason" => true,
                "restore_status_id" => $old_status_id,
                "message" => "Informe o motivo da perda para mover o lead para Perdido."
            ]);
            return;
        }

        if ($old_status_id === $status_id) {
            echo json_encode(["success" => true, "message" => "Status mantido."]);
            return;
        }

        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;
        $data = [
            "status_id" => $status_id,
            "updated_by" => $user_id,
            "updated_at" => $now
        ];

        if ($this->_is_lost_status($new_status)) {
            $data["lost_reason"] = $lost_reason;
        }

        $this->Green_leads_model->ci_save($data, $lead_id);

        $description = "Status alterado de " . $old_status_title . " para " . $new_status_title;
        if ($this->_is_lost_status($new_status) && $lost_reason !== "") {
            $description .= ". Motivo da perda: " . $lost_reason;
        }

        $this->Green_interactions_model->add_system_interaction($lead_id, "Status alterado", $description, $user_id);

        echo json_encode([
            "success" => true,
            "message" => "Status atualizado.",
            "lead_id" => $lead_id,
            "status_id" => $status_id,
            "old_status_id" => $old_status_id
        ]);
    }

    private function _is_won_status($status)
    {
        if ((int) ($status->is_won ?? 0)) {
            return true;
        }

        return green_ascii_key($status->title ?? "") === "VENDIDO";
    }

    private function _is_lost_status($status)
    {
        if ((int) ($status->is_lost ?? 0)) {
            return true;
        }

        return green_ascii_key($status->title ?? "") === "PERDIDO";
    }

    private function _limit_lost_reason($value)
    {
        $value = trim((string) $value);
        if ($value === "") {
            return "";
        }

        return function_exists("mb_substr") ? mb_substr($value, 0, 255) : substr($value, 0, 255);
    }
}
