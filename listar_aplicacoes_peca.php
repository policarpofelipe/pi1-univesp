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
                'texto'  => 'Aplicação de peça cadastrada com sucesso.',
            ],
            'editado' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Aplicação de peça atualizada com sucesso.',
            ],
            'excluido' => [
                'classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'texto'  => 'Aplicação de peça excluída com sucesso.',
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
                'texto'  => 'Ocorreu um erro ao excluir a aplicação.',
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
        ap.id,
        ap.observacao,
        ap.criado_em,
        ap.atualizado_em,

        tp.nome AS tipo_peca_nome,

        mv.nome AS marca_nome,
        mo.nome AS modelo_nome,

        vc.ano_inicio,
        vc.ano_fim,
        vc.motorizacao,
        vc.combustivel,
        vc.versao

    FROM aplicacoes_peca ap
    INNER JOIN tipos_peca tp
        ON tp.id = ap.tipo_peca_id
    INNER JOIN veiculos_configuracao vc
        ON vc.id = ap.veiculo_configuracao_id
    INNER JOIN modelos_veiculo mo
        ON mo.id = vc.modelo_veiculo_id
    INNER JOIN marcas_veiculo mv
        ON mv.id = mo.marca_veiculo_id
    WHERE 1 = 1
";

$params = [];

if ($busca !== '') {
    $sql .= " AND (
        tp.nome LIKE :busca
        OR mv.nome LIKE :busca
        OR mo.nome LIKE :busca
        OR vc.versao LIKE :busca
        OR vc.motorizacao LIKE :busca
        OR vc.combustivel LIKE :busca
        OR ap.observacao LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY tp.nome ASC, mv.nome ASC, mo.nome ASC, vc.ano_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$aplicacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAplicacoes = count($aplicacoes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicações de Peça</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Aplicações de Peça</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Gerencie a compatibilidade entre tipos de peça e configurações veiculares.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('form_aplicacao_peca.php', 'Nova aplicação', 'salvar') ?>
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
                        <label for="busca" class="<?= classe_label() ?>">Buscar aplicação</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite tipo de peça, marca, modelo, versão, motorização, combustível ou observação'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('listar_aplicacoes_peca.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Total listado</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalAplicacoes ?></div>
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
                    <h2 class="text-lg font-semibold text-slate-900">Lista de aplicações</h2>
                </div>

                <?php if (!$aplicacoes): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhuma aplicação de peça encontrada.
                        </p>

                        <div class="mt-4">
                            <?= botao_link('form_aplicacao_peca.php', 'Cadastrar primeira aplicação', 'salvar') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de peça</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Veículo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ano</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Motor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Combustível</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Versão</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Observação</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aplicacoes as $aplicacao): ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm">
                                        <td class="rounded-l-xl px-4 py-4 text-sm text-slate-600">
                                            <?= (int)$aplicacao['id'] ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($aplicacao['tipo_peca_nome']) ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900">
                                                <?= esc($aplicacao['marca_nome']) ?> / <?= esc($aplicacao['modelo_nome']) ?>
                                            </div>
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

                                        <td class="rounded-r-xl px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link(
                                                    'form_aplicacao_peca.php?id=' . (int)$aplicacao['id'],
                                                    'Editar',
                                                    'editar'
                                                ) ?>

                                                <?= botao_excluir(
                                                    'excluir_aplicacao_peca.php?id=' . (int)$aplicacao['id'],
                                                    'Tem certeza que deseja excluir esta aplicação?',
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