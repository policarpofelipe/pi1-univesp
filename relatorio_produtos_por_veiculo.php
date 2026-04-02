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

$marcaVeiculoId = (int)($_GET['marca_veiculo_id'] ?? 0);
$modeloVeiculoId = (int)($_GET['modelo_veiculo_id'] ?? 0);
$veiculoConfiguracaoId = (int)($_GET['veiculo_configuracao_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Carregar marcas
|--------------------------------------------------------------------------
*/
$sqlMarcas = "
    SELECT id, nome
    FROM marcas_veiculo
    WHERE ativo = 1
    ORDER BY nome ASC
";
$stmtMarcas = $pdo->query($sqlMarcas);
$marcas = $stmtMarcas->fetchAll(PDO::FETCH_ASSOC);

$opcoesMarcas = ['' => 'Selecione uma marca'];
foreach ($marcas as $marca) {
    $opcoesMarcas[(string)$marca['id']] = (string)$marca['nome'];
}

/*
|--------------------------------------------------------------------------
| Carregar modelos conforme marca
|--------------------------------------------------------------------------
*/
$opcoesModelos = ['' => 'Selecione um modelo'];

if ($marcaVeiculoId > 0) {
    $sqlModelos = "
        SELECT id, nome
        FROM modelos_veiculo
        WHERE marca_veiculo_id = :marca_veiculo_id
          AND ativo = 1
        ORDER BY nome ASC
    ";
    $stmtModelos = $pdo->prepare($sqlModelos);
    $stmtModelos->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtModelos->execute();
    $modelos = $stmtModelos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modelos as $modelo) {
        $opcoesModelos[(string)$modelo['id']] = (string)$modelo['nome'];
    }
} else {
    $modeloVeiculoId = 0;
    $veiculoConfiguracaoId = 0;
}

/*
|--------------------------------------------------------------------------
| Carregar configurações conforme modelo
|--------------------------------------------------------------------------
*/
$opcoesConfiguracoes = ['' => 'Selecione uma configuração'];

if ($modeloVeiculoId > 0) {
    $sqlConfiguracoes = "
        SELECT
            id,
            ano_inicio,
            ano_fim,
            motorizacao,
            combustivel,
            versao
        FROM veiculos_configuracao
        WHERE modelo_veiculo_id = :modelo_veiculo_id
          AND ativo = 1
        ORDER BY ano_inicio ASC, versao ASC
    ";
    $stmtConfiguracoes = $pdo->prepare($sqlConfiguracoes);
    $stmtConfiguracoes->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtConfiguracoes->execute();
    $configuracoes = $stmtConfiguracoes->fetchAll(PDO::FETCH_ASSOC);

    foreach ($configuracoes as $config) {
        $ano = ((int)$config['ano_inicio'] === (int)$config['ano_fim'])
            ? (string)$config['ano_inicio']
            : $config['ano_inicio'] . ' a ' . $config['ano_fim'];

        $partes = [$ano];

        if (!empty($config['motorizacao'])) {
            $partes[] = $config['motorizacao'];
        }

        if (!empty($config['combustivel'])) {
            $partes[] = $config['combustivel'];
        }

        if (!empty($config['versao'])) {
            $partes[] = $config['versao'];
        }

        $opcoesConfiguracoes[(string)$config['id']] = implode(' / ', $partes);
    }
} else {
    $veiculoConfiguracaoId = 0;
}

$configuracaoSelecionada = null;
$agrupado = [];
$totalProdutos = 0;
$totalTipos = 0;

if ($veiculoConfiguracaoId > 0) {
    /*
    |--------------------------------------------------------------------------
    | Buscar configuração selecionada
    |--------------------------------------------------------------------------
    */
    $sqlConfiguracaoSelecionada = "
        SELECT
            vc.id,
            vc.ano_inicio,
            vc.ano_fim,
            vc.motorizacao,
            vc.combustivel,
            vc.versao,
            mo.nome AS modelo_nome,
            mv.nome AS marca_nome
        FROM veiculos_configuracao vc
        INNER JOIN modelos_veiculo mo
            ON mo.id = vc.modelo_veiculo_id
        INNER JOIN marcas_veiculo mv
            ON mv.id = mo.marca_veiculo_id
        WHERE vc.id = :id
        LIMIT 1
    ";
    $stmtConfiguracaoSelecionada = $pdo->prepare($sqlConfiguracaoSelecionada);
    $stmtConfiguracaoSelecionada->bindValue(':id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtConfiguracaoSelecionada->execute();
    $configuracaoSelecionada = $stmtConfiguracaoSelecionada->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | Buscar produtos compatíveis
    |--------------------------------------------------------------------------
    */
    $sqlProdutos = "
        SELECT
            tp.id AS tipo_peca_id,
            tp.nome AS tipo_peca_nome,

            p.id AS produto_id,
            p.nome_comercial,
            p.sku_interno,
            p.codigo_fabricante,
            p.codigo_barras,
            p.preco,

            mp.nome AS marca_produto_nome,

            ap.observacao AS aplicacao_observacao

        FROM aplicacoes_peca ap
        INNER JOIN tipos_peca tp
            ON tp.id = ap.tipo_peca_id
        INNER JOIN produtos p
            ON p.tipo_peca_id = tp.id
        INNER JOIN marcas_produto mp
            ON mp.id = p.marca_produto_id
        WHERE ap.veiculo_configuracao_id = :veiculo_configuracao_id
          AND p.ativo = 1
        ORDER BY tp.nome ASC, mp.nome ASC, p.nome_comercial ASC
    ";

    $stmtProdutos = $pdo->prepare($sqlProdutos);
    $stmtProdutos->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtProdutos->execute();

    $linhas = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($linhas as $linha) {
        $tipoId = (int)$linha['tipo_peca_id'];

        if (!isset($agrupado[$tipoId])) {
            $agrupado[$tipoId] = [
                'tipo_peca_nome' => $linha['tipo_peca_nome'],
                'observacao'     => $linha['aplicacao_observacao'],
                'produtos'       => [],
            ];
        }

        $agrupado[$tipoId]['produtos'][] = [
            'produto_id'         => (int)$linha['produto_id'],
            'nome_comercial'     => $linha['nome_comercial'],
            'sku_interno'        => $linha['sku_interno'],
            'codigo_fabricante'  => $linha['codigo_fabricante'],
            'codigo_barras'      => $linha['codigo_barras'],
            'preco'              => (float)$linha['preco'],
            'marca_produto_nome' => $linha['marca_produto_nome'],
        ];

        $totalProdutos++;
    }

    $totalTipos = count($agrupado);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Produtos por Veículo</title>

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

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Relatório de Produtos por Veículo</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Consulte os produtos compatíveis com uma configuração veicular específica.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('consulta_veiculo.php', 'Consulta interativa', 'atalho') ?>
                    <button type="button" onclick="imprimirRelatorio()" class="<?= esc($btn_busca ?? 'px-3 py-2 rounded-lg bg-blue-600 text-white') ?>">
                        Imprimir
                    </button>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6 print:hidden">
                <form method="GET" class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca</label>
                        <?= select_padrao(
                            'marca_veiculo_id',
                            $opcoesMarcas,
                            $marcaVeiculoId > 0 ? (string)$marcaVeiculoId : '',
                            [
                                'id' => 'marca_veiculo_id',
                                'onchange' => 'this.form.submit()'
                            ]
                        ) ?>
                    </div>

                    <div>
                        <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo</label>
                        <?= select_padrao(
                            'modelo_veiculo_id',
                            $opcoesModelos,
                            $modeloVeiculoId > 0 ? (string)$modeloVeiculoId : '',
                            [
                                'id' => 'modelo_veiculo_id',
                                'onchange' => 'this.form.submit()'
                            ]
                        ) ?>
                    </div>

                    <div>
                        <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração</label>
                        <?= select_padrao(
                            'veiculo_configuracao_id',
                            $opcoesConfiguracoes,
                            $veiculoConfiguracaoId > 0 ? (string)$veiculoConfiguracaoId : '',
                            [
                                'id' => 'veiculo_configuracao_id'
                            ]
                        ) ?>
                    </div>

                    <div class="md:col-span-3 flex flex-col gap-2 sm:flex-row">
                        <?= botao_submit('Gerar relatório', 'busca') ?>
                        <?= botao_link('relatorio_produtos_por_veiculo.php', 'Limpar seleção', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <?php if ($veiculoConfiguracaoId > 0 && $configuracaoSelecionada): ?>
                <?php
                $anoLabel = ((int)$configuracaoSelecionada['ano_inicio'] === (int)$configuracaoSelecionada['ano_fim'])
                    ? (string)$configuracaoSelecionada['ano_inicio']
                    : $configuracaoSelecionada['ano_inicio'] . ' a ' . $configuracaoSelecionada['ano_fim'];

                $partesVeiculo = [
                    $configuracaoSelecionada['marca_nome'],
                    $configuracaoSelecionada['modelo_nome'],
                    $anoLabel
                ];

                if (!empty($configuracaoSelecionada['motorizacao'])) {
                    $partesVeiculo[] = $configuracaoSelecionada['motorizacao'];
                }

                if (!empty($configuracaoSelecionada['combustivel'])) {
                    $partesVeiculo[] = $configuracaoSelecionada['combustivel'];
                }

                if (!empty($configuracaoSelecionada['versao'])) {
                    $partesVeiculo[] = $configuracaoSelecionada['versao'];
                }

                $veiculoLabel = implode(' / ', $partesVeiculo);
                ?>

                <div class="<?= classe_box() ?> mb-6 print:shadow-none print:border print:border-slate-300">
                    <div class="border-b border-slate-200 pb-4 mb-4">
                        <h2 class="text-xl font-bold text-slate-900">Relatório de Compatibilidade</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            <strong class="text-slate-800">Veículo consultado:</strong>
                            <?= esc($veiculoLabel) ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Tipos de peça</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalTipos ?></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Produtos compatíveis</div>
                            <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalProdutos ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!$agrupado): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">
                            Nenhum produto compatível foi encontrado para esta configuração veicular.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($agrupado as $grupo): ?>
                            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden print:shadow-none">
                                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-900">
                                                <?= esc($grupo['tipo_peca_nome']) ?>
                                            </h3>

                                            <?php if (!empty($grupo['observacao'])): ?>
                                                <p class="mt-1 text-sm text-slate-600">
                                                    <?= esc($grupo['observacao']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="text-sm text-slate-500">
                                            <?= count($grupo['produtos']) ?> produto(s)
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                            <tr class="border-b border-slate-200">
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SKU</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Cód. fabricante</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Cód. barras</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Preço</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 print:hidden">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grupo['produtos'] as $produto): ?>
                                                <tr class="border-b border-slate-100 last:border-b-0">
                                                    <td class="px-4 py-4">
                                                        <div class="font-medium text-slate-900">
                                                            <?= esc($produto['nome_comercial']) ?>
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-4 text-sm text-slate-700">
                                                        <?= esc($produto['marca_produto_nome']) ?>
                                                    </td>

                                                    <td class="px-4 py-4 text-sm text-slate-600">
                                                        <?= esc($produto['sku_interno']) ?>
                                                    </td>

                                                    <td class="px-4 py-4 text-sm text-slate-600">
                                                        <?= esc($produto['codigo_fabricante']) ?>
                                                    </td>

                                                    <td class="px-4 py-4 text-sm text-slate-600">
                                                        <?= esc($produto['codigo_barras'] ?: '—') ?>
                                                    </td>

                                                    <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                                        R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                                                    </td>

                                                    <td class="px-4 py-4 print:hidden">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <?= botao_link(
                                                                'ver_produto.php?id=' . (int)$produto['produto_id'],
                                                                'Ver',
                                                                'atalho'
                                                            ) ?>

                                                            <?= botao_link(
                                                                'ver_aplicacoes_produto.php?id=' . (int)$produto['produto_id'],
                                                                'Aplicações',
                                                                'busca'
                                                            ) ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <p class="text-sm text-slate-600">
                        Selecione marca, modelo e configuração para gerar o relatório.
                    </p>
                </div>
            <?php endif; ?>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm print:shadow-none">
                <strong class="text-slate-800">Observação:</strong>
                este relatório não parte do produto isolado, mas da configuração veicular. Isso é metodologicamente correto para um sistema de autopeças orientado à compatibilidade.
            </div>
        </div>
    </main>
</div>

</body>
</html>