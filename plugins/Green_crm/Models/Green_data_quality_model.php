<?php

namespace Green_crm\Models;

class Green_data_quality_model extends Green_base_model
{
    protected $table = "green_clients";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_rows($options = [])
    {
        $pending_days = max(1, (int) ($options["pending_days"] ?? 30));
        $rows = array_merge(
            $this->_client_rows(),
            $this->_lead_rows(),
            $this->_sale_rows($pending_days),
            $this->_commission_rows()
        );

        usort($rows, function ($a, $b) {
            $severity_order = ["alta" => 1, "media" => 2, "baixa" => 3];
            $severity_a = $severity_order[$a["severity"]] ?? 9;
            $severity_b = $severity_order[$b["severity"]] ?? 9;
            if ($severity_a === $severity_b) {
                return strcmp($a["client_name"], $b["client_name"]);
            }

            return $severity_a <=> $severity_b;
        });

        return $rows;
    }

    public function get_summary($options = [])
    {
        $summary = [
            "clients" => 0,
            "leads" => 0,
            "sales" => 0,
            "commissions" => 0
        ];

        foreach ($this->get_rows($options) as $row) {
            if ($row["type_key"] === "client") {
                $summary["clients"]++;
            } elseif ($row["type_key"] === "lead") {
                $summary["leads"]++;
            } elseif ($row["type_key"] === "sale") {
                $summary["sales"]++;
            } elseif ($row["type_key"] === "commission") {
                $summary["commissions"]++;
            }
        }

        return (object) $summary;
    }

    private function _client_rows()
    {
        $clients = $this->db->prefixTable("green_clients");
        $leads = $this->db->prefixTable("green_leads");
        $sales = $this->db->prefixTable("green_sales");
        $contact_phone_exists = $this->_contact_exists_sql("phone", $clients);
        $contact_email_exists = $this->_contact_exists_sql("email", $clients);

        $checks = [
            ["condition" => "$clients.document_number IS NULL OR $clients.document_number=''", "problem" => "sem CPF/CNPJ", "severity" => "alta", "action" => "Informar CPF/CNPJ do cliente"],
            ["condition" => "NOT $contact_phone_exists", "problem" => "sem telefone", "severity" => "media", "action" => "Cadastrar telefone principal"],
            ["condition" => "NOT $contact_email_exists", "problem" => "sem e-mail", "severity" => "baixa", "action" => "Cadastrar e-mail principal"],
            ["condition" => "$clients.document_number IS NOT NULL
                    AND $clients.document_number<>''
                    AND NOT (
                        ($clients.document_type='CPF' AND CHAR_LENGTH($clients.document_number)=11)
                        OR ($clients.document_type='CNPJ' AND CHAR_LENGTH($clients.document_number)=14)
                    )", "problem" => "documento invalido", "severity" => "alta", "action" => "Corrigir CPF/CNPJ e tipo de documento"]
        ];

        return $this->_checked_rows($checks, "client", "Cliente", ["SELECT $clients.id AS client_id,
                $clients.id AS record_id,
                CONCAT('Cliente #', $clients.id) AS record_label,
                $clients.name AS client_name,
                MIN($leads.id) AS lead_id,
                MIN($sales.id) AS sale_id
            FROM $clients
            LEFT JOIN $leads ON $leads.client_id=$clients.id AND $leads.deleted=0
            LEFT JOIN $sales ON $sales.client_id=$clients.id AND $sales.deleted=0
            WHERE $clients.deleted=0 AND ", " GROUP BY $clients.id"]);
    }

    private function _lead_rows()
    {
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");

        $checks = [
            ["condition" => "$leads.source_id IS NULL OR $leads.source_id=0", "problem" => "sem origem", "severity" => "media", "action" => "Definir origem do lead"],
            ["condition" => "$leads.status_id IS NULL OR $leads.status_id=0", "problem" => "sem status", "severity" => "alta", "action" => "Definir status comercial"],
            ["condition" => "$leads.temperature IS NULL OR $leads.temperature='' OR $leads.temperature='sem_classificacao'", "problem" => "sem temperatura", "severity" => "baixa", "action" => "Classificar temperatura"],
            ["condition" => "$leads.current_operator_id IS NULL OR $leads.current_operator_id=0", "problem" => "sem operadora atual", "severity" => "media", "action" => "Informar operadora atual"],
            ["condition" => "$leads.current_plan_name IS NULL OR $leads.current_plan_name=''", "problem" => "sem plano atual", "severity" => "media", "action" => "Informar plano atual"],
            ["condition" => "$leads.lives_qty IS NULL OR $leads.lives_qty<=0", "problem" => "sem quantidade de vidas", "severity" => "alta", "action" => "Informar quantidade de vidas"],
            ["condition" => "$leads.current_paid_value IS NULL OR $leads.current_paid_value<=0", "problem" => "sem valor pago", "severity" => "media", "action" => "Informar valor pago atual"],
            ["condition" => "$leads.renewal_month IS NULL OR $leads.renewal_month=0", "problem" => "sem mes de reajuste", "severity" => "baixa", "action" => "Informar mes de reajuste"],
            ["condition" => "$leads.next_followup_at IS NULL OR $leads.next_followup_at='0000-00-00 00:00:00'", "problem" => "sem proximo follow-up", "severity" => "alta", "action" => "Criar proximo follow-up"]
        ];

        return $this->_checked_rows($checks, "lead", "Lead", "SELECT $leads.id AS lead_id,
                $leads.id AS record_id,
                COALESCE($leads.lead_code, CONCAT('LEAD-', $leads.id)) AS record_label,
                $clients.id AS client_id,
                $clients.name AS client_name,
                NULL AS sale_id
            FROM $leads
            INNER JOIN $clients ON $clients.id=$leads.client_id AND $clients.deleted=0
            WHERE $leads.deleted=0 AND ");
    }

    private function _sale_rows($pending_days)
    {
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");
        $commissions = $this->db->prefixTable("green_commission_installments");

        $checks = [
            ["condition" => "$sales.implantation_date IS NULL OR $sales.implantation_date='0000-00-00'", "problem" => "sem data implantacao", "severity" => "media", "action" => "Informar data de implantacao"],
            ["condition" => "$sales.fidelity_until IS NULL OR $sales.fidelity_until='0000-00-00'", "problem" => "sem fidelidade", "severity" => "baixa", "action" => "Informar data de fidelidade"],
            ["condition" => "$sales.contract_number IS NULL OR $sales.contract_number=''", "problem" => "sem contrato", "severity" => "media", "action" => "Informar contrato/proposta"],
            ["condition" => "NOT EXISTS (SELECT 1 FROM $commissions WHERE $commissions.sale_id=$sales.id AND $commissions.deleted=0 AND $commissions.status NOT IN ('Cancelado','Estornado'))", "problem" => "sem comissao gerada", "severity" => "alta", "action" => "Gerar parcelas de comissao"],
            ["condition" => "$sales.implantation_status IN ('nao_iniciada','pendente','em_andamento') AND $sales.sale_date < DATE_SUB(CURDATE(), INTERVAL " . (int) $pending_days . " DAY)", "problem" => "implantacao pendente ha mais de " . (int) $pending_days . " dias", "severity" => "alta", "action" => "Atualizar checklist de implantacao"]
        ];

        return $this->_checked_rows($checks, "sale", "Venda", "SELECT $sales.id AS sale_id,
                $sales.id AS record_id,
                COALESCE($sales.sale_code, CONCAT('SALE-', $sales.id)) AS record_label,
                $clients.id AS client_id,
                $clients.name AS client_name,
                $sales.lead_id AS lead_id
            FROM $sales
            INNER JOIN $clients ON $clients.id=$sales.client_id AND $clients.deleted=0
            WHERE $sales.deleted=0 AND $sales.status NOT IN ('Cancelada','Estornada') AND ");
    }

    private function _commission_rows()
    {
        $commissions = $this->db->prefixTable("green_commission_installments");
        $sales = $this->db->prefixTable("green_sales");
        $clients = $this->db->prefixTable("green_clients");

        $checks = [
            ["condition" => "$commissions.expected_amount=0", "problem" => "expected_amount = 0", "severity" => "alta", "action" => "Revisar valor esperado da comissao"],
            ["condition" => "$commissions.status='Parcial'", "problem" => "recebida parcialmente", "severity" => "media", "action" => "Conferir baixa parcial"],
            ["condition" => "$commissions.due_date < CURDATE() AND $commissions.status NOT IN ('Recebido','Cancelado','Estornado')", "problem" => "vencida e nao recebida", "severity" => "alta", "action" => "Cobrar ou dar baixa da comissao"],
            ["condition" => "$commissions.received_amount > 0 AND ROUND(COALESCE($commissions.received_amount,0) - COALESCE($commissions.expected_amount,0), 2) <> 0", "problem" => "divergencia entre recebido e esperado", "severity" => "alta", "action" => "Conciliar diferenca da comissao"]
        ];

        return $this->_checked_rows($checks, "commission", "Comissao", "SELECT $commissions.id AS record_id,
                CONCAT('Comissao #', $commissions.id) AS record_label,
                $clients.id AS client_id,
                $clients.name AS client_name,
                $sales.lead_id AS lead_id,
                $sales.id AS sale_id
            FROM $commissions
            INNER JOIN $sales ON $sales.id=$commissions.sale_id AND $sales.deleted=0
            INNER JOIN $clients ON $clients.id=$sales.client_id AND $clients.deleted=0
            WHERE $commissions.deleted=0 AND ");
    }

    private function _checked_rows($checks, $type_key, $type_label, $base_sql)
    {
        $rows = [];
        $suffix = "";
        if (is_array($base_sql)) {
            $suffix = $base_sql[1] ?? "";
            $base_sql = $base_sql[0];
        }

        foreach ($checks as $check) {
            $sql = $base_sql . "(" . $check["condition"] . ")" . $suffix;
            foreach ($this->db->query($sql)->getResult() as $row) {
                $row->problem = $check["problem"];
                $row->severity = $check["severity"];
                $row->suggested_action = $check["action"];
                $rows[] = $row;
            }
        }

        return $this->_map_rows($rows, $type_key, $type_label);
    }

    private function _map_rows($rows, $type_key, $type_label)
    {
        $mapped = [];
        foreach ($rows as $row) {
            $record_id = (int) ($row->record_id ?? ($row->client_id ?? 0));
            $mapped[] = [
                "type_key" => $type_key,
                "type" => $type_label,
                "record_id" => $record_id,
                "record_label" => $row->record_label ?? ($type_label . " #" . $record_id),
                "client_id" => (int) ($row->client_id ?? 0),
                "client_name" => $row->client_name ?? "-",
                "lead_id" => (int) ($row->lead_id ?? 0),
                "sale_id" => (int) ($row->sale_id ?? 0),
                "problem" => $row->problem,
                "severity" => $row->severity,
                "suggested_action" => $row->suggested_action
            ];
        }

        return $mapped;
    }

    private function _contact_exists_sql($type, $clients_table)
    {
        $contacts = $this->db->prefixTable("green_client_contacts");
        if ($type === "email") {
            return "EXISTS (SELECT 1 FROM $contacts WHERE $contacts.client_id=$clients_table.id AND $contacts.deleted=0 AND $contacts.email IS NOT NULL AND $contacts.email<>'')";
        }

        return "EXISTS (SELECT 1 FROM $contacts WHERE $contacts.client_id=$clients_table.id AND $contacts.deleted=0 AND (($contacts.phone_normalized IS NOT NULL AND $contacts.phone_normalized<>'') OR ($contacts.phone_original IS NOT NULL AND $contacts.phone_original<>'')))";
    }
}
