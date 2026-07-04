# Validacao detalhada - maintenancecosts 1.0.5

Data de preparo: 2026-07-04  
Ambiente alvo: VM `192.168.159.129`  
GLPI: homologacao  
Plugin: `maintenancecosts` versao `1.0.5`

## Objetivo

Executar uma validacao minuciosa via navegador, com foco principal no fluxo de
FormCreator + Centro de Custos, sem deixar de cobrir os pontos do plugin que o
usuario final usa no dia a dia.

## Regras desta validacao

- Validar no navegador, nao apenas por leitura de codigo.
- Registrar cada item como `OK` ou `NOK`.
- Em caso de `NOK`, registrar:
  - URL usada
  - passos exatos
  - resultado observado
  - resultado esperado
  - impacto funcional
- Sempre capturar evidencia visual dos fluxos criticos.

## Credenciais e acesso

- URL base: `http://192.168.159.129/glpi`
- Usuario: `glpi`
- Senha: `glpi`

## Escopo principal desta rodada

1. Fluxo dual de Centro de Custos no FormCreator
2. Busca e exibicao de `codigo - nome`
3. Vinculo estrutural do centro de custo no chamado
4. Persistencia da descricao no ticket
5. Regressao nas areas que podem ser afetadas por esse ajuste

---

## 1. Sanidade inicial do plugin

- [ ] Confirmar que o plugin `Custos de Manutencao` aparece ativo em `Configuracao > Plugins`
- [ ] Confirmar que a versao exibida e `1.0.5`
- [ ] Confirmar que nao ha erro visual de acentuacao no nome do plugin
- [ ] Confirmar que as telas principais do plugin abrem:
  - `Configuracoes`
  - `Centros de custo Novo`
  - `Centros de custo Antigo`
  - `Materiais consumidos`
  - `Relatorios de custos`

---

## 2. Validacao do FormCreator - definicao das listas

Ir em `Administracao > Formularios`, abrir o formulario de validacao criado na VM
e conferir as questoes ja existentes.

- [ ] Existe uma questao do tipo objeto GLPI para `Centro de Custos Antigo`
- [ ] Existe uma questao do tipo objeto GLPI para `Centro de Custos Novo`
- [ ] Os labels aparecem como:
  - `Centro de Custos Antigo`
  - `Centro de Custos Novo`
- [ ] A configuracao da questao continua usando objeto GLPI, nao lista estatica

Observacao importante:
- esta rodada deve confirmar se o comportamento especial continua funcionando
  usando o tipo de questao suportado pelo plugin.

---

## 3. Validacao do formulario publicado - exibicao e busca

Abrir a versao publicada do formulario e validar os dois campos.

### 3.1 Centro de Custos Antigo

- [ ] O dropdown abre sem erro
- [ ] A lista mostra `codigo - nome`, e nao apenas o nome
- [ ] A busca encontra pelo nome
- [ ] A busca encontra pelo codigo com pontuacao
- [ ] A busca encontra pelo codigo sem pontuacao
- [ ] Exemplo recomendado:
  - buscar `007008019`
  - encontrar `007.008.019 - ADM/ANF. LEAL PRADO`

### 3.2 Centro de Custos Novo

- [ ] O dropdown abre sem erro
- [ ] A lista mostra `codigo - nome`, e nao apenas o nome
- [ ] A busca encontra pelo nome
- [ ] A busca encontra pelo codigo com pontuacao
- [ ] A busca encontra pelo codigo sem pontuacao
- [ ] Exemplo recomendado:
  - buscar `0100000000001`
  - encontrar `01.00.000.000.001 - NUCLEO DE BIOEQUIVALENCIA E ENSAIOS CLINICOS - NUBEC`

### 3.3 Erros que nao podem aparecer

- [ ] Nao aparece `Nenhum resultado encontrado` para um codigo valido conhecido
- [ ] Nao aparece `Os resultados nao puderam ser carregados`
- [ ] Nao ha lentidao excessiva ao abrir os dois dropdowns

---

## 4. Cenario A - formulario com os dois centros de custo preenchidos

Preencher:

- `Centro de Custos Antigo` com `007.008.019 - ADM/ANF. LEAL PRADO`
- `Centro de Custos Novo` com `01.00.000.000.001 - NUCLEO DE BIOEQUIVALENCIA E ENSAIOS CLINICOS - NUBEC`

Enviar o formulario.

### 4.1 Resultado imediato

- [ ] O GLPI informa que o formulario foi salvo com sucesso
- [ ] O GLPI informa o numero do chamado criado

### 4.2 Validacao no ticket gerado

Abrir o chamado criado.

- [ ] Na descricao / dados do formulario aparece o centro de custo antigo com `codigo - nome`
- [ ] Na descricao / dados do formulario aparece o centro de custo novo com `codigo - nome`
- [ ] Os dois aparecem simultaneamente
- [ ] Nao ha lixo visual como `nn`, `br` literal, duplicacao estranha ou linha quebrada de forma incorreta

### 4.3 Validacao da aba Centro de Custos

- [ ] A aba `Centro de Custos` existe no chamado
- [ ] O vinculo estrutural do chamado ficou com a tabela `Antigo`
- [ ] O valor vinculado estruturalmente e `007.008.019 - ADM/ANF. LEAL PRADO`
- [ ] O centro de custo novo nao substituiu indevidamente o antigo nesse vinculo

---

## 5. Cenario B - formulario com apenas o centro de custo novo preenchido

Preencher:

- `Centro de Custos Antigo`: deixar vazio
- `Centro de Custos Novo`: selecionar `01.00.000.000.001 - NUCLEO DE BIOEQUIVALENCIA E ENSAIOS CLINICOS - NUBEC`

Enviar o formulario.

### 5.1 Validacao no ticket gerado

- [ ] Na descricao / dados do formulario aparece apenas o centro de custo novo
- [ ] O centro antigo nao aparece indevidamente

### 5.2 Validacao da aba Centro de Custos

- [ ] A aba `Centro de Custos` existe no chamado
- [ ] O vinculo estrutural ficou com a tabela `Novo`
- [ ] O valor vinculado e o centro de custo novo selecionado

---

## 6. Cenario C - formulario com apenas o centro de custo antigo preenchido

Preencher:

- `Centro de Custos Antigo`: selecionar um centro valido conhecido
- `Centro de Custos Novo`: deixar vazio

Enviar o formulario.

- [ ] Na descricao / dados do formulario aparece apenas o centro antigo
- [ ] Na aba `Centro de Custos`, o chamado fica vinculado ao centro antigo

---

## 7. Pesquisa nativa de chamados por centro de custo

Abrir a busca de chamados do GLPI e validar os filtros nativos adicionados pelo plugin.

- [ ] Existe filtro `Centro de Custos Antigo`
- [ ] Existe filtro `Centro de Custos Novo`
- [ ] Filtrar por um centro antigo retorna o chamado correto
- [ ] Filtrar por um centro novo retorna o chamado correto
- [ ] Os resultados nao misturam antigo com novo

Se possivel, usar como evidencia os chamados criados nos cenarios A, B e C.

---

## 8. Aba Materiais Consumidos - regressao minima

Abrir um chamado comum e validar que o ajuste de FormCreator nao quebrou a area de materiais.

- [ ] A aba `Materiais Consumidos` abre normalmente
- [ ] O campo `Data` vem preenchido automaticamente com a data vigente
- [ ] O campo `Data` continua editavel
- [ ] O campo `Competencia` vem preenchido
- [ ] O seletor `Tabela de centro de custo` continua funcionando
- [ ] O dropdown de centro de custo continua carregando resultados

---

## 9. Regressao de relatorios

Abrir `Relatorios de custos`.

- [ ] Tela abre sem travar
- [ ] Dropdowns principais abrem sem erro
- [ ] Nao aparece `Os resultados nao puderam ser carregados`
- [ ] Filtro por centro de custo continua funcionando

---

## 10. Resultado esperado para aprovar 1.0.5

A versao `1.0.5` pode ser considerada aprovada se:

- os cenarios A, B e C forem `OK`;
- os dropdowns de FormCreator mostrarem `codigo - nome`;
- a busca funcionar por codigo com e sem pontuacao;
- o chamado mantiver a descricao correta;
- o vinculo estrutural do ticket respeitar a regra:
  - preferir `Antigo` quando ambos existirem
  - usar `Novo` apenas como fallback;
- nao houver regressao em `Materiais Consumidos` e `Relatorios`.

## Registro do resultado

Salvar o resultado detalhado em:

`plugins/meusplugins/maintenancecosts/docs/session/maintenancecosts-1.0.5-validacao-resultado.md`
