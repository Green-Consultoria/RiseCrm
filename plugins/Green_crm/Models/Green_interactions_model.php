<?php

namespace Green_crm\Models;

class Green_interactions_model extends Green_base_model
{
    protected $table = "green_interactions";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $users = $this->db->prefixTable("users");
        $where = "";

        $lead_id = $this->_get_clean_value($options, "lead_id");
        if ($lead_id) {
            $where .= " AND $table.lead_id=" . (int) $lead_id;
        }

        if (array_key_exists("deleted", $options)) {
            $where .= " AND $table.deleted=" . (int) $this->_get_clean_value($options, "deleted");
        } else {
            $where .= " AND $table.deleted=0";
        }

        return $this->db->query("SELECT $table.*, CONCAT($users.first_name, ' ', $users.last_name) AS user_name
            FROM $table
            LEFT JOIN $users ON $users.id=$table.created_by AND $users.deleted=0
            WHERE 1=1 $where
            ORDER BY $table.id DESC");
    }

    public function add_system_interaction($lead_id, $subject, $description, $user_id = 0)
    {
        $lead_id = (int) $lead_id;
        if (!$lead_id) {
            return false;
        }

        $data = [
            "lead_id" => $lead_id,
            "interaction_type" => "system",
            "subject" => $subject,
            "description" => $description,
            "created_by" => (int) $user_id,
            "created_at" => date("Y-m-d H:i:s"),
            "deleted" => 0
        ];

        return $this->ci_save($data);
    }
}
