<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_marcas_veiculo.php?erro=id_invalido');
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
        FROM marcas_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmtMarca = $pdo->prepare($sqlMarca);
    $stmtMarca->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtMarca->execute();

    $marca = $stmtMarca->fetch(PDO::FETCH_ASSOC);

    if (!$marca) {
        header('Location: listar_marcas_veiculo.php?erro=registro_nao_encontrado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar vínculos em modelos de veículo
    |--------------------------------------------------------------------------
    */
    $sqlModelos = "
        SELECT COUNT(*)
        FROM modelos_veiculo
        WHERE marca_veiculo_id = :id
    ";

    $stmtModelos = $pdo->prepare($sqlModelos);
    $stmtModelos->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtModelos->execute();

    $totalModelos = (int)$stmtModelos->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Se houver vínculo, apenas inativa
    |--------------------------------------------------------------------------
    */
    if ($totalModelos > 0) {
        $sqlInativar = "
            UPDATE marcas_veiculo
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_marcas_veiculo.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM marcas_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_marcas_veiculo.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_marcas_veiculo.php?erro=erro_ao_excluir');
    exit;
}