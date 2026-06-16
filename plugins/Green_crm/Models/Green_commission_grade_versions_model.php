<?php

namespace Green_crm\Models;

class Green_commission_grade_versions_model extends Green_base_model
{
    protected $table = "green_commission_grade_versions";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $versions = $this->db->prefixTable("green_commission_grade_versions");
        $grades = $this->db->prefixTable("green_commission_grades");
        $rules = $this->db->prefixTable("green_commission_rules");
        $sales = $this->db->prefixTable("green_sales");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $versions.id=" . (int) $id;
        }

        $grade_id = $this->_get_clean_value($options, "grade_id");
        if ($grade_id) {
            $where .= " AND $versions.grade_id=" . (int) $grade_id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $versions.status=" . $this->db->escape($status);
        }

        return $this->db->query("SELECT $versions.*,
                $grades.name AS grade_name,
                $grades.partner_name,
                (SELECT COUNT(*) FROM $rules WHERE $rules.grade_version_id=$versions.id AND $rules.deleted=0) AS rules_count,
                (SELECT COUNT(*) FROM $sales WHERE $sales.commission_grade_version_id=$versions.id AND $sales.deleted=0) AS sales_count
            FROM $versions
            INNER JOIN $grades ON $grades.id=$versions.grade_id
            WHERE $versions.deleted=0 $where
            ORDER BY $versions.valid_from DESC, $versions.id DESC");
    }

    /**
     * Versao vigente de uma grade na data informada (congelamento na venda).
     */
    public function get_active_version_for_date($grade_id, $date)
    {
        $versions = $this->db->prefixTable("green_commission_grade_versions");
        $grade_id = (int) $grade_id;
        $date = $this->db->escape($date);

        return $this->db->query("SELECT * FROM $versions
            WHERE deleted=0 AND status='Ativa' AND grade_id=$grade_id
                AND (valid_from IS NULL OR valid_from<=$date)
                AND (valid_until IS NULL OR valid_until>=$date)
            ORDER BY valid_from DESC, id DESC
            LIMIT 1")->getRow();
    }

    public function find_by_grade_and_name($grade_id, $version_name)
    {
        $versions = $this->db->prefixTable("green_commission_grade_versions");
        return $this->db->query("SELECT * FROM $versions
            WHERE deleted=0 AND grade_id=" . (int) $grade_id . " AND version_name=" . $this->db->escape($version_name) . " LIMIT 1")->getRow();
    }

    public function get_dropdown_for_grade($grade_id, $only_active = true)
    {
        $result = [];
        $options = ["grade_id" => (int) $grade_id];
        if ($only_active) {
            $options["status"] = "Ativa";
        }
        foreach ($this->get_details($options)->getResult() as $version) {
            $label = $version->version_name;
            if ($version->valid_from) {
                $label .= " (desde " . date("d/m/Y", strtotime($version->valid_from)) . ")";
            }
            $result[$version->id] = $label;
        }
        return $result;
    }

    /**
     * Duplica uma versao (cabecalho + regras + parcelas-template) para facilitar a edicao.
     */
    public function duplicate_version($version_id, $new_name, $user_id = 0)
    {
        $version = $this->get_one($version_id);
        if (!$version || empty($version->id)) {
            return false;
        }

        $now = date("Y-m-d H:i:s");
        $new_version_id = $this->ci_save([
            "grade_id" => $version->grade_id,
            "version_name" => $new_name ?: ($version->version_name . " (cópia)"),
            "valid_from" => $version->valid_from,
            "valid_until" => $version->valid_until,
            "status" => "Ativa",
            "notes" => $version->notes,
            "source_file_name" => $version->source_file_name,
            "created_by" => (int) $user_id ?: null,
            "updated_by" => (int) $user_id ?: null,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ]);

        if (!$new_version_id) {
            return false;
        }

        $rules_model = model("Green_crm\Models\Green_commission_rules_model");
        $rule_inst_model = model("Green_crm\Models\Green_commission_rule_installments_model");
        foreach ($rules_model->get_details(["grade_version_id" => $version_id])->getResult() as $rule) {
            $new_rule_id = $rules_model->ci_save([
                "grade_id" => $rule->grade_id,
                "grade_version_id" => $new_version_id,
                "operator_id" => $rule->operator_id,
                "operator_name_text" => $rule->operator_name_text,
                "plan_id" => $rule->plan_id,
                "product_name" => $rule->product_name,
                "product_type" => $rule->product_type,
                "lives_range_text" => $rule->lives_range_text,
                "total_multiplier" => $rule->total_multiplier,
                "notes" => $rule->notes,
                "status" => $rule->status,
                "created_by" => (int) $user_id ?: null,
                "updated_by" => (int) $user_id ?: null,
                "created_at" => $now,
                "updated_at" => $now,
                "deleted" => 0
            ]);

            foreach ($rule_inst_model->get_by_rule($rule->id) as $inst) {
                $rule_inst_model->ci_save([
                    "rule_id" => $new_rule_id,
                    "installment_no" => $inst->installment_no,
                    "installment_label" => $inst->installment_label,
                    "commission_rate" => $inst->commission_rate,
                    "due_offset_months" => $inst->due_offset_months,
                    "notes" => $inst->notes,
                    "created_at" => $now,
                    "updated_at" => $now,
                    "deleted" => 0
                ]);
            }
        }

        return $new_version_id;
    }
}
