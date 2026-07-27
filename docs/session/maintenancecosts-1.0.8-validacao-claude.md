# Validacao funcional detalhada - maintenancecosts 1.0.8

Data de preparo: 2026-07-07  
Responsavel pela execucao do checklist: Claude  
Ambiente alvo principal: VM `192.168.159.129`  
Plugin: `maintenancecosts` versao `1.0.8`

## Objetivo

Executar uma validacao funcional exaustiva, orientada ao uso real do usuario,
registrando:

- o que funciona;
- o que falha;
- o que pode melhorar, mesmo quando a funcao estiver tecnicamente funcionando.

Esta rodada tem foco especial em:

- FormCreator com centros de custo Novo e Antigo;
- vinculacao do centro de custo ao chamado;
- consistencia entre aba `Centro de Custos` e aba `Materiais consumidos`;
- edicao, inativacao e persistencia de dados nas tabelas principais.

## Regras obrigatorias desta validacao

- O Claude **nao pode editar codigo** nesta rodada.
- O Claude **nao pode aplicar correcao no plugin** nesta rodada.
- O Claude deve validar **via navegador**, nao apenas por leitura de codigo.
- O Claude deve registrar **OK**, **NOK** ou **MELHORIA** para cada teste.
- Mesmo quando o item estiver `OK`, o Claude deve observar se ha:
  - problema visual;
  - fluxo confuso;
  - nomenclatura ruim;
  - comportamento estranho;
  - acao incompleta.

Exemplo de observacao obrigatoria:

> Nao pode existir um botao `Editar` onde nao seja possivel salvar a atualizacao.

## Arquivos que o Claude deve ler antes de iniciar

Ler nesta ordem:

1. [manual-de-uso.md](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\manual-de-uso.md)
2. [HANDOFF.md](C:\Projetos\glpi\docs\session\HANDOFF.md)
3. [maintenancecosts-1.0.8-validacao-claude.md](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\session\maintenancecosts-1.0.8-validacao-claude.md)

## Arquivo onde o Claude deve montar o relatorio do checklist

Preencher o resultado em:

[maintenancecosts-1.0.8-validacao-resultado.md](C:\Projetos\glpi\plugins\meusplugins\maintenancecosts\docs\session\maintenancecosts-1.0.8-validacao-resultado.md)

## Como registrar cada item

Para cada teste, o Claude deve registrar:

- status: `OK`, `NOK` ou `MELHORIA`
- URL / tela usada
- passos executados
- resultado observado
- resultado esperado
- observacao de melhoria, mesmo que esteja funcionando

Se houver falha, registrar tambem:

- impacto para o usuario
- impacto em dados
- se o problema bloqueia homologacao

---

## 1. Sanidade inicial do plugin

- [ ] Confirmar que o plugin `Custos de Manutencao` esta ativo
- [ ] Confirmar que a versao exibida e `1.0.8`
- [ ] Confirmar que as telas principais do plugin abrem sem erro
- [ ] Confirmar que nao ha tela branca, erro `500`, `403` ou `404`
- [ ] Confirmar que a acentuacao geral do plugin esta legivel

Observacoes obrigatorias:

- O menu do plugin esta claro para o usuario?
- Alguma tela abre, mas com rotulos ruins, desalinhamento ou campos confusos?

---

## 2. FormCreator - questoes usando Objeto GLPI

Validar perguntas configuradas como `Objeto GLPI`.

### 2.1 Centro de Custos Antigo (Objeto GLPI)

- [ ] E possivel criar/editar uma questao usando `Centro de Custos Antigo`
- [ ] A lista publicada mostra `codigo - nome`
- [ ] A busca encontra pelo nome
- [ ] A busca encontra pelo codigo com pontuacao
- [ ] A busca encontra pelo codigo sem pontuacao
- [ ] A selecao fica gravada corretamente

### 2.2 Centro de Custos Novo (Objeto GLPI)

- [ ] E possivel criar/editar uma questao usando `Centro de Custos Novo`
- [ ] A lista publicada mostra `codigo - nome`
- [ ] A busca encontra pelo nome
- [ ] A busca encontra pelo codigo com pontuacao
- [ ] A busca encontra pelo codigo sem pontuacao
- [ ] A selecao fica gravada corretamente

Observacoes obrigatorias:

- O comportamento visual esta igual ao das outras questoes do formulario?
- Existe algum destaque, fundo branco, marcador estranho ou estilo que faca a resposta parecer diferente das demais?

---

## 3. FormCreator - questoes usando Listas Suspensas

Validar perguntas configuradas como `Listas Suspensas`.

### 3.1 Centro de Custos Antigo (Lista Suspensa)

- [ ] E possivel selecionar a lista correspondente ao centro de custo antigo
- [ ] A lista publicada mostra `codigo - nome`
- [ ] A busca encontra pelo nome
- [ ] A busca encontra pelo codigo com pontuacao
- [ ] A busca encontra pelo codigo sem pontuacao

### 3.2 Centro de Custos Novo (Lista Suspensa)

- [ ] E possivel selecionar a lista correspondente ao centro de custo novo
- [ ] A lista publicada mostra `codigo - nome`
- [ ] A busca encontra pelo nome
- [ ] A busca encontra pelo codigo com pontuacao
- [ ] A busca encontra pelo codigo sem pontuacao

Observacoes obrigatorias:

- A experiencia de uso de `Lista Suspensa` esta coerente com `Objeto GLPI`?
- O usuario entende facilmente qual lista deve usar?

---

## 4. Centro de custo do formulario vinculado ao chamado

Criar chamados via formulario e validar o reflexo no ticket.

### Cenario A - somente centro antigo

- [ ] Ao preencher somente o centro antigo, ele aparece na descricao do chamado
- [ ] Ao preencher somente o centro antigo, ele fica vinculado na aba `Centro de Custos`

### Cenario B - somente centro novo

- [ ] Ao preencher somente o centro novo, ele aparece na descricao do chamado
- [ ] Ao preencher somente o centro novo, ele fica vinculado na aba `Centro de Custos`

### Cenario C - antigo e novo ao mesmo tempo

- [ ] Ambos aparecem na descricao do chamado
- [ ] Ambos ficam vinculados na aba `Centro de Custos`
- [ ] A aba mostra um campo para `Antigo` e outro para `Novo`

Observacoes obrigatorias:

- A descricao do chamado exibe o valor como resposta normal ou com estilo estranho?
- Ha coerencia entre o que foi respondido no formulario e o que ficou vinculado na aba?

---

## 5. Aba Centro de Custos do chamado

- [ ] E possivel salvar um centro de custo antigo
- [ ] E possivel salvar um centro de custo novo
- [ ] E possivel salvar os dois ao mesmo tempo
- [ ] E possivel remover os vinculos
- [ ] A tela mantem alinhamento visual adequado apos salvar

Observacoes obrigatorias:

- Os campos ficam compactos e legiveis apos salvar?
- O usuario entende claramente que pode manter um antigo e um novo?

---

## 6. Ao modificar na tab Centro de Custos, modifica o que esta em Materiais?

Validar a relacao entre a aba `Centro de Custos` e a aba `Materiais consumidos`.

- [ ] Quando o chamado possui centro antigo vinculado, a aba `Materiais consumidos` usa esse antigo por padrao
- [ ] Quando o chamado possui centro novo vinculado, a aba `Materiais consumidos` permite alternar e usar o novo vinculado
- [ ] Quando o chamado possui antigo e novo vinculados, a aba `Materiais consumidos` alterna corretamente entre as duas bases
- [ ] Ao trocar o centro na aba `Centro de Custos`, a aba `Materiais consumidos` reflete a mudanca
- [ ] O tecnico nao consegue escolher livremente um centro diferente do vinculado ao chamado

Observacoes obrigatorias:

- A alternancia entre `Antigo` e `Novo` esta clara?
- Ha mensagens desnecessarias ou comportamento confuso?

---

## 7. Se lancar centro de custo via Materiais consumidos, ele vincula ao chamado?

Executar o fluxo em chamado sem centro de custo previamente vinculado.

- [ ] Sem centro vinculado, o tecnico pode escolher livremente um centro ao adicionar material
- [ ] Ao salvar um material com centro escolhido, o centro e vinculado automaticamente ao chamado
- [ ] Depois disso, a aba `Centro de Custos` passa a exibir o centro vinculado
- [ ] Depois disso, a aba `Materiais consumidos` deixa de permitir escolher centros divergentes

Observacoes obrigatorias:

- O usuario percebe que o centro foi vinculado ao chamado?
- O fluxo esta intuitivo ou precisa de melhor orientacao visual?

---

## 8. E possivel nao adicionar materiais e salvar mesmo assim um centro pela tela de materiais?

Validar comportamento invalido.

- [ ] Nao e possivel salvar mudanca de centro de custo pela tela `Materiais consumidos` sem lancar um material
- [ ] A tela nao deve permitir persistencia de alteracoes parciais sem item consumido
- [ ] Deve ficar claro para o usuario que essa tela e para lancamento de material, nao para edicao isolada do centro

Observacoes obrigatorias:

- A mensagem de erro ou bloqueio ajuda o usuario?
- O fluxo induz o usuario a erro?

---

## 9. E possivel editar a tabela SINAPI?

- [ ] E possivel abrir um item de materiais SINAPI
- [ ] E possivel editar os campos relevantes
- [ ] E possivel salvar a alteracao
- [ ] A alteracao persiste ao reabrir o item

Observacoes obrigatorias:

- Existe botao `Editar` sem possibilidade de `Salvar`?
- O formulario de edicao esta claro?

---

## 10. E possivel editar a tabela de materiais Cotacao?

- [ ] E possivel abrir um item de materiais Cotacao
- [ ] E possivel editar os campos relevantes
- [ ] E possivel salvar a alteracao
- [ ] A alteracao persiste ao reabrir o item

Observacoes obrigatorias:

- Existe botao `Editar` sem possibilidade de `Salvar`?
- O formulario de edicao esta claro?

---

## 11. E possivel deixar itens inativos nas duas tabelas?

### 11.1 Materiais SINAPI

- [ ] E possivel marcar item como inativo
- [ ] Item inativo permanece registrado
- [ ] Item inativo deixa de aparecer onde nao deveria ser selecionado

### 11.2 Materiais Cotacao

- [ ] E possivel marcar item como inativo
- [ ] Item inativo permanece registrado
- [ ] Item inativo deixa de aparecer onde nao deveria ser selecionado

Observacoes obrigatorias:

- O status ativo/inativo esta visivel?
- O impacto da inativacao esta claro para o usuario?

---

## 12. Funciona cadastrar um centro de custo Novo e um Antigo?

### 12.1 Centro de Custo Novo

- [ ] E possivel cadastrar manualmente
- [ ] O registro aparece na listagem

### 12.2 Centro de Custo Antigo

- [ ] E possivel cadastrar manualmente
- [ ] O registro aparece na listagem

Observacoes obrigatorias:

- Os labels do formulario estao corretos?
- Existe campo redundante, ambiguidade ou layout ruim?

---

## 13. E possivel editar e salvar um centro de custo Novo? E um Antigo?

### 13.1 Centro de Custo Novo

- [ ] E possivel abrir para edicao
- [ ] E possivel alterar campos
- [ ] Existe botao de salvar
- [ ] O save persiste

### 13.2 Centro de Custo Antigo

- [ ] E possivel abrir para edicao
- [ ] E possivel alterar campos
- [ ] Existe botao de salvar
- [ ] O save persiste

Observacoes obrigatorias:

- Nao pode existir um botao `Editar` sem possibilidade de `Salvar`.
- A posicao do botao de salvar esta evidente?

---

## 14. E possivel inativar um centro de custo Novo? E um Antigo?

### 14.1 Centro de Custo Novo

- [ ] E possivel marcar como inativo
- [ ] A alteracao persiste

### 14.2 Centro de Custo Antigo

- [ ] E possivel marcar como inativo
- [ ] A alteracao persiste

Observacoes obrigatorias:

- O usuario entende se esta ativando/inativando?
- O controle de status esta claro?

---

## 15. Observacoes adicionais que o Claude deve sempre procurar

Mesmo que a funcao esteja `OK`, verificar e anotar se existe:

- [ ] campo com acentuacao quebrada
- [ ] texto mal escrito
- [ ] botao sem acao correspondente
- [ ] botao `Editar` sem `Salvar`
- [ ] tabela com coluna de acoes ocultavel indevidamente
- [ ] dropdown com erro `Os resultados nao puderam ser carregados`
- [ ] campo que ocupa largura exagerada apos salvar
- [ ] fluxo que depende de conhecimento tecnico demais do usuario

---

## Criterio para conclusao do checklist

O Claude so deve marcar a rodada como aprovada se:

- todos os testes criticos acima estiverem `OK`; e
- todos os `NOK` estiverem claramente documentados; e
- todas as `MELHORIAS` observadas estiverem registradas, mesmo que nao bloqueiem.

