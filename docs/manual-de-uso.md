# Manual de Uso - Plugin Custos de Manutencao

## Objetivo

Este manual explica como utilizar o plugin `Custos de Manutencao` no GLPI, funcao por funcao, com foco no uso operacional e administrativo.

O plugin foi criado para controlar materiais consumidos em chamados de manutencao, preservando o valor aplicado no momento do lancamento e permitindo rastreabilidade por:

- material;
- tipo de preco;
- competencia;
- centro de custo;
- contrato;
- tecnico;
- periodo.

---

## Publico-alvo

Este manual atende principalmente:

- administradores do GLPI;
- gestores de manutencao;
- equipes responsaveis por importacao de bases;
- tecnicos que registram materiais nos chamados;
- usuarios que consultam relatorios.

---

## Visao geral do plugin

O plugin esta organizado em abas administrativas e operacionais.

### Abas administrativas

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

### Uso operacional no chamado

Dentro do chamado existe a aba:

- `Materiais consumidos`

E nela o tecnico registra o consumo efetivo do atendimento.

---

## Antes de usar

Antes do uso operacional, recomenda-se validar esta sequencia:

1. Instalar e ativar o plugin.
2. Ajustar permissoes por perfil.
3. Habilitar o plugin nas entidades desejadas.
4. Revisar configuracoes gerais.
5. Cadastrar ou importar materiais.
6. Cadastrar ou importar precos.
7. Cadastrar ou importar centros de custo.
8. Testar um lancamento em chamado.
9. Validar relatorios.

---

## 1. Configuracoes

Local:

`Plug-ins > Custos de Manutencao > Configuracoes`

Esta aba concentra o comportamento geral do plugin.

### O que normalmente configurar

#### Centro de custo obrigatorio

Quando habilitado, o tecnico so consegue registrar material se selecionar um centro de custo.

Use quando a instituicao exige classificacao administrativa obrigatoria.

#### Edicao manual do valor unitario

Quando habilitado, permite ajustar manualmente o valor unitario aplicado no lancamento.

Use com cautela. O ideal e manter rastreabilidade e so permitir excecoes quando houver processo interno para isso.

#### Competencia padrao de preco

Define a competencia sugerida ao selecionar um material.

Na pratica, normalmente o plugin usa a ultima competencia cadastrada, mas o campo ainda pode ser alterado manualmente no lancamento quando necessario.

#### Categorias ITIL permitidas

Permite restringir o uso da aba de materiais consumidos a determinadas categorias de chamados.

Use quando o controle de custo so deve existir em tipos especificos de atendimento.

### Boa pratica

Depois de salvar as configuracoes:

1. abra um chamado de teste;
2. tente registrar um material;
3. confirme se o comportamento esta conforme a regra definida.

---

## 2. Materiais SINAPI

Local:

`Plug-ins > Custos de Manutencao > Materiais SINAPI`

Esta tela mantem o cadastro base dos materiais ligados ao universo SINAPI.

### Para que serve

A tabela de materiais armazena a identidade do item:

- codigo;
- nome;
- unidade;
- categoria;
- status.

Ela nao representa o preco em si. O preco fica na tabela de `Precos SINAPI`.

### Operacoes disponiveis

#### Pesquisar

Use o campo de busca para localizar materiais por:

- codigo;
- nome;
- unidade;
- categoria.

#### Adicionar

Permite criar um material manualmente quando ele ainda nao existe na base.

#### Editar

Permite ajustar descricao, unidade, categoria e status do material.

#### Exportar CSV

Exporta a visao atual da listagem.

### Quando usar cadastro manual

Use apenas quando:

- o item nao existir na base importada;
- o item precisar entrar antes da proxima importacao oficial;
- a equipe tiver certeza da identificacao correta do material.

---

## 3. Materiais Cotacao

Local:

`Plug-ins > Custos de Manutencao > Materiais Cotacao`

Esta tela mantem os materiais usados na base de cotacao/mercado.

### Diferenca para Materiais SINAPI

#### Materiais SINAPI

Itens cuja referencia principal vem da base SINAPI.

#### Materiais Cotacao

Itens usados em cotacao/mercado, inclusive quando nao existem na tabela SINAPI.

### Operacoes disponiveis

- pesquisar;
- adicionar;
- editar;
- exportar CSV.

### Quando usar

Use esta aba quando o item:

- nao pertence a base SINAPI;
- depende de orcamento de mercado;
- precisa de controle separado da base oficial.

---

## 4. Origens do material

Local:

`Plug-ins > Custos de Manutencao > Origens do material`

Esta tabela registra a origem administrativa do material.

### Exemplos de uso

- almoxarifado interno;
- compra emergencial;
- doacao;
- contrato de manutencao;
- reposicao institucional.

### Observacao importante

Nao e obrigatorio manter opcoes pre-cadastradas se o processo da area ainda nao estiver definido.

Se a operacao nao precisa desta classificacao no momento, a tabela pode permanecer vazia ate que a equipe defina um padrao.

### Operacoes disponiveis

- adicionar;
- editar;
- desativar;
- pesquisar;
- exportar.

---

## 5. Centros de custo Novo

Local:

`Plug-ins > Custos de Manutencao > Centros de custo Novo`

Esta aba mantem a estrutura mais atual de centros de custo usada pelo plugin.

### Campos normalmente usados

- codigo;
- nome;
- endereco;
- piso;
- campus;
- departamento / disciplina / setor;
- utilizacao;
- localizacao;
- responsavel;
- ativo.

### Para que serve

Permite classificar o custo consumido no chamado segundo a estrutura administrativa mais atual.

### Operacoes disponiveis

- adicionar;
- editar;
- pesquisar;
- ordenar;
- paginar;
- exportar.

### Boa pratica

Padronize:

- nome;
- codigo;
- endereco;
- campus;
- utilizacao.

Isso melhora muito a qualidade dos relatorios.

---

## 6. Centros de custo Antigo

Local:

`Plug-ins > Custos de Manutencao > Centros de custo Antigo`

Esta aba foi criada para manter compatibilidade com a base legada institucional.

### Quando usar

Use esta tabela quando o consumo ainda precisa ser classificado segundo a estrutura antiga.

### Campos principais

- codigo;
- campus;
- departamento / disciplina / setor;
- endereco fundido;
- piso;
- utilizacao.

### Importacao da base antiga

A importacao do centro de custo antigo aceita planilhas que contenham os dados da estrutura legada e faz o mapeamento dos campos conforme a configuracao implementada no plugin.

### Importante

No chamado, o usuario pode escolher se a pesquisa de centro de custo sera feita na base:

- `Antigo`
- `Novo`

O padrao operacional atual costuma ser `Antigo`, salvo regra diferente da entidade.

---

## 7. Precos SINAPI

Local:

`Plug-ins > Custos de Manutencao > Precos SINAPI`

Esta aba controla os valores por competencia dos materiais SINAPI.

### Diferenca entre Material e Preco

#### Material SINAPI

Cadastro do item.

#### Preco SINAPI

Valor do item em determinada competencia.

Em outras palavras:

- o material diz **o que e**;
- o preco diz **quanto custava naquela competencia**.

### Operacoes disponiveis

#### Adicionar preco SINAPI

Cria manualmente um registro de preco para um material e competencia.

#### Importar SINAPI

Importa precos em lote.

#### Historico de precos

Permite acompanhar alteracoes de valor ao longo do tempo.

#### Exportar CSV

Exporta a tabela.

#### Exportar PDF

Exporta a visao atual em PDF, conforme a implementacao vigente.

### Regra importante

Alterar o preco da tabela nao altera retroativamente os lancamentos ja feitos em chamados.

O valor aplicado no chamado permanece congelado no momento do registro.

---

## 8. Precos Cotacao / Mercado

Local:

`Plug-ins > Custos de Manutencao > Precos SINAPI`  
subaba ou secao de `Cotacao / Mercado`, conforme a interface atual

Esta area controla os valores da base de cotacao/mercado.

### Quando usar

Use quando o item:

- nao usar referencia SINAPI;
- depender de levantamento de mercado;
- precisar de valor manual ou importado por cotacao.

### Operacoes disponiveis

- adicionar preco cotacao;
- importar cotacao;
- consultar historico de precos;
- exportar CSV;
- exportar PDF.

### Diferenca pratica para SINAPI

#### SINAPI

Base oficial de referencia.

#### Cotacao / Mercado

Base complementar, mais flexivel, usada para itens fora da referencia oficial ou para situacoes especificas de compra.

---

## 9. Historico de precos

Local:

Disponivel dentro do fluxo de precos.

### Para que serve

Mostra a evolucao dos precos registrados para cada material ao longo do tempo.

### Utilidade operacional

Ajuda a responder perguntas como:

- qual era o valor anterior;
- quando o preco mudou;
- qual competencia foi usada;
- a mudanca veio de importacao ou cadastro manual.

### Boa pratica

Sempre consulte o historico antes de corrigir um preco que pareca incoerente.

Muitas vezes o valor esta correto para outra competencia.

---

## 10. Importacao SINAPI

Local:

Dentro da area de `Precos SINAPI`

### Como usar

1. Clique em `Importar SINAPI`.
2. Selecione o arquivo CSV/XLSX.
3. Informe a competencia.
4. Execute a importacao.
5. Revise o historico logo abaixo da area de importacao.

### O que a importacao faz

- cria materiais que ainda nao existem, quando aplicavel;
- cria ou atualiza precos da competencia;
- registra historico de importacao;
- preserva os valores ja congelados nos chamados.

### O que validar depois da importacao

1. se a competencia ficou correta;
2. se os materiais esperados apareceram;
3. se os precos foram gravados;
4. se o historico foi alimentado.

---

## 11. Importacao Cotacao

Local:

Dentro da area de precos de `Cotacao / Mercado`

### Como usar

1. Clique em `Importar Cotacao`.
2. Selecione o arquivo de cotacao.
3. Informe ou confirme a competencia.
4. Execute a importacao.
5. Revise os registros importados e o historico.

### Quando preferir importacao em vez de cadastro manual

Use importacao quando houver:

- muitos itens;
- planilha oficial da area;
- necessidade de padronizacao;
- necessidade de reduzir erros de digitacao.

---

## 12. Lancamento de materiais no chamado

Local:

`Chamado > Aba Materiais consumidos`

Esta e a funcao operacional principal do plugin.

### Campos do formulario

#### Material

Selecione o material a ser consumido.

#### Origem do material

Use quando houver necessidade de classificar de onde veio o item.

#### Contrato

Selecione quando o consumo estiver vinculado a um contrato especifico.

#### Tipo de preco

Normalmente:

- `Tabela SINAPI`
- `Cotacao / Mercado`

#### Base de centro de custo

Permite escolher entre:

- `Antigo`
- `Novo`

#### Centro de custo

Depois de escolher a base, pesquise e selecione o centro de custo.

#### Competencia

Vem sugerida pela ultima competencia cadastrada, mas pode ser alterada manualmente.

#### Quantidade

Informe a quantidade consumida.

#### Valor unitario aplicado

E preenchido automaticamente conforme o material, tipo de preco e competencia, salvo ajustes autorizados.

#### Unidade

E preenchida a partir do cadastro do material.

#### Data

Informe a data do consumo.

#### Comentarios

Use para justificativas ou observacoes operacionais.

### O que acontece ao salvar

Ao adicionar o material:

- o valor unitario aplicado fica congelado;
- o total e calculado;
- o consumo fica vinculado ao chamado;
- o registro passa a compor relatorios;
- se houver contrato vinculado, o custo pode refletir no acompanhamento contratual.

### Boa pratica operacional

Antes de salvar, confirme:

1. se o material esta correto;
2. se a competencia esta correta;
3. se a base de centro de custo esta correta;
4. se o contrato foi selecionado quando necessario;
5. se a quantidade faz sentido.

---

## 13. Materiais consumidos

Local:

`Plug-ins > Custos de Manutencao > Materiais consumidos`

Esta tela consolida os lancamentos ja realizados em chamados.

### Para que serve

Permite consultar os consumos ja registrados sem precisar abrir chamado por chamado.

### Uso mais comum

- auditoria;
- conferencias;
- localizacao de lancamentos;
- revisao operacional;
- exportacao.

### O que normalmente aparece

- material;
- quantidade;
- unidade;
- valor unitario;
- total;
- centro de custo;
- origem;
- tipo de preco;
- data;
- tecnico;
- acoes.

---

## 14. Relatorios de custos

Local:

`Plug-ins > Custos de Manutencao > Relatorios de custos`

Esta aba oferece a visao gerencial do plugin.

### Filtros comuns

- periodo;
- centro de custo;
- localizacao;
- material;
- tecnico;
- contrato;
- tipo de preco;
- origem do material.

### Analises normalmente disponiveis

- custo por centro de custo;
- custo por contrato;
- custo por origem do material;
- custo por material;
- custo por periodo;
- custo por tipo de preco.

### Exportacoes

- CSV
- PDF

### Boas praticas de uso

#### Para fechamento mensal

Filtre por periodo e exporte os relatorios principais.

#### Para auditoria

Cruze material, centro de custo, contrato e tecnico.

#### Para tomada de decisao

Observe quais centros de custo ou materiais concentram maior gasto.

### Dica de desempenho

Como alguns filtros consultam bases grandes, prefira:

- preencher periodo primeiro;
- depois selecionar contrato, centro de custo ou material;
- evitar abrir relatorios amplos sem filtros quando a base crescer muito.

---

## 15. Integracao com contratos

Local de uso:

- no formulario de material consumido;
- na visao de contratos do GLPI, quando aplicavel ao fluxo configurado

### Para que serve

Permite relacionar o consumo registrado a um contrato especifico.

### Beneficio

Ajuda a responder:

- quanto foi consumido por contrato;
- quais chamados impactaram determinado contrato;
- qual custo consolidado o contrato vem absorvendo.

---

## 16. Custos nativos do chamado

O plugin tambem complementa o acompanhamento de custo no ecossistema do proprio chamado.

### Resultado esperado

O consumo de material registrado na aba do plugin passa a apoiar a visao financeira do atendimento e seu acompanhamento administrativo.

---

## 17. Permissoes e entidades

O plugin depende de dois niveis de liberacao:

### Perfil

O perfil do usuario precisa ter os direitos do plugin.

### Entidade

A entidade tambem precisa ter o plugin habilitado, quando a configuracao estiver controlada por entidade.

### Se o usuario vir mensagem de falta de permissao

Verifique nesta ordem:

1. o perfil tem direito de acesso?
2. a entidade atual permite o plugin?
3. a aba ou tela esta habilitada para essa entidade?

---

## 18. Aba Sobre

Local:

`Plug-ins > Custos de Manutencao > Sobre`

### Para que serve

Apresenta:

- o objetivo do plugin;
- o problema que ele resolve;
- o fluxo operacional recomendado;
- a explicacao das principais capacidades.

### Quando usar

Use esta aba para:

- onboarding de novos usuarios;
- apresentacao do plugin a gestores;
- referencia rapida de funcionamento.

---

## 19. Fluxo operacional recomendado

Para uso continuo do plugin, recomenda-se esta rotina:

### Administracao

1. Revisar configuracoes.
2. Atualizar materiais e precos.
3. Atualizar centros de custo quando necessario.
4. Validar permissoes e entidades.

### Operacao

1. Abrir o chamado.
2. Ir para `Materiais consumidos`.
3. Selecionar material, tipo de preco e competencia.
4. Escolher a base do centro de custo.
5. Selecionar centro de custo.
6. Informar quantidade.
7. Revisar valor unitario aplicado.
8. Salvar.

### Gestao

1. Acessar relatorios.
2. Filtrar por periodo.
3. Analisar centros de custo, contratos e origem.
4. Exportar evidencias quando necessario.

---

## 20. Perguntas frequentes

### Alterar o preco da tabela muda um chamado antigo?

Nao.

O valor aplicado no chamado fica congelado no momento do lancamento.

### Posso usar cotacao e SINAPI ao mesmo tempo?

Sim.

O plugin suporta os dois modelos, desde que o cadastro e os precos estejam corretos.

### Quando usar centro de custo Antigo ou Novo?

Depende da regra administrativa da instituicao.

Se a area ainda usa a base legada, selecione `Antigo`. Se o processo ja migrou para a base atual, use `Novo`.

### Preciso cadastrar origem do material?

Nao obrigatoriamente.

Essa classificacao pode ser usada apenas quando fizer sentido para o processo.

### O plugin funciona em todas as entidades automaticamente?

Nao necessariamente.

Isso depende da configuracao da entidade e das permissoes do perfil.

---

## 21. Boas praticas

- manter os cadastros padronizados;
- evitar criar materiais duplicados;
- revisar competencia antes de lancar consumo;
- usar contrato sempre que o custo estiver contratualmente vinculado;
- usar comentarios para registrar excecoes;
- validar relatorios apos grandes importacoes;
- evitar ajustes manuais sem justificativa.

---

## 22. Checklist rapido de operacao

### Para o administrador

- plugin instalado e ativo;
- perfis configurados;
- entidades habilitadas;
- bases importadas;
- centros de custo revisados.

### Para o tecnico

- chamado correto;
- material correto;
- base de centro de custo correta;
- centro de custo correto;
- quantidade correta;
- competencia correta;
- contrato correto, quando aplicavel.

### Para o gestor

- relatorios filtrados por periodo;
- exportacao arquivada;
- principais centros de custo acompanhados;
- contratos com maior gasto monitorados.

---

## 23. Limites desta versao

Este manual descreve o comportamento funcional consolidado do plugin na fase operacional atual.

Dependendo da release aplicada no ambiente, alguns detalhes visuais ou nomes de botoes podem variar levemente, mas o fluxo principal permanece:

- cadastrar/importar;
- lancar no chamado;
- rastrear;
- relatar.

