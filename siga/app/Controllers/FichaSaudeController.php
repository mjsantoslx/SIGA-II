<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\FichaSaude;
use App\Models\FichaSaudeHistorico;

/**
 * Gestão da ficha de saúde de um associado, com histórico de alterações
 * (fichas_saude_historico). Até agora só era possível definir a ficha de
 * saúde no momento do registo do associado — isto acrescenta a edição
 * posterior, sempre com rasto de quem alterou o quê e quando.
 */
class FichaSaudeController extends Controller
{
    public function gerir(string $id): void
    {
        $this->exigirAutenticacao();

        $idAssociado = (int) $id;
        $this->exigirAcessoAssociado($idAssociado);

        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $this->vista('associados/ficha_saude', [
            'titulo'     => 'Ficha de saúde — ' . $associado['Nome'],
            'associado'  => $associado,
            'ficha'      => (new FichaSaude())->porAssociado($idAssociado),
            'historico'  => (new FichaSaudeHistorico())->listarPorAssociado($idAssociado),
        ]);
    }

    public function guardar(string $id): void
    {
        $this->exigirAutenticacao();
        $this->validarCsrf();

        $idAssociado = (int) $id;
        $this->exigirAcessoAssociado($idAssociado);

        $associado = (new Associado())->encontrarCompletoPorId($idAssociado);
        if (!$associado) {
            Sessao::guardarMensagem('erro', 'Associado não encontrado.');
            $this->redirecionar('/associados');
            return;
        }

        $dados = $_POST;
        $numUente = trim($dados['NumUente'] ?? '');

        if ($numUente !== '' && !preg_match('/^\d{9}$/', $numUente)) {
            Sessao::guardarMensagem('erro', 'O número de utente deve ter exactamente 9 dígitos.');
            $this->redirecionar('/associados/' . $idAssociado . '/ficha-saude');
            return;
        }

        if ($numUente === '') {
            Sessao::guardarMensagem('erro', 'O número de utente é obrigatório para guardar a ficha de saúde.');
            $this->redirecionar('/associados/' . $idAssociado . '/ficha-saude');
            return;
        }

        $idUtilizadorActual = Sessao::utilizador()['Id'] ?? null;
        if (!$idUtilizadorActual) {
            Sessao::guardarMensagem('erro', 'Não foi possível identificar o utilizador para registar no histórico.');
            $this->redirecionar('/associados/' . $idAssociado . '/ficha-saude');
            return;
        }

        (new FichaSaude())->guardarComHistorico($idAssociado, $dados, (int) $idUtilizadorActual);

        Sessao::guardarMensagem('sucesso', 'Ficha de saúde guardada com sucesso.');
        $this->redirecionar('/associados/' . $idAssociado . '/ficha-saude');
    }
}
