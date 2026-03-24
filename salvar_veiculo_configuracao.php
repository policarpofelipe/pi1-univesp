<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_veiculos_configuracao.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$modeloVeiculoId = (int)($_POST['modelo_veiculo_id'] ?? 0);

$anoInicio = trim((string)($_POST['ano_inicio'] ?? ''));
$anoFim = trim((string)($_POST['ano_fim'] ?? ''));

$motorizacao = trim((string)($_POST['motorizacao'] ?? ''));
$combustivel = trim((string)($_POST['combustivel'] ?? ''));
$versao = trim((string)($_POST['versao'] ?? ''));
$observacoes = trim((string)($_POST['observacoes'] ?? ''));

$ativo = (string)($_POST['ativo'] ?? '1');
$ativo = ($ativo === '0') ? 0 : 1;

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_veiculo_configuracao.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
    exit;
};

if ($modeloVeiculoId <= 0) {
    $redirecionarForm('modelo_obrigatorio');
}

if ($anoInicio === '') {
    $redirecionarForm('ano_inicio_obrigatorio');
}

if ($anoFim === '') {
    $redirecionarForm('ano_fim_obrigatorio');
}

if (!ctype_digit($anoInicio) || !ctype_digit($anoFim)) {
    $redirecionarForm('ano_invalido');
}

$anoInicioInt = (int)$anoInicio;
$anoFimInt = (int)$anoFim;

if ($anoInicioInt < 1900 || $anoInicioInt > 2100 || $anoFimInt < 1900 || $anoFimInt > 2100) {
    $redirecionarForm('ano_invalido');
}

if ($anoFimInt < $anoInicioInt) {
    $redirecionarForm('ano_fim_menor');
}

if (mb_strlen($motorizacao) > 50) {
    $redirecionarForm('erro_interno');
}

if (mb_strlen($combustivel) > 30) {
    $redirecionarForm('erro_interno');
}

if (mb_strlen($versao) > 100) {
    $redirecionarForm('erro_interno');
}

try {
    /*
    |--------------------------------------------------------------------------
    | Validar existência do modelo
    |--------------------------------------------------------------------------
    */
    $sqlModelo = "
        SELECT id
        FROM modelos_veiculo
        WHERE id = :id
        LIMIT 1
    ";

    $stmtModelo = $pdo->prepare($sqlModelo);
    $stmtModelo->bindValue(':id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtModelo->execute();

    if (!$stmtModelo->fetch()) {
        $redirecionarForm('modelo_obrigatorio');
    }

    /*
    |--------------------------------------------------------------------------
    | Normalização simples para comparação de duplicidade
    |--------------------------------------------------------------------------
    */
    $motorizacaoComparacao = ($motorizacao !== '') ? $motorizacao : null;
    $combustivelComparacao = ($combustivel !== '') ? $combustivel : null;
    $versaoComparacao = ($versao !== '') ? $versao : null;

    if ($id > 0) {
        /*
        |--------------------------------------------------------------------------
        | Validar duplicidade na edição
        |--------------------------------------------------------------------------
        */
        $sqlDuplicidade = "
            SELECT id
            FROM veiculos_configuracao
            WHERE modelo_veiculo_id = :modelo_veiculo_id
              AND ano_inicio = :ano_inicio
              AND ano_fim = :ano_fim
              AND (
                    (motorizacao = :motorizacao)
                    OR (motorizacao IS NULL AND :motorizacao IS NULL)
                  )
              AND (
                    (combustivel = :combustivel)
                    OR (combustivel IS NULL AND :combustivel IS NULL)
                  )
              AND (
                    (versao = :versao)
                    OR (versao IS NULL AND :versao IS NULL)
                  )
              AND id <> :id
            LIMIT 1
        ";

        $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
        $stmtDuplicidade->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':ano_inicio', $anoInicioInt, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':ano_fim', $anoFimInt, PDO::PARAM_INT);
        $stmtDuplicidade->bindValue(':motorizacao', $motorizacaoComparacao, $motorizacaoComparacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtDuplicidade->bindValue(':combustivel', $combustivelComparacao, $combustivelComparacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtDuplicidade->bindValue(':versao', $versaoComparacao, $versaoComparacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
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
            UPDATE veiculos_configuracao
            SET
                modelo_veiculo_id = :modelo_veiculo_id,
                ano_inicio = :ano_inicio,
                ano_fim = :ano_fim,
                motorizacao = :motorizacao,
                combustivel = :combustivel,
                versao = :versao,
                observacoes = :observacoes,
                ativo = :ativo,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':ano_inicio', $anoInicioInt, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':ano_fim', $anoFimInt, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':motorizacao', $motorizacao !== '' ? $motorizacao : null, $motorizacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':combustivel', $combustivel !== '' ? $combustivel : null, $combustivel !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':versao', $versao !== '' ? $versao : null, $versao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null, $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: listar_veiculos_configuracao.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validar duplicidade no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlDuplicidade = "
        SELECT id
        FROM veiculos_configuracao
        WHERE modelo_veiculo_id = :modelo_veiculo_id
          AND ano_inicio = :ano_inicio
          AND ano_fim = :ano_fim
          AND (
                (motorizacao = :motorizacao)
                OR (motorizacao IS NULL AND :motorizacao IS NULL)
              )
          AND (
                (combustivel = :combustivel)
                OR (combustivel IS NULL AND :combustivel IS NULL)
              )
          AND (
                (versao = :versao)
                OR (versao IS NULL AND :versao IS NULL)
              )
        LIMIT 1
    ";

    $stmtDuplicidade = $pdo->prepare($sqlDuplicidade);
    $stmtDuplicidade->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtDuplicidade->bindValue(':ano_inicio', $anoInicioInt, PDO::PARAM_INT);
    $stmtDuplicidade->bindValue(':ano_fim', $anoFimInt, PDO::PARAM_INT);
    $stmtDuplicidade->bindValue(':motorizacao', $motorizacaoComparacao, $motorizacaoComparacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtDuplicidade->bindValue(':combustivel', $combustivelComparacao, $combustivelComparacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtDuplicidade->bindValue(':versao', $versaoComparacao, $versaoComparacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtDuplicidade->execute();

    if ($stmtDuplicidade->fetch()) {
        $redirecionarForm('duplicado');
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir novo registro
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO veiculos_configuracao (
            modelo_veiculo_id,
            ano_inicio,
            ano_fim,
            motorizacao,
            combustivel,
            versao,
            observacoes,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :modelo_veiculo_id,
            :ano_inicio,
            :ano_fim,
            :motorizacao,
            :combustivel,
            :versao,
            :observacoes,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':modelo_veiculo_id', $modeloVeiculoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':ano_inicio', $anoInicioInt, PDO::PARAM_INT);
    $stmtInsert->bindValue(':ano_fim', $anoFimInt, PDO::PARAM_INT);
    $stmtInsert->bindValue(':motorizacao', $motorizacao !== '' ? $motorizacao : null, $motorizacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':combustivel', $combustivel !== '' ? $combustivel : null, $combustivel !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':versao', $versao !== '' ? $versao : null, $versao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null, $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    header('Location: listar_veiculos_configuracao.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    $redirecionarForm('erro_interno');
}