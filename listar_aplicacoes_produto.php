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
            'cadastrado' => ['classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'texto' => 'Aplicação do produto cadastrada com sucesso.'],
            'editado' => ['classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'texto' => 'Aplicação do produto atualizada com sucesso.'],
            'excluido' => ['classe' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'texto' => 'Aplicação do produto excluída com sucesso.'],
        ],
        'erro' => [
            'metodo_invalido' => ['classe' => 'border-red-200 bg-red-50 text-red-700', 'texto' => 'Método de requisição inválido.'],
            'id_invalido' => ['classe' => 'border-red-200 bg-red-50 text-red-700', 'texto' => 'ID inválido para a operação solicitada.'],
            'registro_nao_encontrado' => ['classe' => 'border-red-200 bg-red-50 text-red-700', 'texto' => 'Registro não encontrado.'],
            'erro_ao_excluir' => ['classe' => 'border-red-200 bg-red-50 text-red-700', 'texto' => 'Ocorreu um erro ao excluir a aplicação.'],
        ],
    ];

    if (!$tipo || !$codigo) {
        return null;
    }

    return $mapa[$tipo][$codigo] ?? null;
}

$busca = trim((string)($_GET['busca'] ?? ''));
$sucesso = trim((string)($_GET['sucesso'] ?? ''));
$erro = trim((string)($_GET['erro'] ?? ''));

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
        ap.ativo,
        ap.criado_em,
        ap.atualizado_em,
        p.id AS produto_id,
        p.nome_comercial,
        p.sku_interno,
        p.codigo_fabricante,
        tp.nome AS tipo_peca_nome,
        mp.nome AS marca_produto_nome,
        mv.nome AS marca_nome,
        mo.nome AS modelo_nome,
        vc.ano_inicio,
        vc.ano_fim,
        vc.motorizacao,
        vc.combustivel,
        vc.versao
    FROM aplicacoes_produto ap
    INNER JOIN produtos p ON p.id = ap.produto_id
    INNER JOIN tipos_peca tp ON tp.id = p.tipo_peca_id
    INNER JOIN marcas_produto mp ON mp.id = p.marca_produto_id
    INNER JOIN veiculos_configuracao vc ON vc.id = ap.veiculo_configuracao_id
    INNER JOIN modelos_veiculo mo ON mo.id = vc.modelo_veiculo_id
    INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
    WHERE 1 = 1
";

$params = [];
if ($busca !== '') {
    $sql .= " AND (
        p.nome_comercial LIKE :busca
        OR p.sku_interno LIKE :busca
        OR p.codigo_fabricante LIKE :busca
        OR tp.nome LIKE :busca
        OR mp.nome LIKE :busca
        OR mv.nome LIKE :busca
        OR mo.nome LIKE :busca
        OR vc.versao LIKE :busca
        OR ap.observacao LIKE :busca
    )";
    $params[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY p.nome_comercial ASC, mv.nome ASC, mo.nome ASC, vc.ano_inicio ASC";
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
    <title>Aplicações do Produto</title>
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
                            <h1 class="mt-2 text-2xl md:text-3xl font-bold">Aplicações do Produto</h1>
                            <p class="mt-2 text-sm text-slate-300">Gerencie a compatibilidade veicular por produto/peça específica.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?= botao_link('form_aplicacao_produto.php', 'Nova aplicação', 'salvar') ?>
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
                        <label for="busca" class="<?= classe_label() ?>">Buscar aplicação</label>
                        <?= input_texto('busca', $busca, ['id' => 'busca', 'placeholder' => 'Produto, SKU, tipo, marca, veículo, versão ou observação']) ?>
                    </div>
                    <div class="md:col-span-3 flex gap-2">
                        <div class="w-full"><?= botao_submit('Buscar', 'busca') ?></div>
                        <?= botao_link('listar_aplicacoes_produto.php', 'Limpar', 'cancelar', ['style' => 'width:100%; text-align:center;']) ?>
                    </div>
                </form>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="<?= classe_box() ?>">
                    <div class="text-sm text-slate-500">Total listado</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalAplicacoes ?></div>
                </div>
            </div>

            <div class="<?= classe_box() ?>">
                <?php if (!$aplicacoes): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-sm text-slate-600">Nenhuma aplicação do produto encontrada.</p>
                        <div class="mt-4"><?= botao_link('form_aplicacao_produto.php', 'Cadastrar primeira aplicação', 'salvar') ?></div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Classificação</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Veículo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ano</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Detalhes</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($aplicacoes as $aplicacao): ?>
                                <?php
                                $ano = ((int)$aplicacao['ano_inicio'] === (int)$aplicacao['ano_fim'])
                                    ? (string)$aplicacao['ano_inicio']
                                    : $aplicacao['ano_inicio'] . ' a ' . $aplicacao['ano_fim'];
                                $detalhes = [];
                                if (!empty($aplicacao['motorizacao'])) { $detalhes[] = $aplicacao['motorizacao']; }
                                if (!empty($aplicacao['combustivel'])) { $detalhes[] = $aplicacao['combustivel']; }
                                if (!empty($aplicacao['versao'])) { $detalhes[] = $aplicacao['versao']; }
                                ?>
                                <tr class="overflow-hidden rounded-xl bg-slate-50 shadow-sm">
                                    <td class="rounded-l-xl px-4 py-4">
                                        <div class="font-semibold text-slate-900"><?= esc($aplicacao['nome_comercial']) ?></div>
                                        <div class="mt-1 text-xs text-slate-500">SKU: <?= esc($aplicacao['sku_interno']) ?> · Cód.: <?= esc($aplicacao['codigo_fabricante']) ?></div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <?= esc($aplicacao['tipo_peca_nome']) ?><br>
                                        <span class="text-xs text-slate-500"><?= esc($aplicacao['marca_produto_nome']) ?></span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700"><?= esc($aplicacao['marca_nome'] . ' / ' . $aplicacao['modelo_nome']) ?></td>
                                    <td class="px-4 py-4 text-sm text-slate-700"><?= esc($ano) ?></td>
                                    <td class="px-4 py-4 text-sm text-slate-600">
                                        <?= esc($detalhes !== [] ? implode(' / ', $detalhes) : '—') ?>
                                        <?php if (!empty($aplicacao['observacao'])): ?>
                                            <div class="mt-1 text-xs text-slate-500"><?= esc($aplicacao['observacao']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm">
                                        <?= (int)$aplicacao['ativo'] === 1 ? '<span class="text-emerald-700">Ativo</span>' : '<span class="text-red-700">Inativo</span>' ?>
                                    </td>
                                    <td class="rounded-r-xl px-4 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <?= botao_link('form_aplicacao_produto.php?id=' . (int)$aplicacao['id'], 'Editar', 'editar') ?>
                                            <?= botao_excluir('excluir_aplicacao_produto.php?id=' . (int)$aplicacao['id'], 'Confirma a exclusão desta aplicação do produto?') ?>
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
