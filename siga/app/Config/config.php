<?php
/**
 * Configuração central da aplicação SIGA.
 * Em produção, estes valores devem vir de variáveis de ambiente
 * (ex.: getenv('DB_PASS')) e nunca ficar em texto simples no repositório.
 */

return [
    'app' => [
        'nome'      => 'SIGA - Sistema Integrado de Gestão de Associados',
        'sigla'     => 'SIGA - UEP',
        // Ajustar consoante a pasta/subdomínio onde o public/ for publicado.
        'base_url'  => '/',
        'timezone'  => 'Europe/Lisbon',
    ],

    'db' => [
        'host'    => getenv('SIGA_DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('SIGA_DB_PORT') ?: '3306',
        'name'    => getenv('SIGA_DB_NAME') ?: 'siga',
        'user'    => getenv('SIGA_DB_USER') ?: 'siga_app',
        'pass'    => getenv('SIGA_DB_PASS') ?: 'alterar_esta_password',
        'charset' => 'utf8mb4',
    ],

    'sessao' => [
        'nome'                => 'SIGA_SESSAO',
        'tempo_vida_minutos'  => 120,
    ],
];
