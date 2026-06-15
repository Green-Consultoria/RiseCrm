<?php

namespace Green_crm\Models;

class Green_ads_model extends Green_base_model
{
    protected $table = "green_ads";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = [])
    {
        $ads = $this->db->prefixTable("green_ads");
        $sets = $this->db->prefixTable("green_ad_sets");
        $campaigns = $this->db->prefixTable("green_ad_campaigns");
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ads.id=" . (int) $id;
        }

        $adset_id = (int) $this->_get_clean_value($options, "adset_id");
        if ($adset_id) {
            $where .= " AND $ads.adset_id=$adset_id";
        }

        $campaign_id = (int) $this->_get_clean_value($options, "campaign_id");
        if ($campaign_id) {
            $where .= " AND $ads.campaign_id=$campaign_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $ads.status=" . $this->db->escape($status);
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $needle = $this->db->escapeLikeString($search);
            $where .= " AND ($ads.name LIKE '%$needle%' ESCAPE '!' OR $ads.external_id LIKE '%$needle%' ESCAPE '!')";
        }

        return $this->db->query("SELECT $ads.*, $sets.name AS adset_name, $campaigns.name AS campaign_name
            FROM $ads
            LEFT JOIN $sets ON $sets.id=$ads.adset_id AND $sets.deleted=0
            LEFT JOIN $campaigns ON $campaigns.id=$ads.campaign_id AND $campaigns.deleted=0
            WHERE $ads.deleted=0 $where
            ORDER BY $ads.name ASC, $ads.id DESC");
    }

    /**
     * ROI por venda: vincula vendas -> lead -> campanha/anuncio (external_id gravado no lead)
     * e soma a comissao prevista por venda. Estrutura preparada para a proxima etapa.
     */
    public function get_roi_by_sale()
    {
        $sales = $this->db->prefixTable("green_sales");
        $leads = $this->db->prefixTable("green_leads");
        $clients = $this->db->prefixTable("green_clients");
        $operators = $this->db->prefixTable("green_operators");
        $commissions = $this->db->prefixTable("green_commission_installments");

        return $this->db->query("SELECT
                $sales.id AS sale_id,
                $sales.sale_code,
                $sales.sale_date,
                $sales.sale_value,
                $clients.name AS client_name,
                $operators.name AS operator_name,
                $sales.plan_name,
                $leads.campaign_id,
                $leads.adset_id,
                $leads.ad_id,
                COALESCE(SUM(CASE WHEN $commissions.deleted=0 THEN $commissions.expected_amount ELSE 0 END), 0) AS expected_commission
            FROM $sales
            LEFT JOIN $leads ON $leads.id=$sales.lead_id AND $leads.deleted=0
            INNER JOIN $clients ON $clients.id=$sales.client_id AND $clients.deleted=0
            LEFT JOIN $operators ON $operators.id=$sales.operator_id AND $operators.deleted=0
            LEFT JOIN $commissions ON $commissions.sale_id=$sales.id
            WHERE $sales.deleted=0 AND $sales.status NOT IN ('Cancelada','Estornada')
                AND ($leads.campaign_id IS NOT NULL OR $leads.ad_id IS NOT NULL)
            GROUP BY $sales.id
            ORDER BY $sales.sale_date DESC, $sales.id DESC");
    }
}
