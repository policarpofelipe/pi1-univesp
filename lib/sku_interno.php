<?php
declare(strict_types=1);

function gerarSugestaoSkuInterno(PDO $pdo): string
{
    $sql = "
        SELECT MAX(CAST(SUBSTRING(sku_interno, 5) AS UNSIGNED)) AS maior_numero
        FROM produtos
        WHERE sku_interno REGEXP '^SKU-[0-9]+$'
    ";

    $stmt = $pdo->query($sql);
    $maiorNumero = (int)($stmt->fetchColumn() ?: 0);
    $proximoNumero = $maiorNumero + 1;

    return 'SKU-' . str_pad((string)$proximoNumero, 6, '0', STR_PAD_LEFT);
}
