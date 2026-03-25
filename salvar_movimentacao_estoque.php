<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_movimentacoes_estoque.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$produtoId = (int)($_POST['produto_id'] ?? 0);
$estoqueId = (int)($_POST['estoque_id'] ?? 0);
$tipoMovimentacao = trim((string)($_POST['tipo_movimentacao'] ?? ''));
$quantidadeInformada = trim((string)($_POST['quantidade'] ?? ''));
$observacao = trim((string)($_POST['observacao'] ?? ''));

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_movimentacao_estoque.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($produtoId <= 0) {
    $redirecionarForm('produto_obrigatorio');
}

if ($estoqueId <= 0) {
    $redirecionarForm('estoque_obrigatorio');
}

if (!in_array($tipoMovimentacao, ['entrada', 'saida', 'ajuste'], true)) {
    $redirecionarForm('tipo_obrigatorio');
}

if ($quantidadeInformada === '') {
    $redirecionarForm('quantidade_obrigatoria');
}

$quantidadeInformada = str_replace(',', '.', $quantidadeInformada);

if (!is_numeric($quantidadeInformada) || (float)$quantidadeInformada <= 0) {
    $redirecionarForm('quantidade_invalida');
}

$quantidadeBase = (float)$quantidadeInformada;

$quantidadeFinal = match ($tipoMovimentacao) {
    'entrada' => $quantidadeBase,
    'saida'   => $quantidadeBase * -1,
    'ajuste'  => $quantidadeBase,
    default   => 0.0,
};

try {
    /*
    |--------------------------------------------------------------------------
    | Validar existência do produto
    |--------------------------------------------------------------------------
    */
    $sqlProduto = "
        SELECT id
        FROM produtos
        WHERE id = :id
        LIMIT 1
    ";
    $stmtProduto = $pdo->prepare($sqlProduto);
    $stmtProduto->bindValue(':id', $produtoId, PDO::PARAM_INT);
    $stmtProduto->execute();

    if (!$stmtProduto->fetch()) {
        $redirecionarForm('produto_obrigatorio');
    }

    /*
    |--------------------------------------------------------------------------
    | Validar existência do estoque
    |--------------------------------------------------------------------------
    */
    $sqlEstoque = "
        SELECT id
        FROM estoques
        WHERE id = :id
        LIMIT 1
    ";
    $stmtEstoque = $pdo->prepare($sqlEstoque);
    $stmtEstoque->bindValue(':id', $estoqueId, PDO::PARAM_INT);
    $stmtEstoque->execute();

    if (!$stmtEstoque->fetch()) {
        $redirecionarForm('estoque_obrigatorio');
    }

    /*
    |--------------------------------------------------------------------------
    | Atualização de movimentação
    |--------------------------------------------------------------------------
    */
    if ($id > 0) {
        $sqlExiste = "
            SELECT id
            FROM movimentacoes_estoque
            WHERE id = :id
            LIMIT 1
        ";
        $stmtExiste = $pdo->prepare($sqlExiste);
        $stmtExiste->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtExiste->execute();

        if (!$stmtExiste->fetch()) {
            header('Location: listar_movimentacoes_estoque.php?erro=registro_nao_encontrado');
            exit;
        }

        $sqlUpdate = "
            UPDATE movimentacoes_estoque
            SET
                produto_id = :produto_id,
                estoque_id = :estoque_id,
                usuario_id = :usuario_id,
                tipo_movimentacao = :tipo_movimentacao,
                quantidade = :quantidade,
                observacao = :observacao
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':usuario_id', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':tipo_movimentacao', $tipoMovimentacao);
        $stmtUpdate->bindValue(':quantidade', number_format($quantidadeFinal, 2, '.', ''), PDO::PARAM_STR);
        $stmtUpdate->bindValue(':observacao', $observacao !== '' ? $observacao : null, $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_movimentacoes_estoque.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Inserção de movimentação
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO movimentacoes_estoque (
            produto_id,
            estoque_id,
            usuario_id,
            tipo_movimentacao,
            quantidade,
            observacao,
            criado_em
        ) VALUES (
            :produto_id,
            :estoque_id,
            :usuario_id,
            :tipo_movimentacao,
            :quantidade,
            :observacao,
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':usuario_id', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':tipo_movimentacao', $tipoMovimentacao);
    $stmtInsert->bindValue(':quantidade', number_format($quantidadeFinal, 2, '.', ''), PDO::PARAM_STR);
    $stmtInsert->bindValue(':observacao', $observacao !== '' ? $observacao : null, $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->execute();

    header('Location: listar_movimentacoes_estoque.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}