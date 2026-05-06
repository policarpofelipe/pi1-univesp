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

function assistente_obter_ou_criar(PDO $pdo, int $usuarioId, int $assistenteId = 0): array
{
    if ($assistenteId > 0) {
        $sql = "
            SELECT *
            FROM assistente_cadastro_produto
            WHERE id = :id
              AND usuario_id = :usuario_id
              AND status IN ('rascunho', 'em_andamento')
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $assistenteId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($registro) {
            if ((string)$registro['status'] === 'rascunho') {
                $up = $pdo->prepare("UPDATE assistente_cadastro_produto SET status = 'em_andamento', atualizado_em = NOW() WHERE id = :id");
                $up->bindValue(':id', (int)$registro['id'], PDO::PARAM_INT);
                $up->execute();
                $registro['status'] = 'em_andamento';
            }
            return $registro;
        }
    }

    $sqlUltimo = "
        SELECT *
        FROM assistente_cadastro_produto
        WHERE usuario_id = :usuario_id
          AND status IN ('rascunho', 'em_andamento')
        ORDER BY atualizado_em DESC, id DESC
        LIMIT 1
    ";
    $stmtUltimo = $pdo->prepare($sqlUltimo);
    $stmtUltimo->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmtUltimo->execute();
    $ultimo = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
    if ($ultimo) {
        if ((string)$ultimo['status'] === 'rascunho') {
            $up = $pdo->prepare("UPDATE assistente_cadastro_produto SET status = 'em_andamento', atualizado_em = NOW() WHERE id = :id");
            $up->bindValue(':id', (int)$ultimo['id'], PDO::PARAM_INT);
            $up->execute();
            $ultimo['status'] = 'em_andamento';
        }
        return $ultimo;
    }

    $dadosIniciais = json_encode([], JSON_UNESCAPED_UNICODE);
    $ins = $pdo->prepare("
        INSERT INTO assistente_cadastro_produto (
            usuario_id, produto_id, etapa_atual, dados_json, status, criado_em, atualizado_em
        ) VALUES (
            :usuario_id, NULL, 1, :dados_json, 'rascunho', NOW(), NOW()
        )
    ");
    $ins->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $ins->bindValue(':dados_json', $dadosIniciais, PDO::PARAM_STR);
    $ins->execute();
    $novoId = (int)$pdo->lastInsertId();

    $stmtNovo = $pdo->prepare("SELECT * FROM assistente_cadastro_produto WHERE id = :id LIMIT 1");
    $stmtNovo->bindValue(':id', $novoId, PDO::PARAM_INT);
    $stmtNovo->execute();
    $novo = $stmtNovo->fetch(PDO::FETCH_ASSOC) ?: [];
    if ($novo) {
        $up = $pdo->prepare("UPDATE assistente_cadastro_produto SET status = 'em_andamento', atualizado_em = NOW() WHERE id = :id");
        $up->bindValue(':id', $novoId, PDO::PARAM_INT);
        $up->execute();
        $novo['status'] = 'em_andamento';
    }
    return $novo;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
if ($usuarioId <= 0) {
    header('Location: login.php');
    exit;
}

$assistenteId = (int)($_GET['id'] ?? 0);
$assistente = assistente_obter_ou_criar($pdo, $usuarioId, $assistenteId);
if (!$assistente) {
    http_response_code(500);
    exit('Não foi possível inicializar o assistente.');
}

$assistenteIdAtual = (int)$assistente['id'];
$produtoIdAtual = (int)($assistente['produto_id'] ?? 0);
$etapaAtual = max(1, min(5, (int)($assistente['etapa_atual'] ?? 1)));

$dadosJson = [];
if (!empty($assistente['dados_json'])) {
    $dec = json_decode((string)$assistente['dados_json'], true);
    if (is_array($dec)) {
        $dadosJson = $dec;
    }
}

$sucesso = trim((string)($_GET['sucesso'] ?? ''));
$erro = trim((string)($_GET['erro'] ?? ''));
$mensagemSucesso = '';
$mensagemErro = '';
if ($sucesso === 'etapa1_salva') {
    $mensagemSucesso = 'Etapa 1 salva com sucesso.';
} elseif ($sucesso === 'aplicacao_adicionada') {
    $mensagemSucesso = 'Aplicação veicular adicionada com sucesso.';
} elseif ($sucesso === 'aplicacao_removida') {
    $mensagemSucesso = 'Aplicação veicular removida com sucesso.';
} elseif ($sucesso === 'etapa2_salva') {
    $mensagemSucesso = 'Etapa 2 salva com sucesso. Fluxo avançado para a etapa 3.';
} elseif ($sucesso === 'etapa2_pulada') {
    $mensagemSucesso = 'Etapa 2 foi pulada e a pendência de aplicabilidade foi registrada.';
} elseif ($sucesso === 'estoque_inicial_salvo') {
    $mensagemSucesso = 'Estoque inicial registrado com sucesso.';
} elseif ($sucesso === 'etapa3_salva') {
    $mensagemSucesso = 'Etapa 3 salva com sucesso. Fluxo avançado para a etapa 4.';
} elseif ($sucesso === 'etapa3_pulada') {
    $mensagemSucesso = 'Etapa 3 foi pulada e a pendência de estoque inicial foi registrada.';
}
if ($erro !== '') {
    $mapaErros = [
        'assistente_invalido' => 'Assistente inválido ou não encontrado.',
        'produto_nao_disponivel' => 'O assistente ainda não possui produto vinculado. Conclua a etapa 1.',
        'categoria_obrigatoria' => 'Selecione uma categoria existente ou informe uma nova.',
        'tipo_obrigatorio' => 'Selecione um tipo existente ou informe um novo.',
        'marca_obrigatoria' => 'Selecione uma marca existente ou informe uma nova.',
        'sku_obrigatorio' => 'Informe o SKU interno.',
        'codigo_fabricante_obrigatorio' => 'Informe o código do fabricante.',
        'nome_obrigatorio' => 'Informe o nome comercial.',
        'sku_duplicado' => 'Já existe um produto com este SKU.',
        'codigo_fabricante_duplicado' => 'Já existe produto com este código de fabricante para a marca selecionada.',
        'codigo_barras_duplicado' => 'Já existe produto com este código de barras.',
        'veiculo_obrigatorio' => 'Selecione uma configuração veicular válida.',
        'aplicacao_duplicada' => 'Esta configuração veicular já está vinculada a este produto.',
        'aplicacao_invalida' => 'Aplicação inválida para este assistente/produto.',
        'estoque_obrigatorio' => 'Selecione um local de estoque válido.',
        'quantidade_obrigatoria' => 'Informe a quantidade inicial.',
        'quantidade_invalida' => 'A quantidade inicial deve ser numérica e maior que zero.',
        'erro_interno' => 'Ocorreu um erro interno ao salvar a etapa.',
    ];
    $mensagemErro = $mapaErros[$erro] ?? 'Não foi possível processar a etapa.';
}

$produto = null;
if ($produtoIdAtual > 0) {
    $sqlProduto = "
        SELECT p.*, tp.categoria_peca_id
        FROM produtos p
        INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
        WHERE p.id = :id
        LIMIT 1
    ";
    $stmtProduto = $pdo->prepare($sqlProduto);
    $stmtProduto->bindValue(':id', $produtoIdAtual, PDO::PARAM_INT);
    $stmtProduto->execute();
    $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC) ?: null;
}

$valores = [
    'categoria_modo' => (string)($dadosJson['categoria_modo'] ?? 'existente'),
    'categoria_peca_id' => (string)($dadosJson['categoria_peca_id'] ?? ($produto['categoria_peca_id'] ?? '')),
    'nova_categoria_nome' => (string)($dadosJson['nova_categoria_nome'] ?? ''),
    'tipo_modo' => (string)($dadosJson['tipo_modo'] ?? 'existente'),
    'tipo_peca_id' => (string)($dadosJson['tipo_peca_id'] ?? ($produto['tipo_peca_id'] ?? '')),
    'novo_tipo_nome' => (string)($dadosJson['novo_tipo_nome'] ?? ''),
    'marca_modo' => (string)($dadosJson['marca_modo'] ?? 'existente'),
    'marca_produto_id' => (string)($dadosJson['marca_produto_id'] ?? ($produto['marca_produto_id'] ?? '')),
    'nova_marca_nome' => (string)($dadosJson['nova_marca_nome'] ?? ''),
    'sku_interno' => (string)($dadosJson['sku_interno'] ?? ($produto['sku_interno'] ?? '')),
    'codigo_fabricante' => (string)($dadosJson['codigo_fabricante'] ?? ($produto['codigo_fabricante'] ?? '')),
    'nome_comercial' => (string)($dadosJson['nome_comercial'] ?? ($produto['nome_comercial'] ?? '')),
    'codigo_barras' => (string)($dadosJson['codigo_barras'] ?? ($produto['codigo_barras'] ?? '')),
    'descricao' => (string)($dadosJson['descricao'] ?? ($produto['descricao'] ?? '')),
    'custo' => (string)($dadosJson['custo'] ?? (isset($produto['custo']) ? number_format((float)$produto['custo'], 2, '.', '') : '0.00')),
    'preco' => (string)($dadosJson['preco'] ?? (isset($produto['preco']) ? number_format((float)$produto['preco'], 2, '.', '') : '0.00')),
    'estoque_minimo' => (string)($dadosJson['estoque_minimo'] ?? ($produto['estoque_minimo'] ?? '0')),
];

$stmtCategorias = $pdo->query("SELECT id, nome FROM categorias_peca WHERE ativo = 1 ORDER BY nome ASC");
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
$opcoesCategorias = ['' => 'Selecione uma categoria'];
foreach ($categorias as $cat) {
    $opcoesCategorias[(string)$cat['id']] = (string)$cat['nome'];
}

$categoriaSelecionada = (int)$valores['categoria_peca_id'];
$opcoesTipos = ['' => 'Selecione um tipo'];
if ($categoriaSelecionada > 0) {
    $stmtTipos = $pdo->prepare("
        SELECT id, nome
        FROM tipos_peca
        WHERE ativo = 1
          AND categoria_peca_id = :categoria_id
        ORDER BY nome ASC
    ");
    $stmtTipos->bindValue(':categoria_id', $categoriaSelecionada, PDO::PARAM_INT);
    $stmtTipos->execute();
    foreach ($stmtTipos->fetchAll(PDO::FETCH_ASSOC) as $tipo) {
        $opcoesTipos[(string)$tipo['id']] = (string)$tipo['nome'];
    }
}

$stmtMarcas = $pdo->query("SELECT id, nome FROM marcas_produto WHERE ativo = 1 ORDER BY nome ASC");
$marcas = $stmtMarcas->fetchAll(PDO::FETCH_ASSOC);
$opcoesMarcas = ['' => 'Selecione uma marca'];
foreach ($marcas as $marca) {
    $opcoesMarcas[(string)$marca['id']] = (string)$marca['nome'];
}

$marcaVeiculoId = (int)($_GET['marca_veiculo_id'] ?? ($dadosJson['etapa_2']['marca_veiculo_id'] ?? 0));
$modeloVeiculoId = (int)($_GET['modelo_veiculo_id'] ?? ($dadosJson['etapa_2']['modelo_veiculo_id'] ?? 0));
$veiculoConfiguracaoId = (int)($_GET['veiculo_configuracao_id'] ?? 0);
$observacaoAplicacao = trim((string)($_GET['observacao'] ?? ''));

$opcoesMarcasVeiculo = ['' => 'Selecione uma marca'];
$stmtMarcasVeiculo = $pdo->query("SELECT id, nome FROM marcas_veiculo WHERE ativo = 1 ORDER BY nome ASC");
foreach ($stmtMarcasVeiculo->fetchAll(PDO::FETCH_ASSOC) as $mv) {
    $opcoesMarcasVeiculo[(string)$mv['id']] = (string)$mv['nome'];
}

$opcoesModelosVeiculo = ['' => 'Selecione um modelo'];
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
    foreach ($stmtModelos->fetchAll(PDO::FETCH_ASSOC) as $modelo) {
        $opcoesModelosVeiculo[(string)$modelo['id']] = (string)$modelo['nome'];
    }
}

$opcoesConfiguracoesVeiculo = ['' => 'Selecione uma configuração'];
if ($modeloVeiculoId > 0) {
    $stmtConfiguracoes = $pdo->prepare("
        SELECT id, ano_inicio, ano_fim, versao, motorizacao, combustivel
        FROM veiculos_configuracao
        WHERE ativo = 1
          AND modelo_veiculo_id = :modelo_veiculo_id
        ORDER BY ano_inicio ASC, versao ASC
    ");
    $stmtConfiguracoes->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtConfiguracoes->execute();
    foreach ($stmtConfiguracoes->fetchAll(PDO::FETCH_ASSOC) as $vc) {
        $ano = ((int)$vc['ano_inicio'] === (int)$vc['ano_fim'])
            ? (string)$vc['ano_inicio']
            : $vc['ano_inicio'] . ' a ' . $vc['ano_fim'];
        $partes = [$ano];
        if (!empty($vc['versao'])) {
            $partes[] = (string)$vc['versao'];
        }
        if (!empty($vc['motorizacao'])) {
            $partes[] = (string)$vc['motorizacao'];
        }
        if (!empty($vc['combustivel'])) {
            $partes[] = (string)$vc['combustivel'];
        }
        $opcoesConfiguracoesVeiculo[(string)$vc['id']] = implode(' / ', $partes);
    }
}

$aplicacoesProduto = [];
if ($produtoIdAtual > 0) {
    $stmtAplicacoes = $pdo->prepare("
        SELECT
            ap.id,
            ap.observacao,
            mv.nome AS marca_veiculo,
            mo.nome AS modelo_veiculo,
            vc.ano_inicio,
            vc.ano_fim,
            vc.versao,
            vc.motorizacao,
            vc.combustivel
        FROM aplicacoes_produto ap
        INNER JOIN veiculos_configuracao vc ON vc.id = ap.veiculo_configuracao_id
        INNER JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
        INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
        WHERE ap.produto_id = :produto_id
          AND ap.ativo = 1
        ORDER BY mv.nome ASC, mo.nome ASC, vc.ano_inicio ASC, vc.versao ASC
    ");
    $stmtAplicacoes->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
    $stmtAplicacoes->execute();
    $aplicacoesProduto = $stmtAplicacoes->fetchAll(PDO::FETCH_ASSOC);
}
$totalAplicacoes = count($aplicacoesProduto);
$temPendenciaAplicabilidade = !empty($dadosJson['pendencias']['produto_sem_aplicabilidade']) || $totalAplicacoes === 0;

$estoqueIdSelecionadoEtapa3 = (int)($_GET['estoque_id'] ?? ($dadosJson['etapa_3']['estoque_id'] ?? 0));
$quantidadeEtapa3 = (string)($_GET['quantidade_inicial'] ?? ($dadosJson['etapa_3']['quantidade_inicial'] ?? ''));
$observacaoEtapa3 = (string)($_GET['observacao_estoque'] ?? '');

$opcoesEstoques = ['' => 'Selecione um estoque/local'];
$stmtEstoques = $pdo->query("SELECT id, nome, localizacao FROM estoques WHERE ativo = 1 ORDER BY nome ASC");
foreach ($stmtEstoques->fetchAll(PDO::FETCH_ASSOC) as $estoque) {
    $nome = (string)$estoque['nome'];
    $local = trim((string)($estoque['localizacao'] ?? ''));
    $opcoesEstoques[(string)$estoque['id']] = $local !== '' ? $nome . ' (' . $local . ')' : $nome;
}

$saldosProdutoEtapa3 = [];
$movimentacoesIniciaisAssistente = [];
if ($produtoIdAtual > 0) {
    $stmtSaldoEtapa3 = $pdo->prepare("
        SELECT
            e.nome AS estoque_nome,
            e.localizacao,
            COALESCE(SUM(me.quantidade), 0) AS saldo_atual
        FROM estoques e
        LEFT JOIN movimentacoes_estoque me
            ON me.estoque_id = e.id
           AND me.produto_id = :produto_id
        WHERE e.ativo = 1
        GROUP BY e.id, e.nome, e.localizacao
        HAVING COALESCE(SUM(me.quantidade), 0) <> 0
        ORDER BY e.nome ASC
    ");
    $stmtSaldoEtapa3->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
    $stmtSaldoEtapa3->execute();
    $saldosProdutoEtapa3 = $stmtSaldoEtapa3->fetchAll(PDO::FETCH_ASSOC);

    $prefixoAssistente = '[Assistente #' . $assistenteIdAtual . '] Estoque inicial%';
    $stmtMovIni = $pdo->prepare("
        SELECT
            me.id,
            e.nome AS estoque_nome,
            me.quantidade,
            me.observacao,
            me.criado_em
        FROM movimentacoes_estoque me
        INNER JOIN estoques e ON e.id = me.estoque_id
        WHERE me.produto_id = :produto_id
          AND me.tipo_movimento = 'entrada'
          AND me.observacao LIKE :prefixo
        ORDER BY me.criado_em DESC, me.id DESC
    ");
    $stmtMovIni->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
    $stmtMovIni->bindValue(':prefixo', $prefixoAssistente, PDO::PARAM_STR);
    $stmtMovIni->execute();
    $movimentacoesIniciaisAssistente = $stmtMovIni->fetchAll(PDO::FETCH_ASSOC);
}
$totalSaldosEtapa3 = count($saldosProdutoEtapa3);
$temPendenciaEstoqueInicial = !empty($dadosJson['pendencias']['produto_sem_estoque_inicial']) || $totalSaldosEtapa3 === 0;

$tituloEtapa = 'Etapa 1 de 5 — Identificação da peça';
$descricaoEtapa = 'Defina os dados estruturantes do produto para habilitar as próximas etapas.';
$progresso = 20;
if ($etapaAtual === 2) {
    $tituloEtapa = 'Etapa 2 de 5 — Aplicabilidade veicular';
    $descricaoEtapa = 'Informe em quais veículos esta peça/produto se aplica. A compatibilidade é vinculada ao produto específico, não ao tipo de peça.';
    $progresso = 40;
} elseif ($etapaAtual === 3) {
    $tituloEtapa = 'Etapa 3 de 5 — Estoque e localização inicial';
    $descricaoEtapa = 'Informe o saldo inicial e o local de estoque onde esta peça está armazenada. A localização atual usa o cadastro de estoques/localizações existentes do sistema.';
    $progresso = 60;
} elseif ($etapaAtual >= 4) {
    $tituloEtapa = 'Etapa 4 de 5 — Em preparação';
    $descricaoEtapa = 'A próxima etapa será disponibilizada na próxima fase do desenvolvimento.';
    $progresso = 80;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistente de Cadastro de Produto</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                            <h1 class="mt-2 text-2xl md:text-3xl font-bold">Assistente de Cadastro de Produto/Peça</h1>
                            <p class="mt-2 text-sm text-slate-300"><?= esc($tituloEtapa) ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?= botao_link('assistente_cadastro_produto_cancelar.php?id=' . $assistenteIdAtual, 'Cancelar assistente', 'cancelar') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-blue-600" style="width: <?= $progresso ?>%;"></div>
                </div>
                <div class="mt-2 text-sm text-slate-600"><?= esc($descricaoEtapa) ?> (Rascunho #<?= (int)$assistenteIdAtual ?>).</div>
            </div>

            <?php if ($mensagemSucesso !== ''): ?>
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <?= esc($mensagemSucesso) ?>
                </div>
            <?php endif; ?>
            <?php if ($mensagemErro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($mensagemErro) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="<?= classe_box() ?>">
                        <?php if ($etapaAtual === 2): ?>
                            <div class="rounded-xl border border-slate-200 p-4 mb-6">
                                <h2 class="text-base font-semibold text-slate-900">Etapa 2 de 5 — Aplicabilidade veicular</h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    Informe em quais veículos esta peça/produto se aplica. A compatibilidade é vinculada ao produto específico, não ao tipo de peça.
                                </p>
                                <?php if ($temPendenciaAplicabilidade): ?>
                                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                        Este produto ainda não possui veículos compatíveis cadastrados. Você pode continuar, mas esta pendência aparecerá na revisão final.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="GET" class="rounded-xl border border-slate-200 p-4 mb-4">
                                <input type="hidden" name="id" value="<?= (int)$assistenteIdAtual ?>">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div>
                                        <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca do veículo</label>
                                        <?= select_padrao('marca_veiculo_id', $opcoesMarcasVeiculo, (string)$marcaVeiculoId, ['id' => 'marca_veiculo_id']) ?>
                                    </div>
                                    <div>
                                        <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo do veículo</label>
                                        <?= select_padrao('modelo_veiculo_id', $opcoesModelosVeiculo, (string)$modeloVeiculoId, ['id' => 'modelo_veiculo_id']) ?>
                                    </div>
                                    <div class="flex items-end">
                                        <?= botao_submit('Filtrar configurações', 'busca') ?>
                                    </div>
                                </div>
                            </form>

                            <form action="assistente_cadastro_produto_salvar.php" method="POST" class="space-y-4">
                                <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                <input type="hidden" name="acao" value="adicionar_aplicacao">
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração veicular *</label>
                                            <?= select_padrao('veiculo_configuracao_id', $opcoesConfiguracoesVeiculo, (string)$veiculoConfiguracaoId, ['id' => 'veiculo_configuracao_id']) ?>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="observacao" class="<?= classe_label() ?>">Observação (opcional)</label>
                                            <?= textarea_padrao('observacao', $observacaoAplicacao, ['id' => 'observacao', 'rows' => '2']) ?>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <?= botao_submit('Adicionar aplicação', 'salvar') ?>
                                    </div>
                                </div>
                            </form>

                            <div class="rounded-xl border border-slate-200 p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-semibold text-slate-900">Aplicações já adicionadas</h3>
                                    <span class="text-sm text-slate-600"><?= $totalAplicacoes ?> aplicação(ões)</span>
                                </div>
                                <?php if ($totalAplicacoes <= 0): ?>
                                    <p class="mt-3 text-sm text-slate-600">Nenhuma aplicação vinculada até o momento.</p>
                                <?php else: ?>
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead>
                                            <tr class="text-left text-slate-600">
                                                <th class="px-2 py-2">Marca</th>
                                                <th class="px-2 py-2">Modelo</th>
                                                <th class="px-2 py-2">Ano</th>
                                                <th class="px-2 py-2">Versão</th>
                                                <th class="px-2 py-2">Motorização</th>
                                                <th class="px-2 py-2">Combustível</th>
                                                <th class="px-2 py-2">Observação</th>
                                                <th class="px-2 py-2">Ação</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                            <?php foreach ($aplicacoesProduto as $ap): ?>
                                                <?php $anoTexto = ((int)$ap['ano_inicio'] === (int)$ap['ano_fim']) ? (string)$ap['ano_inicio'] : $ap['ano_inicio'] . ' a ' . $ap['ano_fim']; ?>
                                                <tr>
                                                    <td class="px-2 py-2"><?= esc((string)$ap['marca_veiculo']) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$ap['modelo_veiculo']) ?></td>
                                                    <td class="px-2 py-2"><?= esc($anoTexto) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$ap['versao']) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$ap['motorizacao']) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$ap['combustivel']) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$ap['observacao']) ?></td>
                                                    <td class="px-2 py-2">
                                                        <form action="assistente_cadastro_produto_salvar.php" method="POST" onsubmit="return confirm('Remover esta aplicação?');">
                                                            <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                                            <input type="hidden" name="acao" value="remover_aplicacao">
                                                            <input type="hidden" name="aplicacao_id" value="<?= (int)$ap['id'] ?>">
                                                            <?= botao_submit('Remover', 'perigo') ?>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:flex-wrap">
                                <form action="assistente_cadastro_produto_salvar.php" method="POST">
                                    <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                    <input type="hidden" name="acao" value="voltar_etapa_1">
                                    <?= botao_submit('Voltar', 'cancelar') ?>
                                </form>
                                <form action="assistente_cadastro_produto_salvar.php" method="POST">
                                    <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                    <input type="hidden" name="acao" value="salvar_etapa_2">
                                    <?= botao_submit('Salvar e continuar', 'salvar') ?>
                                </form>
                                <form action="assistente_cadastro_produto_salvar.php" method="POST" onsubmit="return confirm('Pular a etapa de aplicabilidade?');">
                                    <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                    <input type="hidden" name="acao" value="pular_etapa_2">
                                    <?= botao_submit('Pular etapa', 'busca') ?>
                                </form>
                                <?= botao_link('assistente_cadastro_produto_cancelar.php?id=' . $assistenteIdAtual, 'Cancelar assistente', 'cancelar') ?>
                            </div>
                        <?php elseif ($etapaAtual === 3): ?>
                            <div class="rounded-xl border border-slate-200 p-4 mb-6">
                                <h2 class="text-base font-semibold text-slate-900">Etapa 3 de 5 — Estoque e localização inicial</h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    Informe o saldo inicial e o local de estoque onde esta peça está armazenada. A localização atual usa o cadastro de estoques/localizações existentes do sistema.
                                </p>
                                <?php if ($temPendenciaEstoqueInicial): ?>
                                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                        Este produto ainda não possui estoque/localização inicial cadastrada. Você pode continuar, mas esta pendência aparecerá na revisão final.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form action="assistente_cadastro_produto_salvar.php" method="POST" class="space-y-4">
                                <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                <input type="hidden" name="acao" value="salvar_estoque_inicial">
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label for="estoque_id" class="<?= classe_label() ?>">Estoque/local *</label>
                                            <?= select_padrao('estoque_id', $opcoesEstoques, (string)$estoqueIdSelecionadoEtapa3, ['id' => 'estoque_id']) ?>
                                        </div>
                                        <div>
                                            <label for="quantidade_inicial" class="<?= classe_label() ?>">Quantidade inicial *</label>
                                            <?= input_texto('quantidade_inicial', $quantidadeEtapa3, ['id' => 'quantidade_inicial', 'type' => 'number', 'step' => '0.01', 'min' => '0.01', 'placeholder' => 'Ex.: 10']) ?>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="observacao_estoque" class="<?= classe_label() ?>">Observação (opcional)</label>
                                            <?= textarea_padrao('observacao_estoque', $observacaoEtapa3, ['id' => 'observacao_estoque', 'rows' => '2', 'placeholder' => 'Ex.: saldo inicial do cadastro']) ?>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <?= botao_submit('Salvar estoque inicial', 'salvar') ?>
                                    </div>
                                </div>
                            </form>

                            <div class="rounded-xl border border-slate-200 p-4 mb-4">
                                <h3 class="text-base font-semibold text-slate-900">Saldo atual por estoque</h3>
                                <?php if (!$saldosProdutoEtapa3): ?>
                                    <p class="mt-3 text-sm text-slate-600">Nenhum saldo encontrado para este produto.</p>
                                <?php else: ?>
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead>
                                            <tr class="text-left text-slate-600">
                                                <th class="px-2 py-2">Estoque</th>
                                                <th class="px-2 py-2">Localização</th>
                                                <th class="px-2 py-2">Saldo atual</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                            <?php foreach ($saldosProdutoEtapa3 as $saldo): ?>
                                                <tr>
                                                    <td class="px-2 py-2"><?= esc((string)$saldo['estoque_nome']) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)($saldo['localizacao'] ?? '')) ?></td>
                                                    <td class="px-2 py-2 font-semibold"><?= number_format((float)$saldo['saldo_atual'], 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4 mb-4">
                                <h3 class="text-base font-semibold text-slate-900">Movimentações iniciais registradas pelo assistente</h3>
                                <?php if (!$movimentacoesIniciaisAssistente): ?>
                                    <p class="mt-3 text-sm text-slate-600">Nenhuma movimentação inicial registrada por este assistente.</p>
                                <?php else: ?>
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead>
                                            <tr class="text-left text-slate-600">
                                                <th class="px-2 py-2">Estoque</th>
                                                <th class="px-2 py-2">Quantidade</th>
                                                <th class="px-2 py-2">Observação</th>
                                                <th class="px-2 py-2">Data</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                            <?php foreach ($movimentacoesIniciaisAssistente as $mov): ?>
                                                <tr>
                                                    <td class="px-2 py-2"><?= esc((string)$mov['estoque_nome']) ?></td>
                                                    <td class="px-2 py-2"><?= number_format((float)$mov['quantidade'], 2, ',', '.') ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$mov['observacao']) ?></td>
                                                    <td class="px-2 py-2"><?= esc((string)$mov['criado_em']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:flex-wrap">
                                <form action="assistente_cadastro_produto_salvar.php" method="POST">
                                    <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                    <input type="hidden" name="acao" value="voltar_etapa_2">
                                    <?= botao_submit('Voltar', 'cancelar') ?>
                                </form>
                                <form action="assistente_cadastro_produto_salvar.php" method="POST">
                                    <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                    <input type="hidden" name="acao" value="salvar_etapa_3">
                                    <?= botao_submit('Salvar e continuar', 'salvar') ?>
                                </form>
                                <form action="assistente_cadastro_produto_salvar.php" method="POST" onsubmit="return confirm('Pular a etapa de estoque inicial?');">
                                    <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                    <input type="hidden" name="acao" value="pular_etapa_3">
                                    <?= botao_submit('Pular etapa', 'busca') ?>
                                </form>
                                <?= botao_link('assistente_cadastro_produto_cancelar.php?id=' . $assistenteIdAtual, 'Cancelar assistente', 'cancelar') ?>
                            </div>
                        <?php elseif ($etapaAtual === 1): ?>
                            <form action="assistente_cadastro_produto_salvar.php" method="POST" class="space-y-6">
                                <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                <input type="hidden" name="acao" value="salvar_etapa_1">

                            <div class="rounded-xl border border-slate-200 p-4">
                                <h2 class="text-base font-semibold text-slate-900">Categoria da peça</h2>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="categoria_modo" value="existente" <?= $valores['categoria_modo'] !== 'nova' ? 'checked' : '' ?>>
                                        Selecionar existente
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="categoria_modo" value="nova" <?= $valores['categoria_modo'] === 'nova' ? 'checked' : '' ?>>
                                        Cadastrar nova
                                    </label>
                                </div>
                                <div class="mt-3">
                                    <label for="categoria_peca_id" class="<?= classe_label() ?>">Categoria existente</label>
                                    <?= select_padrao('categoria_peca_id', $opcoesCategorias, $valores['categoria_peca_id'], ['id' => 'categoria_peca_id']) ?>
                                </div>
                                <div class="mt-3">
                                    <label for="nova_categoria_nome" class="<?= classe_label() ?>">Nova categoria</label>
                                    <?= input_texto('nova_categoria_nome', $valores['nova_categoria_nome'], ['id' => 'nova_categoria_nome', 'maxlength' => '100', 'placeholder' => 'Ex.: Freios']) ?>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <h2 class="text-base font-semibold text-slate-900">Tipo de peça</h2>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="tipo_modo" value="existente" <?= $valores['tipo_modo'] !== 'novo' ? 'checked' : '' ?>>
                                        Selecionar existente
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="tipo_modo" value="novo" <?= $valores['tipo_modo'] === 'novo' ? 'checked' : '' ?>>
                                        Cadastrar novo
                                    </label>
                                </div>
                                <div class="mt-3">
                                    <label for="tipo_peca_id" class="<?= classe_label() ?>">Tipo existente (filtrado por categoria)</label>
                                    <?= select_padrao('tipo_peca_id', $opcoesTipos, $valores['tipo_peca_id'], ['id' => 'tipo_peca_id']) ?>
                                </div>
                                <div class="mt-3">
                                    <label for="novo_tipo_nome" class="<?= classe_label() ?>">Novo tipo</label>
                                    <?= input_texto('novo_tipo_nome', $valores['novo_tipo_nome'], ['id' => 'novo_tipo_nome', 'maxlength' => '150', 'placeholder' => 'Ex.: Pastilha dianteira']) ?>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <h2 class="text-base font-semibold text-slate-900">Marca do produto</h2>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="marca_modo" value="existente" <?= $valores['marca_modo'] !== 'nova' ? 'checked' : '' ?>>
                                        Selecionar existente
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" name="marca_modo" value="nova" <?= $valores['marca_modo'] === 'nova' ? 'checked' : '' ?>>
                                        Cadastrar nova
                                    </label>
                                </div>
                                <div class="mt-3">
                                    <label for="marca_produto_id" class="<?= classe_label() ?>">Marca existente</label>
                                    <?= select_padrao('marca_produto_id', $opcoesMarcas, $valores['marca_produto_id'], ['id' => 'marca_produto_id']) ?>
                                </div>
                                <div class="mt-3">
                                    <label for="nova_marca_nome" class="<?= classe_label() ?>">Nova marca</label>
                                    <?= input_texto('nova_marca_nome', $valores['nova_marca_nome'], ['id' => 'nova_marca_nome', 'maxlength' => '100', 'placeholder' => 'Ex.: Bosch']) ?>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4">
                                <h2 class="text-base font-semibold text-slate-900">Dados do produto</h2>
                                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="sku_interno" class="<?= classe_label() ?>">SKU interno *</label>
                                        <?= input_texto('sku_interno', $valores['sku_interno'], ['id' => 'sku_interno', 'maxlength' => '60', 'required' => true]) ?>
                                    </div>
                                    <div>
                                        <label for="codigo_fabricante" class="<?= classe_label() ?>">Código fabricante *</label>
                                        <?= input_texto('codigo_fabricante', $valores['codigo_fabricante'], ['id' => 'codigo_fabricante', 'maxlength' => '100', 'required' => true]) ?>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="nome_comercial" class="<?= classe_label() ?>">Nome comercial *</label>
                                        <?= input_texto('nome_comercial', $valores['nome_comercial'], ['id' => 'nome_comercial', 'maxlength' => '180', 'required' => true]) ?>
                                    </div>
                                    <div>
                                        <label for="codigo_barras" class="<?= classe_label() ?>">Código de barras</label>
                                        <?= input_texto('codigo_barras', $valores['codigo_barras'], ['id' => 'codigo_barras', 'maxlength' => '50']) ?>
                                    </div>
                                    <div>
                                        <label for="estoque_minimo" class="<?= classe_label() ?>">Estoque mínimo</label>
                                        <?= input_texto('estoque_minimo', $valores['estoque_minimo'], ['id' => 'estoque_minimo', 'type' => 'number', 'min' => '0', 'step' => '1']) ?>
                                    </div>
                                    <div>
                                        <label for="custo" class="<?= classe_label() ?>">Custo</label>
                                        <?= input_texto('custo', $valores['custo'], ['id' => 'custo', 'type' => 'number', 'step' => '0.01', 'min' => '0']) ?>
                                    </div>
                                    <div>
                                        <label for="preco" class="<?= classe_label() ?>">Preço</label>
                                        <?= input_texto('preco', $valores['preco'], ['id' => 'preco', 'type' => 'number', 'step' => '0.01', 'min' => '0']) ?>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="descricao" class="<?= classe_label() ?>">Descrição</label>
                                        <?= textarea_padrao('descricao', $valores['descricao'], ['id' => 'descricao', 'rows' => '3']) ?>
                                    </div>
                                </div>
                            </div>

                                <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                                    <?= botao_submit('Salvar e continuar', 'salvar') ?>
                                    <?= botao_link('assistente_cadastro_produto_cancelar.php?id=' . $assistenteIdAtual, 'Cancelar assistente', 'cancelar') ?>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <h2 class="text-base font-semibold text-slate-900">Próxima etapa em preparação</h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    Esta etapa será implementada na próxima fase. Você pode voltar para revisar os dados anteriores.
                                </p>
                                <div class="mt-4">
                                    <form action="assistente_cadastro_produto_salvar.php" method="POST">
                                        <input type="hidden" name="assistente_id" value="<?= (int)$assistenteIdAtual ?>">
                                        <input type="hidden" name="acao" value="voltar_etapa_2">
                                        <?= botao_submit('Voltar para etapa 2', 'cancelar') ?>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="space-y-4">
                    <div class="<?= classe_box() ?>">
                        <h3 class="text-base font-semibold text-slate-900">Resumo do rascunho</h3>
                        <div class="mt-3 space-y-2 text-sm text-slate-700">
                            <div><strong>ID do assistente:</strong> #<?= $assistenteIdAtual ?></div>
                            <div><strong>Status:</strong> <?= esc((string)$assistente['status']) ?></div>
                            <div><strong>Etapa atual:</strong> <?= $etapaAtual ?></div>
                            <div><strong>Produto vinculado:</strong> <?= $produtoIdAtual > 0 ? '#' . $produtoIdAtual : 'Ainda não criado' ?></div>
                            <div><strong>Última atualização:</strong> <?= esc((string)$assistente['atualizado_em']) ?></div>
                        </div>
                    </div>

                    <div class="<?= classe_box() ?>">
                        <h3 class="text-base font-semibold text-slate-900">Etapas do assistente</h3>
                        <ol class="mt-3 space-y-2 text-sm">
                            <li class="<?= $etapaAtual === 1 ? 'font-semibold text-blue-700' : 'text-slate-500' ?>">1. Identificação da peça</li>
                            <li class="<?= $etapaAtual === 2 ? 'font-semibold text-blue-700' : 'text-slate-500' ?>">2. Aplicabilidade veicular</li>
                            <li class="<?= $etapaAtual === 3 ? 'font-semibold text-blue-700' : 'text-slate-500' ?>">3. Estoque/localização</li>
                            <li class="<?= $etapaAtual === 4 ? 'font-semibold text-blue-700' : 'text-slate-500' ?>">4. Imagens (fase 4)</li>
                            <li class="text-slate-500">5. Revisão e conclusão (fase 5)</li>
                        </ol>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>
</body>
</html>
