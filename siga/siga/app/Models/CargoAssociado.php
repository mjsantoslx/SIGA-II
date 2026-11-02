<?php

namespace App\Models;

use App\Core\Model;

/**
 * Um dirigente pode ter vários cargos em simultâneo — alguns acumulam com
 * outros (regra 34 das regras de negócio). Por enquanto não há nenhuma
 * restrição de incompatibilidade entre cargos específicos implementada
 * (ver nota na regra 34); qualquer combinação é permitida.
 *
 * Segue exactamente o mesmo padrão de OrgaoAssociado: sincronizar() compara
 * a selecção pedida com as ligações activas e só fecha/abre o que mudou.
 */
class CargoAssociado extends Model
{
    protected string $tabela = 'associados_cargos';

    public function listarDoAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT ac.*, c.Designacao
             FROM associados_cargos ac
             INNER JOIN cargos c ON c.Id = ac.IdCargo
             WHERE ac.IdAssociado = :idAssociado AND ac.Activo = 1
             ORDER BY c.Designacao"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }

    private function idsActivos(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT Id, IdCargo FROM associados_cargos WHERE IdAssociado = :idAssociado AND Activo = 1"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        $linhas = $stmt->fetchAll();
        $mapa = [];
        foreach ($linhas as $linha) {
            $mapa[(int) $linha['IdCargo']] = (int) $linha['Id'];
        }
        return $mapa;
    }

    /**
     * @param int[] $idsCargosSeleccionados
     */
    public function sincronizar(int $idAssociado, array $idsCargosSeleccionados, string $dataInicio): void
    {
        $idsCargosSeleccionados = array_map('intval', $idsCargosSeleccionados);
        $activos = $this->idsActivos($idAssociado); // [IdCargo => IdLigacao]

        $this->bd->beginTransaction();
        try {
            foreach ($activos as $idCargo => $idLigacao) {
                if (!in_array($idCargo, $idsCargosSeleccionados, true)) {
                    $stmt = $this->bd->prepare(
                        "UPDATE associados_cargos SET Activo = 0, DataFim = :dataFim WHERE Id = :id"
                    );
                    $stmt->execute(['dataFim' => $dataInicio, 'id' => $idLigacao]);
                }
            }

            foreach ($idsCargosSeleccionados as $idCargo) {
                if (!array_key_exists($idCargo, $activos)) {
                    $this->inserir('associados_cargos', [
                        'IdAssociado' => $idAssociado,
                        'IdCargo'     => $idCargo,
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
