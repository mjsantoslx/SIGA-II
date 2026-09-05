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

    public function listarTodas(): array
    {
        $stmt = $this->bd->query("SELECT * FROM companhias ORDER BY ambito_global DESC, Designacao");
        return $stmt->fetchAll();
    }

    public function designacaoEmUso(string $designacao, ?int $ignorarId = null): bool
    {
        $sql = "SELECT 1 FROM companhias WHERE Designacao = :designacao";
        $parametros = ['designacao' => $designacao];
        if ($ignorarId !== null) {
            $sql .= " AND Id != :ignorarId";
            $parametros['ignorarId'] = $ignorarId;
        }
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Confirma se já existe outra companhia de âmbito global (Chefia
     * Nacional) activa, diferente da indicada — usado para impedir que
     * existam duas em simultâneo (o resto da aplicação assume uma única).
     */
    public function existeOutraDeAmbitoGlobal(?int $ignorarId = null): bool
    {
        $sql = "SELECT 1 FROM companhias WHERE ambito_global = 1 AND Activo = 1";
        $parametros = [];
        if ($ignorarId !== null) {
            $sql .= " AND Id != :ignorarId";
            $parametros['ignorarId'] = $ignorarId;
        }
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $designacao, bool $ambitoGlobal): int
    {
        return $this->inserir('companhias', [
            'Designacao'    => $designacao,
            'ambito_global' => $ambitoGlobal ? 1 : 0,
            'Activo'        => 1,
        ]);
    }

    public function actualizarDados(int $id, string $designacao, bool $ambitoGlobal, bool $activo): bool
    {
        return $this->actualizar('companhias', [
            'Designacao'    => $designacao,
            'ambito_global' => $ambitoGlobal ? 1 : 0,
            'Activo'        => $activo ? 1 : 0,
        ], 'Id', $id);
    }
}
