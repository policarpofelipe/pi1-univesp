<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| COMPONENTES VISUAIS REUTILIZÁVEIS - TAILWIND
|--------------------------------------------------------------------------
| Uso:
| require __DIR__ . '/componentes.php';
|--------------------------------------------------------------------------
*/

function seguro(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function classes_botao_base(): string
{
    return 'inline-flex items-center justify-center gap-2 rounded-lg border border-transparent px-4 py-2 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100 hover:scale-105 active:scale-95';
}

function classes_botao_icone(): string
{
    return 'inline-flex items-center justify-center h-8 w-8 rounded-lg border border-transparent transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1 hover:scale-110 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50';
}

function mapa_variantes_botao(): array
{
    return [
        'salvar'   => 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500',
        'cancelar' => 'bg-gray-300 hover:bg-gray-400 text-gray-800 focus:ring-gray-400',
        'perigo'   => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        'editar'   => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        'busca'    => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        'atalho'   => 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500',
        'alerta'   => 'bg-yellow-500 hover:bg-yellow-600 text-white focus:ring-yellow-500',
        'ver'      => 'bg-cyan-600 hover:bg-cyan-700 text-white focus:ring-cyan-500',
        'itens'    => 'bg-teal-600 hover:bg-teal-700 text-white focus:ring-teal-500',
        'pagina'   => 'bg-slate-600 hover:bg-slate-700 text-white focus:ring-slate-500',
        'link'     => 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500',
    ];
}

function classe_botao(string $variante = 'atalho', bool $icone = false): string
{
    $mapa = mapa_variantes_botao();
    $cor = $mapa[$variante] ?? $mapa['atalho'];
    $base = $icone ? classes_botao_icone() : classes_botao_base();

    return trim($base . ' ' . $cor);
}

function classe_input(): string
{
    return 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm leading-tight text-gray-900 transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500';
}

function classe_select(): string
{
    return 'h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm leading-tight text-gray-900 transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500';
}

function classe_textarea(): string
{
    return 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500';
}

function classe_label(): string
{
    return 'mb-1 block text-sm font-medium text-gray-700';
}

function classe_box(): string
{
    return 'rounded-xl bg-white p-4 shadow-lg';
}

function classe_linha_form(): string
{
    return 'mb-4';
}

function botao_submit(string $texto, string $variante = 'salvar', array $attrs = []): string
{
    $atributos = html_atributos($attrs);
    return '<button type="submit" class="' . seguro(classe_botao($variante)) . '" ' . $atributos . '>' . seguro($texto) . '</button>';
}

function botao_link(string $url, string $texto, string $variante = 'atalho', array $attrs = []): string
{
    $atributos = html_atributos($attrs);
    return '<a href="' . seguro($url) . '" class="' . seguro(classe_botao($variante)) . '" ' . $atributos . '>' . seguro($texto) . '</a>';
}

function botao_abrir_link(string $url, string $titulo = 'Abrir link'): string
{
    return '
    <a href="' . seguro($url) . '" target="_blank" rel="noopener noreferrer"
       title="' . seguro($titulo) . '"
       class="' . seguro(classe_botao('link', true)) . '">
        ' . icone_link_externo() . '
    </a>';
}

function botao_itens(string $url, string $titulo = 'Ver itens'): string
{
    return '
    <a href="' . seguro($url) . '"
       title="' . seguro($titulo) . '"
       class="' . seguro(classe_botao('itens', true)) . '">
        ' . icone_itens() . '
    </a>';
}

function botao_ver_pagina(string $url, string $titulo = 'Ver página'): string
{
    return '
    <a href="' . seguro($url) . '"
       title="' . seguro($titulo) . '"
       class="' . seguro(classe_botao('pagina', true)) . '">
        ' . icone_olho() . '
    </a>';
}

function botao_ver_drawer(string $url, string $titulo = 'Ver'): string
{
    return '
    <button type="button"
        onclick="window.dispatchEvent(new CustomEvent(\'open-drawer\', { detail: { url: \'' . addslashes($url) . '\', title: \'' . addslashes($titulo) . '\' } }))"
        title="' . seguro($titulo) . '"
        class="' . seguro(classe_botao('ver', true)) . '">
        ' . icone_olho() . '
    </button>';
}

function botao_editar_drawer(string $url, string $titulo = 'Editar'): string
{
    return '
    <button type="button"
        onclick="window.dispatchEvent(new CustomEvent(\'open-drawer\', { detail: { url: \'' . addslashes($url) . '\', title: \'' . addslashes($titulo) . '\' } }))"
        title="' . seguro($titulo) . '"
        class="' . seguro(classe_botao('editar', true)) . '">
        ' . icone_lapis() . '
    </button>';
}

function botao_excluir(string $url, string $mensagem = 'Tem certeza?', string $titulo = 'Excluir'): string
{
    return '
    <a href="' . seguro($url) . '"
       onclick="return confirm(\'' . addslashes($mensagem) . '\')"
       title="' . seguro($titulo) . '"
       class="' . seguro(classe_botao('perigo')) . '">
        ' . seguro($titulo) . '
    </a>';
}

function botao_vinculo(string $titulo = 'Possui vínculo'): string
{
    return '
    <span title="' . seguro($titulo) . '"
          class="inline-flex h-8 w-8 cursor-default items-center justify-center rounded-lg border border-transparent bg-slate-400 text-white">
        ' . icone_vinculo() . '
    </span>';
}

function input_texto(string $name, string $value = '', array $attrs = []): string
{
    $attrs = array_merge([
        'type'  => 'text',
        'name'  => $name,
        'value' => $value,
        'class' => classe_input(),
    ], $attrs);

    return '<input ' . html_atributos($attrs) . '>';
}

function input_email(string $name, string $value = '', array $attrs = []): string
{
    $attrs = array_merge([
        'type'  => 'email',
        'name'  => $name,
        'value' => $value,
        'class' => classe_input(),
    ], $attrs);

    return '<input ' . html_atributos($attrs) . '>';
}

function input_senha(string $name, array $attrs = []): string
{
    $attrs = array_merge([
        'type'  => 'password',
        'name'  => $name,
        'class' => classe_input(),
    ], $attrs);

    return '<input ' . html_atributos($attrs) . '>';
}

function input_numero(string $name, string $value = '', array $attrs = []): string
{
    $attrs = array_merge([
        'type'  => 'number',
        'name'  => $name,
        'value' => $value,
        'class' => classe_input(),
    ], $attrs);

    return '<input ' . html_atributos($attrs) . '>';
}

function select_padrao(string $name, array $opcoes, $selecionado = null, array $attrs = []): string
{
    $attrs = array_merge([
        'name'  => $name,
        'class' => classe_select(),
    ], $attrs);

    $html = '<select ' . html_atributos($attrs) . '>';

    foreach ($opcoes as $valor => $texto) {
        $selected = ((string)$valor === (string)$selecionado) ? ' selected' : '';
        $html .= '<option value="' . seguro((string)$valor) . '"' . $selected . '>' . seguro((string)$texto) . '</option>';
    }

    $html .= '</select>';

    return $html;
}

function textarea_padrao(string $name, string $value = '', array $attrs = []): string
{
    $attrs = array_merge([
        'name'  => $name,
        'class' => classe_textarea(),
        'rows'  => '4',
    ], $attrs);

    return '<textarea ' . html_atributos($attrs) . '>' . seguro($value) . '</textarea>';
}

function html_atributos(array $attrs): string
{
    $partes = [];

    foreach ($attrs as $chave => $valor) {
        if ($valor === null || $valor === false) {
            continue;
        }

        if ($valor === true) {
            $partes[] = seguro((string)$chave);
            continue;
        }

        $partes[] = seguro((string)$chave) . '="' . seguro((string)$valor) . '"';
    }

    return implode(' ', $partes);
}

function icone_link_externo(): string
{
    return '
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
    </svg>';
}

function icone_itens(): string
{
    return '
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M8.242 5.992h12m-12 6.003H20.24m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H5.24m-1.92 2.577a1.125 1.125 0 1 1 1.591 1.59l-1.83 1.83h2.16M2.99 15.745h1.125a1.125 1.125 0 0 1 0 2.25H3.74m0-.002h.375a1.125 1.125 0 0 1 0 2.25H2.99" />
    </svg>';
}

function icone_olho(): string
{
    return '
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>';
}

function icone_lapis(): string
{
    return '
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
    </svg>';
}

function icone_lixeira(): string
{
    return '
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
    </svg>';
}

function icone_vinculo(): string
{
    return '
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
    </svg>';
}