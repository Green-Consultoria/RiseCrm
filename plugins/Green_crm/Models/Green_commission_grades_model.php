<?php

namespace Green_crm\Models;

class Green_commission_grades_model extends Green_base_model
{
    protected $table = "green_commission_grades";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $grades = $this->db->prefixTable("green_commission_grades");
        $versions = $this->db->prefixTable("green_commission_grade_versions");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $grades.id=" . (int) $id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $grades.status=" . $this->db->escape($status);
        }

        $name = $this->_get_clean_value($options, "name");
        if ($name) {
            $where .= " AND $grades.name=" . $this->db->escape($name);
        }

        return $this->db->query("SELECT $grades.*,
                (SELECT COUNT(*) FROM $versions WHERE $versions.grade_id=$grades.id AND $versions.deleted=0) AS versions_count
            FROM $grades
            WHERE $grades.deleted=0 $where
            ORDER BY $grades.name ASC, $grades.id DESC");
    }

    public function find_by_name($name)
    {
        $grades = $this->db->prefixTable("green_commission_grades");
        return $this->db->query("SELECT * FROM $grades WHERE deleted=0 AND name=" . $this->db->escape($name) . " LIMIT 1")->getRow();
    }

    public function get_dropdown($only_active = true)
    {
        $result = ["" => "-"];
        $options = $only_active ? ["status" => "Ativa"] : [];
        foreach ($this->get_details($options)->getResult() as $grade) {
            $result[$grade->id] = $grade->name . ($grade->partner_name ? " (" . $grade->partner_name . ")" : "");
        }
        return $result;
    }
}
