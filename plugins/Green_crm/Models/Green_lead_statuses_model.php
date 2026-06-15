<?php

namespace Green_crm\Models;

class Green_lead_statuses_model extends Green_base_model
{
    protected $table = "green_lead_statuses";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=" . (int) $id;
        }

        $title = $this->_get_clean_value($options, "title");
        if ($title) {
            $where .= " AND $table.title LIKE '%" . $this->db->escapeLikeString($title) . "%' ESCAPE '!'";
        }

        if (array_key_exists("deleted", $options)) {
            $where .= " AND $table.deleted=" . (int) $this->_get_clean_value($options, "deleted");
        } else {
            $where .= " AND $table.deleted=0";
        }

        return $this->db->query("SELECT $table.* FROM $table WHERE 1=1 $where ORDER BY $table.sort ASC, $table.id ASC");
    }

    public function get_id_by_title($title)
    {
        $title = trim((string) $title);
        if ($title === "") {
            return null;
        }

        $table = $this->db->prefixTable($this->table_without_prefix);
        $row = $this->db->query("SELECT id FROM $table WHERE deleted=0 AND title=" . $this->db->escape($title) . " LIMIT 1")->getRow();

        return $row && !empty($row->id) ? (int) $row->id : null;
    }
}
