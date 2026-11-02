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
             ORDER BY tc.Designacao, c.Id"
        );
        $stmt->execute(['idPessoa' => $idPessoa]);
        return $stmt->fetchAll();
    }

    /**
     * Confirma que um contacto pertence de facto à pessoa indicada, antes de
     * permitir editá-lo ou removê-lo (evita que alguém altere o Id na URL e
     * mexa no contacto de outra pessoa).
     */
    public function pertenceAPessoa(int $idContacto, int $idPessoa): bool
    {
        $stmt = $this->bd->prepare("SELECT 1 FROM contactos WHERE Id = :id AND IdPessoa = :idPessoa");
        $stmt->execute(['id' => $idContacto, 'idPessoa' => $idPessoa]);
        return (bool) $stmt->fetchColumn();
    }

    public function idTipoPorDesignacao(string $designacao): ?int
    {
        $stmt = $this->bd->prepare("SELECT Id FROM tipos_contacto WHERE Designacao = :d LIMIT 1");
        $stmt->execute(['d' => $designacao]);
        $registo = $stmt->fetch();
        return $registo ? (int) $registo['Id'] : null;
    }

    /**
     * Confirma se uma pessoa já tem um contacto activo de um determinado tipo
     * (pela designação, ex.: "Email Associativo").
     */
    public function temTipo(int $idPessoa, string $designacao): bool
    {
        $stmt = $this->bd->prepare(
            "SELECT 1 FROM contactos c
             INNER JOIN tipos_contacto tc ON tc.Id = c.IdTipoContacto
             WHERE c.IdPessoa = :idPessoa AND tc.Designacao = :designacao
             LIMIT 1"
        );
        $stmt->execute(['idPessoa' => $idPessoa, 'designacao' => $designacao]);
        return (bool) $stmt->fetchColumn();
    }

    public function actualizarValor(int $idContacto, int $idTipoContacto, string $valor): bool
    {
        return $this->actualizar('contactos', [
            'IdTipoContacto' => $idTipoContacto,
            'Valor'          => $valor,
        ], 'Id', $idContacto);
    }

    public function removerDaPessoa(int $idPessoa): bool
    {
        $stmt = $this->bd->prepare("DELETE FROM contactos WHERE IdPessoa = :idPessoa");
        return $stmt->execute(['idPessoa' => $idPessoa]);
    }
}
