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

function mensagemRetorno(?string $tipo, ?string $codigo): ?array
{
    $mapa = [
        'sucesso' => [
            'cadastrado' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Produto cadastrado com sucesso.',
            ],
            'editado' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Produto atualizado com sucesso.',
            ],
            'excluido' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Produto excluído com sucesso.',
            ],
            'inativado' => [
                'classe' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                'texto'  => 'O produto possui vínculos e foi apenas inativado.',
            ],
        ],
        'erro' => [
            'metodo_invalido' => [
                'classe' => 'border-red-200 bg-red-50 text-red-700',
                'texto'  => 'Método de requisição inválido.',
            ],
            'id_invalido' => [
                'classe' => 'border-red-200 bg-red-50 text-red-700',
                'texto'  => 'ID inválido para a operação solicitada.',
            ],
            'registro_nao_encontrado' => [
                'classe' => 'border-red-200 bg-red-50 text-red-700',
                'texto'  => 'Registro não encontrado.',
            ],
            'erro_ao_excluir' => [
                'classe' => 'border-red-200 bg-red-50 text-red-700',
                'texto'  => 'Ocorreu um erro ao excluir o produto.',
            ],
        ],
    ];

    if (!$tipo || !$codigo) {
        return null;
    }

    return $mapa[$tipo][$codigo] ?? null;
}

$busca = trim($_GET['busca'] ?? '');
$sucesso = trim($_GET['sucesso'] ?? '');
$erro = trim($_GET['erro'] ?? '');
$nImportacao = (int)($_GET['n'] ?? 0);

$retorno = null;
if ($sucesso === 'importacao' && $nImportacao > 0) {
    $retorno = [
        'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'texto'  => 'Importação concluída: ' . $nImportacao . ' registro(s) gravado(s).',
    ];
} elseif ($sucesso !== '') {
    $retorno = mensagemRetorno('sucesso', $sucesso);
} elseif ($erro !== '') {
    $retorno = mensagemRetorno('erro', $erro);
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
        mp.nome AS marca_produto_nome,
        pi.caminho_arquivo AS imagem_principal
    FROM produtos p
    INNER JOIN tipos_peca tp
        ON tp.id = p.tipo_peca_id
    INNER JOIN marcas_produto mp
        ON mp.id = p.marca_produto_id
    LEFT JOIN produto_imagens pi
        ON pi.produto_id = p.id
       AND pi.principal = 1
    WHERE 1 = 1
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Produtos</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Gerencie os produtos comerciais do catálogo de autopeças.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('importar_planilha.php?tipo=produtos', 'Importar planilha', 'busca') ?>
                    <?= botao_link('form_produto.php', 'Novo produto', 'salvar') ?>
                </div>
            </div>

            <?php if ($retorno): ?>
                <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= esc($retorno['classe']) ?>">
                    <?= esc($retorno['texto']) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-9">
                        <label for="busca" class="<?= classe_label() ?>">Buscar produto</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite nome, SKU, código do fabricante, código de barras, tipo ou marca'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('listar_produtos.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Total listado</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalProdutos ?></div>
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
                    <h2 class="text-lg font-semibold text-slate-900">Lista de produtos</h2>
                </div>

                <?php if (!$produtos): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum produto encontrado.
                        </p>

                        <div class="mt-4">
                            <?= botao_link('form_produto.php', 'Cadastrar primeiro produto', 'salvar') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        <?php foreach ($produtos as $produto): ?>
                            <?php $ativo = (int)($produto['ativo'] ?? 0) === 1; ?>
                            <article class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
                                <div class="h-44 bg-white">
                                    <?php if (!empty($produto['imagem_principal'])): ?>
                                        <img
                                            src="<?= esc($produto['imagem_principal']) ?>"
                                            alt="<?= esc($produto['nome_comercial']) ?>"
                                            class="h-full w-full object-contain"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="flex h-full flex-col items-center justify-center text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h3l1.2 1.5h4.6L15 5h4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm4 0l10 10M10 13a3 3 0 1 1 4.2-4.2" />
                                            </svg>
                                            <span class="mt-2 text-sm font-medium">Foto em breve</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="space-y-3 px-4 py-4">
                                    <div>
                                        <div class="text-base font-semibold leading-tight text-slate-900">
                                            <?= esc($produto['nome_comercial']) ?>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            <?= esc($produto['descricao'] ?: 'Sem descrição') ?>
                                        </div>
                                    </div>

                                    <div class="space-y-1 text-sm text-slate-700">
                                        <div><span class="font-medium">Tipo:</span> <?= esc($produto['tipo_peca_nome']) ?></div>
                                        <div><span class="font-medium">Marca:</span> <?= esc($produto['marca_produto_nome']) ?></div>
                                        <div><span class="font-medium">SKU:</span> <?= esc($produto['sku_interno']) ?></div>
                                        <div><span class="font-medium">Cód. fabricante:</span> <?= esc($produto['codigo_fabricante']) ?></div>
                                        <div><span class="font-medium">Cód. barras:</span> <?= esc($produto['codigo_barras'] ?: '—') ?></div>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                        <div class="text-lg font-bold text-blue-700">
                                            R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?>
                                        </div>
                                        <?php if ($ativo): ?>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">Ativo</span>
                                        <?php else: ?>
                                            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">Inativo</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-3">
                                        <?= botao_link(
                                            'form_produto.php?id=' . (int)$produto['id'],
                                            'Editar',
                                            'editar'
                                        ) ?>

                                        <?= botao_excluir(
                                            'excluir_produto.php?id=' . (int)$produto['id'],
                                            'Tem certeza que deseja excluir este produto?',
                                            'Excluir'
                                        ) ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>