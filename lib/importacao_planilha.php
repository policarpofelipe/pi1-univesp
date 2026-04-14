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
    $vistosArquivo = [];

    $resultado = [];
    foreach ($linhas as $numLinha => $dados) {
        $erros = [];

        $nome = $dados['nome'] ?? '';
        if ($nome === '') {
            $erros[] = 'Nome é obrigatório.';
        } elseif (isset($limites['nome']) && mb_strlen($nome) > $limites['nome']) {
            $erros[] = 'Nome ultrapassa ' . $limites['nome'] . ' caracteres.';
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
 * @param array<int, array{linha: int, dados: array<string, string>, importavel: bool, erros: string[]}> $validadas
 */
function importacao_planilha_gravar(PDO $pdo, string $tipo, array $validadas): int
{
    $inseridos = 0;
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
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $inseridos;
}
