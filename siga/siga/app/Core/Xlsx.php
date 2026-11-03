<?php

namespace App\Core;

use ZipArchive;

/**
 * Gerador mínimo de ficheiros .xlsx (Office Open XML), sem dependências
 * externas — só precisa da extensão PHP "zip" (comum em alojamento cPanel).
 *
 * Cada coluna é descrita por ['rotulo' => ..., 'tipo' => ...], com tipo
 * 'texto' | 'inteiro' | 'dinheiro' | 'data' (datas recebidas como aaaa-mm-dd).
 * O ficheiro tem uma folha, com título, linhas de informação, cabeçalho a
 * negrito (com filtro e painel congelado), dados e linha de totais opcional.
 */
class Xlsx
{
    // Índices dos estilos (cellXfs) definidos em styles.xml
    private const E_TITULO = 1, E_CABECALHO = 2, E_DATA = 3, E_DINHEIRO = 4, E_INTEIRO = 5,
        E_NEGRITO = 6, E_NEGRITO_DINHEIRO = 7, E_NEGRITO_INTEIRO = 8, E_INFO = 9;

    public static function disponivel(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * Constrói o ficheiro e devolve o caminho de um ficheiro temporário
     * (quem chama é responsável por o apagar).
     */
    public static function criar(string $nomeFolha, string $titulo, array $linhasInfo, array $colunas, array $linhas, ?array $totais = null): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'siga_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($caminho, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o ficheiro Excel.');
        }

        $zip->addFromString('[Content_Types].xml', self::tiposConteudo());
        $zip->addFromString('_rels/.rels', self::relacoesRaiz());
        $zip->addFromString('xl/workbook.xml', self::livro(self::nomeFolhaValido($nomeFolha)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::relacoesLivro());
        $zip->addFromString('xl/styles.xml', self::estilos());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::folha($titulo, $linhasInfo, $colunas, $linhas, $totais));
        $zip->close();

        return $caminho;
    }

    /**
     * Envia o ficheiro ao browser como descarga e termina o pedido.
     */
    public static function enviar(string $nomeFicheiro, string $nomeFolha, string $titulo, array $linhasInfo, array $colunas, array $linhas, ?array $totais = null): void
    {
        $caminho = self::criar($nomeFolha, $titulo, $linhasInfo, $colunas, $linhas, $totais);

        $nomeSeguro = preg_replace('/[^A-Za-z0-9_.-]/', '_', $nomeFicheiro);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nomeSeguro . '"');
        header('Content-Length: ' . filesize($caminho));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($caminho);
        unlink($caminho);
        exit;
    }

    // ------------------------------------------------------------------

    private static function folha(string $titulo, array $linhasInfo, array $colunas, array $linhas, ?array $totais): string
    {
        $nCol = count($colunas);
        $ultimaCol = self::letra($nCol - 1);
        $linhaXml = [];
        $n = 1;

        $linhaXml[] = '<row r="' . $n . '">' . self::celulaTexto('A', $n, $titulo, self::E_TITULO) . '</row>';
        $n++;
        foreach ($linhasInfo as $info) {
            $linhaXml[] = '<row r="' . $n . '">' . self::celulaTexto('A', $n, $info, self::E_INFO) . '</row>';
            $n++;
        }
        $n++; // linha em branco

        $linhaCabecalho = $n;
        $celulas = '';
        foreach ($colunas as $i => $c) {
            $celulas .= self::celulaTexto(self::letra($i), $n, $c['rotulo'], self::E_CABECALHO);
        }
        $linhaXml[] = '<row r="' . $n . '">' . $celulas . '</row>';
        $n++;

        $larguras = [];
        foreach ($colunas as $i => $c) {
            $larguras[$i] = mb_strlen($c['rotulo']) + 3;
        }

        $primeiraDados = $n;
        foreach ($linhas as $linha) {
            $celulas = '';
            foreach ($colunas as $i => $c) {
                $valor = $linha[$i] ?? null;
                $celulas .= self::celula(self::letra($i), $n, $valor, $c['tipo'], false);
                $larguras[$i] = max($larguras[$i], self::larguraDe($valor, $c['tipo']));
            }
            $linhaXml[] = '<row r="' . $n . '">' . $celulas . '</row>';
            $n++;
        }
        $ultimaDados = $n - 1;

        if ($totais !== null) {
            $celulas = '';
            foreach ($colunas as $i => $c) {
                $valor = $totais[$i] ?? null;
                $celulas .= self::celula(self::letra($i), $n, $valor, $c['tipo'], true);
                $larguras[$i] = max($larguras[$i], self::larguraDe($valor, $c['tipo']));
            }
            $linhaXml[] = '<row r="' . $n . '">' . $celulas . '</row>';
        }

        $colunasXml = '';
        foreach ($larguras as $i => $l) {
            $l = max(8, min(60, $l + 2));
            $colunasXml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $l . '" customWidth="1"/>';
        }

        $filtro = $ultimaDados >= $linhaCabecalho
            ? '<autoFilter ref="A' . $linhaCabecalho . ':' . $ultimaCol . max($ultimaDados, $linhaCabecalho) . '"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="' . $linhaCabecalho . '" topLeftCell="A' . ($linhaCabecalho + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $colunasXml . '</cols>'
            . '<sheetData>' . implode('', $linhaXml) . '</sheetData>'
            . $filtro
            . '</worksheet>';
    }

    private static function celula(string $col, int $linha, $valor, string $tipo, bool $negrito): string
    {
        $ref = $col . $linha;

        if ($valor === null || $valor === '') {
            return $negrito ? '<c r="' . $ref . '" s="' . self::E_NEGRITO . '"/>' : '';
        }

        switch ($tipo) {
            case 'inteiro':
                return '<c r="' . $ref . '" s="' . ($negrito ? self::E_NEGRITO_INTEIRO : self::E_INTEIRO) . '"><v>' . (int) $valor . '</v></c>';
            case 'dinheiro':
                return '<c r="' . $ref . '" s="' . ($negrito ? self::E_NEGRITO_DINHEIRO : self::E_DINHEIRO) . '"><v>' . number_format((float) $valor, 2, '.', '') . '</v></c>';
            case 'data':
                $serie = self::serieData((string) $valor);
                if ($serie !== null) {
                    return '<c r="' . $ref . '" s="' . self::E_DATA . '"><v>' . $serie . '</v></c>';
                }
                // continua para texto
            default:
                return self::celulaTexto($col, $linha, (string) $valor, $negrito ? self::E_NEGRITO : 0);
        }
    }

    private static function celulaTexto(string $col, int $linha, string $texto, int $estilo): string
    {
        return '<c r="' . $col . $linha . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">'
            . self::escapar($texto) . '</t></is></c>';
    }

    private static function escapar(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto) ?? '';
        return htmlspecialchars($texto, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /** Número de série do Excel (dias desde 1899-12-30) para aaaa-mm-dd. */
    private static function serieData(string $dataBd): ?int
    {
        $d = \DateTime::createFromFormat('!Y-m-d', substr($dataBd, 0, 10));
        if (!$d) {
            return null;
        }
        $base = new \DateTime('1899-12-30');
        return (int) $base->diff($d)->format('%r%a');
    }

    private static function larguraDe($valor, string $tipo): int
    {
        if ($valor === null) {
            return 0;
        }
        return match ($tipo) {
            'data'     => 10,
            'dinheiro' => 12,
            'inteiro'  => strlen((string) (int) $valor),
            default    => mb_strlen((string) $valor),
        };
    }

    private static function letra(int $indice): string
    {
        $letra = '';
        $indice++;
        while ($indice > 0) {
            $resto = ($indice - 1) % 26;
            $letra = chr(65 + $resto) . $letra;
            $indice = intdiv($indice - 1, 26);
        }
        return $letra;
    }

    private static function nomeFolhaValido(string $nome): string
    {
        $nome = preg_replace('/[\[\]:*?\/\\\\]/u', ' ', $nome) ?? 'Relatório';
        $nome = trim(mb_substr($nome, 0, 31));
        return $nome !== '' ? $nome : 'Relatório';
    }

    private static function tiposConteudo(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function relacoesRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function livro(string $nomeFolha): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::escapar($nomeFolha) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function relacoesLivro(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2">'
            . '<numFmt numFmtId="164" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="165" formatCode="#,##0.00\ &quot;€&quot;"/>'
            . '</numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF666666"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDDE6F0"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left/><right/><top/><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="165" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
            . '<xf numFmtId="1" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
