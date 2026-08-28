<?php

namespace App\Models;

use App\Core\Model;

class Contacto extends Model
{
    protected string $tabela = 'contactos';

    public function criar(int $idPessoa, int $idTipoContacto, string $valor): int
    {
        return $this->inserir('contactos', [
            'IdPessoa'       => $idPessoa,
            'IdTipoContacto' => $idTipoContacto,
            'Valor'          => $valor,
        ]);
    }

    public function listarDaPessoa(int $idPessoa): array
    {
        $stmt = $this->bd->prepare(
            "SELECT c.*, tc.Designacao AS TipoContacto
             FROM contactos c
             INNER JOIN tipos_contacto tc ON tc.Id = c.IdTipoContacto
             WHERE c.IdPessoa = :idPessoa
             ORDER BY tc.Designacao"
        );
        $stmt->execute(['idPessoa' => $idPessoa]);
        return $stmt->fetchAll();
    }

    public function removerDaPessoa(int $idPessoa): bool
    {
        $stmt = $this->bd->prepare("DELETE FROM contactos WHERE IdPessoa = :idPessoa");
        return $stmt->execute(['idPessoa' => $idPessoa]);
    }
}
