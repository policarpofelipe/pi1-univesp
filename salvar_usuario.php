<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_usuarios.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$senha = (string)($_POST['senha'] ?? '');
$ativo = (string)($_POST['ativo'] ?? '1');

$ativo = ($ativo === '0') ? 0 : 1;

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_usuario.php?erro=' . urlencode($erro) . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($nome === '') {
    $redirecionarForm('nome_obrigatorio');
}

if ($email === '') {
    $redirecionarForm('email_obrigatorio');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $redirecionarForm('email_invalido');
}

if ($id <= 0) {
    if ($senha === '') {
        $redirecionarForm('senha_obrigatoria');
    }

    if (mb_strlen($senha) < 6) {
        $redirecionarForm('senha_curta');
    }
} else {
    if ($senha !== '' && mb_strlen($senha) < 6) {
        $redirecionarForm('senha_curta');
    }
}

try {
    if ($id > 0) {
        /*
        |--------------------------------------------------------------------------
        | Verificar existência do usuário
        |--------------------------------------------------------------------------
        */
        $sqlExiste = "
            SELECT id
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ";

        $stmtExiste = $pdo->prepare($sqlExiste);
        $stmtExiste->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtExiste->execute();

        if (!$stmtExiste->fetch()) {
            header('Location: listar_usuarios.php?erro=registro_nao_encontrado');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar duplicidade de e-mail na edição
        |--------------------------------------------------------------------------
        */
        $sqlDuplicidade = "
            SELECT id
            FROM usuarios
            WHERE email = :email
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':email', $email);
        $stmtDuplicidade->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDuplicidade->execute();

        if ($stmtDuplicidade->fetch()) {
            $redirecionarForm('email_duplicado');
        }

        /*
        |--------------------------------------------------------------------------
        | Atualizar com ou sem troca de senha
        |--------------------------------------------------------------------------
        */
        if ($senha !== '') {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sqlUpdate = "
                UPDATE usuarios
                SET
                    nome = :nome,
                    email = :email,
                    senha = :senha,
                    ativo = :ativo,
                    atualizado_em = NOW()
                WHERE id = :id
                LIMIT 1
            ";

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':nome', $nome);
            $stmtUpdate->bindValue(':email', $email);
            $stmtUpdate->bindValue(':senha', $senhaHash);
            $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUpdate->execute();
        } else {
            $sqlUpdate = "
                UPDATE usuarios
                SET
                    nome = :nome,
                    email = :email,
                    ativo = :ativo,
                    atualizado_em = NOW()
                WHERE id = :id
                LIMIT 1
            ";

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':nome', $nome);
            $stmtUpdate->bindValue(':email', $email);
            $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUpdate->execute();
        }

        header('Location: listar_usuarios.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade de e-mail no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM usuarios
        WHERE email = :email
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':email', $email);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        $redirecionarForm('email_duplicado');
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir novo usuário
    |--------------------------------------------------------------------------
    */
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $sqlInsert = "
        INSERT INTO usuarios (
            nome,
            email,
            senha,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :nome,
            :email,
            :senha,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':nome', $nome);
    $stmtInsert->bindValue(':email', $email);
    $stmtInsert->bindValue(':senha', $senhaHash);
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    header('Location: listar_usuarios.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}