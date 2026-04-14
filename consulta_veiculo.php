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

function montar_query_string(array $params): string
{
    return http_build_query(array_filter($params, static function ($v) {
        return $v !== null && $v !== '';
    }));
}

$termoBusca = trim((string)($_GET['q'] ?? ''));
$marcaVeiculoId = (int)($_GET['marca_veiculo_id'] ?? 0);
$modeloVeiculoId = (int)($_GET['modelo_veiculo_id'] ?? 0);
$veiculoConfiguracaoId = (int)($_GET['veiculo_configuracao_id'] ?? 0);
$tipoPecaId = (int)($_GET['tipo_peca_id'] ?? 0);
$marcaProdutoId = (int)($_GET['marca_produto_id'] ?? 0);
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 50;

$erro = '';
$aviso = '';
$totalItens = 0;
$resultados = [];
$totalPaginas = 1;

$opcoesMarcasVeiculo = ['' => 'Todas as marcas'];
$opcoesModelosVeiculo = ['' => 'Todos os modelos'];
$opcoesConfiguracoes = ['' => 'Todas as configurações'];
$opcoesTiposPeca = ['' => 'Todos os tipos de peça'];
$opcoesMarcasProduto = ['' => 'Todas as marcas de produto'];

try {
    $filtrosVeicularesDisponiveis = true;
    try {
        $stmtMarcas = $pdo->query("
            SELECT id, nome
            FROM marcas_veiculo
            WHERE ativo = 1
            ORDER BY nome ASC
        ");
        foreach ($stmtMarcas->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $opcoesMarcasVeiculo[(string)$linha['id']] = (string)$linha['nome'];
        }

        if ($marcaVeiculoId > 0) {
            $stmtModelos = $pdo->prepare("
                SELECT id, nome
                FROM modelos_veiculo
                WHERE ativo = 1
                  AND marca_veiculo_id = :marca_veiculo_id
                ORDER BY nome ASC
            ");
            $stmtModelos->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
            $stmtModelos->execute();
            foreach ($stmtModelos->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $opcoesModelosVeiculo[(string)$linha['id']] = (string)$linha['nome'];
            }
        }

        if ($modeloVeiculoId > 0) {
            $stmtConfigs = $pdo->prepare("
                SELECT id, ano_inicio, ano_fim, motorizacao, combustivel, versao
                FROM veiculos_configuracao
                WHERE ativo = 1
                  AND modelo_veiculo_id = :modelo_veiculo_id
                ORDER BY ano_inicio ASC, versao ASC
            ");
            $stmtConfigs->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
            $stmtConfigs->execute();
            foreach ($stmtConfigs->fetchAll(PDO::FETCH_ASSOC) as $config) {
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
        }
    } catch (Throwable $e) {
        $filtrosVeicularesDisponiveis = false;
        $marcaVeiculoId = 0;
        $modeloVeiculoId = 0;
        $veiculoConfiguracaoId = 0;
        $aviso = 'Filtros de veiculo indisponiveis no momento. A consulta por pecas segue funcionando.';
        error_log('consulta_veiculo.php (filtros veiculares): ' . $e->getMessage());
    }

    $stmtTipos = $pdo->query("
        SELECT id, nome
        FROM tipos_peca
        WHERE ativo = 1
        ORDER BY nome ASC
    ");
    foreach ($stmtTipos->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $opcoesTiposPeca[(string)$linha['id']] = (string)$linha['nome'];
    }

    $stmtMarcasProduto = $pdo->query("
        SELECT id, nome
        FROM marcas_produto
        WHERE ativo = 1
        ORDER BY nome ASC
    ");
    foreach ($stmtMarcasProduto->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $opcoesMarcasProduto[(string)$linha['id']] = (string)$linha['nome'];
    }

    $where = ["p.ativo = 1"];
    $params = [];

    if ($tipoPecaId > 0) {
        $where[] = "p.tipo_peca_id = :tipo_peca_id";
        $params[':tipo_peca_id'] = [$tipoPecaId, PDO::PARAM_INT];
    }

    if ($marcaProdutoId > 0) {
        $where[] = "p.marca_produto_id = :marca_produto_id";
        $params[':marca_produto_id'] = [$marcaProdutoId, PDO::PARAM_INT];
    }

    $termos = preg_split('/\s+/', $termoBusca) ?: [];
    $i = 0;
    foreach ($termos as $termo) {
        $termo = trim($termo);
        if ($termo === '') {
            continue;
        }
        $i++;
        $param = ':termo_' . $i;
        $where[] = "(
            p.nome_comercial LIKE {$param}
            OR p.sku_interno LIKE {$param}
            OR p.codigo_fabricante LIKE {$param}
            OR p.codigo_barras LIKE {$param}
            OR tp.nome LIKE {$param}
            OR mp.nome LIKE {$param}
        )";
        $params[$param] = ['%' . $termo . '%', PDO::PARAM_STR];
    }

    if ($filtrosVeicularesDisponiveis && ($marcaVeiculoId > 0 || $modeloVeiculoId > 0 || $veiculoConfiguracaoId > 0)) {
        $filtroVeiculo = [];
        if ($marcaVeiculoId > 0) {
            $filtroVeiculo[] = "mv.id = :filtro_marca_veiculo_id";
            $params[':filtro_marca_veiculo_id'] = [$marcaVeiculoId, PDO::PARAM_INT];
        }
        if ($modeloVeiculoId > 0) {
            $filtroVeiculo[] = "mo.id = :filtro_modelo_veiculo_id";
            $params[':filtro_modelo_veiculo_id'] = [$modeloVeiculoId, PDO::PARAM_INT];
        }
        if ($veiculoConfiguracaoId > 0) {
            $filtroVeiculo[] = "vc.id = :filtro_configuracao_id";
            $params[':filtro_configuracao_id'] = [$veiculoConfiguracaoId, PDO::PARAM_INT];
        }

        $where[] = "EXISTS (
            SELECT 1
            FROM aplicacoes_peca ap
            INNER JOIN veiculos_configuracao vc ON vc.id = ap.veiculo_configuracao_id
            INNER JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
            INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
            WHERE ap.tipo_peca_id = p.tipo_peca_id
              AND " . implode(' AND ', $filtroVeiculo) . "
        )";
    }

    $whereSql = implode(' AND ', $where);

    $sqlTotal = "
        SELECT COUNT(*) AS total
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
        WHERE {$whereSql}
    ";
    $stmtTotal = $pdo->prepare($sqlTotal);
    foreach ($params as $chave => [$valor, $tipo]) {
        $stmtTotal->bindValue($chave, $valor, $tipo);
    }
    $stmtTotal->execute();
    $totalItens = (int)$stmtTotal->fetchColumn();

    $totalPaginas = max(1, (int)ceil($totalItens / $porPagina));
    if ($paginaAtual > $totalPaginas) {
        $paginaAtual = $totalPaginas;
    }
    $offset = ($paginaAtual - 1) * $porPagina;

    /*
    |--------------------------------------------------------------------------
    | Ordenacao atual: alfabetica
    | Futuro: usar campo de popularidade/favoritos (quando existir)
    |--------------------------------------------------------------------------
    */
    $sqlLista = "
        SELECT
            p.id,
            p.nome_comercial,
            p.sku_interno,
            p.codigo_fabricante,
            p.codigo_barras,
            p.preco,
            tp.nome AS tipo_peca_nome,
            mp.nome AS marca_produto_nome
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
        WHERE {$whereSql}
        ORDER BY p.nome_comercial ASC
        LIMIT :limite OFFSET :offset
    ";
    $stmtLista = $pdo->prepare($sqlLista);
    foreach ($params as $chave => [$valor, $tipo]) {
        $stmtLista->bindValue($chave, $valor, $tipo);
    }
    $stmtLista->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmtLista->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtLista->execute();
    $resultados = $stmtLista->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('consulta_veiculo.php: ' . $e->getMessage());

    try {
        $aviso = 'Consulta simplificada aplicada devido a limitacao temporaria no servidor.';

        $whereFallback = ["p.ativo = 1"];
        $paramsFallback = [];

        if ($tipoPecaId > 0) {
            $whereFallback[] = "p.tipo_peca_id = :tipo_peca_id";
            $paramsFallback[':tipo_peca_id'] = [$tipoPecaId, PDO::PARAM_INT];
        }
        if ($marcaProdutoId > 0) {
            $whereFallback[] = "p.marca_produto_id = :marca_produto_id";
            $paramsFallback[':marca_produto_id'] = [$marcaProdutoId, PDO::PARAM_INT];
        }

        $termosFallback = preg_split('/\s+/', $termoBusca) ?: [];
        $idxFallback = 0;
        foreach ($termosFallback as $termo) {
            $termo = trim($termo);
            if ($termo === '') {
                continue;
            }
            $idxFallback++;
            $param = ':f_termo_' . $idxFallback;
            $whereFallback[] = "(
                p.nome_comercial LIKE {$param}
                OR p.sku_interno LIKE {$param}
                OR p.codigo_fabricante LIKE {$param}
                OR p.codigo_barras LIKE {$param}
            )";
            $paramsFallback[$param] = ['%' . $termo . '%', PDO::PARAM_STR];
        }

        $whereSqlFallback = implode(' AND ', $whereFallback);

        $stmtTotalFallback = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM produtos p
            WHERE {$whereSqlFallback}
        ");
        foreach ($paramsFallback as $chave => [$valor, $tipo]) {
            $stmtTotalFallback->bindValue($chave, $valor, $tipo);
        }
        $stmtTotalFallback->execute();
        $totalItens = (int)$stmtTotalFallback->fetchColumn();

        $totalPaginas = max(1, (int)ceil($totalItens / $porPagina));
        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
        }
        $offset = ($paginaAtual - 1) * $porPagina;

        $stmtListaFallback = $pdo->prepare("
            SELECT
                p.id,
                p.nome_comercial,
                p.sku_interno,
                p.codigo_fabricante,
                p.codigo_barras,
                p.preco,
                tp.nome AS tipo_peca_nome,
                mp.nome AS marca_produto_nome
            FROM produtos p
            INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
            INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
            WHERE {$whereSqlFallback}
            ORDER BY p.nome_comercial ASC
            LIMIT :limite OFFSET :offset
        ");
        foreach ($paramsFallback as $chave => [$valor, $tipo]) {
            $stmtListaFallback->bindValue($chave, $valor, $tipo);
        }
        $stmtListaFallback->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $stmtListaFallback->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtListaFallback->execute();
        $resultados = $stmtListaFallback->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        error_log('consulta_veiculo.php fallback: ' . $e2->getMessage());
        $erro = 'Erro ao processar a consulta. Tente novamente em instantes.';
    }
}

$baseParams = [
    'q' => $termoBusca,
    'marca_veiculo_id' => $marcaVeiculoId > 0 ? (string)$marcaVeiculoId : '',
    'modelo_veiculo_id' => $modeloVeiculoId > 0 ? (string)$modeloVeiculoId : '',
    'veiculo_configuracao_id' => $veiculoConfiguracaoId > 0 ? (string)$veiculoConfiguracaoId : '',
    'tipo_peca_id' => $tipoPecaId > 0 ? (string)$tipoPecaId : '',
    'marca_produto_id' => $marcaProdutoId > 0 ? (string)$marcaProdutoId : '',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<div class="min-h-screen md:flex">
    <?php require __DIR__ . '/menu.php'; ?>

    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Consulta</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Lista padrao de todas as pecas com busca livre e filtros opcionais.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('consulta_veiculo.php', 'Limpar filtros', 'atalho') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>
            <?php if ($aviso !== ''): ?>
                <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    <?= esc($aviso) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label for="q" class="<?= classe_label() ?>">Buscar</label>
                        <?= input_texto('q', $termoBusca, [
                            'id' => 'q',
                            'placeholder' => 'Ex.: mola corsa, pastilha, amortecedor, SKF...'
                        ]) ?>
                    </div>

                    <div>
                        <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca do veiculo</label>
                        <?= select_padrao('marca_veiculo_id', $opcoesMarcasVeiculo, $baseParams['marca_veiculo_id'], ['id' => 'marca_veiculo_id']) ?>
                    </div>

                    <div>
                        <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo do veiculo</label>
                        <?= select_padrao('modelo_veiculo_id', $opcoesModelosVeiculo, $baseParams['modelo_veiculo_id'], ['id' => 'modelo_veiculo_id']) ?>
                    </div>

                    <div>
                        <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuracao</label>
                        <?= select_padrao('veiculo_configuracao_id', $opcoesConfiguracoes, $baseParams['veiculo_configuracao_id'], ['id' => 'veiculo_configuracao_id']) ?>
                    </div>

                    <div>
                        <label for="tipo_peca_id" class="<?= classe_label() ?>">Tipo de peca</label>
                        <?= select_padrao('tipo_peca_id', $opcoesTiposPeca, $baseParams['tipo_peca_id'], ['id' => 'tipo_peca_id']) ?>
                    </div>

                    <div>
                        <label for="marca_produto_id" class="<?= classe_label() ?>">Marca do produto</label>
                        <?= select_padrao('marca_produto_id', $opcoesMarcasProduto, $baseParams['marca_produto_id'], ['id' => 'marca_produto_id']) ?>
                    </div>

                    <div class="md:col-span-3 flex flex-col gap-2 sm:flex-row">
                        <?= botao_submit('Buscar', 'busca') ?>
                        <?= botao_link('consulta_veiculo.php', 'Limpar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="<?= classe_box() ?>">
                <div class="mb-4 border-b border-slate-200 pb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Resultados</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Exibindo <?= count($resultados) ?> de <?= $totalItens ?> itens.
                    </p>
                </div>

                <?php if (!$resultados): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">Nenhum item encontrado.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de peca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Codigos</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Preco</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $item): ?>
                                    <tr class="border-b border-slate-100 last:border-b-0">
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900"><?= esc($item['nome_comercial']) ?></div>
                                            <div class="text-xs text-slate-500">SKU: <?= esc($item['sku_interno']) ?></div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-700"><?= esc($item['tipo_peca_nome']) ?></td>
                                        <td class="px-4 py-4 text-sm text-slate-700"><?= esc($item['marca_produto_nome']) ?></td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <div>Fab.: <?= esc($item['codigo_fabricante'] ?: '—') ?></div>
                                            <div>Barras: <?= esc($item['codigo_barras'] ?: '—') ?></div>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                            R$ <?= number_format((float)$item['preco'], 2, ',', '.') ?>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link('ver_produto.php?id=' . (int)$item['id'], 'Ver', 'atalho') ?>
                                                <?= botao_link('ver_aplicacoes_produto.php?id=' . (int)$item['id'], 'Aplicacoes', 'busca') ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($totalPaginas > 1): ?>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-4">
                        <div class="text-sm text-slate-600">
                            Pagina <?= $paginaAtual ?> de <?= $totalPaginas ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php
                            $paramsAnterior = $baseParams;
                            $paramsAnterior['pagina'] = max(1, $paginaAtual - 1);
                            $paramsProxima = $baseParams;
                            $paramsProxima['pagina'] = min($totalPaginas, $paginaAtual + 1);
                            ?>
                            <?php if ($paginaAtual > 1): ?>
                                <?= botao_link('consulta_veiculo.php?' . montar_query_string($paramsAnterior), 'Anterior', 'cancelar') ?>
                            <?php endif; ?>
                            <?php if ($paginaAtual < $totalPaginas): ?>
                                <?= botao_link('consulta_veiculo.php?' . montar_query_string($paramsProxima), 'Proxima', 'busca') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
