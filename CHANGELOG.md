# Changelog

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
- Mantem a coluna Acoes sempre visivel, nao ocultavel e nao ordenavel nas tabelas customizaveis do plugin.
- Atualiza exportacao CSV/PDF de centros de custo para seguir a nova estrutura.

## v0.5.4 - Compatibilidade de instalacao no GLPI 10.0.24

- Corrige a instalacao/habilitacao pela interface em ambientes GLPI 10.0.24, usando a classe global `\QueryExpression` em vez do namespace `Glpi\DBAL\QueryExpression`.
- Mantem a logica de direitos de perfil inalterada; a mudanca e restrita a compatibilidade da API DBAL do GLPI 10.0.x.

## v0.5.3 - Cotacao Mercado e custos do chamado

- Separa Cotacao/Mercado em tab propria, com acoes de adicionar preco, importar e consultar historico no mesmo contexto.
- Adequa Cotacao/Mercado ao layout da planilha de cotacoes com quantidade, valor aplicado e tres cotacoes comparativas.
- Adiciona a tab Materiais Cotacao abaixo de Materiais SINAPI, listando materiais com precos de cotacao/mercado.
- Ajusta os fluxos Adicionar preco cotacao e Importar Cotacao para exibirem labels, campos e orientacoes proprias de cotacao.
- Preenche a competencia do lancamento de material com a ultima competencia cadastrada, mantendo edicao manual.
- Sincroniza materiais consumidos com a aba nativa Chamado > Custos por meio de TicketCost idempotente.

## v0.5.2 - Icone, campus e correcoes visuais

- Ajusta o cadastro de centros de custo para usar Campus como localizacao GLPI de nivel 1.
- Corrige nomes e acentuacao visivel nas telas de centro de custo e configuracao.
- Adiciona metadados e arquivos de icone/logotipo do plugin para empacotamento.
- Exibe o icone do plugin no card de Plug-ins instalados do GLPI quando o plugin foi instalado localmente.

## v0.5.1 - Performance, importacoes e relatorios

- Otimiza dropdowns grandes de materiais, centros de custo e contratos com carregamento remoto paginado.
- Move o historico de importacoes para a tela de Importar SINAPI e adiciona Importar Cotacao.
- Permite ordenar colunas nas visoes pessoal/global das tabelas do plugin por arrastar e soltar.
- Cria o vinculo chamado-contrato ao selecionar contrato no lancamento de material.
- Melhora exportacao PDF de relatorios com resumo, grafico e tabela em layout visual.

## v0.5.0 - Evolução de centros, preços e relatórios

- Reorganiza centros de custo com código, nome, endereço, piso, campus, departamento/disciplina/setor e utilização.
- Adiciona importação CSV/XLSX de centros de custo com pré-validação.
- Move importações SINAPI para a área de Preços SINAPI.
- Adiciona fluxo de preços por cotação/mercado e filtro por tipo de preço.
- Adiciona unidade e histórico por item em Preços SINAPI.
- Reestrutura relatórios para exibir uma visão por vez, com gráficos configuráveis.
- Adiciona relatórios por origem do material, tipo de preço e contrato.
- Adiciona vínculo explícito de material consumido com contrato e sincronização do custo do contrato.
- Adiciona exportação PDF simples para tabelas e relatórios.
- Mantém navegação por tabs laterais em todas as telas principais do plugin.

## v0.4.1 - Consolidada

- Consolida a entrega aplicada na VM após validação visual e técnica.
- Mantém o menu em Plug-ins, telas revisadas, relatórios pesquisáveis/ordenáveis e correções de dropdowns.
- Confirma congelamento de valores em chamados, histórico de preços e suporte a SINAPI/Cotação.

## v0.4.0 - Base da evolução

- Adiciona histórico formal de alterações de preços, com material, competência, tipo de preço, valor anterior, valor novo, usuário, origem, lote de importação e justificativa.
- Prepara a importação SINAPI para atualizar preço existente por material, competência e tipo de preço, evitando duplicidade de preços da mesma competência.
- Move o menu principal do plugin para a secao Plug-ins do GLPI.
- Amplia o cadastro de centros de custo com campo de endereco e listagem propria com pesquisa.

## v0.3.19 - Release base

- Estabiliza cadastro de materiais SINAPI, centros de custo, origens de material, preços, importações, consumo em chamados, relatórios e página Sobre.
- Corrige dropdowns de material e centro de custo no lancamento de materiais consumidos em chamados.
- Adiciona origem do material e tipo de preco no lancamento de consumo.
- Mantém o valor unitário aplicado no consumo gravado em `unit_price_applied`, preservando os custos já lançados.
- Melhora exibicao de valores em reais e quantidades inteiras nas telas operacionais e relatorios.
- Corrige listagens de materiais, preços e importações para evitar linhas em branco.

