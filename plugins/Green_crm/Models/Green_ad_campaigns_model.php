<?php

namespace Green_crm\Models;

class Green_ad_campaigns_model extends Green_base_model
{
    protected $table = "green_ad_campaigns";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $campaigns = $this->db->prefixTable("green_ad_campaigns");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $campaigns.id=" . (int) $id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $campaigns.status=" . $this->db->escape($status);
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $needle = $this->db->escapeLikeString($search);
            $where .= " AND ($campaigns.name LIKE '%$needle%' ESCAPE '!' OR $campaigns.external_id LIKE '%$needle%' ESCAPE '!')";
        }

        return $this->db->query("SELECT $campaigns.* FROM $campaigns WHERE $campaigns.deleted=0 $where ORDER BY $campaigns.name ASC, $campaigns.id DESC");
    }

    public function get_dropdown($include_blank = true)
    {
        $campaigns = $this->db->prefixTable("green_ad_campaigns");
        $rows = $this->db->query("SELECT id, name FROM $campaigns WHERE deleted=0 ORDER BY name ASC")->getResult();
        $list = $include_blank ? ["" => "- Campanha -"] : [];
        foreach ($rows as $row) {
            $list[$row->id] = $row->name;
        }
        return $list;
    }
}
