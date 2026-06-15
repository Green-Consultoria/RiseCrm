<?php

namespace Green_meta_leads\Models;

use App\Models\Crud_model;

class Green_meta_raw_leads_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "green_meta_raw_leads";
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $raw = $this->db->prefixTable("green_meta_raw_leads");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $raw.id=" . (int) $id;
        }

        $facebook_lead_id = $this->_get_clean_value($options, "facebook_lead_id");
        if ($facebook_lead_id) {
            $where .= " AND $raw.facebook_lead_id=" . $this->db->escape($facebook_lead_id);
        }

        $process_status = $this->_get_clean_value($options, "process_status");
        if ($process_status) {
            $where .= " AND $raw.process_status=" . $this->db->escape($process_status);
        }

        $facebook_form_id = $this->_get_clean_value($options, "facebook_form_id");
        if ($facebook_form_id) {
            $where .= " AND $raw.facebook_form_id=" . $this->db->escape($facebook_form_id);
        }

        $campaign_name = $this->_get_clean_value($options, "campaign_name");
        if ($campaign_name) {
            $where .= " AND $raw.campaign_name=" . $this->db->escape($campaign_name);
        }

        $captured_from = $this->_get_clean_value($options, "captured_from");
        if ($captured_from) {
            $where .= " AND DATE($raw.facebook_created_time)>=" . $this->db->escape($captured_from);
        }

        $captured_to = $this->_get_clean_value($options, "captured_to");
        if ($captured_to) {
            $where .= " AND DATE($raw.facebook_created_time)<=" . $this->db->escape($captured_to);
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($raw.full_name LIKE '%$search%' ESCAPE '!'
                OR $raw.email LIKE '%$search%' ESCAPE '!'
                OR $raw.phone_normalized LIKE '%$search%' ESCAPE '!'
                OR $raw.campaign_name LIKE '%$search%' ESCAPE '!')";
        }

        $limit = (int) $this->_get_clean_value($options, "limit");
        $limit_sql = $limit ? " LIMIT " . $limit : "";

        return $this->db->query("SELECT $raw.* FROM $raw WHERE $raw.deleted=0 $where ORDER BY $raw.id DESC" . $limit_sql);
    }

    public function recent($limit = 50)
    {
        return $this->get_details(["limit" => (int) $limit]);
    }

    public function summary_counts()
    {
        $raw = $this->db->prefixTable("green_meta_raw_leads");
        return $this->db->query("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN process_status='created' THEN 1 ELSE 0 END) AS created,
            SUM(CASE WHEN process_status='linked' THEN 1 ELSE 0 END) AS linked,
            SUM(CASE WHEN process_status='error' THEN 1 ELSE 0 END) AS errors,
            SUM(CASE WHEN process_status='pending' THEN 1 ELSE 0 END) AS pending
            FROM $raw WHERE deleted=0")->getRow();
    }

    public function distinct_campaigns()
    {
        $raw = $this->db->prefixTable("green_meta_raw_leads");
        return $this->db->query("SELECT DISTINCT campaign_name FROM $raw
            WHERE deleted=0 AND campaign_name IS NOT NULL AND campaign_name<>''
            ORDER BY campaign_name ASC")->getResult();
    }

    public function distinct_forms()
    {
        $raw = $this->db->prefixTable("green_meta_raw_leads");
        return $this->db->query("SELECT facebook_form_id, MAX(form_name) AS form_name FROM $raw
            WHERE deleted=0 AND facebook_form_id IS NOT NULL AND facebook_form_id<>''
            GROUP BY facebook_form_id
            ORDER BY form_name ASC")->getResult();
    }
}
