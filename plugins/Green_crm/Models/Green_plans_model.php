<?php

namespace Green_crm\Models;

class Green_plans_model extends Green_base_model
{
    protected $table = "green_plans";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $plans = $this->db->prefixTable("green_plans");
        $operators = $this->db->prefixTable("green_operators");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $plans.id=" . (int) $id;
        }

        $operator_id = $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $plans.operator_id=" . (int) $operator_id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $plans.status=" . $this->db->escape($status);
        }

        return $this->db->query("SELECT $plans.*, $operators.name AS operator_name
            FROM $plans
            LEFT JOIN $operators ON $operators.id=$plans.operator_id
            WHERE $plans.deleted=0 $where
            ORDER BY $operators.name ASC, $plans.name ASC");
    }

    public function normalize_name($name)
    {
        helper("green");
        return green_normalize_plan($name);
    }

    public function find_or_create($name, $operator_id)
    {
        helper("green");
        $name = green_normalize_plan($name);
        $normalized = green_ascii_key($name);
        $operator_id = (int) $operator_id;

        if (!$name || !$operator_id) {
            return 0;
        }

        $existing = $this->get_one_where(["operator_id" => $operator_id, "normalized_name" => $normalized, "deleted" => 0]);
        if ($existing && !empty($existing->id)) {
            return (int) $existing->id;
        }

        $data = [
            "operator_id" => $operator_id,
            "name" => $name,
            "normalized_name" => $normalized,
            "status" => "Ativo",
            "deleted" => 0
        ];

        return $this->ci_save($data);
    }
}
