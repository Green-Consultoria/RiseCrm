CREATE TABLE IF NOT EXISTS `{dbprefix}green_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_type` enum('PF','PJ','NAO_INFORMADO') DEFAULT 'NAO_INFORMADO',
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `document_type` enum('CPF','CNPJ','NAO_INFORMADO') DEFAULT 'NAO_INFORMADO',
  `document_number` varchar(30) DEFAULT NULL,
  `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_document` (`document_type`,`document_number`),
  KEY `idx_name` (`name`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_clients`
  ADD COLUMN IF NOT EXISTS `client_code` varchar(50) DEFAULT NULL AFTER `id`,
  ADD INDEX IF NOT EXISTS `idx_client_code` (`client_code`);

CREATE TABLE IF NOT EXISTS `{dbprefix}green_client_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `phone_original` varchar(80) DEFAULT NULL,
  `phone_normalized` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_phone` (`phone_normalized`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_client_contacts`
  ADD COLUMN IF NOT EXISTS `name` varchar(255) DEFAULT NULL AFTER `client_id`,
  ADD COLUMN IF NOT EXISTS `role` varchar(100) DEFAULT NULL AFTER `name`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_operators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `normalized_name` varchar(255) DEFAULT NULL,
  `aliases` text DEFAULT NULL,
  `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_normalized_name` (`normalized_name`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_lead_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `sort` int(11) DEFAULT 0,
  `is_final` tinyint(1) DEFAULT 0,
  `is_won` tinyint(1) DEFAULT 0,
  `is_lost` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_code` varchar(50) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `source_lead_id` varchar(120) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `temperature` enum('quente','morno','frio','sem_classificacao') DEFAULT 'sem_classificacao',
  `owner_id` int(11) DEFAULT NULL,
  `current_operator_id` int(11) DEFAULT NULL,
  `current_plan_name` varchar(255) DEFAULT NULL,
  `lives_qty` int(11) DEFAULT NULL,
  `ages_text` varchar(255) DEFAULT NULL,
  `renewal_month` tinyint(2) DEFAULT NULL,
  `current_paid_value` decimal(12,2) DEFAULT NULL,
  `proposed_value` decimal(12,2) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `preferred_hospital_text` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `lost_reason` text DEFAULT NULL,
  `next_followup_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status_id`),
  KEY `idx_source_lead` (`source_id`,`source_lead_id`),
  KEY `idx_owner` (`owner_id`),
  KEY `idx_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_leads`
  ADD COLUMN IF NOT EXISTS `lost_reason` text DEFAULT NULL AFTER `notes`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `interaction_type` varchar(80) DEFAULT 'system',
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_interactions_lead` (`lead_id`),
  KEY `idx_green_interactions_type` (`interaction_type`),
  KEY `idx_green_interactions_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_lead_lives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `relationship` varchar(80) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_lead_lives_lead` (`lead_id`),
  KEY `idx_green_lead_lives_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_lead_lives`
  ADD COLUMN IF NOT EXISTS `relationship` varchar(80) DEFAULT NULL AFTER `birth_date`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `responsible_id` int(11) DEFAULT NULL,
  `status` enum('Pendente','Concluida','Cancelada') DEFAULT 'Pendente',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_tasks_lead` (`lead_id`),
  KEY `idx_green_tasks_status` (`status`),
  KEY `idx_green_tasks_responsible` (`responsible_id`),
  KEY `idx_green_tasks_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(120) NOT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_green_audit_user` (`user_id`),
  KEY `idx_green_audit_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `normalized_name` varchar(200) NOT NULL,
  `product_type` varchar(80) DEFAULT NULL,
  `accommodation` varchar(80) DEFAULT NULL,
  `coparticipation` tinyint(1) DEFAULT 0,
  `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_plans_operator` (`operator_id`),
  UNIQUE KEY `uk_green_plan_norm` (`operator_id`,`normalized_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_plans`
  ADD COLUMN IF NOT EXISTS `product_type` varchar(80) DEFAULT NULL AFTER `normalized_name`,
  ADD COLUMN IF NOT EXISTS `accommodation` varchar(80) DEFAULT NULL AFTER `product_type`,
  ADD COLUMN IF NOT EXISTS `coparticipation` tinyint(1) DEFAULT 0 AFTER `accommodation`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_code` varchar(50) DEFAULT NULL,
  `lead_id` int(11) NOT NULL,
  `status` enum('Rascunho','Enviada','Aceita','Recusada','Vencida','Cancelada') DEFAULT 'Rascunho',
  `valid_until` date DEFAULT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_quotes_lead` (`lead_id`),
  KEY `idx_green_quotes_status` (`status`),
  KEY `idx_green_quotes_selected_option` (`selected_option_id`),
  KEY `idx_green_quotes_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_quote_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `monthly_value` decimal(12,2) DEFAULT NULL,
  `lives_qty` int(11) DEFAULT NULL,
  `accommodation` varchar(80) DEFAULT NULL,
  `coparticipation` tinyint(1) DEFAULT 0,
  `economy_amount` decimal(12,2) DEFAULT NULL,
  `economy_percent` decimal(8,2) DEFAULT NULL,
  `hospital_match` tinyint(1) DEFAULT 0,
  `highlight_label` varchar(120) DEFAULT NULL,
  `network_notes` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `is_selected` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_quote_options_quote` (`quote_id`),
  KEY `idx_green_quote_options_operator` (`operator_id`),
  KEY `idx_green_quote_options_plan` (`plan_id`),
  KEY `idx_green_quote_options_selected` (`is_selected`),
  KEY `idx_green_quote_options_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_quote_options`
  ADD COLUMN IF NOT EXISTS `accommodation` varchar(80) DEFAULT NULL AFTER `lives_qty`,
  ADD COLUMN IF NOT EXISTS `coparticipation` tinyint(1) DEFAULT 0 AFTER `accommodation`,
  ADD COLUMN IF NOT EXISTS `economy_amount` decimal(12,2) DEFAULT NULL AFTER `coparticipation`,
  ADD COLUMN IF NOT EXISTS `economy_percent` decimal(8,2) DEFAULT NULL AFTER `economy_amount`,
  ADD COLUMN IF NOT EXISTS `hospital_match` tinyint(1) DEFAULT 0 AFTER `economy_percent`,
  ADD COLUMN IF NOT EXISTS `highlight_label` varchar(120) DEFAULT NULL AFTER `hospital_match`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_code` varchar(50) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `plan_name` varchar(200) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `sale_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_commission_multiplier` decimal(10,4) DEFAULT NULL,
  `bonus_amount` decimal(12,2) DEFAULT NULL,
  `legacy_total` decimal(12,2) DEFAULT NULL,
  `implantation_date` date DEFAULT NULL,
  `fidelity_until` date DEFAULT NULL,
  `contract_number` varchar(120) DEFAULT NULL,
  `consultant_id` int(11) DEFAULT NULL,
  `status` enum('Vendida','Implantacao pendente','Implantada','Cancelada','Estornada') DEFAULT 'Vendida',
  `implantation_status` enum('nao_iniciada','pendente','em_andamento','implantada','cancelada') DEFAULT 'nao_iniciada',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_sales_client` (`client_id`),
  KEY `idx_green_sales_lead` (`lead_id`),
  KEY `idx_green_sales_operator` (`operator_id`),
  KEY `idx_green_sales_plan` (`plan_id`),
  KEY `idx_green_sales_date` (`sale_date`),
  KEY `idx_green_sales_fidelity` (`fidelity_until`),
  KEY `idx_green_sales_status` (`status`),
  KEY `idx_green_sales_implantation_status` (`implantation_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_sales`
  ADD COLUMN IF NOT EXISTS `total_commission_multiplier` decimal(10,4) DEFAULT NULL AFTER `sale_value`,
  ADD COLUMN IF NOT EXISTS `bonus_amount` decimal(12,2) DEFAULT NULL AFTER `total_commission_multiplier`,
  ADD COLUMN IF NOT EXISTS `legacy_total` decimal(12,2) DEFAULT NULL AFTER `bonus_amount`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_commission_installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `installment_no` int(11) NOT NULL DEFAULT 1,
  `commission_type` enum('comissao','bonus','ajuste','estorno') NOT NULL DEFAULT 'comissao',
  `due_month` tinyint(2) NOT NULL,
  `due_year` smallint(4) NOT NULL,
  `due_date` date DEFAULT NULL,
  `base_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(10,4) DEFAULT NULL,
  `expected_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `legacy_rate` decimal(10,4) DEFAULT NULL,
  `legacy_amount` decimal(12,2) DEFAULT NULL,
  `legacy_month_name` varchar(40) DEFAULT NULL,
  `received_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(120) DEFAULT NULL,
  `status` enum('Previsto','A receber','Recebido','Parcial','Cancelado','Estornado') NOT NULL DEFAULT 'Previsto',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_commission_sale` (`sale_id`),
  KEY `idx_green_commission_status` (`status`),
  KEY `idx_green_commission_due` (`due_year`,`due_month`),
  KEY `idx_green_commission_type` (`commission_type`),
  KEY `idx_green_commission_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_commission_installments`
  ADD COLUMN IF NOT EXISTS `legacy_rate` decimal(10,4) DEFAULT NULL AFTER `expected_amount`,
  ADD COLUMN IF NOT EXISTS `legacy_amount` decimal(12,2) DEFAULT NULL AFTER `legacy_rate`,
  ADD COLUMN IF NOT EXISTS `legacy_month_name` varchar(40) DEFAULT NULL AFTER `legacy_amount`;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_sale_implantation_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `item_key` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('pendente','concluido','nao_aplica') DEFAULT 'pendente',
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_green_implantation_sale_item` (`sale_id`,`item_key`,`deleted`),
  KEY `idx_green_implantation_sale` (`sale_id`),
  KEY `idx_green_implantation_status` (`status`),
  KEY `idx_green_implantation_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_import_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `source_type` varchar(80) NOT NULL DEFAULT 'crm_vendidos',
  `imported_by` int(11) DEFAULT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `success_rows` int(11) NOT NULL DEFAULT 0,
  `error_rows` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_import_batches_source` (`source_type`),
  KEY `idx_green_import_batches_user` (`imported_by`),
  KEY `idx_green_import_batches_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_import_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `row_number` int(11) NOT NULL,
  `raw_json` longtext DEFAULT NULL,
  `action` varchar(120) DEFAULT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `warning_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_import_rows_batch` (`batch_id`),
  KEY `idx_green_import_rows_row` (`row_number`),
  KEY `idx_green_import_rows_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{dbprefix}green_import_rows`
  ADD COLUMN IF NOT EXISTS `warning_message` text DEFAULT NULL AFTER `error_message`;

-- =====================================================================
-- Green CRM v2 base (leads tracking, tarefas, banco de senhas, anuncios)
-- =====================================================================

-- Leads: rastreabilidade Meta + datas de auditoria/temperatura
ALTER TABLE `{dbprefix}green_leads`
  ADD COLUMN IF NOT EXISTS `desired_plan_type` varchar(120) DEFAULT NULL AFTER `current_plan_name`,
  ADD COLUMN IF NOT EXISTS `campaign_id` varchar(120) DEFAULT NULL AFTER `source_lead_id`,
  ADD COLUMN IF NOT EXISTS `adset_id` varchar(120) DEFAULT NULL AFTER `campaign_id`,
  ADD COLUMN IF NOT EXISTS `ad_id` varchar(120) DEFAULT NULL AFTER `adset_id`,
  ADD COLUMN IF NOT EXISTS `meta_form_id` varchar(120) DEFAULT NULL AFTER `ad_id`,
  ADD COLUMN IF NOT EXISTS `last_interaction_at` datetime DEFAULT NULL AFTER `next_followup_at`,
  ADD COLUMN IF NOT EXISTS `status_changed_at` datetime DEFAULT NULL AFTER `last_interaction_at`,
  ADD COLUMN IF NOT EXISTS `temperature_changed_at` datetime DEFAULT NULL AFTER `status_changed_at`,
  ADD INDEX IF NOT EXISTS `idx_green_leads_campaign` (`campaign_id`),
  ADD INDEX IF NOT EXISTS `idx_green_leads_adset` (`adset_id`),
  ADD INDEX IF NOT EXISTS `idx_green_leads_ad` (`ad_id`),
  ADD INDEX IF NOT EXISTS `idx_green_leads_temperature` (`temperature`);

-- Tarefas: vinculos extras, prioridade, descricao
ALTER TABLE `{dbprefix}green_tasks`
  ADD COLUMN IF NOT EXISTS `client_id` int(11) DEFAULT NULL AFTER `lead_id`,
  ADD COLUMN IF NOT EXISTS `sale_id` int(11) DEFAULT NULL AFTER `client_id`,
  ADD COLUMN IF NOT EXISTS `description` text DEFAULT NULL AFTER `title`,
  ADD COLUMN IF NOT EXISTS `priority` enum('baixa','media','alta','urgente') NOT NULL DEFAULT 'media' AFTER `responsible_id`,
  ADD INDEX IF NOT EXISTS `idx_green_tasks_due` (`due_date`),
  ADD INDEX IF NOT EXISTS `idx_green_tasks_priority` (`priority`),
  ADD INDEX IF NOT EXISTS `idx_green_tasks_client` (`client_id`),
  ADD INDEX IF NOT EXISTS `idx_green_tasks_sale` (`sale_id`);

-- Tarefa pode existir sem lead (vinculada apenas a cliente/venda)
ALTER TABLE `{dbprefix}green_tasks` MODIFY `lead_id` int(11) DEFAULT NULL;

-- Migracao do vocabulario de status (Pendente/Concluida/Cancelada -> aberta/em_andamento/concluida/cancelada)
ALTER TABLE `{dbprefix}green_tasks` MODIFY `status` enum('Pendente','Concluida','Cancelada','aberta','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'aberta';
UPDATE `{dbprefix}green_tasks` SET `status`='aberta' WHERE `status`='Pendente';
UPDATE `{dbprefix}green_tasks` SET `status`='concluida' WHERE `status`='Concluida';
UPDATE `{dbprefix}green_tasks` SET `status`='cancelada' WHERE `status`='Cancelada';
ALTER TABLE `{dbprefix}green_tasks` MODIFY `status` enum('aberta','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'aberta';

-- Banco de senhas (criptografado com o Encryption Service do Rise)
CREATE TABLE IF NOT EXISTS `{dbprefix}green_password_vault` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(120) DEFAULT NULL,
  `system_url` varchar(255) DEFAULT NULL,
  `login_username` varchar(255) DEFAULT NULL,
  `encrypted_password` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `visibility_scope` enum('team','private') NOT NULL DEFAULT 'team',
  `last_rotated_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_green_vault_category` (`category`),
  KEY `idx_green_vault_owner` (`owner_user_id`),
  KEY `idx_green_vault_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gerenciador de anuncios (estrutura + colunas de metricas, preenchiveis depois)
CREATE TABLE IF NOT EXISTS `{dbprefix}green_ad_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `external_id` varchar(120) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `objective` varchar(120) DEFAULT NULL,
  `status` enum('active','paused','archived','unknown') NOT NULL DEFAULT 'unknown',
  `spend` decimal(14,2) DEFAULT NULL,
  `impressions` bigint(20) DEFAULT NULL,
  `reach` bigint(20) DEFAULT NULL,
  `clicks` bigint(20) DEFAULT NULL,
  `leads` int(11) DEFAULT NULL,
  `cpl` decimal(12,2) DEFAULT NULL,
  `ctr` decimal(8,4) DEFAULT NULL,
  `sales_count` int(11) DEFAULT NULL,
  `expected_commission` decimal(14,2) DEFAULT NULL,
  `roi` decimal(12,4) DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_green_ad_campaign_ext` (`external_id`),
  KEY `idx_green_ad_campaign_status` (`status`),
  KEY `idx_green_ad_campaign_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_ad_sets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) DEFAULT NULL,
  `external_id` varchar(120) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','paused','archived','unknown') NOT NULL DEFAULT 'unknown',
  `spend` decimal(14,2) DEFAULT NULL,
  `impressions` bigint(20) DEFAULT NULL,
  `reach` bigint(20) DEFAULT NULL,
  `clicks` bigint(20) DEFAULT NULL,
  `leads` int(11) DEFAULT NULL,
  `cpl` decimal(12,2) DEFAULT NULL,
  `ctr` decimal(8,4) DEFAULT NULL,
  `sales_count` int(11) DEFAULT NULL,
  `expected_commission` decimal(14,2) DEFAULT NULL,
  `roi` decimal(12,4) DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_green_ad_set_ext` (`external_id`),
  KEY `idx_green_ad_set_campaign` (`campaign_id`),
  KEY `idx_green_ad_set_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbprefix}green_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adset_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `external_id` varchar(120) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `creative_thumb_url` varchar(255) DEFAULT NULL,
  `status` enum('active','paused','archived','unknown') NOT NULL DEFAULT 'unknown',
  `spend` decimal(14,2) DEFAULT NULL,
  `impressions` bigint(20) DEFAULT NULL,
  `reach` bigint(20) DEFAULT NULL,
  `clicks` bigint(20) DEFAULT NULL,
  `leads` int(11) DEFAULT NULL,
  `cpl` decimal(12,2) DEFAULT NULL,
  `ctr` decimal(8,4) DEFAULT NULL,
  `sales_count` int(11) DEFAULT NULL,
  `expected_commission` decimal(14,2) DEFAULT NULL,
  `roi` decimal(12,4) DEFAULT NULL,
  `last_synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_green_ad_ext` (`external_id`),
  KEY `idx_green_ad_adset` (`adset_id`),
  KEY `idx_green_ad_campaign` (`campaign_id`),
  KEY `idx_green_ad_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
