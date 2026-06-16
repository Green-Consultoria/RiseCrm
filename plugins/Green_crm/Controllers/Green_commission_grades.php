<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_commission_grades extends Security_Controller
{
    public $Green_commission_grades_model;
    public $Green_commission_grade_versions_model;
    public $Green_commission_rules_model;
    public $Green_commission_rule_installments_model;
    public $Green_operators_model;
    public $Green_plans_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_commission_grades_model = model("Green_crm\Models\Green_commission_grades_model");
        $this->Green_commission_grade_versions_model = model("Green_crm\Models\Green_commission_grade_versions_model");
        $this->Green_commission_rules_model = model("Green_crm\Models\Green_commission_rules_model");
        $this->Green_commission_rule_installments_model = model("Green_crm\Models\Green_commission_rule_installments_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_plans_model = model("Green_crm\Models\Green_plans_model");
    }

    private function _can_manage()
    {
        return green_can($this->login_user, "green_crm_manage_commission_grades", true);
    }

    private function _deny_if_cannot_manage()
    {
        if (!$this->_can_manage()) {
            echo json_encode(["success" => false, "message" => "Sem permissão para gerenciar grades de comissão."]);
            return true;
        }
        return false;
    }

    // ---------------------------------------------------------------- Grades

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_commission_grades", [
            "can_manage" => $this->_can_manage()
        ]);
    }

    public function grades_list_data()
    {
        $rows = [];
        foreach ($this->Green_commission_grades_model->get_details()->getResult() as $grade) {
            $actions = anchor(get_uri("green_crm/commission_grade/" . $grade->id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Ver versões"]);
            if ($this->_can_manage()) {
                $actions .= modal_anchor(get_uri("green_crm/commission_grade_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $grade->id]);
                if ($grade->status === "Ativa") {
                    $actions .= js_anchor("<i data-feather='slash' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-inactivate-grade", "data-id" => $grade->id, "title" => "Inativar"]);
                }
            }

            $rows[] = [
                $grade->name,
                $grade->partner_name ?: "-",
                $this->_status_badge($grade->status),
                (int) $grade->versions_count,
                $grade->updated_at,
                $actions
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    public function commission_grade_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        return $this->template->view("Green_crm\Views\modal_commission_grade", [
            "model_info" => $id ? $this->Green_commission_grades_model->get_one($id) : new \stdClass()
        ]);
    }

    public function save_grade()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $this->validate_submitted_data(["name" => "required"]);
        $id = (int) $this->request->getPost("id");
        $old = $id ? $this->Green_commission_grades_model->get_one($id) : null;

        $data = [
            "name" => trim((string) $this->request->getPost("name")),
            "partner_name" => trim((string) $this->request->getPost("partner_name")) ?: null,
            "description" => trim((string) $this->request->getPost("description")) ?: null,
            "status" => $this->request->getPost("status") ?: "Ativa",
            "updated_by" => (int) $this->login_user->id,
            "deleted" => 0
        ];
        if (!$id) {
            $data["created_by"] = (int) $this->login_user->id;
        }
        $save_id = $this->Green_commission_grades_model->ci_save($data, $id);
        green_audit("commission_grade", $save_id, $id ? "grade_updated" : "grade_created", $old, $data, $this->login_user->id);
        echo json_encode(["success" => true, "id" => $save_id, "message" => "Grade salva."]);
    }

    public function inactivate_grade()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $id = (int) $this->request->getPost("id");
        $this->Green_commission_grades_model->ci_save(["status" => "Inativa", "updated_by" => (int) $this->login_user->id], $id);
        green_audit("commission_grade", $id, "grade_inactivated", null, null, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Grade inativada."]);
    }

    // -------------------------------------------------------------- Versions

    public function view($grade_id)
    {
        $grade = $this->Green_commission_grades_model->get_details(["id" => (int) $grade_id])->getRow();
        return $this->template->render("Green_crm\Views\view_commission_grade", [
            "grade" => $grade,
            "can_manage" => $this->_can_manage()
        ]);
    }

    public function versions_list_data()
    {
        $grade_id = (int) $this->request->getPost("grade_id");
        $rows = [];
        foreach ($this->Green_commission_grade_versions_model->get_details(["grade_id" => $grade_id])->getResult() as $version) {
            $actions = anchor(get_uri("green_crm/commission_grade_version/" . $version->id), "<i data-feather='list' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Ver regras"]);
            if ($this->_can_manage()) {
                $actions .= modal_anchor(get_uri("green_crm/commission_version_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $version->id, "data-post-grade_id" => $grade_id]);
                $actions .= js_anchor("<i data-feather='copy' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-duplicate-version", "data-id" => $version->id, "title" => "Duplicar versão"]);
                if ($version->status === "Ativa") {
                    $actions .= js_anchor("<i data-feather='slash' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-inactivate-version", "data-id" => $version->id, "title" => "Inativar"]);
                }
            }

            $rows[] = [
                $version->version_name,
                $version->valid_from ? date("d/m/Y", strtotime($version->valid_from)) : "-",
                $version->valid_until ? date("d/m/Y", strtotime($version->valid_until)) : "-",
                $this->_status_badge($version->status),
                (int) $version->rules_count,
                (int) $version->sales_count,
                $version->source_file_name ?: "-",
                $actions
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    public function commission_version_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $grade_id = (int) $this->request->getPost("grade_id");
        $model_info = $id ? $this->Green_commission_grade_versions_model->get_one($id) : new \stdClass();
        if (!$id) {
            $model_info->grade_id = $grade_id;
        }
        return $this->template->view("Green_crm\Views\modal_commission_grade_version", ["model_info" => $model_info]);
    }

    public function save_version()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $this->validate_submitted_data(["grade_id" => "required|numeric", "version_name" => "required"]);
        $id = (int) $this->request->getPost("id");
        $old = $id ? $this->Green_commission_grade_versions_model->get_one($id) : null;

        $data = [
            "grade_id" => (int) $this->request->getPost("grade_id"),
            "version_name" => trim((string) $this->request->getPost("version_name")),
            "valid_from" => green_date_value($this->request->getPost("valid_from")),
            "valid_until" => green_date_value($this->request->getPost("valid_until")),
            "status" => $this->request->getPost("status") ?: "Ativa",
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "source_file_name" => trim((string) $this->request->getPost("source_file_name")) ?: null,
            "updated_by" => (int) $this->login_user->id,
            "deleted" => 0
        ];
        if (!$id) {
            $data["created_by"] = (int) $this->login_user->id;
        }
        $save_id = $this->Green_commission_grade_versions_model->ci_save($data, $id);
        green_audit("commission_grade_version", $save_id, $id ? "version_updated" : "version_created", $old, $data, $this->login_user->id);
        echo json_encode(["success" => true, "id" => $save_id, "message" => "Versão salva."]);
    }

    public function duplicate_version()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $id = (int) $this->request->getPost("id");
        $version = $this->Green_commission_grade_versions_model->get_one($id);
        if (!$version || empty($version->id)) {
            echo json_encode(["success" => false, "message" => "Versão inválida."]);
            return;
        }
        $new_name = trim((string) $this->request->getPost("version_name")) ?: ($version->version_name . " (cópia)");
        $new_id = $this->Green_commission_grade_versions_model->duplicate_version($id, $new_name, (int) $this->login_user->id);
        if (!$new_id) {
            echo json_encode(["success" => false, "message" => "Não foi possível duplicar."]);
            return;
        }
        green_audit("commission_grade_version", $new_id, "version_duplicated", ["source_version_id" => $id], null, $this->login_user->id);
        echo json_encode(["success" => true, "id" => $new_id, "message" => "Versão duplicada."]);
    }

    public function inactivate_version()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $id = (int) $this->request->getPost("id");
        $this->Green_commission_grade_versions_model->ci_save(["status" => "Inativa", "updated_by" => (int) $this->login_user->id], $id);
        green_audit("commission_grade_version", $id, "version_inactivated", null, null, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Versão inativada."]);
    }

    // ----------------------------------------------------------------- Rules

    public function version($version_id)
    {
        $version = $this->Green_commission_grade_versions_model->get_details(["id" => (int) $version_id])->getRow();
        return $this->template->render("Green_crm\Views\view_commission_grade_version", [
            "version" => $version,
            "can_manage" => $this->_can_manage()
        ]);
    }

    public function rules_list_data()
    {
        $version_id = (int) $this->request->getPost("grade_version_id");
        $rows = [];
        foreach ($this->Green_commission_rules_model->get_details(["grade_version_id" => $version_id])->getResult() as $rule) {
            $actions = "";
            if ($this->_can_manage()) {
                $actions .= modal_anchor(get_uri("green_crm/commission_rule_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $rule->id, "data-post-grade_version_id" => $version_id]);
                if ($rule->status === "Ativo") {
                    $actions .= js_anchor("<i data-feather='slash' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-inactivate-rule", "data-id" => $rule->id, "title" => "Inativar"]);
                }
            }

            $rows[] = [
                $rule->operator_display_name ?: "-",
                ($rule->product_name ?: ($rule->plan_registered_name ?: "-")),
                $rule->product_type ?: "-",
                $rule->lives_range_text ?: "-",
                $this->_multiplier_label($rule->total_multiplier),
                (int) $rule->installments_count . " parcela(s)",
                $this->_status_badge($rule->status, true),
                $actions
            ];
        }
        echo json_encode(["data" => $rows]);
    }

    public function commission_rule_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        $version_id = (int) $this->request->getPost("grade_version_id");
        $model_info = $id ? $this->Green_commission_rules_model->get_one($id) : new \stdClass();
        if (!$id) {
            $version = $this->Green_commission_grade_versions_model->get_one($version_id);
            $model_info->grade_version_id = $version_id;
            $model_info->grade_id = $version->grade_id ?? 0;
        }

        return $this->template->view("Green_crm\Views\modal_commission_rule", [
            "model_info" => $model_info,
            "installments" => $id ? $this->Green_commission_rule_installments_model->get_by_rule($id) : [],
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "plans_dropdown" => $this->_plans_dropdown()
        ]);
    }

    public function save_rule()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $this->validate_submitted_data(["grade_version_id" => "required|numeric"]);
        $id = (int) $this->request->getPost("id");
        $version_id = (int) $this->request->getPost("grade_version_id");
        $version = $this->Green_commission_grade_versions_model->get_one($version_id);
        if (!$version || empty($version->id)) {
            echo json_encode(["success" => false, "message" => "Versão inválida."]);
            return;
        }

        $installments = $this->_rule_installments_from_request();
        $total_multiplier = green_money_to_float($this->request->getPost("total_multiplier"));
        if ($total_multiplier === null && count($installments)) {
            $total_multiplier = 0;
            foreach ($installments as $row) {
                $total_multiplier += (float) ($row["commission_rate"] ?? 0);
            }
        }

        $old = $id ? $this->Green_commission_rules_model->get_one($id) : null;
        $data = [
            "grade_id" => (int) $version->grade_id,
            "grade_version_id" => $version_id,
            "operator_id" => (int) $this->request->getPost("operator_id") ?: null,
            "operator_name_text" => trim((string) $this->request->getPost("operator_name_text")) ?: null,
            "plan_id" => (int) $this->request->getPost("plan_id") ?: null,
            "product_name" => trim((string) $this->request->getPost("product_name")) ?: null,
            "product_type" => trim((string) $this->request->getPost("product_type")) ?: null,
            "lives_range_text" => trim((string) $this->request->getPost("lives_range_text")) ?: null,
            "total_multiplier" => $total_multiplier,
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "status" => $this->request->getPost("status") ?: "Ativo",
            "updated_by" => (int) $this->login_user->id,
            "deleted" => 0
        ];
        if (!$id) {
            $data["created_by"] = (int) $this->login_user->id;
        }
        $save_id = $this->Green_commission_rules_model->ci_save($data, $id);
        $this->Green_commission_rule_installments_model->replace_for_rule($save_id, $installments, (int) $this->login_user->id);
        green_audit("commission_rule", $save_id, $id ? "rule_updated" : "rule_created", $old, array_merge($data, ["installments" => $installments]), $this->login_user->id);
        echo json_encode(["success" => true, "id" => $save_id, "message" => "Regra salva."]);
    }

    public function delete_rule()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $id = (int) $this->request->getPost("id");
        $this->Green_commission_rules_model->ci_save(["status" => "Inativo", "deleted" => 1, "updated_by" => (int) $this->login_user->id], $id);
        green_audit("commission_rule", $id, "rule_deleted", null, null, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Regra removida."]);
    }

    public function inactivate_rule()
    {
        if ($this->_deny_if_cannot_manage()) {
            return;
        }
        $id = (int) $this->request->getPost("id");
        $this->Green_commission_rules_model->ci_save(["status" => "Inativo", "updated_by" => (int) $this->login_user->id], $id);
        green_audit("commission_rule", $id, "rule_inactivated", null, null, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Regra inativada."]);
    }

    // --------------------------------------------------------------- Helpers

    private function _rule_installments_from_request()
    {
        $labels = $this->_as_array($this->request->getPost("installment_label"));
        $numbers = $this->_as_array($this->request->getPost("installment_no"));
        $rates = $this->_as_array($this->request->getPost("commission_rate"));
        $offsets = $this->_as_array($this->request->getPost("due_offset_months"));
        $notes = $this->_as_array($this->request->getPost("installment_notes"));

        $result = [];
        foreach ($rates as $index => $rate_value) {
            $rate = green_money_to_float($rate_value);
            if ($rate === null) {
                continue;
            }
            $result[] = [
                "installment_no" => (int) ($numbers[$index] ?? ($index + 1)),
                "installment_label" => trim((string) ($labels[$index] ?? "")),
                "commission_rate" => $rate,
                "due_offset_months" => (int) ($offsets[$index] ?? $index),
                "notes" => trim((string) ($notes[$index] ?? ""))
            ];
        }
        return $result;
    }

    private function _multiplier_label($multiplier)
    {
        if ($multiplier === null || $multiplier === "") {
            return "-";
        }
        $percent = (float) $multiplier * 100;
        return rtrim(rtrim(number_format($percent, 2, ",", "."), "0"), ",") . "%";
    }

    private function _status_badge($status, $masculine = false)
    {
        $active = $masculine ? "Ativo" : "Ativa";
        $class = $status === $active ? "bg-success" : "bg-secondary";
        return "<span class='badge $class'>" . esc($status) . "</span>";
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

    private function _as_array($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return $value === null ? [] : [$value];
    }
}
