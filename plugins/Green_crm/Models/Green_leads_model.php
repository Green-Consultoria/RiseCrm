<?php

namespace Green_crm\Models;

class Green_leads_model extends Green_base_model
{
    protected $table = "green_leads";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $contacts = $this->db->prefixTable("green_client_contacts");
        $statuses = $this->db->prefixTable("green_lead_statuses");
        $sources = $this->db->prefixTable("green_sources");
        $operators = $this->db->prefixTable("green_operators");
        $users = $this->db->prefixTable("users");
        $where = "";

        foreach (["id", "client_id", "status_id", "source_id"] as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value) {
                $where .= " AND $leads.$field=" . (int) $value;
            }
        }

        $operator_id = $this->_get_clean_value($options, "operator_id");
        if ($operator_id) {
            $where .= " AND $leads.current_operator_id=" . (int) $operator_id;
        }

        $temperature = $this->_get_clean_value($options, "temperature");
        if ($temperature) {
            $where .= " AND $leads.temperature=" . $this->db->escape($temperature);
        }

        $renewal_month = $this->_get_clean_value($options, "renewal_month");
        if ($renewal_month) {
            $where .= " AND $leads.renewal_month=" . (int) $renewal_month;
        }

        // Filtros individuais da capa (cada coluna apresentada)
        $like_map = [
            "client_code" => "$clients.client_code",
            "client_name" => "$clients.name",
            "email" => "$contacts.email",
            "plan" => "$leads.current_plan_name",
            "ages" => "$leads.ages_text",
            "region" => "$leads.region",
            "hospital" => "$leads.preferred_hospital_text",
            "notes" => "$leads.notes"
        ];
        foreach ($like_map as $field => $column) {
            $value = $this->_get_clean_value($options, $field);
            if ($value) {
                $where .= " AND $column LIKE '%" . $this->db->escapeLikeString($value) . "%' ESCAPE '!'";
            }
        }

        $document_number = $this->_get_clean_value($options, "document_number");
        if ($document_number) {
            $doc_digits = preg_replace("/\D+/", "", (string) $document_number);
            $needle = $doc_digits !== "" ? $doc_digits : $document_number;
            $where .= " AND $clients.document_number LIKE '%" . $this->db->escapeLikeString($needle) . "%' ESCAPE '!'";
        }

        $phone = $this->_get_clean_value($options, "phone");
        if ($phone) {
            $phone_digits = preg_replace("/\D+/", "", (string) $phone);
            $needle = $phone_digits !== "" ? $phone_digits : $phone;
            $where .= " AND $contacts.phone_normalized LIKE '%" . $this->db->escapeLikeString($needle) . "%' ESCAPE '!'";
        }

        $lives_qty = $this->_get_clean_value($options, "lives_qty");
        if ($lives_qty) {
            $where .= " AND $leads.lives_qty=" . (int) $lives_qty;
        }

        $range_map = [
            "current_paid_min" => ["$leads.current_paid_value", ">="],
            "current_paid_max" => ["$leads.current_paid_value", "<="],
            "proposed_min" => ["$leads.proposed_value", ">="],
            "proposed_max" => ["$leads.proposed_value", "<="]
        ];
        foreach ($range_map as $field => $meta) {
            $raw = $this->_get_clean_value($options, $field);
            if ($raw === null || $raw === "") {
                continue;
            }
            $amount = function_exists("green_money_to_float") ? green_money_to_float($raw) : (float) str_replace(",", ".", (string) $raw);
            if ($amount !== null) {
                $where .= " AND " . $meta[0] . $meta[1] . (float) $amount;
            }
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $search_digits = preg_replace("/\D+/", "", (string) $search);
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($leads.lead_code LIKE '%$search%' ESCAPE '!' OR $clients.name LIKE '%$search%' ESCAPE '!' OR $contacts.email LIKE '%$search%' ESCAPE '!'";
            if ($search_digits) {
                $search_digits = $this->db->escapeLikeString($search_digits);
                $where .= " OR $contacts.phone_normalized LIKE '%$search_digits%' ESCAPE '!'";
            } else {
                $where .= " OR $contacts.phone_normalized LIKE '%$search%' ESCAPE '!'";
            }
            $where .= ")";
        }

        $sql = "SELECT $leads.*,
                $clients.client_code,
                $clients.name AS client_name,
                $clients.legal_name,
                $clients.client_type,
                $clients.document_type,
                $clients.document_number,
                $contacts.phone_normalized,
                $contacts.phone_original,
                $contacts.email,
                $statuses.title AS status_title,
                $sources.title AS source_title,
                $operators.name AS operator_name,
                CONCAT($users.first_name, ' ', $users.last_name) AS owner_name
            FROM $leads
            INNER JOIN $clients ON $clients.id=$leads.client_id
            LEFT JOIN $contacts ON $contacts.client_id=$clients.id AND $contacts.deleted=0 AND $contacts.is_primary=1
            LEFT JOIN $statuses ON $statuses.id=$leads.status_id AND $statuses.deleted=0
            LEFT JOIN $sources ON $sources.id=$leads.source_id AND $sources.deleted=0
            LEFT JOIN $operators ON $operators.id=$leads.current_operator_id AND $operators.deleted=0
            LEFT JOIN $users ON $users.id=$leads.owner_id AND $users.deleted=0
            WHERE $leads.deleted=0 $where
            GROUP BY $leads.id
            ORDER BY $leads.id DESC";

        return $this->db->query($sql);
    }

    public function get_dashboard_counts($options = [])
    {
        $leads = $this->db->prefixTable("green_leads");
        $statuses = $this->db->prefixTable("green_lead_statuses");
        $where = $this->_dashboard_where($options, $leads);

        return $this->db->query("SELECT
            COUNT($leads.id) AS total_leads,
            SUM(CASE WHEN $statuses.title='Novo' THEN 1 ELSE 0 END) AS leads_novos,
            SUM(CASE WHEN $statuses.title='Qualificado' THEN 1 ELSE 0 END) AS leads_qualificados,
            SUM(CASE WHEN $leads.temperature='quente' THEN 1 ELSE 0 END) AS leads_quentes,
            SUM(CASE WHEN $leads.last_interaction_at IS NULL THEN 1 ELSE 0 END) AS leads_sem_contato
            FROM $leads
            LEFT JOIN $statuses ON $statuses.id=$leads.status_id AND $statuses.deleted=0
            WHERE $leads.deleted=0 $where")->getRow();
    }

    public function get_funnel_by_status($options = [])
    {
        $leads = $this->db->prefixTable("green_leads");
        $statuses = $this->db->prefixTable("green_lead_statuses");
        $where = $this->_dashboard_where($options, $leads);

        return $this->db->query("SELECT COALESCE($statuses.title, 'Sem status') AS label, COUNT($leads.id) AS total
            FROM $leads
            LEFT JOIN $statuses ON $statuses.id=$leads.status_id AND $statuses.deleted=0
            WHERE $leads.deleted=0 $where
            GROUP BY COALESCE($statuses.title, 'Sem status'), COALESCE($statuses.sort, 999)
            ORDER BY COALESCE($statuses.sort, 999) ASC, label ASC")->getResult();
    }

    public function get_leads_by_source($options = [])
    {
        $leads = $this->db->prefixTable("green_leads");
        $sources = $this->db->prefixTable("green_sources");
        $where = $this->_dashboard_where($options, $leads);

        return $this->db->query("SELECT COALESCE($sources.title, 'Sem origem') AS label, COUNT($leads.id) AS total
            FROM $leads
            LEFT JOIN $sources ON $sources.id=$leads.source_id AND $sources.deleted=0
            WHERE $leads.deleted=0 $where
            GROUP BY COALESCE($sources.title, 'Sem origem')
            ORDER BY total DESC, label ASC")->getResult();
    }

    public function get_renewal_rows($options = [])
    {
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $contacts = $this->db->prefixTable("green_client_contacts");
        $statuses = $this->db->prefixTable("green_lead_statuses");
        $operators = $this->db->prefixTable("green_operators");
        $users = $this->db->prefixTable("users");
        $sales = $this->db->prefixTable("green_sales");
        $plans = $this->db->prefixTable("green_plans");
        $tasks = $this->db->prefixTable("green_tasks");
        $interactions = $this->db->prefixTable("green_interactions");

        $renewal_month = (int) $this->_get_clean_value($options, "renewal_month");
        $operator_id = (int) $this->_get_clean_value($options, "operator_id");
        $source_id = (int) $this->_get_clean_value($options, "source_id");
        $status_id = (int) $this->_get_clean_value($options, "status_id");
        $consultant_id = (int) $this->_get_clean_value($options, "consultant_id");
        $temperature = $this->_get_clean_value($options, "temperature");
        $only_without_future_task = (int) $this->_get_clean_value($options, "only_without_future_task");
        $only_without_recent_contact = (int) $this->_get_clean_value($options, "only_without_recent_contact");
        $inactive_days = (int) $this->_get_clean_value($options, "inactive_days") ?: 30;
        $fidelity_days = (int) $this->_get_clean_value($options, "fidelity_days") ?: 90;
        $date_from = $this->_get_clean_value($options, "date_from");
        $date_to = $this->_get_clean_value($options, "date_to");

        $inactive_days = min(max($inactive_days, 1), 365);
        $fidelity_days = min(max($fidelity_days, 1), 730);
        $today = date("Y-m-d");
        $fidelity_until = date("Y-m-d", strtotime("+$fidelity_days days"));

        $lead_where = "";
        $sale_without_lead_where = " AND $sales.lead_id IS NULL AND $sales.fidelity_until BETWEEN " . $this->db->escape($today) . " AND " . $this->db->escape($fidelity_until);

        if ($renewal_month) {
            $lead_where .= " AND $leads.renewal_month=$renewal_month";
            $sale_without_lead_where .= " AND 1=0";
        } else {
            $lead_where .= " AND ($leads.renewal_month IS NOT NULL OR fidelity_sale.id IS NOT NULL)";
        }

        if ($operator_id) {
            $lead_where .= " AND ($leads.current_operator_id=$operator_id OR fidelity_sale.operator_id=$operator_id)";
            $sale_without_lead_where .= " AND $sales.operator_id=$operator_id";
        }

        if ($source_id) {
            $lead_where .= " AND $leads.source_id=$source_id";
            $sale_without_lead_where .= " AND 1=0";
        }

        if ($status_id) {
            $lead_where .= " AND $leads.status_id=$status_id";
            $sale_without_lead_where .= " AND 1=0";
        }

        if ($temperature) {
            $lead_where .= " AND $leads.temperature=" . $this->db->escape($temperature);
            $sale_without_lead_where .= " AND 1=0";
        }

        if ($consultant_id) {
            $lead_where .= " AND ($leads.owner_id=$consultant_id OR fidelity_sale.consultant_id=$consultant_id)";
            $sale_without_lead_where .= " AND $sales.consultant_id=$consultant_id";
        }

        if ($date_from || $date_to) {
            $renewal_months = $this->_months_between($date_from ?: $date_to, $date_to ?: $date_from);
            $month_condition = count($renewal_months) ? "$leads.renewal_month IN (" . implode(",", $renewal_months) . ")" : "1=0";
            $fidelity_condition = "fidelity_sale.fidelity_until IS NOT NULL";
            if ($date_from) {
                $fidelity_condition .= " AND fidelity_sale.fidelity_until>=" . $this->db->escape($date_from);
                $sale_without_lead_where .= " AND $sales.fidelity_until>=" . $this->db->escape($date_from);
            }
            if ($date_to) {
                $fidelity_condition .= " AND fidelity_sale.fidelity_until<=" . $this->db->escape($date_to);
                $sale_without_lead_where .= " AND $sales.fidelity_until<=" . $this->db->escape($date_to);
            }

            $lead_where .= " AND (($fidelity_condition) OR ($month_condition))";
        }

        $no_future_task_sql = "future_tasks.future_task_count IS NULL OR future_tasks.future_task_count=0";
        $no_recent_contact_sql = "last_interactions.last_contact_at IS NULL OR last_interactions.last_contact_at < DATE_SUB(NOW(), INTERVAL $inactive_days DAY)";

        if ($only_without_future_task) {
            $lead_where .= " AND ($no_future_task_sql)";
        }

        if ($only_without_recent_contact) {
            $lead_where .= " AND ($no_recent_contact_sql)";
        }

        $fidelity_sale_sql = "SELECT s1.*
            FROM $sales s1
            INNER JOIN (
                SELECT lead_id, MIN(fidelity_until) AS fidelity_until
                FROM $sales
                WHERE deleted=0
                    AND lead_id IS NOT NULL
                    AND status NOT IN ('Cancelada','Estornada')
                    AND fidelity_until BETWEEN " . $this->db->escape($today) . " AND " . $this->db->escape($fidelity_until) . "
                GROUP BY lead_id
            ) nearest_sale ON nearest_sale.lead_id=s1.lead_id AND nearest_sale.fidelity_until=s1.fidelity_until
            WHERE s1.deleted=0
            GROUP BY s1.lead_id";

        $future_tasks_sql = "SELECT lead_id, COUNT(*) AS future_task_count, MIN(due_date) AS next_task_due
            FROM $tasks
            WHERE deleted=0 AND status IN ('aberta','em_andamento') AND due_date >= NOW()
            GROUP BY lead_id";

        $last_interactions_sql = "SELECT lead_id, MAX(created_at) AS last_contact_at
            FROM $interactions
            WHERE deleted=0
            GROUP BY lead_id";

        $lead_sql = "SELECT
                'lead' AS row_source,
                $leads.id AS lead_id,
                fidelity_sale.id AS sale_id,
                $clients.id AS client_id,
                $clients.name AS client_name,
                $contacts.phone_normalized,
                $contacts.phone_original,
                $leads.lead_code,
                COALESCE($leads.current_operator_id, fidelity_sale.operator_id) AS operator_id,
                COALESCE($operators.name, sale_operator.name) AS operator_name,
                COALESCE($leads.current_plan_name, sale_plan.name, fidelity_sale.plan_name) AS plan_name,
                $leads.current_paid_value,
                COALESCE($leads.proposed_value, fidelity_sale.sale_value) AS proposed_value,
                $leads.renewal_month,
                fidelity_sale.fidelity_until,
                $leads.temperature,
                $statuses.title AS status_title,
                last_interactions.last_contact_at,
                COALESCE(future_tasks.next_task_due, $leads.next_followup_at) AS next_followup_at,
                $leads.owner_id,
                CONCAT($users.first_name, ' ', $users.last_name) AS owner_name,
                CASE WHEN $no_future_task_sql THEN 1 ELSE 0 END AS no_future_task,
                CASE WHEN $no_recent_contact_sql THEN 1 ELSE 0 END AS no_recent_contact
            FROM $leads
            INNER JOIN $clients ON $clients.id=$leads.client_id AND $clients.deleted=0
            LEFT JOIN $contacts ON $contacts.client_id=$clients.id AND $contacts.deleted=0 AND $contacts.is_primary=1
            LEFT JOIN $statuses ON $statuses.id=$leads.status_id AND $statuses.deleted=0
            LEFT JOIN $operators ON $operators.id=$leads.current_operator_id AND $operators.deleted=0
            LEFT JOIN $users ON $users.id=$leads.owner_id AND $users.deleted=0
            LEFT JOIN ($fidelity_sale_sql) fidelity_sale ON fidelity_sale.lead_id=$leads.id
            LEFT JOIN $operators sale_operator ON sale_operator.id=fidelity_sale.operator_id AND sale_operator.deleted=0
            LEFT JOIN $plans sale_plan ON sale_plan.id=fidelity_sale.plan_id AND sale_plan.deleted=0
            LEFT JOIN ($future_tasks_sql) future_tasks ON future_tasks.lead_id=$leads.id
            LEFT JOIN ($last_interactions_sql) last_interactions ON last_interactions.lead_id=$leads.id
            WHERE $leads.deleted=0 $lead_where
            GROUP BY $leads.id";

        $sale_without_lead_sql = "SELECT
                'sale' AS row_source,
                NULL AS lead_id,
                $sales.id AS sale_id,
                $clients.id AS client_id,
                $clients.name AS client_name,
                $contacts.phone_normalized,
                $contacts.phone_original,
                NULL AS lead_code,
                $sales.operator_id,
                $operators.name AS operator_name,
                COALESCE($plans.name, $sales.plan_name) AS plan_name,
                NULL AS current_paid_value,
                $sales.sale_value AS proposed_value,
                NULL AS renewal_month,
                $sales.fidelity_until,
                NULL AS temperature,
                $sales.status AS status_title,
                NULL AS last_contact_at,
                NULL AS next_followup_at,
                $sales.consultant_id AS owner_id,
                CONCAT($users.first_name, ' ', $users.last_name) AS owner_name,
                1 AS no_future_task,
                1 AS no_recent_contact
            FROM $sales
            INNER JOIN $clients ON $clients.id=$sales.client_id AND $clients.deleted=0
            LEFT JOIN $contacts ON $contacts.client_id=$clients.id AND $contacts.deleted=0 AND $contacts.is_primary=1
            LEFT JOIN $operators ON $operators.id=$sales.operator_id AND $operators.deleted=0
            LEFT JOIN $plans ON $plans.id=$sales.plan_id AND $plans.deleted=0
            LEFT JOIN $users ON $users.id=$sales.consultant_id AND $users.deleted=0
            WHERE $sales.deleted=0 AND $sales.status NOT IN ('Cancelada','Estornada') $sale_without_lead_where
            GROUP BY $sales.id";

        return $this->db->query("SELECT * FROM (($lead_sql) UNION ALL ($sale_without_lead_sql)) renewal_rows
            ORDER BY CASE WHEN fidelity_until IS NULL THEN 1 ELSE 0 END ASC, fidelity_until ASC, renewal_month ASC, client_name ASC");
    }

    /**
     * Rebaixa a temperatura de leads sem interacao por X dias (quente->morno->frio).
     * Estrutura pronta para ser chamada por cron/hook na proxima etapa.
     * Ordena morno->frio antes de quente->morno para nao rebaixar dois niveis no mesmo run.
     */
    public function downgrade_stale_temperatures($days = 7)
    {
        $days = max(1, (int) $days);
        $leads = $this->db->prefixTable("green_leads");
        $statuses = $this->db->prefixTable("green_lead_statuses");

        $stale = "(($leads.last_interaction_at IS NULL AND $leads.created_at < DATE_SUB(NOW(), INTERVAL $days DAY))
            OR ($leads.last_interaction_at IS NOT NULL AND $leads.last_interaction_at < DATE_SUB(NOW(), INTERVAL $days DAY)))";
        $not_final = "($leads.status_id IS NULL OR $leads.status_id NOT IN (SELECT id FROM $statuses WHERE is_final=1))";

        $this->db->query("UPDATE $leads SET temperature='frio', temperature_changed_at=NOW()
            WHERE deleted=0 AND temperature='morno' AND $stale AND $not_final");
        $morno_to_frio = $this->db->affectedRows();

        $this->db->query("UPDATE $leads SET temperature='morno', temperature_changed_at=NOW()
            WHERE deleted=0 AND temperature='quente' AND $stale AND $not_final");
        $quente_to_morno = $this->db->affectedRows();

        return ["quente_to_morno" => $quente_to_morno, "morno_to_frio" => $morno_to_frio];
    }

    public function get_dropdown($include_blank = true, $limit = 500)
    {
        $limit = max(1, (int) $limit);
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");

        $rows = $this->db->query("SELECT $leads.id, $leads.lead_code, $clients.name AS client_name
            FROM $leads
            INNER JOIN $clients ON $clients.id=$leads.client_id
            WHERE $leads.deleted=0
            ORDER BY $leads.id DESC
            LIMIT $limit")->getResult();

        $dropdown = $include_blank ? ["" => "- Sem lead -"] : [];
        foreach ($rows as $row) {
            $label = ($row->lead_code ?: ("GREEN-" . $row->id)) . " - " . $row->client_name;
            $dropdown[$row->id] = $label;
        }

        return $dropdown;
    }

    public function get_recent($limit = 5)
    {
        $limit = max(1, (int) $limit);
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $statuses = $this->db->prefixTable("green_lead_statuses");
        $operators = $this->db->prefixTable("green_operators");

        return $this->db->query("SELECT $leads.id, $leads.lead_code, $leads.temperature,
                $clients.name AS client_name,
                $statuses.title AS status_title,
                $operators.name AS operator_name
            FROM $leads
            INNER JOIN $clients ON $clients.id=$leads.client_id
            LEFT JOIN $statuses ON $statuses.id=$leads.status_id AND $statuses.deleted=0
            LEFT JOIN $operators ON $operators.id=$leads.current_operator_id AND $operators.deleted=0
            WHERE $leads.deleted=0
            ORDER BY $leads.id DESC
            LIMIT $limit");
    }

    private function _dashboard_where($options, $leads)
    {
        $where = "";

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND DATE($leads.created_at)>=" . $this->db->escape($date_from);
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND DATE($leads.created_at)<=" . $this->db->escape($date_to);
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

        return $where;
    }

    private function _months_between($date_from, $date_to)
    {
        if (!$date_from || !$date_to) {
            return [];
        }

        $start = strtotime($date_from);
        $end = strtotime($date_to);
        if (!$start || !$end) {
            return [];
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $months = [];
        $cursor = strtotime(date("Y-m-01", $start));
        $end_month = strtotime(date("Y-m-01", $end));
        $guard = 0;
        while ($cursor <= $end_month && $guard < 36) {
            $months[(int) date("n", $cursor)] = (int) date("n", $cursor);
            $cursor = strtotime("+1 month", $cursor);
            $guard++;
        }

        return array_values($months);
    }
}
