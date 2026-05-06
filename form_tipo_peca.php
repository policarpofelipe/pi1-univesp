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
$tituloPagina = $modoEdicao ? 'Editar Tipo de Peça' : 'Novo Tipo de Peça';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'categoria_obrigatoria'   => 'Selecione uma categoria.',
    'nome_obrigatorio'        => 'Informe o nome do tipo de peça.',
    'nome_maior_que_limite'   => 'O nome do tipo excede o limite permitido.',
    'nome_duplicado'          => 'Já existe um tipo de peça com este nome nesta categoria.',
    'registro_nao_encontrado' => 'Tipo de peça não encontrado.',
    'erro_interno'            => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'                => 0,
    'categoria_peca_id' => '',
    'nome'              => '',
    'descricao'         => '',
    'ativo'             => '1',
];

/*
|--------------------------------------------------------------------------
| Carregar categorias ativas
|--------------------------------------------------------------------------
*/
$sqlCategorias = "
    SELECT id, nome
    FROM categorias_peca
    WHERE ativo = 1
    ORDER BY nome ASC
";
$stmtCategorias = $pdo->query($sqlCategorias);
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

$opcoesCategorias = ['' => 'Selecione uma categoria'];
foreach ($categorias as $categoria) {
    $opcoesCategorias[(string)$categoria['id']] = (string)$categoria['nome'];
}

/*
|--------------------------------------------------------------------------
| Carregar registro para edição
|--------------------------------------------------------------------------
*/
if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            categoria_peca_id,
            nome,
            descricao,
            ativo
        FROM tipos_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tipo) {
        $erro = 'Tipo de peça não encontrado.';
        $modoEdicao = false;
        $tituloPagina = 'Novo Tipo de Peça';
    } else {
        $dados = [
            'id'                => (int)$tipo['id'],
            'categoria_peca_id' => (string)($tipo['categoria_peca_id'] ?? ''),
            'nome'              => (string)($tipo['nome'] ?? ''),
            'descricao'         => (string)($tipo['descricao'] ?? ''),
            'ativo'             => (string)($tipo['ativo'] ?? '1'),
        ];
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
        <div class="mx-auto max-w-4xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-300">
                        <?= $modoEdicao
                            ? 'Atualize os dados do tipo funcional da peça.'
                            : 'Cadastre um novo tipo funcional para o catálogo.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_tipos_peca.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_tipo_peca.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="categoria_peca_id" class="<?= classe_label() ?>">Categoria</label>
                            <?= select_padrao(
                                'categoria_peca_id',
                                $opcoesCategorias,
                                $dados['categoria_peca_id'],
                                ['id' => 'categoria_peca_id', 'required' => true]
                            ) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                O tipo de peça deve pertencer a uma categoria já cadastrada.
                            </p>
                        </div>

                        <div>
                            <label for="nome" class="<?= classe_label() ?>">Nome do tipo de peça</label>
                            <?= input_texto('nome', $dados['nome'], [
                                'id' => 'nome',
                                'maxlength' => '150',
                                'required' => true,
                                'placeholder' => 'Ex.: Pastilha de freio dianteira, Filtro de óleo, Amortecedor traseiro'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Aqui entra a função da peça, não a marca comercial.
                            </p>
                        </div>

                        <div>
                            <label for="descricao" class="<?= classe_label() ?>">Descrição</label>
                            <?= textarea_padrao('descricao', $dados['descricao'], [
                                'id' => 'descricao',
                                'rows' => '4',
                                'placeholder' => 'Descreva o tipo de peça e eventuais observações funcionais.'
                            ]) ?>
                        </div>

                        <div>
                            <label for="ativo" class="<?= classe_label() ?>">Status</label>
                            <?= select_padrao('ativo', [
                                '1' => 'Ativo',
                                '0' => 'Inativo',
                            ], $dados['ativo'], [
                                'id' => 'ativo'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar tipo de peça', 'salvar') ?>
                        <?= botao_link('listar_tipos_peca.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                não confunda <em>tipo de peça</em> com <em>produto</em>.  
                Exemplo: <em>Pastilha de freio dianteira</em> é um tipo;  
                <em>Pastilha Bosch código XYZ</em> é um produto comercial.
            </div>
        </div>
    </main>
</div>

</body>
</html>