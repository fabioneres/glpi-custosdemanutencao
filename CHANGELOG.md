# Changelog

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

## v0.5.0 - EvoluÃ§Ã£o de centros, preÃ§os e relatÃ³rios

- Reorganiza centros de custo com cÃ³digo, nome, endereÃ§o, piso, campus, departamento/disciplina/setor e utilizaÃ§Ã£o.
- Adiciona importaÃ§Ã£o CSV/XLSX de centros de custo com prÃ©-validaÃ§Ã£o.
- Move importaÃ§Ãµes SINAPI para a Ã¡rea de PreÃ§os SINAPI.
- Adiciona fluxo de preÃ§os por cotaÃ§Ã£o/mercado e filtro por tipo de preÃ§o.
- Adiciona unidade e histÃ³rico por item em PreÃ§os SINAPI.
- Reestrutura relatÃ³rios para exibir uma visÃ£o por vez, com grÃ¡ficos configurÃ¡veis.
- Adiciona relatÃ³rios por origem do material, tipo de preÃ§o e contrato.
- Adiciona vÃ­nculo explÃ­cito de material consumido com contrato e sincronizaÃ§Ã£o do custo do contrato.
- Adiciona exportaÃ§Ã£o PDF simples para tabelas e relatÃ³rios.
- MantÃ©m navegaÃ§Ã£o por tabs laterais em todas as telas principais do plugin.

## v0.4.1 - Consolidada

- Consolida a entrega aplicada na VM apÃ³s validaÃ§Ã£o visual e tÃ©cnica.
- MantÃ©m o menu em Plug-ins, telas revisadas, relatÃ³rios pesquisÃ¡veis/ordenÃ¡veis e correÃ§Ãµes de dropdowns.
- Confirma congelamento de valores em chamados, histÃ³rico de preÃ§os e suporte a SINAPI/CotaÃ§Ã£o.

## v0.4.0 - Base da evoluÃ§Ã£o

- Adiciona histÃ³rico formal de alteraÃ§Ãµes de preÃ§os, com material, competÃªncia, tipo de preÃ§o, valor anterior, valor novo, usuÃ¡rio, origem, lote de importaÃ§Ã£o e justificativa.
- Prepara a importaÃ§Ã£o SINAPI para atualizar preÃ§o existente por material, competÃªncia e tipo de preÃ§o, evitando duplicidade de preÃ§os da mesma competÃªncia.
- Move o menu principal do plugin para a secao Plug-ins do GLPI.
- Amplia o cadastro de centros de custo com campo de endereco e listagem propria com pesquisa.

## v0.3.19 - Release base

- Estabiliza cadastro de materiais SINAPI, centros de custo, origens de material, preÃ§os, importaÃ§Ãµes, consumo em chamados, relatÃ³rios e pÃ¡gina Sobre.
- Corrige dropdowns de material e centro de custo no lancamento de materiais consumidos em chamados.
- Adiciona origem do material e tipo de preco no lancamento de consumo.
- MantÃ©m o valor unitÃ¡rio aplicado no consumo gravado em `unit_price_applied`, preservando os custos jÃ¡ lanÃ§ados.
- Melhora exibicao de valores em reais e quantidades inteiras nas telas operacionais e relatorios.
- Corrige listagens de materiais, preÃ§os e importaÃ§Ãµes para evitar linhas em branco.

