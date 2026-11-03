<?php

namespace App\Controllers;

use App\Core\Actividade;
use App\Core\Controller;
use App\Core\Sessao;
use App\Models\UtilizadorAssociado;
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
            'versao' => $this->config['app']['versao'] ?? '',
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
            // Só se guarda o nome tentado se corresponder a um utilizador real
            // (evita gravar, por engano, uma palavra-passe escrita no campo do nome).
            if ($utilizador) {
                Actividade::registar('login_falhado', 'Tentativa de início de sessão falhada (palavra-passe incorrecta)', 'falhou', $utilizador);
            } else {
                Actividade::registar('login_falhado', 'Tentativa de início de sessão com um nome de utilizador inexistente', 'falhou');
            }
            Sessao::guardarMensagem('erro', 'Credenciais inválidas. Verifique o utilizador e a palavra-passe.');
            $this->redirecionar('/login');
            return;
        }

        Sessao::autenticar($utilizador, (new UtilizadorAssociado())->idAssociadoDoUtilizador((int) $utilizador['Id']));
        Actividade::registar('login', 'Iniciou sessão', 'sucesso', $utilizador);
        Sessao::guardarMensagem('sucesso', 'Sessão iniciada com sucesso. Bem-vindo(a), ' . $utilizador['Nome'] . '.');
        $this->redirecionar('/');
    }

    public function terminarSessao(): void
    {
        if (Sessao::autenticado()) {
            Actividade::registar('logout', 'Terminou sessão', 'sucesso', Sessao::utilizador());
        }
        Sessao::terminar();
        $this->redirecionar('/login');
    }
}
