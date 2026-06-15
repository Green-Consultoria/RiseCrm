<?php

namespace Green_crm\Models;

class Green_operators_model extends Green_base_model
{
    protected $table = "green_operators";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function find_or_create($name)
    {
        helper("green");
        $normalized = green_ascii_key(green_normalize_operator($name));
        $existing = $this->get_one_where(["normalized_name" => $normalized, "deleted" => 0]);
        if ($existing && !empty($existing->id)) {
            return (int) $existing->id;
        }

        $data = ["name" => green_normalize_operator($name), "normalized_name" => $normalized, "deleted" => 0];
        return $this->ci_save($data);
    }
}
