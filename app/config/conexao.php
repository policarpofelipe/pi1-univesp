<?php
declare(strict_types=1);

$host = 'localhost';
$db   = 'pi1_univesp';
$user = 'usuario_mysql';
$pass = 'senha_mysql';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erro ao conectar com o banco de dados.');
}
