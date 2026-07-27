## v1.0.11 - Hotfix de lancamento de materiais e cotacao

- Corrige o botao `Adicionar material` em `Materiais consumidos` quando o deploy parcial nao publicava o JavaScript `ticketmaterial-v3.js`.
- Adiciona fallback automatico no `setup.php` para carregar `ticketmaterial-v2.js` ou `ticketmaterial.js` caso o asset principal nao exista no servidor.
- Atualiza os scripts `deploy-vm.ps1` e `deploy-prod.ps1` para incluir explicitamente o `ticketmaterial-v3.js`.
- Ajusta a tela `Cotacao/Mercado` para mostrar apenas o preco vigente por material na listagem principal, deixando as competencias anteriores disponiveis apenas em `Historico de precos`.
