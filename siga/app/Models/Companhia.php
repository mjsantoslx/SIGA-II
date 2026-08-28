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
}
