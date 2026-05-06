<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$assistenteId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($usuarioId <= 0 || $assistenteId <= 0) {
    header('Location: assistente_cadastro_produto.php?erro=assistente_invalido');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE assistente_cadastro_produto
    SET status = 'cancelado',
        cancelado_em = NOW(),
        atualizado_em = NOW()
    WHERE id = :id
      AND usuario_id = :usuario_id
      AND status IN ('rascunho', 'em_andamento')
    LIMIT 1
");
$stmt->bindValue(':id', $assistenteId, PDO::PARAM_INT);
$stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
$stmt->execute();

header('Location: painel.php');
exit;
