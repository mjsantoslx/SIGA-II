<?php

namespace App\Models;

use App\Core\Model;

/**
 * Consultas dos relatórios (só leitura). Todas aceitam um filtro de
 * companhia: null = todas; inteiro = essa companhia local; 'sem' = associados
 * sem companhia local (Chefia Nacional / órgãos).
 */
class Relatorio extends Model
{
    /** Subconsultas comuns: divisão actual e companhia local actual. */
    private const JUNCOES = "
        INNER JOIN pessoas p ON p.Id = a.IdPessoa
        LEFT JOIN (
            SELECT s.IdAssociado, sec.Designacao, sec.Id AS IdSecao
            FROM associados_secoes s
            INNER JOIN secoes sec ON sec.Id = s.IdSecao
            WHERE s.Activo = 1
        ) secaoActual ON secaoActual.IdAssociado = a.Id
        LEFT JOIN (
            SELECT ac.IdAssociado, c.Designacao, c.Id AS IdCompanhia
            FROM associados_companhias ac
            INNER JOIN companhias c ON c.Id = ac.IdCompanhia
            WHERE ac.Activo = 1 AND c.ambito_global = 0
        ) companhiaActual ON companhiaActual.IdAssociado = a.Id
    ";

    private function condicaoCompanhia(int|string|null $idCompanhia, array &$parametros): ?string
    {
        if ($idCompanhia === null || $idCompanhia === '') {
            return null;
        }
        if ($idCompanhia === 'sem') {
            return 'companhiaActual.IdCompanhia IS NULL';
        }
        $parametros['idCompanhia'] = (int) $idCompanhia;
        return 'companhiaActual.IdCompanhia = :idCompanhia';
    }

    private function subContacto(string $designacao): string
    {
        return "(SELECT c.Valor FROM contactos c
                 INNER JOIN tipos_contacto t ON t.Id = c.IdTipoContacto
                 WHERE c.IdPessoa = a.IdPessoa AND t.Designacao = '{$designacao}'
                 ORDER BY c.Id LIMIT 1)";
    }

    /**
     * Listagem de associados. $filtros: idSecao, estado ('1'|'0'|''),
     * honorarios ('incluir'|'excluir'|'so').
     */
    public function associados(int|string|null $idCompanhia, array $filtros): array
    {
        $condicoes = [];
        $p = [];

        if (($c = $this->condicaoCompanhia($idCompanhia, $p)) !== null) {
            $condicoes[] = $c;
        }
        if (!empty($filtros['idSecao'])) {
            $condicoes[] = 'secaoActual.IdSecao = :idSecao';
            $p['idSecao'] = (int) $filtros['idSecao'];
        }
        if (($filtros['estado'] ?? '') !== '') {
            $condicoes[] = 'a.Activo = :activo';
            $p['activo'] = (int) $filtros['estado'];
        }
        if (($filtros['honorarios'] ?? 'incluir') === 'excluir') {
            $condicoes[] = 'a.MembroHonorario = 0';
        } elseif (($filtros['honorarios'] ?? '') === 'so') {
            $condicoes[] = 'a.MembroHonorario = 1';
        }

        $onde = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

        $stmt = $this->bd->prepare("
            SELECT a.Id, a.NumeroAssociado, p.Nome, a.DataNascimento, a.NIF, a.Activo, a.MembroHonorario,
                   secaoActual.Designacao AS Divisao,
                   companhiaActual.Designacao AS Companhia,
                   {$this->subContacto('Telemóvel')} AS Telemovel,
                   {$this->subContacto('Email')} AS Email,
                   {$this->subContacto('Email Associativo')} AS EmailAssociativo
            FROM associados a
            " . self::JUNCOES . "
            {$onde}
            ORDER BY p.Nome
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    /**
     * Efectivo (activos, sem honorários — regra 51) por companhia e divisão.
     * Devolve linhas [Companhia (ou null), Divisao (ou null), Total].
     */
    public function efectivoPorCompanhiaEDivisao(int|string|null $idCompanhia): array
    {
        $condicoes = ['a.Activo = 1', 'a.MembroHonorario = 0'];
        $p = [];
        if (($c = $this->condicaoCompanhia($idCompanhia, $p)) !== null) {
            $condicoes[] = $c;
        }

        $stmt = $this->bd->prepare("
            SELECT companhiaActual.Designacao AS Companhia, secaoActual.Designacao AS Divisao, COUNT(*) AS Total
            FROM associados a
            " . self::JUNCOES . "
            WHERE " . implode(' AND ', $condicoes) . "
            GROUP BY companhiaActual.Designacao, secaoActual.Designacao
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public function divisoes(): array
    {
        return $this->bd->query('SELECT Id, Designacao FROM secoes ORDER BY Id')->fetchAll();
    }

    /**
     * Cargos e órgãos actuais de associados activos.
     * $tipo: '' (ambos) | 'cargo' | 'orgao'.
     */
    public function dirigentes(int|string|null $idCompanhia, string $tipo): array
    {
        $p = [];
        $filtroCompanhia = $this->condicaoCompanhia($idCompanhia, $p);
        $onde = 'WHERE a.Activo = 1' . ($filtroCompanhia ? ' AND ' . $filtroCompanhia : '');

        $namespace = fn(string $tabelaLigacao, string $tabelaRef, string $colId, string $rotulo) => "
            SELECT p.Nome, a.NumeroAssociado, companhiaActual.Designacao AS Companhia,
                   secaoActual.Designacao AS Divisao,
                   (SELECT 1 FROM associados_companhias acn
                    INNER JOIN companhias cn ON cn.Id = acn.IdCompanhia
                    WHERE acn.IdAssociado = a.Id AND acn.Activo = 1 AND cn.ambito_global = 1
                    LIMIT 1) AS NaChefiaNacional,
                   '{$rotulo}' AS Tipo, r.Designacao AS Designacao, l.DataInicio AS DataInicio
            FROM {$tabelaLigacao} l
            INNER JOIN {$tabelaRef} r ON r.Id = l.{$colId}
            INNER JOIN associados a ON a.Id = l.IdAssociado
            " . self::JUNCOES . "
            {$onde} AND l.Activo = 1
        ";

        $partes = [];
        if ($tipo !== 'orgao') {
            $partes[] = $namespace('associados_cargos', 'cargos', 'IdCargo', 'Cargo');
        }
        if ($tipo !== 'cargo') {
            $partes[] = $namespace('associados_orgaos', 'orgaos', 'IdOrgao', 'Órgão');
        }

        // Os parâmetros repetem-se em cada parte do UNION — nomes únicos por parte.
        $sql = '';
        $parametros = [];
        foreach ($partes as $i => $parte) {
            if ($i > 0) {
                $sql .= ' UNION ALL ';
            }
            $parteSql = $parte;
            foreach ($p as $nome => $valor) {
                $parteSql = str_replace(':' . $nome, ':' . $nome . '_' . $i, $parteSql);
                $parametros[$nome . '_' . $i] = $valor;
            }
            $sql .= $parteSql;
        }
        $sql = "SELECT * FROM ({$sql}) x ORDER BY Tipo, Designacao, Nome";

        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }
}
