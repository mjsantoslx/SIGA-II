<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\AnoEscotista;

/**
 * Definição dos valores anuais do Censo (seguro escotista, quota UEP,
 * quota WFIS) — acesso restrito a administradores.
 */
class AnosEscotistasController extends Controller
{
    public function index(): void
    {
        $this->exigirAdministrador();

        $this->vista('anos_escotistas/index', [
            'titulo' => 'Anos escotistas — valores do Censo',
            'anos'   => (new AnoEscotista())->listarTodos(),
        ]);
    }

    public function criar(): void
    {
        $this->exigirAdministrador();

        $this->vista('anos_escotistas/form', [
            'titulo' => 'Novo ano escotista',
            'ano'    => null,
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $dados = $_POST;
        $modelo = new AnoEscotista();
        $erros = $this->validar($modelo, $dados, null);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('anos_escotistas/form', [
                'titulo' => 'Novo ano escotista',
                'ano'    => $dados,
            ]);
            return;
        }

        $modelo->criar(
            (int) $dados['AnoInicio'],
            (float) str_replace(',', '.', $dados['ValorSeguroEscotista']),
            (float) str_replace(',', '.', $dados['ValorQuotaUEP']),
            (float) str_replace(',', '.', $dados['ValorQuotaWFIS'])
        );

        Sessao::guardarMensagem('sucesso', 'Ano escotista criado com sucesso.');
        $this->redirecionar('/admin/anos-escotistas');
    }

    public function editar(string $id): void
    {
        $this->exigirAdministrador();

        $ano = (new AnoEscotista())->encontrarPorId((int) $id);
        if (!$ano) {
            Sessao::guardarMensagem('erro', 'Ano escotista não encontrado.');
            $this->redirecionar('/admin/anos-escotistas');
            return;
        }

        $this->vista('anos_escotistas/form', [
            'titulo' => 'Editar — Ano escotista ' . $ano['AnoInicio'] . '/' . ($ano['AnoInicio'] + 1),
            'ano'    => $ano,
        ]);
    }

    public function atualizar(string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $idAno = (int) $id;
        $modelo = new AnoEscotista();
        $anoExistente = $modelo->encontrarPorId($idAno);

        if (!$anoExistente) {
            Sessao::guardarMensagem('erro', 'Ano escotista não encontrado.');
            $this->redirecionar('/admin/anos-escotistas');
            return;
        }

        $dados = $_POST;
        $erros = $this->validar($modelo, $dados, $idAno);

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/admin/anos-escotistas/' . $idAno . '/editar');
            return;
        }

        $modelo->actualizarDados(
            $idAno,
            (int) $dados['AnoInicio'],
            (float) str_replace(',', '.', $dados['ValorSeguroEscotista']),
            (float) str_replace(',', '.', $dados['ValorQuotaUEP']),
            (float) str_replace(',', '.', $dados['ValorQuotaWFIS']),
            !empty($dados['Activo'])
        );

        Sessao::guardarMensagem('sucesso', 'Ano escotista actualizado com sucesso.');
        $this->redirecionar('/admin/anos-escotistas');
    }

    private function validar(AnoEscotista $modelo, array $dados, ?int $idAIgnorar): array
    {
        $erros = [];

        $anoInicio = (int) ($dados['AnoInicio'] ?? 0);
        if ($anoInicio < 2000 || $anoInicio > 2100) {
            $erros[] = 'Indique um ano de início válido.';
        } elseif ($modelo->anoEmUso($anoInicio, $idAIgnorar)) {
            $erros[] = 'Já existe um ano escotista com esse ano de início.';
        }

        foreach (['ValorSeguroEscotista' => 'seguro escotista', 'ValorQuotaUEP' => 'quota UEP', 'ValorQuotaWFIS' => 'quota WFIS'] as $campo => $rotulo) {
            $valor = str_replace(',', '.', $dados[$campo] ?? '');
            if (!is_numeric($valor) || (float) $valor < 0) {
                $erros[] = "O valor da {$rotulo} tem de ser um número igual ou superior a zero.";
            }
        }

        return $erros;
    }
}
