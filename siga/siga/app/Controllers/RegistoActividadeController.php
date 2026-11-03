<?php

namespace App\Controllers;

use App\Core\Actividade;
use App\Core\Controller;
use App\Models\RegistoActividade;

/**
 * Consulta do registo de actividade — acesso restrito ao utilizador "Administrador".
 */
class RegistoActividadeController extends Controller
{
    private const POR_PAGINA = 50;

    public function index(): void
    {
        $this->exigirSuperAdministrador();

        $dataValida = static function (string $v): string {
            $d = \DateTime::createFromFormat('!Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };

        $accao = (string) ($_GET['accao'] ?? '');
        $resultado = (string) ($_GET['resultado'] ?? '');

        $filtros = [
            'idUtilizador' => ctype_digit((string) ($_GET['idUtilizador'] ?? '')) ? (string) $_GET['idUtilizador'] : '',
            'accao'        => isset(Actividade::ACCOES[$accao]) ? $accao : '',
            'resultado'    => in_array($resultado, ['sucesso', 'falhou'], true) ? $resultado : '',
            'de'           => $dataValida((string) ($_GET['de'] ?? '')),
            'ate'          => $dataValida((string) ($_GET['ate'] ?? '')),
            'pesquisa'     => trim((string) ($_GET['pesquisa'] ?? '')),
        ];

        $modelo = new RegistoActividade();
        $total = $modelo->contar($filtros);
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->vista('admin/registo_actividade', [
            'titulo'        => 'Registo de actividade',
            'linhas'        => $modelo->pesquisar($filtros, self::POR_PAGINA, ($pagina - 1) * self::POR_PAGINA),
            'filtros'       => $filtros,
            'utilizadores'  => $modelo->utilizadoresRegistados(),
            'accoes'        => Actividade::ACCOES,
            'total'         => $total,
            'pagina'        => $pagina,
            'totalPaginas'  => $totalPaginas,
        ]);
    }
}
