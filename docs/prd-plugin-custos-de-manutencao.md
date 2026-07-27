# PRD - Plugin Custos de Manutencao

## 1. Identificacao do produto

- Produto: `Custos de Manutencao`
- Tipo: plugin para GLPI 10
- Workspace: `C:\Projetos\glpi\plugins\meusplugins\maintenancecosts`
- Plataforma-alvo: GLPI `10.0.x`
- Compatibilidade minima: PHP `7.4+`
- Contexto institucional: controle de custo operacional de manutencao com rastreabilidade administrativa e apoio a uso em chamados, contratos, relatorios e formularios.

---

## 2. Resumo executivo

O plugin `Custos de Manutencao` foi criado para permitir que a instituicao registre, acompanhe e audite materiais consumidos em chamados de manutencao dentro do GLPI.

O problema central resolvido pelo plugin e a falta de controle estruturado sobre:

- quais materiais foram utilizados;
- qual preco foi aplicado no momento do consumo;
- qual centro de custo absorveu a despesa;
- qual contrato esteve associado ao gasto;
- qual tecnico registrou o lancamento;
- em qual periodo o gasto ocorreu.

O plugin preserva o valor aplicado no momento do lancamento, mesmo que a tabela de precos seja atualizada depois. Isso garante coerencia historica e confiabilidade em relatorios, auditorias e comparativos mensais.

---

## 3. Problema de negocio

Antes do plugin, o controle de materiais de manutencao tendia a ficar disperso entre:

- comentarios em chamados;
- planilhas paralelas;
- controles manuais de centro de custo;
- precos sem historico confiavel;
- ausencia de consolidacao por contrato, campus, categoria ou material.

Consequencias desse cenario:

- baixa rastreabilidade;
- dificuldade de fechamento mensal;
- risco de divergencia entre custo real e custo reportado;
- dependencia de consolidacao manual;
- dificuldade de prestar contas por centro de custo, contrato ou tipo de material;
- dificuldade de padronizar o uso de centros de custo em formularios e chamados.

---

## 4. Objetivos do produto

### 4.1 Objetivo principal

Permitir o controle operacional e administrativo dos custos de manutencao registrados em chamados do GLPI.

### 4.2 Objetivos especificos

- registrar materiais consumidos com valor aplicado congelado no momento do lancamento;
- suportar tabelas distintas para materiais/precos SINAPI e Cotacao/Mercado;
- manter historico de preco por material, competencia e origem;
- vincular consumo a centro de custo, contrato, tecnico e chamado;
- permitir relatorios de custo por diferentes recortes gerenciais;
- integrar centros de custo com FormCreator;
- suportar centros de custo em duas bases distintas: `Novo` e `Antigo`;
- manter compatibilidade com ambientes multi-entidade;
- permitir operacao segura em ambientes GLPI 10 com evolucao incremental.

---

## 5. Publico-alvo

### 5.1 Administradores GLPI

Responsaveis por:

- instalar e ativar o plugin;
- conceder permissoes;
- habilitar o plugin por entidade;
- configurar regras globais;
- acompanhar importacoes;
- manter cadastros mestres.

### 5.2 Equipe de manutencao / tecnicos

Responsaveis por:

- lancar materiais consumidos em chamados;
- selecionar centro de custo correto;
- selecionar tipo de preco e competencia;
- justificar observacoes operacionais do lancamento.

### 5.3 Gestores administrativos / financeiros

Responsaveis por:

- analisar custo por centro de custo;
- acompanhar gastos por contrato;
- avaliar materiais mais consumidos;
- acompanhar gastos por periodo, origem e categoria.

### 5.4 Equipe de processos / formularios

Responsaveis por:

- utilizar `Centro de Custos Antigo` e `Centro de Custos Novo` no FormCreator;
- padronizar a abertura de chamados com classificacao administrativa ja capturada no formulario.

---

## 6. Escopo funcional atual

O escopo abaixo reflete o que ja foi implementado e consolidado ao longo da evolucao do plugin.

### 6.1 Configuracoes do plugin

Permite configurar:

- obrigatoriedade de centro de custo no consumo;
- permissao de edicao manual do valor unitario;
- competencia padrao de preco;
- categorias ITIL permitidas para uso do consumo;
- habilitacao por entidade em aba especifica da entidade.

### 6.2 Materiais SINAPI

Funcionalidades:

- cadastro manual;
- listagem paginada;
- pesquisa;
- ativacao/inativacao;
- edicao de registro;
- uso como base de materiais de referencia para precos SINAPI.

### 6.3 Materiais Cotacao

Funcionalidades:

- cadastro manual de materiais que nao pertencem a base SINAPI;
- listagem paginada;
- pesquisa;
- ativacao/inativacao;
- edicao de registro;
- uso para precos de Cotacao/Mercado.

### 6.4 Origens do material

Funcionalidades:

- cadastro e manutencao de origens operacionais;
- uso da origem `Contrato` para habilitar selecao de contrato no consumo;
- uso em relatorios e rastreabilidade.

### 6.5 Precos SINAPI

Funcionalidades:

- cadastro manual de preco;
- importacao de preco por competencia;
- historico de preco;
- listagem paginada;
- separacao conceitual entre material e preco;
- uso do ultimo preco disponivel e da competencia informada no consumo.

### 6.6 Precos Cotacao / Mercado

Funcionalidades:

- tabela separada da SINAPI;
- cadastro manual de preco de mercado;
- importacao de cotacao;
- historico de preco;
- uso em chamados quando o tipo de preco selecionado nao for SINAPI.

### 6.7 Importacoes

Funcionalidades:

- importacao SINAPI;
- importacao Cotacao;
- historico de importacoes;
- validacao sem gravar;
- persistencia de lote importado;
- separacao por tipo de preco.

### 6.8 Centros de custo Novo

Base institucional atualizada, com colunas estruturadas para organizacao administrativa mais completa.

Funcionalidades:

- cadastro manual;
- edicao e salvamento;
- ativacao/inativacao;
- importacao;
- listagem paginada;
- exposicao no FormCreator;
- pesquisa por nome e codigo;
- suporte a vinculo no chamado.

### 6.9 Centros de custo Antigo

Base legada mantida para compatibilidade operacional e historica.

Funcionalidades:

- cadastro manual;
- edicao e salvamento;
- ativacao/inativacao;
- importacao a partir de planilhas legadas;
- listagem paginada;
- exposicao no FormCreator;
- pesquisa por nome e codigo com ou sem pontuacao;
- suporte a vinculo no chamado.

### 6.10 Aba Centro de Custos no chamado

Funcionalidades:

- vinculo de um centro de custo `Antigo`;
- vinculo de um centro de custo `Novo`;
- salvamento separado por base;
- remocao do vinculo;
- comportamento padrao para refletir esses vinculos em `Materiais consumidos`;
- possibilidade de autovinculo quando nao houver centro previamente associado e o primeiro lancamento material definir o centro.

### 6.11 Aba Materiais consumidos no chamado

Funcionalidades:

- lancamento de material consumido;
- selecao de material;
- selecao de origem do material;
- selecao de tipo de preco;
- selecao de competencia;
- preenchimento automatico de unidade;
- preenchimento automatico de valor unitario quando houver preco correspondente;
- preenchimento automatico da data com a data vigente;
- suporte a centro de custo `Antigo` ou `Novo`, respeitando vinculo do chamado;
- congelamento do valor aplicado no lancamento;
- associacao opcional a contrato quando a origem do material for `Contrato`;
- listagem dos consumos registrados;
- soma de custos do chamado.

### 6.12 Relatorios de custos

Funcionalidades consolidadas:

- custo por chamado;
- custo por centro de custo;
- custo por categoria ITIL;
- custo por campus/localizacao;
- custos por material;
- evolucao mensal de custos;
- recortes por contrato;
- recortes por origem do material;
- exportacao CSV;
- exportacao PDF;
- dashboards e resumos para apoio gerencial;
- limite de itens por ranking;
- visual mais proximo do GLPI nativo;
- opcao de mostrar/ocultar colunas.

### 6.13 Integracao com FormCreator

Funcionalidades:

- objetos GLPI `Centro de Custos Antigo` e `Centro de Custos Novo`;
- uso via listas suspensas e via objetos GLPI;
- pesquisa por codigo e nome;
- retorno no formato `codigo - nome`;
- sincronizacao do centro de custo selecionado com o vinculo do chamado;
- suporte ao comportamento em que somente um, ou ambos, centros podem ser preenchidos.

### 6.14 Permissoes e perfis

Funcionalidades:

- direitos por perfil para leitura, atualizacao, criacao e exclusao;
- direitos especificos para:
  - materiais;
  - precos SINAPI;
  - centros de custo;
  - consumo em chamados;
  - importacao SINAPI;
  - relatorios;
  - configuracao do plugin.

### 6.15 Auditoria funcional

Implementado com foco em rastreabilidade de:

- valor aplicado;
- competencia;
- usuario;
- origem;
- alteracoes de preco;
- historico de importacao;
- vinculos administrativos relevantes.

---

## 7. Regras de negocio principais

### 7.1 Congelamento do valor aplicado

Ao adicionar um material no chamado:

- o valor unitario aplicado deve ser gravado no item consumido;
- alteracoes futuras nas tabelas de preco nao podem mudar retroativamente esse valor.

### 7.2 Separacao entre material e preco

`Material` e `Preco` sao entidades diferentes:

- o material e o cadastro mestre do item;
- o preco e a representacao monetaria do item por competencia e tipo.

### 7.3 Separacao entre SINAPI e Cotacao

O plugin deve manter separacao clara entre:

- materiais/precos SINAPI;
- materiais/precos Cotacao/Mercado.

### 7.4 Centro de custo por chamado

O chamado pode possuir:

- somente centro de custo `Antigo`;
- somente centro de custo `Novo`;
- ambos os centros vinculados.

### 7.5 Reflexo do centro de custo no consumo

Em `Materiais consumidos`:

- se houver centro de custo vinculado no chamado, o tecnico deve consumir com base nesse vinculo;
- se nao houver vinculo previo, o primeiro lancamento pode estabelecer esse vinculo automaticamente.

### 7.6 Contrato condicionado a origem

O campo `Contrato` no lancamento deve aparecer somente quando:

- `Origem do material = Contrato`

### 7.7 Competencia padrao

O campo `Competencia` deve vir preenchido com a ultima competencia disponivel, mas continuar editavel.

### 7.8 Pesquisa por codigo sem pontuacao

Na busca de centro de custo, o usuario deve conseguir encontrar itens:

- pelo nome;
- pelo codigo formatado;
- pelo codigo sem pontuacao.

---

## 8. Estrutura de dados consolidada

Tabelas principais do plugin:

- `glpi_plugin_maintenancecosts_materials`
- `glpi_plugin_maintenancecosts_prices`
- `glpi_plugin_maintenancecosts_pricehistories`
- `glpi_plugin_maintenancecosts_materialorigins`
- `glpi_plugin_maintenancecosts_costcenters`
- `glpi_plugin_maintenancecosts_costcenters_legacy`
- `glpi_plugin_maintenancecosts_ticketmaterials`
- `glpi_plugin_maintenancecosts_ticketcostcenters`
- `glpi_plugin_maintenancecosts_importbatches`
- `glpi_plugin_maintenancecosts_configs`
- `glpi_plugin_maintenancecosts_configentities`
- `glpi_plugin_maintenancecosts_auditlogs`

Relacionamentos principais:

- `Ticket` -> `TicketMaterial`
- `Ticket` -> `TicketCostCenter`
- `Material` -> `Price`
- `Price` -> `PriceHistory`
- `TicketMaterial` -> `Contract`
- `TicketMaterial` -> `MaterialOrigin`
- `TicketMaterial` -> `CostCenter` ou `CostCenterLegacy`

---

## 9. Integracoes

### 9.1 GLPI Core

Integracoes com:

- chamados;
- contratos;
- entidades;
- perfis;
- direitos;
- localizacoes;
- relatorios e busca nativa.

### 9.2 FormCreator

Integracoes:

- uso de centros de custo como objetos GLPI;
- uso em listas suspensas;
- sincronizacao com vinculo do chamado.

### 9.3 Importacao de planilhas

Formatos usados ao longo do produto:

- CSV
- XLSX

### 9.4 Exportacao

Saidas disponiveis:

- CSV
- PDF

---

## 10. Requisitos nao funcionais

### 10.1 Compatibilidade

- GLPI 10.0.x
- PHP 7.4+
- MySQL / MariaDB

### 10.2 Performance

- paginacao em tabelas grandes;
- dropdowns com carregamento remoto;
- busca server-side;
- reducao de carregamento massivo em listas extensas.

### 10.3 Seguranca

- uso de perfis e direitos;
- verificacoes por entidade;
- validacao de acesso em operacoes administrativas e operacionais;
- uso de padroes do GLPI para operacoes de install/upgrade.

### 10.4 Multi-entidade

- suporte a habilitacao por entidade;
- comportamento consistente com heranca opcional;
- respeito ao escopo da entidade ativa.

### 10.5 Manutenibilidade

- evolucao incremental;
- reaproveitamento do core do GLPI;
- correcoes orientadas a compatibilidade;
- instalacao com reparos idempotentes de schema.

---

## 11. Historico resumido de evolucao do produto

### 11.1 Fase inicial

- criacao da base de materiais consumidos em chamados;
- configuracoes iniciais de centro de custo, competencia e permissao.

### 11.2 Estruturacao operacional

- materiais SINAPI;
- precos SINAPI;
- importacoes;
- historico de precos;
- primeiros relatorios.

### 11.3 Expansao administrativa

- centros de custo institucionais;
- suporte a contratos;
- aba de centro de custo do chamado;
- dashboards e filtros mais ricos;
- permissao por entidade.

### 11.4 Compatibilidade institucional

- centros de custo `Antigo` e `Novo`;
- integracao com FormCreator;
- busca por codigo e nome;
- suporte a listas suspensas e objetos GLPI;
- vinculo administrativo duplo no chamado.

### 11.5 Fase de endurecimento

- correcao de empacotamento para Linux;
- correcao de instalacao;
- melhorias de UX em tabelas e dropdowns;
- correcoes de saving/editing em centros de custo;
- ajuste de sincronizacao com FormCreator.

---

## 12. Estado atual de maturidade

O plugin esta em estagio operacional avancado, com uso real em homologacao e preparacao para uso continuado por usuarios finais.

Ja atende:

- cadastro e importacao de bases;
- lancamento operacional de materiais;
- vinculacao administrativa por centro de custo;
- apoio a contratos;
- relatorios de apoio gerencial;
- integracao com formularios.

Ainda depende de governanca operacional para:

- definicao institucional de quais centros devem ser usados por fluxo;
- padronizacao do uso de origem do material;
- calibracao de permissao por perfil e entidade;
- validacao de usabilidade em cenarios reais de equipe.

---

## 13. Limitacoes e pontos de atencao

### 13.1 Limitacoes conhecidas historicas

Ao longo da evolucao, ja houve necessidade de tratar:

- problemas de empacotamento ZIP no Windows para servidores Linux;
- inconsistencias de cache de assets;
- diferencas entre ambientes de VM e producao;
- problemas de sincronizacao de centros de custo no FormCreator;
- exigencia de manutencao cuidadosa em indices e migracoes.

### 13.2 Pontos que exigem validacao continua

- comportamento visual em formularios e tickets;
- sincronizacao entre centro do formulario, centro do chamado e centro do material;
- direitos por perfil;
- ativacao/upgrade em ambiente real;
- consistencia entre centro `Antigo` e `Novo`.

---

## 14. Fora de escopo atual

Itens explicitamente deixados para fora em fases anteriores ou ainda nao consolidados como escopo principal:

- tela de auditoria consultavel completa;
- dashboard grafico avancado como produto separado;
- integracoes dedicadas com Metabase / Dashboard Plus como camada de servico completa;
- estoque;
- compras;
- nota fiscal;
- empenho;
- aprovacao de consumo;
- integracoes financeiras externas mais profundas.

---

## 15. Oportunidades futuras

- ampliar dashboards graficos nativos;
- evoluir a experiencia de relatorios por contrato e origem;
- reforcar trilha de auditoria consultavel;
- ampliar automacao de integracao com FormCreator;
- validar compatibilidade futura com GLPI 11;
- consolidar pacote de testes regressivos por modulo;
- reforcar UX nativa GLPI em todas as listagens e formularios.

---

## 16. Criterios de sucesso do produto

O plugin e considerado bem-sucedido quando:

- a equipe consegue registrar materiais em chamados sem planilhas paralelas;
- o valor aplicado fica historicamente confiavel;
- os centros de custo sao vinculados corretamente;
- contratos sao controlados sem perda de rastreabilidade;
- o fechamento mensal pode ser acompanhado por relatorio;
- o FormCreator consegue capturar centro de custo e refletir isso no chamado;
- administradores conseguem manter as bases sem depender de ajuste manual no banco.

---

## 17. Criterios de aceite funcionais macro

### 17.1 Cadastro e importacao

- materiais podem ser cadastrados, editados, inativados e pesquisados;
- precos podem ser cadastrados/importados com historico;
- centros de custo `Novo` e `Antigo` podem ser cadastrados, editados, inativados e importados.

### 17.2 Operacao em chamado

- e possivel vincular centro de custo ao chamado;
- e possivel registrar material com valor, data, competencia, origem e centro corretos;
- contrato so aparece quando a origem do material exigir;
- o valor do lancamento nao muda apos atualizacao de preco.

### 17.3 FormCreator

- objetos `Centro de Custos Antigo` e `Centro de Custos Novo` aparecem para uso;
- pesquisa funciona por codigo e nome;
- retorno visivel ao usuario usa `codigo - nome`;
- vinculo no chamado reflete o que foi respondido no formulario.

### 17.4 Relatorios

- os custos podem ser consultados por principais recortes gerenciais;
- exportacoes funcionam;
- o layout e legivel e coerente com o padrao do GLPI.

---

## 18. Dependencias operacionais

- GLPI instalado e funcional;
- plugin com permissao adequada por perfil;
- entidades habilitadas quando aplicavel;
- bases importadas ou cadastradas;
- precos vigentes cadastrados;
- FormCreator instalado para os fluxos de formulario.

---

## 19. Recomendacoes de uso institucional

- definir claramente quando usar centro `Antigo`, `Novo` ou ambos;
- padronizar o uso da origem `Contrato`;
- revisar perfis antes da entrada em producao;
- validar importacoes em homologacao antes de lotes massivos;
- manter backup antes de upgrades;
- usar checklists de validacao funcional antes de releases maiores.

---

## 20. Referencias internas

- [README do plugin](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\README.md)
- [Manual de uso](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\manual-de-uso.md)
- [Checklist de validacao 1.0.0](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\checklist-validacao-1.0.0.md)
- [Changelog](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\CHANGELOG.md)

---

## 21. Observacao final

Este PRD foi consolidado a partir do historico real de implementacao, homologacao, correcoes, validacoes e decisoes de produto acumuladas durante a evolucao do plugin ate julho de 2026.

Ele deve ser tratado como documento vivo, sendo atualizado a cada nova fase relevante de escopo, release ou mudanca de regra de negocio.
