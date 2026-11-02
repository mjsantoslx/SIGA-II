<?php

namespace App\Models;

use App\Core\Model;

/**
 * Regra 17.1: um contacto de emergência não depende obrigatoriamente da
 * existência de outra pessoa registada no SIGA — por isso guarda Nome e
 * Contacto directamente, em vez de uma referência a "pessoas".
 */
class ContactoEmergencia extends Model
{
    protected string $tabela = 'associados_contactos_emergencia';

    public function criar(int $idAssociado, string $nome, ?string $contacto, int $idTipoRelacao): int
    {
        return $this->inserir('associados_contactos_emergencia', [
            'IdAssociado'   => $idAssociado,
            'Nome'          => $nome,
            'Contacto'      => $contacto ?: null,
            'IdTipoRelacao' => $idTipoRelacao,
            'Activo'        => 1,
        ]);
    }

    public function listarDoAssociado(int $idAssociado): array
    {
        $stmt = $this->bd->prepare(
            "SELECT ce.*, tr.Designacao AS TipoRelacao
             FROM associados_contactos_emergencia ce
             INNER JOIN tipos_relacao tr ON tr.Id = ce.IdTipoRelacao
             WHERE ce.IdAssociado = :idAssociado AND ce.Activo = 1"
        );
        $stmt->execute(['idAssociado' => $idAssociado]);
        return $stmt->fetchAll();
    }
}
