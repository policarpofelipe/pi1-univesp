<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/componentes.php';

date_default_timezone_set('America/Sao_Paulo');

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function carregarDependenciasExportacao(): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Dependências de exportação ausentes. Execute composer install.');
    }
    require_once $autoload;
}

$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT
        p.id AS produto_id,
        p.nome_comercial,
        p.sku_interno,
        p.codigo_fabricante,
        p.codigo_barras,
        p.estoque_minimo,
        p.ativo AS produto_ativo,

        e.id AS estoque_id,
        e.nome AS estoque_nome,

        COALESCE(SUM(
            CASE
                WHEN me.tipo_movimento = 'saida' THEN -me.quantidade
                WHEN me.tipo_movimento = 'entrada' THEN me.quantidade
                ELSE me.quantidade
            END
        ), 0) AS saldo_atual

    FROM produtos p
    CROSS JOIN estoques e
    LEFT JOIN movimentacoes_estoque me
        ON me.produto_id = p.id
       AND me.estoque_id = e.id
    WHERE p.ativo = 1
";

$params = [];

if ($busca !== '') {
    $sql .= " AND (
        p.nome_comercial LIKE :busca
        OR p.sku_interno LIKE :busca
        OR p.codigo_fabricante LIKE :busca
        OR p.codigo_barras LIKE :busca
        OR e.nome LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= "
    GROUP BY
        p.id,
        p.nome_comercial,
        p.sku_interno,
        p.codigo_fabricante,
        p.codigo_barras,
        p.estoque_minimo,
        p.ativo,
        e.id,
        e.nome
    HAVING COALESCE(SUM(
        CASE
            WHEN me.tipo_movimento = 'saida' THEN -me.quantidade
            WHEN me.tipo_movimento = 'entrada' THEN me.quantidade
            ELSE me.quantidade
        END
    ), 0) <= p.estoque_minimo
    ORDER BY
        saldo_atual ASC,
        p.nome_comercial ASC,
        e.nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLinhas = count($linhas);

$totalCritico = 0;
$totalZerado = 0;
$totalNegativo = 0;

foreach ($linhas as $linha) {
    $saldo = (float)$linha['saldo_atual'];

    if ($saldo < 0) {
        $totalNegativo++;
    } elseif ($saldo == 0.0) {
        $totalZerado++;
    } else {
        $totalCritico++;
    }
}

$erroFlash = '';
$erroCodigo = trim((string)($_GET['erro'] ?? ''));
if ($erroCodigo === 'dependencia_exportacao') {
    $erroFlash = 'Não foi possível exportar: dependências ausentes. Execute composer install na raiz do projeto.';
}

$exportar = trim((string)($_GET['exportar'] ?? ''));
if (in_array($exportar, ['pdf', 'planilha'], true)) {
    try {
        carregarDependenciasExportacao();

        if ($exportar === 'planilha') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Estoque baixo');
            $sheet->fromArray([[
                'Produto',
                'SKU',
                'Código fabricante',
                'Código barras',
                'Estoque',
                'Estoque mínimo',
                'Saldo atual',
                'Situação',
            ]], null, 'A1');

            $linhaPlanilha = 2;
            foreach ($linhas as $linha) {
                $saldoAtual = (float)$linha['saldo_atual'];
                $situacaoTexto = 'Abaixo do mínimo';
                if ($saldoAtual < 0) {
                    $situacaoTexto = 'Saldo negativo';
                } elseif ($saldoAtual == 0.0) {
                    $situacaoTexto = 'Sem estoque';
                }
                $sheet->fromArray([[
                    (string)$linha['nome_comercial'],
                    (string)$linha['sku_interno'],
                    (string)$linha['codigo_fabricante'],
                    (string)($linha['codigo_barras'] ?? ''),
                    (string)$linha['estoque_nome'],
                    (float)$linha['estoque_minimo'],
                    $saldoAtual,
                    $situacaoTexto,
                ]], null, 'A' . $linhaPlanilha);
                $linhaPlanilha++;
            }

            foreach (range('A', 'H') as $coluna) {
                $sheet->getColumnDimension($coluna)->setAutoSize(true);
            }

            $nomeArquivo = 'relatorio_estoque_baixo_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }

        if (!class_exists(\Dompdf\Dompdf::class)) {
            header('Location: relatorio_estoque_baixo.php?erro=dependencia_exportacao' . ($busca !== '' ? '&busca=' . urlencode($busca) : ''));
            exit;
        }

        $html = '<html><head><meta charset="UTF-8"><style>
            body{font-family:DejaVu Sans,sans-serif;color:#1e293b;font-size:12px}
            .cab{background:linear-gradient(90deg,#0f172a,#1e293b);color:#fff;padding:18px 20px;border-radius:10px}
            .cab h1{margin:0;font-size:22px}
            .cab p{margin:6px 0 0 0;font-size:11px;color:#cbd5e1}
            .meta{margin:12px 0 14px 0;font-size:11px;color:#475569}
            table{width:100%;border-collapse:collapse}
            th,td{border:1px solid #cbd5e1;padding:6px;text-align:left;vertical-align:top}
            th{background:#f1f5f9;font-size:11px}
        </style></head><body>';
        $html .= '<div class="cab"><h1>Relatório de Estoque Baixo</h1><p>Sistema de Controle de Estoque</p></div>';
        $html .= '<div class="meta">Gerado em: ' . date('d/m/Y H:i') . ($busca !== '' ? ' | Filtro: ' . esc($busca) : '') . '</div>';
        $html .= '<table><thead><tr>
            <th>Produto</th><th>SKU</th><th>Estoque</th><th>Mínimo</th><th>Saldo</th><th>Situação</th>
        </tr></thead><tbody>';
        if ($linhas === []) {
            $html .= '<tr><td colspan="6">Nenhum item encontrado.</td></tr>';
        } else {
            foreach ($linhas as $linha) {
                $saldoAtual = (float)$linha['saldo_atual'];
                $situacaoTexto = 'Abaixo do mínimo';
                if ($saldoAtual < 0) {
                    $situacaoTexto = 'Saldo negativo';
                } elseif ($saldoAtual == 0.0) {
                    $situacaoTexto = 'Sem estoque';
                }
                $html .= '<tr>'
                    . '<td>' . esc((string)$linha['nome_comercial']) . '</td>'
                    . '<td>' . esc((string)$linha['sku_interno']) . '</td>'
                    . '<td>' . esc((string)$linha['estoque_nome']) . '</td>'
                    . '<td>' . number_format((float)$linha['estoque_minimo'], 2, ',', '.') . '</td>'
                    . '<td>' . number_format($saldoAtual, 2, ',', '.') . '</td>'
                    . '<td>' . esc($situacaoTexto) . '</td>'
                    . '</tr>';
            }
        }
        $html .= '</tbody></table></body></html>';

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('relatorio_estoque_baixo_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);
        exit;
    } catch (Throwable $e) {
        header('Location: relatorio_estoque_baixo.php?erro=dependencia_exportacao' . ($busca !== '' ? '&busca=' . urlencode($busca) : ''));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Estoque Baixo</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.Alpine && window.AlpineCollapse) {
                Alpine.plugin(window.AlpineCollapse);
            }
        });
    </script>
</head>
<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen md:flex">
    <?php require __DIR__ . '/menu.php'; ?>

    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6">
        <div class="mx-auto max-w-7xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white shadow-lg">
                <div class="p-5 md:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Sistema de Controle de Estoque</p>
                            <h1 class="mt-2 text-2xl md:text-3xl font-bold">Relatório de Estoque Baixo</h1>
                            <p class="mt-2 text-sm text-slate-300">
                                Produtos cujo saldo atual está igual ou abaixo do estoque mínimo.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                            <?= botao_link('saldo_estoque.php', 'Ver saldo completo', 'atalho') ?>
                            <?= botao_link('movimentar_entrada.php', 'Registrar entrada', 'salvar') ?>
                            <?= botao_link('relatorio_estoque_baixo.php?exportar=pdf' . ($busca !== '' ? '&busca=' . urlencode($busca) : ''), 'Exportar PDF', 'busca') ?>
                            <?= botao_link('relatorio_estoque_baixo.php?exportar=planilha' . ($busca !== '' ? '&busca=' . urlencode($busca) : ''), 'Exportar planilha', 'atalho') ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($erroFlash !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erroFlash) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-9">
                        <label for="busca" class="<?= classe_label() ?>">Buscar no relatório</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite produto, SKU, código do fabricante, código de barras ou estoque'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('relatorio_estoque_baixo.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Itens no relatório</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalLinhas ?></div>
                </div>

                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Abaixo do mínimo</div>
                    <div class="mt-2 text-3xl font-bold text-orange-600"><?= $totalCritico ?></div>
                </div>

                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Saldo zerado</div>
                    <div class="mt-2 text-3xl font-bold text-yellow-600"><?= $totalZerado ?></div>
                </div>

                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Saldo negativo</div>
                    <div class="mt-2 text-3xl font-bold text-red-600"><?= $totalNegativo ?></div>
                </div>
            </div>

            <div class="<?= classe_box() ?>">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Itens com necessidade de reposição</h2>
                </div>

                <?php if (!$linhas): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum item foi encontrado abaixo do estoque mínimo.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Cód. fabricante</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Estoque</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mínimo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo atual</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Situação</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linhas as $linha): ?>
                                    <?php
                                    $saldoAtual = (float)$linha['saldo_atual'];
                                    $estoqueMinimo = (float)$linha['estoque_minimo'];

                                    $situacaoTexto = 'Abaixo do mínimo';
                                    $situacaoClasse = 'bg-orange-100 text-orange-700';

                                    if ($saldoAtual < 0) {
                                        $situacaoTexto = 'Saldo negativo';
                                        $situacaoClasse = 'bg-red-100 text-red-700';
                                    } elseif ($saldoAtual == 0.0) {
                                        $situacaoTexto = 'Sem estoque';
                                        $situacaoClasse = 'bg-yellow-100 text-yellow-700';
                                    }
                                    ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm">
                                        <td class="rounded-l-xl px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($linha['nome_comercial']) ?>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                <?= esc($linha['codigo_barras'] ?: 'Sem código de barras') ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($linha['sku_interno']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($linha['codigo_fabricante']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($linha['estoque_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= number_format($estoqueMinimo, 2, ',', '.') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                            <?= number_format($saldoAtual, 2, ',', '.') ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc($situacaoClasse) ?>">
                                                <?= esc($situacaoTexto) ?>
                                            </span>
                                        </td>

                                        <td class="rounded-r-xl px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link(
                                                    'movimentar_entrada.php?produto_id=' . (int)$linha['produto_id'] . '&estoque_id=' . (int)$linha['estoque_id'],
                                                    'Entrada',
                                                    'salvar'
                                                ) ?>

                                                <?= botao_link(
                                                    'saldo_estoque.php?busca=' . urlencode((string)$linha['sku_interno']),
                                                    'Saldo',
                                                    'atalho'
                                                ) ?>

                                                <?= botao_link(
                                                    'ver_produto.php?id=' . (int)$linha['produto_id'],
                                                    'Ver',
                                                    'busca'
                                                ) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                este relatório deriva do histórico de movimentações. Ele não depende de um campo manual de saldo, o que é conceitualmente melhor, porque preserva rastreabilidade.
            </div>
        </div>
    </main>
</div>

</body>
</html>