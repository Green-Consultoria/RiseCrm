<?php

namespace Green_crm\Models;

class Green_client_contacts_model extends Green_base_model
{
    protected $table = "green_client_contacts";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $contacts = $this->db->prefixTable("green_client_contacts");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $contacts.id=" . (int) $id;
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $contacts.client_id=" . (int) $client_id;
        }

        $phone = $this->_get_clean_value($options, "phone_normalized");
        if ($phone) {
            $where .= " AND $contacts.phone_normalized=" . $this->db->escape($phone);
        }

        $email = $this->_get_clean_value($options, "email");
        if ($email) {
            $where .= " AND $contacts.email=" . $this->db->escape($email);
        }

        return $this->db->query("SELECT $contacts.* FROM $contacts WHERE $contacts.deleted=0 $where ORDER BY $contacts.is_primary DESC, $contacts.id ASC");
    }
}
