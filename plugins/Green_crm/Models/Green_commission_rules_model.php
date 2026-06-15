<?php

namespace Green_crm\Models;

class Green_commission_rules_model extends Green_base_model
{
    protected $table = "green_commission_rules";

    public function __construct()
    {
        parent::__construct($this->table);
    }
}
