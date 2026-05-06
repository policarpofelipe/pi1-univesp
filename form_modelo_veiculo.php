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
$tituloPagina = $modoEdicao ? 'Editar Modelo de Veículo' : 'Novo Modelo de Veículo';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'marca_obrigatoria'        => 'Selecione uma marca de veículo.',
    'nome_obrigatorio'         => 'Informe o nome do modelo.',
    'nome_maior_que_limite'    => 'O nome do modelo excede o limite permitido.',
    'nome_duplicado'           => 'Já existe um modelo com este nome para a marca selecionada.',
    'registro_nao_encontrado'  => 'Modelo de veículo não encontrado.',
    'erro_interno'             => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'               => 0,
    'marca_veiculo_id' => '',
    'nome'             => '',
    'ativo'            => '1',
];

/*
|--------------------------------------------------------------------------
| Carregar marcas de veículo
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Carregar modelo para edição
|--------------------------------------------------------------------------
*/
if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            marca_veiculo_id,
            nome,
            ativo
        FROM modelos_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$modelo) {
        $erro = 'Modelo de veículo não encontrado.';
        $modoEdicao = false;
        $tituloPagina = 'Novo Modelo de Veículo';
    } else {
        $dados = [
            'id'               => (int)$modelo['id'],
            'marca_veiculo_id' => (string)($modelo['marca_veiculo_id'] ?? ''),
            'nome'             => (string)($modelo['nome'] ?? ''),
            'ativo'            => (string)($modelo['ativo'] ?? '1'),
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
                            ? 'Atualize os dados do modelo veicular.'
                            : 'Cadastre um novo modelo vinculado a uma marca de veículo.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_modelos_veiculo.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_modelo_veiculo.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca de veículo</label>
                            <?= select_padrao(
                                'marca_veiculo_id',
                                $opcoesMarcas,
                                $dados['marca_veiculo_id'],
                                [
                                    'id' => 'marca_veiculo_id',
                                    'required' => true
                                ]
                            ) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                O modelo deve estar vinculado a uma marca previamente cadastrada.
                            </p>
                        </div>

                        <div>
                            <label for="nome" class="<?= classe_label() ?>">Nome do modelo</label>
                            <?= input_texto('nome', $dados['nome'], [
                                'id' => 'nome',
                                'maxlength' => '100',
                                'required' => true,
                                'placeholder' => 'Ex.: Corsa, Gol, Uno, Civic'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Informe apenas o modelo, não a versão, motorização ou ano.
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
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar modelo', 'salvar') ?>
                        <?= botao_link('listar_modelos_veiculo.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                o modelo representa a linha do veículo, como <em>Corsa</em> ou <em>Gol</em>.
                Ano, motorização, combustível e versão pertencem à etapa seguinte:
                <em>configuração veicular</em>.
            </div>
        </div>
    </main>
</div>

</body>
</html>