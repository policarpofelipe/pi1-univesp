<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/lib/produto_imagens.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_produtos.php?erro=metodo_invalido');
    exit;
}

$produtoId = (int)($_POST['produto_id'] ?? 0);
$imagemId = (int)($_POST['imagem_id'] ?? 0);

if ($produtoId <= 0 || $imagemId <= 0 || !produtoExiste($pdo, $produtoId)) {
    header('Location: listar_produtos.php?erro=id_invalido');
    exit;
}

if (!definirImagemPrincipal($pdo, $produtoId, $imagemId)) {
    header('Location: form_produto.php?id=' . $produtoId . '&erro=imagem_principal_invalida');
    exit;
}

header('Location: form_produto.php?id=' . $produtoId . '&sucesso=imagem_principal_definida');
exit;
