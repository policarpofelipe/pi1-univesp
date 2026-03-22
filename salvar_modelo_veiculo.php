<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_modelos_veiculo.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$marcaVeiculoId = (int)($_POST['marca_veiculo_id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$ativo = (string)($_POST['ativo'] ?? '1');

$ativo = ($ativo === '0') ? 0 : 1;

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_modelo_veiculo.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($marcaVeiculoId <= 0) {
    $redirecionarForm('marca_obrigatoria');
}

if ($nome === '') {
    $redirecionarForm('nome_obrigatorio');
}

if (mb_strlen($nome) > 100) {
    $redirecionarForm('nome_maior_que_limite');
}

try {
    /*
    |--------------------------------------------------------------------------
    | Validar existência da marca
    |--------------------------------------------------------------------------
    */
    $sqlMarca = "
        SELECT id
        FROM marcas_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmtMarca = $pdo->prepare($sqlMarca);
    $stmtMarca->bindValue(':id', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtMarca->execute();

    if (!$stmtMarca->fetch()) {
        $redirecionarForm('marca_obrigatoria');
    }

    if ($id > 0) {
        /*
        |--------------------------------------------------------------------------
        | Validar duplicidade na edição
        |--------------------------------------------------------------------------
        */
        $sqlDuplicidade = "
            SELECT id
            FROM modelos_veiculo
            WHERE marca_veiculo_id = :marca_veiculo_id
              AND nome = :nome
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
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
            UPDATE modelos_veiculo
            SET
                marca_veiculo_id = :marca_veiculo_id,
                nome = :nome,
                ativo = :ativo,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':nome', $nome);
        $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_modelos_veiculo.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM modelos_veiculo
        WHERE marca_veiculo_id = :marca_veiculo_id
          AND nome = :nome
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
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
        INSERT INTO modelos_veiculo (
            marca_veiculo_id,
            nome,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :marca_veiculo_id,
            :nome,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':marca_veiculo_id', $marcaVeiculoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':nome', $nome);
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    header('Location: listar_modelos_veiculo.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}