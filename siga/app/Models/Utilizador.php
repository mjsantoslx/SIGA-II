<?php

namespace App\Models;

use App\Core\Model;

class Utilizador extends Model
{
    protected string $tabela = 'utilizadores';

    public function encontrarPorNomeOuEmail(string $identificador): ?array
    {
        $stmt = $this->bd->prepare(
            "SELECT * FROM utilizadores WHERE (Nome = :ident1 OR Email = :ident2) AND Activo = 1 LIMIT 1"
        );
        $stmt->execute(['ident1' => $identificador, 'ident2' => $identificador]);
        $registo = $stmt->fetch();
        return $registo ?: null;
    }
}
