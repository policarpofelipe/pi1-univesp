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

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_produtos.php?erro=id_invalido');
    exit;
}

/*
|--------------------------------------------------------------------------
| Buscar produto
|--------------------------------------------------------------------------
*/
$sqlProduto = "
    SELECT
        p.id,
        p.nome_comercial,
        p.sku_interno,
        p.codigo_fabricante,
        p.codigo_barras,
        p.preco,
        p.ativo,
        tp.id AS tipo_peca_id,
        tp.nome AS tipo_peca_nome,
        cp.nome AS categoria_peca_nome,
        mp.nome AS marca_produto_nome
    FROM produtos p
    INNER JOIN tipos_peca tp
        ON tp.id = p.tipo_peca_id
    INNER JOIN categorias_peca cp
        ON cp.id = tp.categoria_peca_id
    INNER JOIN marcas_produto mp
        ON mp.id = p.marca_produto_id
    WHERE p.id = :id
    LIMIT 1
";

$stmtProduto = $pdo->prepare($sqlProduto);
$stmtProduto->bindValue(':id', $id, PDO::PARAM_INT);
$stmtProduto->execute();

$produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: listar_produtos.php?erro=registro_nao_encontrado');
    exit;
}

/*
|--------------------------------------------------------------------------
| Buscar aplicações específicas do produto
|--------------------------------------------------------------------------
*/
$sqlAplicacoes = "
    SELECT
        ap.id,
        ap.ativo,
        ap.observacao,
        mv.nome AS marca_nome,
        mo.nome AS modelo_nome,
        vc.ano_inicio,
        vc.ano_fim,
        vc.motorizacao,
        vc.combustivel,
        vc.versao
    FROM aplicacoes_produto ap
    INNER JOIN veiculos_configuracao vc
        ON vc.id = ap.veiculo_configuracao_id
    INNER JOIN modelos_veiculo mo
        ON mo.id = vc.modelo_veiculo_id
    INNER JOIN marcas_veiculo mv
        ON mv.id = mo.marca_veiculo_id
    WHERE ap.produto_id = :produto_id
    ORDER BY mv.nome ASC, mo.nome ASC, vc.ano_inicio ASC, vc.versao ASC
";

$stmtAplicacoes = $pdo->prepare($sqlAplicacoes);
$stmtAplicacoes->bindValue(':produto_id', (int)$produto['id'], PDO::PARAM_INT);
$stmtAplicacoes->execute();

$aplicacoes = $stmtAplicacoes->fetchAll(PDO::FETCH_ASSOC);
$totalAplicacoes = count($aplicacoes);
$ativo = (int)($produto['ativo'] ?? 0) === 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicações do Produto</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Aplicações do Produto</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Veículos compatíveis vinculados diretamente a este produto.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_produtos.php', 'Voltar para produtos', 'cancelar') ?>
                    <?= botao_link('ver_produto.php?id=' . (int)$produto['id'], 'Ver produto', 'atalho') ?>
                    <?= botao_link('form_produto.php?id=' . (int)$produto['id'], 'Editar produto', 'editar') ?>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">
                            <?= esc($produto['nome_comercial']) ?>
                        </h2>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700">
                                <?= esc($produto['marca_produto_nome']) ?>
                            </span>

                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                <?= esc($produto['tipo_peca_nome']) ?>
                            </span>
                            <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">
                                <?= esc($produto['categoria_peca_nome']) ?>
                            </span>

                            <?php if ($ativo): ?>
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                                    Ativo
                                </span>
                            <?php else: ?>
                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                    Inativo
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <div><strong class="text-slate-800">ID:</strong> <?= (int)$produto['id'] ?></div>
                        <div><strong class="text-slate-800">SKU:</strong> <?= esc($produto['sku_interno']) ?></div>
                        <div><strong class="text-slate-800">Cód. fabricante:</strong> <?= esc($produto['codigo_fabricante']) ?></div>
                        <div><strong class="text-slate-800">Cód. barras:</strong> <?= esc($produto['codigo_barras'] ?: '—') ?></div>
                        <div><strong class="text-slate-800">Preço:</strong> R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">Total de aplicações</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalAplicacoes ?></div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm text-slate-500">Tipo de peça</div>
                        <div class="mt-2 text-base font-semibold text-slate-900">
                            <?= esc($produto['tipo_peca_nome']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="<?= classe_box() ?>">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Veículos compatíveis</h2>
                </div>

                <?php if (!$aplicacoes): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Este produto ainda não possui aplicações veiculares específicas registradas.
                        </p>

                        <div class="mt-4">
                            <?= botao_link('listar_aplicacoes_produto.php', 'Ir para aplicações', 'salvar') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ano</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Motorização</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Combustível</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Versão</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Observação</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aplicacoes as $aplicacao): ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm">
                                        <td class="rounded-l-xl px-4 py-4 text-sm text-slate-700">
                                            <?= esc($aplicacao['marca_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">
                                            <?= esc($aplicacao['modelo_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?php if ((int)$aplicacao['ano_inicio'] === (int)$aplicacao['ano_fim']): ?>
                                                <?= (int)$aplicacao['ano_inicio'] ?>
                                            <?php else: ?>
                                                <?= (int)$aplicacao['ano_inicio'] ?> a <?= (int)$aplicacao['ano_fim'] ?>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($aplicacao['motorizacao'] ?: '—') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($aplicacao['combustivel'] ?: '—') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($aplicacao['versao'] ?: '—') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($aplicacao['observacao'] ?: '—') ?>
                                        </td>
                                        <td class="rounded-r-xl px-4 py-4 text-sm">
                                            <?= (int)$aplicacao['ativo'] === 1 ? '<span class="text-emerald-700">Ativo</span>' : '<span class="text-red-700">Inativo</span>' ?>
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