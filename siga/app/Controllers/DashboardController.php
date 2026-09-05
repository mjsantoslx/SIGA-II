<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Associado;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->exigirAutenticacao();

        $associadoModelo = new Associado();

        // Regra 2: utilizadores não-administradores só veem estatísticas da sua companhia.
        $idCompanhiaRestricao = null;
        if (!Sessao::ehAdministrador()) {
            $idAssociadoUtilizador = Sessao::idAssociado();
            $idCompanhiaRestricao = $idAssociadoUtilizador
                ? ($associadoModelo->companhiaActual($idAssociadoUtilizador)['IdCompanhia'] ?? -1)
                : -1;
        }

        $this->vista('dashboard/index', [
            'titulo'   => 'Painel principal',
            'estados'  => $associadoModelo->contarPorEstado($idCompanhiaRestricao),
            'porSecao' => $associadoModelo->contarPorSecao($idCompanhiaRestricao),
        ]);
    }
}
