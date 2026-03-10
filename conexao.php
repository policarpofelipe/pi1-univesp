<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Conexão PDO com MySQL
|--------------------------------------------------------------------------
| Banco: flivocom_bd_pi1
| Usuário: flivocom_pi1_user
|--------------------------------------------------------------------------
*/

$host = 'localhost';
$banco = 'flivocom_bd_pi1';
$usuario = 'flivocom_pi1_user';
$senha = 'nvLC;t}c8vwi';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$banco};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $senha, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro ao conectar ao banco de dados.');
}