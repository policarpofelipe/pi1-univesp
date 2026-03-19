<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_categorias_peca.php?erro=id_invalido');
    exit;
}

try {
    $sqlCategoria = "
        SELECT id, nome, ativo
        FROM categorias_peca
        WHERE id = :id
        LIMIT 1
    ";
    $stmtCategoria = $pdo->prepare($sqlCategoria);
    $stmtCategoria->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCategoria->execute();

    $categoria = $stmtCategoria->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        header('Location: listar_categorias_peca.php?erro=registro_nao_encontrado');
        exit;
    }

    $sqlVinculos = "
        SELECT COUNT(*) 
        FROM tipos_peca
        WHERE categoria_peca_id = :id
    ";
    $stmtVinculos = $pdo->prepare($sqlVinculos);
    $stmtVinculos->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtVinculos->execute();

    $totalVinculos = (int)$stmtVinculos->fetchColumn();

    if ($totalVinculos > 0) {
        $sqlInativar = "
            UPDATE categorias_peca
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";
        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_categorias_peca.php?sucesso=inativado');
        exit;
    }

    $sqlExcluir = "
        DELETE FROM categorias_peca
        WHERE id = :id
        LIMIT 1
    ";
    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_categorias_peca.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_categorias_peca.php?erro=erro_ao_excluir');
    exit;
}