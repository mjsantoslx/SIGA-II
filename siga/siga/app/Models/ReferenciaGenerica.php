<?php

namespace App\Models;

use App\Core\Database;
use PDOException;

/**
 * Gestão genérica de tabelas de referência simples (uma coluna de
 * designação, opcionalmente com Activo). O nome da tabela e da coluna
 * NUNCA vêm directamente do pedido HTTP — o ReferenciasController só os
 * passa depois de validados contra uma whitelist fixa, por isso é seguro
 * interpolá-los directamente no SQL aqui.
 */
class ReferenciaGenerica
{
    public function listar(string $tabela, string $coluna): array
    {
        $stmt = Database::ligacao()->query("SELECT * FROM {$tabela} ORDER BY {$coluna}");
        return $stmt->fetchAll();
    }

    public function encontrarPorId(string $tabela, int $id): ?array
    {
        $stmt = Database::ligacao()->prepare("SELECT * FROM {$tabela} WHERE Id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    public function valorEmUso(string $tabela, string $coluna, string $valor, ?int $ignorarId): bool
    {
        $sql = "SELECT 1 FROM {$tabela} WHERE {$coluna} = :valor";
        $parametros = ['valor' => $valor];
        if ($ignorarId !== null) {
            $sql .= " AND Id != :ignorarId";
            $parametros['ignorarId'] = $ignorarId;
        }
        $stmt = Database::ligacao()->prepare($sql);
        $stmt->execute($parametros);
        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $tabela, string $coluna, string $valor, bool $temActivo): int
    {
        $bd = Database::ligacao();
        if ($temActivo) {
            $stmt = $bd->prepare("INSERT INTO {$tabela} ({$coluna}, Activo) VALUES (:valor, 1)");
        } else {
            $stmt = $bd->prepare("INSERT INTO {$tabela} ({$coluna}) VALUES (:valor)");
        }
        $stmt->execute(['valor' => $valor]);
        return (int) $bd->lastInsertId();
    }

    public function actualizar(string $tabela, string $coluna, int $id, string $valor, ?bool $activo): bool
    {
        $bd = Database::ligacao();
        if ($activo !== null) {
            $stmt = $bd->prepare("UPDATE {$tabela} SET {$coluna} = :valor, Activo = :activo WHERE Id = :id");
            return $stmt->execute(['valor' => $valor, 'activo' => $activo ? 1 : 0, 'id' => $id]);
        }
        $stmt = $bd->prepare("UPDATE {$tabela} SET {$coluna} = :valor WHERE Id = :id");
        return $stmt->execute(['valor' => $valor, 'id' => $id]);
    }

    /**
     * Elimina um registo. Se estiver em uso (restrição de chave estrangeira
     * noutra tabela), a base de dados recusa e devolvemos false em vez de
     * deixar rebentar uma excepção — quem chamar decide a mensagem a mostrar.
     */
    public function eliminar(string $tabela, int $id): bool
    {
        try {
            $stmt = Database::ligacao()->prepare("DELETE FROM {$tabela} WHERE Id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }
}
