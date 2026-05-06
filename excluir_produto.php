<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/lib/produto_imagens.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_produtos.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlProduto = "
        SELECT id, nome_comercial, ativo
        FROM produtos
        WHERE id = :id
        LIMIT 1
    ";

    $stmtProduto = $pdo->prepare($sqlProduto);
    $stmtProduto->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtProduto->execute();

    $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        header('Location: listar_produtos.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em movimentações de estoque e aplicações
    |--------------------------------------------------------------------------
    */
    $sqlMovimentacoes = "
        SELECT COUNT(*)
        FROM movimentacoes_estoque
        WHERE produto_id = :id
    ";

    $stmtMovimentacoes = $pdo->prepare($sqlMovimentacoes);
    $stmtMovimentacoes->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtMovimentacoes->execute();

    $totalMovimentacoes = (int)$stmtMovimentacoes->fetchColumn();

    $sqlAplicacoes = "
        SELECT COUNT(*)
        FROM aplicacoes_produto
        WHERE produto_id = :id
    ";
    $stmtAplicacoes = $pdo->prepare($sqlAplicacoes);
    $stmtAplicacoes->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtAplicacoes->execute();
    $totalAplicacoes = (int)$stmtAplicacoes->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Se houver vínculo, apenas inativa
    |--------------------------------------------------------------------------
    */
    if ($totalMovimentacoes > 0 || $totalAplicacoes > 0) {
        $sqlInativar = "
            UPDATE produtos
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_produtos.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $imagensProduto = listarImagensProduto($pdo, $id);

    $sqlExcluir = "
        DELETE FROM produtos
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    foreach ($imagensProduto as $imagem) {
        $caminhoRelativo = (string)($imagem['caminho_arquivo'] ?? '');
        if ($caminhoRelativo === '') {
            continue;
        }
        $caminhoAbs = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminhoRelativo);
        if (is_file($caminhoAbs)) {
            @unlink($caminhoAbs);
        }
    }

    header('Location: listar_produtos.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_produtos.php?erro=erro_ao_excluir');
    exit;
}