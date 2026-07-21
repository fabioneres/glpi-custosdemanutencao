## v1.0.10 - Hotfix de instalacao e refinamentos de centros de custo

### Corrigido
- Corrige a instalacao/upgrade do plugin em ambientes onde a camada `DB` nao disponibiliza `listIndexes()`.
- Ajusta a manutencao do indice unico da tabela `glpi_plugin_maintenancecosts_ticketcostcenters` para suportar um centro `Antigo` e um `Novo` por chamado.
- Corrige o salvamento da aba `Centro de Custos` do chamado para trabalhar com selecoes separadas por base.

### Melhorado
- Atualiza o carregamento versionado de JavaScript e CSS do plugin para reduzir cache stale apos upgrade.
- Refina a troca de base de centro de custo nos dropdowns vinculados do chamado.
- Padroniza o cabecalho das tabelas do plugin para ficar mais proximo do visual nativo do GLPI.

### Observacoes
- release gerada a partir da arvore local homologada e alinhada com a VM
- sem alteracao de schema version, mantendo `PLUGIN_MAINTENANCECOSTS_SCHEMA_VERSION = 1.0.1`
