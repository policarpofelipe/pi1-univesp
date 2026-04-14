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
            'titulo' => 'Marcas de produto',
            'volta_lista' => 'listar_marcas_produto.php',
            'cabecalho' => ['nome'],
            'limites' => ['nome' => 100],
            'linhas_exemplo' => [['nome' => 'Ex.: Bosch']],
        ],
        'categorias_peca' => [
            'titulo' => 'Categorias de peça',
            'volta_lista' => 'listar_categorias_peca.php',
            'cabecalho' => ['nome', 'descricao'],
            'limites' => ['nome' => 100, 'descricao' => 255],
            'linhas_exemplo' => [
                ['nome' => 'Ex.: Filtros', 'descricao' => 'Opcional: linha de filtros de ar/lubrificantes'],
            ],
        ],
        'estoques' => [
            'titulo' => 'Locais de estoque',
            'volta_lista' => 'listar_estoques.php',
            'cabecalho' => ['nome', 'localizacao'],
            'limites' => ['nome' => 100, 'localizacao' => 150],
            'linhas_exemplo' => [
                ['nome' => 'Ex.: Depósito A', 'localizacao' => 'Opcional: setor norte'],
            ],
        ],
        'tipos_peca' => [
            'titulo' => 'Tipos de peça',
            'volta_lista' => 'listar_tipos_peca.php',
            'cabecalho' => ['categoria_nome', 'nome', 'descricao'],
            'limites' => ['categoria_nome' => 100, 'nome' => 150, 'descricao' => 255],
            'linhas_exemplo' => [
                ['categoria_nome' => 'Ex.: Filtros', 'nome' => 'Ex.: Filtro de óleo', 'descricao' => 'Opcional'],
            ],
        ],
        'produtos' => [
            'titulo' => 'Produtos',
            'volta_lista' => 'listar_produtos.php',
            'cabecalho' => [
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
            'limites' => [
                'tipo_peca_nome' => 150,
                'marca_produto_nome' => 100,
                'sku_interno' => 60,
                'codigo_fabricante' => 100,
                'codigo_barras' => 50,
                'nome_comercial' => 180,
                'descricao' => 65535,
            ],
            'linhas_exemplo' => [[
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
        'marcas_veiculo' => [
            'titulo' => 'Marcas de veículo',
            'volta_lista' => 'listar_marcas_veiculo.php',
            'cabecalho' => ['nome'],
            'limites' => ['nome' => 100],
            'linhas_exemplo' => [['nome' => 'Ex.: Toyota']],
        ],
        'modelos_veiculo' => [
            'titulo' => 'Modelos de veículo',
            'volta_lista' => 'listar_modelos_veiculo.php',
            'cabecalho' => ['marca_nome', 'nome'],
            'limites' => ['marca_nome' => 100, 'nome' => 100],
            'linhas_exemplo' => [['marca_nome' => 'Ex.: Toyota', 'nome' => 'Ex.: Corolla']],
        ],
        'veiculos_configuracao' => [
            'titulo' => 'Configurações veiculares',
            'volta_lista' => 'listar_veiculos_configuracao.php',
            'cabecalho' => [
                'modelo_veiculo_nome',
                'ano_inicio',
                'ano_fim',
                'motorizacao',
                'combustivel',
                'versao',
                'observacoes',
            ],
            'limites' => [
                'modelo_veiculo_nome' => 220,
                'motorizacao' => 50,
                'combustivel' => 30,
                'versao' => 100,
                'observacoes' => 255,
            ],
            'linhas_exemplo' => [[
                'modelo_veiculo_nome' => 'Escolha da lista',
                'ano_inicio' => '2018',
                'ano_fim' => '2021',
                'motorizacao' => '2.0',
                'combustivel' => 'Flex',
                'versao' => 'XEi',
                'observacoes' => 'Opcional',
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

    return ['cabecalho' => $cabecalhoArquivo, 'linhas' => $linhas];
}

/**
 * @return array<string, int> chave = nome lower trim
 */
function importacao_planilha_mapa_nomes_para_ids(PDO $pdo, string $tabela): array
{
    $permitidas = ['categorias_peca', 'tipos_peca', 'marcas_produto', 'marcas_veiculo'];
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
 * @return array<string, int> chave = "marca / modelo" lower trim
 */
function importacao_planilha_mapa_modelos_compostos(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT mo.id, mv.nome AS marca_nome, mo.nome AS modelo_nome
        FROM modelos_veiculo mo
        INNER JOIN marcas_veiculo mv ON mv.id = mo.marca_veiculo_id
    ");
    $mapa = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $composto = mb_strtolower(trim((string)$row['marca_nome']) . ' / ' . trim((string)$row['modelo_nome']));
        if ($composto !== '') {
            $mapa[$composto] = (int)$row['id'];
        }
    }
    return $mapa;
}

/**
 * @return array<string, true>
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

    if ($tipo === 'modelos_veiculo') {
        $stmt = $pdo->query('SELECT marca_veiculo_id, LOWER(TRIM(nome)) AS nome FROM modelos_veiculo');
        $mapa = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $k = (int)$row['marca_veiculo_id'] . '|' . (string)$row['nome'];
            $mapa[$k] = true;
        }
        return $mapa;
    }

    if ($tipo === 'veiculos_configuracao') {
        $stmt = $pdo->query('SELECT modelo_veiculo_id, ano_inicio, ano_fim, LOWER(TRIM(COALESCE(motorizacao, ""))) AS motorizacao, LOWER(TRIM(COALESCE(combustivel, ""))) AS combustivel, LOWER(TRIM(COALESCE(versao, ""))) AS versao FROM veiculos_configuracao');
        $mapa = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $k = (int)$row['modelo_veiculo_id'] . '|'
                . (int)$row['ano_inicio'] . '|'
                . (int)$row['ano_fim'] . '|'
                . (string)$row['motorizacao'] . '|'
                . (string)$row['combustivel'] . '|'
                . (string)$row['versao'];
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
        'marcas_produto' => 'marcas_produto',
        'categorias_peca' => 'categorias_peca',
        'estoques' => 'estoques',
        'marcas_veiculo' => 'marcas_veiculo',
        default => null,
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
 * @param array<int, array<string, string>> $linhas
 * @return array<int, array{linha: int, dados: array<string, string>, importavel: bool, erros: string[]}>
 */
function importacao_planilha_validar_linhas(PDO $pdo, string $tipo, array $linhas): array
{
    $cfg = importacao_planilha_config($tipo);
    if ($cfg === null) {
        return [];
    }
    $lim = $cfg['limites'];
    $existentes = importacao_planilha_nomes_existentes_no_banco($pdo, $tipo);
    $vistos = [];

    $mapaCategorias = in_array($tipo, ['tipos_peca'], true) ? importacao_planilha_mapa_nomes_para_ids($pdo, 'categorias_peca') : [];
    $mapaTipos = in_array($tipo, ['produtos'], true) ? importacao_planilha_mapa_nomes_para_ids($pdo, 'tipos_peca') : [];
    $mapaMarcasProduto = in_array($tipo, ['produtos'], true) ? importacao_planilha_mapa_nomes_para_ids($pdo, 'marcas_produto') : [];
    $mapaMarcasVeiculo = in_array($tipo, ['modelos_veiculo'], true) ? importacao_planilha_mapa_nomes_para_ids($pdo, 'marcas_veiculo') : [];
    $mapaModelosCompostos = in_array($tipo, ['veiculos_configuracao'], true) ? importacao_planilha_mapa_modelos_compostos($pdo) : [];

    $out = [];
    foreach ($linhas as $numLinha => $dados) {
        $erros = [];

        if (in_array($tipo, ['marcas_produto', 'categorias_peca', 'estoques', 'marcas_veiculo'], true)) {
            $nome = trim((string)($dados['nome'] ?? ''));
            if ($nome === '') {
                $erros[] = 'Nome é obrigatório.';
            } elseif (mb_strlen($nome) > (int)$lim['nome']) {
                $erros[] = 'Nome ultrapassa ' . $lim['nome'] . ' caracteres.';
            }
            $nomeKey = mb_strtolower($nome);
            if ($nome !== '') {
                if (isset($vistos[$nomeKey])) {
                    $erros[] = 'Nome repetido na planilha (linha ' . $vistos[$nomeKey] . ').';
                } else {
                    $vistos[$nomeKey] = $numLinha;
                }
                if (isset($existentes[$nomeKey])) {
                    $erros[] = 'Já existe cadastro com este nome.';
                }
            }
            if ($tipo === 'categorias_peca') {
                $desc = trim((string)($dados['descricao'] ?? ''));
                if ($desc !== '' && mb_strlen($desc) > (int)$lim['descricao']) {
                    $erros[] = 'Descrição ultrapassa ' . $lim['descricao'] . ' caracteres.';
                }
            }
            if ($tipo === 'estoques') {
                $loc = trim((string)($dados['localizacao'] ?? ''));
                if ($loc !== '' && mb_strlen($loc) > (int)$lim['localizacao']) {
                    $erros[] = 'Localização ultrapassa ' . $lim['localizacao'] . ' caracteres.';
                }
            }
        }

        if ($tipo === 'tipos_peca') {
            $catNome = trim((string)($dados['categoria_nome'] ?? ''));
            $nome = trim((string)($dados['nome'] ?? ''));
            $desc = trim((string)($dados['descricao'] ?? ''));
            if ($catNome === '') {
                $erros[] = 'Categoria é obrigatória.';
            } elseif (mb_strlen($catNome) > (int)$lim['categoria_nome']) {
                $erros[] = 'Categoria ultrapassa ' . $lim['categoria_nome'] . ' caracteres.';
            }
            if ($nome === '') {
                $erros[] = 'Nome é obrigatório.';
            } elseif (mb_strlen($nome) > (int)$lim['nome']) {
                $erros[] = 'Nome ultrapassa ' . $lim['nome'] . ' caracteres.';
            }
            if ($desc !== '' && mb_strlen($desc) > (int)$lim['descricao']) {
                $erros[] = 'Descrição ultrapassa ' . $lim['descricao'] . ' caracteres.';
            }
            $catKey = mb_strtolower($catNome);
            if ($catNome !== '' && !isset($mapaCategorias[$catKey])) {
                $erros[] = 'Categoria não encontrada no cadastro.';
            }
            if ($catNome !== '' && $nome !== '' && isset($mapaCategorias[$catKey])) {
                $dup = $mapaCategorias[$catKey] . '|' . mb_strtolower($nome);
                if (isset($vistos[$dup])) {
                    $erros[] = 'Tipo repetido para a mesma categoria na linha ' . $vistos[$dup] . '.';
                } else {
                    $vistos[$dup] = $numLinha;
                }
                if (isset($existentes[$dup])) {
                    $erros[] = 'Já existe tipo com este nome para a categoria informada.';
                }
            }
        }

        if ($tipo === 'modelos_veiculo') {
            $marcaNome = trim((string)($dados['marca_nome'] ?? ''));
            $nome = trim((string)($dados['nome'] ?? ''));
            if ($marcaNome === '') {
                $erros[] = 'Marca é obrigatória.';
            } elseif (mb_strlen($marcaNome) > (int)$lim['marca_nome']) {
                $erros[] = 'Marca ultrapassa ' . $lim['marca_nome'] . ' caracteres.';
            }
            if ($nome === '') {
                $erros[] = 'Nome é obrigatório.';
            } elseif (mb_strlen($nome) > (int)$lim['nome']) {
                $erros[] = 'Nome ultrapassa ' . $lim['nome'] . ' caracteres.';
            }
            $marcaKey = mb_strtolower($marcaNome);
            if ($marcaNome !== '' && !isset($mapaMarcasVeiculo[$marcaKey])) {
                $erros[] = 'Marca não encontrada no cadastro.';
            }
            if ($marcaNome !== '' && $nome !== '' && isset($mapaMarcasVeiculo[$marcaKey])) {
                $dup = $mapaMarcasVeiculo[$marcaKey] . '|' . mb_strtolower($nome);
                if (isset($vistos[$dup])) {
                    $erros[] = 'Modelo repetido para a mesma marca na linha ' . $vistos[$dup] . '.';
                } else {
                    $vistos[$dup] = $numLinha;
                }
                if (isset($existentes[$dup])) {
                    $erros[] = 'Já existe modelo com este nome para a marca informada.';
                }
            }
        }

        if ($tipo === 'veiculos_configuracao') {
            $modeloComp = trim((string)($dados['modelo_veiculo_nome'] ?? ''));
            $anoInicio = trim((string)($dados['ano_inicio'] ?? ''));
            $anoFim = trim((string)($dados['ano_fim'] ?? ''));
            $mot = trim((string)($dados['motorizacao'] ?? ''));
            $comb = trim((string)($dados['combustivel'] ?? ''));
            $ver = trim((string)($dados['versao'] ?? ''));
            $obs = trim((string)($dados['observacoes'] ?? ''));

            if ($modeloComp === '') {
                $erros[] = 'Modelo é obrigatório.';
            } elseif (!isset($mapaModelosCompostos[mb_strtolower($modeloComp)])) {
                $erros[] = 'Modelo não encontrado (use um valor da lista).';
            }

            if (!ctype_digit($anoInicio) || !ctype_digit($anoFim)) {
                $erros[] = 'Ano início/fim deve conter apenas números.';
            } else {
                $ai = (int)$anoInicio;
                $af = (int)$anoFim;
                if ($ai < 1900 || $ai > 2100 || $af < 1900 || $af > 2100) {
                    $erros[] = 'Anos devem ficar entre 1900 e 2100.';
                }
                if ($af < $ai) {
                    $erros[] = 'Ano fim não pode ser menor que ano início.';
                }
            }
            if ($mot !== '' && mb_strlen($mot) > (int)$lim['motorizacao']) {
                $erros[] = 'Motorização ultrapassa ' . $lim['motorizacao'] . ' caracteres.';
            }
            if ($comb !== '' && mb_strlen($comb) > (int)$lim['combustivel']) {
                $erros[] = 'Combustível ultrapassa ' . $lim['combustivel'] . ' caracteres.';
            }
            if ($ver !== '' && mb_strlen($ver) > (int)$lim['versao']) {
                $erros[] = 'Versão ultrapassa ' . $lim['versao'] . ' caracteres.';
            }
            if ($obs !== '' && mb_strlen($obs) > (int)$lim['observacoes']) {
                $erros[] = 'Observações ultrapassam ' . $lim['observacoes'] . ' caracteres.';
            }

            if ($modeloComp !== '' && ctype_digit($anoInicio) && ctype_digit($anoFim) && isset($mapaModelosCompostos[mb_strtolower($modeloComp)])) {
                $dup = $mapaModelosCompostos[mb_strtolower($modeloComp)]
                    . '|' . (int)$anoInicio
                    . '|' . (int)$anoFim
                    . '|' . mb_strtolower($mot)
                    . '|' . mb_strtolower($comb)
                    . '|' . mb_strtolower($ver);
                if (isset($vistos[$dup])) {
                    $erros[] = 'Configuração repetida na planilha (linha ' . $vistos[$dup] . ').';
                } else {
                    $vistos[$dup] = $numLinha;
                }
                if (isset($existentes[$dup])) {
                    $erros[] = 'Esta configuração já existe no cadastro.';
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

            if ($tipoNome === '' || !isset($mapaTipos[mb_strtolower($tipoNome)])) {
                $erros[] = 'Tipo de peça inválido.';
            }
            if ($marcaNome === '' || !isset($mapaMarcasProduto[mb_strtolower($marcaNome)])) {
                $erros[] = 'Marca do produto inválida.';
            }
            if ($sku === '') {
                $erros[] = 'SKU é obrigatório.';
            } elseif (mb_strlen($sku) > (int)$lim['sku_interno']) {
                $erros[] = 'SKU ultrapassa ' . $lim['sku_interno'] . ' caracteres.';
            }
            if ($codigoFabricante === '') {
                $erros[] = 'Código fabricante é obrigatório.';
            } elseif (mb_strlen($codigoFabricante) > (int)$lim['codigo_fabricante']) {
                $erros[] = 'Código fabricante ultrapassa ' . $lim['codigo_fabricante'] . ' caracteres.';
            }
            if ($codigoBarras !== '' && mb_strlen($codigoBarras) > (int)$lim['codigo_barras']) {
                $erros[] = 'Código de barras ultrapassa ' . $lim['codigo_barras'] . ' caracteres.';
            }
            if ($nomeComercial === '') {
                $erros[] = 'Nome comercial é obrigatório.';
            } elseif (mb_strlen($nomeComercial) > (int)$lim['nome_comercial']) {
                $erros[] = 'Nome comercial ultrapassa ' . $lim['nome_comercial'] . ' caracteres.';
            }
            if ($descricao !== '' && mb_strlen($descricao) > (int)$lim['descricao']) {
                $erros[] = 'Descrição muito longa.';
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
                if (isset($vistos['sku:' . $skuKey])) {
                    $erros[] = 'SKU repetido na planilha (linha ' . $vistos['sku:' . $skuKey] . ').';
                } else {
                    $vistos['sku:' . $skuKey] = $numLinha;
                }
                if (isset($existentes['sku:' . $skuKey])) {
                    $erros[] = 'SKU já cadastrado.';
                }
            }

            if ($marcaNome !== '' && $codigoFabricante !== '' && isset($mapaMarcasProduto[mb_strtolower($marcaNome)])) {
                $chaveMarcaCodigo = $mapaMarcasProduto[mb_strtolower($marcaNome)] . '|' . mb_strtolower($codigoFabricante);
                if (isset($vistos['mc:' . $chaveMarcaCodigo])) {
                    $erros[] = 'Código fabricante repetido para a mesma marca na linha ' . $vistos['mc:' . $chaveMarcaCodigo] . '.';
                } else {
                    $vistos['mc:' . $chaveMarcaCodigo] = $numLinha;
                }
                if (isset($existentes['mc:' . $chaveMarcaCodigo])) {
                    $erros[] = 'Código fabricante já cadastrado para esta marca.';
                }
            }

            if ($codigoBarras !== '') {
                $cbKey = mb_strtolower($codigoBarras);
                if (isset($vistos['cb:' . $cbKey])) {
                    $erros[] = 'Código de barras repetido na planilha (linha ' . $vistos['cb:' . $cbKey] . ').';
                } else {
                    $vistos['cb:' . $cbKey] = $numLinha;
                }
                if (isset($existentes['cb:' . $cbKey])) {
                    $erros[] = 'Código de barras já cadastrado.';
                }
            }
        }

        $out[] = [
            'linha' => $numLinha,
            'dados' => $dados,
            'importavel' => $erros === [],
            'erros' => $erros,
        ];
    }
    return $out;
}

/**
 * @param array<int, array{linha: int, dados: array<string, string>, importavel: bool, erros: string[]}> $validadas
 */
function importacao_planilha_gravar(PDO $pdo, string $tipo, array $validadas): int
{
    $inseridos = 0;
    $categorias = $tipo === 'tipos_peca' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'categorias_peca') : [];
    $tipos = $tipo === 'produtos' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'tipos_peca') : [];
    $marcasProduto = $tipo === 'produtos' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'marcas_produto') : [];
    $marcasVeiculo = $tipo === 'modelos_veiculo' ? importacao_planilha_mapa_nomes_para_ids($pdo, 'marcas_veiculo') : [];
    $modelosCompostos = $tipo === 'veiculos_configuracao' ? importacao_planilha_mapa_modelos_compostos($pdo) : [];

    $pdo->beginTransaction();
    try {
        foreach ($validadas as $item) {
            if (!$item['importavel']) {
                continue;
            }
            $d = $item['dados'];

            if ($tipo === 'marcas_produto') {
                $st = $pdo->prepare('INSERT INTO marcas_produto (nome, ativo, criado_em, atualizado_em) VALUES (:nome, 1, NOW(), NOW())');
                $st->bindValue(':nome', $d['nome']);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'categorias_peca') {
                $st = $pdo->prepare('INSERT INTO categorias_peca (nome, descricao, ativo, criado_em, atualizado_em) VALUES (:nome, :descricao, 1, NOW(), NOW())');
                $st->bindValue(':nome', $d['nome']);
                $desc = ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null;
                $st->bindValue(':descricao', $desc, $desc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'estoques') {
                $st = $pdo->prepare('INSERT INTO estoques (nome, localizacao, ativo, criado_em, atualizado_em) VALUES (:nome, :localizacao, 1, NOW(), NOW())');
                $st->bindValue(':nome', $d['nome']);
                $loc = ($d['localizacao'] ?? '') !== '' ? $d['localizacao'] : null;
                $st->bindValue(':localizacao', $loc, $loc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'tipos_peca') {
                $catId = $categorias[mb_strtolower(trim((string)$d['categoria_nome']))] ?? 0;
                if ($catId <= 0) {
                    continue;
                }
                $st = $pdo->prepare('INSERT INTO tipos_peca (categoria_peca_id, nome, descricao, ativo, criado_em, atualizado_em) VALUES (:categoria_peca_id, :nome, :descricao, 1, NOW(), NOW())');
                $st->bindValue(':categoria_peca_id', $catId, PDO::PARAM_INT);
                $st->bindValue(':nome', $d['nome']);
                $desc = ($d['descricao'] ?? '') !== '' ? $d['descricao'] : null;
                $st->bindValue(':descricao', $desc, $desc !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'produtos') {
                $tipoId = $tipos[mb_strtolower(trim((string)$d['tipo_peca_nome']))] ?? 0;
                $marcaId = $marcasProduto[mb_strtolower(trim((string)$d['marca_produto_nome']))] ?? 0;
                if ($tipoId <= 0 || $marcaId <= 0) {
                    continue;
                }
                $st = $pdo->prepare('INSERT INTO produtos (tipo_peca_id, marca_produto_id, sku_interno, codigo_fabricante, codigo_barras, nome_comercial, descricao, custo, preco, estoque_minimo, ativo, criado_em, atualizado_em) VALUES (:tipo_peca_id, :marca_produto_id, :sku_interno, :codigo_fabricante, :codigo_barras, :nome_comercial, :descricao, :custo, :preco, :estoque_minimo, 1, NOW(), NOW())');
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
            } elseif ($tipo === 'marcas_veiculo') {
                $st = $pdo->prepare('INSERT INTO marcas_veiculo (nome, ativo, criado_em, atualizado_em) VALUES (:nome, 1, NOW(), NOW())');
                $st->bindValue(':nome', $d['nome']);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'modelos_veiculo') {
                $marcaId = $marcasVeiculo[mb_strtolower(trim((string)$d['marca_nome']))] ?? 0;
                if ($marcaId <= 0) {
                    continue;
                }
                $st = $pdo->prepare('INSERT INTO modelos_veiculo (marca_veiculo_id, nome, ativo, criado_em, atualizado_em) VALUES (:marca_veiculo_id, :nome, 1, NOW(), NOW())');
                $st->bindValue(':marca_veiculo_id', $marcaId, PDO::PARAM_INT);
                $st->bindValue(':nome', $d['nome']);
                $st->execute();
                $inseridos++;
            } elseif ($tipo === 'veiculos_configuracao') {
                $modeloId = $modelosCompostos[mb_strtolower(trim((string)$d['modelo_veiculo_nome']))] ?? 0;
                if ($modeloId <= 0) {
                    continue;
                }
                $st = $pdo->prepare('INSERT INTO veiculos_configuracao (modelo_veiculo_id, ano_inicio, ano_fim, motorizacao, combustivel, versao, observacoes, ativo, criado_em, atualizado_em) VALUES (:modelo_veiculo_id, :ano_inicio, :ano_fim, :motorizacao, :combustivel, :versao, :observacoes, 1, NOW(), NOW())');
                $st->bindValue(':modelo_veiculo_id', $modeloId, PDO::PARAM_INT);
                $st->bindValue(':ano_inicio', (int)$d['ano_inicio'], PDO::PARAM_INT);
                $st->bindValue(':ano_fim', (int)$d['ano_fim'], PDO::PARAM_INT);
                $mot = ($d['motorizacao'] ?? '') !== '' ? $d['motorizacao'] : null;
                $com = ($d['combustivel'] ?? '') !== '' ? $d['combustivel'] : null;
                $ver = ($d['versao'] ?? '') !== '' ? $d['versao'] : null;
                $obs = ($d['observacoes'] ?? '') !== '' ? $d['observacoes'] : null;
                $st->bindValue(':motorizacao', $mot, $mot !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->bindValue(':combustivel', $com, $com !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->bindValue(':versao', $ver, $ver !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $st->bindValue(':observacoes', $obs, $obs !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
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
