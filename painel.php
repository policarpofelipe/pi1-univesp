<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
</head>
<body>
    <h1>Painel</h1>
    <p>Olá, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></p>
    <p><a href="logout.php">Sair</a></p>
</body>
</html>