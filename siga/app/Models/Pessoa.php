<?php

namespace App\Models;

use App\Core\Model;

class Pessoa extends Model
{
    protected string $tabela = 'pessoas';

    public function criar(string $nome): int
    {
        return $this->inserir('pessoas', ['Nome' => $nome]);
    }

    public function actualizarNome(int $id, string $nome): bool
    {
        return $this->actualizar('pessoas', ['Nome' => $nome], 'Id', $id);
    }
}
