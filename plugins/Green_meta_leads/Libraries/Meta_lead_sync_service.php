<?php

namespace Green_meta_leads\Libraries;

/**
 * In-app Facebook Lead Ads sync.
 *
 * Pulls leads from the Meta Graph API (or a dev mock payload) and pushes each
 * one straight into the Green CRM pipeline: green_clients (+ green_client_contacts)
 * and green_leads, tagged with the "Facebook Lead Ads" source. Idempotent by
 * facebook_lead_id (raw table) and by (source_id, source_lead_id) on green_leads.
 */
class Meta_lead_sync_service
{
    const SOURCE_TITLE = "Facebook Lead Ads";

    protected $db;
    protected $user_id;

    protected $Settings_model;
    protected $Green_meta_raw_leads_model;
    protected $Green_meta_sync_runs_model;
    protected $Green_clients_model;
    protected $Green_client_contacts_model;
    protected $Green_leads_model;
    protected $Green_lead_statuses_model;
    protected $Green_interactions_model;

    protected $token = "";
    protected $graph_version = "v23.0";
    protected $form_ids = [];
    protected $since_days = 30;
    protected $limit = 100;
    protected $max_pages = 100;

    protected $source_id_cache = null;

    public function __construct($user_id = 0)
    {
        helper("green");

        $this->user_id = (int) $user_id;
        $this->db = db_connect();

        $this->Settings_model = model("App\Models\Settings_model");
        $this->Green_meta_raw_leads_model = model("Green_meta_leads\Models\Green_meta_raw_leads_model");
        $this->Green_meta_sync_runs_model = model("Green_meta_leads\Models\Green_meta_sync_runs_model");
        $this->Green_clients_model = model("Green_crm\Models\Green_clients_model");
        $this->Green_client_contacts_model = model("Green_crm\Models\Green_client_contacts_model");
        $this->Green_leads_model = model("Green_crm\Models\Green_leads_model");
        $this->Green_lead_statuses_model = model("Green_crm\Models\Green_lead_statuses_model");
        $this->Green_interactions_model = model("Green_crm\Models\Green_interactions_model");

        //read config directly from DB so a token saved seconds ago is visible (global get_setting() caches at bootstrap)
        $this->token = trim((string) $this->Settings_model->get_setting("green_meta_page_access_token"));
        $this->graph_version = trim((string) $this->Settings_model->get_setting("green_meta_graph_version")) ?: "v23.0";
        $form_ids = trim((string) $this->Settings_model->get_setting("green_meta_form_ids"));
        $this->form_ids = array_values(array_filter(array_map("trim", explode(",", $form_ids))));
        $this->since_days = (int) ($this->Settings_model->get_setting("green_meta_since_days") ?: 30);
    }

    public function run()
    {
        $run_data = [
            "started_at" => date("Y-m-d H:i:s"),
            "status" => "running",
            "message" => "Sincronização iniciada."
        ];
        $run_id = (int) $this->Green_meta_sync_runs_model->ci_save($run_data);

        $totals = ["processed" => 0, "created" => 0, "updated" => 0, "duplicate_updates" => 0, "errors" => 0];
        $status = "success";
        $message = "";

        try {
            if (!$this->green_tables_ready()) {
                throw new \RuntimeException("Tabelas do Green CRM não encontradas. Ative o plugin Green CRM antes de sincronizar.");
            }

            foreach ($this->fetch_leads() as $lead) {
                try {
                    $result = $this->process_lead($lead);
                    $totals["processed"]++;
                    if ($result === "created") {
                        $totals["created"]++;
                    } elseif ($result === "updated") {
                        $totals["updated"]++;
                    } else {
                        $totals["duplicate_updates"]++;
                    }
                } catch (\Throwable $e) {
                    $totals["errors"]++;
                    log_message("error", "Green Meta Leads - lead falhou: " . $e->getMessage());
                }
            }

            $status = $totals["errors"] ? "partial" : "success";
            $message = sprintf(
                "processed=%d created=%d updated=%d duplicate_updates=%d errors=%d",
                $totals["processed"],
                $totals["created"],
                $totals["updated"],
                $totals["duplicate_updates"],
                $totals["errors"]
            );
        } catch (\Throwable $e) {
            $status = "failed";
            $message = $e->getMessage();
            $totals["errors"]++;
        }

        $close_data = [
            "finished_at" => date("Y-m-d H:i:s"),
            "status" => $status,
            "processed" => $totals["processed"],
            "created" => $totals["created"],
            "updated" => $totals["updated"],
            "duplicate_updates" => $totals["duplicate_updates"],
            "errors" => $totals["errors"],
            "message" => $message
        ];
        if ($run_id) {
            $this->Green_meta_sync_runs_model->ci_save($close_data, $run_id);
        }

        return array_merge($totals, [
            "status" => $status,
            "message" => $message,
            "run_id" => $run_id,
            "success" => $status !== "failed"
        ]);
    }

    public function reprocess_raw($raw_id)
    {
        $raw = $this->Green_meta_raw_leads_model->get_one_where(["id" => (int) $raw_id, "deleted" => 0]);
        if (!$raw || empty($raw->id)) {
            return ["success" => false, "message" => "Lead bruto não encontrado."];
        }

        if (!$this->green_tables_ready()) {
            return ["success" => false, "message" => "Tabelas do Green CRM não encontradas. Ative o plugin Green CRM."];
        }

        $payload = json_decode((string) $raw->raw_payload, true);
        if (!is_array($payload)) {
            return ["success" => false, "message" => "Payload bruto inválido para reprocessamento."];
        }

        try {
            $result = $this->process_lead($payload);
            return ["success" => true, "message" => "Lead reprocessado (" . $result . ").", "result" => $result];
        } catch (\Throwable $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    protected function fetch_leads()
    {
        $mock = trim((string) $this->Settings_model->get_setting("green_meta_mock_payload"));
        if ($mock !== "") {
            return $this->load_mock_leads($mock);
        }

        if ($this->token === "") {
            throw new \RuntimeException("Token de acesso do Facebook (Page Access Token) não configurado.");
        }
        if (!count($this->form_ids)) {
            throw new \RuntimeException("Nenhum Form ID configurado.");
        }

        $all = [];
        foreach ($this->form_ids as $form_id) {
            $params = [
                "access_token" => $this->token,
                "limit" => $this->limit,
                "fields" => "id,created_time,field_data,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,form_id,platform",
                "since" => time() - ($this->since_days * 86400)
            ];

            $url = "https://graph.facebook.com/" . rawurlencode($this->graph_version) . "/" . rawurlencode($form_id) . "/leads?" . http_build_query($params);
            $page = 0;
            while ($url && $page < $this->max_pages) {
                $page++;
                $response = $this->graph_request($url);
                foreach (($response["data"] ?? []) as $lead) {
                    if (empty($lead["form_id"])) {
                        $lead["form_id"] = $form_id;
                    }
                    $all[] = $lead;
                }
                $url = $response["paging"]["next"] ?? "";
            }
        }

        return $all;
    }

    protected function load_mock_leads($mock)
    {
        $json = null;
        $trimmed = ltrim($mock);

        if ($trimmed !== "" && ($trimmed[0] === "[" || $trimmed[0] === "{")) {
            $json = json_decode($mock, true);
        } elseif (is_file($mock)) {
            $json = json_decode((string) file_get_contents($mock), true);
        } else {
            $path = __DIR__ . "/../Database/" . ltrim($mock, "/\\");
            if (is_file($path)) {
                $json = json_decode((string) file_get_contents($path), true);
            }
        }

        if (!is_array($json)) {
            throw new \RuntimeException("green_meta_mock_payload inválido (JSON ou caminho de arquivo).");
        }

        if (isset($json["data"]) && is_array($json["data"])) {
            return $json["data"];
        }

        return $json;
    }

    protected function graph_request($url)
    {
        $client = service("curlrequest", ["timeout" => 60, "connect_timeout" => 15, "http_errors" => false]);
        $response = $client->get($url);
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($body === "") {
            throw new \RuntimeException("Graph API sem resposta (HTTP " . $status . ").");
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new \RuntimeException("Graph API retornou JSON inválido.");
        }

        if ($status >= 400 || isset($json["error"])) {
            $message = $json["error"]["message"] ?? ("HTTP " . $status);
            throw new \RuntimeException("Graph API: " . $message);
        }

        return $json;
    }

    protected function process_lead(array $lead)
    {
        $mapped = $this->field_map($lead);
        $fb_id = $mapped["facebook_lead_id"];
        if ($fb_id === "") {
            throw new \RuntimeException("Lead sem ID do Facebook.");
        }

        //idempotency: if this raw lead was already pushed to a green lead, skip the heavy work
        $existing_raw = $this->Green_meta_raw_leads_model->get_one_where(["facebook_lead_id" => $fb_id, "deleted" => 0]);
        $raw_id = !empty($existing_raw->id) ? (int) $existing_raw->id : 0;
        if ($raw_id && !empty($existing_raw->green_lead_id)) {
            return "duplicate";
        }

        $this->db->transBegin();
        try {
            $source_id = $this->facebook_source_id();
            $status_id = $this->default_status_id();
            $now = date("Y-m-d H:i:s");

            //dedup the client by phone, then email
            $client = null;
            if (!empty($mapped["phone_normalized"])) {
                $client = $this->Green_clients_model->find_by_phone($mapped["phone_normalized"]);
            }
            if (!$client && !empty($mapped["email"])) {
                $client = $this->Green_clients_model->find_by_email($mapped["email"]);
            }

            $client_id = (int) ($client->id ?? 0);
            $client_data = [
                "client_type" => "NAO_INFORMADO",
                "name" => $mapped["full_name"] ?: "Lead Facebook",
                "status" => "Ativo",
                "updated_by" => $this->user_id,
                "updated_at" => $now,
                "deleted" => 0
            ];
            if (!$client_id) {
                $client_data["created_by"] = $this->user_id;
                $client_data["created_at"] = $now;
            }
            $client_id = (int) $this->Green_clients_model->save_client($client_data, $client_id);

            //primary contact
            if (!empty($mapped["phone_normalized"]) || !empty($mapped["email"])) {
                $contact = $this->Green_client_contacts_model->get_details(["client_id" => $client_id])->getRow();
                $contact_data = [
                    "client_id" => $client_id,
                    "name" => $mapped["full_name"] ?: null,
                    "phone_original" => $mapped["phone_original"] ?: null,
                    "phone_normalized" => $mapped["phone_normalized"] ?: null,
                    "email" => $mapped["email"] ?: null,
                    "is_primary" => 1,
                    "updated_by" => $this->user_id,
                    "updated_at" => $now,
                    "deleted" => 0
                ];
                if (empty($contact->id)) {
                    $contact_data["created_by"] = $this->user_id;
                    $contact_data["created_at"] = $now;
                }
                $this->Green_client_contacts_model->ci_save($contact_data, $contact->id ?? 0);
            }

            //dedup the lead on (source_id, source_lead_id)
            $leads_table = $this->db->prefixTable("green_leads");
            $existing_lead = $this->db->query("SELECT id FROM `" . $leads_table . "`
                WHERE deleted=0 AND source_id=" . (int) $source_id . " AND source_lead_id=" . $this->db->escape($fb_id) . " LIMIT 1")->getRow();
            $lead_id = (int) ($existing_lead->id ?? 0);
            $lead_existed = (bool) $lead_id;

            // Rastreabilidade de campanha/conjunto/anuncio/formulario gravada na linha do lead
            $lead_data = [
                "client_id" => $client_id,
                "source_id" => $source_id,
                "source_lead_id" => $fb_id,
                "campaign_id" => $mapped["facebook_campaign_id"] ?: null,
                "adset_id" => $mapped["facebook_adset_id"] ?: null,
                "ad_id" => $mapped["facebook_ad_id"] ?: null,
                "meta_form_id" => $mapped["facebook_form_id"] ?: null,
                "region" => $mapped["region"] ?: null,
                "notes" => $this->build_notes($mapped),
                "updated_by" => $this->user_id,
                "updated_at" => $now,
                "deleted" => 0
            ];
            // Status/temperatura/owner so no primeiro cadastro: nao sobrescreve o trabalho manual em leads ja existentes
            if (!$lead_existed) {
                $lead_data["status_id"] = $status_id ?: null;
                $lead_data["temperature"] = "quente";
                $lead_data["owner_id"] = $this->user_id ?: null;
                $lead_data["temperature_changed_at"] = $now;
                $lead_data["status_changed_at"] = $now;
                $lead_data["created_by"] = $this->user_id;
                $lead_data["created_at"] = $now;
            }
            $lead_id = (int) $this->Green_leads_model->ci_save($lead_data, $lead_id);

            if (!$lead_existed) {
                $lead_code_data = ["lead_code" => sprintf("GREEN-%06d", $lead_id)];
                $this->Green_leads_model->ci_save($lead_code_data, $lead_id);
                $this->Green_interactions_model->add_system_interaction(
                    $lead_id,
                    "Lead capturado via Facebook Lead Ads",
                    $this->build_notes($mapped),
                    $this->user_id
                );
                if (function_exists("green_audit")) {
                    green_audit("lead", $lead_id, "created_meta", null, ["source" => "Facebook Lead Ads", "campaign_id" => $mapped["facebook_campaign_id"]], $this->user_id);
                }
            }

            $process_status = $lead_existed ? "linked" : "created";
            $raw_data = $this->raw_row_data($mapped, $client_id, $lead_id, $process_status, null);
            $this->Green_meta_raw_leads_model->ci_save($raw_data, $raw_id);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \RuntimeException("Falha na transação ao gravar o lead.");
            }

            $this->db->transCommit();
            return $lead_existed ? "updated" : "created";
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->record_raw_error($fb_id, $mapped, $e->getMessage());
            throw $e;
        }
    }

    protected function record_raw_error($fb_id, array $mapped, $message)
    {
        try {
            $existing_raw = $this->Green_meta_raw_leads_model->get_one_where(["facebook_lead_id" => $fb_id, "deleted" => 0]);
            $raw_id = !empty($existing_raw->id) ? (int) $existing_raw->id : 0;
            $raw_data = $this->raw_row_data($mapped, null, null, "error", $message);
            $this->Green_meta_raw_leads_model->ci_save($raw_data, $raw_id);
        } catch (\Throwable $ignore) {
            //best effort only
        }
    }

    protected function raw_row_data(array $mapped, $client_id, $lead_id, $status, $message)
    {
        $data = [
            "facebook_lead_id" => $mapped["facebook_lead_id"],
            "facebook_page_id" => $mapped["facebook_page_id"] ?: null,
            "facebook_form_id" => $mapped["facebook_form_id"] ?: null,
            "facebook_ad_id" => $mapped["facebook_ad_id"] ?: null,
            "facebook_campaign_id" => $mapped["facebook_campaign_id"] ?: null,
            "facebook_adset_id" => $mapped["facebook_adset_id"] ?: null,
            "form_name" => $mapped["form_name"] ?: null,
            "campaign_name" => $mapped["campaign_name"] ?: null,
            "ad_name" => $mapped["ad_name"] ?: null,
            "full_name" => $mapped["full_name"] ?: null,
            "phone_original" => $mapped["phone_original"] ?: null,
            "phone_normalized" => $mapped["phone_normalized"] ?: null,
            "email" => $mapped["email"] ?: null,
            "region" => $mapped["region"] ?: null,
            "extra_fields" => $mapped["extra_fields"] ?: null,
            "raw_payload" => $mapped["raw_payload"] ?: null,
            "facebook_created_time" => $mapped["facebook_created_time"] ?: null,
            "process_status" => $status,
            "process_message" => $message,
            "deleted" => 0
        ];

        if ($client_id !== null) {
            $data["green_client_id"] = (int) $client_id;
        }
        if ($lead_id !== null) {
            $data["green_lead_id"] = (int) $lead_id;
        }

        return $data;
    }

    protected function field_map(array $lead)
    {
        $fields = [];
        foreach (($lead["field_data"] ?? []) as $field) {
            $name = (string) ($field["name"] ?? "");
            if ($name === "") {
                continue;
            }
            $values = $field["values"] ?? [];
            $fields[$name] = is_array($values) ? implode(", ", $values) : (string) $values;
        }

        $consumed = [];
        $pick = function (array $names) use ($fields, &$consumed) {
            foreach ($names as $n) {
                foreach ($fields as $key => $value) {
                    if (strtolower($key) === strtolower($n)) {
                        $consumed[strtolower($key)] = true;
                        return (string) $value;
                    }
                }
            }
            return "";
        };

        $full_name = $pick(["full_name", "nome_completo", "nome", "nome_do_responsavel", "responsavel_nome", "name"]);
        $phone_original = $pick(["phone_number", "telefone", "telefone_whatsapp", "whatsapp", "celular", "phone"]);
        $email_raw = $pick(["email", "e-mail"]);
        $city = $pick(["cidade", "city"]);
        $neighborhood = $pick(["bairro", "neighborhood"]);

        $extra = [];
        foreach ($fields as $key => $value) {
            if (empty($consumed[strtolower($key)])) {
                $extra[$key] = $value;
            }
        }

        $region = $this->first_non_empty([$city, $neighborhood]);
        if ($city && $neighborhood) {
            $region = $city . " - " . $neighborhood;
        }

        $created = !empty($lead["created_time"]) ? date("Y-m-d H:i:s", strtotime((string) $lead["created_time"])) : null;

        return [
            "facebook_lead_id" => $this->strip_integration_prefix((string) ($lead["id"] ?? "")),
            "facebook_page_id" => (string) ($lead["page_id"] ?? ""),
            "facebook_form_id" => $this->strip_integration_prefix((string) ($lead["form_id"] ?? "")),
            "facebook_ad_id" => $this->strip_integration_prefix((string) ($lead["ad_id"] ?? "")),
            "facebook_campaign_id" => $this->strip_integration_prefix((string) ($lead["campaign_id"] ?? "")),
            "facebook_adset_id" => $this->strip_integration_prefix((string) ($lead["adset_id"] ?? "")),
            "form_name" => (string) ($lead["form_name"] ?? ""),
            "campaign_name" => (string) ($lead["campaign_name"] ?? ""),
            "ad_name" => (string) ($lead["ad_name"] ?? ""),
            "full_name" => trim($full_name),
            "phone_original" => trim($phone_original),
            "phone_normalized" => green_normalize_phone($phone_original),
            "email" => green_normalize_email($email_raw),
            "region" => $region ?: null,
            "extra_fields" => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            "raw_payload" => json_encode($lead, JSON_UNESCAPED_UNICODE),
            "facebook_created_time" => $created
        ];
    }

    protected function build_notes(array $mapped)
    {
        $parts = ["Origem: Facebook Lead Ads"];
        if (!empty($mapped["campaign_name"])) {
            $parts[] = "Campanha: " . $mapped["campaign_name"];
        }
        if (!empty($mapped["form_name"])) {
            $parts[] = "Formulário: " . $mapped["form_name"];
        }
        if (!empty($mapped["ad_name"])) {
            $parts[] = "Anúncio: " . $mapped["ad_name"];
        }
        if (!empty($mapped["facebook_lead_id"])) {
            $parts[] = "Lead ID: " . $mapped["facebook_lead_id"];
        }

        return implode(" | ", $parts);
    }

    protected function facebook_source_id()
    {
        if ($this->source_id_cache !== null) {
            return $this->source_id_cache;
        }

        $sources = $this->db->prefixTable("green_sources");
        $row = $this->db->query("SELECT id FROM `" . $sources . "`
            WHERE deleted=0 AND title=" . $this->db->escape(self::SOURCE_TITLE) . " LIMIT 1")->getRow();

        if ($row && !empty($row->id)) {
            return $this->source_id_cache = (int) $row->id;
        }

        $this->db->table($sources)->insert([
            "title" => self::SOURCE_TITLE,
            "created_at" => date("Y-m-d H:i:s"),
            "deleted" => 0
        ]);

        return $this->source_id_cache = (int) $this->db->insertID();
    }

    protected function default_status_id()
    {
        $id = $this->Green_lead_statuses_model->get_id_by_title("Novo");
        if ($id) {
            return (int) $id;
        }

        $statuses = $this->db->prefixTable("green_lead_statuses");
        $row = $this->db->query("SELECT id FROM `" . $statuses . "`
            WHERE deleted=0 AND is_final=0 ORDER BY sort ASC, id ASC LIMIT 1")->getRow();

        return $row && !empty($row->id) ? (int) $row->id : null;
    }

    protected function green_tables_ready()
    {
        foreach (["green_clients", "green_client_contacts", "green_leads", "green_sources"] as $table) {
            if (!$this->db->tableExists($this->db->prefixTable($table))) {
                return false;
            }
        }

        return true;
    }

    protected function first_non_empty(array $values, $default = "")
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== "") {
                return $value;
            }
        }

        return $default;
    }

    protected function strip_integration_prefix($value)
    {
        return preg_replace("/^[a-z]+:/i", "", trim((string) $value));
    }
}
