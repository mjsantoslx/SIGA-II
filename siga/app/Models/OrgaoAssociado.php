<?php

namespace App\Models;

use App\Core\Model;

/**
 * Um dirigente pode estar em vários órgãos em simultâneo (ao contrário da
 * companhia local, que é exclusiva). Por isso esta ligação não fecha
 * automaticamente as anteriores — sincronizar() compara a selecção pedida
 * com as ligações activas e só fecha/abre o que realmente mudou.
 */
class OrgaoAssociado extends Model
{
    protected string $tabela = 'associados_orgaos';

    public function listarDoAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT ao.*, o.Designacao
             FROM associados_orgaos ao
             INNER JOIN orgaos o ON o.Id = ao.IdOrgao
             WHERE ao.IdAssociado = :idAssociado AND ao.Activo = 1
             ORDER BY o.Designacao"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }

    private function idsActivos(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT Id, IdOrgao FROM associados_orgaos WHERE IdAssociado = :idAssociado AND Activo = 1"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        $linhas = $stmt->fetchAll();
        $mapa = [];
        foreach ($linhas as $linha) {
            $mapa[(int) $linha['IdOrgao']] = (int) $linha['Id'];
        }
        return $mapa;
    }

    /**
     * Sincroniza os órgãos activos do associado com a selecção pedida:
     * fecha (DataFim/Activo=0) os que deixaram de estar seleccionados e
     * abre novas ligações para os que passaram a estar.
     *
     * @param int[] $idsOrgaosSeleccionados
     */
    public function sincronizar(int $idAssociado, array $idsOrgaosSeleccionados, string $dataInicio): void
    {
        $idsOrgaosSeleccionados = array_map('intval', $idsOrgaosSeleccionados);
        $activos = $this->idsActivos($idAssociado); // [IdOrgao => IdLigacao]

        $this->bd->beginTransaction();
        try {
            // Fechar os que deixaram de estar seleccionados.
            foreach ($activos as $idOrgao => $idLigacao) {
                if (!in_array($idOrgao, $idsOrgaosSeleccionados, true)) {
                    $stmt = $this->bd->prepare(
                        "UPDATE associados_orgaos SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
                    );
                    $stmt->execute(['dataFim' => $dataInicio, 'id' => $idLigacao]);
                }
            }

            // Abrir os novos.
            foreach ($idsOrgaosSeleccionados as $idOrgao) {
                if (!array_key_exists($idOrgao, $activos)) {
                    $this->inserir('associados_orgaos', [
                        'IdAssociado' => $idAssociado,
                        'IdOrgao'     => $idOrgao,
                        'DataInicio'  => $dataInicio,
                        'Activo'      => 1,
                    ]);
                }
            }

            $this->bd->commit();
        } catch (\Throwable $e) {
            $this->bd->rollBack();
            throw $e;
        }
    }
}
