<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login · AutoStock Pro</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

  <!-- card principal -->
  <div class="w-full max-w-md">
    <!-- logotipo / ícone -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-tr from-blue-500 to-cyan-400 rounded-2xl shadow-2xl shadow-blue-500/30 mb-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-white tracking-tight">AutoStock Pro</h1>
      <p class="text-blue-200/80 text-sm mt-1.5">controle de estoque · peças automotivas</p>
    </div>

    <!-- formulário de login -->
    <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl p-8">
      <form action="login.php" method="POST" class="space-y-6">
        <!-- campo email -->
        <div class="space-y-2">
          <label for="email" class="block text-sm font-medium text-blue-100">E-mail</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-blue-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12H8m8 4H8m4-8v12M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" />
              </svg>
            </div>
            <input type="email" name="email" id="email" required
              class="w-full bg-white/5 border border-white/10 rounded-xl py-3.5 pl-10 pr-4 text-white placeholder-blue-200/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:border-transparent transition duration-200"
              placeholder="seu@email.com">
          </div>
        </div>

        <!-- campo senha (hash será gerado no backend) -->
        <div class="space-y-2">
          <label for="senha" class="block text-sm font-medium text-blue-100">Senha</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-blue-300/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <input type="password" name="senha" id="senha" required
              class="w-full bg-white/5 border border-white/10 rounded-xl py-3.5 pl-10 pr-4 text-white placeholder-blue-200/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:border-transparent transition duration-200"
              placeholder="••••••••">
          </div>
          <p class="text-xs text-blue-200/40 text-right mt-1">senha criptografada (hash)</p>
        </div>

        <!-- opções extras: lembrar + esqueci senha -->
        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center space-x-2 text-blue-100">
            <input type="checkbox" class="rounded bg-white/5 border-white/20 text-cyan-400 focus:ring-cyan-400/30 focus:ring-offset-0">
            <span>Manter conectado</span>
          </label>
          <a href="#" class="text-cyan-300 hover:text-cyan-200 underline underline-offset-2 decoration-cyan-500/30 transition">Esqueceu a senha?</a>
        </div>

        <!-- botão de login -->
        <button type="submit"
          class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-blue-500/30 transform transition duration-200 hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-cyan-400/70">
          Entrar no sistema
        </button>

        <!-- link para cadastro (conforme solicitado) -->
        <div class="text-center text-blue-100/80 text-sm">
          Ainda não tem acesso?
          <a href="cadastro.php" class="font-medium text-cyan-300 hover:text-cyan-200 underline underline-offset-2 ml-1 transition">Criar conta</a>
        </div>
      </form>
    </div>

    <!-- rodapé com indicação de hash / segurança -->
    <div class="text-center mt-6">
      <p class="text-xs text-blue-200/30 flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        todas as senhas com hash bcrypt · PI1 UNIVESP 2026
      </p>
    </div>
  </div>

  <!-- pequeno detalhe: se preferir, pode usar um script simples de demonstração (opcional) -->
  <script>
    // Exemplo meramente ilustrativo: no backend você deve usar password_hash() e password_verify()
    // O formulário envia para login.php (tratará a verificação do hash)
  </script>
</body>
</html>