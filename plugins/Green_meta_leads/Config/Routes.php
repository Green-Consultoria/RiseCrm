<?php

if (defined("GREEN_META_LEADS_ROUTES_LOADED")) {
    return;
}

define("GREEN_META_LEADS_ROUTES_LOADED", true);

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group("green_meta_leads", ["namespace" => "Green_meta_leads\Controllers"], function ($routes) {
    $routes->get("/", "Green_meta_leads::index");
    $routes->get("index", "Green_meta_leads::index");
    $routes->post("list_data", "Green_meta_leads::list_data");
    $routes->post("lead_modal_form", "Green_meta_leads::lead_modal_form");
    $routes->post("sync", "Green_meta_leads::sync");
    $routes->post("reprocess", "Green_meta_leads::reprocess");
    $routes->get("settings", "Green_meta_leads::settings");
    $routes->post("save_settings", "Green_meta_leads::save_settings");
});
