<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_data_quality extends Security_Controller
{
    public $Green_data_quality_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_data_quality_model = model("Green_crm\Models\Green_data_quality_model");
    }

    public function index()
    {
        $pending_days = $this->_pending_days();

        return $this->template->render("Green_crm\Views\lista_data_quality", [
            "summary" => $this->Green_data_quality_model->get_summary(["pending_days" => $pending_days]),
            "pending_days" => $pending_days
        ]);
    }

    public function list_data()
    {
        $rows = [];
        $pending_days = $this->_pending_days();

        foreach ($this->Green_data_quality_model->get_rows(["pending_days" => $pending_days]) as $row) {
            $rows[] = $this->_quality_row($row);
        }

        echo json_encode(["data" => $rows]);
    }

    private function _quality_row($row)
    {
        return [
            $this->_type_badge($row["type_key"], $row["type"]),
            esc($row["record_label"]),
            esc($row["client_name"]),
            esc($row["problem"]),
            $this->_severity_badge($row["severity"]),
            esc($row["suggested_action"]),
            $this->_actions($row)
        ];
    }

    private function _actions($row)
    {
        $lead_id = (int) ($row["lead_id"] ?? 0);
        $sale_id = (int) ($row["sale_id"] ?? 0);

        if ($row["type_key"] === "lead" && $lead_id) {
            return anchor(get_uri("green_crm/lead/" . $lead_id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead"]);
        }

        if (($row["type_key"] === "sale" || $row["type_key"] === "commission") && $sale_id) {
            $actions = modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir venda", "data-post-id" => $sale_id]);
            if ($lead_id) {
                $actions .= anchor(get_uri("green_crm/lead/" . $lead_id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead"]);
            }

            return $actions;
        }

        if ($row["type_key"] === "client") {
            if ($lead_id) {
                return anchor(get_uri("green_crm/lead/" . $lead_id), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir lead do cliente"]);
            }
            if ($sale_id) {
                return modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='shopping-cart' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Abrir venda do cliente", "data-post-id" => $sale_id]);
            }
        }

        return "<span class='text-off'>Sem lead/venda</span>";
    }

    private function _type_badge($type_key, $label)
    {
        $classes = [
            "client" => "bg-info",
            "lead" => "bg-primary",
            "sale" => "bg-warning",
            "commission" => "bg-danger"
        ];

        return "<span class='badge " . ($classes[$type_key] ?? "bg-secondary") . "'>" . esc($label) . "</span>";
    }

    private function _severity_badge($severity)
    {
        $classes = [
            "alta" => "bg-danger",
            "media" => "bg-warning",
            "baixa" => "bg-info"
        ];

        return "<span class='badge " . ($classes[$severity] ?? "bg-secondary") . "'>" . esc(ucfirst($severity)) . "</span>";
    }

    private function _pending_days()
    {
        $days = (int) ($this->request->getPost("pending_days") ?: $this->request->getGet("pending_days") ?: 30);
        return max(1, min(365, $days));
    }
}
