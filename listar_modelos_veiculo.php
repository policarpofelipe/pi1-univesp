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
                'texto'  => 'Modelo de veículo cadastrado com sucesso.',
            ],
            'editado' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Modelo de veículo atualizado com sucesso.',
            ],
            'excluido' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Modelo de veículo excluído com sucesso.',
            ],
            'inativado' => [
                'classe' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
                'texto'  => 'O modelo possui vínculos e foi apenas inativado.',
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
                'texto'  => 'Ocorreu um erro ao excluir o modelo de veículo.',
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
        mv.id,
        mv.nome,
        mv.ativo,
        mv.criado_em,
        mv.atualizado_em,
        mar.nome AS marca_veiculo_nome
    FROM modelos_veiculo mv
    INNER JOIN marcas_veiculo mar
        ON mar.id = mv.marca_veiculo_id
    WHERE 1 = 1
";

$params = [];

if ($busca !== '') {
    $sql .= " AND (
        mv.nome LIKE :busca
        OR mar.nome LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY mar.nome ASC, mv.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalModelos = count($modelos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modelos de Veículo</title>

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

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white shadow-lg">
                <div class="p-5 md:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Sistema de Controle de Estoque</p>
                            <h1 class="mt-2 text-2xl md:text-3xl font-bold">Modelos de Veículo</h1>
                            <p class="mt-2 text-sm text-slate-300">Gerencie os modelos vinculados às marcas do catálogo veicular.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                    <?= botao_link('importar_planilha.php?tipo=modelos_veiculo', 'Importar planilha', 'busca') ?>
                    <?= botao_link('form_modelo_veiculo.php', 'Novo modelo', 'salvar') ?>
                        </div>
                    </div>
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
                        <label for="busca" class="<?= classe_label() ?>">Buscar modelo</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite o nome do modelo ou da marca'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('listar_modelos_veiculo.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Total listado</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalModelos ?></div>
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
                    <h2 class="text-lg font-semibold text-slate-900">Lista de modelos de veículo</h2>
                </div>

                <?php if (!$modelos): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum modelo de veículo encontrado.
                        </p>

                        <div class="mt-4">
                            <?= botao_link('form_modelo_veiculo.php', 'Cadastrar primeiro modelo', 'salvar') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Atualizado em</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modelos as $modelo): ?>
                                    <?php $ativo = (int)($modelo['ativo'] ?? 0) === 1; ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm">
                                        <td class="rounded-l-xl px-4 py-4 text-sm text-slate-600">
                                            <?= (int)$modelo['id'] ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700">
                                                <?= esc($modelo['marca_veiculo_nome']) ?>
                                            </span>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($modelo['nome']) ?>
                                            </div>
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
                                            <?= esc($modelo['atualizado_em'] ?: $modelo['criado_em'] ?: '—') ?>
                                        </td>

                                        <td class="rounded-r-xl px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link(
                                                    'form_modelo_veiculo.php?id=' . (int)$modelo['id'],
                                                    'Editar',
                                                    'editar'
                                                ) ?>

                                                <?= botao_excluir(
                                                    'excluir_modelo_veiculo.php?id=' . (int)$modelo['id'],
                                                    'Tem certeza que deseja excluir este modelo de veículo?',
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
