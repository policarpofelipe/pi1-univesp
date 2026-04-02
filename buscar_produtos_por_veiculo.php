<?php
declare(strict_types=1);

if (!isset($pdo)) {
    exit('Conexão não disponível.');
}

if (!function_exists('esc')) {
    function esc(?string $valor): string
    {
        return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$veiculoConfiguracaoId = (int)($_GET['veiculo_configuracao_id'] ?? 0);

if ($veiculoConfiguracaoId <= 0) {
    echo '<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Configuração veicular inválida.</div>';
    return;
}

/*
|--------------------------------------------------------------------------
| Buscar dados da configuração selecionada
|--------------------------------------------------------------------------
*/
$sqlConfiguracao = "
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

$stmtConfiguracao = $pdo->prepare($sqlConfiguracao);
$stmtConfiguracao->bindValue(':id', $veiculoConfiguracaoId, PDO::PARAM_INT);
$stmtConfiguracao->execute();

$configuracao = $stmtConfiguracao->fetch(PDO::FETCH_ASSOC);

if (!$configuracao) {
    echo '<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Configuração veicular não encontrada.</div>';
    return;
}

/*
|--------------------------------------------------------------------------
| Buscar produtos compatíveis por tipo de peça
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
        p.ativo,

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

/*
|--------------------------------------------------------------------------
| Agrupar por tipo de peça
|--------------------------------------------------------------------------
*/
$agrupado = [];

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
        'ativo'              => (int)$linha['ativo'],
    ];
}

$anoLabel = ((int)$configuracao['ano_inicio'] === (int)$configuracao['ano_fim'])
    ? (string)$configuracao['ano_inicio']
    : $configuracao['ano_inicio'] . ' a ' . $configuracao['ano_fim'];

$partesVeiculo = [
    $configuracao['marca_nome'],
    $configuracao['modelo_nome'],
    $anoLabel
];

if (!empty($configuracao['motorizacao'])) {
    $partesVeiculo[] = $configuracao['motorizacao'];
}

if (!empty($configuracao['combustivel'])) {
    $partesVeiculo[] = $configuracao['combustivel'];
}

if (!empty($configuracao['versao'])) {
    $partesVeiculo[] = $configuracao['versao'];
}

$veiculoLabel = implode(' / ', $partesVeiculo);
?>

<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="text-sm text-slate-500">Configuração consultada</div>
    <div class="mt-2 text-lg font-semibold text-slate-900">
        <?= esc($veiculoLabel) ?>
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
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
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
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
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

                                    <td class="px-4 py-4">
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