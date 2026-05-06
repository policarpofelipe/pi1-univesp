<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assistente_cadastro_produto.php?erro=erro_interno');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$assistenteId = (int)($_POST['assistente_id'] ?? 0);

if ($usuarioId <= 0 || $assistenteId <= 0) {
    header('Location: assistente_cadastro_produto.php?erro=assistente_invalido');
    exit;
}

$redirecionarErro = function (string $erro) use ($assistenteId): void {
    header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&erro=' . urlencode($erro));
    exit;
};

$stmtAssistente = $pdo->prepare("
    SELECT *
    FROM assistente_cadastro_produto
    WHERE id = :id
      AND usuario_id = :usuario_id
      AND status IN ('rascunho', 'em_andamento')
    LIMIT 1
");
$stmtAssistente->bindValue(':id', $assistenteId, PDO::PARAM_INT);
$stmtAssistente->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
$stmtAssistente->execute();
$assistente = $stmtAssistente->fetch(PDO::FETCH_ASSOC);
if (!$assistente) {
    $redirecionarErro('assistente_invalido');
}

$produtoId = (int)($assistente['produto_id'] ?? 0);
if ($produtoId <= 0) {
    $redirecionarErro('produto_base_invalido');
}

$stmtProduto = $pdo->prepare("
    SELECT p.*
    FROM produtos p
    WHERE p.id = :id
    LIMIT 1
");
$stmtProduto->bindValue(':id', $produtoId, PDO::PARAM_INT);
$stmtProduto->execute();
$produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);
if (!$produto) {
    $redirecionarErro('produto_base_invalido');
}

$produtoBaseValido = (int)($produto['ativo'] ?? 0) === 1
    && (int)($produto['tipo_peca_id'] ?? 0) > 0
    && (int)($produto['marca_produto_id'] ?? 0) > 0
    && trim((string)($produto['sku_interno'] ?? '')) !== ''
    && trim((string)($produto['codigo_fabricante'] ?? '')) !== ''
    && trim((string)($produto['nome_comercial'] ?? '')) !== '';

if (!$produtoBaseValido) {
    $redirecionarErro('produto_base_invalido');
}

$stmtAplic = $pdo->prepare("SELECT COUNT(*) FROM aplicacoes_produto WHERE produto_id = :produto_id AND ativo = 1");
$stmtAplic->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
$stmtAplic->execute();
$qtdAplicacoes = (int)$stmtAplic->fetchColumn();

$stmtMov = $pdo->prepare("SELECT COUNT(*) FROM movimentacoes_estoque WHERE produto_id = :produto_id");
$stmtMov->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
$stmtMov->execute();
$qtdMovimentacoes = (int)$stmtMov->fetchColumn();

$stmtImg = $pdo->prepare("SELECT COUNT(*) FROM produto_imagens WHERE produto_id = :produto_id");
$stmtImg->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
$stmtImg->execute();
$qtdImagens = (int)$stmtImg->fetchColumn();

$pendencias = [];
if ($qtdAplicacoes <= 0) {
    $pendencias['produto_sem_aplicabilidade'] = true;
}
if ($qtdMovimentacoes <= 0) {
    $pendencias['produto_sem_estoque_inicial'] = true;
}
if ($qtdImagens <= 0) {
    $pendencias['produto_sem_imagem'] = true;
}

$dadosJson = [];
if (!empty($assistente['dados_json'])) {
    $dec = json_decode((string)$assistente['dados_json'], true);
    if (is_array($dec)) {
        $dadosJson = $dec;
    }
}
$dadosJson['pendencias'] = $pendencias;

$up = $pdo->prepare("
    UPDATE assistente_cadastro_produto
    SET status = 'concluido',
        etapa_atual = 5,
        dados_json = :dados_json,
        concluido_em = NOW(),
        atualizado_em = NOW()
    WHERE id = :id
      AND usuario_id = :usuario_id
    LIMIT 1
");
$up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
$up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
$up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
$up->execute();

header('Location: form_produto.php?id=' . $produtoId . '&sucesso=cadastro_concluido_assistente');
exit;
