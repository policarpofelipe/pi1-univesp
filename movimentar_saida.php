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

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erroDetalhe = trim((string)($_GET['detalhe'] ?? ''));
$erro = '';

$mapaErros = [
    'produto_obrigatorio'    => 'Selecione um produto.',
    'estoque_obrigatorio'    => 'Selecione um local de estoque.',
    'quantidade_obrigatoria' => 'Informe a quantidade.',
    'quantidade_invalida'    => 'Informe uma quantidade válida e maior que zero.',
    'saldo_insuficiente'     => 'Saldo insuficiente no estoque selecionado para registrar esta saída.',
    'schema_quantidade_invalido' => 'A coluna quantidade da tabela movimentacoes_estoque está como UNSIGNED. Ajuste para DECIMAL(10,2) sem UNSIGNED.',
    'erro_interno'           => 'Ocorreu um erro interno ao processar a operação.',
];
$quantidadeInformada = trim((string)($_GET['quantidade'] ?? ''));
$observacaoInformada = trim((string)($_GET['observacao'] ?? ''));

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}
if ($erroCodigo === 'erro_detalhado_saida' && $erroDetalhe !== '') {
    $erro = 'Falha ao registrar saída: ' . $erroDetalhe;
}

$produtoSelecionado = trim((string)($_GET['produto_id'] ?? ''));
$estoqueSelecionado = trim((string)($_GET['estoque_id'] ?? ''));

/*
|--------------------------------------------------------------------------
| Produtos ativos
|--------------------------------------------------------------------------
*/
$sqlProdutos = "
    SELECT id, nome_comercial, sku_interno
    FROM produtos
    WHERE ativo = 1
    ORDER BY nome_comercial ASC
";
$stmtProdutos = $pdo->query($sqlProdutos);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

$opcoesProdutos = ['' => 'Selecione um produto'];
foreach ($produtos as $produto) {
    $label = $produto['nome_comercial'] . ' [' . $produto['sku_interno'] . ']';
    $opcoesProdutos[(string)$produto['id']] = $label;
}

/*
|--------------------------------------------------------------------------
| Estoques ativos
|--------------------------------------------------------------------------
*/
$sqlEstoques = "
    SELECT id, nome
    FROM estoques
    WHERE ativo = 1
    ORDER BY nome ASC
";
$stmtEstoques = $pdo->query($sqlEstoques);
$estoques = $stmtEstoques->fetchAll(PDO::FETCH_ASSOC);

$opcoesEstoques = ['' => 'Selecione um local de estoque'];
foreach ($estoques as $estoque) {
    $opcoesEstoques[(string)$estoque['id']] = (string)$estoque['nome'];
}

$sqlImagens = "
    SELECT p.id AS produto_id, pi.caminho_arquivo
    FROM produtos p
    LEFT JOIN produto_imagens pi
        ON pi.produto_id = p.id
       AND pi.principal = 1
    WHERE p.ativo = 1
";
$stmtImagens = $pdo->query($sqlImagens);
$imagemPorProduto = [];
foreach ($stmtImagens->fetchAll(PDO::FETCH_ASSOC) as $img) {
    $imagemPorProduto[(string)$img['produto_id']] = (string)($img['caminho_arquivo'] ?? '');
}

$sqlSaldos = "
    SELECT
        me.produto_id,
        e.nome AS estoque_nome,
        e.localizacao,
        COALESCE(SUM(
            CASE
                WHEN me.tipo_movimento = 'saida' THEN -me.quantidade
                WHEN me.tipo_movimento = 'entrada' THEN me.quantidade
                ELSE me.quantidade
            END
        ), 0) AS saldo
    FROM movimentacoes_estoque me
    INNER JOIN estoques e ON e.id = me.estoque_id
    GROUP BY me.produto_id, me.estoque_id, e.nome, e.localizacao
    HAVING COALESCE(SUM(
        CASE
            WHEN me.tipo_movimento = 'saida' THEN -me.quantidade
            WHEN me.tipo_movimento = 'entrada' THEN me.quantidade
            ELSE me.quantidade
        END
    ), 0) > 0
    ORDER BY e.nome ASC
";
$stmtSaldos = $pdo->query($sqlSaldos);
$saldosPorProduto = [];
foreach ($stmtSaldos->fetchAll(PDO::FETCH_ASSOC) as $linha) {
    $produtoId = (string)$linha['produto_id'];
    if (!isset($saldosPorProduto[$produtoId])) {
        $saldosPorProduto[$produtoId] = [];
    }
    $local = trim((string)($linha['localizacao'] ?? ''));
    $labelEstoque = (string)$linha['estoque_nome'] . ($local !== '' ? ' (' . $local . ')' : '');
    $saldosPorProduto[$produtoId][] = [
        'estoque' => $labelEstoque,
        'saldo' => number_format((float)$linha['saldo'], 2, ',', '.'),
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentar Saída</title>

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

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Registrar Saída</h1>
                    <p class="mt-1 text-sm text-slate-300">
                        Lance a saída de mercadoria de um local de estoque.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_movimentacoes_estoque.php', 'Voltar para movimentações', 'cancelar') ?>
                    <?= botao_link('form_movimentacao_estoque.php', 'Movimentação manual (avançada)', 'atalho') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_movimentacao_estoque.php" method="POST" class="space-y-6">
                    <input type="hidden" name="tipo_movimentacao" value="saida">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="produto_id" class="<?= classe_label() ?>">Produto</label>
                            <?= select_padrao(
                                'produto_id',
                                $opcoesProdutos,
                                $produtoSelecionado,
                                [
                                    'id' => 'produto_id',
                                    'required' => true
                                ]
                            ) ?>
                        </div>

                        <div>
                            <label for="estoque_id" class="<?= classe_label() ?>">Local de estoque</label>
                            <?= select_padrao(
                                'estoque_id',
                                $opcoesEstoques,
                                $estoqueSelecionado,
                                [
                                    'id' => 'estoque_id',
                                    'required' => true
                                ]
                            ) ?>
                        </div>

                        <div>
                            <label for="quantidade" class="<?= classe_label() ?>">Quantidade de saída</label>
                            <?= input_numero('quantidade', $quantidadeInformada, [
                                'id' => 'quantidade',
                                'step' => '0.01',
                                'min' => '0.01',
                                'required' => true,
                                'placeholder' => 'Ex.: 1, 2, 5'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="observacao" class="<?= classe_label() ?>">Observação</label>
                            <?= textarea_padrao('observacao', $observacaoInformada, [
                                'id' => 'observacao',
                                'rows' => '4',
                                'placeholder' => 'Ex.: venda, perda, consumo interno, envio para cliente.'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-900">Produto selecionado</h3>
                            <div class="mt-3 flex flex-col gap-4 md:flex-row">
                                <div class="h-24 w-24 overflow-hidden rounded border border-slate-200 bg-white">
                                    <img id="preview_produto_img" src="" alt="Imagem do produto" class="h-full w-full object-cover" style="display:none;">
                                    <div id="preview_produto_placeholder" class="flex h-full w-full items-center justify-center text-xs text-slate-400">Sem imagem</div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Locais disponíveis para saída</p>
                                    <ul id="lista_saldos_produto" class="mt-2 space-y-1 text-sm text-slate-700">
                                        <li class="text-slate-500">Selecione um produto para visualizar os saldos disponíveis.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit('Registrar saída', 'salvar') ?>
                        <?= botao_link('listar_movimentacoes_estoque.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">
                <strong>Regra aplicada:</strong>
                esta tela sempre grava movimentação do tipo <em>saída</em>, portanto a quantidade será lançada com efeito negativo no saldo.
            </div>
        </div>
    </main>
</div>

<script>
(() => {
    const imagens = <?= json_encode($imagemPorProduto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const saldos = <?= json_encode($saldosPorProduto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const produtoSelect = document.getElementById('produto_id');
    const previewImg = document.getElementById('preview_produto_img');
    const placeholder = document.getElementById('preview_produto_placeholder');
    const lista = document.getElementById('lista_saldos_produto');

    const renderProduto = () => {
        const produtoId = produtoSelect?.value || '';
        const imagem = imagens[produtoId] || '';
        if (imagem) {
            previewImg.src = imagem;
            previewImg.style.display = '';
            placeholder.style.display = 'none';
        } else {
            previewImg.src = '';
            previewImg.style.display = 'none';
            placeholder.style.display = 'flex';
        }

        lista.innerHTML = '';
        const itens = saldos[produtoId] || [];
        if (!itens.length) {
            const li = document.createElement('li');
            li.className = 'text-slate-500';
            li.textContent = 'Sem saldo disponível para este produto.';
            lista.appendChild(li);
            return;
        }
        itens.forEach((item) => {
            const li = document.createElement('li');
            li.textContent = `${item.estoque}: ${item.saldo}`;
            lista.appendChild(li);
        });
    };

    if (produtoSelect) {
        produtoSelect.addEventListener('change', renderProduto);
        renderProduto();
    }
})();
</script>

</body>
</html>