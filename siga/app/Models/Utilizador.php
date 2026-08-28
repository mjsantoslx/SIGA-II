<?php

namespace App\Models;

use App\Core\Model;

class Utilizador extends Model
{
    protected string $tabela = 'utilizadores';

    public function encontrarPorNomeOuEmail(string $identificador): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT * FROM utilizadores WHERE (Nome = :ident OR Email = :ident) AND Activo = 1 LIMIT 1"
        );
        $stmt->execute(['ident' => $identificador]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }
}
