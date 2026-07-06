# Versao 1.0.7

Hotfix de empacotamento e publicacao segura.

Inclui:
- regeneracao da release a partir da arvore local funcional validada em homologacao
- correcao dos arquivos publicados que estavam truncados no pacote da 1.0.6
- manutencao dos ajustes recentes de FormCreator, dropdowns de centros de custo e pesquisa nativa
- novo pacote ZIP pronto para instalacao/atualizacao manual do plugin

Instalacao recomendada:
1. Desabilitar o plugin no GLPI
2. Substituir a pasta `maintenancecosts` pelos arquivos desta release
3. Reabilitar o plugin no GLPI
4. Limpar cache/opcache do PHP se necessario
