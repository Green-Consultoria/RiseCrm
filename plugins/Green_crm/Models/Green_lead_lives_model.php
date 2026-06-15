<?php

namespace Green_crm\Models;

class Green_lead_lives_model extends Green_base_model
{
    protected $table = "green_lead_lives";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $table = $this->_green_table();
        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=" . (int) $id;
        }
        $lead_id = $this->_get_clean_value($options, "lead_id");
        if ($lead_id) {
            $where .= " AND $table.lead_id=" . (int) $lead_id;
        }
        return $this->db->query("SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.id ASC");
    }
}
