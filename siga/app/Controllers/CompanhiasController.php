<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\Companhia;
use App\Models\Morada;

/**
 * Gestão de companhias: consulta e morada (secção 12 das regras de
 * negócio), incluindo a Chefia Nacional, tratada como qualquer outra
 * companhia para esse efeito (secção 11.5). Criação e edição dos dados
 * base (designação, âmbito, estado) restritas a administradores — a
 * gestão de morada continua disponível a qualquer utilizador da própria
 * companhia (ver MoradasController).
 */
class CompanhiasController extends Controller
{
    public function index(): void
    {
        $this->exigirAutenticacao();

        $companhiaModelo = new Companhia();
        $moradaModelo = new Morada();

        // Regra 2: utilizadores não-administradores só veem a sua própria companhia.
        if (Sessao::ehAdministrador()) {
            $companhias = $companhiaModelo->listarTodas();
        } else {
            $idAssociadoUtilizador = Sessao::idAssociado();
            $idCompanhiaUtilizador = $idAssociadoUtilizador
                ? ((new Associado())->companhiaActual($idAssociadoUtilizador)['IdCompanhia'] ?? null)
                : null;
            $companhia = $idCompanhiaUtilizador ? $companhiaModelo->encontrarPorId($idCompanhiaUtilizador) : null;
            $companhias = $companhia ? [$companhia] : [];
        }

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
        $this->exigirAcessoCompanhia($idCompanhia);

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

    public function criar(): void
    {
        $this->exigirAdministrador();

        $this->vista('companhias/form', [
            'titulo'    => 'Nova companhia',
            'modo'      => 'criar',
            'companhia' => null,
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $dados = $_POST;
        $erros = $this->validarDados($dados, null);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('companhias/form', [
                'titulo'     => 'Nova companhia',
                'modo'       => 'criar',
                'companhia'  => $dados,
            ]);
            return;
        }

        (new Companhia())->criar(trim($dados['Designacao']), !empty($dados['AmbitoGlobal']));

        Sessao::guardarMensagem('sucesso', 'Companhia criada com sucesso.');
        $this->redirecionar('/companhias');
    }

    public function editar(string $id): void
    {
        $this->exigirAdministrador();

        $idCompanhia = (int) $id;
        $companhia = (new Companhia())->encontrarPorId($idCompanhia);

        if (!$companhia) {
            Sessao::guardarMensagem('erro', 'Companhia não encontrada.');
            $this->redirecionar('/companhias');
            return;
        }

        $this->vista('companhias/editar', [
            'titulo'    => 'Editar — ' . $companhia['Designacao'],
            'companhia' => $companhia,
        ]);
    }

    public function atualizar(string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $idCompanhia = (int) $id;
        $companhiaModelo = new Companhia();
        $companhiaExistente = $companhiaModelo->encontrarPorId($idCompanhia);

        if (!$companhiaExistente) {
            Sessao::guardarMensagem('erro', 'Companhia não encontrada.');
            $this->redirecionar('/companhias');
            return;
        }

        $dados = $_POST;
        $erros = $this->validarDados($dados, $idCompanhia);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/companhias/' . $idCompanhia . '/editar');
            return;
        }

        $companhiaModelo->actualizarDados(
            $idCompanhia,
            trim($dados['Designacao']),
            !empty($dados['AmbitoGlobal']),
            !empty($dados['Activo'])
        );

        Sessao::guardarMensagem('sucesso', 'Companhia actualizada com sucesso.');
        $this->redirecionar('/companhias');
    }

    private function validarDados(array $dados, ?int $idAIgnorar): array
    {
        $erros = [];

        $designacao = trim($dados['Designacao'] ?? '');
        if ($designacao === '') {
            $erros[] = 'A designação da companhia é obrigatória.';
        } elseif ((new Companhia())->designacaoEmUso($designacao, $idAIgnorar)) {
            $erros[] = 'Já existe uma companhia com essa designação.';
        }

        // Só pode existir uma companhia de âmbito global (Chefia Nacional) de cada vez.
        if (!empty($dados['AmbitoGlobal']) && (new Companhia())->existeOutraDeAmbitoGlobal($idAIgnorar)) {
            $erros[] = 'Já existe uma companhia de âmbito nacional (Chefia Nacional) — só pode existir uma.';
        }

        return $erros;
    }
}
