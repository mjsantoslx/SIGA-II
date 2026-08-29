<?php

namespace App\Models;

use App\Core\Model;

class Companhia extends Model
{
    protected string $tabela = 'companhias';

    public function listarAtivas(): array
    {
        $stmt = $this->bd->query("SELECT * FROM companhias WHERE Activo = 1 ORDER BY Designacao");
        return $stmt->fetchAll();
    }

    /**
     * Companhias "locais" (ambito_global = 0) — excluem a Chefia Nacional,
     * que é gerida à parte porque pode coexistir com uma companhia local.
     */
    public function listarLocais(): array
    {
        $stmt = $this->bd->query("SELECT * FROM companhias WHERE Activo = 1 AND ambito_global = 0 ORDER BY Designacao");
        return $stmt->fetchAll();
    }

    public function chefiaNacional(): ?array
    {
        $stmt = $this->bd->query("SELECT * FROM companhias WHERE ambito_global = 1 AND Activo = 1 LIMIT 1");
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    public function ehAmbitoGlobal(int $idCompanhia): bool
    {
        $stmt = $this->bd->prepare("SELECT ambito_global FROM companhias WHERE Id = :id");
        $stmt->execute(['id' => $idCompanhia]);
        return (bool) $stmt->fetchColumn();
    }
}
