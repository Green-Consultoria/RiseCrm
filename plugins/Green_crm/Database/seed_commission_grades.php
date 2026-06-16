<?php

/**
 * Seed inicial das grades de comissao Ramed e Serra.
 *
 * IMPORTANTE: os PDFs de origem nao acompanham o repositorio, portanto os
 * multiplicadores abaixo sao VALORES DE EXEMPLO ("revisar") para dar estrutura.
 * Ajuste os percentuais reais pela tela de Grades de comissao.
 *
 * "total" e o multiplicador decimal total (ex.: 3.5 = 350%). A distribuicao das
 * parcelas e gerada automaticamente por green_crm_distribute_multiplier().
 */

return [
    [
        "grade" => [
            "name" => "Ramed",
            "partner_name" => "Ramed",
            "description" => "Grade de comissao Ramed (cadastro inicial - revisar valores)."
        ],
        "version" => [
            "version_name" => "Junho 2026 - 03062026",
            "valid_from" => "2026-06-03",
            "valid_until" => null,
            "source_file_name" => "03062026 - GRADE DE COMISSAO.pdf",
            "notes" => "Seed inicial com valores de exemplo - revisar conforme PDF."
        ],
        "rules" => [
            ["operator" => "BRADESCO", "product_name" => "Individual", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 3.0],
            ["operator" => "BRADESCO", "product_name" => "PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 3.5],
            ["operator" => "BRADESCO", "product_name" => "Odontológico Individual", "product_type" => "Odonto", "lives_range_text" => "1 vida", "total" => 1.0],
            ["operator" => "BRADESCO", "product_name" => "Odontológico PME", "product_type" => "Odonto PME", "lives_range_text" => "2+ vidas", "total" => 1.2],
            ["operator" => "PORTO SEGURO", "product_name" => "PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 3.2],
            ["operator" => "SULAMÉRICA", "product_name" => "Individual", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 3.0],
            ["operator" => "AMIL", "product_name" => "Individual", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 2.8],
            ["operator" => "ALICE", "product_name" => "Individual", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 2.5],
            ["operator" => "HAPVIDA", "product_name" => "PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 2.0],
            ["operator" => "OMINT", "product_name" => "Individual", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 3.0],
            ["operator" => "MEDSENIOR PF", "product_name" => "Sênior", "product_type" => "Sênior", "lives_range_text" => "1 vida", "total" => 2.6],
            ["operator" => "CAREPLUS", "product_name" => "PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 2.4],
            ["operator" => "SAMI", "product_name" => "Individual", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 1.8],
            ["operator" => "PREVENT SENIOR", "product_name" => "Sênior", "product_type" => "Sênior", "lives_range_text" => "1 vida", "total" => 2.2],
            ["operator" => "SEGUROS UNIMED", "product_name" => "PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 2.3],
            ["operator" => "", "product_name" => "Administradoras", "product_type" => "Adesão", "lives_range_text" => "-", "total" => 1.5]
        ]
    ],
    [
        "grade" => [
            "name" => "Serra",
            "partner_name" => "Serra",
            "description" => "Grade de comissao Serra (cadastro inicial - revisar valores)."
        ],
        "version" => [
            "version_name" => "Maio 2026",
            "valid_from" => "2026-05-01",
            "valid_until" => null,
            "source_file_name" => "Tabela de Comissoes - Repasse - Maio 2026.pdf",
            "notes" => "Seed inicial com valores de exemplo - revisar conforme PDF."
        ],
        "rules" => [
            ["operator" => "BRADESCO", "product_name" => "Principais Comissões", "product_type" => "Saúde", "lives_range_text" => "-", "total" => 3.4],
            ["operator" => "PORTO SEGURO", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 3.0],
            ["operator" => "SULAMÉRICA", "product_name" => "Planos Individuais", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 2.9],
            ["operator" => "AMIL", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 2.7],
            ["operator" => "ALICE", "product_name" => "Planos Individuais", "product_type" => "Saúde", "lives_range_text" => "1 vida", "total" => 2.4],
            ["operator" => "HAPVIDA", "product_name" => "Adesões", "product_type" => "Adesão", "lives_range_text" => "-", "total" => 1.8],
            ["operator" => "UNIHOSP", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 1.6],
            ["operator" => "TRASMONTANO", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 1.5],
            ["operator" => "PLENA SAÚDE", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 1.4],
            ["operator" => "SÃO MIGUEL", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 1.5],
            ["operator" => "SÃO CRISTÓVÃO", "product_name" => "Planos PME", "product_type" => "PME", "lives_range_text" => "2 a 29 vidas", "total" => 1.6],
            ["operator" => "BRADESCO", "product_name" => "Odonto PF", "product_type" => "Odonto", "lives_range_text" => "1 vida", "total" => 0.9],
            ["operator" => "BRADESCO", "product_name" => "Odonto PME", "product_type" => "Odonto PME", "lives_range_text" => "2+ vidas", "total" => 1.1],
            ["operator" => "", "product_name" => "Plano PET", "product_type" => "PET", "lives_range_text" => "-", "total" => 1.0]
        ]
    ]
];
