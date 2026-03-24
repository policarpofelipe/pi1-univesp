<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_aplicacoes_peca.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$tipoPecaId = (int)($_POST['tipo_peca_id'] ?? 0);
$veiculoConfiguracaoId = (int)($_POST['veiculo_configuracao_id'] ?? 0);
$observacao = trim((string)($_POST['observacao'] ?? ''));

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_aplicacao_peca.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($tipoPecaId <= 0) {
    $redirecionarForm('tipo_obrigatorio');
}

if ($veiculoConfiguracaoId <= 0) {
    $redirecionarForm('veiculo_obrigatorio');
}

try {
    /*
    |--------------------------------------------------------------------------
    | Validar existência do tipo de peça
    |--------------------------------------------------------------------------
    */
    $sqlTipo = "
        SELECT id
        FROM tipos_peca
        WHERE id = :id
        LIMIT 1
    ";

    $stmtTipo = $pdo->prepare($sqlTipo);
    $stmtTipo->bindValue(':id', $tipoPecaId, PDO::PARAM_INT);
    $stmtTipo->execute();

    if (!$stmtTipo->fetch()) {
        $redirecionarForm('tipo_obrigatorio');
    }

    /*
    |--------------------------------------------------------------------------
    | Validar existência da configuração veicular
    |--------------------------------------------------------------------------
    */
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
        /*
        |--------------------------------------------------------------------------
        | Validar duplicidade na edição
        |--------------------------------------------------------------------------
        */
        $sqlDuplicidade = "
            SELECT id
            FROM aplicacoes_peca
            WHERE tipo_peca_id = :tipo_peca_id
              AND veiculo_configuracao_id = :veiculo_configuracao_id
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':tipo_peca_id', $tipoPecaId, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDuplicidade->execute();

        if ($stmtDuplicidade->fetch()) {
            $redirecionarForm('duplicado');
        }

        /*
        |--------------------------------------------------------------------------
        | Atualizar registro
        |--------------------------------------------------------------------------
        */
        $sqlUpdate = "
            UPDATE aplicacoes_peca
            SET
                tipo_peca_id = :tipo_peca_id,
                veiculo_configuracao_id = :veiculo_configuracao_id,
                observacao = :observacao,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':tipo_peca_id', $tipoPecaId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(
            ':observacao',
            $observacao !== '' ? $observacao : null,
            $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_aplicacoes_peca.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM aplicacoes_peca
        WHERE tipo_peca_id = :tipo_peca_id
          AND veiculo_configuracao_id = :veiculo_configuracao_id
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':tipo_peca_id', $tipoPecaId, PDO::PARAM_INT);
    $stmtDuplicidade->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        $redirecionarForm('duplicado');
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir nova aplicação
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO aplicacoes_peca (
            tipo_peca_id,
            veiculo_configuracao_id,
            observacao,
            criado_em,
            atualizado_em
        ) VALUES (
            :tipo_peca_id,
            :veiculo_configuracao_id,
            :observacao,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':tipo_peca_id', $tipoPecaId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(
        ':observacao',
        $observacao !== '' ? $observacao : null,
        $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );
    $stmtInsert->execute();

    header('Location: listar_aplicacoes_peca.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}