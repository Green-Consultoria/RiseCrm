<?php

namespace Green_crm\Models;

class Green_commission_rules_model extends Green_base_model
{
    protected $table = "green_commission_rules";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $rules = $this->db->prefixTable("green_commission_rules");
        $operators = $this->db->prefixTable("green_operators");
        $plans = $this->db->prefixTable("green_plans");
        $versions = $this->db->prefixTable("green_commission_grade_versions");
        $rule_inst = $this->db->prefixTable("green_commission_rule_installments");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $rules.id=" . (int) $id;
        }

        $grade_version_id = $this->_get_clean_value($options, "grade_version_id");
        if ($grade_version_id) {
            $where .= " AND $rules.grade_version_id=" . (int) $grade_version_id;
        }

        $grade_id = $this->_get_clean_value($options, "grade_id");
        if ($grade_id) {
            $where .= " AND $rules.grade_id=" . (int) $grade_id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $rules.status=" . $this->db->escape($status);
        }

        return $this->db->query("SELECT $rules.*,
                COALESCE($operators.name, $rules.operator_name_text) AS operator_display_name,
                $plans.name AS plan_registered_name,
                $versions.version_name,
                (SELECT COUNT(*) FROM $rule_inst WHERE $rule_inst.rule_id=$rules.id AND $rule_inst.deleted=0) AS installments_count
            FROM $rules
            LEFT JOIN $operators ON $operators.id=$rules.operator_id
            LEFT JOIN $plans ON $plans.id=$rules.plan_id
            LEFT JOIN $versions ON $versions.id=$rules.grade_version_id
            WHERE $rules.deleted=0 $where
            ORDER BY operator_display_name ASC, $rules.product_name ASC, $rules.id ASC");
    }

    /**
     * Localiza a regra mais compativel dentro de uma versao de grade.
     * Prioridade: operadora+plano > operadora+tipo > operadora > nome textual da operadora.
     */
    public function find_matching_rule($grade_version_id, $operator_id, $operator_name, $product_type, $plan_id, $lives_qty = null)
    {
        $rules = $this->db->prefixTable("green_commission_rules");
        $grade_version_id = (int) $grade_version_id;
        $operator_id = (int) $operator_id;
        $plan_id = (int) $plan_id;
        $base = "SELECT * FROM $rules WHERE deleted=0 AND status='Ativo' AND grade_version_id=$grade_version_id";

        $attempts = [];
        if ($operator_id && $plan_id) {
            $attempts[] = "$base AND operator_id=$operator_id AND plan_id=$plan_id";
        }
        if ($operator_id && $product_type) {
            $attempts[] = "$base AND operator_id=$operator_id AND product_type=" . $this->db->escape($product_type);
        }
        if ($operator_id) {
            $attempts[] = "$base AND operator_id=$operator_id";
        }
        if ($operator_name) {
            $like = $this->db->escapeLikeString($operator_name);
            $attempts[] = "$base AND operator_name_text LIKE '%$like%' ESCAPE '!'";
        }

        foreach ($attempts as $sql) {
            $row = $this->db->query($sql . " ORDER BY id ASC LIMIT 1")->getRow();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Monta o array $schedule consumido por Green_commission_installments_model::generate_for_sale,
     * a partir das parcelas-template (green_commission_rule_installments) de uma regra.
     */
    public function build_schedule_from_rule($rule, $sale_value, $sale_date)
    {
        if (!$rule || empty($rule->id)) {
            return [];
        }

        $rule_inst_model = model("Green_crm\Models\Green_commission_rule_installments_model");
        $templates = $rule_inst_model->get_by_rule($rule->id);
        if (!count($templates)) {
            return [];
        }

        $base = $sale_date ? strtotime($sale_date) : time();
        $sale_value = (float) $sale_value;
        $schedule = [];

        foreach ($templates as $template) {
            $offset = (int) $template->due_offset_months;
            $due = strtotime("+$offset month", $base);
            $rate = $template->commission_rate !== null ? (float) $template->commission_rate : null;

            $schedule[] = [
                "installment_no" => (int) $template->installment_no,
                "installment_label" => $template->installment_label,
                "commission_type" => "comissao",
                "due_month" => (int) date("n", $due),
                "due_year" => (int) date("Y", $due),
                "commission_rate" => $rate,
                "expected_amount" => $rate !== null ? round($sale_value * $rate, 2) : null,
                "commission_rule_id" => (int) $rule->id,
                "notes" => $template->notes
            ];
        }

        return $schedule;
    }
}
