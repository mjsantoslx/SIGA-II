<?php

namespace App\Models;

use App\Core\Model;

/**
 * Registo de pagamento do Censo por associado e por ano escotista.
 * Membros honorários (regra 51) nunca aparecem nestas listas — não pagam
 * Censos. Cada acção de marcar pago/anular fica registada em
 * censos_historico (ver CensoHistorico), não só sobreposta aqui.
 */
class Censo extends Model
{
    protected string $tabela = 'censos_associados';

    /**
     * Lista os associados abrangidos pelo Censo (activos, não honorários)
     * de uma companhia (ou de todas, se $idCompanhiaRestricao for null),
     * com o respectivo estado de pagamento para o ano escotista indicado.
     * Um associado sem linha em censos_associados aparece como não pago.
     */
    public function listarParaAno(int $idAnoEscotista, ?int $idCompanhiaRestricao): array
    {
        $sql = "
            SELECT
                a.Id AS IdAssociado, a.NumeroAssociado, p.Nome,
                companhiaActual.Designacao AS CompanhiaActual,
                c.Id AS IdCenso, COALESCE(c.Pago, 0) AS Pago, c.DataPagamento
            FROM associados a
            INNER JOIN pessoas p ON p.Id = a.IdPessoa
            LEFT JOIN (
                SELECT ac.IdAssociado, comp.Designacao, comp.Id AS IdCompanhia
                FROM associados_companhias ac
                INNER JOIN companhias comp ON comp.Id = ac.IdCompanhia
                WHERE ac.Activo = 1 AND comp.ambito_global = 0
            ) companhiaActual ON companhiaActual.IdAssociado = a.Id
            LEFT JOIN censos_associados c ON c.IdAssociado = a.Id AND c.IdAnoEscotista = :idAnoEscotista
            WHERE a.Activo = 1 AND a.MembroHonorario = 0
        ";
        $parametros = ['idAnoEscotista' => $idAnoEscotista];

        if ($idCompanhiaRestricao !== null) {
            $sql .= " AND companhiaActual.IdCompanhia = :idCompanhia";
            $parametros['idCompanhia'] = $idCompanhiaRestricao;
        }

        $sql .= " ORDER BY p.Nome";

        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    private function encontrarOuCriarLinha(int $idAssociado, int $idAnoEscotista): int
    {
        $stmt = $this->bd->prepare(
            "SELECT Id FROM censos_associados WHERE IdAssociado = :idAssociado AND IdAnoEscotista = :idAnoEscotista"
        );
        $stmt->execute(['idAssociado' => $idAssociado, 'idAnoEscotista' => $idAnoEscotista]);
        $id = $stmt->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        return $this->inserir('censos_associados', [
            'IdAssociado'    => $idAssociado,
            'IdAnoEscotista' => $idAnoEscotista,
            'Pago'           => 0,
            'DataPagamento'  => null,
        ]);
    }

    public function marcarPago(int $idAssociado, int $idAnoEscotista, string $dataPagamento, int $idUtilizador): void
    {
        $this->bd->beginTransaction();
        try {
            $idCenso = $this->encontrarOuCriarLinha($idAssociado, $idAnoEscotista);

            $this->actualizar('censos_associados', [
                'Pago'          => 1,
                'DataPagamento' => $dataPagamento,
            ], 'Id', $idCenso);

            (new CensoHistorico())->registar($idCenso, $idAssociado, $idAnoEscotista, $idUtilizador, 'PAGAMENTO', $dataPagamento);

            $this->bd->commit();
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    public function marcarNaoPago(int $idAssociado, int $idAnoEscotista, int $idUtilizador): void
    {
        $this->bd->beginTransaction();
        try {
            $idCenso = $this->encontrarOuCriarLinha($idAssociado, $idAnoEscotista);

            $this->actualizar('censos_associados', [
                'Pago'          => 0,
                'DataPagamento' => null,
            ], 'Id', $idCenso);

            (new CensoHistorico())->registar($idCenso, $idAssociado, $idAnoEscotista, $idUtilizador, 'ANULACAO', null);

            $this->bd->commit();
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }

    public function contarPagos(int $idAnoEscotista, ?int $idCompanhiaRestricao): array
    {
        $linhas = $this->listarParaAno($idAnoEscotista, $idCompanhiaRestricao);
        $total = count($linhas);
        $pagos = count(array_filter($linhas, fn($l) => (int) $l['Pago'] === 1));
        return ['total' => $total, 'pagos' => $pagos, 'porPagar' => $total - $pagos];
    }
}
