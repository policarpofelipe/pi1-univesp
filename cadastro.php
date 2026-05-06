<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] > 0) {
    header('Location: consulta_veiculo.php');
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

                header('Location: consulta_veiculo.php');
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

$marcadorData = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Controle de Estoque</title>
    <style>
        *{box-sizing:border-box}
        body{
            margin:0;
            min-height:100vh;
            font-family: Inter, Arial, Helvetica, sans-serif;
            background:linear-gradient(135deg,#0f172a,#1e293b 55%,#0b1220);
            color:#0f172a;
            display:flex;
            padding:0;
        }
        .layout{
            width:100%;
            min-height:100vh;
            overflow:hidden;
            display:grid;
            grid-template-columns:minmax(350px,460px) 1fr;
            background:#fff;
        }
        .painel-form{
            background:#fff;
            padding:40px 34px;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }
        .logo{
            font-size:12px;
            letter-spacing:.12em;
            text-transform:uppercase;
            color:#475569;
            margin-bottom:10px;
        }
        h1{
            margin:0 0 8px 0;
            font-size:28px;
            line-height:1.15;
            color:#0f172a;
        }
        .subtitulo{
            margin:0 0 24px 0;
            font-size:14px;
            color:#64748b;
        }
        label{
            display:block;
            margin-bottom:6px;
            font-weight:600;
            font-size:13px;
            color:#334155;
        }
        input{
            width:100%;
            padding:11px 13px;
            margin-bottom:14px;
            border:1px solid #cbd5e1;
            border-radius:12px;
            font-size:14px;
            transition:.2s border-color,.2s box-shadow;
        }
        input:focus{
            outline:none;
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,.15);
        }
        button{
            width:100%;
            padding:12px 14px;
            border:none;
            border-radius:12px;
            background:#2563eb;
            color:#fff;
            font-size:14px;
            font-weight:700;
            cursor:pointer;
            transition:.2s background,.2s transform;
        }
        button:hover{background:#1d4ed8}
        button:active{transform:translateY(1px)}
        .erro{
            background:#fee2e2;
            color:#991b1b;
            border:1px solid #fecaca;
            padding:10px 12px;
            margin-bottom:16px;
            border-radius:12px;
            font-size:13px;
        }
        .sucesso{
            background:#dcfce7;
            color:#166534;
            border:1px solid #86efac;
            padding:10px 12px;
            margin-bottom:16px;
            border-radius:12px;
            font-size:13px;
        }
        .links{
            margin-top:16px;
            text-align:center;
            font-size:13px;
            color:#475569;
        }
        .links a{
            color:#1e293b;
            text-decoration:none;
            font-weight:700;
        }
        .links a:hover{text-decoration:underline}
        .banner{
            position:relative;
            color:#e2e8f0;
            background:
                radial-gradient(circle at 20% 20%, rgba(59,130,246,.45) 0, rgba(59,130,246,0) 44%),
                radial-gradient(circle at 80% 70%, rgba(14,116,144,.35) 0, rgba(14,116,144,0) 45%),
                linear-gradient(145deg,#0f172a,#1e293b 60%,#0b1220);
            padding:36px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
        }
        .badge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            border:1px solid rgba(148,163,184,.35);
            background:rgba(15,23,42,.35);
            color:#cbd5e1;
            border-radius:999px;
            padding:7px 12px;
            font-size:12px;
            width:max-content;
        }
        .banner h2{
            margin:18px 0 10px 0;
            font-size:34px;
            line-height:1.1;
            color:#f8fafc;
        }
        .banner p{margin:0 0 14px 0;font-size:14px;line-height:1.55}
        .lista{
            margin:0;
            padding-left:18px;
            font-size:13px;
            line-height:1.5;
        }
        .github-link{
            margin-top:20px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:max-content;
            padding:10px 14px;
            border-radius:12px;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
            color:#fff;
            background:#2563eb;
        }
        .github-link:hover{background:#1d4ed8}
        @media (max-width: 920px){
            .layout{grid-template-columns:1fr}
            .banner{order:-1;min-height:290px}
        }
    </style>
</head>
<body>
<div class="layout">
    <section class="painel-form">
        <div class="logo">Sistema de Controle de Estoque</div>
        <h1>Criar conta</h1>
        <p class="subtitulo">Cadastre seu acesso para iniciar no sistema.</p>

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
    </section>

    <aside class="banner">
        <div>
            <span class="badge">Marcador da apresentação: <?= esc($marcadorData) ?></span>
            <h2>Projeto Integrador PI.1</h2>
            <p><strong>Universidade Virtual do Estado de São Paulo</strong></p>
            <p><strong>Grupo 21</strong></p>
            <p><strong>Título:</strong> Sistema Web de Controle de Estoque de Autopeças com Associação de Aplicações Veiculares</p>
            <p><strong>Autores:</strong></p>
            <ul class="lista">
                <li>FABIO DIAS REZENDE CARVALHO</li>
                <li>FABIO ICCARO SILVESTRE DE ALMEIDA</li>
                <li>FELIPE MARTINS POLICARPO</li>
                <li>RENAN ESTEVES QUINTINO SILVA</li>
            </ul>
        </div>

        <a class="github-link" href="https://github.com/policarpofelipe/pi1-univesp" target="_blank" rel="noopener noreferrer">
            Ver repositório no GitHub
        </a>
    </aside>
</div>

</body>
</html>
