<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green extends Security_Controller
{
    public $Green_leads_model;
    public $Green_sales_model;
    public $Green_commission_installments_model;
    public $Green_quotes_model;
    public $Green_tasks_model;
    public $Green_lead_statuses_model;
    public $Green_sources_model;
    public $Green_operators_model;
    public $Green_clients_model;
    public $Green_plans_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Green_sales_model = model("Green_crm\Models\Green_sales_model");
        $this->Green_commission_installments_model = model("Green_crm\Models\Green_commission_installments_model");
        $this->Green_quotes_model = model("Green_crm\Models\Green_quotes_model");
        $this->Green_tasks_model = model("Green_crm\Models\Green_tasks_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
        $this->Green_sources_model = model("Green_crm\Models\Green_sources_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_clients_model = model("Green_crm\Models\Green_clients_model");
        $this->Green_plans_model = model("Green_crm\Models\Green_plans_model");
        $this->Users_model = model("App\Models\Users_model");
    }

    public function index()
    {
        $filters = $this->_dashboard_filters();

        return $this->template->render("Green_crm\Views\index", array_merge($this->_view_data(), [
            "dashboard" => $this->_dashboard_payload($filters),
            "dashboard_blocks" => $this->_dashboard_blocks($filters),
            "dashboard_filters" => $filters,
            "green_active_tab" => $this->_active_tab()
        ]));
    }

    public function dashboard_data()
    {
        $filters = $this->_dashboard_filters();
        echo json_encode(["success" => true, "data" => $this->_dashboard_payload($filters), "blocks" => $this->_dashboard_blocks($filters)]);
    }

    private function _view_data()
    {
        return [
            "statuses_dropdown" => $this->_to_dropdown($this->Green_lead_statuses_model->get_details()->getResult(), "title", true),
            "sources_dropdown" => $this->_to_dropdown($this->Green_sources_model->get_details()->getResult(), "title", true),
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "clients_dropdown" => $this->_to_dropdown($this->Green_clients_model->get_details()->getResult(), "name", true),
            "plans_dropdown" => $this->_plans_dropdown(),
            "consultants_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"])
        ];
    }

    private function _dashboard_payload($filters = [])
    {
        $counts = $this->Green_leads_model->get_dashboard_counts($filters);
        $sales = $this->Green_sales_model->get_totals($filters);
        $commissions = $this->Green_commission_installments_model->get_totals($filters);
        $proposal_count = $this->Green_quotes_model->get_sent_count($filters);
        $renewals = $this->Green_leads_model->get_renewal_rows(array_merge($filters, ["fidelity_days" => 90]))->getResult();
        $task_counts = $this->Green_tasks_model->get_general_counts((int) ($filters["consultant_id"] ?? 0));
        $meta_counts = $this->_meta_counts();

        return [
            "total_leads" => (int) ($counts->total_leads ?? 0),
            "leads_novos" => (int) ($counts->leads_novos ?? 0),
            "leads_quentes" => (int) ($counts->leads_quentes ?? 0),
            "leads_sem_contato" => (int) ($counts->leads_sem_contato ?? 0),
            "leads_qualificados" => (int) ($counts->leads_qualificados ?? 0),
            "tasks_today" => (int) ($task_counts->today ?? 0),
            "meta_processed" => (int) ($meta_counts["processed"] ?? 0),
            "meta_errors" => (int) ($meta_counts["errors"] ?? 0),
            "proposals_sent" => (int) $proposal_count,
            "total_sales" => (int) ($sales->total_sales ?? 0),
            "total_sale_value" => (float) ($sales->total_sale_value ?? 0),
            "average_ticket" => (float) ($sales->average_ticket ?? 0),
            "pending_implantations" => (int) ($sales->pending_implantations ?? 0),
            "commission_expected" => (float) ($commissions->expected_amount_total ?? 0),
            "commission_received" => (float) ($commissions->received_amount_total ?? 0),
            "commission_open" => (float) ($commissions->open_amount_total ?? 0),
            "commission_overdue" => (float) ($commissions->overdue_amount_total ?? 0),
            "commission_difference" => (float) ($commissions->difference_amount_total ?? 0),
            "upcoming_renewals" => count($renewals),
            "overdue_tasks" => (int) ($task_counts->overdue ?? 0),
            "conversion_rate" => $this->_conversion_rate((int) ($sales->total_sales ?? 0), (int) ($counts->total_leads ?? 0))
        ];
    }

    private function _dashboard_blocks($filters = [])
    {
        return [
            "funnel_by_status" => $this->Green_leads_model->get_funnel_by_status($filters),
            "leads_by_source" => $this->Green_leads_model->get_leads_by_source($filters),
            "sales_by_operator" => $this->Green_sales_model->get_sales_by_operator($filters),
            "commission_by_competence" => $this->Green_commission_installments_model->get_commission_by_competence($filters),
            "commission_by_partner" => $this->Green_commission_installments_model->get_totals_by_partner($filters),
            "commission_by_operator" => $this->Green_commission_installments_model->get_totals_by_operator($filters),
            "pending_implantations" => $this->Green_sales_model->get_pending_implantations($filters, 8),
            "upcoming_renewals" => array_slice($this->Green_leads_model->get_renewal_rows(array_merge($filters, ["fidelity_days" => 90]))->getResult(), 0, 8),
            "overdue_tasks" => $this->Green_tasks_model->get_overdue_dashboard($filters, 8)
        ];
    }

    private function _meta_counts()
    {
        $db = db_connect();
        $table = $db->prefixTable("green_meta_raw_leads");
        if (!$db->tableExists($table)) {
            return ["processed" => 0, "errors" => 0];
        }

        $row = $db->query("SELECT
                SUM(CASE WHEN process_status IN ('created','linked') THEN 1 ELSE 0 END) AS processed,
                SUM(CASE WHEN process_status='error' THEN 1 ELSE 0 END) AS errors
            FROM $table WHERE deleted=0")->getRow();

        return ["processed" => (int) ($row->processed ?? 0), "errors" => (int) ($row->errors ?? 0)];
    }

    private function _active_tab()
    {
        return "dashboard";
    }

    private function _conversion_rate($sales, $leads)
    {
        if ($leads <= 0) {
            return 0;
        }

        return round(($sales / $leads) * 100, 2);
    }

    private function _dashboard_filters()
    {
        return [
            "date_from" => $this->_date_filter("date_from"),
            "date_to" => $this->_date_filter("date_to"),
            "consultant_id" => (int) ($this->request->getPost("consultant_id") ?: $this->request->getGet("consultant_id")),
            "source_id" => (int) ($this->request->getPost("source_id") ?: $this->request->getGet("source_id")),
            "operator_id" => (int) ($this->request->getPost("operator_id") ?: $this->request->getGet("operator_id")),
            "status_id" => (int) ($this->request->getPost("status_id") ?: $this->request->getGet("status_id")),
            "temperature" => $this->request->getPost("temperature") ?: $this->request->getGet("temperature")
        ];
    }

    private function _date_filter($key)
    {
        $value = trim((string) ($this->request->getPost($key) ?: $this->request->getGet($key)));
        if (!$value) {
            return "";
        }

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
            return $value;
        }

        helper("green");
        if (function_exists("green_date_value")) {
            return green_date_value($value) ?: "";
        }

        return "";
    }

    private function _to_dropdown($rows, $field, $include_blank = false)
    {
        $result = $include_blank ? ["" => "-"] : [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->{$field};
        }
        return $result;
    }

    private function _plans_dropdown()
    {
        $result = ["" => "-"];
        foreach ($this->Green_plans_model->get_details(["status" => "Ativo"])->getResult() as $plan) {
            $result[$plan->id] = trim(($plan->operator_name ? $plan->operator_name . " - " : "") . $plan->name);
        }
        return $result;
    }
}
