<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_aplicacoes_produto.php?erro=id_invalido');
    exit;
}

try {
    $sqlAplicacao = "
        SELECT id
        FROM aplicacoes_produto
        WHERE id = :id
        LIMIT 1
    ";

    $stmtAplicacao = $pdo->prepare($sqlAplicacao);
    $stmtAplicacao->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtAplicacao->execute();

    if (!$stmtAplicacao->fetch(PDO::FETCH_ASSOC)) {
        header('Location: listar_aplicacoes_produto.php?erro=registro_nao_encontrado');
        exit;
    }

    $sqlExcluir = "
        DELETE FROM aplicacoes_produto
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_aplicacoes_produto.php?sucesso=excluido');
    exit;
} catch (Throwable $e) {
    header('Location: listar_aplicacoes_produto.php?erro=erro_ao_excluir');
    exit;
}
