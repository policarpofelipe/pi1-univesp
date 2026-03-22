1. Fundação do sistema
Esses vêm primeiro porque todo o resto depende deles.
✅ conexao.php
✅ auth.php
✅ componentes.php
✅ menu.php
✅ index.php
✅ login.php
✅ logout.php
painel.php

2. Cadastros-base de peças
Sem isso, produto nenhum pode existir de forma coerente.
✅listar_categorias_peca.php
✅form_categoria_peca.php
✅salvar_categoria_peca.php
✅excluir_categoria_peca.php
✅listar_tipos_peca.php
✅form_tipo_peca.php
✅salvar_tipo_peca.php
✅excluir_tipo_peca.php
✅listar_marcas_produto.php
✅form_marca_produto.php
✅salvar_marca_produto.php
✅excluir_marca_produto.php

3. Produtos
Produto depende de tipo de peça e marca de produto.
✅listar_produtos.php
✅form_produto.php
✅salvar_produto.php
✅excluir_produto.php
✅ver_produto.php

4. Catálogo veicular
Antes de compatibilidade, o veículo precisa existir como estrutura.
listar_marcas_veiculo.php
form_marca_veiculo.php
salvar_marca_veiculo.php
excluir_marca_veiculo.php
listar_modelos_veiculo.php
form_modelo_veiculo.php
salvar_modelo_veiculo.php
excluir_modelo_veiculo.php
listar_veiculos_configuracao.php
form_veiculo_configuracao.php
salvar_veiculo_configuracao.php
excluir_veiculo_configuracao.php

5. Compatibilidade entre peça e veículo
Aqui o sistema começa a adquirir inteligência de autopeças.
listar_aplicacoes_peca.php
form_aplicacao_peca.php
salvar_aplicacao_peca.php
excluir_aplicacao_peca.php
ver_aplicacoes_produto.php

6. Estrutura de estoque
Sem isso, o sistema é catálogo; com isso, vira gestão.
listar_estoques.php
form_estoque.php
salvar_estoque.php
excluir_estoque.php
listar_movimentacoes_estoque.php
form_movimentacao_estoque.php
salvar_movimentacao_estoque.php
movimentar_entrada.php
movimentar_saida.php
ajustar_estoque.php
saldo_estoque.php

7. Consulta principal do sistema
Essa é a funcionalidade mais característica do domínio.
consulta_veiculo.php
buscar_produtos_por_veiculo.php

8. Relatórios essenciais
Entram depois que já existe massa de dados.
relatorio_estoque_baixo.php
relatorio_produtos_por_veiculo.php
relatorio_produtos_sem_aplicacao.php
relatorio_veiculos_sem_produtos.php
relatorio_movimentacoes_periodo.php

9. Usuários e administração
Podem vir depois do núcleo operacional.
cadastro.php
recuperar_senha.php
listar_usuarios.php
form_usuario.php
salvar_usuario.php
excluir_usuario.php
10. Configurações institucionais
Importantes, mas não nucleares para o MVP.
form_config_empresa.php
salvar_config_empresa.php