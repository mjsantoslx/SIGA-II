<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Página inicial do backoffice — acesso restrito a administradores.
 */
class AdminController extends Controller
{
    public function index(): void
    {
        $this->exigirAdministrador();

        $this->vista('admin/index', [
            'titulo' => 'Administração',
        ]);
    }
}
