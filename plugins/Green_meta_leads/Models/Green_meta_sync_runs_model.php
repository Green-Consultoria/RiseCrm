<?php

namespace Green_meta_leads\Models;

use App\Models\Crud_model;

class Green_meta_sync_runs_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "green_meta_sync_runs";
        parent::__construct($this->table);
    }

    public function recent($limit = 10)
    {
        $table = $this->db->prefixTable("green_meta_sync_runs");
        return $this->db->query("SELECT *
            FROM $table
            ORDER BY started_at DESC, id DESC
            LIMIT " . (int) $limit)->getResult();
    }
}
