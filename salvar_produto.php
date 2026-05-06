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

$redirecionarForm = function (string $erro) use ($id): void {
    header('Location: form_produto.php?erro=' . $erro . ($id > 0 ? '&id=' . $id : ''));
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