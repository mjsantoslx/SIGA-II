<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Associado;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->exigirAutenticacao();

        $associadoModelo = new Associado();

        $this->vista('dashboard/index', [
            'titulo'        => 'Painel principal',
            'estados'       => $associadoModelo->contarPorEstado(),
            'porSecao'      => $associadoModelo->contarPorSecao(),
        ]);
    }
}
