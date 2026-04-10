<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';

$tipo = trim((string)($_GET['tipo'] ?? ''));

require_once __DIR__ . '/lib/importacao_planilha.php';

if (!importacao_planilha_tipo_valido($tipo)) {
    http_response_code(404);
    exit('Tipo inválido.');
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Execute composer install na raiz do projeto.');
}
require_once $autoload;

$cfg = importacao_planilha_config($tipo);
if ($cfg === null) {
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

$nomeArquivo = 'modelo_importacao_' . $tipo . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
