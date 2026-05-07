<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listar_produtos.php?erro=metodo_invalido');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$tipoPecaId = (int)($_POST['tipo_peca_id'] ?? 0);
$marcaProdutoId = (int)($_POST['marca_produto_id'] ?? 0);

$skuInterno = trim((string)($_POST['sku_interno'] ?? ''));
$codigoFabricante = trim((string)($_POST['codigo_fabricante'] ?? ''));
$codigoBarras = trim((string)($_POST['codigo_barras'] ?? ''));
$nomeComercial = trim((string)($_POST['nome_comercial'] ?? ''));
$descricao = trim((string)($_POST['descricao'] ?? ''));

$custo = (string)($_POST['custo'] ?? '0');
$preco = (string)($_POST['preco'] ?? '0');
$estoqueMinimo = (string)($_POST['estoque_minimo'] ?? '0');

$ativo = (string)($_POST['ativo'] ?? '1');
$ativo = ($ativo === '0') ? 0 : 1;

$estoqueModo = (string)($_POST['estoque_modo'] ?? 'existente');
if (!in_array($estoqueModo, ['existente', 'novo'], true)) {
    $estoqueModo = 'existente';
}
$estoqueId = (int)($_POST['estoque_id'] ?? 0);
$novoEstoqueNome = trim((string)($_POST['novo_estoque_nome'] ?? ''));
$novaLocalizacaoEstoque = trim((string)($_POST['nova_localizacao_estoque'] ?? ''));
$quantidadeInicialInformada = trim((string)($_POST['quantidade_inicial'] ?? ''));
$observacaoEstoque = trim((string)($_POST['observacao_estoque'] ?? ''));

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

$redirecionarForm = function (string $erro) use (
    $id,
    $estoqueModo,
    $estoqueId,
    $novoEstoqueNome,
    $novaLocalizacaoEstoque,
    $quantidadeInicialInformada,
    $observacaoEstoque
): void {
    $params = ['erro=' . urlencode($erro)];
    if ($id > 0) {
        $params[] = 'id=' . $id;
    }
    $params[] = 'estoque_modo=' . urlencode($estoqueModo);
    if ($estoqueId > 0) {
        $params[] = 'estoque_id=' . $estoqueId;
    }
    if ($novoEstoqueNome !== '') {
        $params[] = 'novo_estoque_nome=' . urlencode($novoEstoqueNome);
    }
    if ($novaLocalizacaoEstoque !== '') {
        $params[] = 'nova_localizacao_estoque=' . urlencode($novaLocalizacaoEstoque);
    }
    if ($quantidadeInicialInformada !== '') {
        $params[] = 'quantidade_inicial=' . urlencode($quantidadeInicialInformada);
    }
    if ($observacaoEstoque !== '') {
        $params[] = 'observacao_estoque=' . urlencode($observacaoEstoque);
    }
    header('Location: form_produto.php?' . implode('&', $params));
    exit;
};

if ($tipoPecaId <= 0) {
    $redirecionarForm('tipo_obrigatorio');
}

if ($marcaProdutoId <= 0) {
    $redirecionarForm('marca_obrigatoria');
}

if ($skuInterno === '') {
    $redirecionarForm('sku_obrigatorio');
}

if ($codigoFabricante === '') {
    $redirecionarForm('codigo_fabricante_obrigatorio');
}

if ($nomeComercial === '') {
    $redirecionarForm('nome_obrigatorio');
}

if (mb_strlen($skuInterno) > 60) {
    $redirecionarForm('sku_obrigatorio');
}

if (mb_strlen($codigoFabricante) > 100) {
    $redirecionarForm('codigo_fabricante_obrigatorio');
}

if (mb_strlen($codigoBarras) > 50) {
    $redirecionarForm('codigo_barras_duplicado');
}

if (mb_strlen($nomeComercial) > 180) {
    $redirecionarForm('nome_obrigatorio');
}

$custo = str_replace(',', '.', $custo);
$preco = str_replace(',', '.', $preco);
$estoqueMinimo = str_replace(',', '.', $estoqueMinimo);

if (!is_numeric($custo) || (float)$custo < 0) {
    $custo = '0.00';
}

if (!is_numeric($preco) || (float)$preco < 0) {
    $preco = '0.00';
}

if (!is_numeric($estoqueMinimo) || (int)$estoqueMinimo < 0) {
    $estoqueMinimo = '0';
}

$deveProcessarEstoqueInicial =
    $quantidadeInicialInformada !== '' ||
    $estoqueId > 0 ||
    $novoEstoqueNome !== '' ||
    $novaLocalizacaoEstoque !== '' ||
    $observacaoEstoque !== '';

$quantidadeInicial = 0.0;
if ($deveProcessarEstoqueInicial) {
    if ($quantidadeInicialInformada === '') {
        $redirecionarForm('quantidade_invalida');
    }
    $quantidadeInicialNormalizada = str_replace(',', '.', $quantidadeInicialInformada);
    if (!is_numeric($quantidadeInicialNormalizada)) {
        $redirecionarForm('quantidade_invalida');
    }
    $quantidadeInicial = (float)$quantidadeInicialNormalizada;
    if ($quantidadeInicial <= 0) {
        $redirecionarForm('quantidade_invalida');
    }
}

try {
    /*
    |--------------------------------------------------------------------------
    | Validar existência de tipo de peça
    |--------------------------------------------------------------------------
    */
    $sqlTipo = "
        SELECT id
        FROM tipos_peca
        WHERE id = :id
        LIMIT 1
    ";
    $stmtTipo = $pdo->prepare($sqlTipo);
    $stmtTipo->bindValue(':id', $tipoPecaId, PDO::PARAM_INT);
    $stmtTipo->execute();

    if (!$stmtTipo->fetch()) {
        $redirecionarForm('tipo_obrigatorio');
    }

    /*
    |--------------------------------------------------------------------------
    | Validar existência de marca de produto
    |--------------------------------------------------------------------------
    */
    $sqlMarca = "
        SELECT id
        FROM marcas_produto
        WHERE id = :id
        LIMIT 1
    ";
    $stmtMarca = $pdo->prepare($sqlMarca);
    $stmtMarca->bindValue(':id', $marcaProdutoId, PDO::PARAM_INT);
    $stmtMarca->execute();

    if (!$stmtMarca->fetch()) {
        $redirecionarForm('marca_obrigatoria');
    }

    if ($deveProcessarEstoqueInicial) {
        if ($estoqueModo === 'novo') {
            if ($novoEstoqueNome === '') {
                $redirecionarForm('estoque_obrigatorio');
            }
            $stmtNomeEstoque = $pdo->prepare("
                SELECT id
                FROM estoques
                WHERE LOWER(TRIM(nome)) = LOWER(TRIM(:nome))
                LIMIT 1
            ");
            $stmtNomeEstoque->bindValue(':nome', $novoEstoqueNome);
            $stmtNomeEstoque->execute();
            if ($stmtNomeEstoque->fetch()) {
                $redirecionarForm('estoque_duplicado');
            }
        } else {
            if ($estoqueId <= 0) {
                $redirecionarForm('estoque_obrigatorio');
            }
            $stmtEstoque = $pdo->prepare("
                SELECT id
                FROM estoques
                WHERE id = :id
                  AND ativo = 1
                LIMIT 1
            ");
            $stmtEstoque->bindValue(':id', $estoqueId, PDO::PARAM_INT);
            $stmtEstoque->execute();
            if (!$stmtEstoque->fetch()) {
                $redirecionarForm('estoque_obrigatorio');
            }
        }
    }

    if ($id > 0) {
        /*
        |--------------------------------------------------------------------------
        | Duplicidade de SKU na edição
        |--------------------------------------------------------------------------
        */
        $sqlSku = "
            SELECT id
            FROM produtos
            WHERE sku_interno = :sku_interno
              AND id <> :id
            LIMIT 1
        ";
        $stmtSku = $pdo->prepare($sqlSku);
        $stmtSku->bindValue(':sku_interno', $skuInterno);
        $stmtSku->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtSku->execute();

        if ($stmtSku->fetch()) {
            $redirecionarForm('sku_duplicado');
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicidade de código do fabricante por marca na edição
        |--------------------------------------------------------------------------
        */
        $sqlCodigoFabricante = "
            SELECT id
            FROM produtos
            WHERE marca_produto_id = :marca_produto_id
              AND codigo_fabricante = :codigo_fabricante
              AND id <> :id
            LIMIT 1
        ";
        $stmtCodigoFabricante = $pdo->prepare($sqlCodigoFabricante);
        $stmtCodigoFabricante->bindValue(':marca_produto_id', $marcaProdutoId, PDO::PARAM_INT);
        $stmtCodigoFabricante->bindValue(':codigo_fabricante', $codigoFabricante);
        $stmtCodigoFabricante->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtCodigoFabricante->execute();

        if ($stmtCodigoFabricante->fetch()) {
            $redirecionarForm('codigo_fabricante_duplicado');
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicidade de código de barras na edição
        |--------------------------------------------------------------------------
        */
        if ($codigoBarras !== '') {
            $sqlCodigoBarras = "
                SELECT id
                FROM produtos
                WHERE codigo_barras = :codigo_barras
                  AND id <> :id
                LIMIT 1
            ";
            $stmtCodigoBarras = $pdo->prepare($sqlCodigoBarras);
            $stmtCodigoBarras->bindValue(':codigo_barras', $codigoBarras);
            $stmtCodigoBarras->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtCodigoBarras->execute();

            if ($stmtCodigoBarras->fetch()) {
                $redirecionarForm('codigo_barras_duplicado');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Atualizar produto
        |--------------------------------------------------------------------------
        */
        $sqlUpdate = "
            UPDATE produtos
            SET
                tipo_peca_id = :tipo_peca_id,
                marca_produto_id = :marca_produto_id,
                sku_interno = :sku_interno,
                codigo_fabricante = :codigo_fabricante,
                codigo_barras = :codigo_barras,
                nome_comercial = :nome_comercial,
                descricao = :descricao,
                custo = :custo,
                preco = :preco,
                estoque_minimo = :estoque_minimo,
                ativo = :ativo,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':tipo_peca_id', $tipoPecaId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':marca_produto_id', $marcaProdutoId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':sku_interno', $skuInterno);
        $stmtUpdate->bindValue(':codigo_fabricante', $codigoFabricante);
        $stmtUpdate->bindValue(':codigo_barras', $codigoBarras !== '' ? $codigoBarras : null, $codigoBarras !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':nome_comercial', $nomeComercial);
        $stmtUpdate->bindValue(':descricao', $descricao !== '' ? $descricao : null, $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmtUpdate->bindValue(':custo', number_format((float)$custo, 2, '.', ''), PDO::PARAM_STR);
        $stmtUpdate->bindValue(':preco', number_format((float)$preco, 2, '.', ''), PDO::PARAM_STR);
        $stmtUpdate->bindValue(':estoque_minimo', (int)$estoqueMinimo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $produtoIdFinal = $id;

        if ($deveProcessarEstoqueInicial) {
            if ($estoqueModo === 'novo') {
                $stmtCriarEstoque = $pdo->prepare("
                    INSERT INTO estoques (nome, localizacao, ativo, criado_em, atualizado_em)
                    VALUES (:nome, :localizacao, 1, NOW(), NOW())
                ");
                $stmtCriarEstoque->bindValue(':nome', $novoEstoqueNome);
                $stmtCriarEstoque->bindValue(
                    ':localizacao',
                    $novaLocalizacaoEstoque !== '' ? $novaLocalizacaoEstoque : null,
                    $novaLocalizacaoEstoque !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
                );
                $stmtCriarEstoque->execute();
                $estoqueId = (int)$pdo->lastInsertId();
            }

            $prefixoObservacao = '[Form Produto] Estoque inicial';
            $observacaoMovimentacao = $prefixoObservacao . ($observacaoEstoque !== '' ? ' - ' . $observacaoEstoque : '');

            $stmtMovExistente = $pdo->prepare("
                SELECT id
                FROM movimentacoes_estoque
                WHERE produto_id = :produto_id
                  AND tipo_movimento = 'entrada'
                  AND observacao LIKE :prefixo
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmtMovExistente->bindValue(':produto_id', $produtoIdFinal, PDO::PARAM_INT);
            $stmtMovExistente->bindValue(':prefixo', $prefixoObservacao . '%');
            $stmtMovExistente->execute();
            $movExistente = $stmtMovExistente->fetch(PDO::FETCH_ASSOC);

            if ($movExistente) {
                $stmtAtualizarMov = $pdo->prepare("
                    UPDATE movimentacoes_estoque
                    SET estoque_id = :estoque_id,
                        usuario_id = :usuario_id,
                        quantidade = :quantidade,
                        observacao = :observacao
                    WHERE id = :id
                    LIMIT 1
                ");
                $stmtAtualizarMov->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
                $stmtAtualizarMov->bindValue(':usuario_id', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmtAtualizarMov->bindValue(':quantidade', number_format($quantidadeInicial, 2, '.', ''), PDO::PARAM_STR);
                $stmtAtualizarMov->bindValue(':observacao', $observacaoMovimentacao);
                $stmtAtualizarMov->bindValue(':id', (int)$movExistente['id'], PDO::PARAM_INT);
                $stmtAtualizarMov->execute();
            } else {
                $stmtInserirMov = $pdo->prepare("
                    INSERT INTO movimentacoes_estoque (
                        produto_id,
                        estoque_id,
                        usuario_id,
                        tipo_movimento,
                        quantidade,
                        custo_unitario,
                        observacao,
                        criado_em
                    ) VALUES (
                        :produto_id,
                        :estoque_id,
                        :usuario_id,
                        'entrada',
                        :quantidade,
                        NULL,
                        :observacao,
                        NOW()
                    )
                ");
                $stmtInserirMov->bindValue(':produto_id', $produtoIdFinal, PDO::PARAM_INT);
                $stmtInserirMov->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
                $stmtInserirMov->bindValue(':usuario_id', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmtInserirMov->bindValue(':quantidade', number_format($quantidadeInicial, 2, '.', ''), PDO::PARAM_STR);
                $stmtInserirMov->bindValue(':observacao', $observacaoMovimentacao);
                $stmtInserirMov->execute();
            }
        }

        header('Location: listar_produtos.php?sucesso=editado');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicidade de SKU no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlSku = "
        SELECT id
        FROM produtos
        WHERE sku_interno = :sku_interno
        LIMIT 1
    ";
    $stmtSku = $pdo->prepare($sqlSku);
    $stmtSku->bindValue(':sku_interno', $skuInterno);
    $stmtSku->execute();

    if ($stmtSku->fetch()) {
        $redirecionarForm('sku_duplicado');
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicidade de código do fabricante por marca no cadastro
    |--------------------------------------------------------------------------
    */
    $sqlCodigoFabricante = "
        SELECT id
        FROM produtos
        WHERE marca_produto_id = :marca_produto_id
          AND codigo_fabricante = :codigo_fabricante
        LIMIT 1
    ";
    $stmtCodigoFabricante = $pdo->prepare($sqlCodigoFabricante);
    $stmtCodigoFabricante->bindValue(':marca_produto_id', $marcaProdutoId, PDO::PARAM_INT);
    $stmtCodigoFabricante->bindValue(':codigo_fabricante', $codigoFabricante);
    $stmtCodigoFabricante->execute();

    if ($stmtCodigoFabricante->fetch()) {
        $redirecionarForm('codigo_fabricante_duplicado');
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicidade de código de barras no cadastro
    |--------------------------------------------------------------------------
    */
    if ($codigoBarras !== '') {
        $sqlCodigoBarras = "
            SELECT id
            FROM produtos
            WHERE codigo_barras = :codigo_barras
            LIMIT 1
        ";
        $stmtCodigoBarras = $pdo->prepare($sqlCodigoBarras);
        $stmtCodigoBarras->bindValue(':codigo_barras', $codigoBarras);
        $stmtCodigoBarras->execute();

        if ($stmtCodigoBarras->fetch()) {
            $redirecionarForm('codigo_barras_duplicado');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Inserir produto
    |--------------------------------------------------------------------------
    */
    $sqlInsert = "
        INSERT INTO produtos (
            tipo_peca_id,
            marca_produto_id,
            sku_interno,
            codigo_fabricante,
            codigo_barras,
            nome_comercial,
            descricao,
            custo,
            preco,
            estoque_minimo,
            ativo,
            criado_em,
            atualizado_em
        ) VALUES (
            :tipo_peca_id,
            :marca_produto_id,
            :sku_interno,
            :codigo_fabricante,
            :codigo_barras,
            :nome_comercial,
            :descricao,
            :custo,
            :preco,
            :estoque_minimo,
            :ativo,
            NOW(),
            NOW()
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->bindValue(':tipo_peca_id', $tipoPecaId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':marca_produto_id', $marcaProdutoId, PDO::PARAM_INT);
    $stmtInsert->bindValue(':sku_interno', $skuInterno);
    $stmtInsert->bindValue(':codigo_fabricante', $codigoFabricante);
    $stmtInsert->bindValue(':codigo_barras', $codigoBarras !== '' ? $codigoBarras : null, $codigoBarras !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':nome_comercial', $nomeComercial);
    $stmtInsert->bindValue(':descricao', $descricao !== '' ? $descricao : null, $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmtInsert->bindValue(':custo', number_format((float)$custo, 2, '.', ''), PDO::PARAM_STR);
    $stmtInsert->bindValue(':preco', number_format((float)$preco, 2, '.', ''), PDO::PARAM_STR);
    $stmtInsert->bindValue(':estoque_minimo', (int)$estoqueMinimo, PDO::PARAM_INT);
    $stmtInsert->bindValue(':ativo', $ativo, PDO::PARAM_INT);
    $stmtInsert->execute();

    $produtoIdFinal = (int)$pdo->lastInsertId();

    if ($deveProcessarEstoqueInicial) {
        if ($estoqueModo === 'novo') {
            $stmtCriarEstoque = $pdo->prepare("
                INSERT INTO estoques (nome, localizacao, ativo, criado_em, atualizado_em)
                VALUES (:nome, :localizacao, 1, NOW(), NOW())
            ");
            $stmtCriarEstoque->bindValue(':nome', $novoEstoqueNome);
            $stmtCriarEstoque->bindValue(
                ':localizacao',
                $novaLocalizacaoEstoque !== '' ? $novaLocalizacaoEstoque : null,
                $novaLocalizacaoEstoque !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmtCriarEstoque->execute();
            $estoqueId = (int)$pdo->lastInsertId();
        }

        $prefixoObservacao = '[Form Produto] Estoque inicial';
        $observacaoMovimentacao = $prefixoObservacao . ($observacaoEstoque !== '' ? ' - ' . $observacaoEstoque : '');

        $stmtInserirMov = $pdo->prepare("
            INSERT INTO movimentacoes_estoque (
                produto_id,
                estoque_id,
                usuario_id,
                tipo_movimento,
                quantidade,
                custo_unitario,
                observacao,
                criado_em
            ) VALUES (
                :produto_id,
                :estoque_id,
                :usuario_id,
                'entrada',
                :quantidade,
                NULL,
                :observacao,
                NOW()
            )
        ");
        $stmtInserirMov->bindValue(':produto_id', $produtoIdFinal, PDO::PARAM_INT);
        $stmtInserirMov->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
        $stmtInserirMov->bindValue(':usuario_id', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmtInserirMov->bindValue(':quantidade', number_format($quantidadeInicial, 2, '.', ''), PDO::PARAM_STR);
        $stmtInserirMov->bindValue(':observacao', $observacaoMovimentacao);
        $stmtInserirMov->execute();
    }

    header('Location: listar_produtos.php?sucesso=cadastrado');
    exit;

} catch (Throwable $e) {
    if ($e instanceof PDOException) {
        $mensagem = (string)$e->getMessage();
        if ($e->getCode() === '23000' && (stripos($mensagem, 'uq_produtos_sku_interno') !== false || stripos($mensagem, 'sku_interno') !== false)) {
            $redirecionarForm('sku_duplicado');
        }
    }
    $redirecionarForm('erro_interno');
}