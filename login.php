<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    header("Location: consulta_veiculo.php");
    exit;
}

$erro = '';
$email = '';

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

            header("Location: consulta_veiculo.php");
            exit;
        }
    }
}

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$marcadorData = date('d/m/Y');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login - Sistema de Controle de Estoque</title>

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
    grid-template-columns: minmax(330px, 420px) 1fr;
    background:#ffffff;
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
.links{
    margin-top:16px;
    display:flex;
    justify-content:space-between;
    gap:10px;
    font-size:13px;
}
.links a{
    color:#1e293b;
    text-decoration:none;
    font-weight:600;
}
.links a:hover{text-decoration:underline}
.erro{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
    padding:10px 12px;
    margin-bottom:16px;
    border-radius:12px;
    font-size:13px;
}
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
        <h1>Acesso ao sistema</h1>
        <p class="subtitulo">Entre com suas credenciais para continuar.</p>

        <?php if ($erro): ?>
            <div class="erro"><?= esc($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required value="<?= esc($email) ?>">

            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" required>

            <button type="submit">Entrar</button>
        </form>

        <div class="links">
            <a href="cadastro.php">Cadastre-se</a>
            <a href="recuperar_senha.php">Esqueci minha senha</a>
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
