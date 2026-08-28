<?php

namespace App\Models;

use App\Core\Model;

class Secao extends Model
{
    protected string $tabela = 'secoes';

    public function listarTodas(): array
    {
        return $this->todos('Id');
    }
}
