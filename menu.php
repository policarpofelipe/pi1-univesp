<?php
declare(strict_types=1);

$paginaAtual = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

$menuPrincipal = [
    [
        'url'   => 'painel.php',
        'label' => 'Painel',
        'icon'  => 'M3 12l2-2m0 0l2 2m-2-2v6m4-10l2-2m0 0l2 2m-2-2v4m4-4l2-2m0 0l2 2m-2-2v6M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z',
    ],
    [
        'url'   => 'consulta_veiculo.php',
        'label' => 'Consulta por Veículo',
        'icon'  => 'M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z',
    ],
    [
        'url'   => 'relatorio_estoque_baixo.php',
        'label' => 'Estoque Baixo',
        'icon'  => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
    ],
];

$menuCadastros = [
    [
        'url'   => 'listar_categorias_peca.php',
        'label' => 'Categorias de Peça',
        'icon'  => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 0 1 .586 1.414V19a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z',
    ],
    [
        'url'   => 'listar_tipos_peca.php',
        'label' => 'Tipos de Peça',
        'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
    ],
    [
        'url'   => 'listar_marcas_produto.php',
        'label' => 'Marcas de Produto',
        'icon'  => 'M16.5 3.5L20 7l-4.5 4.5M7.5 3.5L4 7l4.5 4.5M12 2v20M3.5 7.5L7 4l4.5 4.5M20.5 7.5L17 4l-4.5 4.5',
    ],
    [
        'url'   => 'listar_produtos.php',
        'label' => 'Produtos',
        'icon'  => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    ],
];

$menuCatalogoVeicular = [
    [
        'url'   => 'listar_marcas_veiculo.php',
        'label' => 'Marcas de Veículo',
        'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z',
    ],
    [
        'url'   => 'listar_modelos_veiculo.php',
        'label' => 'Modelos de Veículo',
        'icon'  => 'M8 7h8m-8 4h5M8 15l2-6m6 6l-2-6M5 19h14a2 2 0 0 0 1.84-2.75L13.74 4a2 2 0 0 0-3.48 0L3.16 16.25A2 2 0 0 0 5 19Z',
    ],
    [
        'url'   => 'listar_veiculos_configuracao.php',
        'label' => 'Configurações Veiculares',
        'icon'  => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    ],
    [
        'url'   => 'listar_aplicacoes_peca.php',
        'label' => 'Aplicações / Compatibilidade',
        'icon'  => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    ],
];

$menuEstoque = [
    [
        'url'   => 'listar_estoques.php',
        'label' => 'Locais de Estoque',
        'icon'  => 'M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4',
    ],
    [
        'url'   => 'listar_movimentacoes_estoque.php',
        'label' => 'Movimentações',
        'icon'  => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    ],
    [
        'url'   => 'saldo_estoque.php',
        'label' => 'Saldo Atual',
        'icon'  => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z',
    ],
];

$menuRelatorios = [
    [
        'url'   => 'relatorio_produtos_por_veiculo.php',
        'label' => 'Produtos por Veículo',
        'icon'  => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z',
    ],
    [
        'url'   => 'relatorio_estoque_baixo.php',
        'label' => 'Estoque Mínimo',
        'icon'  => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
    ],
];

$menuConfiguracoes = [
    [
        'url'   => 'listar_usuarios.php',
        'label' => 'Usuários',
        'icon'  => 'M12 4.354a4 4 0 1 1 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z',
    ],
    [
        'url'   => 'form_config_empresa.php',
        'label' => 'Minha Empresa',
        'icon'  => 'M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4',
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
    >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="<?= $iconSize ?> flex-shrink-0 <?= $classeIcone ?> transition-colors group-hover:text-blue-600"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" />
        </svg>

        <span class="<?= $marginLeft ?> whitespace-nowrap text-left <?= $classeTexto ?> <?= $textSize ?> transition-colors group-hover:text-gray-900">
            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    </a>
    <?php
}

function render_bloco_submenu(string $titulo, string $icone, array $itens, string $paginaAtual): void
{
    $isAtivo = submenu_ativo($itens, $paginaAtual);
    $classeFundo = $isAtivo
        ? 'bg-gradient-to-r from-blue-50 to-sky-50 border-l-4 border-blue-600'
        : 'hover:bg-gray-100';

    $classeIcone = $isAtivo ? 'text-blue-600' : 'text-gray-500';
    $classeTexto = $isAtivo ? 'text-gray-900 font-medium' : 'text-gray-700';
    ?>
    <div class="w-full">
        <div class="group flex w-full items-center justify-start rounded-lg px-4 py-3 transition-all duration-200 <?= $classeFundo ?>">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-5 w-5 flex-shrink-0 <?= $classeIcone ?> transition-colors group-hover:text-blue-600"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="<?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?>" />
            </svg>

            <span class="ml-4 whitespace-nowrap text-left <?= $classeTexto ?> transition-colors group-hover:text-gray-900">
                <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <div class="mt-1 space-y-1 overflow-hidden pl-6">
            <?php foreach ($itens as $item): ?>
                <?php render_item_menu($item, $paginaAtual, true); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<aside
    x-data="{ open: false }"
    class="relative hidden h-screen flex-col border-r border-gray-200 bg-white text-gray-900 shadow-lg transition-all duration-300 md:flex overflow-x-hidden"
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
                 stroke="currentColor" 
                 stroke-width="2">
                <path stroke-linecap="round" 
                      stroke-linejoin="round" 
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        <span x-show="open" x-cloak class="text-lg font-semibold text-gray-800">PI.1 Grupo 21</span>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-3
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
            'M4 6h16M4 12h16M4 18h16',
            $menuCadastros,
            $paginaAtual
        );

        render_bloco_submenu(
            'Catálogo Veicular',
            'M8 7h8m-8 4h5M8 15l2-6m6 6l-2-6M5 19h14a2 2 0 0 0 1.84-2.75L13.74 4a2 2 0 0 0-3.48 0L3.16 16.25A2 2 0 0 0 5 19Z',
            $menuCatalogoVeicular,
            $paginaAtual
        );

        render_bloco_submenu(
            'Estoque',
            'M4 8l8-4 8 4-8 4-8-4zm0 6l8-4 8 4-8 4-8-4zm0 6l8-4 8 4-8 4-8-4z',
            $menuEstoque,
            $paginaAtual
        );

        render_bloco_submenu(
            'Relatórios',
            'M4 7h16M4 12h10M4 17h6',
            $menuRelatorios,
            $paginaAtual
        );

        render_bloco_submenu(
            'Configurações',
            'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            $menuConfiguracoes,
            $paginaAtual
        );
        ?>
    </nav>

    <div class="border-t border-gray-200 px-4 py-4 text-center text-sm text-gray-500">
        <span x-show="open" x-cloak>Projeto Integrador UNIVESP • Grupo 21</span>
        <span x-show="!open" x-cloak>PI</span>
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