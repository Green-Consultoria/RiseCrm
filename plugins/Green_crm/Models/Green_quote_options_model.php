<?php

namespace Green_crm\Models;

class Green_quote_options_model extends Green_base_model
{
    protected $table = "green_quote_options";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $options_table = $this->db->prefixTable("green_quote_options");
        $operators = $this->db->prefixTable("green_operators");
        $plans = $this->db->prefixTable("green_plans");
        $where = "";
        $quote_id = $this->_get_clean_value($options, "quote_id");
        if ($quote_id) {
            $where .= " AND $options_table.quote_id=" . (int) $quote_id;
        }
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $options_table.id=" . (int) $id;
        }

        return $this->db->query("SELECT $options_table.*,
                $operators.name AS operator_name,
                $plans.name AS plan_registered_name,
                $plans.product_type AS plan_product_type,
                $plans.accommodation AS plan_accommodation,
                $plans.coparticipation AS plan_coparticipation
            FROM $options_table
            LEFT JOIN $operators ON $operators.id=$options_table.operator_id
            LEFT JOIN $plans ON $plans.id=$options_table.plan_id AND $plans.deleted=0
            WHERE $options_table.deleted=0 $where
            ORDER BY $options_table.is_selected DESC, $options_table.monthly_value ASC, $options_table.id ASC");
    }
}
