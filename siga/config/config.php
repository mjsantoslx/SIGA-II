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
        // Actualizado a cada nova versão entregue — aparece no rodapé.
        'versao'    => 'v01.27',
    ],

    'db' => [
        'host'    => getenv('SIGA_DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('SIGA_DB_PORT') ?: '3306',
        'name'    => getenv('SIGA_DB_NAME') ?: 'siga',
        'user'    => getenv('SIGA_DB_USER') ?: 'usrSiga',
        'pass'    => getenv('SIGA_DB_PASS') ?: 'sigaUsr',
        'charset' => 'utf8mb4',
    ],

    'sessao' => [
        'nome'                => 'SIGA_SESSAO',
        'tempo_vida_minutos'  => 120,
    ],

    'documentos' => [
        // Regra 6: o número do Cartão de Cidadão / documento de identificação
        // deve ser tratado sempre como texto e, quando necessário, completado
        // à esquerda com zeros até este número de algarismos.
        // ⚠️ POR CONFIRMAR: ainda não foi validada a largura exacta usada na
        // versão anterior do SIGA. Enquanto for `null`, a aplicação NÃO aplica
        // qualquer padding automático (o número é guardado exactamente como
        // foi introduzido). Assim que confirmado, defina aqui o valor (ex.: 8)
        // — App\Core\Documentos::preencherComZeros() passa a usá-lo em toda a
        // aplicação sem mais nenhuma alteração de código necessária.
        'largura_cc' => null,
    ],
];
