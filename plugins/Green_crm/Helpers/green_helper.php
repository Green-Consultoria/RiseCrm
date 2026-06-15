<?php

if (!function_exists("green_digits")) {
    function green_digits($value)
    {
        return preg_replace("/\D+/", "", (string) $value) ?: "";
    }
}

if (!function_exists("green_normalize_phone")) {
    function green_normalize_phone($value)
    {
        $digits = green_digits($value);
        if (!$digits) {
            return null;
        }

        $length = strlen($digits);
        if (strpos($digits, "55") === 0 && ($length === 12 || $length === 13)) {
            return $digits;
        }

        if ($length === 10 || $length === 11) {
            return "55" . $digits;
        }

        return $digits;
    }
}

if (!function_exists("green_normalize_document")) {
    function green_normalize_document($value)
    {
        $digits = green_digits($value);
        $type = "NAO_INFORMADO";
        if (strlen($digits) === 11) {
            $type = "CPF";
        } elseif (strlen($digits) === 14) {
            $type = "CNPJ";
        }

        return ["document_type" => $type, "document_number" => $digits];
    }
}

if (!function_exists("green_format_document")) {
    function green_format_document($value, $type = null)
    {
        $digits = green_digits($value);
        if ($digits === "") {
            return "-";
        }

        $length = strlen($digits);
        $is_cpf = $type === "CPF" || ($type === null && $length === 11);
        $is_cnpj = $type === "CNPJ" || ($type === null && $length === 14);

        if ($is_cpf && $length === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $digits);
        }

        if ($is_cnpj && $length === 14) {
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $digits);
        }

        return $digits;
    }
}

if (!function_exists("green_normalize_email")) {
    function green_normalize_email($value)
    {
        $email = strtolower(trim((string) $value));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}

if (!function_exists("green_money_to_float")) {
    function green_money_to_float($value)
    {
        $value = trim((string) $value);
        if ($value === "") {
            return null;
        }

        $value = str_replace(["R$", " "], "", $value);
        $has_comma = strpos($value, ",") !== false;
        $has_dot = strpos($value, ".") !== false;

        if ($has_comma && $has_dot) {
            $value = str_replace(".", "", $value);
            $value = str_replace(",", ".", $value);
        } elseif ($has_comma) {
            $value = str_replace(",", ".", $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

if (!function_exists("green_date_value")) {
    function green_date_value($value)
    {
        if ($value === null || $value === "") {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (int) $value;
            if ($serial > 20000 && $serial < 90000) {
                return gmdate("Y-m-d", ($serial - 25569) * 86400);
            }
        }

        $value = trim((string) $value);
        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
            return $value;
        }

        if (preg_match("/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/", $value, $m)) {
            return sprintf("%04d-%02d-%02d", (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date("Y-m-d", $timestamp) : null;
    }
}

if (!function_exists("green_ascii_key")) {
    function green_ascii_key($value)
    {
        $value = strtoupper(trim((string) $value));
        if (function_exists("iconv")) {
            $converted = @iconv("UTF-8", "ASCII//TRANSLIT", $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = preg_replace("/\s+/", " ", $value);
        return trim($value);
    }
}

if (!function_exists("green_normalize_operator")) {
    function green_normalize_operator($value)
    {
        $key = green_ascii_key($value);
        $sul = "SULAM" . "\u{00C9}" . "RICA";
        $map = [
            "AML" => "AMIL",
            "AMIL" => "AMIL",
            "SULAMERICA" => $sul,
            "SUL AMERICA" => $sul,
            "SULAMERICA SAUDE" => $sul,
            "SUL AMERICA SAUDE" => $sul,
            "PORTO" => "PORTO SEGURO",
            "PORTOSEG" => "PORTO SEGURO",
            "PORTO SEGURO" => "PORTO SEGURO",
            "BRADESCO" => "BRADESCO",
            "ALICE" => "ALICE",
            "MEDSENIOR PF" => "MEDSENIOR PF"
        ];

        return $map[$key] ?? strtoupper(trim((string) $value));
    }
}

if (!function_exists("green_normalize_plan")) {
    function green_normalize_plan($value)
    {
        $value = preg_replace("/\s+/", " ", strtoupper(trim((string) $value)));
        $key = str_replace(["'", "`", "´"], "", green_ascii_key($value));
        $map = [
            "PRATA PRO" => "PRATA PRO",
            "PRATA" => "PRATA"
        ];

        return $map[$key] ?? $value;
    }
}

if (!function_exists("green_month_to_number")) {
    function green_month_to_number($month)
    {
        $key = green_ascii_key($month);
        $months = [
            "JANEIRO" => 1,
            "FEVEREIRO" => 2,
            "MARCO" => 3,
            "ABRIL" => 4,
            "MAIO" => 5,
            "JUNHO" => 6,
            "JULHO" => 7,
            "AGOSTO" => 8,
            "SETEMBRO" => 9,
            "OUTUBRO" => 10,
            "NOVEMBRO" => 11,
            "DEZEMBRO" => 12
        ];

        return $months[$key] ?? null;
    }
}

if (!function_exists("green_audit")) {
    function green_audit($entity_type, $entity_id, $action, $old_data = null, $new_data = null, $user_id = 0)
    {
        if (!function_exists("db_connect")) {
            return;
        }

        $db = db_connect();
        $table = $db->prefixTable("green_audit_logs");
        if (!$db->tableExists($table)) {
            return;
        }

        $db->table($table)->insert([
            "entity_type" => $entity_type,
            "entity_id" => (int) $entity_id,
            "action" => $action,
            "old_data" => $old_data ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null,
            "new_data" => $new_data ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null,
            "user_id" => (int) $user_id,
            "created_at" => date("Y-m-d H:i:s")
        ]);
    }
}

if (!function_exists("green_log_changes")) {
    /**
     * Compara $old e $new (apenas as chaves de $new) e, havendo diferenca,
     * grava um registro de auditoria com o diff (de/para). Retorna o diff.
     */
    function green_log_changes($entity_type, $entity_id, $old, $new, $user_id = 0, $action = "update")
    {
        $old = is_array($old) ? $old : (array) $old;
        $new = is_array($new) ? $new : (array) $new;

        $old_diff = [];
        $new_diff = [];
        foreach ($new as $key => $new_value) {
            $old_value = array_key_exists($key, $old) ? $old[$key] : null;
            //comparacao leniente: trata null/"" como iguais e normaliza numeros como string
            $a = $old_value === null ? "" : (string) $old_value;
            $b = $new_value === null ? "" : (string) $new_value;
            if ($a !== $b) {
                $old_diff[$key] = $old_value;
                $new_diff[$key] = $new_value;
            }
        }

        if (!count($new_diff)) {
            return [];
        }

        green_audit($entity_type, $entity_id, $action, $old_diff, $new_diff, $user_id);
        return $new_diff;
    }
}

if (!function_exists("green_can")) {
    /**
     * Checa permissao do plugin no padrao do Rise.
     * - admin sempre pode;
     * - $strict=false (modulos gerais): permissao NUNCA configurada => liberado (retrocompat);
     *   somente bloqueia quando um admin marcou explicitamente como negada.
     * - $strict=true (revelar senha): exige a flag marcada.
     */
    function green_can($login_user, $permission, $strict = false)
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        $permissions = isset($login_user->permissions) && is_array($login_user->permissions) ? $login_user->permissions : [];

        if (!$strict && !array_key_exists($permission, $permissions)) {
            return true;
        }

        return (bool) get_array_value($permissions, $permission);
    }
}
