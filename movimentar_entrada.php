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
$erro = '';

$mapaErros = [
    'produto_obrigatorio'    => 'Selecione um produto.',
    'estoque_obrigatorio'    => 'Selecione um local de estoque.',
    'quantidade_obrigatoria' => 'Informe a quantidade.',
    'quantidade_invalida'    => 'Informe uma quantidade válida e maior que zero.',
    'erro_interno'           => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentar Entrada</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Registrar Entrada</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Lance a entrada de mercadoria em um local de estoque.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_movimentacoes_estoque.php', 'Voltar para movimentações', 'cancelar') ?>
                    <?= botao_link('form_movimentacao_estoque.php', 'Formulário genérico', 'atalho') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_movimentacao_estoque.php" method="POST" class="space-y-6">
                    <input type="hidden" name="tipo_movimentacao" value="entrada">

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
                            <label for="quantidade" class="<?= classe_label() ?>">Quantidade de entrada</label>
                            <?= input_numero('quantidade', '', [
                                'id' => 'quantidade',
                                'step' => '0.01',
                                'min' => '0.01',
                                'required' => true,
                                'placeholder' => 'Ex.: 1, 5, 10'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="observacao" class="<?= classe_label() ?>">Observação</label>
                            <?= textarea_padrao('observacao', '', [
                                'id' => 'observacao',
                                'rows' => '4',
                                'placeholder' => 'Ex.: compra de fornecedor, devolução recebida, reposição interna.'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit('Registrar entrada', 'salvar') ?>
                        <?= botao_link('listar_movimentacoes_estoque.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                <strong>Regra aplicada:</strong>
                esta tela sempre grava movimentação do tipo <em>entrada</em>, portanto a quantidade será lançada com efeito positivo no saldo.
            </div>
        </div>
    </main>
</div>

</body>
</html>