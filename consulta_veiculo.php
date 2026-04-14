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

function montar_label_configuracao(array $config): string
{
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

    return implode(' / ', $partes);
}

function tamanho_texto(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto);
    }
    return strlen($texto);
}

$termoBusca = trim((string)($_GET['q'] ?? ''));
$marcaVeiculoId = (int)($_GET['marca_veiculo_id'] ?? 0);
$modeloVeiculoId = (int)($_GET['modelo_veiculo_id'] ?? 0);
$veiculoConfiguracaoId = (int)($_GET['veiculo_configuracao_id'] ?? 0);
$tipoPecaId = (int)($_GET['tipo_peca_id'] ?? 0);
$marcaProdutoId = (int)($_GET['marca_produto_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Se vier apenas configuração, inferir marca/modelo para filtros
|--------------------------------------------------------------------------
*/
if ($veiculoConfiguracaoId > 0) {
    $sqlInferencia = "
        SELECT vc.id, vc.modelo_veiculo_id, mo.marca_veiculo_id
        FROM veiculos_configuracao vc
        INNER JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
        WHERE vc.id = :id
        LIMIT 1
    ";
    $stmtInferencia = $pdo->prepare($sqlInferencia);
    $stmtInferencia->bindValue(':id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtInferencia->execute();
    $inferido = $stmtInferencia->fetch(PDO::FETCH_ASSOC);

    if ($inferido) {
        $modeloVeiculoId = (int)$inferido['modelo_veiculo_id'];
        $marcaVeiculoId = (int)$inferido['marca_veiculo_id'];
    } else {
        $veiculoConfiguracaoId = 0;
    }
}

/*
|--------------------------------------------------------------------------
| Dados para filtros avançados
|--------------------------------------------------------------------------
*/
$opcoesMarcas = ['' => 'Todas as marcas'];
$stmtMarcas = $pdo->query("SELECT id, nome FROM marcas_veiculo WHERE ativo = 1 ORDER BY nome ASC");
foreach ($stmtMarcas->fetchAll(PDO::FETCH_ASSOC) as $marca) {
    $opcoesMarcas[(string)$marca['id']] = (string)$marca['nome'];
}

$opcoesModelos = ['' => 'Todos os modelos'];
if ($marcaVeiculoId > 0) {
    $stmtModelos = $pdo->prepare("
        SELECT id, nome
        FROM modelos_veiculo
        WHERE marca_veiculo_id = :marca
          AND ativo = 1
        ORDER BY nome ASC
    ");
    $stmtModelos->bindValue(':marca', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtModelos->execute();
    $modelos = $stmtModelos->fetchAll(PDO::FETCH_ASSOC);
    $modeloValido = false;
    foreach ($modelos as $modelo) {
        $opcoesModelos[(string)$modelo['id']] = (string)$modelo['nome'];
        if ((int)$modelo['id'] === $modeloVeiculoId) {
            $modeloValido = true;
        }
    }
    if ($modeloVeiculoId > 0 && !$modeloValido) {
        $modeloVeiculoId = 0;
        $veiculoConfiguracaoId = 0;
    }
} else {
    $modeloVeiculoId = 0;
    $veiculoConfiguracaoId = 0;
}

$opcoesConfiguracoes = ['' => 'Todas as configurações'];
if ($modeloVeiculoId > 0) {
    $stmtConfiguracoes = $pdo->prepare("
        SELECT id, ano_inicio, ano_fim, motorizacao, combustivel, versao
        FROM veiculos_configuracao
        WHERE modelo_veiculo_id = :modelo
          AND ativo = 1
        ORDER BY ano_inicio ASC, versao ASC
    ");
    $stmtConfiguracoes->bindValue(':modelo', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtConfiguracoes->execute();
    $configs = $stmtConfiguracoes->fetchAll(PDO::FETCH_ASSOC);
    $configValida = false;
    foreach ($configs as $config) {
        $opcoesConfiguracoes[(string)$config['id']] = montar_label_configuracao($config);
        if ((int)$config['id'] === $veiculoConfiguracaoId) {
            $configValida = true;
        }
    }
    if ($veiculoConfiguracaoId > 0 && !$configValida) {
        $veiculoConfiguracaoId = 0;
    }
} else {
    $veiculoConfiguracaoId = 0;
}

$opcoesTiposPeca = ['' => 'Todos os tipos de peça'];
$stmtTipos = $pdo->query("SELECT id, nome FROM tipos_peca WHERE ativo = 1 ORDER BY nome ASC");
foreach ($stmtTipos->fetchAll(PDO::FETCH_ASSOC) as $tipo) {
    $opcoesTiposPeca[(string)$tipo['id']] = (string)$tipo['nome'];
}

$opcoesMarcasProduto = ['' => 'Todas as marcas de produto'];
$stmtMarcasProduto = $pdo->query("SELECT id, nome FROM marcas_produto WHERE ativo = 1 ORDER BY nome ASC");
foreach ($stmtMarcasProduto->fetchAll(PDO::FETCH_ASSOC) as $marcaProduto) {
    $opcoesMarcasProduto[(string)$marcaProduto['id']] = (string)$marcaProduto['nome'];
}

/*
|--------------------------------------------------------------------------
| API de sugestões (autocomplete)
|--------------------------------------------------------------------------
*/
if (($_GET['ajax'] ?? '') === 'sugestoes') {
    header('Content-Type: application/json; charset=utf-8');

    $q = trim((string)($_GET['q'] ?? ''));
    if (tamanho_texto($q) < 2) {
        echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sqlSugestoes = "
        SELECT DISTINCT
            p.id AS produto_id,
            p.nome_comercial,
            p.sku_interno,
            tp.nome AS tipo_peca_nome,
            mp.nome AS marca_produto_nome,
            mv.nome AS marca_veiculo_nome,
            mo.nome AS modelo_veiculo_nome
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
        LEFT JOIN aplicacoes_peca ap ON ap.tipo_peca_id = tp.id
        LEFT JOIN veiculos_configuracao vc ON vc.id = ap.veiculo_configuracao_id
        LEFT JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
        LEFT JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
        WHERE p.ativo = 1
    ";

    $paramsSugestoes = [];
    if ($marcaVeiculoId > 0) {
        $sqlSugestoes .= " AND mv.id = :marca_veiculo_id";
        $paramsSugestoes[':marca_veiculo_id'] = $marcaVeiculoId;
    }
    if ($modeloVeiculoId > 0) {
        $sqlSugestoes .= " AND mo.id = :modelo_veiculo_id";
        $paramsSugestoes[':modelo_veiculo_id'] = $modeloVeiculoId;
    }
    if ($veiculoConfiguracaoId > 0) {
        $sqlSugestoes .= " AND vc.id = :veiculo_configuracao_id";
        $paramsSugestoes[':veiculo_configuracao_id'] = $veiculoConfiguracaoId;
    }
    if ($tipoPecaId > 0) {
        $sqlSugestoes .= " AND tp.id = :tipo_peca_id";
        $paramsSugestoes[':tipo_peca_id'] = $tipoPecaId;
    }
    if ($marcaProdutoId > 0) {
        $sqlSugestoes .= " AND mp.id = :marca_produto_id";
        $paramsSugestoes[':marca_produto_id'] = $marcaProdutoId;
    }

    $termos = preg_split('/\s+/', $q) ?: [];
    $idx = 0;
    foreach ($termos as $termo) {
        $termo = trim($termo);
        if ($termo === '') {
            continue;
        }
        $idx++;
        $param = ':q' . $idx;
        $sqlSugestoes .= "
            AND (
                p.nome_comercial LIKE {$param}
                OR p.sku_interno LIKE {$param}
                OR p.codigo_fabricante LIKE {$param}
                OR p.codigo_barras LIKE {$param}
                OR tp.nome LIKE {$param}
                OR mp.nome LIKE {$param}
                OR mv.nome LIKE {$param}
                OR mo.nome LIKE {$param}
            )
        ";
        $paramsSugestoes[$param] = '%' . $termo . '%';
    }

    $sqlSugestoes .= " ORDER BY p.nome_comercial ASC LIMIT 8";

    try {
        $stmtSugestoes = $pdo->prepare($sqlSugestoes);
        foreach ($paramsSugestoes as $param => $valor) {
            $stmtSugestoes->bindValue($param, $valor);
        }
        $stmtSugestoes->execute();

        $items = [];
        foreach ($stmtSugestoes->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = [
                'texto' => trim($row['nome_comercial'] . ' [' . $row['sku_interno'] . ']'),
                'sub'   => trim($row['tipo_peca_nome'] . ' • ' . $row['marca_produto_nome'] . ' • ' . ($row['marca_veiculo_nome'] ?? 'Sem veículo')),
            ];
        }

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('Falha nas sugestoes consulta_veiculo.php: ' . $e->getMessage());
        echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| Busca principal (livre + filtros opcionais)
|--------------------------------------------------------------------------
*/
$filtroAtivo = (
    $termoBusca !== '' ||
    $marcaVeiculoId > 0 ||
    $modeloVeiculoId > 0 ||
    $veiculoConfiguracaoId > 0 ||
    $tipoPecaId > 0 ||
    $marcaProdutoId > 0
);

$resultados = [];
$erroBusca = '';

if ($filtroAtivo) {
    try {
    $sqlBusca = "
        SELECT DISTINCT
            p.id AS produto_id,
            p.nome_comercial,
            p.sku_interno,
            p.codigo_fabricante,
            p.codigo_barras,
            p.preco,
            tp.nome AS tipo_peca_nome,
            mp.nome AS marca_produto_nome,
            mv.nome AS marca_veiculo_nome,
            mo.nome AS modelo_veiculo_nome,
            vc.ano_inicio,
            vc.ano_fim,
            vc.motorizacao,
            vc.combustivel,
            vc.versao,
            ap.observacao AS aplicacao_observacao
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
        LEFT JOIN aplicacoes_peca ap ON ap.tipo_peca_id = tp.id
        LEFT JOIN veiculos_configuracao vc ON vc.id = ap.veiculo_configuracao_id
        LEFT JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
        LEFT JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
        WHERE p.ativo = 1
    ";

    $paramsBusca = [];
    if ($marcaVeiculoId > 0) {
        $sqlBusca .= " AND mv.id = :marca_veiculo_id";
        $paramsBusca[':marca_veiculo_id'] = $marcaVeiculoId;
    }
    if ($modeloVeiculoId > 0) {
        $sqlBusca .= " AND mo.id = :modelo_veiculo_id";
        $paramsBusca[':modelo_veiculo_id'] = $modeloVeiculoId;
    }
    if ($veiculoConfiguracaoId > 0) {
        $sqlBusca .= " AND vc.id = :veiculo_configuracao_id";
        $paramsBusca[':veiculo_configuracao_id'] = $veiculoConfiguracaoId;
    }
    if ($tipoPecaId > 0) {
        $sqlBusca .= " AND tp.id = :tipo_peca_id";
        $paramsBusca[':tipo_peca_id'] = $tipoPecaId;
    }
    if ($marcaProdutoId > 0) {
        $sqlBusca .= " AND mp.id = :marca_produto_id";
        $paramsBusca[':marca_produto_id'] = $marcaProdutoId;
    }

    $termos = preg_split('/\s+/', $termoBusca) ?: [];
    $idx = 0;
    foreach ($termos as $termo) {
        $termo = trim($termo);
        if ($termo === '') {
            continue;
        }
        $idx++;
        $param = ':t' . $idx;
        $sqlBusca .= "
            AND (
                p.nome_comercial LIKE {$param}
                OR p.sku_interno LIKE {$param}
                OR p.codigo_fabricante LIKE {$param}
                OR p.codigo_barras LIKE {$param}
                OR tp.nome LIKE {$param}
                OR mp.nome LIKE {$param}
                OR mv.nome LIKE {$param}
                OR mo.nome LIKE {$param}
                OR vc.motorizacao LIKE {$param}
                OR vc.combustivel LIKE {$param}
                OR vc.versao LIKE {$param}
                OR ap.observacao LIKE {$param}
            )
        ";
        $paramsBusca[$param] = '%' . $termo . '%';
    }

    $sqlBusca .= " ORDER BY p.nome_comercial ASC LIMIT 300";

    $stmtBusca = $pdo->prepare($sqlBusca);
    foreach ($paramsBusca as $param => $valor) {
        $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmtBusca->bindValue($param, $valor, $tipo);
    }
    $stmtBusca->execute();
    $resultados = $stmtBusca->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $erroBusca = 'Nao foi possivel aplicar todos os filtros. Exibindo resultado simplificado.';
        error_log('Falha na super busca consulta_veiculo.php: ' . $e->getMessage());

        $sqlFallback = "
            SELECT
                p.id AS produto_id,
                p.nome_comercial,
                p.sku_interno,
                p.codigo_fabricante,
                p.codigo_barras,
                p.preco,
                tp.nome AS tipo_peca_nome,
                mp.nome AS marca_produto_nome,
                NULL AS marca_veiculo_nome,
                NULL AS modelo_veiculo_nome,
                NULL AS ano_inicio,
                NULL AS ano_fim,
                NULL AS motorizacao,
                NULL AS combustivel,
                NULL AS versao,
                NULL AS aplicacao_observacao
            FROM produtos p
            INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
            INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
            WHERE p.ativo = 1
        ";

        $paramsFallback = [];
        if ($tipoPecaId > 0) {
            $sqlFallback .= " AND tp.id = :tipo_peca_id";
            $paramsFallback[':tipo_peca_id'] = $tipoPecaId;
        }
        if ($marcaProdutoId > 0) {
            $sqlFallback .= " AND mp.id = :marca_produto_id";
            $paramsFallback[':marca_produto_id'] = $marcaProdutoId;
        }

        $termosFallback = preg_split('/\s+/', $termoBusca) ?: [];
        $idxFallback = 0;
        foreach ($termosFallback as $termo) {
            $termo = trim($termo);
            if ($termo === '') {
                continue;
            }
            $idxFallback++;
            $param = ':f' . $idxFallback;
            $sqlFallback .= "
                AND (
                    p.nome_comercial LIKE {$param}
                    OR p.sku_interno LIKE {$param}
                    OR p.codigo_fabricante LIKE {$param}
                    OR p.codigo_barras LIKE {$param}
                    OR tp.nome LIKE {$param}
                    OR mp.nome LIKE {$param}
                )
            ";
            $paramsFallback[$param] = '%' . $termo . '%';
        }

        $sqlFallback .= " ORDER BY p.nome_comercial ASC LIMIT 300";
        $stmtFallback = $pdo->prepare($sqlFallback);
        foreach ($paramsFallback as $param => $valor) {
            $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmtFallback->bindValue($param, $valor, $tipo);
        }
        $stmtFallback->execute();
        $resultados = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta</title>

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
                    <h1 class="text-2xl font-bold text-slate-900">Consulta</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Digite qualquer termo para encontrar peças, produtos e aplicações. Os filtros avançados são opcionais.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('listar_aplicacoes_peca.php', 'Ver aplicações', 'atalho') ?>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6" x-data="{ advanced: <?= ($marcaVeiculoId > 0 || $modeloVeiculoId > 0 || $veiculoConfiguracaoId > 0 || $tipoPecaId > 0 || $marcaProdutoId > 0) ? 'true' : 'false' ?>, suggestions: [], showSuggestions: false, timer: null }">
                <form method="GET" class="space-y-5">
                    <div class="relative">
                        <label for="q" class="<?= classe_label() ?>">Super busca</label>
                        <?= input_texto('q', $termoBusca, [
                            'id' => 'q',
                            'placeholder' => 'Ex.: suspensão corsa, pastilha freio, amortecedor traseiro, SKF, GM...',
                            '@input' => "
                                clearTimeout(timer);
                                const term = \$event.target.value.trim();
                                if (term.length < 2) { suggestions = []; showSuggestions = false; return; }
                                timer = setTimeout(async () => {
                                    const params = new URLSearchParams(new FormData(\$event.target.form));
                                    params.set('ajax', 'sugestoes');
                                    const resp = await fetch('consulta_veiculo.php?' + params.toString());
                                    const data = await resp.json();
                                    suggestions = data.items || [];
                                    showSuggestions = suggestions.length > 0;
                                }, 220);
                            ",
                            '@focus' => 'showSuggestions = suggestions.length > 0',
                            '@keydown.escape' => 'showSuggestions = false'
                        ]) ?>

                        <div x-show="showSuggestions"
                             x-cloak
                             @click.outside="showSuggestions = false"
                             class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <template x-for="(item, idx) in suggestions" :key="idx">
                                <button type="button"
                                        class="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50"
                                        @click="
                                            document.getElementById('q').value = item.texto;
                                            showSuggestions = false;
                                        ">
                                    <div class="text-sm font-medium text-slate-800" x-text="item.texto"></div>
                                    <div class="text-xs text-slate-500" x-text="item.sub"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <?= botao_submit('Buscar', 'busca') ?>
                        <?= botao_link('consulta_veiculo.php', 'Limpar busca', 'cancelar') ?>
                        <button type="button"
                                class="<?= esc(classe_botao('atalho')) ?>"
                                @click="advanced = !advanced">
                            Filtros avançados
                        </button>
                    </div>

                    <div x-show="advanced" x-collapse class="grid grid-cols-1 gap-4 border-t border-slate-200 pt-5 md:grid-cols-3">
                        <div>
                            <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca do veículo</label>
                            <?= select_padrao('marca_veiculo_id', $opcoesMarcas, $marcaVeiculoId > 0 ? (string)$marcaVeiculoId : '', [
                                'id' => 'marca_veiculo_id',
                                'onchange' => 'this.form.submit()'
                            ]) ?>
                        </div>
                        <div>
                            <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo do veículo</label>
                            <?= select_padrao('modelo_veiculo_id', $opcoesModelos, $modeloVeiculoId > 0 ? (string)$modeloVeiculoId : '', [
                                'id' => 'modelo_veiculo_id',
                                'onchange' => 'this.form.submit()'
                            ]) ?>
                        </div>
                        <div>
                            <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração</label>
                            <?= select_padrao('veiculo_configuracao_id', $opcoesConfiguracoes, $veiculoConfiguracaoId > 0 ? (string)$veiculoConfiguracaoId : '', [
                                'id' => 'veiculo_configuracao_id'
                            ]) ?>
                        </div>
                        <div>
                            <label for="tipo_peca_id" class="<?= classe_label() ?>">Tipo de peça</label>
                            <?= select_padrao('tipo_peca_id', $opcoesTiposPeca, $tipoPecaId > 0 ? (string)$tipoPecaId : '', [
                                'id' => 'tipo_peca_id'
                            ]) ?>
                        </div>
                        <div>
                            <label for="marca_produto_id" class="<?= classe_label() ?>">Marca do produto</label>
                            <?= select_padrao('marca_produto_id', $opcoesMarcasProduto, $marcaProdutoId > 0 ? (string)$marcaProdutoId : '', [
                                'id' => 'marca_produto_id'
                            ]) ?>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (!$filtroAtivo): ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <p class="text-sm text-slate-600">
                        Comece digitando um termo na super busca. Se quiser, refine com filtros avançados.
                    </p>
                </div>
            <?php else: ?>
                <div class="<?= classe_box() ?>">
                    <div class="mb-4 border-b border-slate-200 pb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Resultados</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            <?= count($resultados) ?> item(ns) encontrado(s).
                        </p>
                    </div>
                    <?php if ($erroBusca !== ''): ?>
                        <div class="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            <?= esc($erroBusca) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$resultados): ?>
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <p class="text-sm text-slate-600">
                                Nenhum resultado encontrado para os critérios informados.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de peça</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Aplicação</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Códigos</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Preço</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($resultados as $item): ?>
                                    <?php
                                    $ano = '';
                                    if ($item['ano_inicio'] !== null && $item['ano_fim'] !== null) {
                                        $ano = ((int)$item['ano_inicio'] === (int)$item['ano_fim'])
                                            ? (string)$item['ano_inicio']
                                            : $item['ano_inicio'] . ' a ' . $item['ano_fim'];
                                    }
                                    $veiculoPartes = array_filter([
                                        $item['marca_veiculo_nome'],
                                        $item['modelo_veiculo_nome'],
                                        $ano,
                                        $item['motorizacao'],
                                        $item['combustivel'],
                                        $item['versao'],
                                    ]);
                                    ?>
                                    <tr class="border-b border-slate-100 last:border-b-0">
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-900"><?= esc($item['nome_comercial']) ?></div>
                                            <div class="text-xs text-slate-500"><?= esc($item['marca_produto_nome']) ?> • SKU: <?= esc($item['sku_interno']) ?></div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-700"><?= esc($item['tipo_peca_nome']) ?></td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <?php if ($veiculoPartes): ?>
                                                <?= esc(implode(' / ', $veiculoPartes)) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                            <?php if (!empty($item['aplicacao_observacao'])): ?>
                                                <div class="mt-1 text-xs text-slate-500"><?= esc($item['aplicacao_observacao']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            <div>Fab.: <?= esc($item['codigo_fabricante'] ?: '—') ?></div>
                                            <div>Barras: <?= esc($item['codigo_barras'] ?: '—') ?></div>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-800">
                                            R$ <?= number_format((float)$item['preco'], 2, ',', '.') ?>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?= botao_link('ver_produto.php?id=' . (int)$item['produto_id'], 'Ver', 'atalho') ?>
                                                <?= botao_link('ver_aplicacoes_produto.php?id=' . (int)$item['produto_id'], 'Aplicações', 'busca') ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>