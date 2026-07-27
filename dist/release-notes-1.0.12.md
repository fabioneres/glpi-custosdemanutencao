## v1.0.12 - Hotfix de busca dos materiais SINAPI
- Ajusta a busca AJAX do lancamento de Materiais consumidos para consultar primeiro a tabela de materiais e apenas filtrar a existencia de preco do tipo selecionado.
- Reforca a pesquisa por codigo e nome do material, incluindo comparacao normalizada do codigo sem pontuacao.
- Corrige o cenario em que materiais SINAPI ja importados, como 10236, 10416 e 12747, podiam nao aparecer no dropdown de selecao durante o lancamento.
- Corrige o registro de assets JavaScript e CSS no setup.php, restaurando o carregamento do formulario de Materiais consumidos em ambientes onde o GLPI nao aceitava caminhos de hook com query string.
- Ajusta rotulos e mensagens com acentuacao quebrada em telas e validacoes do plugin.
- Corrige aliases e mensagens do importador para aceitar cabecalhos legados com acentuacao esperada.
