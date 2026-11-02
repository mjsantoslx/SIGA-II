<?php

namespace App\Models;

use App\Core\Model;

class Utilizador extends Model
{
    protected string $tabela = 'utilizadores';

    /**
     * O login só é feito por nome de utilizador — nunca por email.
     */
    public function encontrarPorNome(string $nome): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT * FROM utilizadores WHERE Nome = :nome AND Activo = 1 LIMIT 1"
        );
        $stmt->execute(['nome' => $nome]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }

    public function listarTodos(): array
    {
        $stmt = $this->bd->query("SELECT * FROM utilizadores ORDER BY Nome");
        return $stmt->fetchAll();
    }

    public function nomeEmUso(string $nome, ?int $ignorarId = null): bool
    {
        $sql = "SELECT 1 FROM utilizadores WHERE Nome = :nome";
        $parametros = ['nome' => $nome];
        if ($ignorarId !== null) {
            $sql .= " AND Id != :ignorarId";
            $parametros['ignorarId'] = $ignorarId;
        }
        $stmt = $this->bd->prepare($sql);
        $stmt->execute($parametros);
        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $nome, string $email, string $palavraPasse, bool $administrador): int
    {
        return $this->inserir('utilizadores', [
            'Nome'          => $nome,
            'Email'         => $email,
            'Password'      => password_hash($palavraPasse, PASSWORD_BCRYPT),
            'Administrador' => $administrador ? 1 : 0,
            'Activo'        => 1,
        ]);
    }

    public function actualizarDados(int $id, string $nome, string $email, bool $administrador, bool $activo): bool
    {
        return $this->actualizar('utilizadores', [
            'Nome'          => $nome,
            'Email'         => $email,
            'Administrador' => $administrador ? 1 : 0,
            'Activo'        => $activo ? 1 : 0,
        ], 'Id', $id);
    }

    public function redefinirPalavraPasse(int $id, string $novaPalavraPasse): bool
    {
        return $this->actualizar('utilizadores', [
            'Password' => password_hash($novaPalavraPasse, PASSWORD_BCRYPT),
        ], 'Id', $id);
    }

    public function contarAdministradoresActivos(): int
    {
        $stmt = $this->bd->query("SELECT COUNT(*) FROM utilizadores WHERE Administrador = 1 AND Activo = 1");
        return (int) $stmt->fetchColumn();
    }
}
