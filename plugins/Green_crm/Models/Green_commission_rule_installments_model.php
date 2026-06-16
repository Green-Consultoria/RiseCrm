<?php

namespace Green_crm\Models;

class Green_commission_rule_installments_model extends Green_base_model
{
    protected $table = "green_commission_rule_installments";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_by_rule($rule_id)
    {
        $table = $this->_green_table();
        return $this->db->query("SELECT * FROM $table
            WHERE deleted=0 AND rule_id=" . (int) $rule_id . "
            ORDER BY installment_no ASC, id ASC")->getResult();
    }

    /**
     * Substitui as parcelas-template de uma regra (soft-delete das antigas + insere as novas).
     */
    public function replace_for_rule($rule_id, $rows, $user_id = 0)
    {
        $rule_id = (int) $rule_id;
        $table = $this->_green_table();
        $this->db->query("UPDATE $table SET deleted=1, updated_at=NOW() WHERE rule_id=$rule_id AND deleted=0");

        $now = date("Y-m-d H:i:s");
        $count = 0;
        foreach ($rows as $index => $row) {
            $rate = isset($row["commission_rate"]) && $row["commission_rate"] !== "" ? (float) $row["commission_rate"] : null;
            if ($rate === null) {
                continue;
            }

            $this->ci_save([
                "rule_id" => $rule_id,
                "installment_no" => (int) ($row["installment_no"] ?? ($index + 1)),
                "installment_label" => trim((string) ($row["installment_label"] ?? "")) ?: null,
                "commission_rate" => $rate,
                "due_offset_months" => (int) ($row["due_offset_months"] ?? $index),
                "notes" => trim((string) ($row["notes"] ?? "")) ?: null,
                "created_at" => $now,
                "updated_at" => $now,
                "deleted" => 0
            ]);
            $count++;
        }

        return $count;
    }

    public function total_multiplier_for_rule($rule_id)
    {
        $table = $this->_green_table();
        $row = $this->db->query("SELECT COALESCE(SUM(commission_rate),0) AS total
            FROM $table WHERE deleted=0 AND rule_id=" . (int) $rule_id)->getRow();
        return $row ? (float) $row->total : 0;
    }
}
