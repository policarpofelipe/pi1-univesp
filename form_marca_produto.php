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
$tituloPagina = $modoEdicao ? 'Editar Marca de Produto' : 'Nova Marca de Produto';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'nome_obrigatorio'        => 'Informe o nome da marca.',
    'nome_maior_que_limite'   => 'O nome da marca excede o limite permitido.',
    'nome_duplicado'          => 'Já existe uma marca cadastrada com este nome.',
    'registro_nao_encontrado' => 'Marca de produto não encontrada.',
    'erro_interno'            => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'    => 0,
    'nome'  => '',
    'ativo' => '1',
];

if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            nome,
            ativo
        FROM marcas_produto
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $marca = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$marca) {
        $erro = 'Marca de produto não encontrada.';
        $modoEdicao = false;
        $tituloPagina = 'Nova Marca de Produto';
    } else {
        $dados = [
            'id'    => (int)$marca['id'],
            'nome'  => (string)($marca['nome'] ?? ''),
            'ativo' => (string)($marca['ativo'] ?? '1'),
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
                            ? 'Atualize os dados da marca comercial.'
                            : 'Cadastre uma nova marca comercial para os produtos do catálogo.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_marcas_produto.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_marca_produto.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="nome" class="<?= classe_label() ?>">Nome da marca</label>
                            <?= input_texto('nome', $dados['nome'], [
                                'id' => 'nome',
                                'maxlength' => '100',
                                'required' => true,
                                'placeholder' => 'Ex.: Bosch, GM, Cofap, TRW, Nakata'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Informe a marca comercial vinculada ao produto vendável.
                            </p>
                        </div>

                        <div>
                            <label for="ativo" class="<?= classe_label() ?>">Status</label>
                            <?= select_padrao('ativo', [
                                '1' => 'Ativa',
                                '0' => 'Inativa',
                            ], $dados['ativo'], [
                                'id' => 'ativo'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar marca', 'salvar') ?>
                        <?= botao_link('listar_marcas_produto.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                marca de produto é a identidade comercial da peça vendida.
                Não confunda com <em>categoria</em>, <em>tipo de peça</em> ou <em>marca de veículo</em>.
            </div>
        </div>
    </main>
</div>

</body>
</html>