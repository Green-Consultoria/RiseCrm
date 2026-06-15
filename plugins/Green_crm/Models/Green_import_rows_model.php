<?php

namespace Green_crm\Models;

class Green_import_rows_model extends Green_base_model
{
    protected $table = "green_import_rows";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function add_row($batch_id, $row_number, $raw_json, $action, $entity_type, $target_id, $error_message, $warning_message = "")
    {
        $data = [
            "batch_id" => $batch_id,
            "row_number" => $row_number,
            "raw_json" => $raw_json,
            "action" => $action,
            "entity_type" => $entity_type,
            "target_id" => $target_id,
            "error_message" => $error_message,
            "warning_message" => $warning_message
        ];
        return $this->ci_save($data);
    }

    public function get_by_batch($batch_id)
    {
        return $this->get_details(["batch_id" => $batch_id]);
    }

    public function get_details($options = [])
    {
        $table = $this->_green_table();
        $where = "";
        $batch_id = $this->_get_clean_value($options, "batch_id");
        if ($batch_id) {
            $where .= " AND $table.batch_id=" . (int) $batch_id;
        }
        return $this->db->query("SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.row_number ASC, $table.id ASC");
    }
}
