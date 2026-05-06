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

function log_consulta_veiculo(string $etapa, string $mensagem, string $sql = '', array $params = []): void
{
    error_log('consulta_veiculo.php [' . $etapa . '] ' . json_encode([
        'mensagem' => $mensagem,
        'sql' => $sql,
        'params' => $params,
    ], JSON_UNESCAPED_UNICODE));
}

function bancoAtual(PDO $pdo): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $stmt = $pdo->query('SELECT DATABASE()');
    $cache = (string)$stmt->fetchColumn();
    return $cache;
}

function tabelaExiste(PDO $pdo, string $nomeTabela): bool
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM information_schema.tables
        WHERE table_schema = :schema
          AND table_name = :tabela
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':schema', bancoAtual($pdo), PDO::PARAM_STR);
        $stmt->bindValue(':tabela', $nomeTabela, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        log_consulta_veiculo('filtros', $e->getMessage(), $sql, ['tabela' => $nomeTabela]);
        return false;
    }
}

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM information_schema.columns
        WHERE table_schema = :schema
          AND table_name = :tabela
          AND column_name = :coluna
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':schema', bancoAtual($pdo), PDO::PARAM_STR);
        $stmt->bindValue(':tabela', $tabela, PDO::PARAM_STR);
        $stmt->bindValue(':coluna', $coluna, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        log_consulta_veiculo('filtros', $e->getMessage(), $sql, ['tabela' => $tabela, 'coluna' => $coluna]);
        return false;
    }
}

function schemaVeicularDisponivel(PDO $pdo): bool
{
    $tabelas = ['aplicacoes_peca', 'veiculos_configuracao', 'modelos_veiculo', 'marcas_veiculo'];
    foreach ($tabelas as $tabela) {
        if (!tabelaExiste($pdo, $tabela)) {
            return false;
        }
    }

    $colunas = [
        ['aplicacoes_peca', 'tipo_peca_id'],
        ['aplicacoes_peca', 'veiculo_configuracao_id'],
        ['veiculos_configuracao', 'modelo_veiculo_id'],
        ['modelos_veiculo', 'marca_veiculo_id'],
    ];
    foreach ($colunas as [$tabela, $coluna]) {
        if (!colunaExiste($pdo, $tabela, $coluna)) {
            return false;
        }
    }

    return true;
}

function carregarOpcoesFiltros(PDO $pdo, array $estado): array
{
    $opcoes = [
        'marcasVeiculo' => ['' => 'Todas as marcas'],
        'modelosVeiculo' => ['' => 'Todos os modelos'],
        'configuracoes' => ['' => 'Todas as configurações'],
        'tiposPeca' => ['' => 'Todos os tipos de peça'],
        'marcasProduto' => ['' => 'Todas as marcas de produto'],
    ];
    $avisos = [];

    $schemaVeicularOk = schemaVeicularDisponivel($pdo);
    if (!$schemaVeicularOk) {
        $avisos[] = 'Filtros veiculares indisponiveis (schema incompatível).';
    }

    $sqlTipos = "SELECT id, nome FROM tipos_peca WHERE ativo = 1 ORDER BY nome ASC";
    try {
        $stmt = $pdo->query($sqlTipos);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $opcoes['tiposPeca'][(string)$linha['id']] = (string)$linha['nome'];
        }
    } catch (PDOException $e) {
        log_consulta_veiculo('filtros', $e->getMessage(), $sqlTipos);
        $avisos[] = 'Nao foi possivel carregar tipos de peça.';
    }

    $sqlMarcasProduto = "SELECT id, nome FROM marcas_produto WHERE ativo = 1 ORDER BY nome ASC";
    try {
        $stmt = $pdo->query($sqlMarcasProduto);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $opcoes['marcasProduto'][(string)$linha['id']] = (string)$linha['nome'];
        }
    } catch (PDOException $e) {
        log_consulta_veiculo('filtros', $e->getMessage(), $sqlMarcasProduto);
        $avisos[] = 'Nao foi possivel carregar marcas de produto.';
    }

    if ($schemaVeicularOk) {
        $sqlMarcasVeiculo = "SELECT id, nome FROM marcas_veiculo WHERE ativo = 1 ORDER BY nome ASC";
        try {
            $stmtMarcas = $pdo->query($sqlMarcasVeiculo);
            foreach ($stmtMarcas->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $opcoes['marcasVeiculo'][(string)$linha['id']] = (string)$linha['nome'];
            }

            if ($estado['marca_veiculo_id'] > 0) {
                $sqlModelos = "
                    SELECT id, nome
                    FROM modelos_veiculo
                    WHERE ativo = 1
                      AND marca_veiculo_id = :marca_veiculo_id
                    ORDER BY nome ASC
                ";
                $stmtModelos = $pdo->prepare($sqlModelos);
                $stmtModelos->bindValue(':marca_veiculo_id', $estado['marca_veiculo_id'], PDO::PARAM_INT);
                $stmtModelos->execute();
                foreach ($stmtModelos->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                    $opcoes['modelosVeiculo'][(string)$linha['id']] = (string)$linha['nome'];
                }
            }

            if ($estado['modelo_veiculo_id'] > 0) {
                $sqlConfigs = "
                    SELECT id, ano_inicio, ano_fim, motorizacao, combustivel, versao
                    FROM veiculos_configuracao
                    WHERE ativo = 1
                      AND modelo_veiculo_id = :modelo_veiculo_id
                    ORDER BY ano_inicio ASC, versao ASC
                ";
                $stmtConfigs = $pdo->prepare($sqlConfigs);
                $stmtConfigs->bindValue(':modelo_veiculo_id', $estado['modelo_veiculo_id'], PDO::PARAM_INT);
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
                    $opcoes['configuracoes'][(string)$config['id']] = implode(' / ', $partes);
                }
            }
        } catch (PDOException $e) {
            log_consulta_veiculo('filtros', $e->getMessage(), $sqlMarcasVeiculo);
            $avisos[] = 'Filtros veiculares indisponiveis no momento.';
            $schemaVeicularOk = false;
        }
    }

    return [
        'opcoes' => $opcoes,
        'avisos' => $avisos,
        'schema_veicular_ok' => $schemaVeicularOk,
    ];
}

function montarFiltrosBase(array $entrada): array
{
    $where = ["p.ativo = 1"];
    $params = [];

    if ($entrada['tipo_peca_id'] > 0) {
        $where[] = "p.tipo_peca_id = :tipo_peca_id";
        $params[':tipo_peca_id'] = [$entrada['tipo_peca_id'], PDO::PARAM_INT];
    }
    if ($entrada['marca_produto_id'] > 0) {
        $where[] = "p.marca_produto_id = :marca_produto_id";
        $params[':marca_produto_id'] = [$entrada['marca_produto_id'], PDO::PARAM_INT];
    }

    return [$where, $params];
}

function mapearCampoBusca(string $campo): string
{
    $mapa = [
        'produto' => 'p.nome_comercial',
        'sku' => 'p.sku_interno',
        'codigo_fabricante' => 'p.codigo_fabricante',
        'codigo_barras' => 'p.codigo_barras',
        'tipo_peca' => 'tp.nome',
        'marca_produto' => 'mp.nome',
    ];
    return $mapa[$campo] ?? $mapa['produto'];
}

function montarFiltroBusca(string $termoBusca, string $campoBusca, array &$params, array &$where): void
{
    $termoBusca = trim($termoBusca);
    if ($termoBusca === '') {
        return;
    }

    $campoSql = mapearCampoBusca($campoBusca);
    $param = ':termo_busca';
    $where[] = "{$campoSql} LIKE {$param}";
    $params[$param] = ['%' . $termoBusca . '%', PDO::PARAM_STR];
}

function montarFiltroVeicular(array $entrada, array &$params, array &$where, bool $schemaVeicularOk): void
{
    if (!$schemaVeicularOk) {
        return;
    }
    if ($entrada['marca_veiculo_id'] <= 0 && $entrada['modelo_veiculo_id'] <= 0 && $entrada['veiculo_configuracao_id'] <= 0) {
        return;
    }

    $filtroVeiculo = [];
    if ($entrada['marca_veiculo_id'] > 0) {
        $filtroVeiculo[] = "mv.id = :filtro_marca_veiculo_id";
        $params[':filtro_marca_veiculo_id'] = [$entrada['marca_veiculo_id'], PDO::PARAM_INT];
    }
    if ($entrada['modelo_veiculo_id'] > 0) {
        $filtroVeiculo[] = "mo.id = :filtro_modelo_veiculo_id";
        $params[':filtro_modelo_veiculo_id'] = [$entrada['modelo_veiculo_id'], PDO::PARAM_INT];
    }
    if ($entrada['veiculo_configuracao_id'] > 0) {
        $filtroVeiculo[] = "vc.id = :filtro_configuracao_id";
        $params[':filtro_configuracao_id'] = [$entrada['veiculo_configuracao_id'], PDO::PARAM_INT];
    }

    /*
     * Pelo schema atual, aplicacoes_peca referencia tipo_peca_id (nao produto_id).
     * Portanto o filtro veicular necessariamente ocorre por tipo de peça.
     */
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

function buscarTotalProdutos(PDO $pdo, string $whereSql, array $params): int
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
        WHERE {$whereSql}
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $chave => [$valor, $tipo]) {
        $stmt->bindValue($chave, $valor, $tipo);
    }

    try {
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        log_consulta_veiculo('count', $e->getMessage(), $sql, $params);
        throw $e;
    }
}

function buscarListaProdutos(PDO $pdo, string $whereSql, array $params, int $limite, int $offset): array
{
    $limite = max(1, (int)$limite);
    $offset = max(0, (int)$offset);

    $sql = "
        SELECT
            p.id,
            p.nome_comercial,
            p.sku_interno,
            p.codigo_fabricante,
            p.codigo_barras,
            p.preco,
            pi.caminho_arquivo AS imagem_principal,
            tp.nome AS tipo_peca_nome,
            mp.nome AS marca_produto_nome,
            app_agg.marcas_veiculo AS marcas_veiculo_nome,
            app_agg.modelos_veiculo AS modelos_veiculo_nome
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
        LEFT JOIN produto_imagens pi
            ON pi.produto_id = p.id
           AND pi.principal = 1
        LEFT JOIN (
            SELECT
                ap.tipo_peca_id,
                GROUP_CONCAT(DISTINCT mv.nome ORDER BY mv.nome SEPARATOR ', ') AS marcas_veiculo,
                GROUP_CONCAT(DISTINCT mo.nome ORDER BY mo.nome SEPARATOR ', ') AS modelos_veiculo
            FROM aplicacoes_peca ap
            INNER JOIN veiculos_configuracao vc ON vc.id = ap.veiculo_configuracao_id
            INNER JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
            INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
            GROUP BY ap.tipo_peca_id
        ) app_agg ON app_agg.tipo_peca_id = p.tipo_peca_id
        WHERE {$whereSql}
        ORDER BY p.nome_comercial ASC
        LIMIT {$limite} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $chave => [$valor, $tipo]) {
        $stmt->bindValue($chave, $valor, $tipo);
    }

    try {
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_consulta_veiculo('lista', $e->getMessage(), $sql, $params);
        throw $e;
    }
}

$estado = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'campo_busca' => trim((string)($_GET['campo_busca'] ?? 'produto')),
    'marca_veiculo_id' => max(0, (int)($_GET['marca_veiculo_id'] ?? 0)),
    'modelo_veiculo_id' => max(0, (int)($_GET['modelo_veiculo_id'] ?? 0)),
    'veiculo_configuracao_id' => max(0, (int)($_GET['veiculo_configuracao_id'] ?? 0)),
    'tipo_peca_id' => max(0, (int)($_GET['tipo_peca_id'] ?? 0)),
    'marca_produto_id' => max(0, (int)($_GET['marca_produto_id'] ?? 0)),
    'pagina' => max(1, (int)($_GET['pagina'] ?? 1)),
];

$porPagina = 50;
$erro = '';
$avisos = [];
$resultados = [];
$totalItens = 0;
$totalPaginas = 1;

$filtros = carregarOpcoesFiltros($pdo, $estado);
$opcoes = $filtros['opcoes'];
$avisos = array_merge($avisos, $filtros['avisos']);
$schemaVeicularOk = (bool)$filtros['schema_veicular_ok'];
[$where, $params] = montarFiltrosBase($estado);
montarFiltroBusca($estado['q'], $estado['campo_busca'], $params, $where);
montarFiltroVeicular($estado, $params, $where, $schemaVeicularOk);
$whereSql = implode(' AND ', $where);

try {
    $totalItens = buscarTotalProdutos($pdo, $whereSql, $params);
} catch (PDOException $e) {
    $erro = 'Erro ao processar contagem da consulta.';
}

if ($erro === '') {
    $totalPaginas = max(1, (int)ceil($totalItens / $porPagina));
    if ($estado['pagina'] > $totalPaginas) {
        $estado['pagina'] = $totalPaginas;
    }
    $offset = ($estado['pagina'] - 1) * $porPagina;

    try {
        $resultados = buscarListaProdutos($pdo, $whereSql, $params, $porPagina, $offset);
    } catch (PDOException $e) {
        $erro = 'Erro ao carregar a lista da consulta.';
    }
}

$baseParams = [
    'q' => $estado['q'],
    'campo_busca' => $estado['campo_busca'],
    'marca_veiculo_id' => $estado['marca_veiculo_id'] > 0 ? (string)$estado['marca_veiculo_id'] : '',
    'modelo_veiculo_id' => $estado['modelo_veiculo_id'] > 0 ? (string)$estado['modelo_veiculo_id'] : '',
    'veiculo_configuracao_id' => $estado['veiculo_configuracao_id'] > 0 ? (string)$estado['veiculo_configuracao_id'] : '',
    'tipo_peca_id' => $estado['tipo_peca_id'] > 0 ? (string)$estado['tipo_peca_id'] : '',
    'marca_produto_id' => $estado['marca_produto_id'] > 0 ? (string)$estado['marca_produto_id'] : '',
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
                        Lista padrão de todas as peças com busca livre e filtros opcionais.
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
            <?php foreach ($avisos as $aviso): ?>
                <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    <?= esc($aviso) ?>
                </div>
            <?php endforeach; ?>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label for="q" class="<?= classe_label() ?>">Buscar</label>
                        <div class="flex gap-2">
                            <?= select_padrao('campo_busca', [
                                'produto' => 'Produto',
                                'sku' => 'SKU',
                                'codigo_fabricante' => 'Código fabricante',
                                'codigo_barras' => 'Código de barras',
                                'tipo_peca' => 'Tipo de peça',
                                'marca_produto' => 'Marca do produto',
                            ], $estado['campo_busca'], ['id' => 'campo_busca']) ?>
                            <?= input_texto('q', $estado['q'], [
                                'id' => 'q',
                                'placeholder' => 'Digite o termo...'
                            ]) ?>
                        </div>
                    </div>

                    <div>
                        <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca do veículo</label>
                        <?= select_padrao('marca_veiculo_id', $opcoes['marcasVeiculo'], $baseParams['marca_veiculo_id'], [
                            'id' => 'marca_veiculo_id',
                            'onchange' => "
                                this.form.modelo_veiculo_id.value = '';
                                this.form.veiculo_configuracao_id.value = '';
                                this.form.submit();
                            "
                        ]) ?>
                    </div>

                    <div>
                        <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo do veículo</label>
                        <?= select_padrao('modelo_veiculo_id', $opcoes['modelosVeiculo'], $baseParams['modelo_veiculo_id'], [
                            'id' => 'modelo_veiculo_id',
                            'onchange' => "
                                this.form.veiculo_configuracao_id.value = '';
                                this.form.submit();
                            "
                        ]) ?>
                    </div>

                    <div>
                        <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração</label>
                        <?= select_padrao('veiculo_configuracao_id', $opcoes['configuracoes'], $baseParams['veiculo_configuracao_id'], [
                            'id' => 'veiculo_configuracao_id',
                            'onchange' => 'this.form.submit()'
                        ]) ?>
                    </div>

                    <div>
                        <label for="tipo_peca_id" class="<?= classe_label() ?>">Tipo de peça</label>
                        <?= select_padrao('tipo_peca_id', $opcoes['tiposPeca'], $baseParams['tipo_peca_id'], [
                            'id' => 'tipo_peca_id',
                            'onchange' => 'this.form.submit()'
                        ]) ?>
                    </div>

                    <div>
                        <label for="marca_produto_id" class="<?= classe_label() ?>">Marca do produto</label>
                        <?= select_padrao('marca_produto_id', $opcoes['marcasProduto'], $baseParams['marca_produto_id'], [
                            'id' => 'marca_produto_id',
                            'onchange' => 'this.form.submit()'
                        ]) ?>
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
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        <?php foreach ($resultados as $item): ?>
                            <article class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
                                <div class="h-44 bg-white">
                                    <?php if (!empty($item['imagem_principal'])): ?>
                                        <img
                                            src="<?= esc($item['imagem_principal']) ?>"
                                            alt="<?= esc($item['nome_comercial']) ?>"
                                            class="h-full w-full object-contain"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="flex h-full flex-col items-center justify-center text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h3l1.2 1.5h4.6L15 5h4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm4 0l10 10M10 13a3 3 0 1 1 4.2-4.2" />
                                            </svg>
                                            <span class="mt-2 text-sm font-medium">Foto em breve</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="space-y-3 px-4 py-4">
                                    <div>
                                        <div class="text-base font-semibold leading-tight text-slate-900"><?= esc($item['nome_comercial']) ?></div>
                                        <div class="mt-1 text-xs text-slate-500">SKU: <?= esc($item['sku_interno']) ?></div>
                                    </div>

                                    <div class="space-y-1 text-sm text-slate-700">
                                        <div><span class="font-medium">Tipo:</span> <?= esc($item['tipo_peca_nome']) ?></div>
                                        <div><span class="font-medium">Marca:</span> <?= esc($item['marca_produto_nome']) ?></div>
                                        <div><span class="font-medium">Fab. veículo:</span> <?= esc($item['marcas_veiculo_nome'] ?: '—') ?></div>
                                        <div><span class="font-medium">Modelo veículo:</span> <?= esc($item['modelos_veiculo_nome'] ?: '—') ?></div>
                                        <div><span class="font-medium">Cód. fabricante:</span> <?= esc($item['codigo_fabricante'] ?: '—') ?></div>
                                        <div><span class="font-medium">Cód. barras:</span> <?= esc($item['codigo_barras'] ?: '—') ?></div>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                        <div class="text-lg font-bold text-blue-700">
                                            R$ <?= number_format((float)$item['preco'], 2, ',', '.') ?>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-3">
                                        <?= botao_link('ver_produto.php?id=' . (int)$item['id'], 'Ver', 'atalho') ?>
                                        <?= botao_link('ver_aplicacoes_produto.php?id=' . (int)$item['id'], 'Aplicações', 'busca') ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($totalPaginas > 1): ?>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-4">
                        <div class="text-sm text-slate-600">
                            Página <?= (int)$estado['pagina'] ?> de <?= (int)$totalPaginas ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php
                            $paramsAnterior = $baseParams;
                            $paramsAnterior['pagina'] = max(1, (int)$estado['pagina'] - 1);
                            $paramsProxima = $baseParams;
                            $paramsProxima['pagina'] = min((int)$totalPaginas, (int)$estado['pagina'] + 1);
                            ?>
                            <?php if ((int)$estado['pagina'] > 1): ?>
                                <?= botao_link('consulta_veiculo.php?' . montar_query_string($paramsAnterior), 'Anterior', 'cancelar') ?>
                            <?php endif; ?>
                            <?php if ((int)$estado['pagina'] < (int)$totalPaginas): ?>
                                <?= botao_link('consulta_veiculo.php?' . montar_query_string($paramsProxima), 'Próxima', 'busca') ?>
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
