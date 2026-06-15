<?php

namespace Green_crm\Models;

class Green_clients_model extends Green_base_model
{
    protected $table = "green_clients";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $clients = $this->db->prefixTable("green_clients");
        $contacts = $this->db->prefixTable("green_client_contacts");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $clients.id=" . (int) $id;
        }

        $document_number = $this->_get_clean_value($options, "document_number");
        if ($document_number) {
            $where .= " AND $clients.document_number=" . $this->db->escape($document_number);
        }

        $name = $this->_get_clean_value($options, "name");
        if ($name) {
            $where .= " AND $clients.name LIKE '%" . $this->db->escapeLikeString($name) . "%' ESCAPE '!'";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $clients.status=" . $this->db->escape($status);
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $search_digits = preg_replace("/\D+/", "", (string) $search);
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($clients.name LIKE '%$search%' ESCAPE '!' OR $contacts.email LIKE '%$search%' ESCAPE '!'";
            if ($search_digits) {
                $search_digits = $this->db->escapeLikeString($search_digits);
                $where .= " OR $clients.document_number LIKE '%$search_digits%' ESCAPE '!' OR $contacts.phone_normalized LIKE '%$search_digits%' ESCAPE '!'";
            } else {
                $where .= " OR $clients.document_number LIKE '%$search%' ESCAPE '!' OR $contacts.phone_normalized LIKE '%$search%' ESCAPE '!'";
            }
            $where .= ")";
        }

        $sql = "SELECT $clients.*, $contacts.phone_normalized, $contacts.phone_original, $contacts.email
            FROM $clients
            LEFT JOIN $contacts ON $contacts.client_id=$clients.id AND $contacts.deleted=0 AND $contacts.is_primary=1
            WHERE $clients.deleted=0 $where
            GROUP BY $clients.id
            ORDER BY $clients.name ASC";

        return $this->db->query($sql);
    }

    public function find_by_document($document_type, $document_number)
    {
        if (!$document_number) {
            return null;
        }

        return $this->get_one_where(["document_type" => $document_type, "document_number" => $document_number, "deleted" => 0]);
    }

    public function find_by_phone($normalized_phone)
    {
        if (!$normalized_phone) {
            return null;
        }

        $clients = $this->db->prefixTable("green_clients");
        $contacts = $this->db->prefixTable("green_client_contacts");
        $sql = "SELECT $clients.* FROM $clients
            INNER JOIN $contacts ON $contacts.client_id=$clients.id
            WHERE $clients.deleted=0 AND $contacts.deleted=0 AND $contacts.phone_normalized=" . $this->db->escape($normalized_phone) . "
            ORDER BY $contacts.is_primary DESC, $contacts.id ASC LIMIT 1";

        return $this->db->query($sql)->getRow();
    }

    public function find_by_email($email)
    {
        if (!$email) {
            return null;
        }

        $clients = $this->db->prefixTable("green_clients");
        $contacts = $this->db->prefixTable("green_client_contacts");
        $sql = "SELECT $clients.* FROM $clients
            INNER JOIN $contacts ON $contacts.client_id=$clients.id
            WHERE $clients.deleted=0 AND $contacts.deleted=0 AND $contacts.email=" . $this->db->escape($email) . "
            ORDER BY $contacts.is_primary DESC, $contacts.id ASC LIMIT 1";

        return $this->db->query($sql)->getRow();
    }

    public function save_client($data, $id = 0)
    {
        return $this->ci_save($data, $id);
    }
}
