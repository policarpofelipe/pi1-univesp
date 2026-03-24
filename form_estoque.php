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
$tituloPagina = $modoEdicao ? 'Editar Local de Estoque' : 'Novo Local de Estoque';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'nome_obrigatorio'        => 'Informe o nome do local de estoque.',
    'nome_maior_que_limite'   => 'O nome do local excede o limite permitido.',
    'nome_duplicado'          => 'Já existe um local de estoque cadastrado com este nome.',
    'registro_nao_encontrado' => 'Local de estoque não encontrado.',
    'erro_interno'            => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'         => 0,
    'nome'       => '',
    'localizacao'=> '',
    'ativo'      => '1',
];

if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            nome,
            localizacao,
            ativo
        FROM estoques
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $estoque = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estoque) {
        $erro = 'Local de estoque não encontrado.';
        $modoEdicao = false;
        $tituloPagina = 'Novo Local de Estoque';
    } else {
        $dados = [
            'id'          => (int)$estoque['id'],
            'nome'        => (string)($estoque['nome'] ?? ''),
            'localizacao' => (string)($estoque['localizacao'] ?? ''),
            'ativo'       => (string)($estoque['ativo'] ?? '1'),
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

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-600">
                        <?= $modoEdicao
                            ? 'Atualize os dados do local físico de armazenamento.'
                            : 'Cadastre um novo ponto físico de armazenamento de mercadorias.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_estoques.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_estoque.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="nome" class="<?= classe_label() ?>">Nome do local</label>
                            <?= input_texto('nome', $dados['nome'], [
                                'id' => 'nome',
                                'maxlength' => '100',
                                'required' => true,
                                'placeholder' => 'Ex.: Estoque principal, Loja, Almoxarifado, Depósito externo'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Use um nome claro, distinto e operacionalmente útil.
                            </p>
                        </div>

                        <div>
                            <label for="localizacao" class="<?= classe_label() ?>">Localização</label>
                            <?= textarea_padrao('localizacao', $dados['localizacao'], [
                                'id' => 'localizacao',
                                'rows' => '3',
                                'placeholder' => 'Ex.: Rua tal, fundos da loja, prateleira A3, sala 2, filial centro.'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Pode ser um endereço, setor, sala, corredor ou referência interna.
                            </p>
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
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar local', 'salvar') ?>
                        <?= botao_link('listar_estoques.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                mesmo que hoje exista apenas um estoque, manter essa entidade separada evita rigidez estrutural
                quando surgirem novos depósitos, prateleiras, filiais ou pontos de separação.
            </div>
        </div>
    </main>
</div>

</body>
</html>