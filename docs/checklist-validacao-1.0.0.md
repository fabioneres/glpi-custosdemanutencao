# Checklist de Validacao para Consolidacao da Versao 1.0.0

Este arquivo deve ser usado como roteiro oficial de homologacao do plugin
`Custos de Manutencao` antes da consolidacao da versao `1.0.0`.

## Objetivo

Garantir que:

- o plugin instala e ativa corretamente;
- as permissoes e entidades funcionam;
- os fluxos de centros de custo, materiais, precos, contratos e relatorios
  estao corretos;
- a integracao com FormCreator esta utilizavel;
- nao existem regressões visuais, funcionais ou de dados.

## Ambiente de validacao

- GLPI na VM: `http://192.168.159.129/glpi`
- Plugin principal: `maintenancecosts`
- FormCreator instalado na VM para esta rodada
- Validar pelo navegador, e nao apenas por leitura de codigo

## Como registrar o resultado

Para cada item, registrar:

- `OK`
- `NOK`
- evidencia curta
- URL ou tela usada

Se um item falhar, registrar tambem:

- passos para reproduzir
- resultado observado
- resultado esperado
- impacto em dados, permissao, performance ou usabilidade

---

## 1. Instalacao e ativacao

- [ ] Plugin instala pela interface grafica sem erro
- [ ] Plugin ativa pela interface grafica sem desativar logo em seguida
- [ ] Plugin aparece corretamente no menu `Plug-ins`
- [ ] Nenhuma tela principal do plugin retorna erro `500`, `403` ou `404`
- [ ] FormCreator abre normalmente na VM

## 2. Permissoes e entidades

- [ ] Permissoes do plugin aparecem corretamente nos perfis
- [ ] Permissoes salvas reaparecem marcadas ao reabrir o perfil
- [ ] Configuracoes por entidade funcionam conforme esperado
- [ ] Plugin respeita entidade atual e recursividade
- [ ] Usuario sem permissao nao acessa telas administrativas

## 3. Navegacao geral

- [ ] Todas as tabs abrem sem erro:
  - `Configuracoes`
  - `Materiais SINAPI`
  - `Materiais Cotacao`
  - `Origens do material`
  - `Centros de custo Novo`
  - `Centros de custo Antigo`
  - `Precos SINAPI`
  - `Relatorios de custos`
  - `Materiais consumidos`
  - `Sobre`
- [ ] A troca entre tabs funciona sem precisar voltar em `Configurar`
- [ ] Nao ha problemas visiveis de acentuacao
- [ ] Ortografia geral esta correta

## 4. Configuracoes do plugin

- [ ] Tela `Configuracoes` abre sem erro
- [ ] Campo de categorias ITIL permitidas salva e recarrega corretamente
- [ ] Competencia padrao de preco salva e recarrega corretamente
- [ ] Opcao de centro de custo obrigatorio funciona
- [ ] Opcao de edicao manual do valor unitario funciona

## 5. Materiais SINAPI

- [ ] Tela abre normalmente
- [ ] Busca funciona por codigo e por nome
- [ ] Ordenacao crescente e decrescente funciona
- [ ] Paginacao funciona
- [ ] Visao pessoal e global funcionam
- [ ] Coluna `Acoes` nao pode ser ocultada
- [ ] Coluna `Acoes` nao participa da ordenacao
- [ ] Cadastro manual funciona
- [ ] Edicao funciona
- [ ] Exportacao CSV funciona

## 6. Materiais Cotacao

- [ ] Tela abre normalmente
- [ ] Busca funciona por codigo e por nome
- [ ] Ordenacao crescente e decrescente funciona
- [ ] Paginacao funciona
- [ ] Visao pessoal e global funcionam
- [ ] Coluna `Acoes` nao pode ser ocultada
- [ ] Coluna `Acoes` nao participa da ordenacao
- [ ] Cadastro manual funciona
- [ ] Edicao funciona
- [ ] Exportacao CSV funciona

## 7. Precos SINAPI

- [ ] Tela `Precos SINAPI` abre normalmente
- [ ] Acentuacao da tela esta correta
- [ ] Busca funciona
- [ ] Ordenacao funciona
- [ ] Paginacao funciona
- [ ] Coluna `Unidade` aparece corretamente
- [ ] Historico de precos por item funciona
- [ ] Adicionar preco manual SINAPI funciona
- [ ] Importar SINAPI funciona
- [ ] Historico de importacoes SINAPI aparece na propria area
- [ ] Reimportacao na mesma competencia atualiza preco sem duplicar material
- [ ] Nova competencia cria preco vigente novo e preserva historico

## 8. Precos Cotacao / Mercado

- [ ] Tela abre normalmente
- [ ] Acentuacao da tela esta correta
- [ ] Busca funciona
- [ ] Ordenacao funciona
- [ ] Paginacao funciona
- [ ] Historico de precos por item funciona
- [ ] Adicionar preco de cotacao funciona
- [ ] Importar cotacao funciona
- [ ] Reimportacao preserva historico de precos

## 9. Origens do material

- [ ] Tela abre normalmente
- [ ] Nao existe carga desnecessaria de opcoes por padrao
- [ ] Cadastro manual de origem funciona
- [ ] Edicao de origem funciona
- [ ] Existe ao menos a origem `Contrato` para o teste do fluxo contratual

## 10. Centros de custo Novo

- [ ] Tela abre normalmente
- [ ] Busca funciona
- [ ] Ordenacao funciona
- [ ] Paginacao funciona
- [ ] Visao pessoal e global funcionam
- [ ] Coluna `Acoes` nao pode ser ocultada
- [ ] Coluna `Acoes` nao participa da ordenacao
- [ ] Cadastro manual funciona
- [ ] Edicao manual funciona
- [ ] Importacao funciona
- [ ] Campo de localizacao nivel 1 esta coerente com o uso esperado
- [ ] Labels do formulario estao visualmente corretos

## 11. Centros de custo Antigo

- [ ] Tela abre normalmente
- [ ] Busca funciona
- [ ] Ordenacao funciona
- [ ] Paginacao funciona
- [ ] Visao pessoal e global funcionam
- [ ] Coluna `Acoes` nao pode ser ocultada
- [ ] Coluna `Acoes` nao participa da ordenacao
- [ ] Cadastro manual funciona
- [ ] Edicao manual funciona
- [ ] Importacao pela planilha homologada funciona
- [ ] O mapeamento da importacao segue:
  - `CENTRO DE CUSTO -> Codigo`
  - `CAMPUS -> Campus`
  - `DEPARTAMENTO/DISC./SETOR -> Departamento/Disc./Setor`
  - `Tipo + Logradouro + no -> Endereco`
  - `Piso -> Piso`
  - `UTILIZACAO -> Utilizacao`
- [ ] Linhas sem codigo sao ignoradas sem quebrar a importacao

## 12. Materiais consumidos no chamado

- [ ] A aba `Materiais consumidos` aparece no chamado
- [ ] O total do chamado aparece em `R$`
- [ ] O formulario abre dentro do chamado, sem redirecionar para outra area
- [ ] O campo `Competencia` vem preenchido com a ultima competencia cadastrada
- [ ] A competencia salva no formato `AAAA-MM`
- [ ] Quantidade aparece como numero inteiro quando esse for o caso
- [ ] Valor unitario e carregado automaticamente ao selecionar material
- [ ] Unidade e carregada automaticamente ao selecionar material
- [ ] Total e recalculado corretamente
- [ ] Dropdown de material carrega com desempenho aceitavel
- [ ] Dropdown de centro de custo carrega com desempenho aceitavel
- [ ] O seletor de tabela de centro de custo alterna corretamente entre
  `Antigo` e `Novo`
- [ ] A busca de centro de custo usa de fato a tabela selecionada
- [ ] O dropdown `Antigo/Novo` esta visualmente alinhado aos demais campos

## 13. Materiais consumidos - origem e contrato

- [ ] Campo `Origem do material` funciona
- [ ] Campo `Contrato` fica oculto quando a origem nao e `Contrato`
- [ ] Campo `Contrato` aparece somente quando a origem e `Contrato`
- [ ] Ao trocar a origem para algo diferente de `Contrato`, o contrato
  selecionado e limpo
- [ ] Ao adicionar item com origem `Contrato`, o vinculo e salvo corretamente
- [ ] Ao adicionar item sem origem `Contrato`, nao ocorre vinculacao indevida

## 14. Materiais consumidos - SINAPI e Cotacao no chamado

- [ ] Usuario consegue lancar item usando tabela SINAPI
- [ ] Usuario consegue lancar item usando tabela Cotacao / Mercado
- [ ] O dropdown de material respeita o tipo de preco selecionado
- [ ] Itens SINAPI e Cotacao podem coexistir no mesmo chamado
- [ ] A tabela do chamado exibe corretamente a origem e o tipo de preco do item

## 15. Centro de custo no chamado

- [ ] E possivel definir centro de custo direto no chamado
- [ ] Existem filtros nativos de ticket para:
  - `Centro de Custos Antigo`
  - `Centro de Custos Novo`
- [ ] Materiais do chamado nao podem usar centro de custo diferente do centro
  definido no chamado
- [ ] A busca nativa de chamados do GLPI funciona com esses dois campos

## 16. Custos e contratos

- [ ] Quando houver contrato selecionado, o custo aparece em
  `Gerencia -> Contratos -> Custos`
- [ ] Se o contrato for removido do item consumido, o custo deixa de ficar
  vinculado
- [ ] O botao ou acao de remocao aparece somente quando ha vinculo
- [ ] Ao remover contrato, o item consumido permanece no chamado
- [ ] Ao remover contrato, o custo vinculado ao contrato e efetivamente
  removido ou desvinculado

## 17. Relatorios de custos

- [ ] Tela abre sem travar
- [ ] Nenhum dropdown mostra `Os resultados nao puderam ser carregados`
- [ ] Dropdown de localizacao lista apenas nivel 1
- [ ] Dropdown de centro de custo carrega com desempenho aceitavel
- [ ] Dropdown de material carrega com desempenho aceitavel
- [ ] Filtros funcionam por:
  - periodo
  - entidade
  - categoria ITIL
  - centro de custo
  - localizacao
  - tecnico
  - grupo
  - material
- [ ] Existe opcao de escolher qual relatorio ou tabela exibir
- [ ] Dashboard e graficos carregam sem quebrar a tela
- [ ] Valores monetarios aparecem em `R$`
- [ ] Tabelas estao legiveis
- [ ] Ordenacao das colunas funciona
- [ ] Exportacao CSV funciona
- [ ] Exportacao PDF funciona e sai configurada corretamente

## 18. Relatorios especificos para fechar 1.0.0

- [ ] Relatorio `Custos por chamado` mostra materiais consumidos no periodo e
  valor gasto
- [ ] Relatorio `Custos por material` mostra os materiais com maior consumo no
  mes
- [ ] Relatorio por centro de custo funciona para o centro de custo Novo
- [ ] Relatorio por centro de custo funciona para o centro de custo Antigo
- [ ] Relatorio nao mistura centro de custo Antigo com Novo
- [ ] O cabecalho usa apenas `Codigo` e `Material`
- [ ] Relatorio por origem do material funciona
- [ ] Relatorio por contrato funciona
- [ ] Relatorio distingue gasto por `Tabela SINAPI` e `Cotacao / Mercado`

## 19. Historico de precos

- [ ] Cada importacao registra historico
- [ ] Cada alteracao manual registra historico
- [ ] Tela de historico abre sem erro de dropdown
- [ ] Historico preserva valor antigo, valor novo, competencia, tipo de preco e
  usuario
- [ ] Alterar preco hoje nao muda item ja lancado no chamado

## 20. FormCreator

- [ ] No FormCreator, ao criar pergunta do tipo dropdown GLPI, aparecem:
  - `Centro de Custos Antigo`
  - `Centro de Custos Novo`
- [ ] Selecionar `Centro de Custos Antigo` funciona
- [ ] Selecionar `Centro de Custos Novo` funciona
- [ ] O formulario publicado carrega os valores corretamente para o usuario final
- [ ] O uso dessas listas nao gera erro de permissao nem de carregamento

## 21. Sobre e documentacao

- [ ] Tela `Sobre` abre normalmente
- [ ] O texto `Sem Custos de Manutencao` aparece corretamente
- [ ] O conteudo explica o uso do plugin de forma suficiente para usuario final
- [ ] A versao exibida corresponde a versao real validada

## 22. UI e acabamento final

- [ ] Nao existem textos com encoding quebrado
- [ ] Nao existem labels com ortografia incorreta
- [ ] Nenhum dropdown importante mostra `Os resultados nao puderam ser carregados`
- [ ] Nenhuma tabela principal aparece em branco com linhas nao clicaveis
- [ ] Nenhuma acao principal gera `Voce nao tem permissao para executar essa acao`
  quando perfil e entidade estao corretamente configurados
- [ ] Menu, icone e identidade visual aparecem corretamente

## 23. Regressao minima obrigatoria

- [ ] Plugin instala em ambiente limpo
- [ ] Plugin ativa em ambiente limpo
- [ ] Plugin atualiza de versao anterior sem quebrar dados existentes
- [ ] Chamados com materiais antigos continuam abrindo
- [ ] Contratos com custos antigos continuam abrindo
- [ ] Relatorios continuam abrindo com a base historica existente
- [ ] Nenhuma importacao anterior deixa de ser exibida

---

## Criterio para consolidar a versao 1.0.0

A versao `1.0.0` so deve ser consolidada quando:

- todos os itens criticos de dados, contratos, relatorios e chamados estiverem
  `OK`;
- a integracao com FormCreator estiver validada no navegador;
- nao houver erros indevidos de permissao;
- nao houver erros visiveis de encoding ou acentuacao;
- nao houver travamentos graves nas telas de relatorio e lancamento no chamado.

## Arquivo de apoio para resultado da homologacao

Se quiser registrar o retorno da execucao deste checklist, usar ou atualizar:

- [maintenancecosts-1.0.0-validacao-resultado.md](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\session\maintenancecosts-1.0.0-validacao-resultado.md)
