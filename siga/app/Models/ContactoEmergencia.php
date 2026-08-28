<?php

namespace App\Models;

use App\Core\Model;

class ContactoEmergencia extends Model
{
    protected string $tabela = 'associados_contactos_emergencia';

    public function criar(int $idAssociado, int $idPessoa, int $idTipoRelacao): int
    {
        return $this->inserir('associados_contactos_emergencia', [
            'IdAssociado'   => $idAssociado,
            'IdPessoa'      => $idPessoa,
            'IdTipoRelacao' => $idTipoRelacao,
            'Activo'        => 1,
        ]);
    }

    public function listarDoAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT ce.*, p.Nome, tr.Designacao AS TipoRelacao
             FROM associados_contactos_emergencia ce
             INNER JOIN pessoas p ON p.Id = ce.IdPessoa
             INNER JOIN tipos_relacao tr ON tr.Id = ce.IdTipoRelacao
             WHERE ce.IdAssociado = :idAssociado AND ce.Activo = 1"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }
}
