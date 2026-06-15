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
            ["name" => "Comissões", "url" => get_uri("green_crm/commissions"), "class" => "dollar-sign", "match" => "green_crm/commission"],
            ["name" => "Gerenciador de anúncios", "url" => get_uri("green_crm/ad_campaigns"), "class" => "trello", "match" => "green_crm/ad_"],
            ["name" => "Reajustes", "url" => get_uri("green_crm/renewals"), "class" => "refresh-cw", "match" => "green_crm/renewal"],
            ["name" => "Dados Pendentes", "url" => get_uri("green_crm/data_quality"), "class" => "alert-triangle", "match" => "green_crm/data_quality"],
            ["name" => "Banco de senhas", "url" => get_uri("green_crm/passwords"), "class" => "lock", "match" => "green_crm/password"],
            ["name" => "Configurações", "url" => get_uri("green_crm/settings/statuses"), "class" => "settings", "match" => "green_crm/settings"]
        ];
    }

    function green_crm_left_menu_native_items()
    {
        $uri = function_exists("uri_string") ? (string) uri_string() : "";
        $is_green_active = strpos($uri, "green_crm") === 0 || strpos($uri, "green_meta_leads") === 0;

        $submenu = [];
        foreach (green_crm_left_menu_submenu_items() as $item) {
            $sub = [
                "name" => $item["name"],
                "url" => $item["url"],
                "is_custom_menu_item" => true,
                "class" => $item["class"]
            ];
            $submenu[] = $sub;
        }

        $parent = [
            "name" => "Green CRM",
            "url" => get_uri("green_crm"),
            "is_custom_menu_item" => true,
            "class" => "activity",
            "position" => 4,
            "submenu" => $submenu
        ];

        if ($is_green_active) {
            $parent["is_active_menu"] = 1;
        }

        return ["green_crm" => $parent];
    }

    function green_crm_sync_left_menu_settings($db, $dbprefix)
    {
        $settings_table = $dbprefix . "settings";
        if (!$db->tableExists($settings_table)) {
            return;
        }

        $native_items = [];
        $native_names = [];
        $legacy_submenu_names = ["Dashboard", "Leads", "Funil", "Vendas", "Cotações", "Cotacoes", "Reajustes", "Dados Pendentes", "Comissoes"];
        foreach (green_crm_left_menu_sections() as $key => $item) {
            $native_name = green_crm_left_menu_native_name($key, $item);
            $native_items[] = ["name" => $native_name];
            $native_names[] = $native_name;
        }

        $legacy_root_names = ["Green CRM", "Green_crm"];
        $rows = $db->query("SELECT setting_name, setting_value FROM `" . $settings_table . "`
            WHERE deleted=0 AND (setting_name='default_left_menu' OR setting_name LIKE 'user\\_%\\_left_menu')")->getResult();

        foreach ($rows as $row) {
            $items = @unserialize($row->setting_value);
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $changed = false;
            $inserted_native_items = false;
            $rebuilt = [];
            foreach ($items as $item) {
                $name = $item["name"] ?? "";

                if ($name !== "Green CRM" && strpos($name, "Green ") === 0) {
                    $changed = true;
                    continue;
                }

                if (!empty($item["is_sub_menu"]) && $name !== "Green CRM" && strpos($name, "Green ") === 0) {
                    $changed = true;
                    continue;
                }

                if (in_array($name, $legacy_root_names, true)) {
                    $changed = true;
                    continue;
                }

                $rebuilt[] = $item;
            }

            if ($changed) {
                $db->query("UPDATE `" . $settings_table . "` SET setting_value=" . $db->escape(serialize($rebuilt)) . "
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
        } catch (\Exception $e) {
            log_message("error", "Erro ao instalar/atualizar Green CRM: " . $e->getMessage());
        }
    }
}

register_installation_hook("Green_crm", "green_crm_install_or_update");
register_update_hook("Green_crm", "green_crm_install_or_update");
register_activation_hook("Green_crm", "green_crm_install_or_update");
