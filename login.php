<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    header("Location: painel.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe email e senha.';
    } else {

        $sql = "SELECT id, nome, email, senha_hash, ativo
                FROM usuarios
                WHERE email = :email
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $erro = 'Usuário ou senha inválidos.';
        } elseif ((int)$usuario['ativo'] !== 1) {
            $erro = 'Usuário inativo.';
        } elseif (!password_verify($senha, $usuario['senha_hash'])) {
            $erro = 'Usuário ou senha inválidos.';
        } else {

            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];

            header("Location: painel.php");
            exit;
        }
    }
}

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - Sistema de Estoque</title>

<style>

body{
    font-family: Arial, Helvetica, sans-serif;
    background:#f4f6f8;
    margin:0;
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.card{
    background:#ffffff;
    padding:40px;
    border-radius:8px;
    width:360px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

h1{
    margin-top:0;
    margin-bottom:20px;
    font-size:22px;
    text-align:center;
}

label{
    display:block;
    margin-bottom:6px;
    font-weight:bold;
    font-size:14px;
}

input{
    width:100%;
    padding:10px;
    margin-bottom:16px;
    border:1px solid #ccc;
    border-radius:4px;
    font-size:14px;
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
    background:#ffe4e4;
    color:#900;
    padding:10px;
    margin-bottom:15px;
    border-radius:4px;
    font-size:14px;
}

</style>

</head>
<body>

<div class="card">

<h1>Login</h1>

<?php if ($erro): ?>
<div class="erro"><?= esc($erro) ?></div>
<?php endif; ?>

<form method="POST">

<label>Email</label>
<input type="email" name="email" required>

<label>Senha</label>
<input type="password" name="senha" required>

<button type="submit">Entrar</button>

</form>

</div>

</body>
</html>