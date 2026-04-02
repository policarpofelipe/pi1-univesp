<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/componentes.php';

date_default_timezone_set('America/Sao_Paulo');

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'razao_social_obrigatoria' => 'Informe a razão social.',
    'nome_fantasia_obrigatorio' => 'Informe o nome fantasia.',
    'cnpj_obrigatorio' => 'Informe o CNPJ.',
    'email_invalido' => 'Informe um e-mail válido.',
    'erro_interno' => 'Ocorreu um erro interno ao carregar os dados da empresa.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id' => 0,
    'razao_social' => '',
    'nome_fantasia' => '',
    'cnpj' => '',
    'inscricao_estadual' => '',
    'email' => '',
    'telefone' => '',
    'cep' => '',
    'logradouro' => '',
    'numero' => '',
    'complemento' => '',
    'bairro' => '',
    'cidade' => '',
    'uf' => '',
];

try {
    $sql = "
        SELECT
            id,
            razao_social,
            nome_fantasia,
            cnpj,
            inscricao_estadual,
            email,
            telefone,
            cep,
            logradouro,
            numero,
            complemento,
            bairro,
            cidade,
            uf
        FROM config_empresa
        ORDER BY id ASC
        LIMIT 1
    ";

    $stmt = $pdo->query($sql);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empresa) {
        $dados = [
            'id' => (int)($empresa['id'] ?? 0),
            'razao_social' => (string)($empresa['razao_social'] ?? ''),
            'nome_fantasia' => (string)($empresa['nome_fantasia'] ?? ''),
            'cnpj' => (string)($empresa['cnpj'] ?? ''),
            'inscricao_estadual' => (string)($empresa['inscricao_estadual'] ?? ''),
            'email' => (string)($empresa['email'] ?? ''),
            'telefone' => (string)($empresa['telefone'] ?? ''),
            'cep' => (string)($empresa['cep'] ?? ''),
            'logradouro' => (string)($empresa['logradouro'] ?? ''),
            'numero' => (string)($empresa['numero'] ?? ''),
            'complemento' => (string)($empresa['complemento'] ?? ''),
            'bairro' => (string)($empresa['bairro'] ?? ''),
            'cidade' => (string)($empresa['cidade'] ?? ''),
            'uf' => (string)($empresa['uf'] ?? ''),
        ];
    }
} catch (Throwable $e) {
    $erro = 'Ocorreu um erro interno ao carregar os dados da empresa.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração da Empresa</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.Alpine && window.AlpineCollapse) {
                Alpine.plugin(window.AlpineCollapse);
            }
        });
    </script>
</head>
<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen md:flex">
    <?php require __DIR__ . '/menu.php'; ?>

    <main class="flex-1 p-4 md:p-6 pb-24 md:pb-6">
        <div class="mx-auto max-w-5xl">

            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Minha Empresa</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Configure os dados institucionais da empresa utilizados pelo sistema.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('painel.php', 'Voltar ao painel', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_config_empresa.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="razao_social" class="<?= classe_label() ?>">Razão social</label>
                            <?= input_texto('razao_social', $dados['razao_social'], [
                                'id' => 'razao_social',
                                'maxlength' => '200',
                                'required' => true,
                                'placeholder' => 'Ex.: J C DE ALMEIDA AUTO PEÇAS LTDA'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="nome_fantasia" class="<?= classe_label() ?>">Nome fantasia</label>
                            <?= input_texto('nome_fantasia', $dados['nome_fantasia'], [
                                'id' => 'nome_fantasia',
                                'maxlength' => '200',
                                'required' => true,
                                'placeholder' => 'Ex.: Auto Peças Almeida'
                            ]) ?>
                        </div>

                        <div>
                            <label for="cnpj" class="<?= classe_label() ?>">CNPJ</label>
                            <?= input_texto('cnpj', $dados['cnpj'], [
                                'id' => 'cnpj',
                                'maxlength' => '18',
                                'required' => true,
                                'placeholder' => '00.000.000/0000-00'
                            ]) ?>
                        </div>

                        <div>
                            <label for="inscricao_estadual" class="<?= classe_label() ?>">Inscrição estadual</label>
                            <?= input_texto('inscricao_estadual', $dados['inscricao_estadual'], [
                                'id' => 'inscricao_estadual',
                                'maxlength' => '30',
                                'placeholder' => 'Informe a IE, se houver'
                            ]) ?>
                        </div>

                        <div>
                            <label for="email" class="<?= classe_label() ?>">E-mail</label>
                            <?= input_texto('email', $dados['email'], [
                                'id' => 'email',
                                'type' => 'email',
                                'maxlength' => '150',
                                'placeholder' => 'contato@empresa.com'
                            ]) ?>
                        </div>

                        <div>
                            <label for="telefone" class="<?= classe_label() ?>">Telefone</label>
                            <?= input_texto('telefone', $dados['telefone'], [
                                'id' => 'telefone',
                                'maxlength' => '20',
                                'placeholder' => '(12) 99999-9999'
                            ]) ?>
                        </div>

                        <div>
                            <label for="cep" class="<?= classe_label() ?>">CEP</label>
                            <?= input_texto('cep', $dados['cep'], [
                                'id' => 'cep',
                                'maxlength' => '9',
                                'placeholder' => '00000-000'
                            ]) ?>
                        </div>

                        <div class="md:col-span-2">
                            <label for="logradouro" class="<?= classe_label() ?>">Logradouro</label>
                            <?= input_texto('logradouro', $dados['logradouro'], [
                                'id' => 'logradouro',
                                'maxlength' => '200',
                                'placeholder' => 'Rua, avenida, alameda...'
                            ]) ?>
                        </div>

                        <div>
                            <label for="numero" class="<?= classe_label() ?>">Número</label>
                            <?= input_texto('numero', $dados['numero'], [
                                'id' => 'numero',
                                'maxlength' => '20',
                                'placeholder' => '123'
                            ]) ?>
                        </div>

                        <div>
                            <label for="complemento" class="<?= classe_label() ?>">Complemento</label>
                            <?= input_texto('complemento', $dados['complemento'], [
                                'id' => 'complemento',
                                'maxlength' => '100',
                                'placeholder' => 'Sala, fundos, bloco...'
                            ]) ?>
                        </div>

                        <div>
                            <label for="bairro" class="<?= classe_label() ?>">Bairro</label>
                            <?= input_texto('bairro', $dados['bairro'], [
                                'id' => 'bairro',
                                'maxlength' => '100',
                                'placeholder' => 'Centro'
                            ]) ?>
                        </div>

                        <div>
                            <label for="cidade" class="<?= classe_label() ?>">Cidade</label>
                            <?= input_texto('cidade', $dados['cidade'], [
                                'id' => 'cidade',
                                'maxlength' => '100',
                                'placeholder' => 'Lorena'
                            ]) ?>
                        </div>

                        <div>
                            <label for="uf" class="<?= classe_label() ?>">UF</label>
                            <?= input_texto('uf', $dados['uf'], [
                                'id' => 'uf',
                                'maxlength' => '2',
                                'placeholder' => 'SP'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit('Salvar configuração', 'salvar') ?>
                        <?= botao_link('painel.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                esta tela não é um cadastro mercantil genérico de empresas; ela representa a identidade institucional do sistema. Em termos arquitetônicos, é configuração, não coleção.
            </div>
        </div>
    </main>
</div>

</body>
</html>