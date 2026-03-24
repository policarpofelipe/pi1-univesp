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
$tituloPagina = $modoEdicao ? 'Editar Aplicação de Peça' : 'Nova Aplicação de Peça';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'tipo_obrigatorio'              => 'Selecione um tipo de peça.',
    'veiculo_obrigatorio'           => 'Selecione uma configuração veicular.',
    'duplicado'                     => 'Esta aplicação já está cadastrada.',
    'registro_nao_encontrado'       => 'Aplicação não encontrada.',
    'erro_interno'                  => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'                     => 0,
    'tipo_peca_id'           => '',
    'veiculo_configuracao_id'=> '',
    'observacao'             => '',
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
| Carregar configurações veiculares
|--------------------------------------------------------------------------
*/
$sqlVeiculos = "
    SELECT
        vc.id,
        vc.ano_inicio,
        vc.ano_fim,
        vc.motorizacao,
        vc.combustivel,
        vc.versao,
        mo.nome AS modelo_nome,
        mv.nome AS marca_nome
    FROM veiculos_configuracao vc
    INNER JOIN modelos_veiculo mo
        ON mo.id = vc.modelo_veiculo_id
    INNER JOIN marcas_veiculo mv
        ON mv.id = mo.marca_veiculo_id
    WHERE vc.ativo = 1
      AND mo.ativo = 1
      AND mv.ativo = 1
    ORDER BY mv.nome ASC, mo.nome ASC, vc.ano_inicio ASC, vc.versao ASC
";
$stmtVeiculos = $pdo->query($sqlVeiculos);
$veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);

$opcoesVeiculos = ['' => 'Selecione uma configuração veicular'];
foreach ($veiculos as $veiculo) {
    $ano = ((int)$veiculo['ano_inicio'] === (int)$veiculo['ano_fim'])
        ? (string)$veiculo['ano_inicio']
        : $veiculo['ano_inicio'] . ' a ' . $veiculo['ano_fim'];

    $partes = [
        $veiculo['marca_nome'],
        $veiculo['modelo_nome'],
        $ano
    ];

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

/*
|--------------------------------------------------------------------------
| Carregar aplicação para edição
|--------------------------------------------------------------------------
*/
if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            tipo_peca_id,
            veiculo_configuracao_id,
            observacao
        FROM aplicacoes_peca
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
        $tituloPagina = 'Nova Aplicação de Peça';
    } else {
        $dados = [
            'id'                      => (int)$aplicacao['id'],
            'tipo_peca_id'            => (string)($aplicacao['tipo_peca_id'] ?? ''),
            'veiculo_configuracao_id' => (string)($aplicacao['veiculo_configuracao_id'] ?? ''),
            'observacao'              => (string)($aplicacao['observacao'] ?? ''),
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
        <div class="mx-auto max-w-5xl">

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-600">
                        <?= $modoEdicao
                            ? 'Atualize a compatibilidade entre o tipo de peça e a configuração veicular.'
                            : 'Cadastre uma nova compatibilidade entre tipo de peça e veículo.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_aplicacoes_peca.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_aplicacao_peca.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="tipo_peca_id" class="<?= classe_label() ?>">Tipo de peça</label>
                            <?= select_padrao(
                                'tipo_peca_id',
                                $opcoesTipos,
                                $dados['tipo_peca_id'],
                                [
                                    'id' => 'tipo_peca_id',
                                    'required' => true
                                ]
                            ) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Selecione a função técnica da peça.
                            </p>
                        </div>

                        <div>
                            <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração veicular</label>
                            <?= select_padrao(
                                'veiculo_configuracao_id',
                                $opcoesVeiculos,
                                $dados['veiculo_configuracao_id'],
                                [
                                    'id' => 'veiculo_configuracao_id',
                                    'required' => true
                                ]
                            ) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                Selecione a configuração exata do veículo compatível.
                            </p>
                        </div>

                        <div>
                            <label for="observacao" class="<?= classe_label() ?>">Observação</label>
                            <?= textarea_padrao('observacao', $dados['observacao'], [
                                'id' => 'observacao',
                                'rows' => '4',
                                'placeholder' => 'Informações complementares sobre a aplicação, restrições ou observações técnicas.'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar aplicação', 'salvar') ?>
                        <?= botao_link('listar_aplicacoes_peca.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                esta tela registra a relação mais importante do sistema:
                <em>qual tipo de peça serve em qual configuração veicular</em>.
                Sem isso, o sistema não consegue responder à pergunta operacional central.
            </div>
        </div>
    </main>
</div>

</body>
</html>