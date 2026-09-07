<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Data;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\AnoEscotista;
use App\Models\Censo;
use App\Models\CensoHistorico;

/**
 * Registo de pagamento do Censo. Respeita a regra 2: um utilizador
 * não-administrador só vê/regista pagamentos dos associados da sua
 * própria companhia.
 */
class CensosController extends Controller
{
    public function index(): void
    {
        $this->exigirAutenticacao();

        $anoModelo = new AnoEscotista();
        $anos = $anoModelo->listarTodos();

        if (empty($anos)) {
            $this->vista('censos/index', [
                'titulo' => 'Censos',
                'anos'   => [],
                'anoSeleccionado' => null,
                'linhas' => [],
                'resumo' => null,
                'hojePt' => Data::hojePt(),
            ]);
            return;
        }

        $idAnoSeleccionado = !empty($_GET['ano']) ? (int) $_GET['ano'] : (int) $anos[0]['Id'];

        $idCompanhiaRestricao = null;
        if (!Sessao::ehAdministrador()) {
            $idAssociadoUtilizador = Sessao::idAssociado();
            $idCompanhiaRestricao = $idAssociadoUtilizador
                ? ((new Associado())->companhiaActual($idAssociadoUtilizador)['IdCompanhia'] ?? -1)
                : -1;
        }

        $censoModelo = new Censo();

        $this->vista('censos/index', [
            'titulo'           => 'Censos',
            'anos'             => $anos,
            'anoSeleccionado'  => $idAnoSeleccionado,
            'linhas'           => $censoModelo->listarParaAno($idAnoSeleccionado, $idCompanhiaRestricao),
            'resumo'           => $censoModelo->contarPagos($idAnoSeleccionado, $idCompanhiaRestricao),
            'hojePt'           => Data::hojePt(),
        ]);
    }

    public function marcarPago(string $idAssociado, string $idAnoEscotista): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $idAssociado;
        $idAnoEscotista = (int) $idAnoEscotista;
        $this->exigirAcessoAssociado($idAssociado);

        $dataPagamento = Data::paraBd($_POST['DataPagamento'] ?? '');
        if ($dataPagamento === null || Data::eFutura($dataPagamento)) {
            Sessao::guardarMensagem('erro', 'Indique uma data de pagamento válida (não pode ser posterior a hoje).');
            $this->redirecionar('/censos?ano=' . $idAnoEscotista);
            return;
        }

        $idUtilizadorActual = Sessao::utilizador()['Id'] ?? null;
        if (!$idUtilizadorActual) {
            Sessao::guardarMensagem('erro', 'Não foi possível identificar o utilizador para registar no histórico.');
            $this->redirecionar('/censos?ano=' . $idAnoEscotista);
            return;
        }

        (new Censo())->marcarPago($idAssociado, $idAnoEscotista, $dataPagamento, (int) $idUtilizadorActual);
        Sessao::guardarMensagem('sucesso', 'Censo marcado como pago.');
        $this->redirecionar('/censos?ano=' . $idAnoEscotista);
    }

    public function marcarNaoPago(string $idAssociado, string $idAnoEscotista): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $idAssociado;
        $idAnoEscotista = (int) $idAnoEscotista;
        $this->exigirAcessoAssociado($idAssociado);

        $idUtilizadorActual = Sessao::utilizador()['Id'] ?? null;
        if (!$idUtilizadorActual) {
            Sessao::guardarMensagem('erro', 'Não foi possível identificar o utilizador para registar no histórico.');
            $this->redirecionar('/censos?ano=' . $idAnoEscotista);
            return;
        }

        (new Censo())->marcarNaoPago($idAssociado, $idAnoEscotista, (int) $idUtilizadorActual);
        Sessao::guardarMensagem('sucesso', 'Censo marcado como não pago.');
        $this->redirecionar('/censos?ano=' . $idAnoEscotista);
    }

    public function historico(string $idAssociado, string $idAnoEscotista): void
    {
        $this->exigirAutenticacao();

        $idAssociado = (int) $idAssociado;
        $idAnoEscotista = (int) $idAnoEscotista;
        $this->exigirAcessoAssociado($idAssociado);

        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        $ano = (new AnoEscotista())->encontrarPorId($idAnoEscotista);

        if (!$associado || !$ano) {
            Sessao::guardarMensagem('erro', 'Associado ou ano escotista não encontrado.');
            $this->redirecionar('/censos');
            return;
        }

        $this->vista('censos/historico', [
            'titulo'     => 'Histórico do Censo — ' . $associado['Nome'],
            'associado'  => $associado,
            'ano'        => $ano,
            'historico'  => (new CensoHistorico())->listarPorAssociadoEAno($idAssociado, $idAnoEscotista),
        ]);
    }
}
