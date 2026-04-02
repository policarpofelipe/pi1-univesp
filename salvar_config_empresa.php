<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form_config_empresa.php?erro=erro_interno');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

$razaoSocial = trim((string)($_POST['razao_social'] ?? ''));
$nomeFantasia = trim((string)($_POST['nome_fantasia'] ?? ''));
$cnpj = trim((string)($_POST['cnpj'] ?? ''));
$inscricaoEstadual = trim((string)($_POST['inscricao_estadual'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$telefone = trim((string)($_POST['telefone'] ?? ''));
$cep = trim((string)($_POST['cep'] ?? ''));
$logradouro = trim((string)($_POST['logradouro'] ?? ''));
$numero = trim((string)($_POST['numero'] ?? ''));
$complemento = trim((string)($_POST['complemento'] ?? ''));
$bairro = trim((string)($_POST['bairro'] ?? ''));
$cidade = trim((string)($_POST['cidade'] ?? ''));
$uf = strtoupper(trim((string)($_POST['uf'] ?? '')));

$redirecionar = function (string $erro): void {
    header('Location: form_config_empresa.php?erro=' . urlencode($erro));
    exit;
};

if ($razaoSocial === '') {
    $redirecionar('razao_social_obrigatoria');
}

if ($nomeFantasia === '') {
    $redirecionar('nome_fantasia_obrigatorio');
}

if ($cnpj === '') {
    $redirecionar('cnpj_obrigatorio');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $redirecionar('email_invalido');
}

try {
    /*
    |--------------------------------------------------------------------------
    | Se veio ID, tenta atualizar diretamente
    |--------------------------------------------------------------------------
    */
    if ($id > 0) {
        $sqlExiste = "
            SELECT id
            FROM config_empresa
            WHERE id = :id
            LIMIT 1
        ";

        $stmtExiste = $pdo->prepare($sqlExiste);
        $stmtExiste->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtExiste->execute();

        if ($stmtExiste->fetch()) {
            $sqlUpdate = "
                UPDATE config_empresa
                SET
                    razao_social = :razao_social,
                    nome_fantasia = :nome_fantasia,
                    cnpj = :cnpj,
                    inscricao_estadual = :inscricao_estadual,
                    email = :email,
                    telefone = :telefone,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    uf = :uf,
                    atualizado_em = NOW()
                WHERE id = :id
                LIMIT 1
            ";

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':razao_social', $razaoSocial);
            $stmtUpdate->bindValue(':nome_fantasia', $nomeFantasia);
            $stmtUpdate->bindValue(':cnpj', $cnpj);
            $stmtUpdate->bindValue(':inscricao_estadual', $inscricaoEstadual !== '' ? $inscricaoEstadual : null, $inscricaoEstadual !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':email', $email !== '' ? $email : null, $email !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':telefone', $telefone !== '' ? $telefone : null, $telefone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':cep', $cep !== '' ? $cep : null, $cep !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':logradouro', $logradouro !== '' ? $logradouro : null, $logradouro !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':numero', $numero !== '' ? $numero : null, $numero !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':complemento', $complemento !== '' ? $complemento : null, $complemento !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':bairro', $bairro !== '' ? $bairro : null, $bairro !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':cidade', $cidade !== '' ? $cidade : null, $cidade !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':uf', $uf !== '' ? $uf : null, $uf !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUpdate->execute();

            header('Location: form_config_empresa.php?sucesso=salvo');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Se não houver ID válido, verifica se já existe algum registro singleton
    |--------------------------------------------------------------------------
    */
    $sqlPrimeiro = "
        SELECT id
        FROM config_empresa
        ORDER BY id ASC
        LIMIT 1
    ";

    $stmtPrimeiro = $pdo->query($sqlPrimeiro);
    $registroExistente = $stmtPrimeiro->fetch(PDO::FETCH_ASSOC);

    if ($registroExistente) {
        $idExistente = (int)$registroExistente['id'];

        $sqlUpdate = "
            UPDATE config_empresa
            SET
                razao_social = :razao_social,
                nome_fantasia = :nome_fantasia,
                cnpj = :cnpj,
                inscricao_estadual = :inscricao_estadual,
                email = :email,
                telefone = :telefone,
                cep = :cep,
                logradouro = :logradouro,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                uf = :uf,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':razao_social', $razaoSocial);
        $stmtUpdate->bindValue(':nome_fantasia', $nomeFantasia);
        $stmtUpdate->bindValue(':cnpj', $cnpj);
        $stmtUpdate->bindValue(':inscricao_estadual', $inscricaoEstadual !== '' ? $inscricaoEstadual : null, $inscricaoEstadual !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':email', $email !== '' ? $email : null, $email !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':telefone', $telefone !== '' ? $telefone : null, $telefone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':cep', $cep !== '' ? $cep : null, $cep !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':logradouro', $logradouro !== '' ? $logradouro : null, $logradouro !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':numero', $numero !== '' ? $numero : null, $numero !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':complemento', $complemento !== '' ? $complemento : null, $complemento !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':bairro', $bairro !== '' ? $bairro : null, $bairro !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':cidade', $cidade !== '' ? $cidade : null, $cidade !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':uf', $uf !== '' ? $uf : null, $uf !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':id', $idExistente, PDO::PARAM_INT);
        $stmtUpdate->execute();

        header('Location: form_config_empresa.php?sucesso=salvo');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Se não existir nenhum registro, insere
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO config_empresa (
            razao_social,
            nome_fantasia,
            cnpj,
            inscricao_estadual,
            email,
            telefone,
            cep,
            logradouro,
            numero,
            complemento,
            bairro,
            cidade,
            uf,
            criado_em,
            atualizado_em
        ) VALUES (
            :razao_social,
            :nome_fantasia,
            :cnpj,
            :inscricao_estadual,
            :email,
            :telefone,
            :cep,
            :logradouro,
            :numero,
            :complemento,
            :bairro,
            :cidade,
            :uf,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':razao_social', $razaoSocial);
    $stmtInsert->bindValue(':nome_fantasia', $nomeFantasia);
    $stmtInsert->bindValue(':cnpj', $cnpj);
    $stmtInsert->bindValue(':inscricao_estadual', $inscricaoEstadual !== '' ? $inscricaoEstadual : null, $inscricaoEstadual !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':email', $email !== '' ? $email : null, $email !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':telefone', $telefone !== '' ? $telefone : null, $telefone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':cep', $cep !== '' ? $cep : null, $cep !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':logradouro', $logradouro !== '' ? $logradouro : null, $logradouro !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':numero', $numero !== '' ? $numero : null, $numero !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':complemento', $complemento !== '' ? $complemento : null, $complemento !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':bairro', $bairro !== '' ? $bairro : null, $bairro !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':cidade', $cidade !== '' ? $cidade : null, $cidade !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':uf', $uf !== '' ? $uf : null, $uf !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->execute();

    header('Location: form_config_empresa.php?sucesso=salvo');
    exit;

} catch (Throwable $e) {
    $redirecionar('erro_interno');
}