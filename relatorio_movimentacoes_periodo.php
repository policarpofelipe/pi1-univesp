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

$dataInicial = trim((string)($_GET['data_inicial'] ?? ''));
$dataFinal = trim((string)($_GET['data_final'] ?? ''));
$tipoFiltro = trim((string)($_GET['tipo_movimentacao'] ?? ''));
$busca = trim((string)($_GET['busca'] ?? ''));

$tiposMovimentacao = [
    ''        => 'Todos os tipos',
    'entrada' => 'Entrada',
    'saida'   => 'Saída',
    'ajuste'  => 'Ajuste',
];

$where = [];
$params = [];

if ($dataInicial !== '') {
    $where[] = "DATE(me.criado_em) >= :data_inicial";
    $params[':data_inicial'] = $dataInicial;
}

if ($dataFinal !== '') {
    $where[] = "DATE(me.criado_em) <= :data_final";
    $params[':data_final'] = $dataFinal;
}

if ($tipoFiltro !== '' && in_array($tipoFiltro, ['entrada', 'saida', 'ajuste'], true)) {
    $where[] = "me.tipo_movimento = :tipo_movimentacao";
    $params[':tipo_movimentacao'] = $tipoFiltro;
}

if ($busca !== '') {
    $where[] = "(
        p.nome_comercial LIKE :busca
        OR p.sku_interno LIKE :busca
        OR e.nome LIKE :busca
        OR me.observacao LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql = "
    SELECT
        me.id,
        me.tipo_movimento AS tipo_movimentacao,
        me.quantidade,
        me.observacao,
        me.criado_em,

        p.id AS produto_id,
        p.nome_comercial,
        p.sku_interno,

        e.id AS estoque_id,
        e.nome AS estoque_nome,

        u.nome AS usuario_nome
    FROM movimentacoes_estoque me
    INNER JOIN produtos p
        ON p.id = me.produto_id
    INNER JOIN estoques e
        ON e.id = me.estoque_id
    LEFT JOIN usuarios u
        ON u.id = me.usuario_id
";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY me.criado_em DESC, me.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLinhas = count($movimentacoes);
$totalEntradas = 0.0;
$totalSaidas = 0.0;
$totalAjustes = 0.0;
$saldoLiquido = 0.0;

foreach ($movimentacoes as $mov) {
    $tipo = (string)$mov['tipo_movimentacao'];
    $quantidade = (float)$mov['quantidade'];

    if ($tipo === 'entrada') {
        $totalEntradas += $quantidade;
    } elseif ($tipo === 'saida') {
        $totalSaidas += abs($quantidade);
    } elseif ($tipo === 'ajuste') {
        $totalAjustes += $quantidade;
    }

    $saldoLiquido += $quantidade;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Movimentações por Período</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.Alpine && window.AlpineCollapse) {
                Alpine.plugin(window.AlpineCollapse);
            }
        });

        function imprimirRelatorio() {
            window.print();
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 print:bg-white">

<div class="min-h-screen md:flex print:block">
    <div class="print:hidden">
        <?php require __DIR__ . '/menu.php'; ?>
    </div>

    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6 print:p-0">
        <div class="mx-auto max-w-7xl">

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Relatório de Movimentações por Período</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Consulte entradas, saídas e ajustes em um intervalo de datas.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('listar_movimentacoes_estoque.php', 'Ver movimentações', 'atalho') ?>
                    <button type="button" onclick="imprimirRelatorio()" class="<?= esc($btn_busca ?? 'px-3 py-2 rounded-lg bg-blue-600 text-white') ?>">
                        Imprimir
                    </button>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6 print:hidden">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-2">
                        <label for="data_inicial" class="<?= classe_label() ?>">Data inicial</label>
                        <?= input_texto('data_inicial', $dataInicial, [
                            'id' => 'data_inicial',
                            'type' => 'date'
                        ]) ?>
                    </div>

                    <div class="md:col-span-2">
                        <label for="data_final" class="<?= classe_label() ?>">Data final</label>
                        <?= input_texto('data_final', $dataFinal, [
                            'id' => 'data_final',
                            'type' => 'date'
                        ]) ?>
                    </div>

                    <div class="md:col-span-2">
                        <label for="tipo_movimentacao" class="<?= classe_label() ?>">Tipo</label>
                        <?= select_padrao(
                            'tipo_movimentacao',
                            $tiposMovimentacao,
                            $tipoFiltro,
                            ['id' => 'tipo_movimentacao']
                        ) ?>
                    </div>

                    <div class="md:col-span-4">
                        <label for="busca" class="<?= classe_label() ?>">Busca</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite produto, SKU, estoque ou observação'
                        ]) ?>
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Filtrar', 'busca') ?>
                        </div>
                        <?= botao_link('relatorio_movimentacoes_periodo.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Registros</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalLinhas ?></div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Entradas</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600">
                        <?= number_format($totalEntradas, 2, ',', '.') ?>
                    </div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Saídas</div>
                    <div class="mt-2 text-3xl font-bold text-red-600">
                        <?= number_format($totalSaidas, 2, ',', '.') ?>
                    </div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Ajustes</div>
                    <div class="mt-2 text-3xl font-bold text-yellow-600">
                        <?= number_format($totalAjustes, 2, ',', '.') ?>
                    </div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Saldo líquido</div>
                    <div class="mt-2 text-3xl font-bold <?= $saldoLiquido < 0 ? 'text-red-600' : 'text-slate-900' ?>">
                        <?= number_format($saldoLiquido, 2, ',', '.') ?>
                    </div>
                </div>
            </div>

            <div class="<?= classe_box() ?> print:shadow-none print:border print:border-slate-300">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Movimentações do período</h2>
                </div>

                <?php if (!$movimentacoes): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhuma movimentação encontrada para os filtros informados.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2 print:border-collapse print:border-spacing-0">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Estoque</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Usuário</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimentacoes as $mov): ?>
                                    <?php
                                    $tipo = (string)$mov['tipo_movimentacao'];
                                    $quantidade = (float)$mov['quantidade'];

                                    $classeTipo = 'bg-slate-200 text-slate-700';
                                    if ($tipo === 'entrada') {
                                        $classeTipo = 'bg-emerald-100 text-emerald-700';
                                    } elseif ($tipo === 'saida') {
                                        $classeTipo = 'bg-red-100 text-red-700';
                                    } elseif ($tipo === 'ajuste') {
                                        $classeTipo = 'bg-yellow-100 text-yellow-700';
                                    }
                                    ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm print:shadow-none print:border-b print:border-slate-200">
                                        <td class="rounded-l-xl px-4 py-4 text-sm text-slate-600">
                                            <?= esc($mov['criado_em']) ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($mov['nome_comercial']) ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($mov['sku_interno']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($mov['estoque_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc($classeTipo) ?>">
                                                <?= esc(ucfirst($tipo)) ?>
                                            </span>
                                        </td>

                                        <td class="px-4 py-4 text-sm font-medium <?= $quantidade < 0 ? 'text-red-700' : 'text-slate-800' ?>">
                                            <?= number_format($quantidade, 2, ',', '.') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($mov['usuario_nome'] ?: '—') ?>
                                        </td>

                                        <td class="rounded-r-xl px-4 py-4 text-sm text-slate-600">
                                            <?= esc($mov['observacao'] ?: '—') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm print:shadow-none">
                <strong class="text-slate-800">Observação:</strong>
                este relatório opera sobre eventos históricos, não sobre saldo manual. Isso é estruturalmente melhor, porque preserva rastreabilidade e permite auditoria do período.
            </div>
        </div>
    </main>
</div>

</body>
</html>