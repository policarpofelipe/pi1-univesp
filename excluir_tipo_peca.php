<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_tipos_peca.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlTipo = "
        SELECT id, nome, ativo
        FROM tipos_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmtTipo = $pdo->prepare($sqlTipo);
    $stmtTipo->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtTipo->execute();

    $tipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);

    if (!$tipo) {
        header('Location: listar_tipos_peca.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em produtos
    |--------------------------------------------------------------------------
    */
    $sqlProdutos = "
        SELECT COUNT(*)
        FROM produtos
        WHERE tipo_peca_id = :id
    ";

    $stmtProdutos = $pdo->prepare($sqlProdutos);
    $stmtProdutos->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtProdutos->execute();

    $totalProdutos = (int)$stmtProdutos->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em aplicações
    |--------------------------------------------------------------------------
    */
    $sqlAplicacoes = "
        SELECT COUNT(*)
        FROM aplicacoes_peca
        WHERE tipo_peca_id = :id
    ";

    $stmtAplicacoes = $pdo->prepare($sqlAplicacoes);
    $stmtAplicacoes->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtAplicacoes->execute();

    $totalAplicacoes = (int)$stmtAplicacoes->fetchColumn();

    $totalVinculos = $totalProdutos + $totalAplicacoes;

    /*
    |--------------------------------------------------------------------------
    | Se houver vínculo, apenas inativa
    |--------------------------------------------------------------------------
    */
    if ($totalVinculos > 0) {
        $sqlInativar = "
            UPDATE tipos_peca
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_tipos_peca.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM tipos_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_tipos_peca.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_tipos_peca.php?erro=erro_ao_excluir');
    exit;
}