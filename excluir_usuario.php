<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id = (int)($_GET['id'] ?? 0);
$usuarioLogadoId = (int)($_SESSION['usuario_id'] ?? 0);

if ($id <= 0) {
    header('Location: listar_usuarios.php?erro=id_invalido');
    exit;
}

/*
|--------------------------------------------------------------------------
| Impedir autoexclusão
|--------------------------------------------------------------------------
*/
if ($usuarioLogadoId > 0 && $id === $usuarioLogadoId) {
    header('Location: listar_usuarios.php?erro=erro_ao_excluir');
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar existência do registro
    |--------------------------------------------------------------------------
    */
    $sqlUsuario = "
        SELECT id, nome, email, ativo
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ";

    $stmtUsuario = $pdo->prepare($sqlUsuario);
    $stmtUsuario->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtUsuario->execute();

    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: listar_usuarios.php?erro=registro_nao_encontrado');
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
        WHERE usuario_id = :id
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
            UPDATE usuarios
            SET ativo = 0,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtInativar = $pdo->prepare($sqlInativar);
        $stmtInativar->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtInativar->execute();

        header('Location: listar_usuarios.php?sucesso=inativado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver vínculo, exclui fisicamente
    |--------------------------------------------------------------------------
    */
    $sqlExcluir = "
        DELETE FROM usuarios
        WHERE id = :id
        LIMIT 1
    ";

    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtExcluir->execute();

    header('Location: listar_usuarios.php?sucesso=excluido');
    exit;

} catch (Throwable $e) {
    header('Location: listar_usuarios.php?erro=erro_ao_excluir');
    exit;
}