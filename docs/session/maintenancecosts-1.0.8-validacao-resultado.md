# Resultado da validacao - maintenancecosts 1.0.8

Data da execucao: 2026-07-07
Responsavel: Claude
Ambiente validado: VM de homologacao 192.168.159.129/glpi (Super-Admin)
Versao validada: 1.0.8

## Arquivos lidos antes da validacao

- [x] [manual-de-uso.md](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\manual-de-uso.md)
- [x] [HANDOFF.md](C:\Projetos\glpi\docs\session\HANDOFF.md)
- [x] [maintenancecosts-1.0.8-validacao-claude.md](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\session\maintenancecosts-1.0.8-validacao-claude.md)

## Resumo executivo

- Status geral: o hotfix especifico da 1.0.8 (botao `Salvar` na edicao de Centro de Custos Novo e Antigo) foi confirmado funcionando nos dois cadastros. Alem do escopo do hotfix, a rodada encontrou 2 problemas relevantes ja existentes no plugin (nao causados pela 1.0.8): um bug que permite salvar um lancamento de material vazio (Secao 5.3, NOK) e uma limitacao estrutural conhecida que impede vincular os dois centros de custo (Antigo e Novo) simultaneamente ao chamado (Secao 4.3/5.1, NOK documentado no HANDOFF.md como intencional).
- Bloqueantes encontrados: 1 (Secao 5.3 - possivel salvar registro de material vazio/sem centro de custo pela aba Materiais Consumidos).
- Melhorias sugeridas: 4 (ver Secao 7).
- Observacao geral de UX: o fluxo central do plugin (FormCreator -> chamado -> vinculo de centro de custo -> lancamento de materiais -> tabelas auxiliares) funciona de ponta a ponta. Os principais pontos de atrito sao a falta de validacao no lancamento de materiais e a trava de alternancia Antigo/Novo apos o primeiro vinculo, que nao correspondem ao comportamento descrito no checklist.

## Registro dos testes

---

## 1. Sanidade inicial do plugin

### 1.1 Plugin ativo e versao correta

- Status: OK
- URL / tela: `Configurar > Plugins` e tela `Sobre` do plugin Custos de Manutencao
- Passos executados: Verificado que o plugin esta listado como ativo em Plugins; aberta a tela "Sobre"; percorridas as 10 abas/telas principais do plugin (Materiais SINAPI, Materiais Cotacao, Precos, Centros de Custo Novo/Antigo, Relatorios, Configuracoes, etc.).
- Resultado observado: Plugin ativo, versao exibida `1.0.8`, todas as telas abriram sem tela branca e sem erro HTTP (500/403/404). Texto e acentuacao legiveis em todas as telas percorridas.
- Resultado esperado: Plugin ativo na versao 1.0.8, telas abrindo sem erro.
- Observacao de melhoria: Nenhuma adicional; menu do plugin e submenus estao claros e agrupados de forma logica.
- Impacto: N/A

---

## 2. FormCreator - Objeto GLPI

### 2.1 Centro de Custos Antigo (Objeto GLPI)

- Status: OK
- URL / tela: FormCreator > formulario de teste com pergunta do tipo `Objeto GLPI` vinculada a `PluginMaintenancecostsCostCenterLegacy`
- Passos executados: Aberta a pergunta configurada; testada busca por nome do campus, por codigo com pontuacao (ex.: `001.005.000`) e por codigo sem pontuacao (ex.: `001005000`); selecionado um item e submetido o formulario.
- Resultado observado: Lista exibida no formato `codigo - nome`; as tres formas de busca (nome, codigo pontuado, codigo sem pontuacao) retornaram o item esperado; selecao gravada corretamente na resposta do formulario.
- Resultado esperado: Busca funcionando nos tres formatos e gravacao correta da selecao.
- Observacao de melhoria: O estilo do campo (chip do Select2) fica visualmente diferente das perguntas do tipo `Lista Suspensa` no mesmo formulario (ver Secao 7).
- Impacto: N/A

### 2.2 Centro de Custos Novo (Objeto GLPI)

- Status: OK
- URL / tela: FormCreator > formulario de teste com pergunta do tipo `Objeto GLPI` vinculada a `PluginMaintenancecostsCostCenter`
- Passos executados: Mesma sequencia de testes da Secao 2.1, aplicada ao Centro de Custos Novo.
- Resultado observado: Comportamento identico ao 2.1 - busca por nome, codigo pontuado e codigo sem pontuacao funcionando; selecao gravada corretamente.
- Resultado esperado: Busca funcionando nos tres formatos e gravacao correta da selecao.
- Observacao de melhoria: Mesma observacao de estilo visual da Secao 2.1.
- Impacto: N/A

---

## 3. FormCreator - Lista Suspensa

### 3.1 Centro de Custos Antigo (Lista Suspensa)

- Status: OK
- URL / tela: FormCreator > formulario de teste com pergunta do tipo `Lista Suspensa` vinculada ao dropdown de Centro de Custos Antigo
- Passos executados: Testada busca por nome, codigo com pontuacao e codigo sem pontuacao; selecionado um item.
- Resultado observado: Lista no formato `codigo - nome`; as tres buscas retornaram o item esperado corretamente.
- Resultado esperado: Busca funcionando nos tres formatos.
- Observacao de melhoria: O estilo padrao do `<select>` nativo (sem chip) contrasta com o Select2 usado em `Objeto GLPI`; para o usuario final pode parecer que sao dois comportamentos diferentes de campo (ver Secao 7).
- Impacto: N/A

### 3.2 Centro de Custos Novo (Lista Suspensa)

- Status: OK
- URL / tela: FormCreator > formulario de teste com pergunta do tipo `Lista Suspensa` vinculada ao dropdown de Centro de Custos Novo
- Passos executados: Mesma sequencia de testes da Secao 3.1, aplicada ao Centro de Custos Novo.
- Resultado observado: Comportamento identico ao 3.1, com busca funcionando nos tres formatos.
- Resultado esperado: Busca funcionando nos tres formatos.
- Observacao de melhoria: Mesma observacao de estilo visual da Secao 3.1.
- Impacto: N/A

---

## 4. Vinculo do centro de custo ao chamado

### 4.1 Somente centro antigo

- Status: OK
- URL / tela: Submissao de formulario FormCreator (Cenario A) e chamado gerado
- Passos executados: Preenchido apenas o Centro de Custos Antigo no formulario; submetido; aberto o chamado gerado e verificada a descricao e a aba `Centro de Custos`.
- Resultado observado: Centro Antigo aparece corretamente na descricao do chamado e fica vinculado na aba `Centro de Custos`.
- Resultado esperado: Centro Antigo refletido na descricao e vinculado na aba.
- Observacao de melhoria: Nenhuma.
- Impacto: N/A

### 4.2 Somente centro novo

- Status: OK
- URL / tela: Submissao de formulario FormCreator (Cenario B) e chamado gerado
- Passos executados: Preenchido apenas o Centro de Custos Novo no formulario; submetido; aberto o chamado gerado e verificada a descricao e a aba `Centro de Custos`.
- Resultado observado: Centro Novo aparece corretamente na descricao do chamado e fica vinculado na aba `Centro de Custos`.
- Resultado esperado: Centro Novo refletido na descricao e vinculado na aba.
- Observacao de melhoria: Nenhuma.
- Impacto: N/A

### 4.3 Centro antigo + centro novo

- Status: NOK
- URL / tela: Submissao de formulario FormCreator (Cenario C) e chamado gerado (ticket #94)
- Passos executados: Preenchidos ambos os centros (Antigo e Novo) no mesmo formulario; submetido; aberto o ticket #94 e verificada a descricao e a aba `Centro de Custos`.
- Resultado observado: Os dois centros aparecem corretamente na descricao do chamado, mas apenas o centro Antigo fica estruturalmente vinculado na aba `Centro de Custos` automaticamente. A tabela `ticketcostcenters` possui `UNIQUE KEY uniq_ticket (tickets_id)`, permitindo apenas 1 vinculo estrutural por chamado; a logica atual prioriza o Antigo. E possivel vincular manualmente o segundo centro depois (ver Secao 5), mas isso nao acontece automaticamente a partir do FormCreator.
- Resultado esperado (segundo o checklist): "Ambos ficam vinculados na aba Centro de Custos" e "a aba mostra um campo para Antigo e outro para Novo" automaticamente a partir da submissao do formulario.
- Observacao de melhoria: Este comportamento e uma limitacao arquitetural conhecida e documentada no HANDOFF.md ("intencional: vinculamos so o CC antigo... se no futuro quiser vincular os dois estruturalmente, sera necessaria migration de DB"), portanto nao e uma regressao da 1.0.8. Fica registrado aqui porque o checklist desta rodada espera explicitamente a vinculacao automatica dos dois.
- Impacto: Usuario que preenche os dois centros no formulario precisa entrar manualmente no chamado e vincular o segundo centro pela aba `Centro de Custos`; sem essa acao manual, o segundo centro fica "orfao" (citado na descricao, mas nao vinculado). Nao bloqueia o uso do plugin, mas pode gerar relatorios incompletos se o vinculo manual nao for feito. Corrigir exigiria migration de banco (remover/alterar a UNIQUE KEY), fora do escopo de um hotfix.

---

## 5. Tab Centro de Custos x Materiais consumidos

### 5.1 Reflexo das alteracoes no chamado

- Status: NOK
- URL / tela: Ticket #94 > aba `Centro de Custos` e aba `Materiais Consumidos`
- Passos executados: Vinculados manualmente os dois centros (Antigo e Novo) na aba `Centro de Custos` do ticket #94; em seguida aberta a aba `Materiais Consumidos` para verificar se e possivel alternar a base de busca entre Antigo/Novo.
- Resultado observado: A vinculacao manual dos dois centros funciona e persiste (e tambem possivel remover os vinculos). Porem, uma vez que um centro de custo esta vinculado ao chamado, o campo "Tabela de centro de custo" na aba `Materiais Consumidos` fica travado em um rotulo estatico e nao oferece alternancia entre Antigo/Novo mesmo quando ambos estao vinculados ao chamado.
- Resultado esperado: "Quando o chamado possui antigo e novo vinculados, a aba Materiais consumidos alterna corretamente entre as duas bases."
- Observacao de melhoria: A trava em si e coerente com o objetivo de evitar divergencia entre o centro do chamado e o centro dos materiais consumidos (conforme CHANGELOG v1.0.0), mas contradiz a expectativa deste checklist de alternancia livre quando ha dois vinculos. Recomenda-se alinhar a documentacao/checklist com o comportamento real, ou implementar a alternancia quando ambos os centros estiverem vinculados.
- Impacto: Baixo/medio. Nao impede o lancamento de materiais, mas o tecnico nao consegue escolher qual dos dois centros vinculados usar no lancamento quando ambos existem no chamado.

### 5.2 Vinculo automatico via lancamento de material

- Status: OK
- URL / tela: Ticket #94 > aba `Materiais Consumidos` (chamado sem centro de custo previamente vinculado)
- Passos executados: Em um chamado sem centro de custo vinculado, escolhido livremente um centro ao adicionar um material; lancado o material com quantidade e centro selecionados; salvo.
- Resultado observado: Ao salvar o material, o centro escolhido foi automaticamente vinculado ao chamado; a aba `Centro de Custos` passou a exibir o centro vinculado; a aba `Materiais Consumidos` deixou de permitir escolher um centro divergente nos lancamentos seguintes. Autofill de unidade/valor unitario e recalculo do total por quantidade tambem funcionaram corretamente.
- Resultado esperado: Vinculo automatico do centro ao salvar o primeiro material, refletido na aba `Centro de Custos`.
- Observacao de melhoria: O usuario nao recebe uma mensagem explicita informando que o centro foi vinculado ao chamado a partir desse lancamento; a mudanca so fica visivel ao abrir a aba `Centro de Custos` manualmente (ver Secao 7).
- Impacto: N/A (funcionalidade correta, apenas falta de feedback visual).

### 5.3 Bloqueio de salvar centro sem material

- Status: NOK (bloqueante)
- URL / tela: Ticket #94 > aba `Materiais Consumidos` > `Adicionar`
- Passos executados: Clicado em "Adicionar" na aba Materiais Consumidos sem selecionar nenhum material, com quantidade "0" e sem escolher centro de custo; confirmado o salvamento.
- Resultado observado: O sistema permitiu salvar o lancamento mesmo assim, criando um registro com nome do material vazio, quantidade "0", valor unitario "R$ 0,00" e centro de custo em branco. O registro bogus foi removido manualmente apos o teste (acao "Cancelar" na linha) para nao deixar lixo de teste na base.
- Resultado esperado: "Nao e possivel salvar mudanca de centro de custo pela tela Materiais consumidos sem lancar um material" - ou seja, o sistema deveria bloquear o salvamento.
- Observacao de melhoria: A situacao real e mais grave que o cenario descrito no checklist: nao e apenas possivel salvar uma mudanca de centro sem material, e possivel salvar um registro essencialmente vazio (sem material, sem quantidade valida e sem centro). Recomenda-se validacao obrigatoria no backend (material selecionado, quantidade > 0) antes de persistir o lancamento.
- Impacto: Usuario pode gerar registros de consumo vazios/invalidos que poluem relatorios de custo e a lista de materiais consumidos do chamado, sem qualquer aviso de erro. Recomenda-se tratar como bloqueante para uma proxima correcao, embora nao seja causado pela mudanca da 1.0.8 (bug preexistente).

---

## 6. Tabelas e cadastros

### 6.1 Edicao de tabela SINAPI

- Status: OK
- URL / tela: `Materiais SINAPI` > edicao do item id=47
- Passos executados: Aberto o item; alterado um campo; clicado em Salvar; reaberto o item para conferir persistencia.
- Resultado observado: Edicao salva com sucesso e alteracao confirmada ao reabrir o item.
- Resultado esperado: Edicao e persistencia funcionando, com botao de salvar disponivel.
- Observacao de melhoria: Nenhuma adicional.
- Impacto: N/A

### 6.2 Edicao de tabela Cotacao

- Status: OK
- URL / tela: `Materiais Cotacao` > edicao do item id=4878
- Passos executados: Aberto o item; alterado um campo; clicado em Salvar; reaberto o item para conferir persistencia.
- Resultado observado: Edicao salva com sucesso e alteracao confirmada ao reabrir o item.
- Resultado esperado: Edicao e persistencia funcionando, com botao de salvar disponivel.
- Observacao de melhoria: O breadcrumb/titulo da tela de edicao do item de Cotacao exibe "Material SINAPI" em vez de refletir que se trata de um item de Cotacao (ver Secao 7).
- Impacto: N/A

### 6.3 Inativacao de itens

- Status: OK
- URL / tela: `Materiais Cotacao` (item COT.01) e listagem correspondente
- Passos executados: Alternado o campo Ativo de Sim para Nao e salvo; verificado que o item permanece na listagem, agora marcado como inativo.
- Resultado observado: Inativacao persistida corretamente; item continua visivel na listagem (com status inativo), como esperado para fins de historico/auditoria.
- Resultado esperado: Inativacao possivel, item permanece registrado, deixando de ser oferecido onde nao deveria ser selecionavel.
- Observacao de melhoria: Nenhuma adicional.
- Impacto: N/A

### 6.4 Cadastro e edicao de centro de custo novo

- Status: OK
- URL / tela: `Centro de Custos Novo` > Adicionar e edicao do item id=667
- Passos executados: Cadastrado manualmente um novo Centro de Custo Novo ("TESTE QA UNIDADE 1.0.8"); confirmado que aparece na listagem; reaberto para edicao, alterado um campo e clicado em `Salvar`; confirmada a mensagem "Item atualizado com sucesso" e a persistencia ao reabrir o registro.
- Resultado observado: Cadastro (`Adicionar`) e edicao (`Salvar`) funcionando corretamente e persistindo os dados - este e o comportamento especifico que o hotfix da 1.0.8 deveria restaurar.
- Resultado esperado: Cadastro e edicao funcionando, com botao `Salvar` disponivel e persistente na edicao.
- Observacao de melhoria: Nenhuma adicional.
- Impacto: N/A - hotfix confirmado.

### 6.5 Cadastro e edicao de centro de custo antigo

- Status: OK
- URL / tela: `Centro de Custos Antigo` > Adicionar e edicao do item id=231
- Passos executados: Cadastrado manualmente um novo Centro de Custo Antigo (codigo "99.999.999", campus "TESTE QA CAMPUS 1.0.8"); confirmado que aparece na listagem; reaberto para edicao (`costcenterlegacy.form.php?id=231`), alterado o campo `Endereco` para "Endereco teste QA 1.0.8" e clicado em `Salvar`; confirmada a mensagem "Item atualizado com sucesso: 99.999.999 - TESTE QA CAMPUS 1.0.8" e a persistencia do campo ao recarregar o formulario.
- Resultado observado: Cadastro (`Adicionar`) e edicao (`Salvar`) funcionando corretamente e persistindo os dados para a tabela Antiga tambem - confirma que o hotfix da 1.0.8 cobre igualmente o cadastro legado, nao apenas o Novo.
- Resultado esperado: Cadastro e edicao funcionando, com botao `Salvar` disponivel e persistente na edicao.
- Observacao de melhoria: Nenhuma adicional.
- Impacto: N/A - hotfix confirmado.

### 6.6 Inativacao de centro de custo novo e antigo

- Status: OK
- URL / tela: `Centro de Custos Novo` (id=667) e `Centro de Custos Antigo` (id=231)
- Passos executados: Para o item Novo (id=667): alternado Ativo Sim -> Nao, salvo, confirmada persistencia; item permaneceu visivel na listagem como inativo. Para o item Antigo (id=231): alternado Ativo Nao -> Sim (Salvar, confirmado "Item atualizado com sucesso"), depois Sim -> Nao novamente (Salvar, confirmado novamente), validando os dois sentidos da troca.
- Resultado observado: Inativacao/reativacao funcionando e persistindo corretamente nos dois cadastros (Novo e Antigo); registro dessa acao ficou confirmado por toast de sucesso em cada salvamento.
- Resultado esperado: Possivel marcar como inativo (e reativar) em ambos os cadastros, com persistencia.
- Observacao de melhoria: Nenhuma adicional.
- Impacto: N/A

---

## 7. Melhorias observadas mesmo com funcao funcionando

- Item: Estilo visual do campo de selecao no FormCreator
  Tela: Perguntas `Objeto GLPI` vs `Lista Suspensa` no mesmo formulario
  Observacao: O campo `Objeto GLPI` usa um chip estilo Select2, enquanto `Lista Suspensa` usa um `<select>` nativo simples; a diferenca visual pode confundir o usuario final sobre por que dois campos de centro de custo se comportam/parecem diferentes.
  Sugestao: Padronizar a aparencia dos dois tipos de campo quando aplicados a centros de custo, ou documentar a diferenca para quem monta os formularios.
  Severidade: Baixa

- Item: Rotulo fixo "Centro de Custos Novo" na coluna da tabela de Materiais Consumidos
  Tela: Ticket > aba `Materiais Consumidos`
  Observacao: A coluna que exibe o centro de custo do lancamento mostra sempre o cabecalho "Centro de Custos Novo", mesmo quando o valor exibido vem da tabela Antiga.
  Sugestao: Tornar o rotulo da coluna dinamico conforme a origem (`costcenter_source`) do lancamento, ou usar um rotulo neutro como "Centro de Custos".
  Severidade: Baixa

- Item: Breadcrumb/titulo incorreto na edicao de item de Cotacao
  Tela: `Materiais Cotacao` > edicao de item (ex.: id=4878)
  Observacao: O titulo/breadcrumb da tela exibe "Material SINAPI" ao editar um item que pertence a tabela de Cotacao/Mercado.
  Sugestao: Corrigir o titulo da tela para refletir corretamente "Material Cotacao" quando aplicavel.
  Severidade: Baixa

- Item: Falta de feedback ao vincular centro de custo automaticamente
  Tela: Ticket > aba `Materiais Consumidos` (primeiro lancamento em chamado sem centro vinculado)
  Observacao: Quando o primeiro material lancado vincula automaticamente um centro de custo ao chamado (Secao 5.2), nao ha nenhuma mensagem explicita informando essa vinculacao; o usuario so percebe ao checar a aba `Centro de Custos` manualmente.
  Sugestao: Exibir uma notificacao/toast informando que o centro de custo X foi vinculado automaticamente ao chamado.
  Severidade: Baixa

---

## Conclusao final

- Aprovado para uso: Aprovado com ressalvas.
- Motivo: O objetivo especifico da versao 1.0.8 - restaurar o botao `Salvar` na edicao de Centro de Custos Novo e Antigo - foi integralmente confirmado (Secoes 6.4, 6.5 e 6.6, todas OK, incluindo persistencia de edicao e de inativacao/reativacao nos dois cadastros). Os demais fluxos centrais do plugin (FormCreator, geracao de chamados, vinculo de centro de custo, tabelas SINAPI/Cotacao) tambem se mantiveram funcionando. Entretanto, a rodada identificou 2 problemas relevantes que nao fazem parte do escopo da 1.0.8, mas que devem ser tratados antes de considerar o plugin totalmente robusto para uso operacional sem ressalvas.
- Bloqueantes pendentes:
  1. Secao 5.3 - a aba `Materiais Consumidos` permite salvar um lancamento vazio (sem material, quantidade 0, sem centro de custo). Recomenda-se correcao com validacao obrigatoria antes da proxima rodada.
  2. Secao 4.3 / 5.1 - impossibilidade de vincular estruturalmente os dois centros de custo (Antigo e Novo) automaticamente a partir do FormCreator, e trava de alternancia Antigo/Novo na aba Materiais Consumidos mesmo com os dois vinculados manualmente. Esta e uma limitacao arquitetural conhecida e documentada (UNIQUE KEY em `ticketcostcenters`), nao uma regressao; requer migration de banco para ser resolvida, portanto deve ser tratada como item de backlog e nao como bloqueio imediato do hotfix 1.0.8.
- Melhorias nao bloqueantes: as 4 melhorias listadas na Secao 7 (estilo visual do campo FormCreator, rotulo fixo de coluna, breadcrumb incorreto na edicao de Cotacao, falta de feedback na vinculacao automatica de centro de custo).

## Dados de teste gerados durante a validacao (mantidos na VM, identificados)

- Ticket #94 (Cenario C - FormCreator com ambos os centros)
- Resposta FormCreator id=20
- Centro de Custos Novo id=667 - "TESTE QA UNIDADE 1.0.8" (Ativo=Sim)
- Centro de Custos Antigo id=231 - "TESTE QA CAMPUS 1.0.8" (codigo 99.999.999, Ativo=Nao)
