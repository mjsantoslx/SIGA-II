<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Data;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\Companhia;
use App\Models\Morada;

/**
 * Gestão de moradas de associados e de companhias (secções 11 e 12 das
 * regras de negócio). Distingue sempre duas operações:
 *  - "Corrigir": altera os dados da morada existente, afectando todos os
 *    que a partilham (ex.: corrigir um número de porta mal escrito).
 *  - "Substituir": cria uma morada nova e fecha a ligação anterior,
 *    mantendo o histórico (DataInicio/DataFim).
 */
class MoradasController extends Controller
{
    public function editarPessoa(string $idAssociado): void
    {
        $this->exigirAutenticacao();

        $idAssociado = (int) $idAssociado;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $moradaModelo = new Morada();
        $ligacao = $moradaModelo->ligacaoActivaDaPessoa((int) $associado['IdPessoa']);

        $this->vista('moradas/editar', [
            'titulo'          => 'Morada — ' . $associado['Nome'],
            'tipoEntidade'    => 'associado',
            'urlVoltar'       => '/associados/' . $idAssociado,
            'urlCorrigir'     => '/associados/' . $idAssociado . '/morada/corrigir',
            'urlSubstituir'   => '/associados/' . $idAssociado . '/morada/substituir',
            'ligacao'         => $ligacao,
            'partilhas'       => $ligacao ? $moradaModelo->contarLigacoesActivas((int) $ligacao['Id']) : 0,
            'hojePt'          => Data::hojePt(),
        ]);
    }

    public function corrigirPessoa(string $idAssociado): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $idAssociado;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $moradaModelo = new Morada();
        $ligacao = $moradaModelo->ligacaoActivaDaPessoa((int) $associado['IdPessoa']);

        if (!$ligacao) {
            Sessao::guardarMensagem('erro', 'Este associado ainda não tem morada registada — utilize "Substituir" para criar uma.');
            $this->redirecionar('/associados/' . $idAssociado . '/morada/editar');
            return;
        }

        $erros = $this->validarDadosMorada($_POST);
        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/associados/' . $idAssociado . '/morada/editar');
            return;
        }

        $moradaModelo->corrigir((int) $ligacao['Id'], $_POST);
        Sessao::guardarMensagem('sucesso', 'Morada corrigida. A alteração aplica-se a todos os que partilham esta morada.');
        $this->redirecionar('/associados/' . $idAssociado);
    }

    public function substituirPessoa(string $idAssociado): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $idAssociado;
        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $erros = $this->validarDadosMorada($_POST);
        $dataInicio = Data::paraBd($_POST['DataInicio'] ?? '');
        if ($dataInicio === null) {
            $erros[] = 'Indique uma data de início válida para a nova morada.';
        } elseif (Data::eFutura($dataInicio)) {
            $erros[] = 'A data de início da nova morada não pode ser posterior a hoje.';
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/associados/' . $idAssociado . '/morada/editar');
            return;
        }

        $moradaModelo = new Morada();
        $ligacaoAnterior = $moradaModelo->ligacaoActivaDaPessoa((int) $associado['IdPessoa']);

        $moradaModelo->substituirLigacaoPessoa(
            (int) $associado['IdPessoa'],
            $ligacaoAnterior['IdLigacao'] ?? null,
            $_POST,
            $dataInicio
        );

        Sessao::guardarMensagem('sucesso', 'Nova morada registada; a anterior foi mantida no histórico.');
        $this->redirecionar('/associados/' . $idAssociado);
    }

    public function editarCompanhia(string $idCompanhia): void
    {
        $this->exigirAutenticacao();

        $idCompanhia = (int) $idCompanhia;
        $companhia = (new Companhia())->encontrarPorId($idCompanhia);
        if (!$companhia) {
            Sessao::guardarMensagem('erro', 'Companhia não encontrada.');
            $this->redirecionar('/companhias');
            return;
        }

        $moradaModelo = new Morada();
        $ligacao = $moradaModelo->ligacaoActivaDaCompanhia($idCompanhia);

        $this->vista('moradas/editar', [
            'titulo'          => 'Morada — ' . $companhia['Designacao'],
            'tipoEntidade'    => 'companhia',
            'urlVoltar'       => '/companhias/' . $idCompanhia,
            'urlCorrigir'     => '/companhias/' . $idCompanhia . '/morada/corrigir',
            'urlSubstituir'   => '/companhias/' . $idCompanhia . '/morada/substituir',
            'ligacao'         => $ligacao,
            'partilhas'       => $ligacao ? $moradaModelo->contarLigacoesActivas((int) $ligacao['Id']) : 0,
            'hojePt'          => Data::hojePt(),
        ]);
    }

    public function corrigirCompanhia(string $idCompanhia): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idCompanhia = (int) $idCompanhia;
        $companhia = (new Companhia())->encontrarPorId($idCompanhia);
        if (!$companhia) {
            Sessao::guardarMensagem('erro', 'Companhia não encontrada.');
            $this->redirecionar('/companhias');
            return;
        }

        $moradaModelo = new Morada();
        $ligacao = $moradaModelo->ligacaoActivaDaCompanhia($idCompanhia);

        if (!$ligacao) {
            Sessao::guardarMensagem('erro', 'Esta companhia ainda não tem morada registada — utilize "Substituir" para criar uma.');
            $this->redirecionar('/companhias/' . $idCompanhia . '/morada/editar');
            return;
        }

        $erros = $this->validarDadosMorada($_POST);
        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/companhias/' . $idCompanhia . '/morada/editar');
            return;
        }

        $moradaModelo->corrigir((int) $ligacao['Id'], $_POST);
        Sessao::guardarMensagem('sucesso', 'Morada corrigida. A alteração aplica-se a todos os que partilham esta morada.');
        $this->redirecionar('/companhias/' . $idCompanhia);
    }

    public function substituirCompanhia(string $idCompanhia): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idCompanhia = (int) $idCompanhia;
        $companhia = (new Companhia())->encontrarPorId($idCompanhia);
        if (!$companhia) {
            Sessao::guardarMensagem('erro', 'Companhia não encontrada.');
            $this->redirecionar('/companhias');
            return;
        }

        $erros = $this->validarDadosMorada($_POST);
        $dataInicio = Data::paraBd($_POST['DataInicio'] ?? '');
        if ($dataInicio === null) {
            $erros[] = 'Indique uma data de início válida para a nova morada.';
        } elseif (Data::eFutura($dataInicio)) {
            $erros[] = 'A data de início da nova morada não pode ser posterior a hoje.';
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/companhias/' . $idCompanhia . '/morada/editar');
            return;
        }

        $moradaModelo = new Morada();
        $ligacaoAnterior = $moradaModelo->ligacaoActivaDaCompanhia($idCompanhia);

        $moradaModelo->substituirLigacaoCompanhia(
            $idCompanhia,
            $ligacaoAnterior['IdLigacao'] ?? null,
            $_POST,
            $dataInicio
        );

        Sessao::guardarMensagem('sucesso', 'Nova morada registada; a anterior foi mantida no histórico.');
        $this->redirecionar('/companhias/' . $idCompanhia);
    }

    private function validarDadosMorada(array $dados): array
    {
        $erros = [];
        if (trim($dados['Morada'] ?? '') === '') {
            $erros[] = 'A morada (rua/número) é obrigatória.';
        }
        return $erros;
    }
}
