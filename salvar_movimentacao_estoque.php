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

/*
|--------------------------------------------------------------------------
| Definir tela de retorno conforme origem lógica
|--------------------------------------------------------------------------
*/
$paginaRetorno = 'form_movimentacao_estoque.php';

if ($tipoMovimentacao === 'entrada') {
    $paginaRetorno = 'movimentar_entrada.php';
} elseif ($tipoMovimentacao === 'saida') {
    $paginaRetorno = 'movimentar_saida.php';
} elseif ($tipoMovimentacao === 'ajuste') {
    $paginaRetorno = 'ajustar_estoque.php';
}

if ($id > 0) {
    $paginaRetorno = 'form_movimentacao_estoque.php';
}

$redirecionarForm = function (string $erro) use ($id, $paginaRetorno, $produtoId, $estoqueId, $quantidadeInformada): void {
    $params = ['erro=' . urlencode($erro)];

    if ($id > 0) {
        $params[] = 'id=' . $id;
    } else {
        if ($produtoId > 0) {
            $params[] = 'produto_id=' . $produtoId;
        }

        if ($estoqueId > 0) {
            $params[] = 'estoque_id=' . $estoqueId;
        }
        if ($quantidadeInformada !== '') {
            $params[] = 'quantidade=' . urlencode($quantidadeInformada);
        }
    }

    header('Location: ' . $paginaRetorno . '?' . implode('&', $params));
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

if (!is_numeric($quantidadeInformada)) {
    $redirecionarForm('quantidade_invalida');
}

$quantidadeBase = (float)$quantidadeInformada;

/*
|--------------------------------------------------------------------------
| Regras por tipo
|--------------------------------------------------------------------------
*/
if (in_array($tipoMovimentacao, ['entrada', 'saida'], true) && $quantidadeBase <= 0) {
    $redirecionarForm('quantidade_invalida');
}

if ($tipoMovimentacao === 'ajuste' && $quantidadeBase == 0.0) {
    $redirecionarForm('quantidade_invalida');
}

$quantidadeFinal = match ($tipoMovimentacao) {
    'entrada' => abs($quantidadeBase),
    'saida'   => abs($quantidadeBase) * -1,
    'ajuste'  => $quantidadeBase,
    default   => 0.0,
};

try {
    if ($usuarioId > 0) {
        $stmtUsuario = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
        $stmtUsuario->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmtUsuario->execute();
        if (!$stmtUsuario->fetch()) {
            $usuarioId = 0;
        }
    }

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

    if ($tipoMovimentacao === 'saida') {
        $stmtTipoColuna = $pdo->query("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'movimentacoes_estoque'
              AND COLUMN_NAME = 'quantidade'
            LIMIT 1
        ");
        $columnTypeQuantidade = strtolower((string)$stmtTipoColuna->fetchColumn());
        if ($columnTypeQuantidade !== '' && str_contains($columnTypeQuantidade, 'unsigned')) {
            $pdo->exec("ALTER TABLE movimentacoes_estoque MODIFY COLUMN quantidade DECIMAL(10,2) NOT NULL");
        }

        $stmtSaldo = $pdo->prepare("
            SELECT COALESCE(SUM(quantidade), 0)
            FROM movimentacoes_estoque
            WHERE produto_id = :produto_id
              AND estoque_id = :estoque_id
        ");
        $stmtSaldo->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmtSaldo->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
        $stmtSaldo->execute();
        $saldoAtual = (float)$stmtSaldo->fetchColumn();
        if (abs($quantidadeBase) > $saldoAtual) {
            $redirecionarForm('saldo_insuficiente');
        }
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
                tipo_movimento = :tipo_movimentacao,
                quantidade = :quantidade,
                custo_unitario = :custo_unitario,
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
        $stmtUpdate->bindValue(':custo_unitario', null, PDO::PARAM_NULL);
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
            tipo_movimento,
            quantidade,
            custo_unitario,
            observacao,
            criado_em
        ) VALUES (
            :produto_id,
            :estoque_id,
            :usuario_id,
            :tipo_movimentacao,
            :quantidade,
            :custo_unitario,
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
    $stmtInsert->bindValue(':custo_unitario', null, PDO::PARAM_NULL);
    $stmtInsert->bindValue(':observacao', $observacao !== '' ? $observacao : null, $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->execute();

    header('Location: listar_movimentacoes_estoque.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    error_log('salvar_movimentacao_estoque.php erro: ' . $e->getMessage());
    $redirecionarForm('erro_interno');
}