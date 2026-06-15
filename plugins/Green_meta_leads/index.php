<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
Plugin Name: Green Meta Leads
Description: Captação de leads do Facebook Lead Ads direto no pipeline do Green CRM.
Version: 0.1.0
Requires at least: 3.0
*/

// Prioridade 20: roda depois do Green CRM (10) para injetar dentro do menu pai
app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    //remove the legacy "Leads SIAMESA" item from the live menu
    foreach ($sidebar_menu as $key => $item) {
        $name = $item["name"] ?? "";
        $url = $item["url"] ?? "";
        $url_text = is_array($url) ? implode("/", $url) : (string) $url;

        if ($name === "Leads SIAMESA" || strpos($url_text, "siamesa_leads") !== false || $name === "Tráfego Pago") {
            unset($sidebar_menu[$key]);
        }
    }

    $meta_item = green_meta_leads_left_menu_submenu_item();

    // Integra como submenu "Leads Meta Ads" dentro do pai "Green CRM" (mesmo dominio de Leads)
    if (isset($sidebar_menu["green_crm"]) && isset($sidebar_menu["green_crm"]["submenu"]) && is_array($sidebar_menu["green_crm"]["submenu"])) {
        $submenu = $sidebar_menu["green_crm"]["submenu"];
        $rebuilt = [];
        foreach ($submenu as $sub) {
            $rebuilt[] = $sub;
            //insere logo apos o item "Leads"
            if (($sub["name"] ?? "") === "Leads") {
                $rebuilt[] = $meta_item;
            }
        }
        //fallback: se nao achou "Leads", anexa ao fim
        if (count($rebuilt) === count($submenu)) {
            $rebuilt[] = $meta_item;
        }
        $sidebar_menu["green_crm"]["submenu"] = $rebuilt;

        if (function_exists("uri_string") && strpos((string) uri_string(), "green_meta_leads") === 0) {
            $sidebar_menu["green_crm"]["is_active_menu"] = 1;
        }

        return $sidebar_menu;
    }

    // Fallback: Green CRM inativo -> mantem item proprio de topo
    foreach (green_meta_leads_left_menu_native_items() as $key => $item) {
        $sidebar_menu[$key] = $item;
    }

    return $sidebar_menu;
}, 20);

app_hooks()->add_action('app_hook_role_permissions_extension', function () {
    echo view("Green_meta_leads\Views\permissions");
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = service("request");
    $permissions["green_meta_leads_view"] = $request->getPost("green_meta_leads_view") ? "1" : "";
    $permissions["green_meta_leads_sync"] = $request->getPost("green_meta_leads_sync") ? "1" : "";

    return $permissions;
});

if (function_exists("service")) {
    require __DIR__ . "/Config/Routes.php";
}

if (!function_exists("green_meta_leads_install_or_update")) {
    function green_meta_leads_left_menu_submenu_item()
    {
        return [
            "name" => "Leads Meta Ads",
            "url" => get_uri("green_meta_leads"),
            "is_custom_menu_item" => true,
            "class" => "facebook"
        ];
    }

    function green_meta_leads_left_menu_native_items()
    {
        $is_active = function_exists("uri_string") && strpos((string) uri_string(), "green_meta_leads") === 0;

        $item = [
            "name" => "Leads Meta Ads",
            "url" => get_uri("green_meta_leads"),
            "is_custom_menu_item" => true,
            "class" => "facebook",
            "position" => 18
        ];

        if ($is_active) {
            $item["is_active_menu"] = 1;
        }

        return ["green_meta_leads" => $item];
    }

    function green_meta_leads_sync_left_menu_settings($db, $dbprefix)
    {
        $settings_table = $dbprefix . "settings";
        if (!$db->tableExists($settings_table)) {
            return;
        }

        $native_names = [];
        $native_items = [];
        foreach (green_meta_leads_left_menu_native_items() as $item) {
            $native_names[] = $item["name"];
            $native_items[] = ["name" => $item["name"]];
        }

        $legacy_names = ["Leads SIAMESA", "Captação SIAMESA", "FacebookLeadsSiamesa", "SiamesaLeads", "Leads Facebook"];
        $rows = $db->query("SELECT setting_name, setting_value FROM `" . $settings_table . "`
            WHERE deleted=0 AND (setting_name='default_left_menu' OR setting_name LIKE 'user\\_%\\_left_menu')")->getResult();

        foreach ($rows as $row) {
            $items = @unserialize($row->setting_value);
            if (!is_array($items) || !count($items)) {
                continue;
            }

            $changed = false;
            $has_native_item = false;
            $rebuilt = [];

            foreach ($items as $item) {
                $name = $item["name"] ?? "";

                if (in_array($name, $native_names, true)) {
                    $has_native_item = true;
                    $rebuilt[] = $item;
                    continue;
                }

                if (in_array($name, $legacy_names, true)) {
                    $changed = true;
                    continue;
                }

                $rebuilt[] = $item;
            }

            if (!$has_native_item) {
                $insert_after = min(16, count($rebuilt));
                array_splice($rebuilt, $insert_after, 0, $native_items);
                $changed = true;
            }

            if ($changed) {
                $db->query("UPDATE `" . $settings_table . "` SET setting_value=" . $db->escape(serialize($rebuilt)) . "
                    WHERE setting_name=" . $db->escape($row->setting_name));
            }
        }
    }

    function green_meta_leads_run_sql_file($db, $file)
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

    function green_meta_leads_ensure_source($db)
    {
        $sources_table = $db->prefixTable("green_sources");
        if (!$db->tableExists($sources_table)) {
            //Green CRM not installed yet; the controller re-runs this hook on the next request
            return;
        }

        $row = $db->query("SELECT id FROM `" . $sources_table . "`
            WHERE deleted=0 AND title=" . $db->escape("Facebook Lead Ads") . " LIMIT 1")->getRow();

        if (!$row) {
            $db->table($sources_table)->insert([
                "title" => "Facebook Lead Ads",
                "created_at" => date("Y-m-d H:i:s"),
                "deleted" => 0
            ]);
        }
    }

    function green_meta_leads_install_or_update()
    {
        if (!function_exists("db_connect")) {
            return;
        }

        try {
            $db = db_connect();
            green_meta_leads_sync_left_menu_settings($db, $db->getPrefix());
            green_meta_leads_run_sql_file($db, __DIR__ . "/Database/install.sql");
            green_meta_leads_ensure_source($db);
        } catch (\Throwable $e) {
            log_message("error", "Erro ao instalar/atualizar Green Meta Leads: " . $e->getMessage());
        }
    }
}

register_installation_hook("Green_meta_leads", "green_meta_leads_install_or_update");
register_update_hook("Green_meta_leads", "green_meta_leads_install_or_update");
register_activation_hook("Green_meta_leads", "green_meta_leads_install_or_update");
