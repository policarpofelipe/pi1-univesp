<?php
declare(strict_types=1);

const PRODUTO_IMAGENS_MAX_BYTES = 5 * 1024 * 1024;
const PRODUTO_IMAGENS_DIR_BASE = 'uploads/produtos';

/**
 * @return array<int, array<string, mixed>>
 */
function normalizarArrayFiles(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalizados = [];
    $total = count($files['name']);

    for ($i = 0; $i < $total; $i++) {
        $normalizados[] = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }

    return $normalizados;
}

function produtoExiste(PDO $pdo, int $produtoId): bool
{
    if ($produtoId <= 0) {
        return false;
    }

    $sql = "SELECT id FROM produtos WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $produtoId, PDO::PARAM_INT);
    $stmt->execute();

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @return array<int, array<string, mixed>>
 */
function listarImagensProduto(PDO $pdo, int $produtoId): array
{
    $sql = "
        SELECT
            id,
            produto_id,
            caminho_arquivo,
            nome_arquivo,
            nome_original,
            mime_type,
            tamanho_bytes,
            largura,
            altura,
            ordem,
            principal,
            alt_text,
            criado_em,
            atualizado_em
        FROM produto_imagens
        WHERE produto_id = :produto_id
        ORDER BY principal DESC, ordem ASC, id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @return array<string, mixed>|null
 */
function obterImagemProduto(PDO $pdo, int $produtoId, int $imagemId): ?array
{
    $sql = "
        SELECT
            id,
            produto_id,
            caminho_arquivo,
            nome_arquivo,
            nome_original,
            mime_type,
            tamanho_bytes,
            largura,
            altura,
            ordem,
            principal,
            alt_text,
            criado_em,
            atualizado_em
        FROM produto_imagens
        WHERE produto_id = :produto_id
          AND id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmt->bindValue(':id', $imagemId, PDO::PARAM_INT);
    $stmt->execute();

    $linha = $stmt->fetch(PDO::FETCH_ASSOC);
    return $linha ?: null;
}

function proximaOrdemImagem(PDO $pdo, int $produtoId): int
{
    $sql = "SELECT COALESCE(MAX(ordem), -1) + 1 FROM produto_imagens WHERE produto_id = :produto_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

/**
 * @return array{sucesso: int, erros: array<int, string>}
 */
function salvarImagensProduto(PDO $pdo, int $produtoId, array $files): array
{
    $resultado = ['sucesso' => 0, 'erros' => []];
    $itens = normalizarArrayFiles($files);

    foreach ($itens as $item) {
        if (($item['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $r = salvarUmaImagemProduto($pdo, $produtoId, $item);
        if (!($r['ok'] ?? false)) {
            $resultado['erros'][] = (string)($r['erro'] ?? 'Falha ao salvar imagem.');
            continue;
        }
        $resultado['sucesso']++;
    }

    if ($resultado['sucesso'] > 0) {
        promoverImagemPrincipalSeNecessario($pdo, $produtoId);
    }

    return $resultado;
}

/**
 * @param array<string, mixed> $file
 * @return array{ok: bool, erro?: string}
 */
function salvarUmaImagemProduto(PDO $pdo, int $produtoId, array $file): array
{
    if (!produtoExiste($pdo, $produtoId)) {
        return ['ok' => false, 'erro' => 'Produto inválido para upload de imagem.'];
    }

    $erro = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($erro !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'erro' => 'Falha no upload do arquivo.'];
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return ['ok' => false, 'erro' => 'Arquivo temporário inválido.'];
    }

    $nomeOriginal = trim((string)($file['name'] ?? ''));
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extensao, $permitidas, true)) {
        return ['ok' => false, 'erro' => 'Formato inválido. Use apenas JPG, JPEG, PNG ou WEBP.'];
    }

    $tamanho = (int)($file['size'] ?? 0);
    if ($tamanho <= 0 || $tamanho > PRODUTO_IMAGENS_MAX_BYTES) {
        return ['ok' => false, 'erro' => 'Arquivo fora do limite permitido (máximo 5 MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmpName);
    $mimesPermitidos = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    if (($mimesPermitidos[$extensao] ?? '') !== $mime) {
        return ['ok' => false, 'erro' => 'MIME inválido para a extensão informada.'];
    }

    $largura = null;
    $altura = null;
    $imgInfo = @getimagesize($tmpName);
    if (is_array($imgInfo)) {
        $largura = isset($imgInfo[0]) ? (int)$imgInfo[0] : null;
        $altura = isset($imgInfo[1]) ? (int)$imgInfo[1] : null;
    }

    $nomeSeguro = $produtoId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extensao;
    $baseDirAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'produtos';
    $produtoDirAbs = $baseDirAbs . DIRECTORY_SEPARATOR . $produtoId;

    if (!is_dir($produtoDirAbs) && !mkdir($produtoDirAbs, 0755, true) && !is_dir($produtoDirAbs)) {
        return ['ok' => false, 'erro' => 'Não foi possível preparar diretório de upload.'];
    }

    $destinoAbs = $produtoDirAbs . DIRECTORY_SEPARATOR . $nomeSeguro;
    $caminhoRelativo = str_replace('\\', '/', PRODUTO_IMAGENS_DIR_BASE . '/' . $produtoId . '/' . $nomeSeguro);

    if (!move_uploaded_file($tmpName, $destinoAbs)) {
        return ['ok' => false, 'erro' => 'Não foi possível salvar o arquivo no servidor.'];
    }

    $ordem = proximaOrdemImagem($pdo, $produtoId);

    $sql = "
        INSERT INTO produto_imagens (
            produto_id,
            caminho_arquivo,
            nome_arquivo,
            nome_original,
            mime_type,
            tamanho_bytes,
            largura,
            altura,
            ordem,
            principal,
            alt_text,
            criado_em,
            atualizado_em
        ) VALUES (
            :produto_id,
            :caminho_arquivo,
            :nome_arquivo,
            :nome_original,
            :mime_type,
            :tamanho_bytes,
            :largura,
            :altura,
            :ordem,
            0,
            NULL,
            NOW(),
            NOW()
        )
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':caminho_arquivo', $caminhoRelativo, PDO::PARAM_STR);
        $stmt->bindValue(':nome_arquivo', $nomeSeguro, PDO::PARAM_STR);
        $stmt->bindValue(':nome_original', $nomeOriginal !== '' ? $nomeOriginal : null, $nomeOriginal !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':mime_type', $mime, PDO::PARAM_STR);
        $stmt->bindValue(':tamanho_bytes', $tamanho, PDO::PARAM_INT);
        $stmt->bindValue(':largura', $largura, $largura !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':altura', $altura, $altura !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ordem', $ordem, PDO::PARAM_INT);
        $stmt->execute();
    } catch (Throwable $e) {
        if (is_file($destinoAbs)) {
            @unlink($destinoAbs);
        }
        return ['ok' => false, 'erro' => 'Erro ao registrar imagem no banco de dados.'];
    }

    return ['ok' => true];
}

function definirImagemPrincipal(PDO $pdo, int $produtoId, int $imagemId): bool
{
    $imagem = obterImagemProduto($pdo, $produtoId, $imagemId);
    if (!$imagem) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $sqlLimpar = "UPDATE produto_imagens SET principal = 0, atualizado_em = NOW() WHERE produto_id = :produto_id";
        $stmtLimpar = $pdo->prepare($sqlLimpar);
        $stmtLimpar->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmtLimpar->execute();

        $sqlPrincipal = "
            UPDATE produto_imagens
            SET principal = 1, atualizado_em = NOW()
            WHERE produto_id = :produto_id
              AND id = :id
            LIMIT 1
        ";
        $stmtPrincipal = $pdo->prepare($sqlPrincipal);
        $stmtPrincipal->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmtPrincipal->bindValue(':id', $imagemId, PDO::PARAM_INT);
        $stmtPrincipal->execute();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }

    return true;
}

function promoverImagemPrincipalSeNecessario(PDO $pdo, int $produtoId): void
{
    $sqlExistePrincipal = "
        SELECT id
        FROM produto_imagens
        WHERE produto_id = :produto_id
          AND principal = 1
        LIMIT 1
    ";
    $stmtPrincipal = $pdo->prepare($sqlExistePrincipal);
    $stmtPrincipal->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmtPrincipal->execute();
    if ($stmtPrincipal->fetch(PDO::FETCH_ASSOC)) {
        return;
    }

    $sqlPrimeira = "
        SELECT id
        FROM produto_imagens
        WHERE produto_id = :produto_id
        ORDER BY ordem ASC, id ASC
        LIMIT 1
    ";
    $stmtPrimeira = $pdo->prepare($sqlPrimeira);
    $stmtPrimeira->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
    $stmtPrimeira->execute();
    $primeira = $stmtPrimeira->fetch(PDO::FETCH_ASSOC);

    if (!$primeira) {
        return;
    }

    definirImagemPrincipal($pdo, $produtoId, (int)$primeira['id']);
}

function excluirImagemProduto(PDO $pdo, int $produtoId, int $imagemId): bool
{
    $imagem = obterImagemProduto($pdo, $produtoId, $imagemId);
    if (!$imagem) {
        return false;
    }

    $caminhoRelativo = (string)$imagem['caminho_arquivo'];
    $caminhoAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminhoRelativo);

    $pdo->beginTransaction();
    try {
        $sql = "DELETE FROM produto_imagens WHERE produto_id = :produto_id AND id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $imagemId, PDO::PARAM_INT);
        $stmt->execute();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }

    if (is_file($caminhoAbs)) {
        @unlink($caminhoAbs);
    }

    promoverImagemPrincipalSeNecessario($pdo, $produtoId);
    return true;
}
