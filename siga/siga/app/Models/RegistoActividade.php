<?php

namespace App\Models;

use App\Core\Model;

/**
 * Consulta do registo de actividade (só leitura — o registo nunca é
 * alterado nem eliminado pela aplicação).
 */
class RegistoActividade extends Model
{
    protected string $tabela = 'registo_actividade';

    public function pesquisar(array $filtros, int $limite, int $deslocamento): array
    {
        [$onde, $parametros] = $this->construirFiltro($filtros);

        $stmt = $this->bd->prepare(
            "SELECT * FROM registo_actividade {$onde}
             ORDER BY DataHora DESC, Id DESC
             LIMIT {$limite} OFFSET {$deslocamento}"
        );
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public function contar(array $filtros): int
    {
        [$onde, $parametros] = $this->construirFiltro($filtros);
        $stmt = $this->bd->prepare("SELECT COUNT(*) FROM registo_actividade {$onde}");
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Utilizadores que já aparecem no registo (para o filtro).
     */
    public function utilizadoresRegistados(): array
    {
        return $this->bd->query(
            'SELECT IdUtilizador, MAX(NomeUtilizador) AS Nome
             FROM registo_actividade
             WHERE IdUtilizador IS NOT NULL
             GROUP BY IdUtilizador
             ORDER BY Nome'
        )->fetchAll();
    }

    private function construirFiltro(array $f): array
    {
        $condicoes = [];
        $p = [];

        if ($f['idUtilizador'] !== '') {
            $condicoes[] = 'IdUtilizador = :idu';
            $p['idu'] = (int) $f['idUtilizador'];
        }
        if ($f['accao'] !== '') {
            $condicoes[] = 'Accao = :accao';
            $p['accao'] = $f['accao'];
        }
        if ($f['resultado'] !== '') {
            $condicoes[] = 'Resultado = :res';
            $p['res'] = $f['resultado'];
        }
        if ($f['de'] !== '') {
            $condicoes[] = 'DataHora >= :de';
            $p['de'] = $f['de'] . ' 00:00:00';
        }
        if ($f['ate'] !== '') {
            $condicoes[] = 'DataHora <= :ate';
            $p['ate'] = $f['ate'] . ' 23:59:59';
        }
        if ($f['pesquisa'] !== '') {
            $condicoes[] = '(Descricao LIKE :q OR NomeUtilizador LIKE :q)';
            $p['q'] = '%' . addcslashes($f['pesquisa'], '%_\\') . '%';
        }

        return [$condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '', $p];
    }
}
