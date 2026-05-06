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
        p.id,
        p.nome_comercial,
        p.sku_interno,
        p.codigo_fabricante,
        p.codigo_barras,
        p.preco,
        p.ativo,
        p.criado_em,
        p.atualizado_em,

        tp.nome AS tipo_peca_nome,
        mp.nome AS marca_produto_nome

    FROM produtos p
    INNER JOIN tipos_peca tp
        ON tp.id = p.tipo_peca_id
    INNER JOIN marcas_produto mp
        ON mp.id = p.marca_produto_id
    LEFT JOIN aplicacoes_produto ap
        ON ap.produto_id = p.id
       AND ap.ativo = 1
    WHERE ap.id IS NULL
";

$params = [];

if ($busca !== '') {
    $sql .= " AND (
        p.nome_comercial LIKE :busca
        OR p.sku_interno LIKE :busca
        OR p.codigo_fabricante LIKE :busca
        OR p.codigo_barras LIKE :busca
        OR tp.nome LIKE :busca
        OR mp.nome LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY p.nome_comercial ASC, mp.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalProdutos = count($produtos);

$totalAtivos = 0;
$totalInativos = 0;

foreach ($produtos as $produto) {
    if ((int)$produto['ativo'] === 1) {
        $totalAtivos++;
    } else {
        $totalInativos++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Produtos sem Aplicação</title>

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

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-white">Relatório de Produtos sem Aplicação</h1>
                    <p class="mt-1 text-sm text-slate-300">
                        Produtos cadastrados sem compatibilidade veicular específica registrada.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('listar_aplicacoes_produto.php', 'Ver aplicações', 'atalho') ?>
                    <button type="button" onclick="imprimirRelatorio()" class="<?= esc($btn_busca ?? 'px-3 py-2 rounded-lg bg-blue-600 text-white') ?>">
                        Imprimir
                    </button>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6 print:hidden">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-9">
                        <label for="busca" class="<?= classe_label() ?>">Buscar no relatório</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite produto, SKU, código do fabricante, tipo de peça ou marca'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('relatorio_produtos_sem_aplicacao.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Total no relatório</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalProdutos ?></div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Produtos ativos</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600"><?= $totalAtivos ?></div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Produtos inativos</div>
                    <div class="mt-2 text-3xl font-bold text-red-600"><?= $totalInativos ?></div>
                </div>
            </div>

            <div class="<?= classe_box() ?> print:shadow-none print:border print:border-slate-300">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Produtos sem aplicação veicular</h2>
                </div>

                <?php if (!$produtos): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum produto sem aplicação foi encontrado.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2 print:border-collapse print:border-spacing-0">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de peça</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Cód. fabricante</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Preço</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 print:hidden">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos as $produto): ?>
                                    <?php $ativo = (int)$produto['ativo'] === 1; ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm print:shadow-none print:border-b print:border-slate-200">
                                        <td class="rounded-l-xl px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($produto['nome_comercial']) ?>
                                            </div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                <?= esc($produto['codigo_barras'] ?: 'Sem código de barras') ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($produto['marca_produto_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($produto['tipo_peca_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($produto['sku_interno']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($produto['codigo_fabricante']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                            R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <?php if ($ativo): ?>
                                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                                                    Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                                    Inativo
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="rounded-r-xl px-4 py-4 print:hidden">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link(
                                                    'ver_produto.php?id=' . (int)$produto['id'],
                                                    'Ver',
                                                    'atalho'
                                                ) ?>

                                                <?= botao_link(
                                                    'ver_aplicacoes_produto.php?id=' . (int)$produto['id'],
                                                    'Aplicações',
                                                    'busca'
                                                ) ?>

                                                <?= botao_link(
                                                    'form_aplicacao_produto.php',
                                                    'Cadastrar aplicação',
                                                    'salvar'
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

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm print:shadow-none">
                <strong class="text-slate-800">Observação:</strong>
                este relatório revela incompletude cadastral. Um produto sem aplicação não é necessariamente inválido, mas ainda não é operacionalmente útil para consulta por veículo.
            </div>
        </div>
    </main>
</div>

</body>
</html>