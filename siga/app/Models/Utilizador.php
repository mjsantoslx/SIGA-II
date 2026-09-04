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
}
