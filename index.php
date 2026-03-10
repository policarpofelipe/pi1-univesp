<?php
declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Ponto de entrada do sistema
|--------------------------------------------------------------------------
| Se existir sessão ativa → painel
| Caso contrário → tela de login
*/

if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    header("Location: painel.php");
    exit;
}

header("Location: login.php");
exit;