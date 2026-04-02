<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/componentes.php';

date_default_timezone_set('America/Sao_Paulo');

function contar(PDO $pdo, string $tabela): int
{
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$totais = [
    'produtos'        => contar($pdo, 'produtos'),
    'categorias'      => contar($pdo, 'categorias_peca'),
    'tipos'           => contar($pdo, 'tipos_peca'),
    'marcas_produto'  => contar($pdo, 'marcas_produto'),
    'marcas_veiculo'  => contar($pdo, 'marcas_veiculo'),
    'modelos'         => contar($pdo, 'modelos_veiculo'),
    'veiculos'        => contar($pdo, 'veiculos_configuracao'),
    'aplicacoes'      => contar($pdo, 'aplicacoes_peca'),
    'estoques'        => contar($pdo, 'estoques'),
    'usuarios'        => contar($pdo, 'usuarios')
];

$cards = [
    ['titulo' => 'Produtos', 'valor' => $totais['produtos'], 'link' => 'listar_produtos.php'],
    ['titulo' => 'Categorias de Peça', 'valor' => $totais['categorias'], 'link' => 'listar_categorias_peca.php'],
    ['titulo' => 'Tipos de Peça', 'valor' => $totais['tipos'], 'link' => 'listar_tipos_peca.php'],
    ['titulo' => 'Marcas de Produto', 'valor' => $totais['marcas_produto'], 'link' => 'listar_marcas_produto.php'],
    ['titulo' => 'Marcas de Veículo', 'valor' => $totais['marcas_veiculo'], 'link' => 'listar_marcas_veiculo.php'],
    ['titulo' => 'Modelos de Veículo', 'valor' => $totais['modelos'], 'link' => 'listar_modelos_veiculo.php'],
    ['titulo' => 'Configurações Veiculares', 'valor' => $totais['veiculos'], 'link' => 'listar_veiculos_configuracao.php'],
    ['titulo' => 'Aplicações de Peça', 'valor' => $totais['aplicacoes'], 'link' => 'listar_aplicacoes_peca.php'],
    ['titulo' => 'Locais de Estoque', 'valor' => $totais['estoques'], 'link' => 'listar_estoques.php'],
    ['titulo' => 'Usuários', 'valor' => $totais['usuarios'], 'link' => 'listar_usuarios.php'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>

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
        <div class="mx-auto max-w-7xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white shadow-lg">
                <div class="p-5 md:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">
                                Sistema de Controle de Estoque
                            </p>

                            <h1 class="mt-2 text-2xl md:text-3xl font-bold">
                                Painel do Sistema
                            </h1>

                            <p class="mt-2 text-sm text-slate-300">
                                Olá, <?= esc($_SESSION['usuario_nome'] ?? 'Usuário') ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                            <?= botao_link('consulta_veiculo.php', 'Consulta por veículo', 'atalho') ?>
                            <?= botao_link('saldo_estoque.php', 'Saldo de estoque', 'atalho') ?>
                            <?= botao_link('movimentar_entrada.php', 'Entrada', 'salvar') ?>
                            <?= botao_link('movimentar_saida.php', 'Saída', 'perigo') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200">
                    <div class="text-sm text-slate-500">Cadastros principais</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        <?= $totais['produtos'] + $totais['categorias'] + $totais['tipos'] + $totais['marcas_produto'] ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200">
                    <div class="text-sm text-slate-500">Catálogo veicular</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        <?= $totais['marcas_veiculo'] + $totais['modelos'] + $totais['veiculos'] ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200">
                    <div class="text-sm text-slate-500">Aplicações</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        <?= $totais['aplicacoes'] ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200">
                    <div class="text-sm text-slate-500">Estrutura operacional</div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        <?= $totais['estoques'] + $totais['usuarios'] ?>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h2 class="text-lg md:text-xl font-semibold text-slate-900">
                    Visão geral dos cadastros
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Toque em um cartão para abrir a área correspondente.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <?php foreach ($cards as $card): ?>
                    <a
                        href="<?= esc($card['link']) ?>"
                        class="group rounded-2xl bg-white p-4 shadow-sm border border-slate-200 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm text-slate-500 leading-5">
                                    <?= esc($card['titulo']) ?>
                                </div>

                                <div class="mt-2 text-2xl md:text-3xl font-bold text-slate-900">
                                    <?= (int)$card['valor'] ?>
                                </div>
                            </div>

                            <div class="shrink-0 rounded-xl bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 group-hover:bg-slate-900 group-hover:text-white transition">
                                Abrir
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">
                    Leitura estrutural
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Este painel já separa três camadas do sistema: catálogo de peças, catálogo veicular e operação de estoque.
                    Isso é importante, porque um sistema de autopeças não é apenas uma lista de produtos; ele precisa articular
                    identidade comercial, compatibilidade técnica e movimentação física.
                </p>
            </div>
        </div>
    </main>
</div>

</body>
</html>