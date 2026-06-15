<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_imports extends Security_Controller
{
    public $Green_import_batches_model;
    public $Green_import_rows_model;
    public $Green_clients_model;
    public $Green_operators_model;
    public $Green_plans_model;
    public $Green_sales_model;
    public $Green_commission_installments_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_import_batches_model = model("Green_crm\Models\Green_import_batches_model");
        $this->Green_import_rows_model = model("Green_crm\Models\Green_import_rows_model");
        $this->Green_clients_model = model("Green_crm\Models\Green_clients_model");
        $this->Green_operators_model = model("Green_crm\Models\Green_operators_model");
        $this->Green_plans_model = model("Green_crm\Models\Green_plans_model");
        $this->Green_sales_model = model("Green_crm\Models\Green_sales_model");
        $this->Green_commission_installments_model = model("Green_crm\Models\Green_commission_installments_model");
    }

    public function modal_form()
    {
        return $this->template->view("Green_crm\Views\importar_modal");
    }

    public function preview()
    {
        $file = $this->request->getFile("file");
        if (!$file || !$file->isValid()) {
            echo json_encode(["success" => false, "message" => "Arquivo invalido."]);
            return;
        }

        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, ["xlsx", "xls", "csv"], true)) {
            echo json_encode(["success" => false, "message" => "Envie um arquivo XLSX, XLS ou CSV."]);
            return;
        }

        try {
            $rows = $this->_read_file($file->getTempName(), $extension);
            $preview = $this->_build_preview($rows);
        } catch (\Throwable $e) {
            log_message("error", "Erro no preview de importacao Green CRM: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Nao foi possivel ler a planilha."]);
            return;
        }

        $has_errors = $this->_has_blocking_errors($preview);
        $this->session->set("green_import_preview", [
            "file_name" => $file->getClientName(),
            "rows" => $preview,
            "has_errors" => $has_errors
        ]);

        echo json_encode([
            "success" => true,
            "preview_html" => view("Green_crm\Views\importar_preview", ["rows" => $preview, "has_errors" => $has_errors]),
            "message" => $has_errors ? "Preview gerado com erros bloqueantes." : "Preview gerado."
        ]);
    }

    public function confirm()
    {
        $payload = $this->session->get("green_import_preview");
        if (!$payload || empty($payload["rows"])) {
            echo json_encode(["success" => false, "message" => "Nenhum preview pendente."]);
            return;
        }

        if (!empty($payload["has_errors"]) || $this->_has_blocking_errors($payload["rows"])) {
            echo json_encode(["success" => false, "message" => "Corrija os erros bloqueantes antes de confirmar a importacao."]);
            return;
        }

        $batch_id = $this->Green_import_batches_model->create_batch($payload["file_name"], "crm_vendidos", $this->login_user->id);
        $success = 0;
        $errors = 0;
        $db = db_connect();

        foreach ($payload["rows"] as $row) {
            $db->transBegin();
            try {
                $client_id = $this->_upsert_client($row);
                $operator_id = $this->Green_operators_model->find_or_create($row["operator_normalized"]);
                $plan_id = $row["plan_normalized"] ? $this->Green_plans_model->find_or_create($row["plan_normalized"], $operator_id) : 0;

                $sale_data = [
                    "client_id" => $client_id,
                    "operator_id" => $operator_id,
                    "plan_id" => $plan_id ?: null,
                    "plan_name" => $row["plan_original"] ?: $row["plan_normalized"],
                    "sale_date" => $row["sale_date"],
                    "sale_value" => $row["sale_value"],
                    "total_commission_multiplier" => $row["commission_total"],
                    "bonus_amount" => $row["bonus_amount"],
                    "legacy_total" => $row["legacy_total"],
                    "implantation_date" => $row["implantation_date"],
                    "fidelity_until" => $row["fidelity_until"],
                    "consultant_id" => $this->login_user->id,
                    "status" => "Vendida",
                    "implantation_status" => $row["implantation_date"] ? "implantada" : "pendente",
                    "created_by" => $this->login_user->id,
                    "updated_by" => $this->login_user->id,
                    "deleted" => 0
                ];

                $sale_id = $this->Green_sales_model->ci_save($sale_data);
                $sale_code_data = ["sale_code" => sprintf("SALE-%s-%06d", date("Y"), $sale_id)];
                $this->Green_sales_model->ci_save($sale_code_data, $sale_id);

                if (!empty($row["schedule"])) {
                    $this->Green_commission_installments_model->generate_for_sale($sale_id, $row["schedule"], (int) $this->login_user->id);
                }

                $this->Green_import_rows_model->add_row(
                    $batch_id,
                    $row["line"],
                    $this->_json($row["raw"]),
                    $row["action"],
                    "sale",
                    $sale_id,
                    "",
                    implode("; ", $row["warnings"])
                );

                $db->transCommit();
                $success++;
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message("error", "Erro ao confirmar importacao Green CRM linha " . ($row["line"] ?? 0) . ": " . $e->getMessage());

                $this->Green_import_rows_model->add_row(
                    $batch_id,
                    $row["line"] ?? 0,
                    $this->_json($row["raw"] ?? []),
                    "Erro",
                    "sale",
                    0,
                    $e->getMessage(),
                    implode("; ", $row["warnings"] ?? [])
                );
                $errors++;
            }
        }

        $this->Green_import_batches_model->update_totals($batch_id, count($payload["rows"]), $success, $errors);
        $this->session->remove("green_import_preview");
        green_audit("import", $batch_id, "import_confirmed", null, ["success" => $success, "errors" => $errors], $this->login_user->id);

        echo json_encode([
            "success" => $errors === 0,
            "report_html" => view("Green_crm\Views\importar_relatorio", [
                "batch_id" => $batch_id,
                "success" => $success,
                "errors" => $errors,
                "rows" => $this->Green_import_rows_model->get_by_batch($batch_id)->getResult()
            ]),
            "message" => $errors ? "Importacao concluida com erros." : "Importacao concluida."
        ]);
    }

    public function report($batch_id)
    {
        return $this->template->view("Green_crm\Views\importar_relatorio", [
            "batch_id" => $batch_id,
            "rows" => $this->Green_import_rows_model->get_by_batch($batch_id)->getResult()
        ]);
    }

    private function _read_file($path, $extension)
    {
        if (in_array($extension, ["xlsx", "xls"], true)) {
            $autoload = APPPATH . "ThirdParty/PHPOffice-PhpSpreadsheet/vendor/autoload.php";
            if (!is_file($autoload)) {
                throw new \RuntimeException("PhpSpreadsheet nao encontrado.");
            }

            require_once $autoload;
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                if (preg_match("/^\d{4}$/", $sheet->getTitle())) {
                    return $sheet->toArray(null, true, true, true);
                }
            }

            return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }

        $handle = fopen($path, "r");
        $rows = [];
        while (($row = fgetcsv($handle, 0, ";")) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function _build_preview($rows)
    {
        if (!count($rows)) {
            return [];
        }

        array_shift($rows);

        $out = [];
        $line = 1;
        foreach ($rows as $row) {
            $line++;
            if ($this->_row_is_empty($row)) {
                continue;
            }

            $out[] = $this->_preview_row($row, $line);
        }

        return $out;
    }

    private function _preview_row($row, $line)
    {
        $client = trim((string) $this->_cell($row, "A"));
        $document = green_normalize_document($this->_cell($row, "B"));
        $sale_date = green_date_value($this->_cell($row, "C"));
        $operator_original = trim((string) $this->_cell($row, "D"));
        $operator_normalized = green_normalize_operator($operator_original);
        $plan_original = trim((string) $this->_cell($row, "E"));
        $plan_normalized = green_normalize_plan($plan_original);
        $sale_value = green_money_to_float($this->_cell($row, "F"));
        $implantation_raw = $this->_cell($row, "G");
        $fidelity_raw = $this->_cell($row, "H");
        $implantation_date = green_date_value($implantation_raw);
        $fidelity_until = green_date_value($fidelity_raw);
        $commission_total = $this->_rate_to_float($this->_cell($row, "I"));
        $bonus_amount = green_money_to_float($this->_cell($row, "J"));
        $legacy_total = green_money_to_float($this->_cell($row, "AI"));

        $errors = [];
        $warnings = [];

        if (!$client) {
            $errors[] = "CLIENTE vazio";
        }
        if (!$sale_date) {
            $errors[] = "DATA VENDA invalida";
        }
        if ($sale_value === null || $sale_value <= 0) {
            $errors[] = "VALOR VENDA invalido";
        }
        if (!$operator_original) {
            $errors[] = "OPERADORA vazia";
        }
        if (!$document["document_number"]) {
            $warnings[] = "CPF/CNPJ vazio";
        }
        if (!$implantation_date) {
            $warnings[] = trim((string) $implantation_raw) === "" ? "Implantacao vazia" : "Implantacao invalida";
        }
        if (!$fidelity_until) {
            $warnings[] = trim((string) $fidelity_raw) === "" ? "Fidelidade vazia" : "Fidelidade invalida";
        }
        if ($plan_original && $this->_plan_not_standardized($plan_original, $plan_normalized)) {
            $warnings[] = "Plano nao padronizado";
        }

        $due_year = $sale_date ? (int) date("Y", strtotime($sale_date)) : (int) date("Y");
        $schedule = [];
        $sum_rates = 0;
        $total_expected = 0;
        $installment_no = 1;

        foreach ($this->_commission_month_columns() as $month) {
            $rate_raw = $this->_cell($row, $month["rate_column"]);
            $legacy_raw = $this->_cell($row, $month["amount_column"]);
            $has_rate = trim((string) $rate_raw) !== "";
            $has_legacy = trim((string) $legacy_raw) !== "";
            $rate = $this->_rate_to_float($rate_raw);
            $legacy_amount = green_money_to_float($legacy_raw);

            if ($has_rate && $rate === null) {
                $warnings[] = "Percentual de " . $month["month_name"] . " invalido";
            }
            if ($has_legacy && $legacy_amount === null) {
                $warnings[] = "Valor mensal de " . $month["month_name"] . " invalido";
            }
            if ($has_legacy && $rate === null) {
                $warnings[] = "Valor mensal preenchido sem percentual em " . $month["month_name"];
            }
            if ($rate !== null && !$has_legacy) {
                $warnings[] = "Percentual preenchido sem valor mensal em " . $month["month_name"];
            }

            if ($rate !== null || $legacy_amount !== null) {
                $expected = $rate !== null && $sale_value !== null ? round($sale_value * $rate, 2) : 0;
                $schedule[] = [
                    "installment_no" => $installment_no++,
                    "due_month" => $month["month_number"],
                    "due_year" => $due_year,
                    "commission_type" => "comissao",
                    "commission_rate" => $rate,
                    "expected_amount" => $expected,
                    "legacy_rate" => $rate,
                    "legacy_amount" => $legacy_amount,
                    "legacy_month_name" => $month["month_name"],
                    "notes" => "Importacao Excel CRM Vendidos"
                ];
                $sum_rates += (float) $rate;
                $total_expected += $expected;
            }
        }

        if ($bonus_amount !== null && $bonus_amount > 0) {
            $bonus_month = $sale_date ? (int) date("n", strtotime($sale_date)) : (int) date("n");
            $schedule[] = [
                "installment_no" => $installment_no++,
                "due_month" => $bonus_month,
                "due_year" => $due_year,
                "commission_type" => "bonus",
                "commission_rate" => null,
                "expected_amount" => $bonus_amount,
                "legacy_rate" => null,
                "legacy_amount" => null,
                "legacy_month_name" => "BONUS",
                "notes" => "Bonus importado do Excel CRM Vendidos"
            ];
            $total_expected += $bonus_amount;
        }

        if ($commission_total !== null && abs($commission_total - $sum_rates) > 0.01) {
            $warnings[] = "Comissao total divergente da soma dos percentuais";
        }

        $difference = $legacy_total !== null ? round($legacy_total - $total_expected, 2) : null;
        if ($legacy_total !== null && abs($difference) > 0.01) {
            $warnings[] = "Total legado diferente do total calculado";
        }

        return [
            "line" => $line,
            "client" => $client,
            "document" => $document,
            "operator_original" => $operator_original,
            "operator_normalized" => $operator_normalized,
            "plan_original" => $plan_original,
            "plan_normalized" => $plan_normalized,
            "sale_value" => $sale_value,
            "sale_date" => $sale_date,
            "implantation_date" => $implantation_date,
            "fidelity_until" => $fidelity_until,
            "commission_total" => $commission_total,
            "bonus_amount" => $bonus_amount,
            "legacy_total" => $legacy_total,
            "schedule" => $schedule,
            "total_expected_calculated" => round($total_expected, 2),
            "total_legacy_informed" => $legacy_total,
            "difference" => $difference,
            "action" => count($errors) ? "Erro" : "Criar venda",
            "errors" => $errors,
            "warnings" => array_values(array_unique($warnings)),
            "raw" => $row
        ];
    }

    private function _upsert_client($row)
    {
        $document = $row["document"];
        // Dedup: 1) documento (CPF/CNPJ), 2) telefone, 3) e-mail, 4) nome exato
        $existing = $document["document_number"] ? $this->Green_clients_model->find_by_document($document["document_type"], $document["document_number"]) : null;

        if (!$existing && !empty($row["phone"])) {
            $phone_normalized = green_normalize_phone($row["phone"]);
            $existing = $phone_normalized ? $this->Green_clients_model->find_by_phone($phone_normalized) : null;
        }

        if (!$existing && !empty($row["email"])) {
            $email_normalized = green_normalize_email($row["email"]);
            $existing = $email_normalized ? $this->Green_clients_model->find_by_email($email_normalized) : null;
        }

        if (!$existing && $row["client"]) {
            $db = db_connect();
            $clients = $db->prefixTable("green_clients");
            $existing = $db->query("SELECT * FROM $clients
                WHERE deleted=0 AND name=" . $db->escape($row["client"]) . "
                ORDER BY id ASC LIMIT 1")->getRow();
        }

        $client_data = [
            "client_type" => $document["document_type"] === "CNPJ" ? "PJ" : ($document["document_type"] === "CPF" ? "PF" : "NAO_INFORMADO"),
            "name" => $row["client"],
            "document_type" => $document["document_type"],
            "document_number" => $document["document_number"] ?: null,
            "created_by" => $this->login_user->id,
            "updated_by" => $this->login_user->id,
            "deleted" => 0
        ];

        $client_id = $this->Green_clients_model->ci_save($client_data, $existing->id ?? 0);

        // Garante Cod Cliente (client_code) para clientes importados sem codigo
        $saved = $this->Green_clients_model->get_details(["id" => $client_id])->getRow();
        if ($saved && empty($saved->client_code)) {
            $this->Green_clients_model->ci_save([
                "client_code" => sprintf("CLI-%s-%06d", date("Y"), $client_id)
            ], $client_id);
        }

        return $client_id;
    }

    private function _commission_month_columns()
    {
        return [
            ["month_name" => "JANEIRO", "month_number" => 1, "rate_column" => "K", "amount_column" => "L"],
            ["month_name" => "FEVEREIRO", "month_number" => 2, "rate_column" => "M", "amount_column" => "N"],
            ["month_name" => "MARCO", "month_number" => 3, "rate_column" => "O", "amount_column" => "P"],
            ["month_name" => "ABRIL", "month_number" => 4, "rate_column" => "Q", "amount_column" => "R"],
            ["month_name" => "MAIO", "month_number" => 5, "rate_column" => "S", "amount_column" => "T"],
            ["month_name" => "JUNHO", "month_number" => 6, "rate_column" => "U", "amount_column" => "V"],
            ["month_name" => "JULHO", "month_number" => 7, "rate_column" => "W", "amount_column" => "X"],
            ["month_name" => "AGOSTO", "month_number" => 8, "rate_column" => "Y", "amount_column" => "Z"],
            ["month_name" => "SETEMBRO", "month_number" => 9, "rate_column" => "AA", "amount_column" => "AB"],
            ["month_name" => "OUTUBRO", "month_number" => 10, "rate_column" => "AC", "amount_column" => "AD"],
            ["month_name" => "NOVEMBRO", "month_number" => 11, "rate_column" => "AE", "amount_column" => "AF"],
            ["month_name" => "DEZEMBRO", "month_number" => 12, "rate_column" => "AG", "amount_column" => "AH"]
        ];
    }

    private function _cell($row, $column)
    {
        if (is_array($row) && array_key_exists($column, $row)) {
            return $row[$column];
        }

        $index = $this->_column_to_index($column);
        if (is_array($row) && array_key_exists($index, $row)) {
            return $row[$index];
        }

        return "";
    }

    private function _column_to_index($column)
    {
        $column = strtoupper($column);
        $number = 0;
        for ($i = 0; $i < strlen($column); $i++) {
            $number = ($number * 26) + (ord($column[$i]) - 64);
        }

        return $number - 1;
    }

    private function _rate_to_float($value)
    {
        if ($value === null || trim((string) $value) === "") {
            return null;
        }

        $value = trim((string) $value);
        $has_percent = strpos($value, "%") !== false;
        $value = str_replace("%", "", $value);
        $number = green_money_to_float($value);
        if ($number === null) {
            return null;
        }

        return $has_percent ? $number / 100 : $number;
    }

    private function _row_is_empty($row)
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== "") {
                return false;
            }
        }

        return true;
    }

    private function _plan_not_standardized($original, $normalized)
    {
        $original_key = green_ascii_key($original);
        $normalized_key = green_ascii_key($normalized);
        if ($original_key !== $normalized_key) {
            return false;
        }

        return !in_array($normalized_key, ["PRATA", "PRATA PRO"], true);
    }

    private function _has_blocking_errors($rows)
    {
        foreach ($rows as $row) {
            if (!empty($row["errors"])) {
                return true;
            }
        }

        return false;
    }

    private function _json($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
