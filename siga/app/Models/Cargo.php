<?php

namespace App\Models;

use App\Core\Model;

class Cargo extends Model
{
    protected string $tabela = 'cargos';

    public function listarAtivos(): array
    {
        $stmt = $this->bd->query("SELECT * FROM cargos WHERE Activo = 1 ORDER BY Designacao");
        return $stmt->fetchAll();
    }
}
