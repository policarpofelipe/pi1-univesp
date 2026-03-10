<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

function contar(PDO $pdo, string $tabela): int
{
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$totais = [
    'produtos' => contar($pdo,'produtos'),
    'categorias' => contar($pdo,'categorias_peca'),
    'tipos' => contar($pdo,'tipos_peca'),
    'marcas_produto' => contar($pdo,'marcas_produto'),
    'marcas_veiculo' => contar($pdo,'marcas_veiculo'),
    'modelos' => contar($pdo,'modelos_veiculo'),
    'veiculos' => contar($pdo,'veiculos_configuracao'),
    'aplicacoes' => contar($pdo,'aplicacoes_peca'),
    'estoques' => contar($pdo,'estoques'),
    'usuarios' => contar($pdo,'usuarios')
];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
<meta charset="UTF-8">
<title>Painel</title>

<script src="https://cdn.tailwindcss.com"></script>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

</head>

<body class="bg-slate-100">

<div class="min-h-screen md:flex">

<?php require __DIR__ . '/menu.php'; ?>

<main class="flex-1 p-6">

<div class="mb-6">

<h1 class="text-2xl font-bold text-slate-800">
Painel do Sistema
</h1>

<p class="text-slate-600 mt-1">
Olá, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?>
</p>

</div>


<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Produtos</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['produtos'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Categorias de Peça</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['categorias'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Tipos de Peça</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['tipos'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Marcas de Produto</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['marcas_produto'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Marcas de Veículo</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['marcas_veiculo'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Modelos de Veículo</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['modelos'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Configurações Veiculares</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['veiculos'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Aplicações de Peça</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['aplicacoes'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Locais de Estoque</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['estoques'] ?>
</div>
</div>


<div class="bg-white rounded-xl shadow p-5">
<div class="text-sm text-gray-500">Usuários</div>
<div class="text-3xl font-bold text-slate-800">
<?= $totais['usuarios'] ?>
</div>
</div>

</div>

</main>
</div>

</body>
</html>