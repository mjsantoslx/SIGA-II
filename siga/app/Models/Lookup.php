<?php

namespace App\Models;

use App\Core\Database;

/**
 * Acesso genérico e seguro às tabelas de referência (listas de escolha) do SIGA:
 * nacionalidades, estados_civis, confissoes_religiosas, tipos_documento_identificacao,
 * tipos_contacto, tipos_relacao, tipos_evento.
 *
 * As tabelas permitidas estão numa lista fechada para evitar SQL injection
 * através do nome da tabela (que não pode ser parametrizado via PDO).
 */
class Lookup
{
    private const TABELAS_PERMITIDAS = [
        'nacionalidades'                 => 'Nacionalidade',
        'estados_civis'                  => 'Designacao',
        'confissoes_religiosas'          => 'Designacao',
        'tipos_documento_identificacao'  => 'Designacao',
        'tipos_contacto'                 => 'Designacao',
        'tipos_relacao'                  => 'Designacao',
        'tipos_evento'                   => 'Designacao',
    ];

    public static function listar(string $tabela): array
    {
        if (!array_key_exists($tabela, self::TABELAS_PERMITIDAS)) {
            throw new \InvalidArgumentException("Tabela de referência não permitida: {$tabela}");
        }

        $coluna = self::TABELAS_PERMITIDAS[$tabela];

        // Regra: na lista de nacionalidades, "Portuguesa" aparece sempre primeiro.
        $ordem = $tabela === 'nacionalidades'
            ? "ORDER BY ({$coluna} != 'Portuguesa'), {$coluna}"
            : "ORDER BY {$coluna}";

        $stmt = Database::ligacao()->query("SELECT Id, {$coluna} AS Designacao FROM {$tabela} {$ordem}");
        return $stmt->fetchAll();
    }
}
