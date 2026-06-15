<?php

namespace Green_crm\Models;

class Green_audit_logs_model extends Green_base_model
{
    protected $table = "green_audit_logs";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_lead_and_client($lead_id, $client_id)
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $users = $this->db->prefixTable("users");
        $where = " AND (";
        $where .= "($table.entity_type='lead' AND $table.entity_id=" . (int) $lead_id . ")";
        if ($client_id) {
            $where .= " OR ($table.entity_type='client' AND $table.entity_id=" . (int) $client_id . ")";
        }
        $where .= ")";

        return $this->db->query("SELECT $table.*, CONCAT($users.first_name, ' ', $users.last_name) AS user_name
            FROM $table
            LEFT JOIN $users ON $users.id=$table.user_id AND $users.deleted=0
            WHERE $table.deleted=0 $where
            ORDER BY $table.id DESC");
    }
}
