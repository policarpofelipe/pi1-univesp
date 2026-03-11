<?php
declare(strict_types=1);

$paginaAtual = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

$menuPrincipal = [
    [
        'url'   => 'painel.php',
        'label' => 'Painel',
        'icon'  => 'M3.75 3h16.5A1.5 1.5 0 0 1 21.75 4.5v4.125A1.5 1.5 0 0 1 20.25 10.125H3.75a1.5 1.5 0 0 1-1.5-1.5V4.5A1.5 1.5 0 0 1 3.75 3Zm0 10.875h7.5a1.5 1.5 0 0 1 1.5 1.5v5.125a1.5 1.5 0 0 1-1.5 1.5h-7.5a1.5 1.5 0 0 1-1.5-1.5v-5.125a1.5 1.5 0 0 1 1.5-1.5Zm10.5 0h6a1.5 1.5 0 0 1 1.5 1.5v1.125a1.5 1.5 0 0 1-1.5 1.5h-6a1.5 1.5 0 0 1-1.5-1.5v-1.125a1.5 1.5 0 0 1 1.5-1.5Zm0 5.25h6a1.5 1.5 0 0 1 1.5 1.5v.375a1.5 1.5 0 0 1-1.5 1.5h-6a1.5 1.5 0 0 1-1.5-1.5V20.625a1.5 1.5 0 0 1 1.5-1.5Z',
    ],
    [
        'url'   => 'consulta_veiculo.php',
        'label' => 'Consulta por Veículo',
        'icon'  => 'M8.25 18.75a1.5 1.5 0 0 1-1.5 1.5h-.375a1.875 1.875 0 0 1-1.875-1.875V16.5m12.75 2.25a1.5 1.5 0 0 0 1.5 1.5h.375A1.875 1.875 0 0 0 21 18.375V16.5M3.75 13.5h16.5M5.25 16.5h13.5a2.25 2.25 0 0 0 2.25-2.25v-3.19a2.25 2.25 0 0 0-.659-1.591l-1.46-1.46a2.25 2.25 0 0 1-.659-1.591V5.625A2.625 2.625 0 0 0 15.597 3H8.403A2.625 2.625 0 0 0 5.778 5.625v.793a2.25 2.25 0 0 1-.659 1.591l-1.46 1.46A2.25 2.25 0 0 0 3 11.06v3.19a2.25 2.25 0 0 0 2.25 2.25Z',
    ],
    [
        'url'   => 'relatorio_estoque_baixo.php',
        'label' => 'Estoque Baixo',
        'icon'  => 'M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.95 3.374H4.647c-1.733 0-2.816-1.874-1.95-3.374L10.05 3.374c.866-1.5 3.034-1.5 3.9 0l7.353 12.752ZM12 16.5h.008v.008H12V16.5Z',
    ],
];

$menuCadastros = [
    [
        'url'   => 'listar_categorias_peca.php',
        'label' => 'Categorias de Peça',
        'icon'  => 'M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5',
    ],
    [
        'url'   => 'listar_tipos_peca.php',
        'label' => 'Tipos de Peça',
        'icon'  => 'M9 12.75 11.25 15 15 9.75M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z',
    ],
    [
        'url'   => 'listar_marcas_produto.php',
        'label' => 'Marcas de Produto',
        'icon'  => 'M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v12A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75Z',
    ],
    [
        'url'   => 'listar_produtos.php',
        'label' => 'Produtos',
        'icon'  => 'M20.25 7.5 12 3 3.75 7.5 12 12l8.25-4.5ZM3.75 7.5V16.5L12 21l8.25-4.5V7.5',
    ],
];

$menuCatalogoVeicular = [
    [
        'url'   => 'listar_marcas_veiculo.php',
        'label' => 'Marcas de Veículo',
        'icon'  => 'M9.75 17.25h4.5M3 13.5h18M4.5 13.5l1.532-6.128A2.25 2.25 0 0 1 8.214 5.25h7.572a2.25 2.25 0 0 1 2.182 1.122L19.5 13.5M6.75 17.25h.008v.008H6.75v-.008Zm10.5 0h.008v.008h-.008v-.008Z',
    ],
    [
        'url'   => 'listar_modelos_veiculo.php',
        'label' => 'Modelos de Veículo',
        'icon'  => 'M8.25 18.75a1.5 1.5 0 0 1-1.5 1.5h-.375a1.875 1.875 0 0 1-1.875-1.875V16.5m12.75 2.25a1.5 1.5 0 0 0 1.5 1.5h.375A1.875 1.875 0 0 0 21 18.375V16.5M3.75 13.5h16.5',
    ],
    [
        'url'   => 'listar_veiculos_configuracao.php',
        'label' => 'Configurações Veiculares',
        'icon'  => 'M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v12a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 18V6a2.25 2.25 0 0 1 2.25-2.25ZM8.25 8.25h7.5m-7.5 3.75h7.5m-7.5 3.75h4.5',
    ],
    [
        'url'   => 'listar_aplicacoes_peca.php',
        'label' => 'Aplicações / Compatibilidade',
        'icon'  => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
    ],
];

$menuEstoque = [
    [
        'url'   => 'listar_estoques.php',
        'label' => 'Locais de Estoque',
        'icon'  => 'M3 7.5 12 3l9 4.5M4.5 9.75V18L12 21l7.5-3V9.75',
    ],
    [
        'url'   => 'listar_movimentacoes_estoque.php',
        'label' => 'Movimentações',
        'icon'  => 'M3 3v1.5M3 19.5V21M21 3v1.5M21 19.5V21M8.25 6.75h7.5m-9 4.5h10.5m-7.5 4.5h4.5M5.25 3h13.5A2.25 2.25 0 0 1 21 5.25v13.5A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75V5.25A2.25 2.25 0 0 1 5.25 3Z',
    ],
    [
        'url'   => 'saldo_estoque.php',
        'label' => 'Saldo Atual',
        'icon'  => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12.75M16.5 7.5h3.75m0 0V3.75m0 3.75-4.5-4.5M7.5 12H12m0 0h4.5M12 12V7.5m0 4.5v4.5',
    ],
];

$menuRelatorios = [
    [
        'url'   => 'relatorio_produtos_por_veiculo.php',
        'label' => 'Produtos por Veículo',
        'icon'  => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
    ],
    [
        'url'   => 'relatorio_estoque_baixo.php',
        'label' => 'Estoque Mínimo',
        'icon'  => 'M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.95 3.374H4.647c-1.733 0-2.816-1.874-1.95-3.374L10.05 3.374c.866-1.5 3.034-1.5 3.9 0l7.353 12.752ZM12 16.5h.008v.008H12V16.5Z',
    ],
];

$menuConfiguracoes = [
    [
        'url'   => 'listar_usuarios.php',
        'label' => 'Usuários',
        'icon'  => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    ],
    [
        'url'   => 'form_config_empresa.php',
        'label' => 'Minha Empresa',
        'icon'  => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21',
    ],
    [
        'url'   => 'logout.php',
        'label' => 'Sair',
        'icon'  => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
    ],
];

function arquivo_url(string $url): string
{
    $path = parse_url($url, PHP_URL_PATH);
    return basename($path ?: $url);
}

function submenu_ativo(array $itens, string $paginaAtual): bool
{
    foreach ($itens as $item) {
        if (arquivo_url($item['url']) === $paginaAtual) {
            return true;
        }
    }
    return false;
}

function render_item_menu(array $item, string $paginaAtual, bool $open = true, bool $sub = false): void
{
    $isAtivo = (arquivo_url($item['url']) === $paginaAtual);

    $classeFundo = $isAtivo
        ? 'bg-gradient-to-r from-blue-50 to-sky-50 border-l-4 border-blue-600'
        : 'hover:bg-gray-100';

    $classeIcone = $isAtivo ? 'text-blue-600' : 'text-gray-500';
    $classeTexto = $isAtivo ? 'text-gray-900 font-medium' : 'text-gray-700';
    $paddingY = $sub ? 'py-2' : 'py-3';
    $marginLeft = $sub ? 'ml-3' : 'ml-4';
    $iconSize = $sub ? 'h-4 w-4' : 'h-5 w-5';
    $textSize = $sub ? 'text-sm' : '';

    ?>
    <a
        href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
        class="group flex w-full items-center justify-start rounded-lg px-4 <?= $paddingY ?> transition-all duration-200 <?= $classeFundo ?>"
        :title="!open ? '<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>' : ''"
    >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="<?= $iconSize ?> flex-shrink-0 <?= $classeIcone ?> transition-colors group-hover:text-blue-600"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" />
        </svg>

        <?php if ($open): ?>
            <span class="<?= $marginLeft ?> whitespace-nowrap text-left <?= $classeTexto ?> <?= $textSize ?> transition-colors group-hover:text-gray-900">
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>
    </a>
    <?php
}

function render_bloco_submenu(string $titulo, string $icone, array $itens, string $paginaAtual, string $stateName): void
{
    $isAtivo = submenu_ativo($itens, $paginaAtual);
    $classeFundo = $isAtivo
        ? 'bg-gradient-to-r from-blue-50 to-sky-50 border-l-4 border-blue-600'
        : 'hover:bg-gray-100';

    $classeIcone = $isAtivo ? 'text-blue-600' : 'text-gray-500';
    $classeTexto = $isAtivo ? 'text-gray-900 font-medium' : 'text-gray-700';
    ?>
    <div class="w-full">
        <button
            @click="if (!open) { open = true }; <?= $stateName ?> = !<?= $stateName ?>"
            class="group flex w-full items-center justify-start rounded-lg px-4 py-3 transition-all duration-200 <?= $classeFundo ?>"
            :title="!open ? '<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>' : ''"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-5 w-5 flex-shrink-0 <?= $classeIcone ?> transition-colors group-hover:text-blue-600"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="<?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?>" />
            </svg>

            <span
                x-show="open"
                class="ml-4 whitespace-nowrap text-left <?= $classeTexto ?> transition-colors group-hover:text-gray-900"
            >
                <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <svg
                x-show="open"
                xmlns="http://www.w3.org/2000/svg"
                class="ml-auto h-4 w-4 transition-transform duration-300 <?= $classeIcone ?>"
                :class="{ 'rotate-180': <?= $stateName ?> }"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="<?= $stateName ?> && open"
            x-collapse
            class="mt-1 space-y-1 overflow-hidden pl-6"
        >
            <?php foreach ($itens as $item): ?>
                <?php render_item_menu($item, $paginaAtual, true, true); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

$isCadastrosAtivo = submenu_ativo($menuCadastros, $paginaAtual);
$isCatalogoAtivo = submenu_ativo($menuCatalogoVeicular, $paginaAtual);
$isEstoqueAtivo = submenu_ativo($menuEstoque, $paginaAtual);
$isRelatoriosAtivo = submenu_ativo($menuRelatorios, $paginaAtual);
$isConfiguracoesAtivo = submenu_ativo($menuConfiguracoes, $paginaAtual);
?>

<aside
    x-data="{
        open: false,
        cadastrosOpen: <?= $isCadastrosAtivo ? 'true' : 'false' ?>,
        catalogoOpen: <?= $isCatalogoAtivo ? 'true' : 'false' ?>,
        estoqueOpen: <?= $isEstoqueAtivo ? 'true' : 'false' ?>,
        relatoriosOpen: <?= $isRelatoriosAtivo ? 'true' : 'false' ?>,
        configuracoesOpen: <?= $isConfiguracoesAtivo ? 'true' : 'false' ?>
    }"
    class="relative hidden h-screen flex-col border-r border-gray-200 bg-white text-gray-900 shadow-lg transition-all duration-300 md:flex"
    :class="open ? 'w-72' : 'w-20'"
>
    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4">
        <button
            @click="open = !open"
            class="rounded-lg p-2 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
            title="Alternar menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="h-6 w-6 text-gray-700" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke="currentColor">
                <path stroke-linecap="round" 
                      stroke-linejoin="round" 
                      stroke-width="2" 
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        <span x-show="open" class="text-lg font-semibold text-gray-800">PI Estoque</span>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-3
                [&::-webkit-scrollbar]:w-1
                [&::-webkit-scrollbar-track]:bg-transparent
                [&::-webkit-scrollbar-thumb]:rounded-full
                [&::-webkit-scrollbar-thumb]:bg-gray-300
                hover:[&::-webkit-scrollbar-thumb]:bg-gray-400
                [scrollbar-width]:thin
                [scrollbar-color]:rgb(209_213_219)_transparent">

        <?php foreach ($menuPrincipal as $item): ?>
            <?php render_item_menu($item, $paginaAtual); ?>
        <?php endforeach; ?>

        <?php
        render_bloco_submenu(
            'Cadastros',
            'M3.75 4.5h16.5M3.75 9.75h16.5M3.75 15h16.5M3.75 20.25h16.5',
            $menuCadastros,
            $paginaAtual,
            'cadastrosOpen'
        );

        render_bloco_submenu(
            'Catálogo Veicular',
            'M8.25 18.75a1.5 1.5 0 0 1-1.5 1.5h-.375a1.875 1.875 0 0 1-1.875-1.875V16.5m12.75 2.25a1.5 1.5 0 0 0 1.5 1.5h.375A1.875 1.875 0 0 0 21 18.375V16.5M3.75 13.5h16.5M5.25 16.5h13.5a2.25 2.25 0 0 0 2.25-2.25v-3.19a2.25 2.25 0 0 0-.659-1.591l-1.46-1.46a2.25 2.25 0 0 1-.659-1.591V5.625A2.625 2.625 0 0 0 15.597 3H8.403A2.625 2.625 0 0 0 5.778 5.625v.793a2.25 2.25 0 0 1-.659 1.591l-1.46 1.46A2.25 2.25 0 0 0 3 11.06v3.19a2.25 2.25 0 0 0 2.25 2.25Z',
            $menuCatalogoVeicular,
            $paginaAtual,
            'catalogoOpen'
        );

        render_bloco_submenu(
            'Estoque',
            'M20.25 7.5 12 3 3.75 7.5 12 12l8.25-4.5ZM3.75 7.5V16.5L12 21l8.25-4.5V7.5',
            $menuEstoque,
            $paginaAtual,
            'estoqueOpen'
        );

        render_bloco_submenu(
            'Relatórios',
            'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
            $menuRelatorios,
            $paginaAtual,
            'relatoriosOpen'
        );

        render_bloco_submenu(
            'Configurações',
            'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z',
            $menuConfiguracoes,
            $paginaAtual,
            'configuracoesOpen'
        );
        ?>
    </nav>

    <div class="border-t border-gray-200 px-4 py-4 text-center text-sm text-gray-500">
        <span x-text="open ? 'Projeto Integrador UNIVESP • Grupo 21' : 'PI'"></span>
    </div>
</aside>

<div
    x-data="{ mobilePanel: null }"
    class="md:hidden"
>
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur shadow-lg">
        <div class="flex items-stretch justify-around py-1">
            <?php
            $itemMobile = $menuPrincipal[0];
            $ativoMobile = (arquivo_url($itemMobile['url']) === $paginaAtual);
            ?>
            <a href="<?= htmlspecialchars($itemMobile['url'], ENT_QUOTES, 'UTF-8') ?>"
               class="flex flex-1 flex-col items-center justify-center py-1">
                <div class="flex items-center justify-center rounded-full p-1.5 <?= $ativoMobile ? 'border border-blue-500 bg-blue-50' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 <?= $ativoMobile ? 'text-blue-600' : 'text-gray-500' ?>"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="<?= htmlspecialchars($itemMobile['icon'], ENT_QUOTES, 'UTF-8') ?>" />
                    </svg>
                </div>
                <span class="mt-0.5 text-[11px] leading-none <?= $ativoMobile ? 'text-blue-600' : 'text-gray-500' ?>">
                    <?= htmlspecialchars($itemMobile['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </a>

            <button type="button"
                    @click="mobilePanel = (mobilePanel === 'menu' ? null : 'menu')"
                    class="flex flex-1 flex-col items-center justify-center py-1">
                <div class="flex items-center justify-center rounded-full p-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 text-gray-500"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 12h14M5 6h14M5 18h14" />
                    </svg>
                </div>
                <span class="mt-0.5 text-[11px] leading-none text-gray-500">Mais</span>
            </button>
        </div>
    </nav>

    <div x-show="mobilePanel !== null"
         x-transition.opacity
         class="fixed inset-0 z-40 bg-gray-900/20 backdrop-blur-sm"
         @click="mobilePanel = null"></div>

    <div
        x-show="mobilePanel === 'menu'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed inset-x-0 bottom-0 z-50 flex max-h-[82vh] flex-col rounded-t-2xl border-t border-gray-200 bg-white shadow-2xl"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 pb-2 pt-3">
            <h2 class="text-sm font-semibold text-gray-800">Menu</h2>
            <button type="button" @click="mobilePanel = null" class="rounded-full p-1 hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 text-gray-500"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="space-y-2 overflow-y-auto px-3 py-3
                    [&::-webkit-scrollbar]:w-1
                    [&::-webkit-scrollbar-track]:bg-transparent
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    [&::-webkit-scrollbar-thumb]:bg-gray-300
                    hover:[&::-webkit-scrollbar-thumb]:bg-gray-400">

            <?php
            $gruposMobile = [
                'Principal' => $menuPrincipal,
                'Cadastros' => $menuCadastros,
                'Catálogo Veicular' => $menuCatalogoVeicular,
                'Estoque' => $menuEstoque,
                'Relatórios' => $menuRelatorios,
                'Configurações' => $menuConfiguracoes,
            ];

            foreach ($gruposMobile as $tituloGrupo => $itensGrupo):
            ?>
                <div class="border-t border-gray-200 pt-2 first:border-t-0 first:pt-0">
                    <div class="mb-1 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        <?= htmlspecialchars($tituloGrupo, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php foreach ($itensGrupo as $item):
                        $isAtivo = (arquivo_url($item['url']) === $paginaAtual);
                        $clsBG   = $isAtivo
                            ? 'bg-gradient-to-r from-blue-50 to-sky-50 border border-blue-200'
                            : 'bg-gray-50 border border-gray-200';
                        $clsText = $isAtivo ? 'text-gray-900' : 'text-gray-700';
                        $clsIcon = $isAtivo ? 'text-blue-600' : 'text-gray-500';
                    ?>
                        <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
                           class="ml-3 flex w-auto items-center justify-between rounded-xl px-3 py-2 transition-transform duration-100 active:scale-[0.99] <?= $clsBG ?>"
                           @click="mobilePanel = null">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-4 w-4 <?= $clsIcon ?>"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" />
                                </svg>
                                <span class="ml-3 text-sm <?= $clsText ?>">
                                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="h-4 w-4 text-gray-400"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
