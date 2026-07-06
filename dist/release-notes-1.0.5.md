# v1.0.5 - FormCreator dual-CC e correcao de pesquisa nativa

## O que mudou

- Corrige o prefixo visual `nn` na descricao dos chamados criados via FormCreator quando ha Centro de Custos Antigo.
- Corrige a pesquisa nativa de chamados por `Centro de Custos Antigo` e `Centro de Custos Novo` nos filtros do GLPI.
- Corrige o mapeamento do itemtype `CostCenterLegacy` para garantir compatibilidade com Search, Dropdowns e FormCreator.

## Validacoes

- Cenario A validado: descricao sem `nn`, CC Antigo + CC Novo corretos, aba de centro de custo OK.
- Cenario B validado: apenas CC Novo, sem regressao.
- Cenario C validado: descricao sem `nn`, CC Antigo correto, CC Novo vazio OK.
- Filtros nativos por centro de custo retornando chamados corretamente.

## Artefato

- `maintenancecosts-1.0.5.zip`
