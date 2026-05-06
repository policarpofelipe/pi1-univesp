<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_aplicacoes_produto.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$produtoId = (int)($_POST['produto_id'] ?? 0);
$veiculoConfiguracaoId = (int)($_POST['veiculo_configuracao_id'] ?? 0);
$observacao = trim((string)($_POST['observacao'] ?? ''));
$ativo = (string)($_POST['ativo'] ?? '1');
$ativo = ($ativo === '0') ? 0 : 1;

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_aplicacao_produto.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($produtoId <= 0) {
    $redirecionarForm('produto_obrigatorio');
}

if ($veiculoConfiguracaoId <= 0) {
    $redirecionarForm('veiculo_obrigatorio');
}

try {
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

    $sqlVeiculo = "
        SELECT id
        FROM veiculos_configuracao
        WHERE id = :id
        LIMIT 1
    ";
    $stmtVeiculo = $pdo->prepare($sqlVeiculo);
    $stmtVeiculo->bindValue(':id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtVeiculo->execute();

    if (!$stmtVeiculo->fetch()) {
        $redirecionarForm('veiculo_obrigatorio');
    }

    if ($id > 0) {
        $sqlDuplicidade = "
            SELECT id
            FROM aplicacoes_produto
            WHERE produto_id = :produto_id
              AND veiculo_configuracao_id = :veiculo_configuracao_id
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDuplicidade->execute();

        if ($stmtDuplicidade->fetch()) {
            $redirecionarForm('duplicado');
        }

        $sqlUpdate = "
            UPDATE aplicacoes_produto
            SET
                produto_id = :produto_id,
                veiculo_configuracao_id = :veiculo_configuracao_id,
                observacao = :observacao,
                ativo = :ativo,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':observacao', $observacao !== '' ? $observacao : null, $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_aplicacoes_produto.php?sucesso=editado');
        exit;
    }

    $sqlDuplicidade = "
        SELECT id
        FROM aplicacoes_produto
        WHERE produto_id = :produto_id
          AND veiculo_configuracao_id = :veiculo_configuracao_id
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmtDuplicidade->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        $redirecionarForm('duplicado');
    }

    $sqlInsert = "
        INSERT INTO aplicacoes_produto (
            produto_id,
            veiculo_configuracao_id,
            observacao,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :produto_id,
            :veiculo_configuracao_id,
            :observacao,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':observacao', $observacao !== '' ? $observacao : null, $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    header('Location: listar_aplicacoes_produto.php?sucesso=cadastrado');
    exit;
} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}
