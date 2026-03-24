<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_aplicacoes_peca.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlAplicacao = "
        SELECT id
        FROM aplicacoes_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmtAplicacao = $pdo->prepare($sqlAplicacao);
    $stmtAplicacao->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtAplicacao->execute();

    $aplicacao = $stmtAplicacao->fetch(PDO::FETCH_ASSOC);

    if (!$aplicacao) {
        header('Location: listar_aplicacoes_peca.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Excluir vínculo
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM aplicacoes_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_aplicacoes_peca.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_aplicacoes_peca.php?erro=erro_ao_excluir');
    exit;
}