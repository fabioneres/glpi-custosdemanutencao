# Custos de Manutencao

## Objetivo

Registrar materiais consumidos em atendimentos de manutencao no GLPI, preservando o valor unitario aplicado no momento do lancamento e permitindo rastreabilidade por centro de custo.

## Instalacao

1. Copiar a pasta `maintenancecosts` para `plugins/`.
2. Acessar `Configuracao > Plugins`.
3. Instalar e ativar `Custos de Manutencao`.
4. Configurar os direitos nos perfis autorizados.

## Atualizacao

A rotina de instalacao tambem executa reparos idempotentes de schema e direitos. Em atualizacoes futuras, validar backup do banco antes de ativar nova versao.

## Estrutura do Banco

- `glpi_plugin_maintenancecosts_materials`
- `glpi_plugin_maintenancecosts_prices`
- `glpi_plugin_maintenancecosts_costcenters`
- `glpi_plugin_maintenancecosts_ticketmaterials`
- `glpi_plugin_maintenancecosts_importbatches`
- `glpi_plugin_maintenancecosts_configs`
- `glpi_plugin_maintenancecosts_auditlogs`

## Perfis e Permissoes

O plugin cria direitos especificos para materiais, precos, centros de custo, consumo em chamados, importacao, relatorios e configuracao.

## Fluxo Operacional

1. Configurar o plugin em `Configurar > Plugins > Custos de Manutencao`.
2. Importar CSV ou XLSX SINAPI em `Gerencia > Custos de Manutencao > Importacoes SINAPI`.
3. Revisar materiais e precos importados.
4. Cadastrar centros de custo.
5. Registrar materiais consumidos na aba do chamado.
6. Consultar totais, historico de precos, exportacoes CSV e relatorios.

## Limitacoes Conhecidas

Esta primeira entrega operacional nao implementa dashboards graficos, aprovacao de consumo, estoque ou integracoes externas. A importacao grava lotes e historico de precos. A pre-visualizacao e confirmacao ocorrem como dois processamentos manuais: marcar `Validar sem gravar` para previa e desmarcar para gravar.

## Roadmap Futuro

- Servicos reutilizaveis para Dashboard Plus e ferramentas externas.
- Validacao futura para GLPI 11.
- Melhorias de usabilidade no modal de lancamento.
- Exportacao PDF dos relatorios.
