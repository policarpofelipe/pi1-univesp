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

$id = (int)($_GET['id'] ?? 0);

$modoEdicao = $id > 0;
$tituloPagina = $modoEdicao ? 'Editar Usuário' : 'Novo Usuário';

$erroCodigo = trim((string)($_GET['erro'] ?? ''));
$erro = '';

$mapaErros = [
    'nome_obrigatorio'        => 'Informe o nome do usuário.',
    'email_obrigatorio'       => 'Informe o e-mail do usuário.',
    'email_invalido'          => 'Informe um e-mail válido.',
    'email_duplicado'         => 'Já existe um usuário cadastrado com este e-mail.',
    'senha_obrigatoria'       => 'Informe a senha do usuário.',
    'senha_curta'             => 'A senha deve ter pelo menos 6 caracteres.',
    'registro_nao_encontrado' => 'Usuário não encontrado.',
    'erro_interno'            => 'Ocorreu um erro interno ao processar a operação.',
];

if ($erroCodigo !== '' && isset($mapaErros[$erroCodigo])) {
    $erro = $mapaErros[$erroCodigo];
}

$dados = [
    'id'    => 0,
    'nome'  => '',
    'email' => '',
    'ativo' => '1',
];

if ($modoEdicao) {
    $sql = "
        SELECT
            id,
            nome,
            email,
            ativo
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $erro = 'Usuário não encontrado.';
        $modoEdicao = false;
        $tituloPagina = 'Novo Usuário';
    } else {
        $dados = [
            'id'    => (int)$usuario['id'],
            'nome'  => (string)($usuario['nome'] ?? ''),
            'email' => (string)($usuario['email'] ?? ''),
            'ativo' => (string)($usuario['ativo'] ?? '1'),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($tituloPagina) ?></title>

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
        <div class="mx-auto max-w-4xl">

            <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 md:p-6 shadow-lg flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white"><?= esc($tituloPagina) ?></h1>
                    <p class="mt-1 text-sm text-slate-300">
                        <?= $modoEdicao
                            ? 'Atualize os dados de acesso e identificação do usuário.'
                            : 'Cadastre um novo usuário com acesso ao sistema.' ?>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <?= botao_link('listar_usuarios.php', 'Voltar para listagem', 'cancelar') ?>
                </div>
            </div>

            <?php if ($erro !== ''): ?>
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?= esc($erro) ?>
                </div>
            <?php endif; ?>

            <div class="<?= classe_box() ?>">
                <form action="salvar_usuario.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= (int)$dados['id'] ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="nome" class="<?= classe_label() ?>">Nome</label>
                            <?= input_texto('nome', $dados['nome'], [
                                'id' => 'nome',
                                'maxlength' => '150',
                                'required' => true,
                                'placeholder' => 'Nome completo do usuário'
                            ]) ?>
                        </div>

                        <div>
                            <label for="email" class="<?= classe_label() ?>">E-mail</label>
                            <?= input_texto('email', $dados['email'], [
                                'id' => 'email',
                                'type' => 'email',
                                'maxlength' => '150',
                                'required' => true,
                                'placeholder' => 'email@dominio.com'
                            ]) ?>
                        </div>

                        <div>
                            <label for="senha" class="<?= classe_label() ?>">
                                <?= $modoEdicao ? 'Nova senha' : 'Senha' ?>
                            </label>
                            <?= input_texto('senha', '', [
                                'id' => 'senha',
                                'type' => 'password',
                                'maxlength' => '255',
                                'required' => $modoEdicao ? false : true,
                                'placeholder' => $modoEdicao
                                    ? 'Preencha apenas se quiser alterar a senha'
                                    : 'Digite a senha de acesso'
                            ]) ?>
                            <p class="mt-1 text-xs text-slate-500">
                                <?= $modoEdicao
                                    ? 'Deixe em branco para manter a senha atual.'
                                    : 'Utilize uma senha com pelo menos 6 caracteres.' ?>
                            </p>
                        </div>

                        <div>
                            <label for="ativo" class="<?= classe_label() ?>">Status</label>
                            <?= select_padrao('ativo', [
                                '1' => 'Ativo',
                                '0' => 'Inativo',
                            ], $dados['ativo'], [
                                'id' => 'ativo'
                            ]) ?>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <?= botao_submit($modoEdicao ? 'Salvar alterações' : 'Cadastrar usuário', 'salvar') ?>
                        <?= botao_link('listar_usuarios.php', 'Cancelar', 'cancelar') ?>
                    </div>
                </form>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
                <strong class="text-slate-800">Observação:</strong>
                usuário não é apenas cadastro visual; é identidade de operação. Por isso, a alteração de senha deve ser tratada com algum rigor, mesmo neste MVP.
            </div>
        </div>
    </main>
</div>

</body>
</html>