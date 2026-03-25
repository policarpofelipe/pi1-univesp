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

$modoEdicao = $id > 0;
$tituloPagina = $modoEdicao ? 'Editar Movimentação de Estoque' : 'Nova Movimentação de Estoque';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'produto_obrigatorio'         => 'Selecione um produto.',
    'estoque_obrigatorio'         => 'Selecione um local de estoque.',
    'tipo_obrigatorio'            => 'Selecione o tipo de movimentação.',
    'quantidade_obrigatoria'      => 'Informe a quantidade.',
    'quantidade_invalida'         => 'Informe uma quantidade válida e maior que zero.',
    'registro_nao_encontrado'     => 'Movimentação não encontrada.',
    'erro_interno'                => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'               => 0,
    'produto_id'       => '',
    'estoque_id'       => '',
    'tipo_movimentacao'=> '',
    'quantidade'       => '',
    'observacao'       => '',
];

/*
|--------------------------------------------------------------------------
| Carregar produtos ativos
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
| Carregar estoques ativos
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

$tiposMovimentacao = [
    ''        => 'Selecione o tipo',
    'entrada' => 'Entrada',
    'saida'   => 'Saída',
    'ajuste'  => 'Ajuste',
];

/*
|--------------------------------------------------------------------------
| Carregar movimentação para edição
|--------------------------------------------------------------------------
*/
if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            produto_id,
            estoque_id,
            tipo_movimentacao,
            quantidade,
            observacao
        FROM movimentacoes_estoque
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $mov = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mov) {
        $erro = 'Movimentação não encontrada.';
        $modoEdicao = false;
        $tituloPagina = 'Nova Movimentação de Estoque';
    } else {
        $dados = [
            'id'                => (int)$mov['id'],
            'produto_id'        => (string)($mov['produto_id'] ?? ''),
            'estoque_id'        => (string)($mov['estoque_id'] ?? ''),
            'tipo_movimentacao' => (string)($mov['tipo_movimentacao'] ?? ''),
            'quantidade'        => number_format((float)($mov['quantidade'] ?? 0), 2, '.', ''),
            'observacao'        => (string)($mov['observacao'] ?? ''),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($tituloPagina) ?></title>

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
                    <h1 class="text-2xl font-bold text-slate-900"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-600">
                        <?= $modoEdicao
                            ? 'Atualize os dados da movimentação registrada.'
                            : 'Registre uma entrada, saída ou ajuste de estoque.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_movimentacoes_estoque.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_movimentacao_estoque.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="produto_id" class="<?= classe_label() ?>">Produto</label>
                            <?= select_padrao(
                                'produto_id',
                                $opcoesProdutos,
                                $dados['produto_id'],
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
                                $dados['estoque_id'],
                                [
                                    'id' => 'estoque_id',
                                    'required' => true
                                ]
                            ) ?>
                        </div>

                        <div>
                            <label for="tipo_movimentacao" class="<?= classe_label() ?>">Tipo de movimentação</label>
                            <?= select_padrao(
                                'tipo_movimentacao',
                                $tiposMovimentacao,
                                $dados['tipo_movimentacao'],
                                [
                                    'id' => 'tipo_movimentacao',
                                    'required' => true
                                ]
                            ) ?>
                        </div>

                        <div>
                            <label for="quantidade" class="<?= classe_label() ?>">Quantidade</label>
                            <?= input_numero('quantidade', $dados['quantidade'], [
                                'id' => 'quantidade',
                                'step' => '0.01',
                                'min' => '0.01',
                                'required' => true,
                                'placeholder' => 'Ex.: 1, 2, 5, 10'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                A quantidade deve ser positiva. O tipo da movimentação define o efeito sobre o saldo.
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="observacao" class="<?= classe_label() ?>">Observação</label>
                            <?= textarea_padrao('observacao', $dados['observacao'], [
                                'id' => 'observacao',
                                'rows' => '4',
                                'placeholder' => 'Descreva o motivo da movimentação, origem, destino ou contexto do ajuste.'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Registrar movimentação', 'salvar') ?>
                        <?= botao_link('listar_movimentacoes_estoque.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                a movimentação é um evento histórico. Em termos rigorosos, o saldo de estoque deve derivar dessas ocorrências,
                e não de edição manual direta do saldo.
            </div>
        </div>
    </main>
</div>

</body>
</html>