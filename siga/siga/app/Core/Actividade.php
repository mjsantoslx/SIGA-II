<?php

namespace App\Core;

use PDO;
use Throwable;

/**
 * Registo de actividade (regra 59): quem fez o quê e quando.
 *
 * - Todos os pedidos POST das rotas listadas em ROTAS (ou seja, tudo o que
 *   cria, edita, desactiva, reactiva ou elimina dados) são registados
 *   automaticamente pelo Router, sem ser preciso alterar cada controlador.
 * - Inícios/fins de sessão e tentativas falhadas são registados pelo
 *   AuthController.
 * - Nunca se guardam valores submetidos (palavras-passe, dados de saúde,
 *   etc.) — só um resumo legível da acção e do alvo.
 * - Uma falha ao registar nunca impede a aplicação de funcionar.
 */
class Actividade
{
    public const ACCOES = [
        'login'         => 'Início de sessão',
        'login_falhado' => 'Início de sessão falhado',
        'logout'        => 'Fim de sessão',
        'criar'         => 'Criação',
        'editar'        => 'Edição',
        'desativar'     => 'Desactivação',
        'reativar'      => 'Reactivação',
        'eliminar'      => 'Eliminação',
        'censo'         => 'Censo',
        'exportar'      => 'Exportação',
    ];

    /**
     * Rotas POST registadas: padrão => [acção, descrição].
     * Na descrição, {nome} é o parâmetro da rota; {tipo:nome} acrescenta o
     * nome do registo (ex.: "n.º 12 (Maria Silva)").
     */
    private const ROTAS = [
        '/associados/criar'                                  => ['criar',     'Registou um novo associado'],
        '/associados/{id}/editar'                            => ['editar',    'Editou o associado {associado:id}'],
        '/associados/{id}/desativar'                         => ['desativar', 'Desactivou o associado {associado:id}'],
        '/associados/{id}/reativar'                          => ['reativar',  'Reactivou o associado {associado:id}'],
        '/associados/{id}/morada/corrigir'                   => ['editar',    'Corrigiu a morada do associado {associado:id}'],
        '/associados/{id}/morada/substituir'                 => ['editar',    'Substituiu a morada do associado {associado:id}'],
        '/associados/{id}/contactos/adicionar'               => ['criar',     'Adicionou um contacto ao associado {associado:id}'],
        '/associados/{id}/contactos/{idContacto}/editar'     => ['editar',    'Editou um contacto do associado {associado:id}'],
        '/associados/{id}/contactos/{idContacto}/remover'    => ['eliminar',  'Removeu um contacto do associado {associado:id}'],
        '/associados/{id}/ficha-saude'                       => ['editar',    'Actualizou a ficha de saúde do associado {associado:id}'],
        '/companhias/criar'                                  => ['criar',     'Criou uma nova companhia'],
        '/companhias/{id}/editar'                            => ['editar',    'Editou a companhia {companhia:id}'],
        '/companhias/{id}/morada/corrigir'                   => ['editar',    'Corrigiu a morada da companhia {companhia:id}'],
        '/companhias/{id}/morada/substituir'                 => ['editar',    'Substituiu a morada da companhia {companhia:id}'],
        '/utilizadores/criar'                                => ['criar',     'Criou um novo utilizador'],
        '/utilizadores/{id}/editar'                          => ['editar',    'Editou o utilizador {utilizador:id}'],
        '/admin/anos-escotistas/criar'                       => ['criar',     'Criou um novo ano escotista'],
        '/admin/anos-escotistas/{id}/editar'                 => ['editar',    'Editou o ano escotista {ano:id}'],
        '/censos/{idAssociado}/{idAnoEscotista}/pago'        => ['censo',     'Registou o pagamento do Censo do associado {associado:idAssociado}, ano escotista {ano:idAnoEscotista}'],
        '/censos/{idAssociado}/{idAnoEscotista}/nao-pago'    => ['censo',     'Anulou o pagamento do Censo do associado {associado:idAssociado}, ano escotista {ano:idAnoEscotista}'],
        '/admin/referencias/{tabela}/criar'                  => ['criar',     'Criou um registo em "{tabela}"'],
        '/admin/referencias/{tabela}/{id}/editar'            => ['editar',    'Editou o registo n.º {id} de "{tabela}"'],
        '/admin/referencias/{tabela}/{id}/eliminar'          => ['eliminar',  'Eliminou o registo n.º {id} de "{tabela}"'],
    ];

    /**
     * Chamado pelo Router antes de despachar um POST: se a rota estiver
     * mapeada, agenda o registo para o fim do pedido (quando já se sabe se
     * correu bem ou mal).
     */
    public static function acompanharPedido(string $metodo, string $padrao, array $parametros): void
    {
        if ($metodo !== 'POST' || !isset(self::ROTAS[$padrao])) {
            return;
        }

        [$accao, $modelo] = self::ROTAS[$padrao];

        register_shutdown_function(static function () use ($accao, $modelo, $parametros) {
            try {
                $utilizador = Sessao::utilizador();
                if ($utilizador === null) {
                    return; // pedido sem sessão (ex.: bloqueado antes do login)
                }

                $mensagemErro = null;
                foreach ($_SESSION['mensagens'] ?? [] as $m) {
                    if (($m['tipo'] ?? '') === 'erro') {
                        $mensagemErro = (string) $m['texto'];
                        break;
                    }
                }

                $erroFatal = error_get_last();
                $fatal = $erroFatal !== null
                    && in_array($erroFatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
                $falhou = $mensagemErro !== null || $fatal || http_response_code() >= 400;

                $descricao = self::compor($modelo, $parametros);
                if ($falhou) {
                    $descricao .= ' — NÃO CONCLUÍDO' . ($mensagemErro !== null ? ': ' . $mensagemErro : '');
                }

                self::registar($accao, $descricao, $falhou ? 'falhou' : 'sucesso', $utilizador);
            } catch (Throwable $e) {
                error_log('[SIGA] Falha ao registar actividade: ' . $e->getMessage());
            }
        });
    }

    /**
     * Grava uma linha no registo. Nunca lança excepções.
     */
    public static function registar(
        string $accao,
        string $descricao,
        string $resultado = 'sucesso',
        ?array $utilizador = null,
        ?string $nomeUtilizador = null
    ): void {
        try {
            $stmt = Database::ligacao()->prepare(
                'INSERT INTO registo_actividade
                    (DataHora, IdUtilizador, NomeUtilizador, Accao, Descricao, Resultado, EnderecoIP)
                 VALUES (:dh, :idu, :nome, :accao, :desc, :res, :ip)'
            );
            $stmt->execute([
                'dh'    => date('Y-m-d H:i:s'),
                'idu'   => $utilizador['Id'] ?? null,
                'nome'  => mb_substr((string) ($utilizador['Nome'] ?? $nomeUtilizador ?? ''), 0, 100) ?: null,
                'accao' => $accao,
                'desc'  => mb_substr($descricao, 0, 500),
                'res'   => $resultado === 'falhou' ? 'falhou' : 'sucesso',
                'ip'    => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            ]);
        } catch (Throwable $e) {
            error_log('[SIGA] Falha ao registar actividade: ' . $e->getMessage());
        }
    }

    private static function compor(string $modelo, array $parametros): string
    {
        return preg_replace_callback('/\{(?:(\w+):)?(\w+)\}/', static function ($m) use ($parametros) {
            [, $tipo, $nome] = $m;
            $valor = (string) ($parametros[$nome] ?? '');
            $valor = preg_replace('/[^\p{L}\p{N}_\- ]/u', '', $valor);

            if ($tipo === '') {
                return $valor;
            }

            $rotulo = ctype_digit($valor) ? self::rotulo($tipo, (int) $valor) : null;
            if ($tipo === 'ano') {
                return $rotulo !== null ? $rotulo : 'n.º ' . $valor;
            }
            return 'n.º ' . $valor . ($rotulo !== null ? ' (' . $rotulo . ')' : '');
        }, $modelo);
    }

    private static function rotulo(string $tipo, int $id): ?string
    {
        $sql = match ($tipo) {
            'associado'  => 'SELECT p.Nome FROM associados a INNER JOIN pessoas p ON p.Id = a.IdPessoa WHERE a.Id = :id',
            'companhia'  => 'SELECT Designacao FROM companhias WHERE Id = :id',
            'utilizador' => 'SELECT Nome FROM utilizadores WHERE Id = :id',
            'ano'        => "SELECT CONCAT(AnoInicio, '/', AnoInicio + 1) FROM anos_escotistas WHERE Id = :id",
            default      => null,
        };
        if ($sql === null) {
            return null;
        }

        $stmt = Database::ligacao()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? null : (string) $valor;
    }
}
