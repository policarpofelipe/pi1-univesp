<?php
declare(strict_types=1);

require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

function esc(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

$mensagem = '';
$classeMensagem = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if ($email === '') {
        $mensagem = 'Informe seu e-mail.';
        $classeMensagem = 'border-red-200 bg-red-50 text-red-700';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';
        $classeMensagem = 'border-red-200 bg-red-50 text-red-700';
    } else {
        try {
            $sql = "
                SELECT id, nome, email, ativo
                FROM usuarios
                WHERE email = :email
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            /*
            |--------------------------------------------------------------------------
            | Mensagem neutra para não expor existência de conta
            |--------------------------------------------------------------------------
            */
            $mensagem = 'Se houver uma conta vinculada a este e-mail, procure o administrador do sistema para redefinição da senha.';
            $classeMensagem = 'border-emerald-200 bg-emerald-50 text-emerald-700';

            /*
            |--------------------------------------------------------------------------
            | Futuramente, aqui entra:
            | - geração de token
            | - persistência do token
            | - envio de e-mail
            |--------------------------------------------------------------------------
            */
        } catch (Throwable $e) {
            $mensagem = 'Ocorreu um erro ao processar a solicitação.';
            $classeMensagem = 'border-red-200 bg-red-50 text-red-700';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        <div class="rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                <h1 class="text-2xl font-bold text-slate-900">Recuperar senha</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Informe seu e-mail para iniciar o processo de recuperação.
                </p>
            </div>

            <div class="p-6">
                <?php if ($mensagem !== ''): ?>
                    <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?= esc($classeMensagem) ?>">
                        <?= esc($mensagem) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
                            E-mail
                        </label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="<?= esc($email) ?>"
                            required
                            maxlength="150"
                            placeholder="email@dominio.com"
                            class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        >
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition"
                        >
                            Solicitar recuperação
                        </button>

                        <a
                            href="index.php"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-300 transition"
                        >
                            Voltar ao login
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-slate-500">
            Nesta versão do sistema, a redefinição de senha é mediada pelo administrador.
        </p>
    </div>

</body>
</html>