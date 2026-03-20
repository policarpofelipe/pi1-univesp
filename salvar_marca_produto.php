<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_marcas_produto.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$ativo = (string)($_POST['ativo'] ?? '1');

$ativo = ($ativo === '0') ? 0 : 1;

if ($nome === '') {
    header('Location: form_marca_produto.php?erro=nome_obrigatorio' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

if (mb_strlen($nome) > 100) {
    header('Location: form_marca_produto.php?erro=nome_maior_que_limite' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

try {
    if ($id > 0) {
        /*
        |--------------------------------------------------------------------------
        | Validar duplicidade na edição
        |--------------------------------------------------------------------------
        */
        $sqlDuplicidade = "
            SELECT id
            FROM marcas_produto
            WHERE nome = :nome
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':nome', $nome);
        $stmtDuplicidade->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDuplicidade->execute();

        if ($stmtDuplicidade->fetch()) {
            header('Location: form_marca_produto.php?id=' . $id . '&erro=nome_duplicado');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Atualizar registro
        |--------------------------------------------------------------------------
        */
        $sqlUpdate = "
            UPDATE marcas_produto
            SET
                nome = :nome,
                ativo = :ativo,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':nome', $nome);
        $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_marcas_produto.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM marcas_produto
        WHERE nome = :nome
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':nome', $nome);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        header('Location: form_marca_produto.php?erro=nome_duplicado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir novo registro
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO marcas_produto (
            nome,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :nome,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':nome', $nome);
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    header('Location: listar_marcas_produto.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    header('Location: form_marca_produto.php?erro=erro_interno' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}