<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sessao;
use App\Models\Associado;
use App\Models\Utilizador;
use App\Models\UtilizadorAssociado;

/**
 * Gestão de utilizadores — acesso restrito a administradores.
 *
 * Regras aplicadas aqui (ver docs/regras_de_negocio.txt):
 *  - Regra 1: o utilizador "Administrador" nunca pode ser eliminado, nem
 *    desactivado, nem perder o estatuto de administrador — os campos
 *    correspondentes ficam bloqueados no formulário e são sempre forçados
 *    no servidor, independentemente do que for submetido.
 *  - Regra 3: para qualquer outro utilizador, o estatuto de administrador
 *    é inteiramente derivado da Chefia Nacional do associado ligado — não
 *    é uma opção manual no formulário.
 *  - Regra 2/4: todo o utilizador (excepto o "Administrador") tem de estar
 *    ligado a um associado; se esse associado não estiver na Chefia
 *    Nacional (ou seja, o utilizador não vai ser administrador), esse
 *    associado tem de ter uma companhia local.
 */
class UtilizadoresController extends Controller
{
    private const NOME_SUPERADMIN = 'Administrador';

    public function index(): void
    {
        $this->exigirAdministrador();

        $utilizadorAssociadoModelo = new UtilizadorAssociado();
        $associadoModelo = new Associado();

        $utilizadores = (new Utilizador())->listarTodos();
        foreach ($utilizadores as &$utilizador) {
            $idAssociado = $utilizadorAssociadoModelo->idAssociadoDoUtilizador((int) $utilizador['Id']);
            $utilizador['AssociadoLigado'] = null;
            if ($idAssociado) {
                $associado = $associadoModelo->encontrarCompletoPorId($idAssociado);
                $utilizador['AssociadoLigado'] = $associado['Nome'] ?? null;
            }
        }
        unset($utilizador);

        $this->vista('utilizadores/index', [
            'titulo'       => 'Utilizadores',
            'utilizadores' => $utilizadores,
        ]);
    }

    public function criar(): void
    {
        $this->exigirAdministrador();

        $this->vista('utilizadores/form', [
            'titulo'      => 'Novo utilizador',
            'modo'        => 'criar',
            'utilizador'  => null,
            'associados'  => (new Associado())->listarDisponiveisParaUtilizador(null),
        ]);
    }

    public function guardar(): void
    {
        $this->exigirAdministrador();
        $this->validarCsrf();

        $dados = $_POST;
        $erros = $this->validarDados($dados, null);

        if (empty($dados['PalavraPasse'])) {
            $erros[] = 'A palavra-passe é obrigatória para um novo utilizador.';
        } elseif (strlen($dados['PalavraPasse']) < 8) {
            $erros[] = 'A palavra-passe deve ter pelo menos 8 caracteres.';
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->vista('utilizadores/form', [
                'titulo'     => 'Novo utilizador',
                'modo'       => 'criar',
                'utilizador' => $dados,
                'associados' => (new Associado())->listarDisponiveisParaUtilizador(null),
            ]);
            return;
        }

        $idAssociado = (int) $dados['IdAssociado'];
        $administrador = (bool) (new Associado())->chefiaNacionalActual($idAssociado);

        $utilizadorModelo = new Utilizador();
        $idUtilizador = $utilizadorModelo->criar(trim($dados['Nome']), trim($dados['Email']), $dados['PalavraPasse'], $administrador);
        (new UtilizadorAssociado())->associar($idUtilizador, $idAssociado);

        Sessao::guardarMensagem('sucesso', 'Utilizador criado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    public function editar(string $id): void
    {
        $this->exigirAdministrador();

        $idUtilizador = (int) $id;
        $utilizador = (new Utilizador())->encontrarPorId($idUtilizador);
        if (!$utilizador) {
            Sessao::guardarMensagem('erro', 'Utilizador não encontrado.');
            $this->redirecionar('/utilizadores');
            return;
        }

        $idAssociadoActual = (new UtilizadorAssociado())->idAssociadoDoUtilizador($idUtilizador);

        $this->vista('utilizadores/editar', [
            'titulo'            => 'Editar — ' . $utilizador['Nome'],
            'utilizador'        => $utilizador,
            'ehSuperAdmin'      => $utilizador['Nome'] === self::NOME_SUPERADMIN,
            'idAssociadoActual' => $idAssociadoActual,
            'associados'        => (new Associado())->listarDisponiveisParaUtilizador($idUtilizador),
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

        $ehSuperAdmin = $utilizadorExistente['Nome'] === self::NOME_SUPERADMIN;
        $dados = $_POST;

        // Regra 1: o "Administrador" nunca perde privilégios, é desactivado,
        // ou muda de nome (o nome identifica-o de forma estável) — ignora-se
        // o que vier no formulário para estes campos.
        if ($ehSuperAdmin) {
            $dados['Nome'] = self::NOME_SUPERADMIN;
        }

        $erros = $this->validarDados($dados, $idUtilizador, $ehSuperAdmin);

        if (!empty($dados['PalavraPasse']) && strlen($dados['PalavraPasse']) < 8) {
            $erros[] = 'A nova palavra-passe deve ter pelo menos 8 caracteres.';
        }

        $utilizadorAssociadoModelo = new UtilizadorAssociado();
        $administrador = true; // por omissão para o Administrador; recalculado abaixo para os restantes.

        if (!$ehSuperAdmin) {
            $idAssociadoNovo = !empty($dados['IdAssociado']) ? (int) $dados['IdAssociado'] : null;
            if (!$idAssociadoNovo) {
                $erros[] = 'É obrigatório ligar este utilizador a um associado.';
            } else {
                $administrador = (bool) (new Associado())->chefiaNacionalActual($idAssociadoNovo);
            }

            // Salvaguardas de auto-remoção/último administrador, considerando
            // o estatuto de administrador que resultará desta gravação.
            $utilizadorSessao = Sessao::utilizador();
            $eASuaPropriaConta = $utilizadorSessao && (int) $utilizadorSessao['Id'] === $idUtilizador;
            $eraAdminActivo = (bool) $utilizadorExistente['Administrador'] && (bool) $utilizadorExistente['Activo'];
            $novoActivo = !empty($dados['Activo']);
            $vaiPerderPrivilegios = $eraAdminActivo && (!$administrador || !$novoActivo);

            if ($eASuaPropriaConta && $vaiPerderPrivilegios) {
                $erros[] = 'Não pode remover os seus próprios privilégios de administrador nem desactivar a sua própria conta.';
            } elseif ($vaiPerderPrivilegios && $utilizadorModelo->contarAdministradoresActivos() <= 1) {
                $erros[] = 'Não pode remover o único administrador activo do sistema.';
            }
        }

        if ($erros) {
            Sessao::guardarMensagem('erro', implode(' ', $erros));
            $this->redirecionar('/utilizadores/' . $idUtilizador . '/editar');
            return;
        }

        $novoActivo = $ehSuperAdmin ? true : !empty($dados['Activo']);
        $utilizadorModelo->actualizarDados($idUtilizador, trim($dados['Nome']), trim($dados['Email']), $administrador, $novoActivo);

        if (!$ehSuperAdmin) {
            $utilizadorAssociadoModelo->associar($idUtilizador, (int) $dados['IdAssociado']);
        }

        if (!empty($dados['PalavraPasse'])) {
            $utilizadorModelo->redefinirPalavraPasse($idUtilizador, $dados['PalavraPasse']);
        }

        Sessao::guardarMensagem('sucesso', 'Utilizador actualizado com sucesso.');
        $this->redirecionar('/utilizadores');
    }

    private function validarDados(array $dados, ?int $idAIgnorar, bool $ehSuperAdmin = false): array
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

        if (!$ehSuperAdmin && !empty($dados['IdAssociado'])) {
            $idAssociado = (int) $dados['IdAssociado'];
            $associadoModelo = new Associado();

            if ((new UtilizadorAssociado())->associadoJaLigadoAOutroUtilizador($idAssociado, $idAIgnorar)) {
                $erros[] = 'Esse associado já está ligado a outro utilizador.';
            }

            // Regra 2: um utilizador não-administrador (associado fora da
            // Chefia Nacional) tem de ter uma companhia local.
            $naChefiaNacional = (bool) $associadoModelo->chefiaNacionalActual($idAssociado);
            if (!$naChefiaNacional && !$associadoModelo->companhiaActual($idAssociado)) {
                $erros[] = 'O associado ligado tem de ter uma companhia local, a não ser que esteja na Chefia Nacional.';
            }
        }

        return $erros;
    }
}
