<?php

namespace App\Controllers;

use App\Core\Actividade;
use App\Core\Controller;
use App\Core\Sessao;
use App\Core\Xlsx;
use App\Models\AnoEscotista;
use App\Models\Associado;
use App\Models\Censo;
use App\Models\Companhia;
use App\Models\Relatorio;

/**
 * Relatórios (regra 60): vistos no browser e exportáveis para .xlsx
 * (parâmetro formato=xlsx). Respeitam a regra 2: um utilizador
 * não-administrador só vê dados da sua própria companhia.
 */
class RelatoriosController extends Controller
{
    private const RELATORIOS = [
        'associados' => ['Listagem de associados', 'Associados com divisão, companhia, NIF e contactos, com filtros.'],
        'efectivo'   => ['Efectivo por companhia e divisão', 'Associados activos (sem honorários) por companhia e por divisão.'],
        'censos'     => ['Censos: pagos e em dívida', 'Estado de pagamento do Censo por ano escotista, com valores e totais.'],
        'dirigentes' => ['Dirigentes, cargos e órgãos', 'Quem ocupa que cargo ou órgão.'],
    ];

    public function index(): void
    {
        $this->exigirAutenticacao();

        $this->vista('relatorios/index', [
            'titulo'     => 'Relatórios',
            'relatorios' => self::RELATORIOS,
        ]);
    }

    public function associados(): void
    {
        $this->exigirAutenticacao();
        [$idCompanhia, $filtroCompanhia] = $this->companhiaDoPedido();

        $filtros = [
            'idSecao'    => ctype_digit((string) ($_GET['idSecao'] ?? '')) ? (string) $_GET['idSecao'] : '',
            'estado'     => in_array($_GET['estado'] ?? '1', ['1', '0', ''], true) ? ($_GET['estado'] ?? '1') : '1',
            'honorarios' => in_array($_GET['honorarios'] ?? 'incluir', ['incluir', 'excluir', 'so'], true) ? ($_GET['honorarios'] ?? 'incluir') : 'incluir',
        ];

        $modelo = new Relatorio();
        $dados = $modelo->associados($idCompanhia, $filtros);

        $divisoes = ['' => 'Todas as divisões'];
        foreach ($modelo->divisoes() as $d) {
            $divisoes[(string) $d['Id']] = $d['Designacao'];
        }

        $linhas = array_map(fn($r) => [
            $r['NumeroAssociado'], $r['Nome'], $r['Divisao'], $r['Companhia'] ?? 'Sem companhia local',
            (int) $r['Activo'] === 1 ? 'Activo' : 'Inactivo',
            (int) $r['MembroHonorario'] === 1 ? 'Sim' : 'Não',
            $r['DataNascimento'], $r['NIF'], $r['Telemovel'], $r['Email'], $r['EmailAssociativo'],
        ], $dados);

        $this->responder('associados', [
            'colunas' => [
                ['rotulo' => 'Nº associado', 'tipo' => 'texto'],
                ['rotulo' => 'Nome', 'tipo' => 'texto'],
                ['rotulo' => 'Divisão', 'tipo' => 'texto'],
                ['rotulo' => 'Companhia', 'tipo' => 'texto'],
                ['rotulo' => 'Estado', 'tipo' => 'texto'],
                ['rotulo' => 'Honorário', 'tipo' => 'texto'],
                ['rotulo' => 'Data de nascimento', 'tipo' => 'data'],
                ['rotulo' => 'NIF', 'tipo' => 'texto'],
                ['rotulo' => 'Telemóvel', 'tipo' => 'texto'],
                ['rotulo' => 'Email', 'tipo' => 'texto'],
                ['rotulo' => 'Email associativo', 'tipo' => 'texto'],
            ],
            'linhas' => $linhas,
            'totais' => null,
            'resumo' => ['Associados listados' => count($linhas)],
            'filtros' => array_merge($filtroCompanhia, [
                ['nome' => 'idSecao', 'rotulo' => 'Divisão', 'opcoes' => $divisoes, 'valor' => $filtros['idSecao']],
                ['nome' => 'estado', 'rotulo' => 'Estado', 'opcoes' => ['1' => 'Activos', '0' => 'Inactivos', '' => 'Todos'], 'valor' => $filtros['estado']],
                ['nome' => 'honorarios', 'rotulo' => 'Membros honorários', 'opcoes' => ['incluir' => 'Incluir', 'excluir' => 'Excluir', 'so' => 'Só honorários'], 'valor' => $filtros['honorarios']],
            ]),
            'descricaoFiltros' => $this->descreverFiltros($filtroCompanhia, [
                'Divisão' => $divisoes[$filtros['idSecao']] ?? '',
                'Estado' => ['1' => 'Activos', '0' => 'Inactivos', '' => 'Todos'][$filtros['estado']],
                'Membros honorários' => ['incluir' => 'Incluir', 'excluir' => 'Excluir', 'so' => 'Só honorários'][$filtros['honorarios']],
            ]),
        ]);
    }

    public function efectivo(): void
    {
        $this->exigirAutenticacao();
        [$idCompanhia, $filtroCompanhia] = $this->companhiaDoPedido();

        $modelo = new Relatorio();
        $divisoes = array_column($modelo->divisoes(), 'Designacao');
        $contagens = $modelo->efectivoPorCompanhiaEDivisao($idCompanhia);

        // Linhas-base: as companhias locais pedidas (mesmo sem associados) e,
        // se aplicável, os associados sem companhia local.
        $matriz = [];
        foreach ((new Companhia())->listarLocais() as $c) {
            if ($idCompanhia === null || (is_int($idCompanhia) && (int) $c['Id'] === $idCompanhia)) {
                $matriz[$c['Designacao']] = [];
            }
        }
        $semDivisao = false;
        foreach ($contagens as $c) {
            $nomeCompanhia = $c['Companhia'] ?? 'Sem companhia local (Chefia Nacional / órgãos)';
            $nomeDivisao = $c['Divisao'] ?? 'Sem divisão';
            if ($c['Divisao'] === null) {
                $semDivisao = true;
            }
            $matriz[$nomeCompanhia][$nomeDivisao] = ($matriz[$nomeCompanhia][$nomeDivisao] ?? 0) + (int) $c['Total'];
        }

        $colunasDivisao = $divisoes;
        if ($semDivisao) {
            $colunasDivisao[] = 'Sem divisão';
        }

        $colunas = [['rotulo' => 'Companhia', 'tipo' => 'texto']];
        foreach ($colunasDivisao as $d) {
            $colunas[] = ['rotulo' => $d, 'tipo' => 'inteiro'];
        }
        $colunas[] = ['rotulo' => 'Total', 'tipo' => 'inteiro'];

        $linhas = [];
        $somas = array_fill(0, count($colunasDivisao) + 1, 0);
        foreach ($matriz as $nomeCompanhia => $porDivisao) {
            $linha = [$nomeCompanhia];
            $totalLinha = 0;
            foreach ($colunasDivisao as $i => $d) {
                $v = $porDivisao[$d] ?? 0;
                $linha[] = $v;
                $totalLinha += $v;
                $somas[$i] += $v;
            }
            $linha[] = $totalLinha;
            $somas[count($colunasDivisao)] += $totalLinha;
            $linhas[] = $linha;
        }
        $totais = array_merge(['Total'], $somas);

        $this->responder('efectivo', [
            'colunas' => $colunas,
            'linhas'  => $linhas,
            'totais'  => $totais,
            'resumo'  => ['Efectivo total' => $somas[count($colunasDivisao)]],
            'filtros' => $filtroCompanhia,
            'descricaoFiltros' => $this->descreverFiltros($filtroCompanhia, []),
            'nota'    => 'Não inclui membros honorários nem associados inactivos.',
        ]);
    }

    public function censos(): void
    {
        $this->exigirAutenticacao();
        [$idCompanhia, $filtroCompanhia] = $this->companhiaDoPedido();

        $anos = (new AnoEscotista())->listarTodos();
        if (empty($anos)) {
            $this->responder('censos', [
                'colunas' => [], 'linhas' => [], 'totais' => null, 'resumo' => [],
                'filtros' => [], 'descricaoFiltros' => [],
                'nota' => 'Ainda não existem anos escotistas definidos (Administração → Anos escotistas).',
            ]);
            return;
        }

        $opcoesAnos = [];
        $anoPorId = [];
        foreach ($anos as $a) {
            $opcoesAnos[(string) $a['Id']] = $a['AnoInicio'] . '/' . ($a['AnoInicio'] + 1);
            $anoPorId[(string) $a['Id']] = $a;
        }
        $idAno = (string) ($_GET['ano'] ?? '');
        if (!isset($anoPorId[$idAno])) {
            $idAno = (string) $anos[0]['Id'];
        }
        $ano = $anoPorId[$idAno];
        $estado = in_array($_GET['estado'] ?? '', ['pago', 'divida'], true) ? $_GET['estado'] : '';

        $valorUnitario = (float) $ano['ValorSeguroEscotista'] + (float) $ano['ValorQuotaUEP'] + (float) $ano['ValorQuotaWFIS'];

        $registos = (new Censo())->listarParaAno((int) $idAno, is_int($idCompanhia) ? $idCompanhia : null);
        if ($idCompanhia === 'sem') {
            $registos = array_values(array_filter($registos, fn($r) => $r['CompanhiaActual'] === null));
        }

        $totalAssociados = count($registos);
        $pagos = count(array_filter($registos, fn($r) => (int) $r['Pago'] === 1));

        if ($estado === 'pago') {
            $registos = array_values(array_filter($registos, fn($r) => (int) $r['Pago'] === 1));
        } elseif ($estado === 'divida') {
            $registos = array_values(array_filter($registos, fn($r) => (int) $r['Pago'] !== 1));
        }

        $linhas = array_map(fn($r) => [
            $r['NumeroAssociado'], $r['Nome'], $r['CompanhiaActual'] ?? 'Sem companhia local',
            $valorUnitario,
            (int) $r['Pago'] === 1 ? 'Pago' : 'Em dívida',
            (int) $r['Pago'] === 1 ? $r['DataPagamento'] : null,
        ], $registos);

        $emDivida = $totalAssociados - $pagos;

        $this->responder('censos', [
            'colunas' => [
                ['rotulo' => 'Nº associado', 'tipo' => 'texto'],
                ['rotulo' => 'Nome', 'tipo' => 'texto'],
                ['rotulo' => 'Companhia', 'tipo' => 'texto'],
                ['rotulo' => 'Valor do Censo', 'tipo' => 'dinheiro'],
                ['rotulo' => 'Estado', 'tipo' => 'texto'],
                ['rotulo' => 'Data de pagamento', 'tipo' => 'data'],
            ],
            'linhas' => $linhas,
            'totais' => ['Total (' . count($linhas) . ')', null, null, $valorUnitario * count($linhas), null, null],
            'resumo' => [
                'Ano escotista' => $opcoesAnos[$idAno],
                'Valor por associado' => number_format($valorUnitario, 2, ',', ' ') . ' € (seguro '
                    . number_format((float) $ano['ValorSeguroEscotista'], 2, ',', ' ') . ' + UEP '
                    . number_format((float) $ano['ValorQuotaUEP'], 2, ',', ' ') . ' + WFIS '
                    . number_format((float) $ano['ValorQuotaWFIS'], 2, ',', ' ') . ')',
                'Associados abrangidos' => $totalAssociados,
                'Pagos' => $pagos . ' (' . number_format($pagos * $valorUnitario, 2, ',', ' ') . ' €)',
                'Em dívida' => $emDivida . ' (' . number_format($emDivida * $valorUnitario, 2, ',', ' ') . ' €)',
            ],
            'filtros' => array_merge([
                ['nome' => 'ano', 'rotulo' => 'Ano escotista', 'opcoes' => $opcoesAnos, 'valor' => $idAno],
            ], $filtroCompanhia, [
                ['nome' => 'estado', 'rotulo' => 'Estado', 'opcoes' => ['' => 'Todos', 'pago' => 'Pagos', 'divida' => 'Em dívida'], 'valor' => $estado],
            ]),
            'descricaoFiltros' => $this->descreverFiltros($filtroCompanhia, [
                'Estado' => ['' => 'Todos', 'pago' => 'Pagos', 'divida' => 'Em dívida'][$estado],
            ]),
            'nota' => 'Só associados activos e não honorários (regra 51). O resumo refere-se a todos os abrangidos, independentemente do filtro de estado.',
        ]);
    }

    public function dirigentes(): void
    {
        $this->exigirAutenticacao();
        [$idCompanhia, $filtroCompanhia] = $this->companhiaDoPedido();

        $tipo = in_array($_GET['tipo'] ?? '', ['cargo', 'orgao'], true) ? $_GET['tipo'] : '';
        $dados = (new Relatorio())->dirigentes($idCompanhia, $tipo);

        $linhas = array_map(fn($r) => [
            $r['Tipo'], $r['Designacao'], $r['Nome'], $r['NumeroAssociado'],
            $r['Companhia'] ?? 'Sem companhia local',
            $r['NaChefiaNacional'] ? 'Sim' : 'Não',
            $r['DataInicio'],
        ], $dados);

        $this->responder('dirigentes', [
            'colunas' => [
                ['rotulo' => 'Tipo', 'tipo' => 'texto'],
                ['rotulo' => 'Cargo / Órgão', 'tipo' => 'texto'],
                ['rotulo' => 'Nome', 'tipo' => 'texto'],
                ['rotulo' => 'Nº associado', 'tipo' => 'texto'],
                ['rotulo' => 'Companhia', 'tipo' => 'texto'],
                ['rotulo' => 'Chefia Nacional', 'tipo' => 'texto'],
                ['rotulo' => 'Desde', 'tipo' => 'data'],
            ],
            'linhas' => $linhas,
            'totais' => null,
            'resumo' => ['Registos' => count($linhas)],
            'filtros' => array_merge($filtroCompanhia, [
                ['nome' => 'tipo', 'rotulo' => 'Tipo', 'opcoes' => ['' => 'Cargos e órgãos', 'cargo' => 'Só cargos', 'orgao' => 'Só órgãos'], 'valor' => $tipo],
            ]),
            'descricaoFiltros' => $this->descreverFiltros($filtroCompanhia, [
                'Tipo' => ['' => 'Cargos e órgãos', 'cargo' => 'Só cargos', 'orgao' => 'Só órgãos'][$tipo],
            ]),
            'nota' => 'Um associado aparece uma vez por cada cargo ou órgão que ocupa (só associados activos).',
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Filtro de companhia do pedido. Administradores escolhem (todas, uma,
     * ou "sem companhia local"); os restantes ficam fixos na sua companhia
     * (regra 2) e o filtro nem lhes é apresentado.
     *
     * @return array{0: int|string|null, 1: array} [valor para a consulta, definição do filtro]
     */
    private function companhiaDoPedido(): array
    {
        if (!Sessao::ehAdministrador()) {
            $idAssociado = Sessao::idAssociado();
            $id = $idAssociado
                ? ((new Associado())->companhiaActual($idAssociado)['IdCompanhia'] ?? -1)
                : -1;
            return [(int) $id, []];
        }

        $opcoes = ['' => 'Todas as companhias'];
        foreach ((new Companhia())->listarLocais() as $c) {
            $opcoes[(string) $c['Id']] = $c['Designacao'];
        }
        $opcoes['sem'] = 'Sem companhia local (Chefia Nacional / órgãos)';

        $pedido = (string) ($_GET['idCompanhia'] ?? '');
        if (!isset($opcoes[$pedido])) {
            $pedido = '';
        }

        $valor = $pedido === '' ? null : ($pedido === 'sem' ? 'sem' : (int) $pedido);

        return [$valor, [['nome' => 'idCompanhia', 'rotulo' => 'Companhia', 'opcoes' => $opcoes, 'valor' => $pedido]]];
    }

    private function descreverFiltros(array $filtroCompanhia, array $outros): array
    {
        $linhas = [];
        if ($filtroCompanhia) {
            $f = $filtroCompanhia[0];
            $linhas[] = 'Companhia: ' . $f['opcoes'][$f['valor']];
        } else {
            $linhas[] = 'Companhia: a sua companhia (restrição por permissões)';
        }
        foreach ($outros as $rotulo => $valor) {
            if ($valor !== '') {
                $linhas[] = $rotulo . ': ' . $valor;
            }
        }
        return $linhas;
    }

    /**
     * Mostra o relatório no browser ou, com formato=xlsx, descarrega-o.
     */
    private function responder(string $chave, array $relatorio): void
    {
        $titulo = self::RELATORIOS[$chave][0];
        $relatorio['chave'] = $chave;
        $relatorio['titulo'] = $titulo;

        if (($_GET['formato'] ?? '') === 'xlsx' && !empty($relatorio['colunas'])) {
            if (!Xlsx::disponivel()) {
                Sessao::guardarMensagem('erro', 'A exportação para Excel precisa da extensão PHP "zip", que não está activa neste servidor.');
                $this->redirecionar('/relatorios/' . $chave);
            }

            $utilizador = Sessao::utilizador();
            Actividade::registar('exportar', 'Exportou para Excel o relatório "' . $titulo . '"', 'sucesso', $utilizador);

            $info = ['Gerado em ' . date('d/m/Y H:i') . ' por ' . ($utilizador['Nome'] ?? '')];
            foreach ($relatorio['resumo'] as $r => $v) {
                $info[] = $r . ': ' . $v;
            }
            $info = array_merge($info, $relatorio['descricaoFiltros']);

            Xlsx::enviar(
                'SIGA_' . $chave . '_' . date('Ymd_His') . '.xlsx',
                $titulo,
                $titulo,
                $info,
                $relatorio['colunas'],
                $relatorio['linhas'],
                $relatorio['totais']
            );
        }

        $this->vista('relatorios/ver', $relatorio);
    }
}
