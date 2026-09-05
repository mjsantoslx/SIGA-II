<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Utilizador;

class UtilizadoresController extends Controller
{
    public function index(): void
    {
        $this->exigirAdministrador();

        $this->vista('utilizadores/index', [
            'titulo'      => 'Utilizadores',
            'utilizadores' => (new Utilizador())->listarTodos(),
        ]);
    }

    public function criar(): void
    {
        $this->exigirAdministrador();

        $this->vista('utilizadores/form', [
            'titulo'     => 'Novo utilizador',
            'modo'       => 'criar',
            'utilizador' => null,
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $dados = $_POST;
        $erros = $this->validarDados($dados);

        if (!empty($dados['PalavraPasse']) && strlen($dados['PalavraPasse']) < 8) {
            $erros[] = 'A palavra-passe deve ter pelo menos 8 caracteres.';
        }
        if (empty($dados['PalavraPasse'])) {
            $erros[] = 'A palavra-passe é obrigatória para um novo utilizador.';
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('utilizadores/form', [
                'titulo'     => 'Novo utilizador',
                'modo'       => 'criar',
                'utilizador' => $dados,
            ]);
            return;
        }

        (new Utilizador())->criar(
            trim($dados['Nome']),
            trim($dados['Email']),
            $dados['PalavraPasse'],
            !empty($dados['Administrador'])
        );

        Sessao::guardarMensagem('sucesso', 'Utilizador criado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    public function editar(string $id): void
    {
        $this->exigirAdministrador();

        $utilizador = (new Utilizador())->encontrarPorId((int) $id);
        if (!$utilizador) {
            Sessao::guardarMensagem('erro', 'Utilizador não encontrado.');
            $this->redirecionar('/utilizadores');
            return;
        }

        $this->vista('utilizadores/editar', [
            'titulo'     => 'Editar — ' . $utilizador['Nome'],
            'utilizador' => $utilizador,
        ]);
    }

    public function atualizar(string $id): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $idUtilizador = (int) $id;
        $utilizadorModelo = new Utilizador();
        $utilizadorExistente = $utilizadorModelo->encontrarPorId($idUtilizador);

        if (!$utilizadorExistente) {
            Sessao::guardarMensagem('erro', 'Utilizador não encontrado.');
            $this->redirecionar('/utilizadores');
            return;
        }

        $dados = $_POST;
        $erros = $this->validarDados($dados, $idUtilizador);

        $novoAdministrador = !empty($dados['Administrador']);
        $novoActivo        = !empty($dados['Activo']);
        $eraAdminActivo    = (bool) $utilizadorExistente['Administrador'] && (bool) $utilizadorExistente['Activo'];
        $vaiPerderPrivilegios = $eraAdminActivo && (!$novoAdministrador || !$novoActivo);

        $utilizadorSessao = Sessao::utilizador();
        $eASuaPropriaConta = $utilizadorSessao && (int) $utilizadorSessao['Id'] === $idUtilizador;

        if ($eASuaPropriaConta && $vaiPerderPrivilegios) {
            $erros[] = 'Não pode remover os seus próprios privilégios de administrador nem desactivar a sua própria conta.';
        } elseif ($vaiPerderPrivilegios && $utilizadorModelo->contarAdministradoresActivos() <= 1) {
            $erros[] = 'Não pode remover o único administrador activo do sistema.';
        }

        if (!empty($dados['PalavraPasse']) && strlen($dados['PalavraPasse']) < 8) {
            $erros[] = 'A nova palavra-passe deve ter pelo menos 8 caracteres.';
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/utilizadores/' . $idUtilizador . '/editar');
            return;
        }

        $utilizadorModelo->actualizarDados($idUtilizador, trim($dados['Nome']), trim($dados['Email']), $novoAdministrador, $novoActivo);

        if (!empty($dados['PalavraPasse'])) {
            $utilizadorModelo->redefinirPalavraPasse($idUtilizador, $dados['PalavraPasse']);
        }

        Sessao::guardarMensagem('sucesso', 'Utilizador actualizado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    private function validarDados(array $dados, ?int $idAIgnorar = null): array
    {
        $erros = [];

        $nome = trim($dados['Nome'] ?? '');
        if ($nome === '') {
            $erros[] = 'O nome de utilizador é obrigatório.';
        } elseif ((new Utilizador())->nomeEmUso($nome, $idAIgnorar)) {
            $erros[] = 'Já existe um utilizador com esse nome.';
        }

        if (trim($dados['Email'] ?? '') === '') {
            $erros[] = 'O email é obrigatório.';
        } elseif (!filter_var($dados['Email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'O email indicado não é válido.';
        }

        return $erros;
    }
}
