<?php

namespace App\Core;

/**
 * Gera cabeçalhos de coluna clicáveis para ordenação de listagens,
 * preservando os restantes filtros/parâmetros activos no URL.
 */
class Tabela
{
    public static function cabecalhoOrdenavel(
        string $coluna,
        string $rotulo,
        string $ordenarActual,
        string $direcaoActual,
        array $parametrosExtra = []
    ): string {
        $novaDirecao = ($ordenarActual === $coluna && $direcaoActual === 'asc') ? 'desc' : 'asc';
        $parametros = array_merge($parametrosExtra, ['ordenar' => $coluna, 'direcao' => $novaDirecao]);
        $query = http_build_query($parametros);

        $seta = '';
        if ($ordenarActual === $coluna) {
            $seta = $direcaoActual === 'asc' ? ' ▲' : ' ▼';
        }

        return '<a href="?' . htmlspecialchars($query) . '" class="ligacao-ordenacao">'
            . htmlspecialchars($rotulo) . $seta . '</a>';
    }

    /**
     * Ordena um array associativo (ex.: resultado de fetchAll()) por uma
     * chave, com suporte a acesso aninhado via "->" (ex.: "Morada->Morada")
     * para colunas calculadas depois da consulta principal. Usado nas
     * listagens pequenas (companhias, utilizadores) que combinam dados de
     * várias fontes em PHP.
     */
    public static function ordenarArray(array $linhas, string $chave, string $direcao): array
    {
        $partes = explode('->', $chave);

        $valorDe = static function ($linha) use ($partes) {
            $valor = $linha;
            foreach ($partes as $parte) {
                if (is_array($valor) && array_key_exists($parte, $valor)) {
                    $valor = $valor[$parte];
                } else {
                    return null;
                }
            }
            return $valor;
        };

        usort($linhas, function ($a, $b) use ($valorDe, $direcao) {
            $valorA = $valorDe($a);
            $valorB = $valorDe($b);

            if ($valorA === $valorB) {
                return 0;
            }
            if ($valorA === null) {
                return 1;
            }
            if ($valorB === null) {
                return -1;
            }

            $comparacao = is_numeric($valorA) && is_numeric($valorB)
                ? ($valorA <=> $valorB)
                : strcasecmp((string) $valorA, (string) $valorB);

            return $direcao === 'desc' ? -$comparacao : $comparacao;
        });

        return $linhas;
    }

    /**
     * Valida e normaliza um par (coluna, direcção) vindo do URL contra uma
     * lista de colunas permitidas, devolvendo sempre valores seguros.
     */
    public static function normalizar(string $ordenarPedido, string $direcaoPedida, array $colunasPermitidas, string $colunaOmissao): array
    {
        $coluna = in_array($ordenarPedido, $colunasPermitidas, true) ? $ordenarPedido : $colunaOmissao;
        $direcao = $direcaoPedida === 'desc' ? 'desc' : 'asc';
        return [$coluna, $direcao];
    }
}
