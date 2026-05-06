<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/componentes.php';
require __DIR__ . '/lib/produto_imagens.php';

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

$sql = "
    SELECT
        p.id,
        p.sku_interno,
        p.codigo_fabricante,
        p.codigo_barras,
        p.nome_comercial,
        p.descricao,
        p.custo,
        p.preco,
        p.estoque_minimo,
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
    WHERE p.id = :id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: listar_produtos.php?erro=registro_nao_encontrado');
    exit;
}

$sqlSaldosPorEstoque = "
    SELECT
        e.id,
        e.nome,
        e.localizacao,
        COALESCE(SUM(
            CASE
                WHEN me.tipo_movimento = 'saida' THEN -me.quantidade
                WHEN me.tipo_movimento = 'entrada' THEN me.quantidade
                ELSE me.quantidade
            END
        ), 0) AS saldo_atual
    FROM estoques e
    LEFT JOIN movimentacoes_estoque me
        ON me.estoque_id = e.id
       AND me.produto_id = :produto_id
    WHERE e.ativo = 1
    GROUP BY e.id, e.nome, e.localizacao
    HAVING COALESCE(SUM(
        CASE
            WHEN me.tipo_movimento = 'saida' THEN -me.quantidade
            WHEN me.tipo_movimento = 'entrada' THEN me.quantidade
            ELSE me.quantidade
        END
    ), 0) <> 0
    ORDER BY e.nome ASC
";

$stmtSaldos = $pdo->prepare($sqlSaldosPorEstoque);
$stmtSaldos->bindValue(':produto_id', (int)$produto['id'], PDO::PARAM_INT);
$stmtSaldos->execute();
$saldosPorEstoque = $stmtSaldos->fetchAll(PDO::FETCH_ASSOC);

$saldoTotal = 0.0;
foreach ($saldosPorEstoque as $linhaSaldo) {
    $saldoTotal += (float)$linhaSaldo['saldo_atual'];
}

$ativo = (int)($produto['ativo'] ?? 0) === 1;
$imagensProduto = listarImagensProduto($pdo, (int)$produto['id']);
$imagemPrincipal = null;
foreach ($imagensProduto as $img) {
    if ((int)$img['principal'] === 1) {
        $imagemPrincipal = $img;
        break;
    }
}
if ($imagemPrincipal === null && $imagensProduto !== []) {
    $imagemPrincipal = $imagensProduto[0];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Produto</title>

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
        <div class="mx-auto max-w-5xl">

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Detalhes do Produto</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Visualização completa do cadastro do produto.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_produtos.php', 'Voltar para listagem', 'cancelar') ?>
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
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 pt-6 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 text-sm font-semibold text-slate-800">Informações comerciais</div>

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-slate-700">Marca do produto:</span>
                                <span class="text-slate-600"><?= esc($produto['marca_produto_nome']) ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Tipo de peça:</span>
                                <span class="text-slate-600"><?= esc($produto['tipo_peca_nome']) ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Nome comercial:</span>
                                <span class="text-slate-600"><?= esc($produto['nome_comercial']) ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Código do fabricante:</span>
                                <span class="text-slate-600"><?= esc($produto['codigo_fabricante']) ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Código de barras:</span>
                                <span class="text-slate-600"><?= esc($produto['codigo_barras'] ?: '—') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 text-sm font-semibold text-slate-800">Valores e estoque</div>

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-slate-700">Custo:</span>
                                <span class="text-slate-600">R$ <?= number_format((float)$produto['custo'], 2, ',', '.') ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Preço de venda:</span>
                                <span class="text-slate-600">R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Estoque mínimo:</span>
                                <span class="text-slate-600"><?= (int)$produto['estoque_minimo'] ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Saldo total disponível:</span>
                                <span class="text-slate-600"><?= number_format($saldoTotal, 2, ',', '.') ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Criado em:</span>
                                <span class="text-slate-600"><?= esc($produto['criado_em']) ?></span>
                            </div>

                            <div>
                                <span class="font-medium text-slate-700">Atualizado em:</span>
                                <span class="text-slate-600"><?= esc($produto['atualizado_em']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">Descrição</div>
                    <div class="text-sm leading-6 text-slate-600">
                        <?= nl2br(esc($produto['descricao'] ?: 'Sem descrição cadastrada.')) ?>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 text-sm font-semibold text-slate-800">Galeria de imagens</div>
                    <?php if (!$imagensProduto): ?>
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                            Este produto ainda não possui imagens cadastradas.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="md:col-span-2">
                                <div class="aspect-video overflow-hidden rounded-xl bg-slate-100">
                                    <img
                                        src="<?= esc((string)$imagemPrincipal['caminho_arquivo']) ?>"
                                        alt="<?= esc((string)($imagemPrincipal['alt_text'] ?: $imagemPrincipal['nome_original'] ?: $imagemPrincipal['nome_arquivo'])) ?>"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 md:grid-cols-2">
                                <?php foreach ($imagensProduto as $img): ?>
                                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                        <img
                                            src="<?= esc($img['caminho_arquivo']) ?>"
                                            alt="<?= esc($img['alt_text'] ?: $img['nome_original'] ?: $img['nome_arquivo']) ?>"
                                            class="h-24 w-full object-cover"
                                            loading="lazy"
                                        >
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="mb-3 text-sm font-semibold text-slate-800">Disponibilidade por local de estoque</div>

                    <?php if (!$saldosPorEstoque): ?>
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                            Não há saldo disponível para este produto nos estoques ativos.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Local de estoque</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Endereço interno</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade disponível</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($saldosPorEstoque as $saldo): ?>
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="px-3 py-3 text-sm text-slate-800"><?= esc($saldo['nome']) ?></td>
                                            <td class="px-3 py-3 text-sm text-slate-600"><?= esc($saldo['localizacao'] ?: '—') ?></td>
                                            <td class="px-3 py-3 text-right text-sm font-medium text-slate-800">
                                                <?= number_format((float)$saldo['saldo_atual'], 2, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>