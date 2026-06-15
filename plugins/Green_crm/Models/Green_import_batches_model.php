<?php

namespace Green_crm\Models;

class Green_import_batches_model extends Green_base_model
{
    protected $table = "green_import_batches";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function create_batch($file_name, $source_type, $imported_by)
    {
        $data = ["file_name" => $file_name, "source_type" => $source_type, "imported_by" => $imported_by];
        return $this->ci_save($data);
    }

    public function update_totals($batch_id, $total, $success, $errors)
    {
        $data = ["total_rows" => $total, "success_rows" => $success, "error_rows" => $errors];
        return $this->ci_save($data, $batch_id);
    }
}
