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
if ($produtoId <= 0 || !produtoExiste($pdo, $produtoId)) {
    header('Location: listar_produtos.php?erro=id_invalido');
    exit;
}

if (!isset($_FILES['imagens'])) {
    header('Location: form_produto.php?id=' . $produtoId . '&erro=upload_invalido');
    exit;
}

$resultado = salvarImagensProduto($pdo, $produtoId, $_FILES['imagens']);

if ($resultado['sucesso'] > 0 && $resultado['erros'] === []) {
    header('Location: form_produto.php?id=' . $produtoId . '&sucesso=imagem_cadastrada');
    exit;
}

if ($resultado['sucesso'] > 0 && $resultado['erros'] !== []) {
    header('Location: form_produto.php?id=' . $produtoId . '&sucesso=imagem_cadastrada_com_alerta');
    exit;
}

header('Location: form_produto.php?id=' . $produtoId . '&erro=upload_invalido');
exit;
