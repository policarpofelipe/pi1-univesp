<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_tipos_peca.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$categoriaPecaId = (int)($_POST['categoria_peca_id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$descricao = trim((string)($_POST['descricao'] ?? ''));
$ativo = (string)($_POST['ativo'] ?? '1');

$ativo = ($ativo === '0') ? 0 : 1;

if ($categoriaPecaId <= 0) {
    header('Location: form_tipo_peca.php?erro=categoria_obrigatoria' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

if ($nome === '') {
    header('Location: form_tipo_peca.php?erro=nome_obrigatorio' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

if (mb_strlen($nome) > 150) {
    header('Location: form_tipo_peca.php?erro=nome_maior_que_limite' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

try {
    /*
    |--------------------------------------------------------------------------
    | Validar se a categoria existe
    |--------------------------------------------------------------------------
    */
    $sqlCategoria = "
        SELECT id
        FROM categorias_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmtCategoria = $pdo->prepare($sqlCategoria);
    $stmtCategoria->bindValue(':id', $categoriaPecaId, PDO::PARAM_INT);
    $stmtCategoria->execute();

    if (!$stmtCategoria->fetch()) {
        header('Location: form_tipo_peca.php?erro=categoria_obrigatoria' . ($id > 0 ? '&id=' . $id : ''));
        exit;
    }

    if ($id > 0) {
        /*
        |--------------------------------------------------------------------------
        | Validar duplicidade na edição
        |--------------------------------------------------------------------------
        */
        $sqlDuplicidade = "
            SELECT id
            FROM tipos_peca
            WHERE categoria_peca_id = :categoria_peca_id
              AND nome = :nome
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':categoria_peca_id', $categoriaPecaId, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':nome', $nome);
        $stmtDuplicidade->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDuplicidade->execute();

        if ($stmtDuplicidade->fetch()) {
            header('Location: form_tipo_peca.php?id=' . $id . '&erro=nome_duplicado');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Atualizar registro
        |--------------------------------------------------------------------------
        */
        $sqlUpdate = "
            UPDATE tipos_peca
            SET
                categoria_peca_id = :categoria_peca_id,
                nome = :nome,
                descricao = :descricao,
                ativo = :ativo,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':categoria_peca_id', $categoriaPecaId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':nome', $nome);
        $stmtUpdate->bindValue(
            ':descricao',
            $descricao !== '' ? $descricao : null,
            $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_tipos_peca.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM tipos_peca
        WHERE categoria_peca_id = :categoria_peca_id
          AND nome = :nome
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':categoria_peca_id', $categoriaPecaId, PDO::PARAM_INT);
    $stmtDuplicidade->bindValue(':nome', $nome);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        header('Location: form_tipo_peca.php?erro=nome_duplicado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir novo registro
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO tipos_peca (
            categoria_peca_id,
            nome,
            descricao,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :categoria_peca_id,
            :nome,
            :descricao,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':categoria_peca_id', $categoriaPecaId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':nome', $nome);
    $stmtInsert->bindValue(
        ':descricao',
        $descricao !== '' ? $descricao : null,
        $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    header('Location: listar_tipos_peca.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    header('Location: form_tipo_peca.php?erro=erro_interno' . ($id > 0 ? '&id=' . $id : ''));
    exit;
}