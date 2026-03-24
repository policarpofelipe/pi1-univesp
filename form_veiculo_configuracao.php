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
$tituloPagina = $modoEdicao ? 'Editar Configuração Veicular' : 'Nova Configuração Veicular';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'modelo_obrigatorio'        => 'Selecione um modelo de veículo.',
    'ano_inicio_obrigatorio'    => 'Informe o ano inicial.',
    'ano_fim_obrigatorio'       => 'Informe o ano final.',
    'ano_invalido'              => 'Informe anos válidos.',
    'ano_fim_menor'             => 'O ano final não pode ser menor que o ano inicial.',
    'duplicado'                 => 'Já existe uma configuração veicular com esses mesmos dados.',
    'registro_nao_encontrado'   => 'Configuração veicular não encontrada.',
    'erro_interno'              => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'                => 0,
    'modelo_veiculo_id' => '',
    'ano_inicio'        => '',
    'ano_fim'           => '',
    'motorizacao'       => '',
    'combustivel'       => '',
    'versao'            => '',
    'observacoes'       => '',
    'ativo'             => '1',
];

/*
|--------------------------------------------------------------------------
| Carregar modelos com marcas
|--------------------------------------------------------------------------
*/
$sqlModelos = "
    SELECT
        mo.id,
        mo.nome AS modelo_nome,
        mv.nome AS marca_nome
    FROM modelos_veiculo mo
    INNER JOIN marcas_veiculo mv
        ON mv.id = mo.marca_veiculo_id
    WHERE mo.ativo = 1
      AND mv.ativo = 1
    ORDER BY mv.nome ASC, mo.nome ASC
";
$stmtModelos = $pdo->query($sqlModelos);
$modelos = $stmtModelos->fetchAll(PDO::FETCH_ASSOC);

$opcoesModelos = ['' => 'Selecione um modelo'];
foreach ($modelos as $modelo) {
    $label = $modelo['marca_nome'] . ' - ' . $modelo['modelo_nome'];
    $opcoesModelos[(string)$modelo['id']] = $label;
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
            modelo_veiculo_id,
            ano_inicio,
            ano_fim,
            motorizacao,
            combustivel,
            versao,
            observacoes,
            ativo
        FROM veiculos_configuracao
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        $erro = 'Configuração veicular não encontrada.';
        $modoEdicao = false;
        $tituloPagina = 'Nova Configuração Veicular';
    } else {
        $dados = [
            'id'                => (int)$config['id'],
            'modelo_veiculo_id' => (string)($config['modelo_veiculo_id'] ?? ''),
            'ano_inicio'        => (string)($config['ano_inicio'] ?? ''),
            'ano_fim'           => (string)($config['ano_fim'] ?? ''),
            'motorizacao'       => (string)($config['motorizacao'] ?? ''),
            'combustivel'       => (string)($config['combustivel'] ?? ''),
            'versao'            => (string)($config['versao'] ?? ''),
            'observacoes'       => (string)($config['observacoes'] ?? ''),
            'ativo'             => (string)($config['ativo'] ?? '1'),
        ];
    }
}

$combustiveis = [
    ''         => 'Selecione o combustível',
    'Gasolina' => 'Gasolina',
    'Etanol'   => 'Etanol',
    'Flex'     => 'Flex',
    'Diesel'   => 'Diesel',
    'GNV'      => 'GNV',
    'Híbrido'  => 'Híbrido',
    'Elétrico' => 'Elétrico',
    'Outro'    => 'Outro',
];
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

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-600">
                        <?= $modoEdicao
                            ? 'Atualize os dados técnicos e comerciais da configuração veicular.'
                            : 'Cadastre uma configuração específica de veículo com ano, motor, combustível e versão.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_veiculos_configuracao.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_veiculo_configuracao.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo de veículo</label>
                            <?= select_padrao(
                                'modelo_veiculo_id',
                                $opcoesModelos,
                                $dados['modelo_veiculo_id'],
                                [
                                    'id' => 'modelo_veiculo_id',
                                    'required' => true
                                ]
                            ) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Selecione o modelo já vinculado à marca correspondente.
                            </p>
                        </div>

                        <div>
                            <label for="ano_inicio" class="<?= classe_label() ?>">Ano inicial</label>
                            <?= input_numero('ano_inicio', $dados['ano_inicio'], [
                                'id' => 'ano_inicio',
                                'min' => '1900',
                                'max' => '2100',
                                'step' => '1',
                                'required' => true,
                                'placeholder' => 'Ex.: 2001'
                            ]) ?>
                        </div>

                        <div>
                            <label for="ano_fim" class="<?= classe_label() ?>">Ano final</label>
                            <?= input_numero('ano_fim', $dados['ano_fim'], [
                                'id' => 'ano_fim',
                                'min' => '1900',
                                'max' => '2100',
                                'step' => '1',
                                'required' => true,
                                'placeholder' => 'Ex.: 2003'
                            ]) ?>
                        </div>

                        <div>
                            <label for="motorizacao" class="<?= classe_label() ?>">Motorização</label>
                            <?= input_texto('motorizacao', $dados['motorizacao'], [
                                'id' => 'motorizacao',
                                'maxlength' => '50',
                                'placeholder' => 'Ex.: 1.0, 1.6, 2.0 Turbo'
                            ]) ?>
                        </div>

                        <div>
                            <label for="combustivel" class="<?= classe_label() ?>">Combustível</label>
                            <?= select_padrao(
                                'combustivel',
                                $combustiveis,
                                $dados['combustivel'],
                                ['id' => 'combustivel']
                            ) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="versao" class="<?= classe_label() ?>">Versão</label>
                            <?= input_texto('versao', $dados['versao'], [
                                'id' => 'versao',
                                'maxlength' => '100',
                                'placeholder' => 'Ex.: Wind, GL, LX, EX'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                A versão ajuda a diferenciar configurações que o modelo sozinho não explica.
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="observacoes" class="<?= classe_label() ?>">Observações</label>
                            <?= textarea_padrao('observacoes', $dados['observacoes'], [
                                'id' => 'observacoes',
                                'rows' => '4',
                                'placeholder' => 'Informações complementares relevantes para identificação da configuração.'
                            ]) ?>
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
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar configuração', 'salvar') ?>
                        <?= botao_link('listar_veiculos_configuracao.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                esta é a camada realmente útil do catálogo veicular.  
                Marca e modelo sozinhos são abstratos demais; a compatibilidade de peças costuma depender de
                <em>ano</em>, <em>motorização</em>, <em>combustível</em> e <em>versão</em>.
            </div>
        </div>
    </main>
</div>

</body>
</html>