<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Utilizador;

class AuthController extends Controller
{
    public function mostrarLogin(): void
    {
        if (Sessao::autenticado()) {
            $this->redirecionar('/');
            return;
        }

        $this->vista('auth/login', [
            'titulo' => 'Entrar',
        ], comLayout: false);
    }

    public function autenticar(): void
    {
        $this->validarCsrf();

        $nomeUtilizador = trim($_POST['nome_utilizador'] ?? '');
        $palavraPasse   = (string) ($_POST['palavra_passe'] ?? '');

        if ($nomeUtilizador === '' || $palavraPasse === '') {
            Sessao::guardarMensagem('erro', 'Preencha o utilizador e a palavra-passe.');
            $this->redirecionar('/login');
            return;
        }

        $utilizadorModelo = new Utilizador();
        $utilizador = $utilizadorModelo->encontrarPorNome($nomeUtilizador);

        if (!$utilizador || !password_verify($palavraPasse, $utilizador['Password'])) {
            Sessao::guardarMensagem('erro', 'Credenciais inválidas. Verifique o utilizador e a palavra-passe.');
            $this->redirecionar('/login');
            return;
        }

        Sessao::autenticar($utilizador);
        Sessao::guardarMensagem('sucesso', 'Sessão iniciada com sucesso. Bem-vindo(a), ' . $utilizador['Nome'] . '.');
        $this->redirecionar('/');
    }

    public function terminarSessao(): void
    {
        Sessao::terminar();
        $this->redirecionar('/login');
    }
}
