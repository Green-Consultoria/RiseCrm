<?php

namespace Green_crm\Models;

class Green_ad_sets_model extends Green_base_model
{
    protected $table = "green_ad_sets";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $sets = $this->db->prefixTable("green_ad_sets");
        $campaigns = $this->db->prefixTable("green_ad_campaigns");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $sets.id=" . (int) $id;
        }

        $campaign_id = (int) $this->_get_clean_value($options, "campaign_id");
        if ($campaign_id) {
            $where .= " AND $sets.campaign_id=$campaign_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $sets.status=" . $this->db->escape($status);
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $needle = $this->db->escapeLikeString($search);
            $where .= " AND ($sets.name LIKE '%$needle%' ESCAPE '!' OR $sets.external_id LIKE '%$needle%' ESCAPE '!')";
        }

        return $this->db->query("SELECT $sets.*, $campaigns.name AS campaign_name
            FROM $sets
            LEFT JOIN $campaigns ON $campaigns.id=$sets.campaign_id AND $campaigns.deleted=0
            WHERE $sets.deleted=0 $where
            ORDER BY $sets.name ASC, $sets.id DESC");
    }

    public function get_dropdown($include_blank = true)
    {
        $sets = $this->db->prefixTable("green_ad_sets");
        $rows = $this->db->query("SELECT id, name FROM $sets WHERE deleted=0 ORDER BY name ASC")->getResult();
        $list = $include_blank ? ["" => "- Conjunto -"] : [];
        foreach ($rows as $row) {
            $list[$row->id] = $row->name;
        }
        return $list;
    }
}
