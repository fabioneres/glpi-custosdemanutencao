# Changelog

## v1.0.1 - Ajustes pos-consolidacao da 1.0.0

- Adiciona busca tolerante a pontuacao no dropdown de centro de custos, permitindo localizar codigos sem digitar separadores.
- Move o vinculo de centro de custo do chamado para uma aba propria `Centro de Custos`, separada da aba `Materiais Consumidos`.
- Remove o formulario de centro de custo da aba de materiais consumidos para evitar duplicidade de manutencao no chamado.
- Mantem o deploy homologado na VM com validacao funcional posterior via navegador.

## v1.0.0 - Consolidacao funcional para uso operacional

- Consolida o fluxo operacional do plugin para uso em producao, com foco em materiais consumidos, centros de custo, contratos e relatorios.
- Mantem o valor unitario congelado no momento do lancamento do material no chamado, mesmo com novas importacoes de preco.
- Separa claramente materiais e precos das tabelas SINAPI e Cotacao / Mercado.
- Expande a integracao com contratos, incluindo vinculo de custos a partir do chamado e remocao do vinculo quando necessario.
- Disponibiliza centros de custo Novo e Antigo com importacoes dedicadas, uso no chamado e suporte a filtros nativos do GLPI.
- Adiciona vinculacao direta de centro de custo ao chamado, impedindo divergencia entre o centro do chamado e o centro dos materiais consumidos.
- Exponibiliza `Centro de Custos Novo` e `Centro de Custos Antigo` para uso no FormCreator como objetos GLPI.
- Mantem paginacao e carregamento remoto nas tabelas grandes para preservar desempenho.
- Consolida a base local preparada para homologacao final via navegador antes da publicacao externa.

## v0.9.1 - Correcao do pacote para Linux

- Regenera o pacote de distribuicao com estrutura de caminhos compativel com servidores Linux.
- Mantem a pasta `maintenancecosts/` como raiz do artefato, pronta para extracao direta em `plugins/`.
- Preserva integralmente as funcionalidades da `v0.9.0`, sem mudancas funcionais adicionais no plugin.

## v0.9.0 - Centro de custo antigo, selecao no chamado e correcoes finais

- Duplica a area de centros de custo e separa os cadastros em `Centros de custo (Novo)` e `Centros de custo (Antigo)`.
- Adiciona a nova tabela legada de centros de custo com cadastro, listagem e importacao dedicados.
- Implementa o mapeamento de importacao do centro de custo antigo a partir da planilha institucional:
  - `CENTRO DE CUSTO` -> `Codigo`
  - `CAMPUS` -> `Campus`
  - `DEPARTAMENTO /DISC./SETOR` -> `Departamento/Disc./Setor`
  - `Tipo + Logradouro + no` -> `Endereco`
  - `Piso` -> `Piso`
  - `UTILIZACAO` -> `Utilizacao`
- Torna a importacao legada tolerante a linhas sem codigo, ignorando registros vazios em vez de abortar o lote inteiro.
- Permite escolher, na aba `Materiais consumidos` do chamado, se a pesquisa de centro de custo sera feita na base `Antigo` ou `Novo`, com padrao em `Antigo`.
- Remove o atalho de cadastro manual de material da tela de consumo no chamado.
- Persiste a origem do centro de custo consumido em `costcenter_source`, com compatibilidade para valores antigos como `novo` e `new`.
- Corrige a busca de `Materiais consumidos` para localizar tambem por nome/codigo de centro de custo.
- Corrige os relatorios para respeitarem a base de centro de custo usada no lancamento, evitando associacoes incorretas entre centro antigo e centro novo.
- Corrige o autofill do material no formulario de consumo para disparar corretamente ao selecionar itens via Select2.
- Corrige rotulos com encoding quebrado no cadastro de centros de custo antigos.

## v0.5.10 - Disponibilidade por entidade

- Move a habilitacao do plugin por entidade para uma aba propria dentro de `Administracao > Entidades`, evitando listas extensas na configuracao global.
- Permite definir disponibilidade por entidade com heranca opcional para entidades filhas.
- Mantem a configuracao global do plugin focada apenas em parametros operacionais, sem misturar escopo organizacional.
- Corrige a renderizacao dos controles da nova aba de entidade e o fluxo de salvamento correspondente.

## v0.5.9 - Preenchimento do valor unitario no chamado

- Corrige o carregamento de dados do material no formulario de consumo usando fallback de AJAX quando `fetch` nao estiver disponivel.
- Ao selecionar material, busca preco da competencia informada e, se nao existir, usa o ultimo preco cadastrado para o material e tipo de preco.
- Mantem preenchimento automatico de unidade, competencia, valor unitario aplicado e total na aba Materiais Consumidos.

## v0.5.8 - Correcao de dropdowns em GLPI instalado na raiz

- Corrige a montagem das URLs AJAX dos dropdowns em ambientes onde o GLPI roda na raiz do dominio, sem o prefixo `/glpi`.
- Mantem compatibilidade com instalacoes em subdiretorio, como `/glpi`, calculando o caminho base a partir do script carregado quando `CFG_GLPI.root_doc` nao estiver disponivel.
- Evita falha `Os resultados nao puderam ser carregados` nos dropdowns de material, centro de custo e contratos na aba Materiais Consumidos.

## v0.5.7 - Dropdowns no consumo do chamado

- Ajusta permissoes do endpoint AJAX de dropdowns para permitir selecao de materiais e centros de custo nos fluxos autorizados de consumo, relatorios e cadastros.
- Evita a mensagem `Os resultados nao puderam ser carregados` quando o usuario possui permissao para lancar consumo no chamado, mas nao administra diretamente o cadastro auxiliar.

## v0.5.6 - Paginacao das tabelas grandes

- Adiciona paginacao server-side em Materiais SINAPI, Materiais Cotacao, Precos SINAPI, Precos Cotacao, Centros de custo e Materiais consumidos.
- Substitui limites fixos e renderizacao massiva por contagem total, pagina atual e seletor de 20/50/100/200 linhas.
- Mantem busca, filtros de tipo de preco e ordenacao visual, reduzindo carga em tabelas com milhares de itens.

## v0.5.5 - Centros de custo institucionais e origem manual

- Adequa centros de custo ao layout da planilha institucional, com codigo, unidade gestora, unidade academica, departamento, divisao, secao, codigo SIORG, sigla SIORG, endereco e responsavel.
- Vincula Unidade gestora a localizacao GLPI de nivel 1 quando houver correspondencia de nome.
- Adiciona importacao XLSX/CSV de centros de custo com reconhecimento de cabecalhos acentuados em maiusculas.
- Remove a criacao automatica de origens do material; origens passam a ser somente cadastros manuais.
- Remove origens padrao legadas sem uso durante upgrade idempotente.
- Mantem a coluna `Acoes` sempre visivel, nao ocultavel e nao ordenavel nas tabelas customizaveis do plugin.
- Atualiza exportacao CSV/PDF de centros de custo para seguir a nova estrutura.

## v0.5.4 - Compatibilidade de instalacao no GLPI 10.0.24

- Corrige a instalacao/habilitacao pela interface em ambientes GLPI 10.0.24, usando a classe global `\QueryExpression` em vez do namespace `Glpi\DBAL\QueryExpression`.
- Mantem a logica de direitos de perfil inalterada; a mudanca e restrita a compatibilidade da API DBAL do GLPI 10.0.x.

## v0.5.3 - Cotacao Mercado e custos do chamado

- Separa Cotacao/Mercado em tab propria, com acoes de adicionar preco, importar e consultar historico no mesmo contexto.
- Adequa Cotacao/Mercado ao layout da planilha de cotacoes com quantidade, valor aplicado e tres cotacoes comparativas.
- Adiciona a tab Materiais Cotacao abaixo de Materiais SINAPI, listando materiais com precos de cotacao/mercado.
- Ajusta os fluxos `Adicionar preco cotacao` e `Importar Cotacao` para exibirem labels, campos e orientacoes proprias de cotacao.
- Preenche a competencia do lancamento de material com a ultima competencia cadastrada, mantendo edicao manual.
- Sincroniza materiais consumidos com a aba nativa `Chamado > Custos` por meio de `TicketCost` idempotente.

## v0.5.2 - Icone, campus e correcoes visuais

- Ajusta o cadastro de centros de custo para usar Campus como localizacao GLPI de nivel 1.
- Corrige nomes e acentuacao visivel nas telas de centro de custo e configuracao.
- Adiciona metadados e arquivos de icone/logotipo do plugin para empacotamento.
- Exibe o icone do plugin no card de Plug-ins instalados do GLPI quando o plugin foi instalado localmente.

## v0.5.1 - Performance, importacoes e relatorios

- Otimiza dropdowns grandes de materiais, centros de custo e contratos com carregamento remoto paginado.
- Move o historico de importacoes para a tela de Importar SINAPI e adiciona `Importar Cotacao`.
- Permite ordenar colunas nas visoes pessoal/global das tabelas do plugin por arrastar e soltar.
- Cria o vinculo chamado-contrato ao selecionar contrato no lancamento de material.
- Melhora exportacao PDF de relatorios com resumo, grafico e tabela em layout visual.

## v0.5.0 - Evolucao de centros, precos e relatorios

- Reorganiza centros de custo com codigo, nome, endereco, piso, campus, departamento/disciplina/setor e utilizacao.
- Adiciona importacao CSV/XLSX de centros de custo com pre-validacao.
- Move importacoes SINAPI para a area de Precos SINAPI.
- Adiciona fluxo de precos por cotacao/mercado e filtro por tipo de preco.
- Adiciona unidade e historico por item em Precos SINAPI.
- Reestrutura relatorios para exibir uma visao por vez, com graficos configuraveis.
- Adiciona relatorios por origem do material, tipo de preco e contrato.
