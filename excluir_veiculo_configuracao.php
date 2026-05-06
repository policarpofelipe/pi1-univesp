<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_veiculos_configuracao.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlConfig = "
        SELECT id, ativo
        FROM veiculos_configuracao
        WHERE id = :id
        LIMIT 1
    ";

    $stmtConfig = $pdo->prepare($sqlConfig);
    $stmtConfig->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtConfig->execute();

    $configuracao = $stmtConfig->fetch(PDO::FETCH_ASSOC);

    if (!$configuracao) {
        header('Location: listar_veiculos_configuracao.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em aplicações de produto
    |--------------------------------------------------------------------------
    */
    $sqlAplicacoes = "
        SELECT COUNT(*)
        FROM aplicacoes_produto
        WHERE veiculo_configuracao_id = :id
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
    if ($totalAplicacoes > 0) {
        $sqlInativar = "
            UPDATE veiculos_configuracao
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_veiculos_configuracao.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM veiculos_configuracao
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_veiculos_configuracao.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_veiculos_configuracao.php?erro=erro_ao_excluir');
    exit;
}