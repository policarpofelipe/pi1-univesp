<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_estoques.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlEstoque = "
        SELECT id, nome, ativo
        FROM estoques
        WHERE id = :id
        LIMIT 1
    ";

    $stmtEstoque = $pdo->prepare($sqlEstoque);
    $stmtEstoque->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtEstoque->execute();

    $estoque = $stmtEstoque->fetch(PDO::FETCH_ASSOC);

    if (!$estoque) {
        header('Location: listar_estoques.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em movimentações de estoque
    |--------------------------------------------------------------------------
    */
    $sqlMovimentacoes = "
        SELECT COUNT(*)
        FROM movimentacoes_estoque
        WHERE estoque_id = :id
    ";

    $stmtMovimentacoes = $pdo->prepare($sqlMovimentacoes);
    $stmtMovimentacoes->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtMovimentacoes->execute();

    $totalMovimentacoes = (int)$stmtMovimentacoes->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Se houver vínculo, apenas inativa
    |--------------------------------------------------------------------------
    */
    if ($totalMovimentacoes > 0) {
        $sqlInativar = "
            UPDATE estoques
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_estoques.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM estoques
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_estoques.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_estoques.php?erro=erro_ao_excluir');
    exit;
}