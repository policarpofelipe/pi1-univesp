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

$id = (int)($_GET['id'] ?? 0);
$modoEdicao = $id > 0;
$tituloPagina = $modoEdicao ? 'Editar Aplicação do Produto' : 'Nova Aplicação do Produto';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';
$mapaErros = [
    'produto_obrigatorio'        => 'Selecione um produto.',
    'veiculo_obrigatorio'        => 'Selecione uma configuração veicular.',
    'duplicado'                  => 'Este produto já está vinculado a esta configuração veicular.',
    'registro_nao_encontrado'    => 'Aplicação não encontrada.',
    'erro_interno'               => 'Ocorreu um erro interno ao processar a operação.',
];
if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id' => 0,
    'produto_id' => '',
    'veiculo_configuracao_id' => '',
    'observacao' => '',
    'ativo' => '1',
];

$produtoIdFiltro = max(0, (int)($_GET['produto_id'] ?? 0));
$marcaVeiculoId = max(0, (int)($_GET['marca_veiculo_id'] ?? 0));
$modeloVeiculoId = max(0, (int)($_GET['modelo_veiculo_id'] ?? 0));

$sqlProdutos = "
    SELECT p.id, p.nome_comercial, p.sku_interno, p.codigo_fabricante, mp.nome AS marca_nome
    FROM produtos p
    INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
    WHERE p.ativo = 1
    ORDER BY p.nome_comercial ASC
";
$stmtProdutos = $pdo->query($sqlProdutos);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
$opcoesProdutos = ['' => 'Selecione um produto'];
foreach ($produtos as $produto) {
    $rotulo = trim((string)$produto['nome_comercial']) . ' | SKU: ' . trim((string)$produto['sku_interno']) . ' | Marca: ' . trim((string)$produto['marca_nome']);
    $opcoesProdutos[(string)$produto['id']] = $rotulo;
}

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

$opcoesModelos = ['' => 'Selecione um modelo'];
if ($marcaVeiculoId > 0) {
    $sqlModelos = "
        SELECT id, nome
        FROM modelos_veiculo
        WHERE ativo = 1
          AND marca_veiculo_id = :marca_veiculo_id
        ORDER BY nome ASC
    ";
    $stmtModelos = $pdo->prepare($sqlModelos);
    $stmtModelos->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtModelos->execute();
    foreach ($stmtModelos->fetchAll(PDO::FETCH_ASSOC) as $modelo) {
        $opcoesModelos[(string)$modelo['id']] = (string)$modelo['nome'];
    }
}

$opcoesVeiculos = ['' => 'Selecione uma configuração veicular'];
if ($modeloVeiculoId > 0) {
    $sqlVeiculos = "
        SELECT id, ano_inicio, ano_fim, motorizacao, combustivel, versao
        FROM veiculos_configuracao
        WHERE ativo = 1
          AND modelo_veiculo_id = :modelo_veiculo_id
        ORDER BY ano_inicio ASC, versao ASC
    ";
    $stmtVeiculos = $pdo->prepare($sqlVeiculos);
    $stmtVeiculos->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtVeiculos->execute();

    foreach ($stmtVeiculos->fetchAll(PDO::FETCH_ASSOC) as $veiculo) {
        $ano = ((int)$veiculo['ano_inicio'] === (int)$veiculo['ano_fim'])
            ? (string)$veiculo['ano_inicio']
            : $veiculo['ano_inicio'] . ' a ' . $veiculo['ano_fim'];
        $partes = [$ano];
        if (!empty($veiculo['motorizacao'])) {
            $partes[] = $veiculo['motorizacao'];
        }
        if (!empty($veiculo['combustivel'])) {
            $partes[] = $veiculo['combustivel'];
        }
        if (!empty($veiculo['versao'])) {
            $partes[] = $veiculo['versao'];
        }
        $opcoesVeiculos[(string)$veiculo['id']] = implode(' / ', $partes);
    }
}

if ($modoEdicao) {
    $sql = "
        SELECT id, produto_id, veiculo_configuracao_id, observacao, ativo
        FROM aplicacoes_produto
        WHERE id = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $aplicacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aplicacao) {
        $erro = 'Aplicação não encontrada.';
        $modoEdicao = false;
        $tituloPagina = 'Nova Aplicação do Produto';
    } else {
        $dados = [
            'id' => (int)$aplicacao['id'],
            'produto_id' => (string)($aplicacao['produto_id'] ?? ''),
            'veiculo_configuracao_id' => (string)($aplicacao['veiculo_configuracao_id'] ?? ''),
            'observacao' => (string)($aplicacao['observacao'] ?? ''),
            'ativo' => (string)($aplicacao['ativo'] ?? '1'),
        ];

        $sqlFiltro = "
            SELECT mo.id AS modelo_veiculo_id, mv.id AS marca_veiculo_id
            FROM veiculos_configuracao vc
            INNER JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
            INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
            WHERE vc.id = :veiculo_configuracao_id
            LIMIT 1
        ";
        $stmtFiltro = $pdo->prepare($sqlFiltro);
        $stmtFiltro->bindValue(':veiculo_configuracao_id', (int)$dados['veiculo_configuracao_id'], PDO::PARAM_INT);
        $stmtFiltro->execute();
        $filtro = $stmtFiltro->fetch(PDO::FETCH_ASSOC);
        if ($filtro) {
            $marcaVeiculoId = (int)$filtro['marca_veiculo_id'];
            $modeloVeiculoId = (int)$filtro['modelo_veiculo_id'];
        }
    }
}

if ($marcaVeiculoId > 0) {
    $sqlModelos = "
        SELECT id, nome
        FROM modelos_veiculo
        WHERE ativo = 1
          AND marca_veiculo_id = :marca_veiculo_id
        ORDER BY nome ASC
    ";
    $stmtModelos = $pdo->prepare($sqlModelos);
    $stmtModelos->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtModelos->execute();
    $opcoesModelos = ['' => 'Selecione um modelo'];
    foreach ($stmtModelos->fetchAll(PDO::FETCH_ASSOC) as $modelo) {
        $opcoesModelos[(string)$modelo['id']] = (string)$modelo['nome'];
    }
}

if ($modeloVeiculoId > 0) {
    $sqlVeiculos = "
        SELECT id, ano_inicio, ano_fim, motorizacao, combustivel, versao
        FROM veiculos_configuracao
        WHERE ativo = 1
          AND modelo_veiculo_id = :modelo_veiculo_id
        ORDER BY ano_inicio ASC, versao ASC
    ";
    $stmtVeiculos = $pdo->prepare($sqlVeiculos);
    $stmtVeiculos->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtVeiculos->execute();
    $opcoesVeiculos = ['' => 'Selecione uma configuração veicular'];
    foreach ($stmtVeiculos->fetchAll(PDO::FETCH_ASSOC) as $veiculo) {
        $ano = ((int)$veiculo['ano_inicio'] === (int)$veiculo['ano_fim'])
            ? (string)$veiculo['ano_inicio']
            : $veiculo['ano_inicio'] . ' a ' . $veiculo['ano_fim'];
        $partes = [$ano];
        if (!empty($veiculo['motorizacao'])) {
            $partes[] = $veiculo['motorizacao'];
        }
        if (!empty($veiculo['combustivel'])) {
            $partes[] = $veiculo['combustivel'];
        }
        if (!empty($veiculo['versao'])) {
            $partes[] = $veiculo['versao'];
        }
        $opcoesVeiculos[(string)$veiculo['id']] = implode(' / ', $partes);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($tituloPagina) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<div class="min-h-screen md:flex">
    <?php require __DIR__ . '/menu.php'; ?>
    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white shadow-lg">
                <div class="p-5 md:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Sistema de Controle de Estoque</p>
                            <h1 class="mt-2 text-2xl md:text-3xl font-bold"><?= esc($tituloPagina) ?></h1>
                            <p class="mt-2 text-sm text-slate-300">Vincule veículos compatíveis diretamente ao produto/peça.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?= botao_link('listar_aplicacoes_produto.php', 'Voltar para listagem', 'cancelar') ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <?php if ($modoEdicao): ?>
                        <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">
                    <?php endif; ?>
                    <div>
                        <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca do veículo</label>
                        <?= select_padrao('marca_veiculo_id', $opcoesMarcas, (string)$marcaVeiculoId, ['id' => 'marca_veiculo_id']) ?>
                    </div>
                    <div>
                        <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo do veículo</label>
                        <?= select_padrao('modelo_veiculo_id', $opcoesModelos, (string)$modeloVeiculoId, ['id' => 'modelo_veiculo_id']) ?>
                    </div>
                    <div class="flex items-end gap-2">
                        <div class="w-full"><?= botao_submit('Filtrar configurações', 'busca') ?></div>
                        <?= botao_link($modoEdicao ? 'form_aplicacao_produto.php?id=' . (int)$dados['id'] : 'form_aplicacao_produto.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="<?= classe_box() ?>">
                <form action="salvar_aplicacao_produto.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="produto_id" class="<?= classe_label() ?>">Produto/peça</label>
                            <?= select_padrao('produto_id', $opcoesProdutos, $dados['produto_id'], ['id' => 'produto_id', 'required' => true]) ?>
                        </div>
                        <div>
                            <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração veicular</label>
                            <?= select_padrao('veiculo_configuracao_id', $opcoesVeiculos, $dados['veiculo_configuracao_id'], ['id' => 'veiculo_configuracao_id', 'required' => true]) ?>
                        </div>
                        <div>
                            <label for="observacao" class="<?= classe_label() ?>">Observação</label>
                            <?= textarea_padrao('observacao', $dados['observacao'], ['id' => 'observacao', 'rows' => '3', 'maxlength' => '255']) ?>
                        </div>
                        <div>
                            <label for="ativo" class="<?= classe_label() ?>">Status</label>
                            <?= select_padrao('ativo', ['1' => 'Ativo', '0' => 'Inativo'], $dados['ativo'], ['id' => 'ativo']) ?>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar aplicação', 'salvar') ?>
                        <?= botao_link('listar_aplicacoes_produto.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
