<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/componentes.php';
require_once __DIR__ . '/lib/importacao_planilha.php';

date_default_timezone_set('America/Sao_Paulo');

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$tipo = trim((string)($_GET['tipo'] ?? $_POST['tipo'] ?? ''));

if (!importacao_planilha_tipo_valido($tipo)) {
    http_response_code(404);
    exit('Tipo de importação inválido.');
}

$config = importacao_planilha_config($tipo);
if ($config === null) {
    exit;
}

$chaveSessao = 'importacao_planilha_' . $tipo;

/*
|--------------------------------------------------------------------------
| Cancelar pré-visualização
|--------------------------------------------------------------------------
*/
if (isset($_GET['cancelar']) && $_GET['cancelar'] === '1') {
    unset($_SESSION[$chaveSessao]);
    header('Location: importar_planilha.php?tipo=' . urlencode($tipo));
    exit;
}

$erroFlash = '';
$sucessoFlash = '';

/*
|--------------------------------------------------------------------------
| POST: envio da planilha ou confirmação
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = trim((string)($_POST['acao'] ?? ''));

    if ($acao === 'enviar_planilha') {
        if (!isset($_FILES['planilha']) || !is_uploaded_file($_FILES['planilha']['tmp_name'] ?? '')) {
            $erroFlash = 'Selecione um arquivo para enviar.';
        } else {
            $arquivo = $_FILES['planilha'];
            if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $erroFlash = 'Falha no upload do arquivo.';
            } elseif (($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
                $erroFlash = 'Arquivo muito grande (máximo 2 MB).';
            } else {
                $tmp = $arquivo['tmp_name'];
                $ext = strtolower(pathinfo($arquivo['name'] ?? '', PATHINFO_EXTENSION));
                if (!in_array($ext, ['xls', 'xlsx'], true)) {
                    $erroFlash = 'Formato inválido. Use .xls ou .xlsx.';
                } else {
                    try {
                        $lido = importacao_planilha_ler_arquivo($tmp, $tipo, (string)($arquivo['name'] ?? ''));
                        if (isset($lido['erro'])) {
                            $erroFlash = $lido['erro'];
                        } else {
                            $validadas = importacao_planilha_validar_linhas($pdo, $tipo, $lido['linhas']);
                            $token = bin2hex(random_bytes(16));
                            $_SESSION[$chaveSessao] = [
                                'token'     => $token,
                                'cabecalho' => $lido['cabecalho'],
                                'validadas' => $validadas,
                                'criado_em' => time(),
                            ];
                            header('Location: importar_planilha.php?tipo=' . urlencode($tipo) . '&passo=preview');
                            exit;
                        }
                    } catch (Throwable $e) {
                        $erroFlash = $e->getMessage();
                    }
                }
            }
        }
    }

    if ($acao === 'confirmar_importacao') {
        $dadosSess = $_SESSION[$chaveSessao] ?? null;
        $tokenPost = (string)($_POST['token'] ?? '');
        if (
            !is_array($dadosSess)
            || ($dadosSess['token'] ?? '') !== $tokenPost
            || $tokenPost === ''
        ) {
            $erroFlash = 'Sessão de importação expirada ou inválida. Envie a planilha novamente.';
            unset($_SESSION[$chaveSessao]);
        } elseif ((time() - (int)($dadosSess['criado_em'] ?? 0)) > 3600) {
            $erroFlash = 'A pré-visualização expirou (1 hora). Envie a planilha novamente.';
            unset($_SESSION[$chaveSessao]);
        } else {
            $validadas = $dadosSess['validadas'] ?? [];
            $importaveis = array_filter($validadas, static fn (array $r): bool => $r['importavel']);
            if ($importaveis === []) {
                $erroFlash = 'Não há linhas válidas para importar. Corrija a planilha e envie novamente.';
            } else {
                try {
                    $n = importacao_planilha_gravar($pdo, $tipo, $validadas);
                    unset($_SESSION[$chaveSessao]);
                    $volta = $config['volta_lista'];
                    header('Location: ' . $volta . '?sucesso=importacao&n=' . $n);
                    exit;
                } catch (Throwable $e) {
                    $erroFlash = 'Erro ao gravar no banco. Tente novamente.';
                }
            }
        }
    }
}

$passo = trim((string)($_GET['passo'] ?? ''));
$dadosPreview = $_SESSION[$chaveSessao] ?? null;
$mostrarPreview = $passo === 'preview'
    && is_array($dadosPreview)
    && isset($dadosPreview['validadas']);

if ($mostrarPreview && (time() - (int)($dadosPreview['criado_em'] ?? 0)) > 3600) {
    unset($_SESSION[$chaveSessao]);
    $mostrarPreview = false;
    $erroFlash = 'A pré-visualização expirou. Envie a planilha novamente.';
}

$totalPreview = 0;
$totalImportaveis = 0;
$totalComErro = 0;
if ($mostrarPreview) {
    $validadas = $dadosPreview['validadas'];
    $totalPreview = count($validadas);
    foreach ($validadas as $v) {
        if ($v['importavel']) {
            $totalImportaveis++;
        } else {
            $totalComErro++;
        }
    }
}

$urlModelo = 'download_modelo_importacao.php?tipo=' . urlencode($tipo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar planilha — <?= esc((string)$config['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen md:flex">
    <?php require __DIR__ . '/menu.php'; ?>

    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6">
        <div class="mx-auto max-w-6xl">

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Importar por planilha</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        <?= esc((string)$config['titulo']) ?> — envie uma planilha no formato do modelo, confira a pré-visualização e confirme para gravar.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?= botao_link($config['volta_lista'], 'Voltar à listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erroFlash !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erroFlash) ?>
                </div>
            <?php endif; ?>

            <?php if (!$mostrarPreview): ?>

                <div class="<?= classe_box() ?> mb-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-900">1. Baixar modelo</h2>
                    <p class="text-sm text-slate-600">
                        A primeira linha da planilha deve conter exatamente os cabeçalhos (mesma ordem):
                        <strong class="text-slate-800"><?= esc(implode(' → ', $config['cabecalho'])) ?></strong>.
                        Linhas em branco são ignoradas. Extensões aceitas: .xls e .xlsx (até 2 MB).
                    </p>
                    <?= botao_link($urlModelo, 'Baixar planilha modelo (.xlsx)', 'busca') ?>
                </div>

                <div class="<?= classe_box() ?> space-y-4">
                    <h2 class="text-lg font-semibold text-slate-900">2. Enviar arquivo</h2>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="tipo" value="<?= esc($tipo) ?>">
                        <input type="hidden" name="acao" value="enviar_planilha">

                        <div>
                            <label for="planilha" class="<?= classe_label() ?>">Arquivo</label>
                            <input
                                type="file"
                                name="planilha"
                                id="planilha"
                                accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                required
                                class="<?= classe_input() ?>"
                            >
                        </div>

                        <?= botao_submit('Validar e pré-visualizar', 'salvar') ?>
                    </form>
                </div>

            <?php else: ?>

                <?php
                $validadas = $dadosPreview['validadas'];
                $token = (string)($dadosPreview['token'] ?? '');
                ?>

                <div class="<?= classe_box() ?> mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Pré-visualização</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            <?= (int)$totalPreview ?> linha(s) —
                            <span class="text-emerald-700 font-medium"><?= (int)$totalImportaveis ?> pronta(s) para importar</span>
                            <?php if ($totalComErro > 0): ?>
                                · <span class="text-red-700 font-medium"><?= (int)$totalComErro ?> com erro (não serão gravadas)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?= botao_link('importar_planilha.php?tipo=' . urlencode($tipo) . '&cancelar=1', 'Descartar e recomeçar', 'cancelar') ?>
                </div>

                <div class="<?= classe_box() ?> overflow-x-auto mb-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Linha</th>
                                <th class="px-3 py-3">Status</th>
                                <?php foreach ($config['cabecalho'] as $h): ?>
                                    <th class="px-3 py-3"><?= esc($h) ?></th>
                                <?php endforeach; ?>
                                <th class="px-3 py-3">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validadas as $item): ?>
                                <tr class="border-b border-slate-100 <?= $item['importavel'] ? 'bg-white' : 'bg-red-50/50' ?>">
                                    <td class="px-3 py-3 text-slate-600"><?= (int)$item['linha'] ?></td>
                                    <td class="px-3 py-3">
                                        <?php if ($item['importavel']): ?>
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">OK</span>
                                        <?php else: ?>
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Erro</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php foreach ($config['cabecalho'] as $h): ?>
                                        <td class="px-3 py-3 text-slate-800"><?= esc((string)($item['dados'][$h] ?? '')) ?></td>
                                    <?php endforeach; ?>
                                    <td class="px-3 py-3 text-slate-700">
                                        <?= $item['erros'] !== [] ? esc(implode(' ', $item['erros'])) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="<?= classe_box() ?>">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">3. Confirmar importação</h2>
                    <p class="text-sm text-slate-600 mb-4">
                        Somente as linhas marcadas como <strong>OK</strong> serão inseridas no cadastro. Esta ação não pode ser desfeita automaticamente.
                    </p>
                    <form method="POST" class="flex flex-wrap gap-2">
                        <input type="hidden" name="tipo" value="<?= esc($tipo) ?>">
                        <input type="hidden" name="acao" value="confirmar_importacao">
                        <input type="hidden" name="token" value="<?= esc($token) ?>">
                        <?= botao_submit('Consolidar importação', 'salvar', $totalImportaveis === 0 ? ['disabled' => true] : []) ?>
                        <?= botao_link('importar_planilha.php?tipo=' . urlencode($tipo) . '&cancelar=1', 'Cancelar', 'cancelar') ?>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>
