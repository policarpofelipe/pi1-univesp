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
    'produto_obrigatorio'         => 'Selecione um produto.',
    'estoque_obrigatorio'         => 'Selecione um local de estoque.',
    'saldo_final_obrigatorio'     => 'Informe o saldo final desejado.',
    'saldo_final_invalido'        => 'Informe um saldo final válido.',
    'erro_interno'                => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$produtoSelecionado = (int)($_GET['produto_id'] ?? 0);
$estoqueSelecionado = (int)($_GET['estoque_id'] ?? 0);
$saldoAtual = null;
$produtoInfo = null;

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

/*
|--------------------------------------------------------------------------
| Buscar saldo atual quando produto e estoque estiverem selecionados
|--------------------------------------------------------------------------
*/
if ($produtoSelecionado > 0 && $estoqueSelecionado > 0) {
    $sqlProdutoInfo = "
        SELECT id, nome_comercial, sku_interno
        FROM produtos
        WHERE id = :produto_id
        LIMIT 1
    ";
    $stmtProdutoInfo = $pdo->prepare($sqlProdutoInfo);
    $stmtProdutoInfo->bindValue(':produto_id', $produtoSelecionado, PDO::PARAM_INT);
    $stmtProdutoInfo->execute();
    $produtoInfo = $stmtProdutoInfo->fetch(PDO::FETCH_ASSOC);

    $sqlSaldo = "
        SELECT COALESCE(SUM(
            CASE
                WHEN tipo_movimento = 'saida' THEN -quantidade
                WHEN tipo_movimento = 'entrada' THEN quantidade
                ELSE quantidade
            END
        ), 0) AS saldo_atual
        FROM movimentacoes_estoque
        WHERE produto_id = :produto_id
          AND estoque_id = :estoque_id
    ";
    $stmtSaldo = $pdo->prepare($sqlSaldo);
    $stmtSaldo->bindValue(':produto_id', $produtoSelecionado, PDO::PARAM_INT);
    $stmtSaldo->bindValue(':estoque_id', $estoqueSelecionado, PDO::PARAM_INT);
    $stmtSaldo->execute();

    $saldoAtual = (float)$stmtSaldo->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustar Estoque</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Ajustar Estoque</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Informe o saldo final desejado e o sistema calculará a diferença a ajustar.
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

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="produto_id" class="<?= classe_label() ?>">Produto</label>
                        <?= select_padrao(
                            'produto_id',
                            $opcoesProdutos,
                            $produtoSelecionado > 0 ? (string)$produtoSelecionado : '',
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
                            $estoqueSelecionado > 0 ? (string)$estoqueSelecionado : '',
                            [
                                'id' => 'estoque_id',
                                'required' => true
                            ]
                        ) ?>
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Consultar saldo', 'busca') ?>
                        </div>
                        <?= botao_link('ajustar_estoque.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <?php if ($produtoSelecionado > 0 && $estoqueSelecionado > 0 && $produtoInfo): ?>
                <div class="<?= classe_box() ?>">
                    <div class="mb-4 border-b border-slate-200 pb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Ajuste por saldo final</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Produto: <strong><?= esc($produtoInfo['nome_comercial']) ?></strong>
                            [<?= esc($produtoInfo['sku_interno']) ?>]
                        </p>
                    </div>

                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Saldo atual</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900">
                                <?= number_format((float)$saldoAtual, 2, ',', '.') ?>
                            </div>
                        </div>
                    </div>

                    <form
                        action="salvar_movimentacao_estoque.php"
                        method="POST"
                        x-data="{
                            saldoAtual: <?= json_encode((float)$saldoAtual) ?>,
                            saldoFinal: '',
                            get diferenca() {
                                let valor = parseFloat((this.saldoFinal + '').replace(',', '.'));
                                if (isNaN(valor)) return '';
                                return (valor - this.saldoAtual).toFixed(2);
                            }
                        }"
                        class="space-y-6"
                    >
                        <input type="hidden" name="produto_id" value="<?= (int)$produtoSelecionado ?>">
                        <input type="hidden" name="estoque_id" value="<?= (int)$estoqueSelecionado ?>">
                        <input type="hidden" name="tipo_movimentacao" value="ajuste">

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="saldo_final" class="<?= classe_label() ?>">Saldo final desejado</label>
                                <?= input_numero('saldo_final', '', [
                                    'id' => 'saldo_final',
                                    'step' => '0.01',
                                    'required' => true,
                                    'placeholder' => 'Ex.: 12, 25, 0'
                                ]) ?>
                                <p class="mt-1 text-xs text-slate-500">
                                    Digite o saldo que deve permanecer após o ajuste.
                                </p>
                            </div>

                            <div>
                                <label class="<?= classe_label() ?>">Diferença a lançar</label>
                                <div class="h-10 rounded-lg border border-slate-300 bg-slate-50 px-3 flex items-center text-sm text-slate-700">
                                    <span x-text="diferenca === '' ? '—' : diferenca"></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Valor positivo aumenta o saldo; valor negativo reduz o saldo.
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label for="observacao" class="<?= classe_label() ?>">Observação</label>
                                <?= textarea_padrao('observacao', '', [
                                    'id' => 'observacao',
                                    'rows' => '4',
                                    'placeholder' => 'Ex.: ajuste por inventário físico, correção de divergência, conferência de prateleira.'
                                ]) ?>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const form = document.currentScript.closest('form');
                                const saldoFinal = form.querySelector('#saldo_final');

                                form.addEventListener('submit', function (e) {
                                    const atual = <?= json_encode((float)$saldoAtual) ?>;
                                    const finalValor = parseFloat((saldoFinal.value + '').replace(',', '.'));

                                    if (isNaN(finalValor)) {
                                        return;
                                    }

                                    const diferenca = (finalValor - atual).toFixed(2);

                                    let hiddenQuantidade = form.querySelector('input[name="quantidade"]');
                                    if (!hiddenQuantidade) {
                                        hiddenQuantidade = document.createElement('input');
                                        hiddenQuantidade.type = 'hidden';
                                        hiddenQuantidade.name = 'quantidade';
                                        form.appendChild(hiddenQuantidade);
                                    }

                                    hiddenQuantidade.value = diferenca;
                                });
                            });
                        </script>

                        <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                            <?= botao_submit('Registrar ajuste', 'salvar') ?>
                            <?= botao_link('listar_movimentacoes_estoque.php', 'Cancelar', 'cancelar') ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="mt-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 shadow-sm">
                <strong>Regra aplicada:</strong>
                esta tela não pede a quantidade do ajuste diretamente. Ela pede o <em>saldo final desejado</em> e calcula a diferença em relação ao saldo atual.
            </div>
        </div>
    </main>
</div>

</body>
</html>