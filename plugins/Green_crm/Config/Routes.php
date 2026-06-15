<?php

if (defined("GREEN_CRM_ROUTES_LOADED")) {
    return;
}

define("GREEN_CRM_ROUTES_LOADED", true);

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group("green_crm", ["namespace" => "Green_crm\Controllers"], function ($routes) {
    $routes->get("/", "Green::index");
    $routes->get("index", "Green::index");
    $routes->post("dashboard_data", "Green::dashboard_data");
    $routes->get("renewals", "Green_renewals::index");
    $routes->post("renewals_list_data", "Green_renewals::list_data");
    $routes->post("create_renewal_followup", "Green_renewals::create_renewal_followup");
    $routes->get("data_quality", "Green_data_quality::index");
    $routes->post("data_quality_list_data", "Green_data_quality::list_data");
    $routes->get("kanban", "Green_kanban::index");
    $routes->post("update_lead_status", "Green_kanban::update_lead_status");

    $routes->get("leads", "Green_leads::index");
    $routes->post("leads_list_data", "Green_leads::list_data");
    $routes->post("lead_modal_form", "Green_leads::modal_form");
    $routes->post("save_lead", "Green_leads::save");
    $routes->post("delete_lead", "Green_leads::delete");
    $routes->get("lead/(:num)", "Green_leads::view/$1");
    $routes->post("contact_modal_form", "Green_leads::contact_modal_form");
    $routes->post("save_contact", "Green_leads::save_contact");
    $routes->post("delete_contact", "Green_leads::delete_contact");
    $routes->post("life_modal_form", "Green_leads::life_modal_form");
    $routes->post("save_life", "Green_leads::save_life");
    $routes->post("interaction_modal_form", "Green_leads::interaction_modal_form");
    $routes->post("save_interaction", "Green_leads::save_interaction");
    $routes->post("task_modal_form", "Green_leads::task_modal_form");
    $routes->post("save_task", "Green_leads::save_task");
    $routes->post("complete_task", "Green_leads::complete_task");

    $routes->get("sales", "Green_sales::index");
    $routes->post("sales_list_data", "Green_sales::list_data");
    $routes->post("sale_modal_form", "Green_sales::modal_form");
    $routes->post("import_modal_form", "Green_imports::modal_form");
    $routes->post("import_preview", "Green_imports::preview");
    $routes->post("confirm_import", "Green_imports::confirm");
    $routes->get("import_report/(:num)", "Green_imports::report/$1");
    $routes->get("search_leads", "Green_sales::search_leads");
    $routes->post("search_leads", "Green_sales::search_leads");
    $routes->get("search_clients", "Green_sales::search_clients");
    $routes->post("search_clients", "Green_sales::search_clients");
    $routes->post("save_sale", "Green_sales::save");
    $routes->post("cancel_sale", "Green_sales::cancel");
    $routes->post("convert_lead_to_sale", "Green_sales::convert_lead_to_sale");
    $routes->get("sale_implantation_checklist/(:num)", "Green_sales::sale_implantation_checklist/$1");
    $routes->post("update_implantation_item", "Green_sales::update_implantation_item");

    $routes->get("quotes", "Green_quotes::index");
    $routes->post("quotes_list_data", "Green_quotes::list_data");
    $routes->get("quote/(:num)", "Green_quotes::view/$1");
    $routes->post("quote_modal_form", "Green_quotes::modal_form");
    $routes->post("quote_option_modal_form", "Green_quotes::option_modal_form");
    $routes->post("save_quote", "Green_quotes::save");
    $routes->post("save_quote_option", "Green_quotes::save_option");
    $routes->post("delete_quote_option", "Green_quotes::delete_option");
    $routes->post("select_quote_option", "Green_quotes::select_option");
    $routes->post("send_quote", "Green_quotes::mark_as_sent");
    $routes->post("accept_quote", "Green_quotes::accept");
    $routes->post("convert_selected_quote_option_to_sale", "Green_quotes::convert_selected_to_sale");

    $routes->get("commissions", "Green_commissions::index");
    $routes->post("commissions_list_data", "Green_commissions::list_data");
    $routes->post("commissions_summary_data", "Green_commissions::summary_data");
    $routes->post("commission_generation_modal_form", "Green_commissions::generation_modal_form");
    $routes->post("generate_commissions", "Green_commissions::generate");
    $routes->post("commission_payment_modal_form", "Green_commissions::payment_modal_form");
    $routes->post("save_commission_payment", "Green_commissions::save_payment");
    $routes->post("cancel_commission", "Green_commissions::cancel");

    // Tarefas e lembretes (tela geral)
    $routes->get("tasks", "Green_tasks::index");
    $routes->post("tasks_list_data", "Green_tasks::list_data");
    $routes->post("task_general_modal_form", "Green_tasks::modal_form");
    $routes->post("save_general_task", "Green_tasks::save_general_task");
    $routes->post("update_task_status", "Green_tasks::update_status");
    $routes->post("delete_task", "Green_tasks::delete");

    // Banco de senhas
    $routes->get("passwords", "Green_passwords::index");
    $routes->post("passwords_list_data", "Green_passwords::list_data");
    $routes->post("password_modal_form", "Green_passwords::modal_form");
    $routes->post("save_password", "Green_passwords::save");
    $routes->post("reveal_password", "Green_passwords::reveal");
    $routes->post("log_copy_password", "Green_passwords::log_copy");
    $routes->post("delete_password", "Green_passwords::delete");

    // Gerenciador de anuncios
    $routes->get("ad_campaigns", "Green_ads::campaigns");
    $routes->post("campaigns_list_data", "Green_ads::campaigns_list_data");
    $routes->get("ad_sets", "Green_ads::ad_sets");
    $routes->post("ad_sets_list_data", "Green_ads::ad_sets_list_data");
    $routes->get("ads", "Green_ads::ads");
    $routes->post("ads_list_data", "Green_ads::ads_list_data");
    $routes->get("roi", "Green_ads::roi");
    $routes->post("roi_list_data", "Green_ads::roi_list_data");
    $routes->post("ad_modal_form", "Green_ads::ad_modal_form");
    $routes->post("save_ad", "Green_ads::save_ad");
    $routes->post("delete_ad", "Green_ads::delete_ad");

    $routes->get("settings/statuses", "Green_settings::statuses");
    $routes->get("settings/operators", "Green_settings::operators");
    $routes->get("settings/plans", "Green_settings::plans");
    $routes->post("statuses_list_data", "Green_settings::statuses_list_data");
    $routes->post("status_modal_form", "Green_settings::status_modal_form");
    $routes->post("save_status", "Green_settings::save_status");
    $routes->post("operators_list_data", "Green_settings::operators_list_data");
    $routes->post("operator_modal_form", "Green_settings::operator_modal_form");
    $routes->post("save_operator", "Green_settings::save_operator");
    $routes->post("plans_list_data", "Green_settings::plans_list_data");
    $routes->post("plan_modal_form", "Green_settings::plan_modal_form");
    $routes->post("save_plan", "Green_settings::save_plan");
});
