<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Companhia;
use App\Models\Morada;

/**
 * Consulta e gestão de morada das companhias (secção 12 das regras de
 * negócio), incluindo a Chefia Nacional, que é tratada como qualquer outra
 * companhia para este efeito (secção 11.5). A criação/edição dos dados
 * base das companhias fica para a futura página de administração.
 */
class CompanhiasController extends Controller
{
    public function index(): void
    {
        $this->exigirAutenticacao();

        $companhiaModelo = new Companhia();
        $moradaModelo = new Morada();

        $companhias = $companhiaModelo->listarAtivas();
        foreach ($companhias as &$companhia) {
            $companhia['Morada'] = $moradaModelo->ligacaoActivaDaCompanhia((int) $companhia['Id']);
        }
        unset($companhia);

        $this->vista('companhias/index', [
            'titulo'     => 'Companhias',
            'companhias' => $companhias,
        ]);
    }

    public function ver(string $id): void
    {
        $this->exigirAutenticacao();

        $idCompanhia = (int) $id;
        $companhia = (new Companhia())->encontrarPorId($idCompanhia);

        if (!$companhia) {
            Sessao::guardarMensagem('erro', 'Companhia não encontrada.');
            $this->redirecionar('/companhias');
            return;
        }

        $this->vista('companhias/show', [
            'titulo'    => $companhia['Designacao'],
            'companhia' => $companhia,
            'morada'    => (new Morada())->ligacaoActivaDaCompanhia($idCompanhia),
        ]);
    }
}
