<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/lib/produto_imagens.php';

date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assistente_cadastro_produto.php?erro=erro_interno');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$assistenteId = (int)($_POST['assistente_id'] ?? 0);
$acao = trim((string)($_POST['acao'] ?? 'salvar_etapa_1'));
if ($usuarioId <= 0 || $assistenteId <= 0) {
    header('Location: assistente_cadastro_produto.php?erro=assistente_invalido');
    exit;
}

$redirecionarErro = function (string $erro) use ($assistenteId): void {
    header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&erro=' . urlencode($erro));
    exit;
};

$stmtAssistente = $pdo->prepare("
    SELECT *
    FROM assistente_cadastro_produto
    WHERE id = :id
      AND usuario_id = :usuario_id
      AND status IN ('rascunho', 'em_andamento')
    LIMIT 1
");
$stmtAssistente->bindValue(':id', $assistenteId, PDO::PARAM_INT);
$stmtAssistente->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
$stmtAssistente->execute();
$assistente = $stmtAssistente->fetch(PDO::FETCH_ASSOC);
if (!$assistente) {
    $redirecionarErro('assistente_invalido');
}

if (
    $acao === 'adicionar_aplicacao' ||
    $acao === 'remover_aplicacao' ||
    $acao === 'salvar_etapa_2' ||
    $acao === 'pular_etapa_2' ||
    $acao === 'voltar_etapa_1' ||
    $acao === 'salvar_estoque_inicial' ||
    $acao === 'salvar_etapa_3' ||
    $acao === 'pular_etapa_3' ||
    $acao === 'voltar_etapa_2' ||
    $acao === 'enviar_imagem' ||
    $acao === 'remover_imagem' ||
    $acao === 'definir_imagem_principal' ||
    $acao === 'salvar_etapa_4' ||
    $acao === 'pular_etapa_4' ||
    $acao === 'voltar_etapa_3'
) {
    $produtoIdAtual = (int)($assistente['produto_id'] ?? 0);
    if ($produtoIdAtual <= 0) {
        $redirecionarErro('produto_nao_disponivel');
    }

    $dadosJson = [];
    if (!empty($assistente['dados_json'])) {
        $dec = json_decode((string)$assistente['dados_json'], true);
        if (is_array($dec)) {
            $dadosJson = $dec;
        }
    }

    try {
        if ($acao === 'adicionar_aplicacao') {
            $veiculoConfiguracaoId = (int)($_POST['veiculo_configuracao_id'] ?? 0);
            $observacao = trim((string)($_POST['observacao'] ?? ''));
            if ($veiculoConfiguracaoId <= 0) {
                $redirecionarErro('veiculo_obrigatorio');
            }

            $stmtVc = $pdo->prepare("SELECT id FROM veiculos_configuracao WHERE id = :id AND ativo = 1 LIMIT 1");
            $stmtVc->bindValue(':id', $veiculoConfiguracaoId, PDO::PARAM_INT);
            $stmtVc->execute();
            if (!$stmtVc->fetch()) {
                $redirecionarErro('veiculo_obrigatorio');
            }

            $stmtDup = $pdo->prepare("
                SELECT id
                FROM aplicacoes_produto
                WHERE produto_id = :produto_id
                  AND veiculo_configuracao_id = :veiculo_configuracao_id
                LIMIT 1
            ");
            $stmtDup->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtDup->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
            $stmtDup->execute();
            if ($stmtDup->fetch()) {
                $redirecionarErro('aplicacao_duplicada');
            }

            $ins = $pdo->prepare("
                INSERT INTO aplicacoes_produto (
                    produto_id,
                    veiculo_configuracao_id,
                    observacao,
                    ativo,
                    criado_em,
                    atualizado_em
                ) VALUES (
                    :produto_id,
                    :veiculo_configuracao_id,
                    :observacao,
                    1,
                    NOW(),
                    NOW()
                )
            ");
            $ins->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $ins->bindValue(':veiculo_configuracao_id', $veiculoConfiguracaoId, PDO::PARAM_INT);
            $ins->bindValue(':observacao', $observacao !== '' ? $observacao : null, $observacao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $ins->execute();

            unset($dadosJson['pendencias']['produto_sem_aplicabilidade']);
            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 2,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=aplicacao_adicionada');
            exit;
        }

        if ($acao === 'remover_aplicacao') {
            $aplicacaoId = (int)($_POST['aplicacao_id'] ?? 0);
            if ($aplicacaoId <= 0) {
                $redirecionarErro('aplicacao_invalida');
            }

            $stmtApp = $pdo->prepare("
                SELECT id
                FROM aplicacoes_produto
                WHERE id = :id
                  AND produto_id = :produto_id
                LIMIT 1
            ");
            $stmtApp->bindValue(':id', $aplicacaoId, PDO::PARAM_INT);
            $stmtApp->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtApp->execute();
            if (!$stmtApp->fetch()) {
                $redirecionarErro('aplicacao_invalida');
            }

            $del = $pdo->prepare("DELETE FROM aplicacoes_produto WHERE id = :id AND produto_id = :produto_id LIMIT 1");
            $del->bindValue(':id', $aplicacaoId, PDO::PARAM_INT);
            $del->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $del->execute();

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM aplicacoes_produto WHERE produto_id = :produto_id AND ativo = 1");
            $stmtCount->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtCount->execute();
            $totalAplicacoes = (int)$stmtCount->fetchColumn();
            if ($totalAplicacoes <= 0) {
                $dadosJson['pendencias']['produto_sem_aplicabilidade'] = true;
            }

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 2,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=aplicacao_removida');
            exit;
        }

        if ($acao === 'salvar_etapa_2') {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM aplicacoes_produto WHERE produto_id = :produto_id AND ativo = 1");
            $stmtCount->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtCount->execute();
            $totalAplicacoes = (int)$stmtCount->fetchColumn();
            if ($totalAplicacoes <= 0) {
                $dadosJson['pendencias']['produto_sem_aplicabilidade'] = true;
            } else {
                unset($dadosJson['pendencias']['produto_sem_aplicabilidade']);
            }

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 3,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa2_salva');
            exit;
        }

        if ($acao === 'pular_etapa_2') {
            $dadosJson['pendencias']['produto_sem_aplicabilidade'] = true;
            $dadosJson['etapa_2_pulada'] = true;

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 3,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa2_pulada');
            exit;
        }

        if ($acao === 'voltar_etapa_1') {
            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 1,
                    status = 'em_andamento',
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId);
            exit;
        }

        if ($acao === 'salvar_estoque_inicial') {
            $estoqueId = (int)($_POST['estoque_id'] ?? 0);
            $quantidadeInformada = trim((string)($_POST['quantidade_inicial'] ?? ''));
            $observacao = trim((string)($_POST['observacao_estoque'] ?? ''));

            if ($estoqueId <= 0) {
                $redirecionarErro('estoque_obrigatorio');
            }
            if ($quantidadeInformada === '') {
                $redirecionarErro('quantidade_obrigatoria');
            }

            $quantidadeNormalizada = str_replace(',', '.', $quantidadeInformada);
            if (!is_numeric($quantidadeNormalizada)) {
                $redirecionarErro('quantidade_invalida');
            }
            $quantidade = (float)$quantidadeNormalizada;
            if ($quantidade <= 0) {
                $redirecionarErro('quantidade_invalida');
            }

            $stmtEstoque = $pdo->prepare("SELECT id FROM estoques WHERE id = :id AND ativo = 1 LIMIT 1");
            $stmtEstoque->bindValue(':id', $estoqueId, PDO::PARAM_INT);
            $stmtEstoque->execute();
            if (!$stmtEstoque->fetch()) {
                $redirecionarErro('estoque_obrigatorio');
            }

            $prefixoAssistente = '[Assistente #' . $assistenteId . '] Estoque inicial';
            $observacaoFinal = $prefixoAssistente . ($observacao !== '' ? ' - ' . $observacao : '');

            // Regra da fase: apenas um registro inicial por produto+estoque dentro do assistente.
            $stmtExistente = $pdo->prepare("
                SELECT id
                FROM movimentacoes_estoque
                WHERE produto_id = :produto_id
                  AND estoque_id = :estoque_id
                  AND tipo_movimento = 'entrada'
                  AND observacao LIKE :prefixo
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmtExistente->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtExistente->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
            $stmtExistente->bindValue(':prefixo', $prefixoAssistente . '%', PDO::PARAM_STR);
            $stmtExistente->execute();
            $movExistente = $stmtExistente->fetch(PDO::FETCH_ASSOC);

            if ($movExistente) {
                $upMov = $pdo->prepare("
                    UPDATE movimentacoes_estoque
                    SET quantidade = :quantidade,
                        observacao = :observacao
                    WHERE id = :id
                    LIMIT 1
                ");
                $upMov->bindValue(':quantidade', number_format($quantidade, 2, '.', ''), PDO::PARAM_STR);
                $upMov->bindValue(':observacao', $observacaoFinal, PDO::PARAM_STR);
                $upMov->bindValue(':id', (int)$movExistente['id'], PDO::PARAM_INT);
                $upMov->execute();
            } else {
                $insMov = $pdo->prepare("
                    INSERT INTO movimentacoes_estoque (
                        produto_id,
                        estoque_id,
                        usuario_id,
                        tipo_movimento,
                        quantidade,
                        observacao,
                        criado_em
                    ) VALUES (
                        :produto_id,
                        :estoque_id,
                        :usuario_id,
                        'entrada',
                        :quantidade,
                        :observacao,
                        NOW()
                    )
                ");
                $insMov->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
                $insMov->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
                $insMov->bindValue(':usuario_id', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $insMov->bindValue(':quantidade', number_format($quantidade, 2, '.', ''), PDO::PARAM_STR);
                $insMov->bindValue(':observacao', $observacaoFinal, PDO::PARAM_STR);
                $insMov->execute();
            }

            unset($dadosJson['pendencias']['produto_sem_estoque_inicial']);
            unset($dadosJson['etapa_3_pulada']);
            $dadosJson['etapa_3'] = [
                'estoque_id' => $estoqueId,
                'quantidade_inicial' => number_format($quantidade, 2, '.', ''),
            ];

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 3,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=estoque_inicial_salvo');
            exit;
        }

        if ($acao === 'salvar_etapa_3') {
            $stmtSaldo = $pdo->prepare("
                SELECT COUNT(*)
                FROM movimentacoes_estoque
                WHERE produto_id = :produto_id
            ");
            $stmtSaldo->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtSaldo->execute();
            $qtdMov = (int)$stmtSaldo->fetchColumn();
            if ($qtdMov <= 0) {
                $dadosJson['pendencias']['produto_sem_estoque_inicial'] = true;
            } else {
                unset($dadosJson['pendencias']['produto_sem_estoque_inicial']);
            }

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 4,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa3_salva');
            exit;
        }

        if ($acao === 'pular_etapa_3') {
            $dadosJson['pendencias']['produto_sem_estoque_inicial'] = true;
            $dadosJson['etapa_3_pulada'] = true;

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 4,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa3_pulada');
            exit;
        }

        if ($acao === 'voltar_etapa_2') {
            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 2,
                    status = 'em_andamento',
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId);
            exit;
        }

        if ($acao === 'enviar_imagem') {
            if (!produtoExiste($pdo, $produtoIdAtual)) {
                $redirecionarErro('produto_nao_disponivel');
            }
            if (!isset($_FILES['imagens'])) {
                $redirecionarErro('imagem_obrigatoria');
            }

            $resultadoUpload = salvarImagensProduto($pdo, $produtoIdAtual, $_FILES['imagens']);
            if (($resultadoUpload['sucesso'] ?? 0) <= 0) {
                $mensagem = (string)($resultadoUpload['erros'][0] ?? '');
                if (stripos($mensagem, 'formato inválido') !== false || stripos($mensagem, 'mime inválido') !== false) {
                    $redirecionarErro('upload_invalido');
                }
                $redirecionarErro('imagem_obrigatoria');
            }

            unset($dadosJson['pendencias']['produto_sem_imagem']);
            unset($dadosJson['etapa_4_pulada']);
            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 4,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=imagem_enviada');
            exit;
        }

        if ($acao === 'remover_imagem') {
            $imagemId = (int)($_POST['imagem_id'] ?? 0);
            if ($imagemId <= 0) {
                $redirecionarErro('imagem_invalida');
            }
            if (!obterImagemProduto($pdo, $produtoIdAtual, $imagemId)) {
                $redirecionarErro('imagem_invalida');
            }
            if (!excluirImagemProduto($pdo, $produtoIdAtual, $imagemId)) {
                $redirecionarErro('erro_interno');
            }

            $qtdImagens = count(listarImagensProduto($pdo, $produtoIdAtual));
            if ($qtdImagens <= 0) {
                $dadosJson['pendencias']['produto_sem_imagem'] = true;
            }

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 4,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=imagem_removida');
            exit;
        }

        if ($acao === 'definir_imagem_principal') {
            $imagemId = (int)($_POST['imagem_id'] ?? 0);
            if ($imagemId <= 0 || !obterImagemProduto($pdo, $produtoIdAtual, $imagemId)) {
                $redirecionarErro('imagem_principal_invalida');
            }
            if (!definirImagemPrincipal($pdo, $produtoIdAtual, $imagemId)) {
                $redirecionarErro('imagem_principal_invalida');
            }

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=imagem_principal_definida');
            exit;
        }

        if ($acao === 'salvar_etapa_4') {
            $qtdImagens = count(listarImagensProduto($pdo, $produtoIdAtual));
            if ($qtdImagens <= 0) {
                $dadosJson['pendencias']['produto_sem_imagem'] = true;
            } else {
                unset($dadosJson['pendencias']['produto_sem_imagem']);
            }

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 5,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa4_salva');
            exit;
        }

        if ($acao === 'pular_etapa_4') {
            $dadosJson['pendencias']['produto_sem_imagem'] = true;
            $dadosJson['etapa_4_pulada'] = true;

            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 5,
                    status = 'em_andamento',
                    dados_json = :dados_json,
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':dados_json', json_encode($dadosJson, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa4_pulada');
            exit;
        }

        if ($acao === 'voltar_etapa_3') {
            $up = $pdo->prepare("
                UPDATE assistente_cadastro_produto
                SET etapa_atual = 3,
                    status = 'em_andamento',
                    atualizado_em = NOW()
                WHERE id = :id
                  AND usuario_id = :usuario_id
                LIMIT 1
            ");
            $up->bindValue(':id', $assistenteId, PDO::PARAM_INT);
            $up->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $up->execute();

            header('Location: assistente_cadastro_produto.php?id=' . $assistenteId);
            exit;
        }
    } catch (Throwable $e) {
        $redirecionarErro('erro_interno');
    }
}

$categoriaModo = (string)($_POST['categoria_modo'] ?? 'existente');
$categoriaId = (int)($_POST['categoria_peca_id'] ?? 0);
$novaCategoriaNome = trim((string)($_POST['nova_categoria_nome'] ?? ''));

$tipoModo = (string)($_POST['tipo_modo'] ?? 'existente');
$tipoId = (int)($_POST['tipo_peca_id'] ?? 0);
$novoTipoNome = trim((string)($_POST['novo_tipo_nome'] ?? ''));

$marcaModo = (string)($_POST['marca_modo'] ?? 'existente');
$marcaId = (int)($_POST['marca_produto_id'] ?? 0);
$novaMarcaNome = trim((string)($_POST['nova_marca_nome'] ?? ''));

$skuInterno = trim((string)($_POST['sku_interno'] ?? ''));
$codigoFabricante = trim((string)($_POST['codigo_fabricante'] ?? ''));
$nomeComercial = trim((string)($_POST['nome_comercial'] ?? ''));
$codigoBarras = trim((string)($_POST['codigo_barras'] ?? ''));
$descricao = trim((string)($_POST['descricao'] ?? ''));
$custo = str_replace(',', '.', (string)($_POST['custo'] ?? '0'));
$preco = str_replace(',', '.', (string)($_POST['preco'] ?? '0'));
$estoqueMinimo = str_replace(',', '.', (string)($_POST['estoque_minimo'] ?? '0'));

if ($skuInterno === '') {
    $redirecionarErro('sku_obrigatorio');
}
if ($codigoFabricante === '') {
    $redirecionarErro('codigo_fabricante_obrigatorio');
}
if ($nomeComercial === '') {
    $redirecionarErro('nome_obrigatorio');
}
if (mb_strlen($skuInterno) > 60 || mb_strlen($codigoFabricante) > 100 || mb_strlen($nomeComercial) > 180 || mb_strlen($codigoBarras) > 50) {
    $redirecionarErro('erro_interno');
}

if (!is_numeric($custo) || (float)$custo < 0) {
    $custo = '0.00';
}
if (!is_numeric($preco) || (float)$preco < 0) {
    $preco = '0.00';
}
if (!is_numeric($estoqueMinimo) || (int)$estoqueMinimo < 0) {
    $estoqueMinimo = '0';
}

try {
    $pdo->beginTransaction();

    if ($categoriaModo === 'nova') {
        if ($novaCategoriaNome === '' || mb_strlen($novaCategoriaNome) > 100) {
            $redirecionarErro('categoria_obrigatoria');
        }
        $stmtCat = $pdo->prepare("SELECT id FROM categorias_peca WHERE nome = :nome LIMIT 1");
        $stmtCat->bindValue(':nome', $novaCategoriaNome);
        $stmtCat->execute();
        $catExistente = $stmtCat->fetch(PDO::FETCH_ASSOC);
        if ($catExistente) {
            $categoriaId = (int)$catExistente['id'];
        } else {
            $insCat = $pdo->prepare("
                INSERT INTO categorias_peca (nome, descricao, ativo, criado_em, atualizado_em)
                VALUES (:nome, NULL, 1, NOW(), NOW())
            ");
            $insCat->bindValue(':nome', $novaCategoriaNome);
            $insCat->execute();
            $categoriaId = (int)$pdo->lastInsertId();
        }
    } else {
        if ($categoriaId <= 0) {
            $redirecionarErro('categoria_obrigatoria');
        }
        $stmtCat = $pdo->prepare("SELECT id FROM categorias_peca WHERE id = :id LIMIT 1");
        $stmtCat->bindValue(':id', $categoriaId, PDO::PARAM_INT);
        $stmtCat->execute();
        if (!$stmtCat->fetch()) {
            $redirecionarErro('categoria_obrigatoria');
        }
    }

    if ($tipoModo === 'novo') {
        if ($novoTipoNome === '' || mb_strlen($novoTipoNome) > 150 || $categoriaId <= 0) {
            $redirecionarErro('tipo_obrigatorio');
        }
        $stmtTipo = $pdo->prepare("
            SELECT id
            FROM tipos_peca
            WHERE categoria_peca_id = :categoria_peca_id
              AND nome = :nome
            LIMIT 1
        ");
        $stmtTipo->bindValue(':categoria_peca_id', $categoriaId, PDO::PARAM_INT);
        $stmtTipo->bindValue(':nome', $novoTipoNome);
        $stmtTipo->execute();
        $tipoExistente = $stmtTipo->fetch(PDO::FETCH_ASSOC);
        if ($tipoExistente) {
            $tipoId = (int)$tipoExistente['id'];
        } else {
            $insTipo = $pdo->prepare("
                INSERT INTO tipos_peca (categoria_peca_id, nome, descricao, ativo, criado_em, atualizado_em)
                VALUES (:categoria_peca_id, :nome, NULL, 1, NOW(), NOW())
            ");
            $insTipo->bindValue(':categoria_peca_id', $categoriaId, PDO::PARAM_INT);
            $insTipo->bindValue(':nome', $novoTipoNome);
            $insTipo->execute();
            $tipoId = (int)$pdo->lastInsertId();
        }
    } else {
        if ($tipoId <= 0) {
            $redirecionarErro('tipo_obrigatorio');
        }
        $stmtTipo = $pdo->prepare("
            SELECT id
            FROM tipos_peca
            WHERE id = :id
              AND categoria_peca_id = :categoria_peca_id
            LIMIT 1
        ");
        $stmtTipo->bindValue(':id', $tipoId, PDO::PARAM_INT);
        $stmtTipo->bindValue(':categoria_peca_id', $categoriaId, PDO::PARAM_INT);
        $stmtTipo->execute();
        if (!$stmtTipo->fetch()) {
            $redirecionarErro('tipo_obrigatorio');
        }
    }

    if ($marcaModo === 'nova') {
        if ($novaMarcaNome === '' || mb_strlen($novaMarcaNome) > 100) {
            $redirecionarErro('marca_obrigatoria');
        }
        $stmtMarca = $pdo->prepare("SELECT id FROM marcas_produto WHERE nome = :nome LIMIT 1");
        $stmtMarca->bindValue(':nome', $novaMarcaNome);
        $stmtMarca->execute();
        $marcaExistente = $stmtMarca->fetch(PDO::FETCH_ASSOC);
        if ($marcaExistente) {
            $marcaId = (int)$marcaExistente['id'];
        } else {
            $insMarca = $pdo->prepare("
                INSERT INTO marcas_produto (nome, ativo, criado_em, atualizado_em)
                VALUES (:nome, 1, NOW(), NOW())
            ");
            $insMarca->bindValue(':nome', $novaMarcaNome);
            $insMarca->execute();
            $marcaId = (int)$pdo->lastInsertId();
        }
    } else {
        if ($marcaId <= 0) {
            $redirecionarErro('marca_obrigatoria');
        }
        $stmtMarca = $pdo->prepare("SELECT id FROM marcas_produto WHERE id = :id LIMIT 1");
        $stmtMarca->bindValue(':id', $marcaId, PDO::PARAM_INT);
        $stmtMarca->execute();
        if (!$stmtMarca->fetch()) {
            $redirecionarErro('marca_obrigatoria');
        }
    }

    $produtoIdAtual = (int)($assistente['produto_id'] ?? 0);

    if ($produtoIdAtual > 0) {
        $stmtSku = $pdo->prepare("SELECT id FROM produtos WHERE sku_interno = :sku AND id <> :id LIMIT 1");
        $stmtSku->bindValue(':sku', $skuInterno);
        $stmtSku->bindValue(':id', $produtoIdAtual, PDO::PARAM_INT);
        $stmtSku->execute();
        if ($stmtSku->fetch()) {
            $redirecionarErro('sku_duplicado');
        }

        $stmtFab = $pdo->prepare("
            SELECT id
            FROM produtos
            WHERE marca_produto_id = :marca_produto_id
              AND codigo_fabricante = :codigo_fabricante
              AND id <> :id
            LIMIT 1
        ");
        $stmtFab->bindValue(':marca_produto_id', $marcaId, PDO::PARAM_INT);
        $stmtFab->bindValue(':codigo_fabricante', $codigoFabricante);
        $stmtFab->bindValue(':id', $produtoIdAtual, PDO::PARAM_INT);
        $stmtFab->execute();
        if ($stmtFab->fetch()) {
            $redirecionarErro('codigo_fabricante_duplicado');
        }

        if ($codigoBarras !== '') {
            $stmtBarras = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = :codigo_barras AND id <> :id LIMIT 1");
            $stmtBarras->bindValue(':codigo_barras', $codigoBarras);
            $stmtBarras->bindValue(':id', $produtoIdAtual, PDO::PARAM_INT);
            $stmtBarras->execute();
            if ($stmtBarras->fetch()) {
                $redirecionarErro('codigo_barras_duplicado');
            }
        }

        $upProduto = $pdo->prepare("
            UPDATE produtos
            SET tipo_peca_id = :tipo_peca_id,
                marca_produto_id = :marca_produto_id,
                sku_interno = :sku_interno,
                codigo_fabricante = :codigo_fabricante,
                codigo_barras = :codigo_barras,
                nome_comercial = :nome_comercial,
                descricao = :descricao,
                custo = :custo,
                preco = :preco,
                estoque_minimo = :estoque_minimo,
                ativo = 1,
                atualizado_em = NOW()
            WHERE id = :id
            LIMIT 1
        ");
        $upProduto->bindValue(':tipo_peca_id', $tipoId, PDO::PARAM_INT);
        $upProduto->bindValue(':marca_produto_id', $marcaId, PDO::PARAM_INT);
        $upProduto->bindValue(':sku_interno', $skuInterno);
        $upProduto->bindValue(':codigo_fabricante', $codigoFabricante);
        $upProduto->bindValue(':codigo_barras', $codigoBarras !== '' ? $codigoBarras : null, $codigoBarras !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $upProduto->bindValue(':nome_comercial', $nomeComercial);
        $upProduto->bindValue(':descricao', $descricao !== '' ? $descricao : null, $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $upProduto->bindValue(':custo', number_format((float)$custo, 2, '.', ''), PDO::PARAM_STR);
        $upProduto->bindValue(':preco', number_format((float)$preco, 2, '.', ''), PDO::PARAM_STR);
        $upProduto->bindValue(':estoque_minimo', (int)$estoqueMinimo, PDO::PARAM_INT);
        $upProduto->bindValue(':id', $produtoIdAtual, PDO::PARAM_INT);
        $upProduto->execute();
    } else {
        $stmtSku = $pdo->prepare("SELECT id FROM produtos WHERE sku_interno = :sku LIMIT 1");
        $stmtSku->bindValue(':sku', $skuInterno);
        $stmtSku->execute();
        if ($stmtSku->fetch()) {
            $redirecionarErro('sku_duplicado');
        }

        $stmtFab = $pdo->prepare("
            SELECT id
            FROM produtos
            WHERE marca_produto_id = :marca_produto_id
              AND codigo_fabricante = :codigo_fabricante
            LIMIT 1
        ");
        $stmtFab->bindValue(':marca_produto_id', $marcaId, PDO::PARAM_INT);
        $stmtFab->bindValue(':codigo_fabricante', $codigoFabricante);
        $stmtFab->execute();
        if ($stmtFab->fetch()) {
            $redirecionarErro('codigo_fabricante_duplicado');
        }

        if ($codigoBarras !== '') {
            $stmtBarras = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = :codigo_barras LIMIT 1");
            $stmtBarras->bindValue(':codigo_barras', $codigoBarras);
            $stmtBarras->execute();
            if ($stmtBarras->fetch()) {
                $redirecionarErro('codigo_barras_duplicado');
            }
        }

        $insProduto = $pdo->prepare("
            INSERT INTO produtos (
                tipo_peca_id,
                marca_produto_id,
                sku_interno,
                codigo_fabricante,
                codigo_barras,
                nome_comercial,
                descricao,
                custo,
                preco,
                estoque_minimo,
                ativo,
                criado_em,
                atualizado_em
            ) VALUES (
                :tipo_peca_id,
                :marca_produto_id,
                :sku_interno,
                :codigo_fabricante,
                :codigo_barras,
                :nome_comercial,
                :descricao,
                :custo,
                :preco,
                :estoque_minimo,
                1,
                NOW(),
                NOW()
            )
        ");
        $insProduto->bindValue(':tipo_peca_id', $tipoId, PDO::PARAM_INT);
        $insProduto->bindValue(':marca_produto_id', $marcaId, PDO::PARAM_INT);
        $insProduto->bindValue(':sku_interno', $skuInterno);
        $insProduto->bindValue(':codigo_fabricante', $codigoFabricante);
        $insProduto->bindValue(':codigo_barras', $codigoBarras !== '' ? $codigoBarras : null, $codigoBarras !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $insProduto->bindValue(':nome_comercial', $nomeComercial);
        $insProduto->bindValue(':descricao', $descricao !== '' ? $descricao : null, $descricao !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $insProduto->bindValue(':custo', number_format((float)$custo, 2, '.', ''), PDO::PARAM_STR);
        $insProduto->bindValue(':preco', number_format((float)$preco, 2, '.', ''), PDO::PARAM_STR);
        $insProduto->bindValue(':estoque_minimo', (int)$estoqueMinimo, PDO::PARAM_INT);
        $insProduto->execute();
        $produtoIdAtual = (int)$pdo->lastInsertId();
    }

    $dadosAuxiliares = [
        'categoria_modo' => $categoriaModo,
        'categoria_peca_id' => $categoriaId,
        'nova_categoria_nome' => $novaCategoriaNome,
        'tipo_modo' => $tipoModo,
        'tipo_peca_id' => $tipoId,
        'novo_tipo_nome' => $novoTipoNome,
        'marca_modo' => $marcaModo,
        'marca_produto_id' => $marcaId,
        'nova_marca_nome' => $novaMarcaNome,
        'sku_interno' => $skuInterno,
        'codigo_fabricante' => $codigoFabricante,
        'nome_comercial' => $nomeComercial,
        'codigo_barras' => $codigoBarras,
        'descricao' => $descricao,
        'custo' => number_format((float)$custo, 2, '.', ''),
        'preco' => number_format((float)$preco, 2, '.', ''),
        'estoque_minimo' => (int)$estoqueMinimo,
    ];

    $upAssistente = $pdo->prepare("
        UPDATE assistente_cadastro_produto
        SET produto_id = :produto_id,
            etapa_atual = 2,
            dados_json = :dados_json,
            status = 'em_andamento',
            atualizado_em = NOW()
        WHERE id = :id
          AND usuario_id = :usuario_id
        LIMIT 1
    ");
    $upAssistente->bindValue(':produto_id', $produtoIdAtual, PDO::PARAM_INT);
    $upAssistente->bindValue(':dados_json', json_encode($dadosAuxiliares, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
    $upAssistente->bindValue(':id', $assistenteId, PDO::PARAM_INT);
    $upAssistente->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $upAssistente->execute();

    $pdo->commit();

    header('Location: assistente_cadastro_produto.php?id=' . $assistenteId . '&sucesso=etapa1_salva');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $redirecionarErro('erro_interno');
}
