<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
Plugin Name: Green CRM
Description: CRM operacional para consultoria e corretagem de planos de saude.
Version: 0.1.0
Requires at least: 3.0
*/

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $dashboard_seen = false;
    $green_generic_names = [
        "Leads",
        "Funil",
        "Vendas",
        "Cotacoes",
        "Cotações",
        "CotaÃ§Ãµes",
        "Cota&ccedil;&otilde;es",
        "Comissoes",
        "Comissões",
        "ComissÃµes",
        "Comiss&otilde;es",
        "Reajustes",
        "Dados Pendentes"
    ];

    foreach ($sidebar_menu as $key => $item) {
        $name = $item["name"] ?? "";
        $url = $item["url"] ?? "";
        $url_text = is_array($url) ? implode("/", $url) : (string) $url;

        if ($name === "Dashboard") {
            if ($dashboard_seen) {
                unset($sidebar_menu[$key]);
                continue;
            }
            $dashboard_seen = true;
        }

        if (strpos($url_text, "green_crm") !== false || in_array($name, $green_generic_names, true) || $name === "Green CRM" || $name === "Green_crm" || ($name !== "" && strpos($name, "Green ") === 0)) {
            unset($sidebar_menu[$key]);
        }
    }

    foreach (green_crm_left_menu_native_items() as $key => $item) {
        $sidebar_menu[$key] = $item;
    }

    return $sidebar_menu;
});

// Permissoes do plugin no padrao nativo do Rise (aparecem na tela de Roles)
app_hooks()->add_action('app_hook_role_permissions_extension', function () {
    echo view("Green_crm\Views\permissions");
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = service("request");
    $keys = [
        "green_crm_view",
        "green_crm_manage_leads",
        "green_crm_manage_sales",
        "green_crm_manage_commissions",
        "green_crm_manage_commission_grades",
        "green_crm_recalculate_commissions",
        "green_crm_manage_settings",
        "green_crm_view_passwords",
        "green_crm_manage_passwords",
        "green_crm_reveal_passwords"
    ];
    foreach ($keys as $key) {
        $permissions[$key] = $request->getPost($key) ? "1" : "";
    }

    return $permissions;
});

if (function_exists("service")) {
    require __DIR__ . "/Config/Routes.php";
}

if (!function_exists("green_crm_install_or_update")) {
    function green_crm_left_menu_sections()
    {
        return [
            "dashboard" => ["name" => "Dashboard", "class" => "bar-chart-2", "position" => 4],
            "leads" => ["name" => "Clientes / Capa", "class" => "users", "position" => 6],
            "kanban" => ["name" => "Funil", "class" => "columns", "position" => 8],
            "sales" => ["name" => "Vendas", "class" => "shopping-cart", "position" => 10],
            "quotes" => ["name" => "Green Cotações", "class" => "file-text", "position" => 12],
            "commissions" => ["name" => "Green Comissões", "class" => "dollar-sign", "position" => 14],
            "renewals" => ["name" => "Reajustes", "class" => "refresh-cw", "position" => 16],
            "data_quality" => ["name" => "Dados Pendentes", "class" => "alert-triangle", "position" => 20],
            "settings" => ["name" => "Configurações", "class" => "settings", "position" => 22]
        ];
    }

    function green_crm_left_menu_native_name($key, $item)
    {
        $display_names = [
            "dashboard" => "Dashboard",
            "leads" => "Clientes / Capa",
            "kanban" => "Funil",
            "sales" => "Vendas",
            "quotes" => "Cotações",
            "commissions" => "Comissões",
            "renewals" => "Reajustes",
            "data_quality" => "Dados Pendentes",
            "settings" => "Configura&ccedil;&otilde;es"
        ];
        $display_names["quotes"] = "Cota&ccedil;&otilde;es";
        $display_names["commissions"] = "Comiss&otilde;es";

        if (isset($display_names[$key])) {
            return $display_names[$key];
        }

        return $item["name"];
    }

    function green_crm_left_menu_submenu_items()
    {
        // Submenu de um nivel (limite nativo do Rise). "Leads Meta Ads" eh injetado
        // pelo plugin Green_meta_leads quando ativo (green_meta_leads/index.php).
        return [
            ["name" => "Dashboard", "url" => get_uri("green_crm"), "class" => "bar-chart-2", "match" => "green_crm/index"],
            ["name" => "Leads", "url" => get_uri("green_crm/leads"), "class" => "users", "match" => "green_crm/lead"],
            ["name" => "Funil / Kanban", "url" => get_uri("green_crm/kanban"), "class" => "columns", "match" => "green_crm/kanban"],
            ["name" => "Tarefas e lembretes", "url" => get_uri("green_crm/tasks"), "class" => "check-square", "match" => "green_crm/task"],
            ["name" => "Cotações", "url" => get_uri("green_crm/quotes"), "class" => "file-text", "match" => "green_crm/quote"],
            ["name" => "Vendas", "url" => get_uri("green_crm/sales"), "class" => "shopping-cart", "match" => "green_crm/sale"],
            ["name" => "Comissões", "url" => get_uri("green_crm/commissions"), "class" => "dollar-sign", "match" => "green_crm/commissions"],
            ["name" => "Grades de comissão", "url" => get_uri("green_crm/commission_grades"), "class" => "sliders", "match" => "green_crm/commission_grade"],
            ["name" => "Gerenciador de anúncios", "url" => get_uri("green_crm/ad_campaigns"), "class" => "trello", "match" => "green_crm/ad_"],
            ["name" => "Reajustes", "url" => get_uri("green_crm/renewals"), "class" => "refresh-cw", "match" => "green_crm/renewal"],
            ["name" => "Dados Pendentes", "url" => get_uri("green_crm/data_quality"), "class" => "alert-triangle", "match" => "green_crm/data_quality"],
            ["name" => "Banco de senhas", "url" => get_uri("green_crm/passwords"), "class" => "lock", "match" => "green_crm/password"],
            ["name" => "Configurações", "url" => get_uri("green_crm/settings/statuses"), "class" => "settings", "match" => "green_crm/settings"]
        ];
    }

    function green_crm_left_menu_native_items()
    {
        // Cada pagina da Green e um MENU DE TOPO independente (sem item-pai "Green CRM").
        // Posicoes sequenciais mantem as paginas agrupadas perto do topo da barra.
        $items = [];
        $position = 4;
        foreach (green_crm_left_menu_submenu_items() as $item) {
            $items[$item["name"]] = [
                "name" => $item["name"],
                "url" => $item["url"],
                "is_custom_menu_item" => true,
                "class" => $item["class"],
                "position" => $position
            ];
            $position++;
        }

        return $items;
    }

    function green_crm_sync_left_menu_settings($db, $dbprefix)
    {
        $settings_table = $dbprefix . "settings";
        if (!$db->tableExists($settings_table)) {
            return;
        }

        $parent_name = "Green CRM";
        $legacy_root_names = ["Green_crm"];

        // Nomes canonicos das paginas Green (mesma fonte do submenu nativo).
        $green_sub_names = [];
        foreach (green_crm_left_menu_submenu_items() as $item) {
            $green_sub_names[] = $item["name"];
        }
        // Sub injetada por plugin irmao (Green_meta_leads) que tambem deve ficar no grupo.
        $extra_green_subs = ["Leads Meta Ads"];

        $is_green_item = function ($name) use ($parent_name, $legacy_root_names, $green_sub_names, $extra_green_subs) {
            if ($name === "") {
                return false;
            }
            if ($name === $parent_name || in_array($name, $legacy_root_names, true)) {
                return true;
            }
            if (in_array($name, $green_sub_names, true) || in_array($name, $extra_green_subs, true)) {
                return true;
            }
            // Legados "Green ..." (ex.: "Green Cotacoes").
            return strpos($name, "Green ") === 0;
        };

        $rows = $db->query("SELECT setting_name, setting_value FROM `" . $settings_table . "`
            WHERE deleted=0 AND (setting_name='default_left_menu' OR setting_name LIKE 'user\\_%\\_left_menu')")->getResult();

        foreach ($rows as $row) {
            $items = @unserialize($row->setting_value);
            if (!is_array($items) || !count($items)) {
                continue;
            }

            // 1) Retira todo item Green (pai, paginas e legados) das posicoes atuais
            //    para que nao fiquem pendurados como submenu de outro menu (ex.: Projetos).
            $rebuilt = [];
            $had_meta_ads = false;
            foreach ($items as $item) {
                $name = is_array($item) ? ($item["name"] ?? "") : "";
                if ($is_green_item($name)) {
                    if (in_array($name, $extra_green_subs, true)) {
                        $had_meta_ads = true;
                    }
                    continue;
                }
                $rebuilt[] = $item;
            }

            // 2) Monta o bloco Green: cada pagina vira um MENU DE TOPO independente
            //    (sem is_sub_menu e sem item-pai), para nao ficar sob nenhum outro menu.
            $block = [];
            foreach ($green_sub_names as $sub_name) {
                $block[] = ["name" => $sub_name];
                if ($sub_name === "Leads" && $had_meta_ads) {
                    $block[] = ["name" => "Leads Meta Ads"];
                }
            }

            // 3) Insere o bloco logo apos "projects" (ou no topo, se nao existir).
            $insert_pos = 0;
            foreach ($rebuilt as $idx => $it) {
                if ((is_array($it) ? ($it["name"] ?? "") : "") === "projects") {
                    $insert_pos = $idx + 1;
                    break;
                }
            }

            $new_items = array_merge(
                array_slice($rebuilt, 0, $insert_pos),
                $block,
                array_slice($rebuilt, $insert_pos)
            );

            // 4) Grava apenas quando muda (evita escrita a cada carregamento de pagina).
            $new_value = serialize($new_items);
            if ($new_value !== $row->setting_value) {
                $db->query("UPDATE `" . $settings_table . "` SET setting_value=" . $db->escape($new_value) . "
                    WHERE setting_name=" . $db->escape($row->setting_name));
            }
        }
    }

    function green_crm_run_sql_file($db, $file)
    {
        if (!is_file($file)) {
            return;
        }

        $sql = file_get_contents($file);
        $sql = str_replace("{dbprefix}", $db->getPrefix(), $sql);
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== "") {
                $db->query($statement);
            }
        }
    }

    function green_crm_install_or_update()
    {
        if (!function_exists("db_connect")) {
            return;
        }

        try {
            $db = db_connect();
            green_crm_sync_left_menu_settings($db, $db->getPrefix());
            green_crm_run_sql_file($db, __DIR__ . "/Database/install.sql");
            green_crm_run_sql_file($db, __DIR__ . "/Database/seeds.sql");
            green_crm_seed_commission_grades($db);
        } catch (\Exception $e) {
            log_message("error", "Erro ao instalar/atualizar Green CRM: " . $e->getMessage());
        }
    }

    /**
     * Gera a distribuicao de parcelas a partir de um multiplicador total.
     * Mesma logica do gerador "padrao por multiplicador" da tela de comissoes:
     * primeiras 2 parcelas ate 1.0, restante em blocos de 0.5.
     */
    function green_crm_distribute_multiplier($total)
    {
        $remaining = max(0, round((float) $total, 4));
        $rates = [];
        while ($remaining > 0.0001 && count($rates) < 2) {
            $chunk = min(1, $remaining);
            $rates[] = round($chunk, 4);
            $remaining = round($remaining - $chunk, 4);
        }
        while ($remaining > 0.0001) {
            $chunk = $remaining > 0.5 ? 0.5 : $remaining;
            $rates[] = round($chunk, 4);
            $remaining = round($remaining - $chunk, 4);
        }

        $installments = [];
        foreach ($rates as $index => $rate) {
            $installments[] = [
                "installment_no" => $index + 1,
                "installment_label" => ($index + 1) . "ª",
                "commission_rate" => $rate,
                "due_offset_months" => $index
            ];
        }
        return $installments;
    }

    /**
     * Seed idempotente das grades Ramed/Serra. Nunca sobrescreve linhas ja existentes
     * (identifica por nome da grade + versao + operadora + produto).
     */
    function green_crm_seed_commission_grades($db)
    {
        $grades_table = $db->prefixTable("green_commission_grades");
        $versions_table = $db->prefixTable("green_commission_grade_versions");
        $rules_table = $db->prefixTable("green_commission_rules");
        $rule_inst_table = $db->prefixTable("green_commission_rule_installments");
        $operators_table = $db->prefixTable("green_operators");

        if (!$db->tableExists($grades_table) || !$db->tableExists($rules_table)) {
            return;
        }

        helper("green");
        $seed_file = __DIR__ . "/Database/seed_commission_grades.php";
        if (!is_file($seed_file)) {
            return;
        }
        $seed = require $seed_file;
        $now = date("Y-m-d H:i:s");

        foreach ($seed as $entry) {
            $grade_name = $entry["grade"]["name"];

            $grade_row = $db->query("SELECT id FROM $grades_table WHERE name=" . $db->escape($grade_name) . " AND deleted=0 LIMIT 1")->getRow();
            if ($grade_row) {
                $grade_id = (int) $grade_row->id;
            } else {
                $db->table($grades_table)->insert([
                    "name" => $grade_name,
                    "partner_name" => $entry["grade"]["partner_name"],
                    "description" => $entry["grade"]["description"],
                    "status" => "Ativa",
                    "created_at" => $now,
                    "updated_at" => $now,
                    "deleted" => 0
                ]);
                $grade_id = (int) $db->insertID();
            }

            $version_name = $entry["version"]["version_name"];
            $version_row = $db->query("SELECT id FROM $versions_table WHERE grade_id=$grade_id AND version_name=" . $db->escape($version_name) . " AND deleted=0 LIMIT 1")->getRow();
            if ($version_row) {
                $version_id = (int) $version_row->id;
            } else {
                $db->table($versions_table)->insert([
                    "grade_id" => $grade_id,
                    "version_name" => $version_name,
                    "valid_from" => $entry["version"]["valid_from"],
                    "valid_until" => $entry["version"]["valid_until"],
                    "status" => "Ativa",
                    "notes" => $entry["version"]["notes"],
                    "source_file_name" => $entry["version"]["source_file_name"],
                    "created_at" => $now,
                    "updated_at" => $now,
                    "deleted" => 0
                ]);
                $version_id = (int) $db->insertID();
            }

            foreach ($entry["rules"] as $rule) {
                $operator_name = trim((string) ($rule["operator"] ?? ""));
                $operator_id = null;
                $operator_name_text = null;
                if ($operator_name !== "") {
                    $normalized = green_ascii_key(green_normalize_operator($operator_name));
                    $op_row = $db->query("SELECT id FROM $operators_table WHERE normalized_name=" . $db->escape($normalized) . " AND deleted=0 LIMIT 1")->getRow();
                    if ($op_row) {
                        $operator_id = (int) $op_row->id;
                    } else {
                        $operator_name_text = $operator_name;
                    }
                }

                $product_name = $rule["product_name"];
                $exists_sql = "SELECT id FROM $rules_table WHERE grade_version_id=$version_id AND deleted=0 AND product_name=" . $db->escape($product_name);
                if ($operator_id) {
                    $exists_sql .= " AND operator_id=$operator_id";
                } elseif ($operator_name_text !== null) {
                    $exists_sql .= " AND operator_name_text=" . $db->escape($operator_name_text);
                } else {
                    $exists_sql .= " AND operator_id IS NULL AND operator_name_text IS NULL";
                }
                if ($db->query($exists_sql . " LIMIT 1")->getRow()) {
                    continue;
                }

                $total = (float) $rule["total"];
                $db->table($rules_table)->insert([
                    "grade_id" => $grade_id,
                    "grade_version_id" => $version_id,
                    "operator_id" => $operator_id,
                    "operator_name_text" => $operator_name_text,
                    "product_name" => $product_name,
                    "product_type" => $rule["product_type"] ?? null,
                    "lives_range_text" => $rule["lives_range_text"] ?? null,
                    "total_multiplier" => $total,
                    "notes" => "revisar",
                    "status" => "Ativo",
                    "created_at" => $now,
                    "updated_at" => $now,
                    "deleted" => 0
                ]);
                $rule_id = (int) $db->insertID();

                foreach (green_crm_distribute_multiplier($total) as $inst) {
                    $db->table($rule_inst_table)->insert([
                        "rule_id" => $rule_id,
                        "installment_no" => $inst["installment_no"],
                        "installment_label" => $inst["installment_label"],
                        "commission_rate" => $inst["commission_rate"],
                        "due_offset_months" => $inst["due_offset_months"],
                        "created_at" => $now,
                        "updated_at" => $now,
                        "deleted" => 0
                    ]);
                }
            }
        }
    }
}

register_installation_hook("Green_crm", "green_crm_install_or_update");
register_update_hook("Green_crm", "green_crm_install_or_update");
register_activation_hook("Green_crm", "green_crm_install_or_update");
