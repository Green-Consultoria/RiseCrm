<?php

namespace Green_crm\Controllers;

use App\Controllers\Security_Controller;

class Green_passwords extends Security_Controller
{
    public $Green_password_vault_model;
    public $Users_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        helper("green");

        if (function_exists("green_crm_install_or_update")) {
            green_crm_install_or_update();
        }

        $this->Green_password_vault_model = model("Green_crm\Models\Green_password_vault_model");
        $this->Users_model = model("App\Models\Users_model");

        if (!green_can($this->login_user, "green_crm_view_passwords")) {
            app_redirect("forbidden");
        }
    }

    public function index()
    {
        return $this->template->render("Green_crm\Views\lista_passwords", [
            "members_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"]),
            "categories_dropdown" => $this->Green_password_vault_model->get_categories(),
            "can_manage" => green_can($this->login_user, "green_crm_manage_passwords"),
            "can_reveal" => green_can($this->login_user, "green_crm_reveal_passwords", true)
        ]);
    }

    public function list_data()
    {
        $options = [
            "category" => $this->request->getPost("category"),
            "owner_user_id" => $this->request->getPost("owner_user_id"),
            "search" => $this->request->getPost("search")
        ];

        $can_reveal = green_can($this->login_user, "green_crm_reveal_passwords", true);
        $can_manage = green_can($this->login_user, "green_crm_manage_passwords");

        $rows = [];
        foreach ($this->Green_password_vault_model->get_details($options)->getResult() as $item) {
            $rows[] = $this->_row($item, $can_reveal, $can_manage);
        }

        echo json_encode(["data" => $rows]);
    }

    public function modal_form()
    {
        if (!green_can($this->login_user, "green_crm_manage_passwords")) {
            app_redirect("forbidden");
        }

        $id = (int) $this->request->getPost("id");
        $model_info = $id ? $this->Green_password_vault_model->get_details(["id" => $id])->getRow() : new \stdClass();

        return $this->template->view("Green_crm\Views\modal_password", [
            "model_info" => $model_info ?: new \stdClass(),
            "members_dropdown" => $this->Users_model->get_dropdown_list_with_blank_option(["first_name", "last_name"], "-", ["status" => "active", "user_type" => "staff"]),
            "current_user_id" => (int) $this->login_user->id,
            "has_password" => $id && !empty($model_info->encrypted_password)
        ]);
    }

    public function save()
    {
        if (!green_can($this->login_user, "green_crm_manage_passwords")) {
            echo json_encode(["success" => false, "message" => "Sem permissão para gerenciar senhas."]);
            return;
        }

        $title = trim((string) $this->request->getPost("title"));
        if (!$title) {
            echo json_encode(["success" => false, "message" => "Informe o nome do acesso."]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        $now = date("Y-m-d H:i:s");
        $user_id = (int) $this->login_user->id;
        $plain_password = (string) $this->request->getPost("password");

        $data = [
            "title" => $title,
            "category" => trim((string) $this->request->getPost("category")) ?: null,
            "system_url" => trim((string) $this->request->getPost("system_url")) ?: null,
            "login_username" => trim((string) $this->request->getPost("login_username")) ?: null,
            "notes" => trim((string) $this->request->getPost("notes")) ?: null,
            "owner_user_id" => (int) $this->request->getPost("owner_user_id") ?: $user_id,
            "visibility_scope" => $this->request->getPost("visibility_scope") === "private" ? "private" : "team",
            "updated_by" => $user_id,
            "updated_at" => $now,
            "deleted" => 0
        ];

        // Só atualiza a senha quando um novo valor for informado (em branco mantém a atual)
        if ($plain_password !== "") {
            $encrypted = $this->_encrypt($plain_password);
            if ($encrypted === null) {
                echo json_encode(["success" => false, "message" => "Falha ao criptografar a senha. Verifique a configuração de criptografia do Rise."]);
                return;
            }
            $data["encrypted_password"] = $encrypted;
            $data["last_rotated_at"] = $now;
        }

        if (!$id) {
            $data["created_by"] = $user_id;
            $data["created_at"] = $now;
        }

        $vault_id = $this->Green_password_vault_model->ci_save($data, $id);

        if (function_exists("green_audit")) {
            green_audit("password_vault", $vault_id, $id ? "updated" : "created", null, ["title" => $title], $user_id);
        }

        echo json_encode(["success" => true, "id" => $vault_id, "message" => "Acesso salvo."]);
    }

    public function reveal()
    {
        if (!green_can($this->login_user, "green_crm_reveal_passwords", true)) {
            echo json_encode(["success" => false, "message" => "Sem permissão para revelar senha."]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        $item = $id ? $this->Green_password_vault_model->get_details(["id" => $id])->getRow() : null;
        if (!$item) {
            echo json_encode(["success" => false, "message" => "Acesso não encontrado."]);
            return;
        }

        $plain = $this->_decrypt($item->encrypted_password);
        if ($plain === null) {
            echo json_encode(["success" => false, "message" => "Não foi possível descriptografar a senha."]);
            return;
        }

        if (function_exists("green_audit")) {
            green_audit("password_vault", $id, "reveal", null, ["title" => $item->title], (int) $this->login_user->id);
        }

        echo json_encode(["success" => true, "password" => $plain]);
    }

    public function log_copy()
    {
        if (!green_can($this->login_user, "green_crm_reveal_passwords", true)) {
            echo json_encode(["success" => false]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        if ($id && function_exists("green_audit")) {
            green_audit("password_vault", $id, "copy", null, null, (int) $this->login_user->id);
        }

        echo json_encode(["success" => true]);
    }

    public function delete()
    {
        if (!green_can($this->login_user, "green_crm_manage_passwords")) {
            echo json_encode(["success" => false, "message" => "Sem permissão."]);
            return;
        }

        $id = (int) $this->request->getPost("id");
        if (!$id) {
            echo json_encode(["success" => false, "message" => "Acesso inválido."]);
            return;
        }

        $delete_data = [
            "deleted" => 1,
            "updated_by" => (int) $this->login_user->id,
            "updated_at" => date("Y-m-d H:i:s")
        ];
        $this->Green_password_vault_model->ci_save($delete_data, $id);

        if (function_exists("green_audit")) {
            green_audit("password_vault", $id, "deleted", null, null, (int) $this->login_user->id);
        }

        echo json_encode(["success" => true, "message" => "Acesso removido."]);
    }

    private function _row($item, $can_reveal, $can_manage)
    {
        $reveal_btn = "";
        if ($can_reveal && !empty($item->encrypted_password)) {
            $reveal_btn = js_anchor("<i data-feather='eye' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-reveal-password", "data-id" => $item->id, "title" => "Revelar/copiar senha"]);
        }

        $edit_btn = "";
        $delete_btn = "";
        if ($can_manage) {
            $edit_btn = modal_anchor(get_uri("green_crm/password_modal_form"), "<i data-feather='edit' class='icon-16'></i>", ["class" => "btn btn-default btn-sm", "title" => "Editar", "data-post-id" => $item->id]);
            $delete_btn = js_anchor("<i data-feather='trash-2' class='icon-16'></i>", ["class" => "btn btn-default btn-sm green-delete-password", "data-id" => $item->id, "title" => "Excluir"]);
        }

        $url = "-";
        if ($item->system_url) {
            $href = preg_match("#^https?://#i", $item->system_url) ? $item->system_url : ("http://" . $item->system_url);
            $url = anchor($href, esc($item->system_url), ["target" => "_blank", "rel" => "noopener"]);
        }

        return [
            esc($item->title),
            $item->category ? esc($item->category) : "-",
            $url,
            $item->login_username ? esc($item->login_username) : "-",
            "<span class='text-monospace'>••••••••</span>",
            esc($item->owner_name ?: "-"),
            $item->updated_at ? format_to_datetime($item->updated_at) : "-",
            $reveal_btn . $edit_btn . $delete_btn
        ];
    }

    private function _encrypt($plain)
    {
        try {
            $encrypter = get_encrypter();
            return base64_encode($encrypter->encrypt($plain));
        } catch (\Throwable $e) {
            log_message("error", "Green CRM vault encrypt: " . $e->getMessage());
            return null;
        }
    }

    private function _decrypt($stored)
    {
        if ($stored === null || $stored === "") {
            return "";
        }

        try {
            $encrypter = get_encrypter();
            return $encrypter->decrypt(base64_decode($stored));
        } catch (\Throwable $e) {
            log_message("error", "Green CRM vault decrypt: " . $e->getMessage());
            return null;
        }
    }
}
