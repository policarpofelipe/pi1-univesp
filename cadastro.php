<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] > 0) {
    header('Location: painel.php');
    exit;
}

$erro = '';
$sucesso = '';

$nome  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome            = trim($_POST['nome'] ?? '');
    $email           = mb_strtolower(trim($_POST['email'] ?? ''));
    $senha           = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if ($nome === '' || $email === '' || $senha === '' || $confirmar_senha === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um email válido.';
    } elseif (mb_strlen($nome) < 3) {
        $erro = 'O nome deve ter ao menos 3 caracteres.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter ao menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } else {
        $sql = "SELECT id FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $usuarioExistente = $stmt->fetch();

        if ($usuarioExistente) {
            $erro = 'Já existe um usuário cadastrado com este email.';
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sqlInsert = "INSERT INTO usuarios (
                            nome,
                            email,
                            senha_hash,
                            ativo,
                            criado_em,
                            atualizado_em
                          ) VALUES (
                            :nome,
                            :email,
                            :senha_hash,
                            1,
                            NOW(),
                            NOW()
                          )";

            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->bindValue(':nome', $nome);
            $stmtInsert->bindValue(':email', $email);
            $stmtInsert->bindValue(':senha_hash', $senhaHash);

            if ($stmtInsert->execute()) {
                $usuarioId = (int)$pdo->lastInsertId();

                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuarioId;
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_email'] = $email;

                header('Location: painel.php');
                exit;
            } else {
                $erro = 'Não foi possível concluir o cadastro.';
            }
        }
    }
}

function esc($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Controle de Estoque</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            background:#f4f6f8;
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            box-sizing:border-box;
        }

        .container{
            width:100%;
            max-width:460px;
        }

        .card{
            background:#ffffff;
            padding:40px;
            border-radius:8px;
            box-shadow:0 5px 20px rgba(0,0,0,0.08);
        }

        h1{
            margin-top:0;
            margin-bottom:10px;
            font-size:24px;
            text-align:center;
            color:#111827;
        }

        .subtitulo{
            margin:0 0 24px 0;
            text-align:center;
            font-size:14px;
            color:#6b7280;
        }

        label{
            display:block;
            margin-bottom:6px;
            font-weight:bold;
            font-size:14px;
            color:#111827;
        }

        input{
            width:100%;
            padding:11px 12px;
            margin-bottom:16px;
            border:1px solid #d1d5db;
            border-radius:4px;
            font-size:14px;
            box-sizing:border-box;
        }

        input:focus{
            outline:none;
            border-color:#1f2937;
        }

        button{
            width:100%;
            padding:12px;
            border:none;
            border-radius:4px;
            background:#1f2937;
            color:white;
            font-size:15px;
            cursor:pointer;
        }

        button:hover{
            background:#111827;
        }

        .erro{
            background:#fee2e2;
            color:#991b1b;
            padding:10px 12px;
            margin-bottom:18px;
            border-radius:4px;
            font-size:14px;
        }

        .sucesso{
            background:#dcfce7;
            color:#166534;
            padding:10px 12px;
            margin-bottom:18px;
            border-radius:4px;
            font-size:14px;
        }

        .links{
            margin-top:18px;
            text-align:center;
            font-size:14px;
        }

        .links a{
            color:#1f2937;
            text-decoration:none;
            font-weight:bold;
        }

        .links a:hover{
            text-decoration:underline;
        }

        .projeto{
            margin-top:24px;
            padding:15px;
            background:#f8fafc;
            border-radius:6px;
            font-size:13px;
            color:#333;
            line-height:1.55;
        }

        .projeto strong{
            display:block;
            margin-bottom:8px;
            color:#111827;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Cadastre-se</h1>
        <p class="subtitulo">Crie seu acesso ao sistema</p>

        <?php if ($erro !== ''): ?>
            <div class="erro"><?= esc($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="sucesso"><?= esc($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" action="cadastro.php" autocomplete="off">
            <label for="nome">Nome</label>
            <input
                type="text"
                id="nome"
                name="nome"
                value="<?= esc($nome) ?>"
                required
                maxlength="150"
            >

            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= esc($email) ?>"
                required
                maxlength="150"
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                required
                minlength="6"
            >

            <label for="confirmar_senha">Confirmar senha</label>
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                required
                minlength="6"
            >

            <button type="submit">Criar conta</button>
        </form>

        <div class="links">
            Já tem acesso? <a href="login.php">Entrar</a>
        </div>

        <div class="projeto">
            <strong>Projeto Integrador UNIVESP — Grupo 21</strong>
            FELIPE BONIFACIO PERONA<br>
            FELIPE DA COSTA JARDIM<br>
            FABIO DIAS REZENDE CARVALHO<br>
            RENAN ESTEVES QUINTINO SILVA<br>
            FABIO ICCARO SILVESTRE DE ALMEIDA<br>
            FELIPE MARTINS POLICARPO<br>
            MARCOS PAULO DE CARVALHO GOMES<br>
            PACHELLI PERILLO BENVENUTI DE MORAES
        </div>

    </div>
</div>

</body>
</html>
