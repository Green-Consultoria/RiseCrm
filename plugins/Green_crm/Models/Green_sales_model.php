<?php

namespace Green_crm\Models;

class Green_sales_model extends Green_base_model
{
    protected $table = "green_sales";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");
        $leads = $this->db->prefixTable("green_leads");
        $operators = $this->db->prefixTable("green_operators");
        $plans = $this->db->prefixTable("green_plans");
        $checklist = $this->db->prefixTable("green_sale_implantation_checklist");
        $grades = $this->db->prefixTable("green_commission_grades");
        $grade_versions = $this->db->prefixTable("green_commission_grade_versions");
        $where = "";

        foreach (["id", "lead_id", "client_id", "operator_id", "plan_id", "consultant_id"] as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value) {
                $where .= " AND $sales.$field=" . (int) $value;
            }
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $sales.status=" . $this->db->escape($status);
        }

        $implantation_status = $this->_get_clean_value($options, "implantation_status");
        if ($implantation_status) {
            $where .= " AND $sales.implantation_status=" . $this->db->escape($implantation_status);
        }

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND $sales.sale_date>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND $sales.sale_date<=" . $this->db->escape($date_to);
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($sales.sale_code LIKE '%$search%' ESCAPE '!' OR $clients.name LIKE '%$search%' ESCAPE '!' OR $leads.lead_code LIKE '%$search%' ESCAPE '!' OR $sales.contract_number LIKE '%$search%' ESCAPE '!')";
        }

        return $this->db->query("SELECT $sales.*,
                $clients.name AS client_name,
                $leads.lead_code,
                $operators.name AS operator_name,
                $plans.name AS plan_registered_name,
                $grades.name AS commission_grade_name,
                $grade_versions.version_name AS commission_grade_version_name,
                COALESCE(checklist_progress.total_items, 9) AS implantation_checklist_total,
                COALESCE(checklist_progress.completed_items, 0) AS implantation_checklist_completed,
                COALESCE(checklist_progress.pending_items, 9) AS implantation_checklist_pending
            FROM $sales
            INNER JOIN $clients ON $clients.id=$sales.client_id
            LEFT JOIN $leads ON $leads.id=$sales.lead_id
            LEFT JOIN $operators ON $operators.id=$sales.operator_id
            LEFT JOIN $plans ON $plans.id=$sales.plan_id
            LEFT JOIN $grades ON $grades.id=$sales.commission_grade_id
            LEFT JOIN $grade_versions ON $grade_versions.id=$sales.commission_grade_version_id
            LEFT JOIN (
                SELECT sale_id,
                    COUNT(*) AS total_items,
                    SUM(CASE WHEN status='concluido' THEN 1 ELSE 0 END) AS completed_items,
                    SUM(CASE WHEN status='pendente' THEN 1 ELSE 0 END) AS pending_items
                FROM $checklist
                WHERE deleted=0
                GROUP BY sale_id
            ) AS checklist_progress ON checklist_progress.sale_id=$sales.id
            WHERE $sales.deleted=0 $where
            ORDER BY $sales.sale_date DESC, $sales.id DESC");
    }

    public function get_totals($options = [])
    {
        $sales = $this->db->prefixTable("green_sales");
        $leads = $this->db->prefixTable("green_leads");
        $checklist = $this->db->prefixTable("green_sale_implantation_checklist");
        $where = " AND $sales.status NOT IN ('Cancelada','Estornada')";

        $where .= $this->_dashboard_where($options, $sales, $leads);

        return $this->db->query("SELECT
            COUNT(*) AS total_sales,
            COALESCE(SUM($sales.sale_value), 0) AS total_sale_value,
            CASE WHEN COUNT(*) > 0 THEN COALESCE(SUM($sales.sale_value), 0) / COUNT(*) ELSE 0 END AS average_ticket,
            SUM(CASE
                WHEN $sales.status='Implantada' OR $sales.implantation_status='implantada' THEN 0
                WHEN implantation_done.status IS NULL THEN CASE WHEN $sales.implantation_status IN ('nao_iniciada','pendente','em_andamento') THEN 1 ELSE 0 END
                WHEN implantation_done.status='concluido' THEN 0
                ELSE 1
            END) AS pending_implantations
            FROM $sales
            LEFT JOIN $leads ON $leads.id=$sales.lead_id AND $leads.deleted=0
            LEFT JOIN $checklist AS implantation_done ON implantation_done.sale_id=$sales.id
                AND implantation_done.item_key='implantacao_concluida'
                AND implantation_done.deleted=0
            WHERE $sales.deleted=0 $where")->getRow();
    }

    public function get_sales_by_operator($options = [])
    {
        $sales = $this->db->prefixTable("green_sales");
        $leads = $this->db->prefixTable("green_leads");
        $operators = $this->db->prefixTable("green_operators");
        $where = " AND $sales.status NOT IN ('Cancelada','Estornada')";
        $where .= $this->_dashboard_where($options, $sales, $leads);

        return $this->db->query("SELECT COALESCE($operators.name, 'Sem operadora') AS label,
                COUNT($sales.id) AS total_sales,
                COALESCE(SUM($sales.sale_value), 0) AS total_value
            FROM $sales
            LEFT JOIN $leads ON $leads.id=$sales.lead_id AND $leads.deleted=0
            LEFT JOIN $operators ON $operators.id=$sales.operator_id AND $operators.deleted=0
            WHERE $sales.deleted=0 $where
            GROUP BY COALESCE($operators.name, 'Sem operadora')
            ORDER BY total_value DESC, total_sales DESC, label ASC")->getResult();
    }

    public function get_pending_implantations($options = [], $limit = 8)
    {
        $limit = max(1, (int) $limit);
        $sales = $this->db->prefixTable("green_sales");
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $operators = $this->db->prefixTable("green_operators");
        $checklist = $this->db->prefixTable("green_sale_implantation_checklist");
        $where = " AND $sales.status NOT IN ('Cancelada','Estornada')";
        $where .= $this->_dashboard_where($options, $sales, $leads);

        return $this->db->query("SELECT $sales.id, $sales.sale_code, $sales.sale_date, $sales.implantation_status,
                $clients.name AS client_name,
                $operators.name AS operator_name,
                COALESCE(checklist_progress.total_items, 9) AS total_items,
                COALESCE(checklist_progress.completed_items, 0) AS completed_items
            FROM $sales
            INNER JOIN $clients ON $clients.id=$sales.client_id AND $clients.deleted=0
            LEFT JOIN $leads ON $leads.id=$sales.lead_id AND $leads.deleted=0
            LEFT JOIN $operators ON $operators.id=$sales.operator_id AND $operators.deleted=0
            LEFT JOIN (
                SELECT sale_id,
                    COUNT(*) AS total_items,
                    SUM(CASE WHEN status='concluido' THEN 1 ELSE 0 END) AS completed_items
                FROM $checklist
                WHERE deleted=0
                GROUP BY sale_id
            ) AS checklist_progress ON checklist_progress.sale_id=$sales.id
            LEFT JOIN $checklist AS implantation_done ON implantation_done.sale_id=$sales.id
                AND implantation_done.item_key='implantacao_concluida'
                AND implantation_done.deleted=0
            WHERE $sales.deleted=0 $where
                AND ($sales.implantation_status IN ('nao_iniciada','pendente','em_andamento')
                    OR (implantation_done.status IS NOT NULL AND implantation_done.status<>'concluido'))
            ORDER BY $sales.sale_date ASC, $sales.id ASC
            LIMIT $limit")->getResult();
    }

    public function get_one($id = 0)
    {
        return parent::get_one($id);
    }

    public function get_by_lead($lead_id)
    {
        return $this->get_details(["lead_id" => $lead_id]);
    }

    public function get_by_client($client_id)
    {
        return $this->get_details(["client_id" => $client_id]);
    }

    public function get_recent($limit = 5)
    {
        $limit = max(1, (int) $limit);
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");
        $operators = $this->db->prefixTable("green_operators");

        return $this->db->query("SELECT $sales.id, $sales.sale_code, $sales.sale_value, $sales.status,
                $clients.name AS client_name,
                $operators.name AS operator_name
            FROM $sales
            INNER JOIN $clients ON $clients.id=$sales.client_id
            LEFT JOIN $operators ON $operators.id=$sales.operator_id
            WHERE $sales.deleted=0
            ORDER BY $sales.sale_date DESC, $sales.id DESC
            LIMIT $limit");
    }

    private function _dashboard_where($options, $sales, $leads)
    {
        $where = "";

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND $sales.sale_date>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND $sales.sale_date<=" . $this->db->escape($date_to);
        }

        $consultant_id = (int) $this->_get_clean_value($options, "consultant_id");
        if ($consultant_id) {
            $where .= " AND $sales.consultant_id=$consultant_id";
        }

        $operator_id = (int) $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $sales.operator_id=$operator_id";
        }

        $source_id = (int) $this->_get_clean_value($options, "source_id");
        if ($source_id) {
            $where .= " AND $leads.source_id=$source_id";
        }

        $status_id = (int) $this->_get_clean_value($options, "status_id");
        if ($status_id) {
            $where .= " AND $leads.status_id=$status_id";
        }

        $temperature = $this->_get_clean_value($options, "temperature");
        if ($temperature) {
            $where .= " AND $leads.temperature=" . $this->db->escape($temperature);
        }

        return $where;
    }
}
