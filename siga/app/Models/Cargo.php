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

    public function designacaoPorId(int $idCargo): ?string
    {
        $stmt = $this->bd->prepare("SELECT Designacao FROM cargos WHERE Id = :id");
        $stmt->execute(['id' => $idCargo]);
        $valor = $stmt->fetchColumn();
        return $valor !== false ? $valor : null;
    }
}
