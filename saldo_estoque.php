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
        e.ativo AS estoque_ativo,

        COALESCE(SUM(me.quantidade), 0) AS saldo_atual

    FROM produtos p
    CROSS JOIN estoques e
    LEFT JOIN movimentacoes_estoque me
        ON me.produto_id = p.id
       AND me.estoque_id = e.id
    WHERE 1 = 1
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
        e.nome,
        e.ativo
    HAVING COALESCE(SUM(me.quantidade), 0) <> 0
        OR p.ativo = 1
        OR e.ativo = 1
    ORDER BY
        p.nome_comercial ASC,
        e.nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$saldos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLinhas = count($saldos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saldo de Estoque</title>

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

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Saldo de Estoque</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Consulte o saldo consolidado por produto e local de estoque.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('movimentar_entrada.php', 'Entrada', 'salvar') ?>
                    <?= botao_link('movimentar_saida.php', 'Saída', 'perigo') ?>
                    <?= botao_link('ajustar_estoque.php', 'Ajuste', 'alerta') ?>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-9">
                        <label for="busca" class="<?= classe_label() ?>">Buscar saldo</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite produto, SKU, código do fabricante, código de barras ou estoque'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('saldo_estoque.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Linhas retornadas</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalLinhas ?></div>
                </div>

                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Usuário logado</div>
                    <div class="mt-2 text-base font-semibold text-slate-900">
                        <?= esc($_SESSION['usuario_nome'] ?? 'Usuário') ?>
                    </div>
                </div>
            </div>

            <div class="<?= classe_box() ?>">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Saldos por produto e estoque</h2>
                </div>

                <?php if (!$saldos): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum saldo encontrado.
                        </p>

                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <?= botao_link('movimentar_entrada.php', 'Registrar entrada', 'salvar') ?>
                            <?= botao_link('movimentar_saida.php', 'Registrar saída', 'perigo') ?>
                        </div>
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
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saldos as $linha): ?>
                                    <?php
                                    $saldoAtual = (float)$linha['saldo_atual'];
                                    $estoqueMinimo = (float)$linha['estoque_minimo'];

                                    $classeSaldo = 'text-slate-700 bg-slate-100';
                                    if ($saldoAtual < 0) {
                                        $classeSaldo = 'text-red-700 bg-red-100';
                                    } elseif ($saldoAtual == 0.0) {
                                        $classeSaldo = 'text-yellow-700 bg-yellow-100';
                                    } elseif ($saldoAtual <= $estoqueMinimo && $estoqueMinimo > 0) {
                                        $classeSaldo = 'text-orange-700 bg-orange-100';
                                    } else {
                                        $classeSaldo = 'text-emerald-700 bg-emerald-100';
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

                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium <?= esc($classeSaldo) ?>">
                                                <?= number_format($saldoAtual, 2, ',', '.') ?>
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
                                                    'movimentar_saida.php?produto_id=' . (int)$linha['produto_id'] . '&estoque_id=' . (int)$linha['estoque_id'],
                                                    'Saída',
                                                    'perigo'
                                                ) ?>

                                                <?= botao_link(
                                                    'ajustar_estoque.php?produto_id=' . (int)$linha['produto_id'] . '&estoque_id=' . (int)$linha['estoque_id'],
                                                    'Ajuste',
                                                    'alerta'
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
        </div>
    </main>
</div>

</body>
</html>