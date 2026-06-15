<?php

namespace Green_crm\Models;

class Green_quotes_model extends Green_base_model
{
    protected $table = "green_quotes";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $quotes = $this->db->prefixTable("green_quotes");
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $options_table = $this->db->prefixTable("green_quote_options");
        $operators = $this->db->prefixTable("green_operators");
        $plans = $this->db->prefixTable("green_plans");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $quotes.id=" . (int) $id;
        }

        $lead_id = $this->_get_clean_value($options, "lead_id");
        if ($lead_id) {
            $where .= " AND $quotes.lead_id=" . (int) $lead_id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $quotes.status=" . $this->db->escape($status);
        }

        return $this->db->query("SELECT $quotes.*, $clients.name AS client_name, $leads.lead_code,
                $leads.current_paid_value,
                $leads.current_plan_name,
                $leads.owner_id,
                $leads.client_id,
                $options_table.plan_name AS selected_plan_name,
                $options_table.monthly_value AS selected_monthly_value,
                $options_table.is_selected,
                $operators.name AS selected_operator_name,
                $plans.name AS selected_registered_plan_name
            FROM $quotes
            INNER JOIN $leads ON $leads.id=$quotes.lead_id
            INNER JOIN $clients ON $clients.id=$leads.client_id
            LEFT JOIN $options_table ON $options_table.id=$quotes.selected_option_id AND $options_table.deleted=0
            LEFT JOIN $operators ON $operators.id=$options_table.operator_id AND $operators.deleted=0
            LEFT JOIN $plans ON $plans.id=$options_table.plan_id AND $plans.deleted=0
            WHERE $quotes.deleted=0 $where
            ORDER BY $quotes.id DESC");
    }

    public function get_sent_count($options = [])
    {
        $quotes = $this->db->prefixTable("green_quotes");
        $leads = $this->db->prefixTable("green_leads");
        $where = " AND $quotes.status IN ('Enviada','Aceita')";

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND DATE($quotes.created_at)>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND DATE($quotes.created_at)<=" . $this->db->escape($date_to);
        }

        $consultant_id = (int) $this->_get_clean_value($options, "consultant_id");
        if ($consultant_id) {
            $where .= " AND $leads.owner_id=$consultant_id";
        }

        $source_id = (int) $this->_get_clean_value($options, "source_id");
        if ($source_id) {
            $where .= " AND $leads.source_id=$source_id";
        }

        $operator_id = (int) $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $leads.current_operator_id=$operator_id";
        }

        $status_id = (int) $this->_get_clean_value($options, "status_id");
        if ($status_id) {
            $where .= " AND $leads.status_id=$status_id";
        }

        $temperature = $this->_get_clean_value($options, "temperature");
        if ($temperature) {
            $where .= " AND $leads.temperature=" . $this->db->escape($temperature);
        }

        return (int) ($this->db->query("SELECT COUNT($quotes.id) AS total
            FROM $quotes
            INNER JOIN $leads ON $leads.id=$quotes.lead_id AND $leads.deleted=0
            WHERE $quotes.deleted=0 $where")->getRow()->total ?? 0);
    }
}
