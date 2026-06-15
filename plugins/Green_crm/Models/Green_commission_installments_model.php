<?php

namespace Green_crm\Models;

class Green_commission_installments_model extends Green_base_model
{
    protected $table = "green_commission_installments";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $items = $this->db->prefixTable("green_commission_installments");
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");
        $operators = $this->db->prefixTable("green_operators");
        $users = $this->db->prefixTable("users");
        $where = "";

        foreach (["id", "sale_id", "due_month", "due_year"] as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value) {
                $where .= " AND $items.$field=" . (int) $value;
            }
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $items.status=" . $this->db->escape($status);
        }

        $commission_type = $this->_get_clean_value($options, "commission_type");
        if ($commission_type) {
            $where .= " AND $items.commission_type=" . $this->db->escape($commission_type);
        }

        $operator_id = $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $sales.operator_id=" . (int) $operator_id;
        }

        $consultant_id = $this->_get_clean_value($options, "consultant_id");
        if ($consultant_id) {
            $where .= " AND $sales.consultant_id=" . (int) $consultant_id;
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $sales.client_id=" . (int) $client_id;
        }

        $sale_code = $this->_get_clean_value($options, "sale_code");
        if ($sale_code) {
            $sale_code = $this->db->escapeLikeString($sale_code);
            $where .= " AND $sales.sale_code LIKE '%$sale_code%' ESCAPE '!'";
        }

        $client_search = $this->_get_clean_value($options, "client_search");
        if ($client_search) {
            $client_search = $this->db->escapeLikeString($client_search);
            $where .= " AND $clients.name LIKE '%$client_search%' ESCAPE '!'";
        }

        if ((int) $this->_get_clean_value($options, "only_overdue")) {
            $where .= " AND $items.due_date < CURDATE() AND $items.status NOT IN ('Recebido','Cancelado','Estornado')";
        }

        if ((int) $this->_get_clean_value($options, "only_divergent")) {
            $where .= " AND $items.received_amount > 0 AND ROUND(COALESCE($items.received_amount,0) - COALESCE($items.expected_amount,0), 2) <> 0";
        }

        return $this->db->query("SELECT $items.*,
                COALESCE($items.received_amount,0) - COALESCE($items.expected_amount,0) AS difference_amount,
                $sales.sale_code,
                $sales.sale_value,
                $sales.plan_name,
                $sales.lead_id,
                $sales.consultant_id,
                $clients.name AS client_name,
                $operators.name AS operator_name,
                CONCAT($users.first_name, ' ', $users.last_name) AS consultant_name
            FROM $items
            INNER JOIN $sales ON $sales.id=$items.sale_id
            INNER JOIN $clients ON $clients.id=$sales.client_id
            LEFT JOIN $operators ON $operators.id=$sales.operator_id
            LEFT JOIN $users ON $users.id=$sales.consultant_id AND $users.deleted=0
            WHERE $items.deleted=0 $where
            ORDER BY $items.due_year DESC, $items.due_month DESC, $items.installment_no ASC");
    }

    public function get_totals($options = [])
    {
        $items = $this->db->prefixTable("green_commission_installments");
        if (!$this->db->tableExists($items)) {
            return (object) [
                "expected_amount_total" => 0,
                "received_amount_total" => 0,
                "open_amount_total" => 0,
                "overdue_amount_total" => 0,
                "difference_amount_total" => 0,
                "bonus_expected_total" => 0,
                "reversal_amount_total" => 0
            ];
        }

        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");
        $leads = $this->db->prefixTable("green_leads");
        $where = "";

        foreach (["sale_id", "due_month", "due_year"] as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value) {
                $where .= " AND $items.$field=" . (int) $value;
            }
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $items.status=" . $this->db->escape($status);
        }

        $commission_type = $this->_get_clean_value($options, "commission_type");
        if ($commission_type) {
            $where .= " AND $items.commission_type=" . $this->db->escape($commission_type);
        }

        $operator_id = $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $sales.operator_id=" . (int) $operator_id;
        }

        $consultant_id = $this->_get_clean_value($options, "consultant_id");
        if ($consultant_id) {
            $where .= " AND $sales.consultant_id=" . (int) $consultant_id;
        }

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND $items.due_date>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND $items.due_date<=" . $this->db->escape($date_to);
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

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $sales.client_id=" . (int) $client_id;
        }

        $sale_code = $this->_get_clean_value($options, "sale_code");
        if ($sale_code) {
            $sale_code = $this->db->escapeLikeString($sale_code);
            $where .= " AND $sales.sale_code LIKE '%$sale_code%' ESCAPE '!'";
        }

        $client_search = $this->_get_clean_value($options, "client_search");
        if ($client_search) {
            $client_search = $this->db->escapeLikeString($client_search);
            $where .= " AND $clients.name LIKE '%$client_search%' ESCAPE '!'";
        }

        if ((int) $this->_get_clean_value($options, "only_overdue")) {
            $where .= " AND $items.due_date < CURDATE() AND $items.status NOT IN ('Recebido','Cancelado','Estornado')";
        }

        if ((int) $this->_get_clean_value($options, "only_divergent")) {
            $where .= " AND $items.received_amount > 0 AND ROUND(COALESCE($items.received_amount,0) - COALESCE($items.expected_amount,0), 2) <> 0";
        }

        return $this->db->query("SELECT
            COALESCE(SUM(CASE WHEN $items.status NOT IN ('Cancelado','Estornado') THEN $items.expected_amount ELSE 0 END),0) AS expected_amount_total,
            COALESCE(SUM(CASE WHEN $items.status IN ('Recebido','Parcial') THEN $items.received_amount ELSE 0 END),0) AS received_amount_total,
            COALESCE(SUM(CASE WHEN $items.status NOT IN ('Recebido','Cancelado','Estornado') THEN $items.expected_amount - COALESCE($items.received_amount,0) ELSE 0 END),0) AS open_amount_total,
            COALESCE(SUM(CASE WHEN $items.due_date < CURDATE() AND $items.status NOT IN ('Recebido','Cancelado','Estornado') THEN $items.expected_amount - COALESCE($items.received_amount,0) ELSE 0 END),0) AS overdue_amount_total,
            COALESCE(SUM(CASE WHEN $items.status NOT IN ('Cancelado','Estornado') THEN COALESCE($items.received_amount,0) - COALESCE($items.expected_amount,0) ELSE 0 END),0) AS difference_amount_total,
            COALESCE(SUM(CASE WHEN $items.commission_type='bonus' AND $items.status NOT IN ('Cancelado','Estornado') THEN $items.expected_amount ELSE 0 END),0) AS bonus_expected_total,
            COALESCE(SUM(CASE WHEN $items.commission_type='estorno' AND $items.status NOT IN ('Cancelado') THEN $items.expected_amount ELSE 0 END),0) AS reversal_amount_total
            FROM $items
            INNER JOIN $sales ON $sales.id=$items.sale_id
            INNER JOIN $clients ON $clients.id=$sales.client_id
            LEFT JOIN $leads ON $leads.id=$sales.lead_id AND $leads.deleted=0
            WHERE $items.deleted=0 $where")->getRow();
    }

    public function get_commission_by_competence($options = [])
    {
        $items = $this->db->prefixTable("green_commission_installments");
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");
        $leads = $this->db->prefixTable("green_leads");
        $where = "";

        $operator_id = (int) $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $sales.operator_id=$operator_id";
        }

        $consultant_id = (int) $this->_get_clean_value($options, "consultant_id");
        if ($consultant_id) {
            $where .= " AND $sales.consultant_id=$consultant_id";
        }

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND $items.due_date>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND $items.due_date<=" . $this->db->escape($date_to);
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

        return $this->db->query("SELECT $items.due_year, $items.due_month,
                COALESCE(SUM(CASE WHEN $items.status NOT IN ('Cancelado','Estornado') THEN $items.expected_amount ELSE 0 END),0) AS expected_amount,
                COALESCE(SUM(CASE WHEN $items.status IN ('Recebido','Parcial') THEN $items.received_amount ELSE 0 END),0) AS received_amount
            FROM $items
            INNER JOIN $sales ON $sales.id=$items.sale_id AND $sales.deleted=0
            INNER JOIN $clients ON $clients.id=$sales.client_id AND $clients.deleted=0
            LEFT JOIN $leads ON $leads.id=$sales.lead_id AND $leads.deleted=0
            WHERE $items.deleted=0 $where
            GROUP BY $items.due_year, $items.due_month
            ORDER BY $items.due_year DESC, $items.due_month DESC
            LIMIT 12")->getResult();
    }

    public function get_by_sale($sale_id)
    {
        return $this->get_details(["sale_id" => $sale_id]);
    }

    public function get_by_lead($lead_id)
    {
        $items = $this->db->prefixTable("green_commission_installments");
        $sales = $this->db->prefixTable("green_sales");

        return $this->db->query("SELECT $items.*, $sales.sale_code
            FROM $items
            INNER JOIN $sales ON $sales.id=$items.sale_id AND $sales.deleted=0
            WHERE $items.deleted=0 AND $sales.lead_id=" . (int) $lead_id . "
            ORDER BY $items.due_year DESC, $items.due_month DESC, $items.installment_no ASC");
    }

    public function get_pending_recent($limit = 5)
    {
        $limit = max(1, (int) $limit);
        $items = $this->db->prefixTable("green_commission_installments");
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");

        return $this->db->query("SELECT $items.id, $items.due_month, $items.due_year, $items.expected_amount, $items.status,
                $sales.sale_code,
                $clients.name AS client_name
            FROM $items
            INNER JOIN $sales ON $sales.id=$items.sale_id
            INNER JOIN $clients ON $clients.id=$sales.client_id
            WHERE $items.deleted=0 AND $items.status IN ('Previsto','A receber','Parcial')
            ORDER BY $items.due_year ASC, $items.due_month ASC, $items.installment_no ASC
            LIMIT $limit");
    }

    public function generate_for_sale($sale_id, $schedule, $user_id = 0)
    {
        $sales = $this->db->prefixTable("green_sales");
        $sale = $this->db->query("SELECT * FROM $sales WHERE id=" . (int) $sale_id . " AND deleted=0")->getRow();
        if (!$sale) {
            return false;
        }

        $table = $this->_green_table();
        $this->db->query("UPDATE $table
            SET status='Cancelado', updated_by=" . (int) $user_id . ", updated_at=NOW()
            WHERE sale_id=" . (int) $sale_id . " AND status IN ('Previsto','A receber')");

        $count = 0;
        foreach ($schedule as $index => $item) {
            $type = $this->_valid_type($item["commission_type"] ?? "comissao");
            $rate = isset($item["commission_rate"]) && $item["commission_rate"] !== "" ? (float) $item["commission_rate"] : null;
            $expected = isset($item["expected_amount"]) && $item["expected_amount"] !== "" ? (float) $item["expected_amount"] : null;
            $legacy_rate = isset($item["legacy_rate"]) && $item["legacy_rate"] !== "" ? (float) $item["legacy_rate"] : $rate;
            $legacy_amount = isset($item["legacy_amount"]) && $item["legacy_amount"] !== "" ? (float) $item["legacy_amount"] : null;
            $legacy_month_name = trim((string) ($item["legacy_month_name"] ?? ""));

            if ($expected === null) {
                $expected = $rate !== null ? (float) $sale->sale_value * (float) $rate : 0;
            }

            if ($expected <= 0 && $type !== "estorno" && $legacy_amount === null) {
                continue;
            }

            $month = (int) ($item["due_month"] ?? date("m"));
            $year = (int) ($item["due_year"] ?? date("Y"));
            $installment_data = [
                "sale_id" => $sale_id,
                "installment_no" => (int) ($item["installment_no"] ?? ($index + 1)),
                "commission_type" => $type,
                "due_month" => $month,
                "due_year" => $year,
                "due_date" => sprintf("%04d-%02d-10", $year, $month),
                "base_amount" => $sale->sale_value,
                "commission_rate" => $rate,
                "expected_amount" => $expected,
                "legacy_rate" => $legacy_rate,
                "legacy_amount" => $legacy_amount,
                "legacy_month_name" => $legacy_month_name ?: null,
                "status" => "Previsto",
                "notes" => $item["notes"] ?? null,
                "created_by" => (int) $user_id ?: null,
                "updated_by" => (int) $user_id ?: null,
                "deleted" => 0
            ];
            $this->ci_save($installment_data);
            $count++;
        }

        return $count;
    }

    public function mark_as_paid($id, $received_amount, $paid_at, $payment_method, $notes, $user_id = 0)
    {
        $row = $this->get_one($id);
        if (!$row || !$row->id || in_array($row->status, ["Cancelado", "Estornado"], true)) {
            return false;
        }

        $total_received = (float) $row->received_amount + (float) $received_amount;
        $expected = (float) $row->expected_amount;

        $status = ($expected > 0 && $total_received >= $expected) ? "Recebido" : "Parcial";
        $data = [
            "received_amount" => $total_received,
            "paid_at" => $paid_at,
            "payment_method" => $payment_method,
            "notes" => $notes,
            "status" => $status,
            "updated_by" => (int) $user_id ?: null,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        return $this->ci_save($data, $id);
    }

    public function cancel_installment($id, $notes)
    {
        $data = ["status" => "Cancelado", "notes" => $notes];
        return $this->ci_save($data, $id);
    }

    private function _valid_type($type)
    {
        return in_array($type, ["comissao", "bonus", "ajuste", "estorno"], true) ? $type : "comissao";
    }
}
