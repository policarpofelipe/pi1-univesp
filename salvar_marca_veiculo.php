<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_marcas_veiculo.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$ativo = (string)($_POST['ativo'] ?? '1');

$ativo = ($ativo === '0') ? 0 : 1;

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_marca_veiculo.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($nome === '') {
    $redirecionarForm('nome_obrigatorio');
}

if (mb_strlen($nome) > 100) {
    $redirecionarForm('nome_maior_que_limite');
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
            FROM marcas_veiculo
            WHERE nome = :nome
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':nome', $nome);
        $stmtDuplicidade->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDuplicidade->execute();

        if ($stmtDuplicidade->fetch()) {
            $redirecionarForm('nome_duplicado');
        }

        /*
        |--------------------------------------------------------------------------
        | Atualizar registro
        |--------------------------------------------------------------------------
        */
        $sqlUpdate = "
            UPDATE marcas_veiculo
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

        header('Location: listar_marcas_veiculo.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM marcas_veiculo
        WHERE nome = :nome
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':nome', $nome);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        $redirecionarForm('nome_duplicado');
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir novo registro
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO marcas_veiculo (
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

    header('Location: listar_marcas_veiculo.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}