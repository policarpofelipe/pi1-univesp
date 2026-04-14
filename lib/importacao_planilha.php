<?php

declare(strict_types=1);

/**
 * Leitura, validação e metadados para importação por planilha (.xls / .xlsx).
 */

function importacao_planilha_autoload(): void
{
    static $carregado = false;
    if ($carregado) {
        return;
    }
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException(
            'Dependência PhpSpreadsheet ausente. Execute na raiz do projeto: composer install'
        );
    }
    require_once $autoload;
    $carregado = true;
}

/**
 * @return array<string, array<string, mixed>>
 */
function importacao_planilha_tipos(): array
{
    return [
        'marcas_produto' => [
            'titulo'          => 'Marcas de produto',
            'volta_lista'     => 'listar_marcas_produto.php',
            'cabecalho'       => ['nome'],
            'limites'         => ['nome' => 100],
            'linhas_exemplo'  => [['nome' => 'Ex.: Bosch']],
        ],
        'categorias_peca' => [
            'titulo'          => 'Categorias de peça',
            'volta_lista'     => 'listar_categorias_peca.php',
            'cabecalho'       => ['nome', 'descricao'],
            'limites'         => ['nome' => 100, 'descricao' => 255],
            'linhas_exemplo'  => [
                ['nome' => 'Ex.: Filtros', 'descricao' => 'Opcional: linha de filtros de ar/lubrificantes'],
            ],
        ],
        'estoques' => [
            'titulo'          => 'Locais de estoque',
            'volta_lista'     => 'listar_estoques.php',
            'cabecalho'       => ['nome', 'localizacao'],
            'limites'         => ['nome' => 100, 'localizacao' => 150],
            'linhas_exemplo'  => [
                ['nome' => 'Ex.: Depósito A', 'localizacao' => 'Opcional: setor norte'],
            ],
        ],
        'tipos_peca' => [
            'titulo'          => 'Tipos de peça',
            'volta_lista'     => 'listar_tipos_peca.php',
            'cabecalho'       => ['categoria_nome', 'nome', 'descricao'],
            'limites'         => ['categoria_nome' => 100, 'nome' => 150, 'descricao' => 255],
            'linhas_exemplo'  => [
                ['categoria_nome' => 'Ex.: Filtros', 'nome' => 'Ex.: Filtro de óleo', 'descricao' => 'Opcional'],
            ],
        ],
        'produtos' => [
            'titulo'          => 'Produtos',
            'volta_lista'     => 'listar_produtos.php',
            'cabecalho'       => [
                'tipo_peca_nome',
                'marca_produto_nome',
                'sku_interno',
                'codigo_fabricante',
                'codigo_barras',
                'nome_comercial',
                'descricao',
                'custo',
                'preco',
                'estoque_minimo',
            ],
            'limites'         => [
                'tipo_peca_nome' => 150,
                'marca_produto_nome' => 100,
                'sku_interno' => 60,
                'codigo_fabricante' => 100,
                'codigo_barras' => 50,
                'nome_comercial' => 180,
                'descricao' => 65535,
            ],
            'linhas_exemplo'  => [[
                'tipo_peca_nome' => 'Escolha da lista',
                'marca_produto_nome' => 'Escolha da lista',
                'sku_interno' => 'SKU-0001',
                'codigo_fabricante' => 'FAB-0001',
                'codigo_barras' => '',
                'nome_comercial' => 'Filtro de óleo premium',
                'descricao' => 'Opcional',
                'custo' => '10.50',
                'preco' => '18.90',
                'estoque_minimo' => '5',
            ]],
        ],
    ];
}

function importacao_planilha_tipo_valido(string $tipo): bool
{
    return isset(importacao_planilha_tipos()[$tipo]);
}

/**
 * @return array<string, mixed>|null
 */
function importacao_planilha_config(string $tipo): ?array
{
    return importacao_planilha_tipos()[$tipo] ?? null;
}

function importacao_planilha_normalizar_celula(mixed $valor): string
{
    if ($valor === null) {
        return '';
    }
    if (is_numeric($valor) && !is_string($valor)) {
        return trim((string)$valor);
    }
    return trim((string)$valor);
}

/**
 * @param array<int, string> $cabecalhoArquivo
 * @param array<int, string> $cabecalhoEsperado
 */
function importacao_planilha_cabecalho_confere(array $cabecalhoArquivo, array $cabecalhoEsperado): bool
{
    if (count($cabecalhoArquivo) !== count($cabecalhoEsperado)) {
        return false;
    }
    foreach ($cabecalhoEsperado as $i => $esperado) {
        $obtido = $cabecalhoArquivo[$i] ?? '';
        if (mb_strtolower($obtido) !== mb_strtolower($esperado)) {
            return false;
        }
    }
    return true;
}

/**
 * Lê a primeira aba e devolve cabeçalho + linhas associativas.
 *
 * @return array{erro?: string, cabecalho?: array<int, string>, linhas?: array<int, array<string, string>>}
 */
function importacao_planilha_ler_arquivo(string $caminho, string $tipo, ?string $nomeOriginal = null): array
{
    importacao_planilha_autoload();

    $cfg = importacao_planilha_config($tipo);
    if ($cfg === null) {
        return ['erro' => 'Tipo de importação inválido.'];
    }

    $nomeParaValidacao = $nomeOriginal !== null && $nomeOriginal !== '' ? $nomeOriginal : $caminho;
    $ext = strtolower(pathinfo($nomeParaValidacao, PATHINFO_EXTENSION));
    if (!in_array($ext, ['xls', 'xlsx'], true)) {
        return ['erro' => 'Envie um arquivo .xls ou .xlsx.'];
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($caminho);
    } catch (Throwable $e) {
        return ['erro' => 'Não foi possível ler a planilha. Verifique se o arquivo não está corrompido.'];
    }

    $sheet = $spreadsheet->getActiveSheet();
    $matriz = $sheet->toArray(null, true, true, false);
    if ($matriz === [] || $matriz === [[]]) {
        return ['erro' => 'A planilha está vazia.'];
    }

    $primeira = array_shift($matriz);
    $cabecalhoArquivo = [];
    foreach ($primeira as $cel) {
        $cabecalhoArquivo[] = importacao_planilha_normalizar_celula($cel);
    }
    // remove colunas vazias no fim do cabeçalho
    while ($cabecalhoArquivo !== [] && end($cabecalhoArquivo) === '') {
        array_pop($cabecalhoArquivo);
    }

    $esperado = $cfg['cabecalho'];
    if (!importacao_planilha_cabecalho_confere($cabecalhoArquivo, $esperado)) {
        return [
            'erro' => 'Cabeçalho inválido. A primeira linha deve ser exatamente: '
                . implode(' | ', $esperado)
                . ' (mesma ordem do modelo).',
        ];
    }

    $linhas = [];
    $numLinhaPlanilha = 2;
    $maxLinhas = 1000;
    $contagem = 0;

    foreach ($matriz as $linhaBruta) {
        if ($contagem >= $maxLinhas) {
            break;
        }
        $assoc = [];
        $algumPreenchido = false;
        foreach ($esperado as $idx => $chave) {
            $valor = $linhaBruta[$idx] ?? '';
            $assoc[$chave] = importacao_planilha_normalizar_celula($valor);
            if ($assoc[$chave] !== '') {
                $algumPreenchido = true;
            }
        }
        if (!$algumPreenchido) {
            $numLinhaPlanilha++;
            continue;
        }
        $linhas[$numLinhaPlanilha] = $assoc;
        $numLinhaPlanilha++;
        $contagem++;
    }

    if ($linhas === []) {
        return ['erro' => 'Nenhuma linha de dados encontrada (após o cabeçalho).'];
    }

    return [
        'cabecalho' => $cabecalhoArquivo,
        'linhas'    => $linhas,
    ];
}

/**
 * @param array<int, array<string, string>> $linhas chave = número da linha na planilha
 * @return array<int, array{linha: int, dados: array<string, string>, importavel: bool, erros: string[]}>
 */
function importacao_planilha_validar_linhas(PDO $pdo, string $tipo, array $linhas): array
{
    $cfg = importacao_planilha_config($tipo);
    if ($cfg === null) {
        return [];
    }

    $limites = $cfg['limites'];
    $existentesDb = importacao_planilha_nomes_existentes_no_banco($pdo, $tipo);
    $mapaCategorias = $tipo === 'tipos_peca' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'categorias_peca') : [];
    $mapaTipos = $tipo === 'produtos' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'tipos_peca') : [];
    $mapaMarcas = $tipo === 'produtos' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'marcas_produto') : [];
    $vistosArquivo = [];

    $resultado = [];
    foreach ($linhas as $numLinha => $dados) {
        $erros = [];

        if (in_array($tipo, ['marcas_produto', 'categorias_peca', 'estoques'], true)) {
            $nome = $dados['nome'] ?? '';
            if ($nome === '') {
                $erros[] = 'Nome é obrigatório.';
            } elseif (isset($limites['nome']) && mb_strlen($nome) > $limites['nome']) {
                $erros[] = 'Nome ultrapassa ' . $limites['nome'] . ' caracteres.';
            }
            $chaveDup = mb_strtolower($nome);
            if ($nome !== '') {
                if (isset($vistosArquivo[$chaveDup])) {
                    $erros[] = 'Nome repetido na planilha (linha ' . $vistosArquivo[$chaveDup] . ').';
                } else {
                    $vistosArquivo[$chaveDup] = $numLinha;
                }
                if (isset($existentesDb[$chaveDup])) {
                    $erros[] = 'Já existe cadastro com este nome.';
                }
            }
        }

        if ($tipo === 'categorias_peca') {
            $desc = $dados['descricao'] ?? '';
            if ($desc !== '' && isset($limites['descricao']) && mb_strlen($desc) > $limites['descricao']) {
                $erros[] = 'Descrição ultrapassa ' . $limites['descricao'] . ' caracteres.';
            }
        }

        if ($tipo === 'estoques') {
            $loc = $dados['localizacao'] ?? '';
            if ($loc !== '' && isset($limites['localizacao']) && mb_strlen($loc) > $limites['localizacao']) {
                $erros[] = 'Localização ultrapassa ' . $limites['localizacao'] . ' caracteres.';
            }
        }

        if ($tipo === 'tipos_peca') {
            $categoriaNome = trim((string)($dados['categoria_nome'] ?? ''));
            $nome = trim((string)($dados['nome'] ?? ''));
            $desc = trim((string)($dados['descricao'] ?? ''));

            if ($categoriaNome === '') {
                $erros[] = 'Categoria é obrigatória.';
            } elseif (mb_strlen($categoriaNome) > (int)$limites['categoria_nome']) {
                $erros[] = 'Categoria ultrapassa ' . $limites['categoria_nome'] . ' caracteres.';
            }

            if ($nome === '') {
                $erros[] = 'Nome é obrigatório.';
            } elseif (mb_strlen($nome) > (int)$limites['nome']) {
                $erros[] = 'Nome ultrapassa ' . $limites['nome'] . ' caracteres.';
            }

            if ($desc !== '' && mb_strlen($desc) > (int)$limites['descricao']) {
                $erros[] = 'Descrição ultrapassa ' . $limites['descricao'] . ' caracteres.';
            }

            $catKey = mb_strtolower($categoriaNome);
            $nomeKey = mb_strtolower($nome);
            if ($categoriaNome !== '' && !isset($mapaCategorias[$catKey])) {
                $erros[] = 'Categoria não encontrada no cadastro.';
            }

            if ($categoriaNome !== '' && $nome !== '' && isset($mapaCategorias[$catKey])) {
                $dupKey = $mapaCategorias[$catKey] . '|' . $nomeKey;
                if (isset($vistosArquivo[$dupKey])) {
                    $erros[] = 'Tipo repetido para a mesma categoria na linha ' . $vistosArquivo[$dupKey] . '.';
                } else {
                    $vistosArquivo[$dupKey] = $numLinha;
                }
                if (isset($existentesDb[$dupKey])) {
                    $erros[] = 'Já existe tipo com este nome para a categoria informada.';
                }
            }
        }

        if ($tipo === 'produtos') {
            $tipoNome = trim((string)($dados['tipo_peca_nome'] ?? ''));
            $marcaNome = trim((string)($dados['marca_produto_nome'] ?? ''));
            $sku = trim((string)($dados['sku_interno'] ?? ''));
            $codigoFabricante = trim((string)($dados['codigo_fabricante'] ?? ''));
            $codigoBarras = trim((string)($dados['codigo_barras'] ?? ''));
            $nomeComercial = trim((string)($dados['nome_comercial'] ?? ''));
            $descricao = trim((string)($dados['descricao'] ?? ''));
            $custo = trim((string)($dados['custo'] ?? '0'));
            $preco = trim((string)($dados['preco'] ?? '0'));
            $estoqueMinimo = trim((string)($dados['estoque_minimo'] ?? '0'));

            if ($tipoNome === '') {
                $erros[] = 'Tipo de peça é obrigatório.';
            } elseif (!isset($mapaTipos[mb_strtolower($tipoNome)])) {
                $erros[] = 'Tipo de peça não encontrado.';
            }
            if ($marcaNome === '') {
                $erros[] = 'Marca do produto é obrigatória.';
            } elseif (!isset($mapaMarcas[mb_strtolower($marcaNome)])) {
                $erros[] = 'Marca do produto não encontrada.';
            }
            if ($sku === '') {
                $erros[] = 'SKU interno é obrigatório.';
            } elseif (mb_strlen($sku) > (int)$limites['sku_interno']) {
                $erros[] = 'SKU interno ultrapassa ' . $limites['sku_interno'] . ' caracteres.';
            }
            if ($codigoFabricante === '') {
                $erros[] = 'Código do fabricante é obrigatório.';
            } elseif (mb_strlen($codigoFabricante) > (int)$limites['codigo_fabricante']) {
                $erros[] = 'Código do fabricante ultrapassa ' . $limites['codigo_fabricante'] . ' caracteres.';
            }
            if ($codigoBarras !== '' && mb_strlen($codigoBarras) > (int)$limites['codigo_barras']) {
                $erros[] = 'Código de barras ultrapassa ' . $limites['codigo_barras'] . ' caracteres.';
            }
            if ($nomeComercial === '') {
                $erros[] = 'Nome comercial é obrigatório.';
            } elseif (mb_strlen($nomeComercial) > (int)$limites['nome_comercial']) {
                $erros[] = 'Nome comercial ultrapassa ' . $limites['nome_comercial'] . ' caracteres.';
            }
            if ($descricao !== '' && mb_strlen($descricao) > (int)$limites['descricao']) {
                $erros[] = 'Descrição ultrapassa ' . $limites['descricao'] . ' caracteres.';
            }

            $custoNorm = str_replace(',', '.', $custo);
            $precoNorm = str_replace(',', '.', $preco);
            $estoqueNorm = str_replace(',', '.', $estoqueMinimo);
            if (!is_numeric($custoNorm) || (float)$custoNorm < 0) {
                $erros[] = 'Custo deve ser numérico e >= 0.';
            } else {
                $dados['custo'] = number_format((float)$custoNorm, 2, '.', '');
            }
            if (!is_numeric($precoNorm) || (float)$precoNorm < 0) {
                $erros[] = 'Preço deve ser numérico e >= 0.';
            } else {
                $dados['preco'] = number_format((float)$precoNorm, 2, '.', '');
            }
            if (!preg_match('/^\d+$/', $estoqueNorm)) {
                $erros[] = 'Estoque mínimo deve ser inteiro >= 0.';
            } else {
                $dados['estoque_minimo'] = (string)((int)$estoqueNorm);
            }

            $skuKey = mb_strtolower($sku);
            if ($sku !== '') {
                if (isset($vistosArquivo['sku:' . $skuKey])) {
                    $erros[] = 'SKU repetido na planilha (linha ' . $vistosArquivo['sku:' . $skuKey] . ').';
                } else {
                    $vistosArquivo['sku:' . $skuKey] = $numLinha;
                }
                if (isset($existentesDb['sku:' . $skuKey])) {
                    $erros[] = 'SKU já cadastrado.';
                }
            }

            if ($marcaNome !== '' && $codigoFabricante !== '' && isset($mapaMarcas[mb_strtolower($marcaNome)])) {
                $chaveMarcaCodigo = $mapaMarcas[mb_strtolower($marcaNome)] . '|' . mb_strtolower($codigoFabricante);
                if (isset($vistosArquivo['mc:' . $chaveMarcaCodigo])) {
                    $erros[] = 'Código fabricante repetido para a mesma marca na linha ' . $vistosArquivo['mc:' . $chaveMarcaCodigo] . '.';
                } else {
                    $vistosArquivo['mc:' . $chaveMarcaCodigo] = $numLinha;
                }
                if (isset($existentesDb['mc:' . $chaveMarcaCodigo])) {
                    $erros[] = 'Código fabricante já cadastrado para esta marca.';
                }
            }

            if ($codigoBarras !== '') {
                $cbKey = mb_strtolower($codigoBarras);
                if (isset($vistosArquivo['cb:' . $cbKey])) {
                    $erros[] = 'Código de barras repetido na planilha (linha ' . $vistosArquivo['cb:' . $cbKey] . ').';
                } else {
                    $vistosArquivo['cb:' . $cbKey] = $numLinha;
                }
                if (isset($existentesDb['cb:' . $cbKey])) {
                    $erros[] = 'Código de barras já cadastrado.';
                }
            }
        }

        $resultado[] = [
            'linha'       => $numLinha,
            'dados'       => $dados,
            'importavel'  => $erros === [],
            'erros'       => $erros,
        ];
    }

    return $resultado;
}

/**
 * @return array<string, true> chave = mb_strtolower(trim(nome))
 */
function importacao_planilha_nomes_existentes_no_banco(PDO $pdo, string $tipo): array
{
    if ($tipo === 'tipos_peca') {
        $stmt = $pdo->query('SELECT categoria_peca_id, LOWER(TRIM(nome)) AS nome FROM tipos_peca');
        $mapa = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $k = (int)$row['categoria_peca_id'] . '|' . (string)$row['nome'];
            $mapa[$k] = true;
        }
        return $mapa;
    }

    if ($tipo === 'produtos') {
        $mapa = [];
        $stmt = $pdo->query('SELECT LOWER(TRIM(sku_interno)) AS sku, marca_produto_id, LOWER(TRIM(codigo_fabricante)) AS codigo_fabricante, LOWER(TRIM(COALESCE(codigo_barras, ""))) AS codigo_barras FROM produtos');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sku = (string)($row['sku'] ?? '');
            $mc = (int)($row['marca_produto_id'] ?? 0) . '|' . (string)($row['codigo_fabricante'] ?? '');
            $cb = (string)($row['codigo_barras'] ?? '');
            if ($sku !== '') {
                $mapa['sku:' . $sku] = true;
            }
            if ($mc !== '0|') {
                $mapa['mc:' . $mc] = true;
            }
            if ($cb !== '') {
                $mapa['cb:' . $cb] = true;
            }
        }
        return $mapa;
    }

    $tabela = match ($tipo) {
        'marcas_produto'  => 'marcas_produto',
        'categorias_peca' => 'categorias_peca',
        'estoques'        => 'estoques',
        default           => null,
    };
    if ($tabela === null) {
        return [];
    }
    $stmt = $pdo->query('SELECT LOWER(TRIM(nome)) AS k FROM `' . $tabela . '`');
    $mapa = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $k = (string)($row['k'] ?? '');
        if ($k !== '') {
            $mapa[$k] = true;
        }
    }
    return $mapa;
}

/**
 * @return array<string, int> chave = nome (lower trim), valor = id
 */
function importacao_planilha_mapa_nomes_para_ids(PDO $pdo, string $tabela): array
{
    $permitidas = ['categorias_peca', 'tipos_peca', 'marcas_produto'];
    if (!in_array($tabela, $permitidas, true)) {
        return [];
    }
    $stmt = $pdo->query('SELECT id, LOWER(TRIM(nome)) AS nome FROM `' . $tabela . '`');
    $mapa = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nome = (string)($row['nome'] ?? '');
        if ($nome !== '') {
            $mapa[$nome] = (int)$row['id'];
        }
    }
    return $mapa;
}

/**
 * @param array<int, array{linha: int, dados: array<string, string>, importavel: bool, erros: string[]}> $validadas
 */
function importacao_planilha_gravar(PDO $pdo, string $tipo, array $validadas): int
{
    $inseridos = 0;
    $categorias = $tipo === 'tipos_peca' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'categorias_peca') : [];
    $tipos = $tipo === 'produtos' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'tipos_peca') : [];
    $marcas = $tipo === 'produtos' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'marcas_produto') : [];
    $pdo->beginTransaction();
    try {
        foreach ($validadas as $item) {
            if (!$item['importavel']) {
                continue;
            }
            $d = $item['dados'];
            if ($tipo === 'marcas_produto') {
                $sql = 'INSERT INTO marcas_produto (nome, ativo, criado_em, atualizado_em) VALUES (:nome, 1, NOW(), NOW())';
                $st = $pdo->prepare($sql);
                $st->bindValue(':nome', $d['nome']);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'categorias_peca') {
                $sql = 'INSERT INTO categorias_peca (nome, descricao, ativo, criado_em, atualizado_em) VALUES (:nome, :descricao, 1, NOW(), NOW())';
                $st = $pdo->prepare($sql);
                $st->bindValue(':nome', $d['nome']);
                $desc = ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null;
                $st->bindValue(':descricao', $desc, $desc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'estoques') {
                $sql = 'INSERT INTO estoques (nome, localizacao, ativo, criado_em, atualizado_em) VALUES (:nome, :localizacao, 1, NOW(), NOW())';
                $st = $pdo->prepare($sql);
                $st->bindValue(':nome', $d['nome']);
                $loc = ($d['localizacao'] ?? '') !== '' ? $d['localizacao'] : null;
                $st->bindValue(':localizacao', $loc, $loc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'tipos_peca') {
                $categoriaId = $categorias[mb_strtolower(trim((string)$d['categoria_nome']))] ?? 0;
                if ($categoriaId <= 0) {
                    continue;
                }
                $sql = 'INSERT INTO tipos_peca (categoria_peca_id, nome, descricao, ativo, criado_em, atualizado_em) VALUES (:categoria_peca_id, :nome, :descricao, 1, NOW(), NOW())';
                $st = $pdo->prepare($sql);
                $st->bindValue(':categoria_peca_id', $categoriaId, PDO::PARAM_INT);
                $st->bindValue(':nome', $d['nome']);
                $desc = ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null;
                $st->bindValue(':descricao', $desc, $desc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'produtos') {
                $tipoId = $tipos[mb_strtolower(trim((string)$d['tipo_peca_nome']))] ?? 0;
                $marcaId = $marcas[mb_strtolower(trim((string)$d['marca_produto_nome']))] ?? 0;
                if ($tipoId <= 0 || $marcaId <= 0) {
                    continue;
                }
                $sql = 'INSERT INTO produtos (tipo_peca_id, marca_produto_id, sku_interno, codigo_fabricante, codigo_barras, nome_comercial, descricao, custo, preco, estoque_minimo, ativo, criado_em, atualizado_em) VALUES (:tipo_peca_id, :marca_produto_id, :sku_interno, :codigo_fabricante, :codigo_barras, :nome_comercial, :descricao, :custo, :preco, :estoque_minimo, 1, NOW(), NOW())';
                $st = $pdo->prepare($sql);
                $st->bindValue(':tipo_peca_id', $tipoId, PDO::PARAM_INT);
                $st->bindValue(':marca_produto_id', $marcaId, PDO::PARAM_INT);
                $st->bindValue(':sku_interno', $d['sku_interno']);
                $st->bindValue(':codigo_fabricante', $d['codigo_fabricante']);
                $codigoBarras = ($d['codigo_barras'] ?? '') !== '' ? $d['codigo_barras'] : null;
                $st->bindValue(':codigo_barras', $codigoBarras, $codigoBarras !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->bindValue(':nome_comercial', $d['nome_comercial']);
                $desc = ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null;
                $st->bindValue(':descricao', $desc, $desc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->bindValue(':custo', $d['custo'] ?? '0.00', PDO::PARAM_STR);
                $st->bindValue(':preco', $d['preco'] ?? '0.00', PDO::PARAM_STR);
                $st->bindValue(':estoque_minimo', (int)($d['estoque_minimo'] ?? 0), PDO::PARAM_INT);
                $st->execute();
                $inseridos++;
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $inseridos;
}
