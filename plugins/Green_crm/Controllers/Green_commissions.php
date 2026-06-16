<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_commissions extends Security_Controller
{
    public $Green_commission_installments_model;
    public $Green_sales_model;
    public $Green_interactions_model;
    public $Green_operators_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        helper("green");
        $this->Green_commission_installments_model = model("Green_crm\Models\Green_commission_installments_model");
        $this->Green_sales_model = model("Green_crm\Models\Green_sales_model");
        $this->Green_interactions_model = model("Green_crm\Models\Green_interactions_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Users_model = model("App\Models\Users_model");
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_commissions", [
            "operators_dropdown" => $this->_to_dropdown($this->Green_operators_model->get_details()->getResult(), "name", true),
            "consultants_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"])
        ]);
    }

    public function list_data()
    {
        $rows = [];
        foreach ($this->Green_commission_installments_model->get_details($this->_filters_from_request())->getResult() as $data) {
            $rows[] = $this->_commission_row($data);
        }
        echo json_encode(["data" => $rows]);
    }

    public function summary_data()
    {
        $totals = $this->Green_commission_installments_model->get_totals($this->_filters_from_request());
        echo json_encode([
            "success" => true,
            "data" => [
                "expected_amount_total" => (float) ($totals->expected_amount_total ?? 0),
                "received_amount_total" => (float) ($totals->received_amount_total ?? 0),
                "open_amount_total" => (float) ($totals->open_amount_total ?? 0),
                "overdue_amount_total" => (float) ($totals->overdue_amount_total ?? 0),
                "difference_amount_total" => (float) ($totals->difference_amount_total ?? 0),
                "bonus_expected_total" => (float) ($totals->bonus_expected_total ?? 0),
                "reversal_amount_total" => (float) ($totals->reversal_amount_total ?? 0)
            ]
        ]);
    }

    public function generate()
    {
        $sale_id = (int) $this->request->getPost("sale_id");
        if (!$sale_id) {
            $sale_id = (int) $this->request->getPost("id");
        }

        $schedule = $this->_schedule_from_request();
        if (!$sale_id || !count($schedule)) {
            echo json_encode(["success" => false, "message" => "Informe a venda e ao menos uma parcela."]);
            return;
        }

        $count = $this->Green_commission_installments_model->generate_for_sale($sale_id, $schedule, (int) $this->login_user->id);
        if ($count === false) {
            echo json_encode(["success" => false, "message" => "Nao foi possivel gerar. Verifique venda ou parcelas ja recebidas."]);
            return;
        }
        green_audit("sale", $sale_id, "commission_generated", null, $schedule, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "$count parcela(s) gerada(s)."]);
    }

    public function generation_modal_form()
    {
        $sale_id = (int) $this->request->getPost("sale_id");
        if (!$sale_id) {
            $sale_id = (int) $this->request->getPost("id");
        }

        $sale = $this->Green_sales_model->get_details(["id" => $sale_id])->getRow();
        if (!$sale) {
            echo "Venda invalida.";
            return;
        }

        return $this->template->view("Green_crm\Views\modal_commission_generation", [
            "sale" => $sale,
            "installments" => $this->Green_commission_installments_model->get_by_sale($sale_id)->getResult()
        ]);
    }

    public function payment_modal_form()
    {
        $id = (int) $this->request->getPost("id");
        return $this->template->view("Green_crm\Views\modal_commission_payment", ["model_info" => $this->Green_commission_installments_model->get_one($id)]);
    }

    public function save_payment()
    {
        $this->validate_submitted_data(["id" => "required|numeric", "received_amount" => "required", "paid_at" => "required"]);
        $id = (int) $this->request->getPost("id");
        $received = green_money_to_float($this->request->getPost("received_amount"));
        if (!$received || $received <= 0) {
            echo json_encode(["success" => false, "message" => "Valor recebido deve ser maior que zero."]);
            return;
        }

        $paid_at = $this->_datetime_value($this->request->getPost("paid_at"));
        if (!$paid_at) {
            echo json_encode(["success" => false, "message" => "Informe a data de pagamento."]);
            return;
        }

        $old_row = $this->Green_commission_installments_model->get_details(["id" => $id])->getRow();
        if (!$this->Green_commission_installments_model->mark_as_paid($id, $received, $paid_at, $this->request->getPost("payment_method"), $this->request->getPost("notes"), (int) $this->login_user->id)) {
            echo json_encode(["success" => false, "message" => "Nao foi possivel registrar a baixa."]);
            return;
        }

        $new_row = $this->Green_commission_installments_model->get_details(["id" => $id])->getRow();
        green_audit("commission", $id, "commission_paid", $old_row, $new_row, $this->login_user->id);
        if ($new_row && !empty($new_row->lead_id)) {
            $description = "Baixa de comissão registrada. Recebido: R$ " . number_format((float) $new_row->received_amount, 2, ",", ".") .
                ". Esperado: R$ " . number_format((float) $new_row->expected_amount, 2, ",", ".") . ".";
            $this->Green_interactions_model->add_system_interaction((int) $new_row->lead_id, "Baixa de comissão", $description, (int) $this->login_user->id);
        }
        echo json_encode(["success" => true, "message" => "Baixa registrada."]);
    }

    public function cancel()
    {
        $id = (int) $this->request->getPost("id");
        $this->Green_commission_installments_model->cancel_installment($id, $this->request->getPost("notes"));
        green_audit("commission", $id, "commission_cancelled", null, null, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Comissao cancelada."]);
    }

    public function mark_as_reversed()
    {
        $id = (int) $this->request->getPost("id");
        $notes = trim((string) $this->request->getPost("notes"));
        if (!$notes) {
            echo json_encode(["success" => false, "message" => "Informe a justificativa do estorno."]);
            return;
        }

        $old_row = $this->Green_commission_installments_model->get_details(["id" => $id])->getRow();
        if (!$this->Green_commission_installments_model->mark_as_reversed($id, $notes, (int) $this->login_user->id)) {
            echo json_encode(["success" => false, "message" => "Parcela inválida."]);
            return;
        }
        $new_row = $this->Green_commission_installments_model->get_details(["id" => $id])->getRow();
        green_audit("commission", $id, "commission_reversed", $old_row, $new_row, $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Comissão estornada."]);
    }

    public function create_adjustment()
    {
        $sale_id = (int) $this->request->getPost("sale_id");
        $amount = green_money_to_float($this->request->getPost("amount"));
        $notes = trim((string) $this->request->getPost("notes"));
        if (!$sale_id || $amount === null) {
            echo json_encode(["success" => false, "message" => "Informe a venda e o valor do ajuste."]);
            return;
        }
        if (!$notes) {
            echo json_encode(["success" => false, "message" => "A justificativa do ajuste é obrigatória."]);
            return;
        }

        $new_id = $this->Green_commission_installments_model->create_adjustment(
            $sale_id,
            $amount,
            (int) $this->request->getPost("due_month"),
            (int) $this->request->getPost("due_year"),
            $notes,
            (int) $this->login_user->id
        );
        if (!$new_id) {
            echo json_encode(["success" => false, "message" => "Não foi possível criar o ajuste."]);
            return;
        }
        green_audit("commission", $new_id, "commission_adjustment_created", null, ["sale_id" => $sale_id, "amount" => $amount, "notes" => $notes], $this->login_user->id);
        echo json_encode(["success" => true, "message" => "Ajuste manual registrado."]);
    }

    private function _commission_row($data)
    {
        $actions = modal_anchor(get_uri("green_crm/sale_modal_form"), "<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Ver venda", "data-post-id" => $data->sale_id]);
        if (!in_array($data->status, ["Recebido", "Cancelado", "Estornado"], true)) {
            $actions .= modal_anchor(get_uri("green_crm/commission_payment_modal_form"), "<i data-feather='check-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Dar baixa", "data-post-id" => $data->id])
                . js_anchor("<i data-feather='x-circle' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-cancel-commission", "data-id" => $data->id, "title" => "Cancelar"]);
        }
        if (in_array($data->status, ["Recebido", "Parcial", "Divergente"], true)) {
            $actions .= js_anchor("<i data-feather='rotate-ccw' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-reverse-commission", "data-id" => $data->id, "title" => "Estornar"]);
        }

        return [
            sprintf("%02d/%04d", $data->due_month, $data->due_year),
            $data->client_name,
            $data->sale_code,
            $data->operator_name,
            $data->consultant_name,
            $data->plan_name,
            number_format((float) $data->sale_value, 2, ",", "."),
            $data->commission_type,
            $data->installment_no,
            $data->commission_rate,
            number_format((float) $data->expected_amount, 2, ",", "."),
            number_format((float) $data->received_amount, 2, ",", "."),
            $this->_difference_badge($data->difference_amount),
            $this->_status_badge($data->status),
            $data->paid_at,
            $actions
        ];
    }

    private function _status_badge($status)
    {
        $classes = [
            "Previsto" => "bg-secondary",
            "A receber" => "bg-warning",
            "Recebido" => "bg-success",
            "Parcial" => "bg-info",
            "Cancelado" => "bg-danger",
            "Estornado" => "bg-danger",
            "Divergente" => "bg-warning"
        ];
        $class = $classes[$status] ?? "bg-secondary";

        return "<span class='badge $class'>" . esc($status) . "</span>";
    }

    private function _difference_badge($difference)
    {
        $difference = (float) $difference;
        $formatted = "R$ " . number_format($difference, 2, ",", ".");
        if (abs($difference) < 0.01) {
            return "<span class='badge bg-success'>ok</span><br><span class='text-off'>$formatted</span>";
        }

        if ($difference < 0) {
            return "<span class='badge bg-danger'>recebeu menos</span><br><span>$formatted</span>";
        }

        return "<span class='badge bg-info'>recebeu mais</span><br><span>$formatted</span>";
    }

    private function _filters_from_request()
    {
        return [
            "due_month" => $this->request->getPost("due_month"),
            "due_year" => $this->request->getPost("due_year"),
            "status" => $this->request->getPost("status"),
            "commission_type" => $this->request->getPost("commission_type"),
            "operator_id" => $this->request->getPost("operator_id"),
            "consultant_id" => $this->request->getPost("consultant_id"),
            "sale_code" => $this->request->getPost("sale_code"),
            "client_search" => $this->request->getPost("client_search"),
            "only_overdue" => $this->request->getPost("only_overdue"),
            "only_divergent" => $this->request->getPost("only_divergent")
        ];
    }

    private function _to_dropdown($rows, $field, $include_blank = false)
    {
        $result = $include_blank ? ["" => "-"] : [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->{$field};
        }
        return $result;
    }

    private function _schedule_from_request()
    {
        $schedule_json = $this->request->getPost("schedule");
        $schedule = $schedule_json ? json_decode($schedule_json, true) : null;
        if (is_array($schedule)) {
            return $this->_sanitize_schedule($schedule);
        }

        $rates = $this->_as_array($this->request->getPost("commission_rate"));
        $installment_nos = $this->_as_array($this->request->getPost("installment_no"));
        $months = $this->_as_array($this->request->getPost("due_month"));
        $years = $this->_as_array($this->request->getPost("due_year"));
        $types = $this->_as_array($this->request->getPost("commission_type"));
        $expected_amounts = $this->_as_array($this->request->getPost("expected_amount"));
        $notes = $this->_as_array($this->request->getPost("notes"));
        $default_month = (int) date("m");
        $default_year = (int) date("Y");
        $schedule = [];

        foreach ($rates as $index => $rate_value) {
            $rate = green_money_to_float($rate_value);
            $expected = green_money_to_float($expected_amounts[$index] ?? "");
            if ($rate === null && $expected === null) {
                continue;
            }

            $schedule[] = [
                "installment_no" => (int) ($installment_nos[$index] ?? ($index + 1)),
                "commission_type" => $types[$index] ?? "comissao",
                "due_month" => (int) ($months[$index] ?? $default_month),
                "due_year" => (int) ($years[$index] ?? $default_year),
                "commission_rate" => $rate,
                "expected_amount" => $expected,
                "notes" => $notes[$index] ?? null
            ];
        }

        $bonus = green_money_to_float($this->request->getPost("bonus_amount"));
        if ($bonus && $bonus > 0) {
            $schedule[] = [
                "installment_no" => count($schedule) + 1,
                "commission_type" => "bonus",
                "due_month" => (int) ($this->request->getPost("bonus_due_month") ?: $default_month),
                "due_year" => (int) ($this->request->getPost("bonus_due_year") ?: $default_year),
                "commission_rate" => null,
                "expected_amount" => $bonus,
                "notes" => $this->request->getPost("bonus_notes") ?: "Bonus informado manualmente."
            ];
        }

        return $this->_sanitize_schedule($schedule);
    }

    private function _sanitize_schedule($schedule)
    {
        $result = [];
        foreach ($schedule as $item) {
            if (!is_array($item)) {
                continue;
            }

            $rate = array_key_exists("commission_rate", $item) ? green_money_to_float($item["commission_rate"]) : null;
            $expected = array_key_exists("expected_amount", $item) ? green_money_to_float($item["expected_amount"]) : null;
            if ($rate === null && $expected === null) {
                continue;
            }

            $month = (int) ($item["due_month"] ?? date("m"));
            $year = (int) ($item["due_year"] ?? date("Y"));
            if ($month < 1 || $month > 12 || $year < 2000) {
                continue;
            }

            $type = $item["commission_type"] ?? "comissao";
            if (!in_array($type, ["comissao", "bonus", "ajuste", "estorno"], true)) {
                $type = "comissao";
            }

            $result[] = [
                "installment_no" => (int) ($item["installment_no"] ?? (count($result) + 1)),
                "commission_type" => $type,
                "due_month" => $month,
                "due_year" => $year,
                "commission_rate" => $rate,
                "expected_amount" => $expected,
                "notes" => $item["notes"] ?? null
            ];
        }

        return $result;
    }

    private function _as_array($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return $value === null ? [] : [$value];
    }

    private function _datetime_value($value)
    {
        if (!$value) {
            return null;
        }

        $timestamp = strtotime(str_replace("T", " ", (string) $value));
        return $timestamp ? date("Y-m-d H:i:s", $timestamp) : null;
    }
}
