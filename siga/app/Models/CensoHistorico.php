<?php

namespace App\Models;

use App\Core\Model;

/**
 * Histórico de pagamentos de Censos: cada acção (marcar pago / anular)
 * fica registada, para se poder ver a evolução completa, não só o estado
 * actual em censos_associados.
 */
class CensoHistorico extends Model
{
    protected string $tabela = 'censos_historico';

    public function registar(
        int $idCenso,
        int $idAssociado,
        int $idAnoEscotista,
        int $idUtilizador,
        string $operacao,
        ?string $dataPagamento
    ): int {
        return $this->inserir('censos_historico', [
            'IdCenso'        => $idCenso,
            'IdAssociado'    => $idAssociado,
            'IdAnoEscotista' => $idAnoEscotista,
            'IdUtilizador'   => $idUtilizador,
            'Operacao'       => $operacao,
            'DataPagamento'  => $dataPagamento,
        ]);
    }

    public function listarPorAssociadoEAno(int $idAssociado, int $idAnoEscotista): array
    {
        $stmt = $this->bd->prepare(
            "SELECT h.*, u.Nome AS NomeUtilizador
             FROM censos_historico h
             LEFT JOIN utilizadores u ON u.Id = h.IdUtilizador
             WHERE h.IdAssociado = :idAssociado AND h.IdAnoEscotista = :idAnoEscotista
             ORDER BY h.DataHora DESC, h.Id DESC"
        );
        $stmt->execute(['idAssociado' => $idAssociado, 'idAnoEscotista' => $idAnoEscotista]);
        return $stmt->fetchAll();
    }
}
