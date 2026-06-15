-- Status do funil v2 (operacionais). Os status legados nao listados aqui
-- (Em qualificacao, Proposta enviada, Proposta aceita) NAO sao removidos.
INSERT INTO `{dbprefix}green_lead_statuses` (`title`, `sort`, `is_final`, `is_won`, `is_lost`, `deleted`) VALUES
('Novo', 1, 0, 0, 0, 0),
('Primeiro contato', 2, 0, 0, 0, 0),
('Em atendimento', 3, 0, 0, 0, 0),
('Qualificado', 4, 0, 0, 0, 0),
('Aguardando documentos', 5, 0, 0, 0, 0),
('Aguardando estudo/cotação', 6, 0, 0, 0, 0),
('Cotação enviada', 7, 0, 0, 0, 0),
('Cliente analisando', 8, 0, 0, 0, 0),
('Negociação', 9, 0, 0, 0, 0),
('Vendido', 10, 1, 1, 0, 0),
('Perdido', 11, 1, 0, 1, 0),
('Implantação pendente', 12, 0, 0, 0, 0),
('Implantado', 13, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE
  `sort` = VALUES(`sort`),
  `is_final` = VALUES(`is_final`),
  `is_won` = VALUES(`is_won`),
  `is_lost` = VALUES(`is_lost`),
  `deleted` = 0;

INSERT INTO `{dbprefix}green_sources` (`id`, `title`, `deleted`) VALUES
(1, 'Manual', 0),
(2, 'Excel legado', 0),
(3, 'WhatsApp', 0),
(4, 'Indicação', 0),
(5, 'Meta Ads', 0),
(6, 'Site', 0),
(7, 'Outro', 0)
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `deleted` = 0;

UPDATE `{dbprefix}green_sources` SET `deleted` = 1 WHERE `id` NOT IN (1,2,3,4,5,6,7);

INSERT INTO `{dbprefix}green_operators` (`id`, `name`, `normalized_name`, `aliases`, `deleted`) VALUES
(1, 'AMIL', 'AMIL', 'AML', 0),
(2, 'PORTO SEGURO', 'PORTO SEGURO', 'PORTO,PORTOSEG', 0),
(3, 'BRADESCO', 'BRADESCO', '', 0),
(4, 'ALICE', 'ALICE', '', 0),
(5, 'SULAMÉRICA', 'SULAMERICA', 'SUL AMERICA,SULAMERICA,SULAMÉRICA', 0),
(6, 'MEDSENIOR PF', 'MEDSENIOR PF', '', 0)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `normalized_name` = VALUES(`normalized_name`),
  `aliases` = VALUES(`aliases`),
  `deleted` = 0;

UPDATE `{dbprefix}green_operators` SET `deleted` = 1 WHERE `id` NOT IN (1,2,3,4,5,6);

INSERT INTO `{dbprefix}green_plans` (`operator_id`, `name`, `normalized_name`, `deleted`) VALUES
(1, 'S750', 'S750', 0),
(1, 'BRONZE SP', 'BRONZE SP', 0),
(1, 'PRATA', 'PRATA', 0),
(2, 'PORTOMED', 'PORTOMED', 0),
(2, 'P320', 'P320', 0),
(2, 'PRATA PRO', 'PRATA PRO', 0),
(3, 'EFETIVO PLUS', 'EFETIVO PLUS', 0),
(3, 'NACIONAL III', 'NACIONAL III', 0),
(4, 'SUPER CONFORTO', 'SUPER CONFORTO', 0),
(4, 'EQUILIBRIO / CONFORTO / SUPER CONFORTO', 'EQUILIBRIO / CONFORTO / SUPER CONFORTO', 0),
(5, 'ESPECIAL 100', 'ESPECIAL 100', 0),
(5, 'ESPECIAL VITAL', 'ESPECIAL VITAL', 0),
(6, 'BLACK', 'BLACK', 0)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `deleted` = 0;
