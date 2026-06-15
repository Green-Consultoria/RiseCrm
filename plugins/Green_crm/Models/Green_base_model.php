<?php

namespace Green_crm\Models;

use App\Models\Crud_model;

class Green_base_model extends Crud_model
{
    protected $table = null;

    public function __construct($table = null)
    {
        $this->table = $table ?: $this->table;
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

        $status = $this->_get_clean_value($options, "status");
        if ($status && $this->_has_column("status")) {
            $where .= " AND $table.status=" . $this->db->escape($status);
        }

        $name = $this->_get_clean_value($options, "name");
        if ($name && $this->_has_column("name")) {
            $where .= " AND $table.name LIKE '%" . $this->db->escapeLikeString($name) . "%' ESCAPE '!'";
        }

        $title = $this->_get_clean_value($options, "title");
        if ($title && $this->_has_column("title")) {
            $where .= " AND $table.title LIKE '%" . $this->db->escapeLikeString($title) . "%' ESCAPE '!'";
        }

        if (array_key_exists("deleted", $options)) {
            $where .= " AND $table.deleted=" . (int) $this->_get_clean_value($options, "deleted");
        } elseif ($this->_has_column("deleted")) {
            $where .= " AND $table.deleted=0";
        }

        $sql = "SELECT $table.* FROM $table WHERE 1=1 $where ORDER BY $table.id DESC";
        return $this->db->query($sql);
    }

    protected function _has_column($column)
    {
        static $cache = [];
        $key = $this->table_without_prefix . "." . $column;
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = (bool) $this->db->query("SHOW COLUMNS FROM `" . $this->_green_table() . "` LIKE " . $this->db->escape($column))->getRow();
        }

        return $cache[$key];
    }

    protected function _green_table()
    {
        return $this->db->prefixTable($this->table_without_prefix);
    }
}
