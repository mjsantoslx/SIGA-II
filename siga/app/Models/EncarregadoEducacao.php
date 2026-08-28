<?php

namespace App\Models;

use App\Core\Model;

class EncarregadoEducacao extends Model
{
    protected string $tabela = 'associados_encarregados_educacao';

    public function criar(int $idAssociado, int $idPessoa, int $idTipoRelacao, string $dataInicio): int
    {
        return $this->inserir('associados_encarregados_educacao', [
            'IdAssociado'    => $idAssociado,
            'IdPessoa'       => $idPessoa,
            'IdTipoRelacao'  => $idTipoRelacao,
            'DataInicio'     => $dataInicio,
            'Activo'         => 1,
        ]);
    }

    public function listarDoAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT ae.*, p.Nome, tr.Designacao AS TipoRelacao
             FROM associados_encarregados_educacao ae
             INNER JOIN pessoas p ON p.Id = ae.IdPessoa
             INNER JOIN tipos_relacao tr ON tr.Id = ae.IdTipoRelacao
             WHERE ae.IdAssociado = :idAssociado AND ae.Activo = 1
             ORDER BY ae.DataInicio"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }
}
