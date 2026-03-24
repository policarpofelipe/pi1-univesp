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
                'texto'  => 'Local de estoque cadastrado com sucesso.',
            ],
            'editado' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Local de estoque atualizado com sucesso.',
            ],
            'excluido' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Local de estoque excluído com sucesso.',
            ],
            'inativado' => [
                'classe' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                'texto'  => 'O local possui vínculos e foi apenas inativado.',
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
                'texto'  => 'Ocorreu um erro ao excluir o local de estoque.',
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

$retorno = null;
if ($sucesso !== '') {
    $retorno = mensagemRetorno('sucesso', $sucesso);
} elseif ($erro !== '') {
    $retorno = mensagemRetorno('erro', $erro);
}

$sql = "
    SELECT
        id,
        nome,
        localizacao,
        ativo,
        criado_em,
        atualizado_em
    FROM estoques
    WHERE 1 = 1
";

$params = [];

if ($busca !== '') {
    $sql .= " AND (
        nome LIKE :busca
        OR localizacao LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estoques = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEstoques = count($estoques);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locais de Estoque</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Locais de Estoque</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Gerencie depósitos, prateleiras, almoxarifados ou outros pontos físicos de armazenamento.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('form_estoque.php', 'Novo local', 'salvar') ?>
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
                        <label for="busca" class="<?= classe_label() ?>">Buscar local de estoque</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite nome ou localização'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('listar_estoques.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Total listado</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalEstoques ?></div>
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
                    <h2 class="text-lg font-semibold text-slate-900">Lista de locais de estoque</h2>
                </div>

                <?php if (!$estoques): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum local de estoque encontrado.
                        </p>

                        <div class="mt-4">
                            <?= botao_link('form_estoque.php', 'Cadastrar primeiro local', 'salvar') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Localização</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Atualizado em</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estoques as $estoque): ?>
                                    <?php $ativo = (int)($estoque['ativo'] ?? 0) === 1; ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm">
                                        <td class="rounded-l-xl px-4 py-4 text-sm text-slate-600">
                                            <?= (int)$estoque['id'] ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($estoque['nome']) ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($estoque['localizacao'] ?: '—') ?>
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

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?= esc($estoque['atualizado_em'] ?: $estoque['criado_em'] ?: '—') ?>
                                        </td>

                                        <td class="rounded-r-xl px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link(
                                                    'form_estoque.php?id=' . (int)$estoque['id'],
                                                    'Editar',
                                                    'editar'
                                                ) ?>

                                                <?= botao_excluir(
                                                    'excluir_estoque.php?id=' . (int)$estoque['id'],
                                                    'Tem certeza que deseja excluir este local de estoque?',
                                                    'Excluir'
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