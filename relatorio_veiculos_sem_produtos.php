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

$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT
        vc.id,
        vc.ano_inicio,
        vc.ano_fim,
        vc.motorizacao,
        vc.combustivel,
        vc.versao,
        vc.observacoes,
        vc.ativo,
        vc.criado_em,
        vc.atualizado_em,

        mo.nome AS modelo_nome,
        mv.nome AS marca_nome,

        COUNT(DISTINCT p.id) AS total_produtos_ativos

    FROM veiculos_configuracao vc
    INNER JOIN modelos_veiculo mo
        ON mo.id = vc.modelo_veiculo_id
    INNER JOIN marcas_veiculo mv
        ON mv.id = mo.marca_veiculo_id
    LEFT JOIN aplicacoes_produto ap
        ON ap.veiculo_configuracao_id = vc.id
       AND ap.ativo = 1
    LEFT JOIN produtos p
        ON p.id = ap.produto_id
       AND p.ativo = 1
    WHERE 1 = 1
";

$params = [];

if ($busca !== '') {
    $sql .= " AND (
        mv.nome LIKE :busca
        OR mo.nome LIKE :busca
        OR vc.motorizacao LIKE :busca
        OR vc.combustivel LIKE :busca
        OR vc.versao LIKE :busca
        OR vc.observacoes LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= "
    GROUP BY
        vc.id,
        vc.ano_inicio,
        vc.ano_fim,
        vc.motorizacao,
        vc.combustivel,
        vc.versao,
        vc.observacoes,
        vc.ativo,
        vc.criado_em,
        vc.atualizado_em,
        mo.nome,
        mv.nome
    HAVING COUNT(DISTINCT p.id) = 0
    ORDER BY
        mv.nome ASC,
        mo.nome ASC,
        vc.ano_inicio ASC,
        vc.versao ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalVeiculos = count($veiculos);
$totalAtivos = 0;
$totalInativos = 0;

foreach ($veiculos as $veiculo) {
    if ((int)$veiculo['ativo'] === 1) {
        $totalAtivos++;
    } else {
        $totalInativos++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Veículos sem Produtos</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.Alpine && window.AlpineCollapse) {
                Alpine.plugin(window.AlpineCollapse);
            }
        });

        function imprimirRelatorio() {
            window.print();
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 print:bg-white">

<div class="min-h-screen md:flex print:block">
    <div class="print:hidden">
        <?php require __DIR__ . '/menu.php'; ?>
    </div>

    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6 print:p-0">
        <div class="mx-auto max-w-7xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-white">Relatório de Veículos sem Produtos</h1>
                    <p class="mt-1 text-sm text-slate-300">
                        Configurações veiculares cadastradas que ainda não retornam nenhum produto ativo compatível.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('consulta_veiculo.php', 'Consulta por veículo', 'atalho') ?>
                    <button type="button" onclick="imprimirRelatorio()" class="<?= esc($btn_busca ?? 'px-3 py-2 rounded-lg bg-blue-600 text-white') ?>">
                        Imprimir
                    </button>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6 print:hidden">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-9">
                        <label for="busca" class="<?= classe_label() ?>">Buscar no relatório</label>
                        <?= input_texto('busca', $busca, [
                            'id' => 'busca',
                            'placeholder' => 'Digite marca, modelo, motorização, combustível, versão ou observação'
                        ]) ?>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full">
                            <?= botao_submit('Buscar', 'busca') ?>
                        </div>
                        <?= botao_link('relatorio_veiculos_sem_produtos.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Total no relatório</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalVeiculos ?></div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Configurações ativas</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600"><?= $totalAtivos ?></div>
                </div>

                <div class="<?= classe_box() ?> print:shadow-none">
                    <div class="text-sm text-slate-500">Configurações inativas</div>
                    <div class="mt-2 text-3xl font-bold text-red-600"><?= $totalInativos ?></div>
                </div>
            </div>

            <div class="<?= classe_box() ?> print:shadow-none print:border print:border-slate-300">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Configurações sem produtos compatíveis</h2>
                </div>

                <?php if (!$veiculos): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhuma configuração veicular sem produtos foi encontrada.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2 print:border-collapse print:border-spacing-0">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ano</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Motorização</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Combustível</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Versão</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 print:hidden">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($veiculos as $veiculo): ?>
                                    <?php
                                    $ativo = (int)$veiculo['ativo'] === 1;
                                    $anoLabel = ((int)$veiculo['ano_inicio'] === (int)$veiculo['ano_fim'])
                                        ? (string)$veiculo['ano_inicio']
                                        : $veiculo['ano_inicio'] . ' a ' . $veiculo['ano_fim'];
                                    ?>
                                    <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm print:shadow-none print:border-b print:border-slate-200">
                                        <td class="rounded-l-xl px-4 py-4 text-sm text-slate-700">
                                            <?= esc($veiculo['marca_nome']) ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= esc($veiculo['modelo_nome']) ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($anoLabel) ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($veiculo['motorizacao'] ?: '—') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($veiculo['combustivel'] ?: '—') ?>
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <?= esc($veiculo['versao'] ?: '—') ?>
                                        </td>

                                        <td class="px-4 py-4">
                                            <?php if ($ativo): ?>
                                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                                                    Ativa
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                                    Inativa
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="rounded-r-xl px-4 py-4 print:hidden">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link(
                                                    'consulta_veiculo.php?marca_veiculo_id=' . urlencode((string)($_GET['marca_veiculo_id'] ?? '')) .
                                                    '&modelo_veiculo_id=' . (int)$veiculo['id'],
                                                    'Consultar',
                                                    'atalho'
                                                ) ?>

                                                <?= botao_link(
                                                    'form_aplicacao_produto.php',
                                                    'Cadastrar aplicação',
                                                    'salvar'
                                                ) ?>

                                                <?= botao_link(
                                                    'form_veiculo_configuracao.php?id=' . (int)$veiculo['id'],
                                                    'Editar',
                                                    'editar'
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

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm print:shadow-none">
                <strong class="text-slate-800">Observação:</strong>
                este relatório mostra configurações veiculares semanticamente incompletas do ponto de vista comercial. O veículo existe no catálogo, mas ainda não retorna peças utilizáveis na consulta.
            </div>
        </div>
    </main>
</div>

</body>
</html>