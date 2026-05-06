<?php
declare(strict_types=1);

$paginaAtual = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

$menuPrincipal = [
    [
        'url'   => 'consulta_veiculo.php',
        'label' => 'Consulta',
        'icon'  => 'M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z',
    ],
    [
        'url'   => 'painel.php',
        'label' => 'Painel',
        'icon'  => 'M5 4h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1M5 16h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1M15 12h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1M15 4h4a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1',
    ],
];

$menuCadastros = [
    [
        'url'   => 'listar_categorias_peca.php',
        'label' => 'Categorias de Peça',
        'icon'  => 'M3 7.5h18M3 12h18M3 16.5h12',
    ],
    [
        'url'   => 'listar_tipos_peca.php',
        'label' => 'Tipos de Peça',
        'icon'  => 'M12 22a3 3 0 1 1 0 -6a3 3 0 0 1 0 6M12 16v-12M13 2h-2v2h2v-2M9 11l6 -1M9 14l6 -1M9 8l6 -1',
    ],
    [
        'url'   => 'listar_marcas_produto.php',
        'label' => 'Marcas de Produto',
        'icon'  => 'M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0M10 15v-6h2a2 2 0 1 1 0 4h-2M14 15l-2 -2',
    ],
    [
        'url'   => 'listar_produtos.php',
        'label' => 'Produtos',
        'icon'  => 'M17 3.34a10 10 0 1 1 -15 8.66l.005 -.324a10 10 0 0 1 14.995 -8.336m-13 8.66a8 8 0 0 0 7 7.937v-5.107a3 3 0 0 1 -1.898 -2.05l-5.07 -1.504q -.031 .36 -.032 .725m15.967 -.725l-5.069 1.503a3 3 0 0 1 -1.897 2.051v5.108a8 8 0 0 0 6.985 -8.422zm-11.967 -6.204a8 8 0 0 0 -3.536 4.244l4.812 1.426a3 3 0 0 1 5.448 0l4.812 -1.426a8 8 0 0 0 -11.536 -4.244',
    ],
];

$menuCatalogoVeicular = [
    [
        'url'   => 'listar_marcas_veiculo.php',
        'label' => 'Marcas de Veículo',
        'icon'  => 'M12 21a9 9 0 0 0 9 -9a9 9 0 0 0 -9 -9a9 9 0 0 0 -9 9a9 9 0 0 0 9 9M5 7l4.5 11l1.5 -5h2l1.5 5l4.5 -11M9 4l2 6h2l2 -6',
    ],
    [
        'url'   => 'listar_modelos_veiculo.php',
        'label' => 'Modelos de Veículo',
        'icon'  => 'M9 17h6M9 17a2 2 0 1 1 -4 0a2 2 0 0 1 4 0M19 17a2 2 0 1 1 -4 0a2 2 0 0 1 4 0M17 10l-2 -3M19 17h2v-5a2 2 0 0 0 -2 -2h-5v2h-2.586a1 1 0 0 1 -.707 -.293l-1.121 -1.121a2 2 0 0 0 -1.414 -.586h-4.172a1 1 0 0 0 -1 1v6h2',
    ],
    [
        'url'   => 'listar_veiculos_configuracao.php',
        'label' => 'Configurações Veiculares',
        'icon'  => 'M8 17a2 2 0 1 0 4 0a2 2 0 1 0 -4 0M7 6l4 5h1a2 2 0 0 1 2 2v4h-2M8 17h-5M3 11h8M5 11v-5M7 6h-4M14 8v-2M19 12h2M17.5 15.5l1.5 1.5M17.5 8.5l1.5 -1.5',
    ],
    [
        'url'   => 'listar_aplicacoes_produto.php',
        'label' => 'Aplicações do Produto',
        'icon'  => 'M4 7h3a1 1 0 0 0 1 -1v-1a2 2 0 0 1 4 0v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v3a1 1 0 0 0 1 1h1a2 2 0 0 1 0 4h-1a1 1 0 0 0 -1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-1a2 2 0 0 0 -4 0v1a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h1a2 2 0 0 0 0 -4h-1a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1',
    ],
];

$menuEstoque = [
    [
        'url'   => 'listar_estoques.php',
        'label' => 'Locais de Estoque',
        'icon'  => 'M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0',
    ],
    [
        'url'   => 'listar_movimentacoes_estoque.php',
        'label' => 'Movimentações',
        'icon'  => 'M11 16h10M11 16l4 4M11 16l4 -4M13 8h-10M13 8l-4 4M13 8l-4 -4',
    ],
    [
        'url'   => 'saldo_estoque.php',
        'label' => 'Saldo Atual',
        'icon'  => 'M11 6h9M11 12h9M12 18h8M4 16a2 2 0 1 1 4 0c0 .591 -.5 1 -1 1.5l-3 2.5h4M6 10v-6l-2 2',
    ],
];

$menuRelatorios = [
    [
        'url'   => 'relatorio_produtos_por_veiculo.php',
        'label' => 'Produtos por Veículo',
        'icon'  => 'M5 17a2 2 0 1 0 4 0a2 2 0 0 0 -4 0M16 17a2 2 0 1 0 4 0a2 2 0 0 0 -4 0M5 9l2 -4h7.438a2 2 0 0 1 1.94 1.515l.622 2.485h3a2 2 0 0 1 2 2v3M10 9v-4M2 7v4M22.001 14.001a4.992 4.992 0 0 0 -4.001 -2.001a4.992 4.992 0 0 0 -4 2h-3a4.998 4.998 0 0 0 -8.003 .003M5 12v-3h13',
    ],
    [
        'url'   => 'relatorio_estoque_baixo.php',
        'label' => 'Estoque Mínimo',
        'icon'  => 'M4 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0M12 17h-6v-14h-2M6 5l14 1l-.79 5.526M16 13h-10M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0M19.001 15.5v1.5M19.001 21v1.5M22.032 17.25l-1.299 .75M17.27 20l-1.3 .75M15.97 17.25l1.3 .75M20.733 20l1.3 .75',
    ],
];

$menuConfiguracoes = [
    [
        'url'   => 'listar_usuarios.php',
        'label' => 'Usuários',
        'icon'  => 'M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0M9 10a3 3 0 1 0 6 0a3 3 0 1 0 -6 0M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855',
    ],
    [
        'url'   => 'form_config_empresa.php',
        'label' => 'Minha Empresa',
        'icon'  => 'M4 21v-15c0 -1 1 -2 2 -2h5c1 0 2 1 2 2v15M16 8h2c1 0 2 1 2 2v11M3 21h18M10 12v.01M10 16v.01M10 8v.01M7 12v.01M7 16v.01M7 8v.01M17 12v.01M17 16v.01',
    ],
    [
        'url'   => 'logout.php',
        'label' => 'Sair',
        'icon'  => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1',
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

function render_item_menu(array $item, string $paginaAtual, bool $sub = false): void
{
    $isAtivo = (arquivo_url($item['url']) === $paginaAtual);

    $classeFundo = $isAtivo
        ? 'bg-white/15 border border-white/25 shadow-sm'
        : 'hover:bg-white/10 border border-transparent';

    $classeIcone = $isAtivo ? 'text-white' : 'text-slate-300';
    $classeTexto = $isAtivo ? 'text-white font-semibold' : 'text-slate-200';
    $paddingY = $sub ? 'py-2' : 'py-3';
    $marginLeft = $sub ? 'ml-3' : 'ml-4';
    $iconSize = $sub ? 'h-4 w-4' : 'h-5 w-5';
    $textSize = $sub ? 'text-sm' : '';

    ?>
    <a
        href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
        class="menu-link group flex w-full items-center justify-start rounded-lg px-4 <?= $paddingY ?> transition-all duration-200 <?= $classeFundo ?>"
    >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="<?= $iconSize ?> flex-shrink-0 <?= $classeIcone ?> transition-colors group-hover:text-white"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" />
        </svg>

        <span class="menu-label <?= $marginLeft ?> whitespace-nowrap text-left <?= $classeTexto ?> <?= $textSize ?> transition-colors group-hover:text-white">
            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    </a>
    <?php
}

function render_bloco_submenu(string $titulo, string $icone, array $itens, string $paginaAtual): void
{
    $isAtivo = submenu_ativo($itens, $paginaAtual);
    $classeFundo = $isAtivo
        ? 'bg-white/15 border border-white/25 shadow-sm'
        : 'hover:bg-white/10 border border-transparent';

    $classeIcone = $isAtivo ? 'text-white' : 'text-slate-300';
    $classeTexto = $isAtivo ? 'text-white font-semibold' : 'text-slate-200';
    ?>
    <div class="w-full">
        <div class="menu-link group flex w-full items-center justify-start rounded-lg px-4 py-3 transition-all duration-200 <?= $classeFundo ?>">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-5 w-5 flex-shrink-0 <?= $classeIcone ?> transition-colors group-hover:text-white"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="<?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?>" />
            </svg>

            <span class="menu-label ml-4 whitespace-nowrap text-left <?= $classeTexto ?> transition-colors group-hover:text-white">
                <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <div class="submenu-items mt-1 space-y-1 overflow-hidden pl-6">
            <?php foreach ($itens as $item): ?>
                <?php render_item_menu($item, $paginaAtual, true); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<style>
    #desktop-menu.menu-collapsed {
        width: 5rem;
    }
    #desktop-menu.menu-expanded {
        width: 18rem;
    }
    #desktop-menu.menu-collapsed .menu-label,
    #desktop-menu.menu-collapsed .submenu-items,
    #desktop-menu.menu-collapsed .menu-footer-open {
        display: none;
    }
    #desktop-menu.menu-expanded .menu-footer-closed {
        display: none;
    }
    #desktop-menu.menu-collapsed .menu-link {
        justify-content: center;
    }
</style>

<aside
    id="desktop-menu"
    data-open="true"
    class="menu-expanded relative m-3 hidden h-[calc(100vh-1.5rem)] flex-col rounded-2xl border border-slate-700/50 bg-gradient-to-b from-slate-900 to-slate-800 text-white shadow-xl transition-all duration-300 md:flex overflow-x-hidden"
>
    <div class="flex items-center justify-between border-b border-slate-700/60 px-4 py-4">
        <button
            type="button"
            onclick="toggleDesktopMenu()"
            class="rounded-lg p-2 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-sky-400"
            title="Alternar menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="h-6 w-6 text-slate-100" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke="currentColor" 
                 stroke-width="2">
                <path stroke-linecap="round" 
                      stroke-linejoin="round" 
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        <span class="menu-label text-lg font-semibold text-white">PI.1 Grupo 21</span>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-3
                [&::-webkit-scrollbar]:w-1
                [&::-webkit-scrollbar-track]:bg-transparent
                [&::-webkit-scrollbar-thumb]:rounded-full
                [&::-webkit-scrollbar-thumb]:bg-slate-500
                hover:[&::-webkit-scrollbar-thumb]:bg-slate-400
                [scrollbar-width]:thin
                [scrollbar-color]:rgb(100_116_139)_transparent">

        <?php foreach ($menuPrincipal as $item): ?>
            <?php render_item_menu($item, $paginaAtual); ?>
        <?php endforeach; ?>

        <?php
        render_bloco_submenu(
            'Cadastros',
            'M2 5a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2l0 -8M6 7l0 .01M10 7l0 .01M14 7l0 .01M18 7l0 .01M6 11l0 .01M18 11l0 .01M10 11l4 0M10 21l2 -2l2 2',
            $menuCadastros,
            $paginaAtual
        );

        render_bloco_submenu(
            'Catálogo Veicular',
            'M5 20a2 2 0 1 0 4 0a2 2 0 0 0 -4 0M15 20a2 2 0 1 0 4 0a2 2 0 0 0 -4 0M5 20h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2M15 20h-6M3 14h15M12 14v-5M3 6l9 -4l9 4',
            $menuCatalogoVeicular,
            $paginaAtual
        );

        render_bloco_submenu(
            'Estoque',
            'M4 17v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-1M8 16h8M8.322 12.582l7.956 .836M8.787 9.168l7.826 1.664M10.096 5.764l7.608 2.472',
            $menuEstoque,
            $paginaAtual
        );

        render_bloco_submenu(
            'Relatórios',
            'M14 3v4a1 1 0 0 0 1 1h4M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2M9 17l0 -5M12 17l0 -1M15 17l0 -3',
            $menuRelatorios,
            $paginaAtual
        );

        render_bloco_submenu(
            'Configurações',
            'M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0',
            $menuConfiguracoes,
            $paginaAtual
        );
        ?>
    </nav>

    <div class="border-t border-slate-700/60 px-4 py-4 text-center text-sm text-slate-300">
        <span class="menu-footer-open">Projeto Integrador UNIVESP • Grupo 21</span>
        <span class="menu-footer-closed text-slate-200">PI</span>
    </div>
</aside>

<script>
    function toggleDesktopMenu() {
        const menu = document.getElementById('desktop-menu');
        if (!menu) return;
        const isOpen = menu.dataset.open === 'true';
        menu.dataset.open = isOpen ? 'false' : 'true';
        menu.classList.toggle('menu-expanded', !isOpen);
        menu.classList.toggle('menu-collapsed', isOpen);
    }
</script>

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
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
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
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 6h16M4 12h16M4 18h16" />
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
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
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
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" />
                                </svg>
                                <span class="ml-3 text-sm <?= $clsText ?>">
                                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="h-4 w-4 text-gray-400"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>