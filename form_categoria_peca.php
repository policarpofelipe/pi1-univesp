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
$tituloPagina = $modoEdicao ? 'Editar Categoria de Peça' : 'Nova Categoria de Peça';

$erro = '';
$dados = [
    'id'        => 0,
    'nome'      => '',
    'descricao' => '',
    'ativo'     => '1',
];

if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            nome,
            descricao,
            ativo
        FROM categorias_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        $erro = 'Categoria não encontrada.';
        $modoEdicao = false;
        $tituloPagina = 'Nova Categoria de Peça';
    } else {
        $dados = [
            'id'        => (int)$categoria['id'],
            'nome'      => (string)($categoria['nome'] ?? ''),
            'descricao' => (string)($categoria['descricao'] ?? ''),
            'ativo'     => (string)($categoria['ativo'] ?? '1'),
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
        <div class="mx-auto max-w-4xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-300">
                        <?= $modoEdicao
                            ? 'Atualize os dados da categoria selecionada.'
                            : 'Cadastre uma nova categoria para organizar o catálogo de peças.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_categorias_peca.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_categoria_peca.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="nome" class="<?= classe_label() ?>">Nome da categoria</label>
                            <?= input_texto('nome', $dados['nome'], [
                                'id' => 'nome',
                                'maxlength' => '100',
                                'required' => true,
                                'placeholder' => 'Ex.: Freio, Suspensão, Motor, Elétrica'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Use um nome curto e claro. Evite duplicidades conceituais.
                            </p>
                        </div>

                        <div>
                            <label for="descricao" class="<?= classe_label() ?>">Descrição</label>
                            <?= textarea_padrao('descricao', $dados['descricao'], [
                                'id' => 'descricao',
                                'rows' => '4',
                                'placeholder' => 'Descreva o propósito desta categoria no catálogo.'
                            ]) ?>
                        </div>

                        <div>
                            <label for="ativo" class="<?= classe_label() ?>">Status</label>
                            <?= select_padrao('ativo', [
                                '1' => 'Ativa',
                                '0' => 'Inativa',
                            ], $dados['ativo'], [
                                'id' => 'ativo'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar categoria', 'salvar') ?>
                        <?= botao_link('listar_categorias_peca.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                categorias são um nível amplo de organização. Não confunda categoria com tipo de peça.
                Por exemplo, <em>Freio</em> é categoria; <em>Pastilha de freio dianteira</em> é tipo de peça.
            </div>
        </div>
    </main>
</div>

</body>
</html>