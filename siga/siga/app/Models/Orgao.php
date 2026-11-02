<?php

namespace App\Models;

use App\Core\Model;

class Orgao extends Model
{
    protected string $tabela = 'orgaos';

    public function listarAtivos(): array
    {
        $stmt = $this->bd->query("SELECT * FROM orgaos WHERE Activo = 1 ORDER BY Designacao");
        return $stmt->fetchAll();
    }
}
