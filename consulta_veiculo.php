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

$marcaVeiculoId = (int)($_GET['marca_veiculo_id'] ?? 0);
$modeloVeiculoId = (int)($_GET['modelo_veiculo_id'] ?? 0);
$veiculoConfiguracaoId = (int)($_GET['veiculo_configuracao_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Carregar marcas
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
| Carregar modelos conforme marca
|--------------------------------------------------------------------------
*/
$opcoesModelos = ['' => 'Selecione um modelo'];

if ($marcaVeiculoId > 0) {
    $sqlModelos = "
        SELECT id, nome
        FROM modelos_veiculo
        WHERE marca_veiculo_id = :marca_veiculo_id
          AND ativo = 1
        ORDER BY nome ASC
    ";
    $stmtModelos = $pdo->prepare($sqlModelos);
    $stmtModelos->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtModelos->execute();
    $modelos = $stmtModelos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modelos as $modelo) {
        $opcoesModelos[(string)$modelo['id']] = (string)$modelo['nome'];
    }
} else {
    $modeloVeiculoId = 0;
    $veiculoConfiguracaoId = 0;
}

/*
|--------------------------------------------------------------------------
| Carregar configurações conforme modelo
|--------------------------------------------------------------------------
*/
$opcoesConfiguracoes = ['' => 'Selecione uma configuração'];

if ($modeloVeiculoId > 0) {
    $sqlConfiguracoes = "
        SELECT
            id,
            ano_inicio,
            ano_fim,
            motorizacao,
            combustivel,
            versao
        FROM veiculos_configuracao
        WHERE modelo_veiculo_id = :modelo_veiculo_id
          AND ativo = 1
        ORDER BY ano_inicio ASC, versao ASC
    ";
    $stmtConfiguracoes = $pdo->prepare($sqlConfiguracoes);
    $stmtConfiguracoes->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtConfiguracoes->execute();
    $configuracoes = $stmtConfiguracoes->fetchAll(PDO::FETCH_ASSOC);

    foreach ($configuracoes as $config) {
        $ano = ((int)$config['ano_inicio'] === (int)$config['ano_fim'])
            ? (string)$config['ano_inicio']
            : $config['ano_inicio'] . ' a ' . $config['ano_fim'];

        $partes = [$ano];

        if (!empty($config['motorizacao'])) {
            $partes[] = $config['motorizacao'];
        }

        if (!empty($config['combustivel'])) {
            $partes[] = $config['combustivel'];
        }

        if (!empty($config['versao'])) {
            $partes[] = $config['versao'];
        }

        $opcoesConfiguracoes[(string)$config['id']] = implode(' / ', $partes);
    }
} else {
    $veiculoConfiguracaoId = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta por Veículo</title>

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
        <div class="mx-auto max-w-6xl">

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Consulta por Veículo</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Selecione a configuração veicular para localizar os tipos de peça e os produtos compatíveis.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                    <?= botao_link('listar_aplicacoes_peca.php', 'Ver aplicações', 'atalho') ?>
                </div>
            </div>

            <div class="<?= classe_box() ?> mb-6">
                <form method="GET" class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label for="marca_veiculo_id" class="<?= classe_label() ?>">Marca</label>
                        <?= select_padrao(
                            'marca_veiculo_id',
                            $opcoesMarcas,
                            $marcaVeiculoId > 0 ? (string)$marcaVeiculoId : '',
                            [
                                'id' => 'marca_veiculo_id',
                                'onchange' => 'this.form.submit()'
                            ]
                        ) ?>
                    </div>

                    <div>
                        <label for="modelo_veiculo_id" class="<?= classe_label() ?>">Modelo</label>
                        <?= select_padrao(
                            'modelo_veiculo_id',
                            $opcoesModelos,
                            $modeloVeiculoId > 0 ? (string)$modeloVeiculoId : '',
                            [
                                'id' => 'modelo_veiculo_id',
                                'onchange' => 'this.form.submit()'
                            ]
                        ) ?>
                    </div>

                    <div>
                        <label for="veiculo_configuracao_id" class="<?= classe_label() ?>">Configuração</label>
                        <?= select_padrao(
                            'veiculo_configuracao_id',
                            $opcoesConfiguracoes,
                            $veiculoConfiguracaoId > 0 ? (string)$veiculoConfiguracaoId : '',
                            [
                                'id' => 'veiculo_configuracao_id'
                            ]
                        ) ?>
                    </div>

                    <div class="md:col-span-3 flex flex-col gap-2 sm:flex-row">
                        <?= botao_submit('Consultar produtos compatíveis', 'busca') ?>
                        <?= botao_link('consulta_veiculo.php', 'Limpar seleção', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <?php if ($veiculoConfiguracaoId > 0): ?>
                <div class="<?= classe_box() ?>">
                    <div class="mb-4 border-b border-slate-200 pb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Resultado da consulta</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Produtos comerciais compatíveis com a configuração selecionada.
                        </p>
                    </div>

                    <?php include __DIR__ . '/buscar_produtos_por_veiculo.php'; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <p class="text-sm text-slate-600">
                        Selecione marca, modelo e configuração para visualizar os produtos compatíveis.
                    </p>
                </div>
            <?php endif; ?>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                esta tela materializa a finalidade principal do sistema: partir do veículo e chegar às peças compatíveis.
                Sem isso, o cadastro permanece apenas estrutural; com isso, ele se torna operacional.
            </div>
        </div>
    </main>
</div>

</body>
</html>