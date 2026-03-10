<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Proteção de páginas autenticadas
|--------------------------------------------------------------------------
| Uso:
| require __DIR__ . '/auth.php';
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$usuarioLogado = isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] > 0;

if (!$usuarioLogado) {
    header('Location: login.php');
    exit;
}