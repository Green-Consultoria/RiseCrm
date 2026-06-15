<?php

namespace Green_crm\Models;

class Green_sale_implantation_checklist_model extends Green_base_model
{
    protected $table = "green_sale_implantation_checklist";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function default_items()
    {
        return [
            "documentos_recebidos" => "Documentos recebidos",
            "proposta_preenchida" => "Proposta preenchida",
            "proposta_assinada" => "Proposta assinada",
            "boleto_enviado" => "Boleto enviado",
            "pagamento_confirmado" => "Pagamento confirmado",
            "protocolo_operadora" => "Protocolo enviado à operadora",
            "implantacao_solicitada" => "Implantação solicitada",
            "implantacao_concluida" => "Implantação concluída",
            "carteirinha_enviada" => "Carteirinha enviada ao cliente"
        ];
    }

    public function default_items_count()
    {
        return count($this->default_items());
    }

    public function get_details($options = [])
    {
        $items = $this->db->prefixTable("green_sale_implantation_checklist");
        $users = $this->db->prefixTable("users");
        $where = "";

        foreach (["id", "sale_id"] as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value) {
                $where .= " AND $items.$field=" . (int) $value;
            }
        }

        $item_key = $this->_get_clean_value($options, "item_key");
        if ($item_key) {
            $where .= " AND $items.item_key=" . $this->db->escape($item_key);
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $items.status=" . $this->db->escape($status);
        }

        return $this->db->query("SELECT $items.*,
                CONCAT($users.first_name, ' ', $users.last_name) AS completed_by_name
            FROM $items
            LEFT JOIN $users ON $users.id=$items.completed_by
            WHERE $items.deleted=0 $where
            ORDER BY FIELD($items.item_key,
                'documentos_recebidos',
                'proposta_preenchida',
                'proposta_assinada',
                'boleto_enviado',
                'pagamento_confirmado',
                'protocolo_operadora',
                'implantacao_solicitada',
                'implantacao_concluida',
                'carteirinha_enviada'
            ), $items.id ASC");
    }

    public function ensure_default_items($sale_id, $user_id = 0)
    {
        $sale_id = (int) $sale_id;
        if (!$sale_id) {
            return 0;
        }

        $table = $this->_green_table();
        $existing_rows = $this->db->query("SELECT item_key FROM $table WHERE sale_id=$sale_id AND deleted=0")->getResult();
        $existing = [];
        foreach ($existing_rows as $row) {
            $existing[$row->item_key] = true;
        }

        $now = date("Y-m-d H:i:s");
        $inserted = 0;
        foreach ($this->default_items() as $key => $title) {
            if (!empty($existing[$key])) {
                continue;
            }

            $data = [
                "sale_id" => $sale_id,
                "item_key" => $key,
                "title" => $title,
                "status" => "pendente",
                "created_by" => (int) $user_id ?: null,
                "updated_by" => (int) $user_id ?: null,
                "created_at" => $now,
                "updated_at" => $now,
                "deleted" => 0
            ];
            $this->ci_save($data);
            $inserted++;
        }

        return $inserted;
    }

    public function update_item($id, $status, $notes, $user_id = 0)
    {
        $id = (int) $id;
        $status = $this->valid_status($status);
        if (!$id || !$status) {
            return false;
        }

        $now = date("Y-m-d H:i:s");
        $data = [
            "status" => $status,
            "notes" => trim((string) $notes) ?: null,
            "updated_by" => (int) $user_id ?: null,
            "updated_at" => $now
        ];

        if ($status === "concluido") {
            $data["completed_at"] = $now;
            $data["completed_by"] = (int) $user_id ?: null;
        } else {
            $data["completed_at"] = null;
            $data["completed_by"] = null;
        }

        return $this->ci_save($data, $id);
    }

    public function get_progress($sale_id)
    {
        $sale_id = (int) $sale_id;
        $table = $this->_green_table();
        $row = $this->db->query("SELECT
                COUNT(*) AS total_items,
                SUM(CASE WHEN status='concluido' THEN 1 ELSE 0 END) AS completed_items,
                SUM(CASE WHEN status='pendente' THEN 1 ELSE 0 END) AS pending_items
            FROM $table
            WHERE deleted=0 AND sale_id=$sale_id")->getRow();

        $total = (int) ($row->total_items ?? 0);
        if (!$total) {
            $total = $this->default_items_count();
        }

        return (object) [
            "total_items" => $total,
            "completed_items" => (int) ($row->completed_items ?? 0),
            "pending_items" => (int) ($row->pending_items ?? $total)
        ];
    }

    public function valid_status($status)
    {
        return in_array($status, ["pendente", "concluido", "nao_aplica"], true) ? $status : null;
    }
}
