<?php

namespace Green_crm\Models;

class Green_tasks_model extends Green_base_model
{
    protected $table = "green_tasks";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $tasks = $this->db->prefixTable("green_tasks");
        $users = $this->db->prefixTable("users");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $tasks.id=" . (int) $id;
        }

        $lead_id = $this->_get_clean_value($options, "lead_id");
        if ($lead_id) {
            $where .= " AND $tasks.lead_id=" . (int) $lead_id;
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $tasks.status=" . $this->db->escape($status);
        }

        return $this->db->query("SELECT $tasks.*, CONCAT($users.first_name, ' ', $users.last_name) AS responsible_name
            FROM $tasks
            LEFT JOIN $users ON $users.id=$tasks.responsible_id AND $users.deleted=0
            WHERE $tasks.deleted=0 $where
            ORDER BY CASE WHEN $tasks.status IN ('aberta','em_andamento') THEN 0 ELSE 1 END ASC, $tasks.due_date ASC, $tasks.id DESC");
    }

    public function get_general($options = [])
    {
        $tasks = $this->db->prefixTable("green_tasks");
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $users = $this->db->prefixTable("users");
        $where = "";

        $responsible_id = (int) $this->_get_clean_value($options, "responsible_id");
        if ($responsible_id) {
            $where .= " AND $tasks.responsible_id=$responsible_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $tasks.status=" . $this->db->escape($status);
        }

        $priority = $this->_get_clean_value($options, "priority");
        if ($priority) {
            $where .= " AND $tasks.priority=" . $this->db->escape($priority);
        }

        $lead_id = (int) $this->_get_clean_value($options, "lead_id");
        if ($lead_id) {
            $where .= " AND $tasks.lead_id=$lead_id";
        }

        $due_filter = $this->_get_clean_value($options, "due_filter");
        $open = "$tasks.status IN ('aberta','em_andamento')";
        if ($due_filter === "overdue") {
            $where .= " AND $open AND $tasks.due_date IS NOT NULL AND $tasks.due_date < NOW()";
        } elseif ($due_filter === "today") {
            $where .= " AND $tasks.due_date IS NOT NULL AND DATE($tasks.due_date)=CURDATE()";
        } elseif ($due_filter === "upcoming") {
            $where .= " AND $open AND $tasks.due_date IS NOT NULL AND $tasks.due_date > NOW()";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $needle = $this->db->escapeLikeString($search);
            $where .= " AND ($tasks.title LIKE '%$needle%' ESCAPE '!' OR $tasks.description LIKE '%$needle%' ESCAPE '!' OR $clients.name LIKE '%$needle%' ESCAPE '!' OR $leads.lead_code LIKE '%$needle%' ESCAPE '!')";
        }

        return $this->db->query("SELECT $tasks.*, $leads.lead_code,
                $clients.name AS client_name,
                CONCAT($users.first_name, ' ', $users.last_name) AS responsible_name
            FROM $tasks
            LEFT JOIN $leads ON $leads.id=$tasks.lead_id AND $leads.deleted=0
            LEFT JOIN $clients ON $clients.id=COALESCE($tasks.client_id, $leads.client_id) AND $clients.deleted=0
            LEFT JOIN $users ON $users.id=$tasks.responsible_id AND $users.deleted=0
            WHERE $tasks.deleted=0 $where
            ORDER BY CASE WHEN $open THEN 0 ELSE 1 END ASC, $tasks.due_date IS NULL ASC, $tasks.due_date ASC, $tasks.id DESC");
    }

    public function get_general_counts($responsible_id = 0)
    {
        $tasks = $this->db->prefixTable("green_tasks");
        $responsible_id = (int) $responsible_id;
        $owner_where = $responsible_id ? " AND $tasks.responsible_id=$responsible_id" : "";

        return $this->db->query("SELECT
                SUM(CASE WHEN $tasks.status IN ('aberta','em_andamento') AND $tasks.due_date IS NOT NULL AND $tasks.due_date < NOW() THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN $tasks.due_date IS NOT NULL AND DATE($tasks.due_date)=CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN $tasks.status IN ('aberta','em_andamento') THEN 1 ELSE 0 END) AS open_total
            FROM $tasks
            WHERE $tasks.deleted=0 $owner_where")->getRow();
    }

    public function get_overdue_dashboard($options = [], $limit = 8)
    {
        $limit = max(1, (int) $limit);
        $tasks = $this->db->prefixTable("green_tasks");
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $users = $this->db->prefixTable("users");
        $where = " AND $tasks.status IN ('aberta','em_andamento') AND $tasks.due_date < NOW()";

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND DATE($tasks.due_date)>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND DATE($tasks.due_date)<=" . $this->db->escape($date_to);
        }

        $consultant_id = (int) $this->_get_clean_value($options, "consultant_id");
        if ($consultant_id) {
            $where .= " AND ($tasks.responsible_id=$consultant_id OR $leads.owner_id=$consultant_id)";
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

        return $this->db->query("SELECT $tasks.*, $leads.lead_code,
                $clients.name AS client_name,
                CONCAT($users.first_name, ' ', $users.last_name) AS responsible_name
            FROM $tasks
            INNER JOIN $leads ON $leads.id=$tasks.lead_id AND $leads.deleted=0
            INNER JOIN $clients ON $clients.id=$leads.client_id AND $clients.deleted=0
            LEFT JOIN $users ON $users.id=$tasks.responsible_id AND $users.deleted=0
            WHERE $tasks.deleted=0 $where
            ORDER BY $tasks.due_date ASC, $tasks.id ASC
            LIMIT $limit")->getResult();
    }
}
