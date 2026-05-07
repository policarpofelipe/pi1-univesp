<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

$tipo = trim((string)($_GET['tipo'] ?? ''));

require_once __DIR__ . '/lib/importacao_planilha.php';

if (!importacao_planilha_tipo_valido($tipo)) {
    http_response_code(404);
    exit('Tipo inválido.');
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    header('Location: importar_planilha.php?tipo=' . urlencode($tipo) . '&erro=dependencia_planilha');
    exit;
}
require_once $autoload;

$cfg = importacao_planilha_config($tipo);
if ($cfg === null) {
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @return array<int, string>
 */
function opcoes_lista_nome(PDO $pdo, string $tabela): array
{
    $permitidas = ['categorias_peca', 'tipos_peca', 'marcas_produto', 'marcas_veiculo'];
    if (!in_array($tabela, $permitidas, true)) {
        return [];
    }
    $stmt = $pdo->query('SELECT nome FROM `' . $tabela . '` ORDER BY nome ASC');
    $nomes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nome = trim((string)($row['nome'] ?? ''));
        if ($nome !== '') {
            $nomes[] = $nome;
        }
    }
    return array_values(array_unique($nomes));
}

/**
 * @return array<int, string>
 */
function opcoes_modelos_compostos(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT mv.nome AS marca_nome, mo.nome AS modelo_nome
        FROM modelos_veiculo mo
        INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
        ORDER BY mv.nome ASC, mo.nome ASC
    ");
    $itens = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $valor = trim((string)$row['marca_nome']) . ' / ' . trim((string)$row['modelo_nome']);
        if (trim($valor) !== '/') {
            $itens[] = $valor;
        }
    }
    return array_values(array_unique($itens));
}

/**
 * @return array<int, string>
 */
function opcoes_skus_produtos(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT sku_interno FROM produtos ORDER BY sku_interno ASC');
    $itens = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sku = trim((string)($row['sku_interno'] ?? ''));
        if ($sku !== '') {
            $itens[] = $sku;
        }
    }
    return array_values(array_unique($itens));
}

/**
 * @return array<string, array<int, string>>
 */
function opcoes_modelo_importacao(PDO $pdo, string $tipo): array
{
    if ($tipo === 'tipos_peca') {
        return ['categoria_nome' => opcoes_lista_nome($pdo, 'categorias_peca')];
    }
    if ($tipo === 'produtos') {
        return [
            'tipo_peca_nome' => opcoes_lista_nome($pdo, 'tipos_peca'),
            'marca_produto_nome' => opcoes_lista_nome($pdo, 'marcas_produto'),
            'estoque_nome' => opcoes_lista_nome($pdo, 'estoques'),
        ];
    }
    if ($tipo === 'modelos_veiculo') {
        return ['marca_nome' => opcoes_lista_nome($pdo, 'marcas_veiculo')];
    }
    if ($tipo === 'veiculos_configuracao') {
        return ['modelo_veiculo_nome' => opcoes_modelos_compostos($pdo)];
    }
    if ($tipo === 'aplicacoes_produto') {
        return [
            'produto_sku_interno' => opcoes_skus_produtos($pdo),
            'modelo_veiculo_nome' => opcoes_modelos_compostos($pdo),
        ];
    }
    return [];
}

function aplicar_validacao_lista(Worksheet $sheet, string $coluna, int $inicio, int $fim, string $formula): void
{
    $range = $coluna . $inicio . ':' . $coluna . $fim;
    $validation = new DataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(false);
    $validation->setShowDropDown(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setErrorTitle('Valor inválido');
    $validation->setError('Selecione um item da lista.');
    $validation->setFormula1($formula);
    $sheet->setDataValidation($range, $validation);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Importar');

$sheet->fromArray([$cfg['cabecalho']], null, 'A1');

$row = 2;
foreach ($cfg['linhas_exemplo'] as $exemplo) {
    $linha = [];
    foreach ($cfg['cabecalho'] as $chave) {
        $linha[] = $exemplo[$chave] ?? '';
    }
    $sheet->fromArray([$linha], null, 'A' . $row);
    $row++;
}

$opcoes = opcoes_modelo_importacao($pdo, $tipo);
if ($opcoes !== []) {
    $sheetListas = $spreadsheet->createSheet();
    $sheetListas->setTitle('Listas');
    $spreadsheet->setActiveSheetIndex(0);

    $mapaColunas = [];
    $col = 'A';
    foreach ($opcoes as $chaveCabecalho => $itens) {
        $sheetListas->setCellValue($col . '1', $chaveCabecalho);
        $row = 2;
        foreach ($itens as $item) {
            $sheetListas->setCellValue($col . $row, $item);
            $row++;
        }
        $mapaColunas[$chaveCabecalho] = [
            'col' => $col,
            'count' => max(count($itens), 1),
        ];
        $col++;
    }
    $sheetListas->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

    $indicesCabecalho = array_flip($cfg['cabecalho']);
    foreach ($mapaColunas as $chaveCabecalho => $meta) {
        if (!isset($indicesCabecalho[$chaveCabecalho])) {
            continue;
        }
        $indice = (int)$indicesCabecalho[$chaveCabecalho] + 1;
        $colImport = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($indice);
        $formula = '=Listas!$' . $meta['col'] . '$2:$' . $meta['col'] . '$' . ($meta['count'] + 1);
        aplicar_validacao_lista($sheet, $colImport, 2, 1000, $formula);
    }
}

$nomeArquivo = 'modelo_importacao_' . $tipo . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
