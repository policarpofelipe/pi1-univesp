<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/componentes.php';
require __DIR__ . '/lib/produto_imagens.php';

date_default_timezone_set('America/Sao_Paulo');

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);

$modoEdicao = $id > 0;
$tituloPagina = $modoEdicao ? 'Editar Produto' : 'Novo Produto';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'tipo_obrigatorio'              => 'Selecione um tipo de peça.',
    'marca_obrigatoria'             => 'Selecione uma marca de produto.',
    'sku_obrigatorio'               => 'Informe o SKU interno.',
    'codigo_fabricante_obrigatorio' => 'Informe o código do fabricante.',
    'nome_obrigatorio'              => 'Informe o nome comercial do produto.',
    'sku_duplicado'                 => 'Já existe um produto cadastrado com este SKU interno.',
    'codigo_fabricante_duplicado'   => 'Já existe um produto com este código de fabricante para essa marca.',
    'codigo_barras_duplicado'       => 'Já existe um produto cadastrado com este código de barras.',
    'registro_nao_encontrado'       => 'Produto não encontrado.',
    'erro_interno'                  => 'Ocorreu um erro interno ao processar a operação.',
    'upload_invalido'               => 'Não foi possível processar o upload. Verifique formato e tamanho do arquivo.',
    'imagem_principal_invalida'     => 'Não foi possível definir a imagem principal.',
    'imagem_exclusao_falhou'        => 'Não foi possível excluir a imagem selecionada.',
];
$mapaSucesso = [
    'imagem_cadastrada'             => 'Imagem(ns) enviada(s) com sucesso.',
    'imagem_cadastrada_com_alerta'  => 'Algumas imagens foram salvas, porém houve arquivo(s) inválido(s).',
    'imagem_principal_definida'     => 'Imagem principal definida com sucesso.',
    'imagem_excluida'               => 'Imagem excluída com sucesso.',
    'cadastro_concluido_assistente' => 'Cadastro concluído pelo assistente com sucesso.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}
$sucessoCodigo = trim((string)($_GET['sucesso'] ?? ''));
$sucesso = '';
if ($sucessoCodigo !== '' && isset($mapaSucesso[$sucessoCodigo])) {
    $sucesso = $mapaSucesso[$sucessoCodigo];
}

$dados = [
    'id'                 => 0,
    'tipo_peca_id'       => '',
    'marca_produto_id'   => '',
    'sku_interno'        => '',
    'codigo_fabricante'  => '',
    'codigo_barras'      => '',
    'nome_comercial'     => '',
    'descricao'          => '',
    'custo'              => '0.00',
    'preco'              => '0.00',
    'estoque_minimo'     => '0',
    'ativo'              => '1',
];

/*
|--------------------------------------------------------------------------
| Carregar tipos de peça
|--------------------------------------------------------------------------
*/
$sqlTipos = "
    SELECT id, nome
    FROM tipos_peca
    WHERE ativo = 1
    ORDER BY nome ASC
";
$stmtTipos = $pdo->query($sqlTipos);
$tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

$opcoesTipos = ['' => 'Selecione um tipo de peça'];
foreach ($tipos as $tipo) {
    $opcoesTipos[(string)$tipo['id']] = (string)$tipo['nome'];
}

/*
|--------------------------------------------------------------------------
| Carregar marcas de produto
|--------------------------------------------------------------------------
*/
$sqlMarcas = "
    SELECT id, nome
    FROM marcas_produto
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
| Carregar produto para edição
|--------------------------------------------------------------------------
*/
if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            tipo_peca_id,
            marca_produto_id,
            sku_interno,
            codigo_fabricante,
            codigo_barras,
            nome_comercial,
            descricao,
            custo,
            preco,
            estoque_minimo,
            ativo
        FROM produtos
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $erro = 'Produto não encontrado.';
        $modoEdicao = false;
        $tituloPagina = 'Novo Produto';
    } else {
        $dados = [
            'id'                 => (int)$produto['id'],
            'tipo_peca_id'       => (string)($produto['tipo_peca_id'] ?? ''),
            'marca_produto_id'   => (string)($produto['marca_produto_id'] ?? ''),
            'sku_interno'        => (string)($produto['sku_interno'] ?? ''),
            'codigo_fabricante'  => (string)($produto['codigo_fabricante'] ?? ''),
            'codigo_barras'      => (string)($produto['codigo_barras'] ?? ''),
            'nome_comercial'     => (string)($produto['nome_comercial'] ?? ''),
            'descricao'          => (string)($produto['descricao'] ?? ''),
            'custo'              => number_format((float)($produto['custo'] ?? 0), 2, '.', ''),
            'preco'              => number_format((float)($produto['preco'] ?? 0), 2, '.', ''),
            'estoque_minimo'     => (string)($produto['estoque_minimo'] ?? '0'),
            'ativo'              => (string)($produto['ativo'] ?? '1'),
        ];
    }
}

$imagensProduto = [];
if ($modoEdicao && (int)$dados['id'] > 0) {
    $imagensProduto = listarImagensProduto($pdo, (int)$dados['id']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($tituloPagina) ?></title>

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
        <div class="mx-auto max-w-5xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-300">
                        <?= $modoEdicao
                            ? 'Atualize os dados comerciais e cadastrais do produto.'
                            : 'Cadastre um novo produto comercial no catálogo.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_produtos.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>
            <?php if ($sucesso !== ''): ?>
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <?= esc($sucesso) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_produto.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="tipo_peca_id" class="<?= classe_label() ?>">Tipo de peça</label>
                            <?= select_padrao('tipo_peca_id', $opcoesTipos, $dados['tipo_peca_id'], [
                                'id' => 'tipo_peca_id',
                                'required' => true,
                            ]) ?>
                        </div>

                        <div>
                            <label for="marca_produto_id" class="<?= classe_label() ?>">Marca do produto</label>
                            <?= select_padrao('marca_produto_id', $opcoesMarcas, $dados['marca_produto_id'], [
                                'id' => 'marca_produto_id',
                                'required' => true,
                            ]) ?>
                        </div>

                        <div>
                            <label for="sku_interno" class="<?= classe_label() ?>">SKU interno</label>
                            <?= input_texto('sku_interno', $dados['sku_interno'], [
                                'id' => 'sku_interno',
                                'maxlength' => '60',
                                'required' => true,
                                'placeholder' => 'Identificador interno do produto'
                            ]) ?>
                        </div>

                        <div>
                            <label for="codigo_fabricante" class="<?= classe_label() ?>">Código do fabricante</label>
                            <?= input_texto('codigo_fabricante', $dados['codigo_fabricante'], [
                                'id' => 'codigo_fabricante',
                                'maxlength' => '100',
                                'required' => true,
                                'placeholder' => 'Código técnico/comercial do fabricante'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="codigo_barras" class="<?= classe_label() ?>">Código de barras</label>
                            <?= input_texto('codigo_barras', $dados['codigo_barras'], [
                                'id' => 'codigo_barras',
                                'maxlength' => '50',
                                'placeholder' => 'EAN/GTIN, se houver'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="nome_comercial" class="<?= classe_label() ?>">Nome comercial</label>
                            <?= input_texto('nome_comercial', $dados['nome_comercial'], [
                                'id' => 'nome_comercial',
                                'maxlength' => '180',
                                'required' => true,
                                'placeholder' => 'Ex.: Pastilha de freio dianteira Bosch'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="descricao" class="<?= classe_label() ?>">Descrição</label>
                            <?= textarea_padrao('descricao', $dados['descricao'], [
                                'id' => 'descricao',
                                'rows' => '4',
                                'placeholder' => 'Observações complementares do produto'
                            ]) ?>
                        </div>

                        <div>
                            <label for="custo" class="<?= classe_label() ?>">Custo</label>
                            <?= input_numero('custo', $dados['custo'], [
                                'id' => 'custo',
                                'step' => '0.01',
                                'min' => '0',
                            ]) ?>
                        </div>

                        <div>
                            <label for="preco" class="<?= classe_label() ?>">Preço de venda</label>
                            <?= input_numero('preco', $dados['preco'], [
                                'id' => 'preco',
                                'step' => '0.01',
                                'min' => '0',
                            ]) ?>
                        </div>

                        <div>
                            <label for="estoque_minimo" class="<?= classe_label() ?>">Estoque mínimo</label>
                            <?= input_numero('estoque_minimo', $dados['estoque_minimo'], [
                                'id' => 'estoque_minimo',
                                'step' => '1',
                                'min' => '0',
                            ]) ?>
                        </div>

                        <div>
                            <label for="ativo" class="<?= classe_label() ?>">Status</label>
                            <?= select_padrao('ativo', [
                                '1' => 'Ativo',
                                '0' => 'Inativo',
                            ], $dados['ativo'], [
                                'id' => 'ativo',
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar produto', 'salvar') ?>
                        <?= botao_link('listar_produtos.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <?php if ($modoEdicao && (int)$dados['id'] > 0): ?>
                <div class="<?= classe_box() ?> mt-6">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Imagens do produto</h2>
                        <span class="text-sm text-slate-500"><?= count($imagensProduto) ?> imagem(ns)</span>
                    </div>

                    <form action="salvar_imagens_produto.php" method="POST" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="hidden" name="produto_id" value="<?= (int)$dados['id'] ?>">
                        <label for="imagens" class="<?= classe_label() ?>">Adicionar imagens (JPG, PNG, WEBP - até 5MB)</label>
                        <input
                            type="file"
                            name="imagens[]"
                            id="imagens"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            multiple
                            class="<?= classe_input() ?>"
                        >
                        <div class="mt-3">
                            <?= botao_submit('Enviar imagens', 'salvar') ?>
                        </div>
                    </form>

                    <?php if (!$imagensProduto): ?>
                        <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                            Nenhuma imagem cadastrada para este produto.
                        </div>
                    <?php else: ?>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <?php foreach ($imagensProduto as $imagem): ?>
                                <div class="rounded-xl border border-slate-200 bg-white p-3">
                                    <div class="aspect-video overflow-hidden rounded-lg bg-slate-100">
                                        <img
                                            src="<?= esc($imagem['caminho_arquivo']) ?>"
                                            alt="<?= esc($imagem['alt_text'] ?: $imagem['nome_original'] ?: $imagem['nome_arquivo']) ?>"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <div>
                                            <div class="text-sm font-medium text-slate-800"><?= esc($imagem['nome_arquivo']) ?></div>
                                            <div class="text-xs text-slate-500">
                                                Ordem: <?= (int)$imagem['ordem'] ?> · <?= (int)$imagem['tamanho_bytes'] ?> bytes
                                            </div>
                                        </div>
                                        <?php if ((int)$imagem['principal'] === 1): ?>
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Principal</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <?php if ((int)$imagem['principal'] !== 1): ?>
                                            <form action="definir_imagem_principal_produto.php" method="POST">
                                                <input type="hidden" name="produto_id" value="<?= (int)$dados['id'] ?>">
                                                <input type="hidden" name="imagem_id" value="<?= (int)$imagem['id'] ?>">
                                                <?= botao_submit('Definir principal', 'editar') ?>
                                            </form>
                                        <?php endif; ?>
                                        <form action="excluir_imagem_produto.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta imagem?');">
                                            <input type="hidden" name="produto_id" value="<?= (int)$dados['id'] ?>">
                                            <input type="hidden" name="imagem_id" value="<?= (int)$imagem['id'] ?>">
                                            <?= botao_submit('Excluir imagem', 'perigo') ?>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                    <strong class="text-slate-800">Imagens:</strong>
                    salve o produto primeiro para habilitar o upload de múltiplas imagens.
                </div>
            <?php endif; ?>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                o produto é o item comercial vendável.  
                Ele se diferencia do <em>tipo de peça</em>, que representa a função técnica, e da
                <em>marca do produto</em>, que representa a identidade comercial.
            </div>
        </div>
    </main>
</div>

</body>
</html>