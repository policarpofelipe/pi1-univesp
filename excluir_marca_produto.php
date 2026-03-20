<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_marcas_produto.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlMarca = "
        SELECT id, nome, ativo
        FROM marcas_produto
        WHERE id = :id
        LIMIT 1
    ";

    $stmtMarca = $pdo->prepare($sqlMarca);
    $stmtMarca->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtMarca->execute();

    $marca = $stmtMarca->fetch(PDO::FETCH_ASSOC);

    if (!$marca) {
        header('Location: listar_marcas_produto.php?erro=registro_nao_encontrado');
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
        WHERE marca_produto_id = :id
    ";

    $stmtProdutos = $pdo->prepare($sqlProdutos);
    $stmtProdutos->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtProdutos->execute();

    $totalProdutos = (int)$stmtProdutos->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Se houver vínculo, apenas inativa
    |--------------------------------------------------------------------------
    */
    if ($totalProdutos > 0) {
        $sqlInativar = "
            UPDATE marcas_produto
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_marcas_produto.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM marcas_produto
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_marcas_produto.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_marcas_produto.php?erro=erro_ao_excluir');
    exit;
}