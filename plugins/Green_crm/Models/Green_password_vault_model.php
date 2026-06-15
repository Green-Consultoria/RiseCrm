<?php

namespace Green_crm\Models;

class Green_password_vault_model extends Green_base_model
{
    protected $table = "green_password_vault";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $vault = $this->db->prefixTable("green_password_vault");
        $users = $this->db->prefixTable("users");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $vault.id=" . (int) $id;
        }

        $category = $this->_get_clean_value($options, "category");
        if ($category) {
            $where .= " AND $vault.category=" . $this->db->escape($category);
        }

        $owner_user_id = (int) $this->_get_clean_value($options, "owner_user_id");
        if ($owner_user_id) {
            $where .= " AND $vault.owner_user_id=$owner_user_id";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $needle = $this->db->escapeLikeString($search);
            $where .= " AND ($vault.title LIKE '%$needle%' ESCAPE '!' OR $vault.system_url LIKE '%$needle%' ESCAPE '!' OR $vault.login_username LIKE '%$needle%' ESCAPE '!' OR $vault.category LIKE '%$needle%' ESCAPE '!')";
        }

        return $this->db->query("SELECT $vault.*,
                CONCAT($users.first_name, ' ', $users.last_name) AS owner_name
            FROM $vault
            LEFT JOIN $users ON $users.id=$vault.owner_user_id AND $users.deleted=0
            WHERE $vault.deleted=0 $where
            ORDER BY $vault.title ASC, $vault.id DESC");
    }

    public function get_categories()
    {
        $vault = $this->db->prefixTable("green_password_vault");
        $rows = $this->db->query("SELECT DISTINCT category FROM $vault WHERE deleted=0 AND category IS NOT NULL AND category<>'' ORDER BY category ASC")->getResult();

        $list = ["" => "Categoria"];
        foreach ($rows as $row) {
            $list[$row->category] = $row->category;
        }

        return $list;
    }
}
