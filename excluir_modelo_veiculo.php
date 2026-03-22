<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_modelos_veiculo.php?erro=id_invalido');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlModelo = "
        SELECT id, nome, ativo
        FROM modelos_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmtModelo = $pdo->prepare($sqlModelo);
    $stmtModelo->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtModelo->execute();

    $modelo = $stmtModelo->fetch(PDO::FETCH_ASSOC);

    if (!$modelo) {
        header('Location: listar_modelos_veiculo.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em configurações veiculares
    |--------------------------------------------------------------------------
    */
    $sqlConfiguracoes = "
        SELECT COUNT(*)
        FROM veiculos_configuracao
        WHERE modelo_veiculo_id = :id
    ";

    $stmtConfiguracoes = $pdo->prepare($sqlConfiguracoes);
    $stmtConfiguracoes->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtConfiguracoes->execute();

    $totalConfiguracoes = (int)$stmtConfiguracoes->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Se houver vínculo, apenas inativa
    |--------------------------------------------------------------------------
    */
    if ($totalConfiguracoes > 0) {
        $sqlInativar = "
            UPDATE modelos_veiculo
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_modelos_veiculo.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM modelos_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_modelos_veiculo.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_modelos_veiculo.php?erro=erro_ao_excluir');
    exit;
}