<?php

namespace App\Models;

use App\Core\Model;

class EventoAssociado extends Model
{
    protected string $tabela = 'eventos_associados';

    public function registar(int $idAssociado, int $idTipoEvento, string $dataEvento, ?string $observacoes = null): int
    {
        return $this->inserir('eventos_associados', [
            'IdAssociado'   => $idAssociado,
            'IdTipoEvento'  => $idTipoEvento,
            'DataEvento'    => $dataEvento,
            'Observacoes'   => $observacoes,
        ]);
    }

    public function listarDoAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT ea.*, te.Designacao AS TipoEvento
             FROM eventos_associados ea
             INNER JOIN tipos_evento te ON te.Id = ea.IdTipoEvento
             WHERE ea.IdAssociado = :idAssociado
             ORDER BY ea.DataEvento DESC, ea.Id DESC"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }

    public function idTipoEventoPorDesignacao(string $designacao): ?int
    {
        $stmt = $this->bd->prepare("SELECT Id FROM tipos_evento WHERE Designacao = :d LIMIT 1");
        $stmt->execute(['d' => $designacao]);
        $registo = $stmt->fetch();
        return $registo ? (int) $registo['Id'] : null;
    }
}
