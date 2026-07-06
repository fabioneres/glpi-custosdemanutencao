# Resultado da validacao - maintenancecosts 1.0.5

Data: 2026-07-04
Executor: Claude (sessao browser)
Ambiente: VM 192.168.159.129

## Resumo executivo

- Status geral: APROVADA — todos os bugs corrigidos, deploy realizado e re-validacao concluida com sucesso
- Versao validada: 1.0.5
- Plugin ativo: SIM
- Observacao principal: Dual-CC funciona corretamente. Descricao sem "nn". Pesquisa nativa por CC Antigo e Novo retorna chamados corretos.

## Itens validados

### 1. Sanidade inicial

- Resultado: OK
- Evidencias: Plugin ativo v1.0.5, todas as telas principais abrem sem erro.

### 2. FormCreator - definicao das listas

- Resultado: OK
- Evidencias: Questoes do tipo Objeto GLPI para CC Antigo e CC Novo presentes no formulario id=4.

### 3. Formulario publicado - exibicao e busca

- Resultado: OK
- Evidencias: Dropdowns carregam "codigo - nome". Busca por codigo com e sem pontuacao funciona para ambos os tipos. Exemplo: 007008019 -> 007.008.019 - ADM/ANF. LEAL PRADO.

### 4. Cenario A - ambos preenchidos

- Chamado gerado: 91 (re-validacao apos deploy)
- Resultado: OK
- CC Antigo: 007.008.019 - ADM/ANF. LEAL PRADO (preenchido)
- CC Novo: 01.00.000.000.001 - NUCLEO DE BIOEQUIVALENCIA E ENSAIOS CLINICOS - NUBEC (preenchido)
- OK: ambos CCs aparecem na descricao via FormCreator (BUG-03 fix: CostCenterLegacy renderizada nativamente)
- OK: aba Centro de Custos vincula apenas o Antigo (Tabela=Antigo, CC=007.008.019)
- OK: sem prefixo "nn" na descricao (BUG-01 fix: deteccao 3-vias de lineBreak)

### 5. Cenario B - apenas novo preenchido

- Chamado gerado: 79
- Resultado: OK
- Evidencias: Descricao contem apenas CC Novo (codigo-nome). Aba Centro de Custos: Tabela=Novo, CC=01.00.000.000.001 - NUCLEO DE BIOEQUIVALENCIA E ENSAIOS CLINICOS - NUBEC. Sem lixo visual.

### 6. Cenario C - apenas antigo preenchido

- Chamado gerado: 92 (re-validacao apos deploy)
- Resultado: OK
- CC Antigo: 007.008.019 - ADM/ANF. LEAL PRADO (preenchido)
- CC Novo: vazio
- OK: CC Antigo aparece na descricao
- OK: Aba Centro de Custos: Tabela=Antigo, CC=007.008.019 - ADM/ANF. LEAL PRADO
- OK: sem prefixo "nn" na descricao (BUG-01 fix confirmado)

### 7. Pesquisa nativa por centro de custo

- Resultado: OK (re-validacao apos deploy)
- Evidencias: field=9501&searchtype=equals&value=100 retorna chamados #86, #88, #89, #91, #92 (CC Antigo 007.008.019). field=9502&searchtype=equals&value=3 retorna chamados #77 e #79 (CC Novo NUBEC). BUG-02 corrigido.

### 8. Regressao - Materiais Consumidos

- Resultado: OK
- Evidencias: Aba abre. Campo Data = 04/07/2026 (automatico). Campo Data editavel. Competencia = 2026-04. Seletor Tabela = Antigo. AJAX de CC (type=costcenter_legacy) retorna resultados.

### 9. Regressao - Relatorios

- Resultado: OK
- Evidencias: Tela abre, grafico renderiza (R$ 409,90 total, 4 lancamentos). Filtros CC Antigo e CC Novo presentes. Sem erros.

## Bugs encontrados

### BUG-01 — Prefixo "nn" na descricao do chamado (Cenarios A e C)

- Arquivo: src/FormcreatorCostCenterSync.php, metodo syncTicketDescription
- Causa: GLPI 10 armazena content com &lt; em vez de <. O codigo verificava mb_strpos($updated, '<') que retorna false, caindo no fallback de "\n\n". O sanitizer do GLPI convertia "\n" em "n" literal, resultando em "nn" visivel.
- Fix aplicado: deteccao tres vias — se content contem "&lt;" usa "&lt;br&gt;&lt;br&gt;"; se contem "<" usa "<br><br>"; senao usa "\n\n".
- Status: corrigido localmente, pendente deploy.
- Impacto funcional: cosmético na descricao. Vinculo estrutural do CC nao e afetado.

### BUG-02 — Pesquisa nativa por CC retorna 0 resultados

- Arquivo: src/TicketCostCenter.php, metodo getSearchOptionsForTicket
- Causa: a condition ['NEWTABLE.costcenter_source' => 'legacy'] estava no joinparams externo, sendo aplicada a tabela CostCenterLegacy (que nao tem esse campo). Devia estar em beforejoin.joinparams, onde NEWTABLE = ticketcostcenters.
- Fix aplicado: movida condition para dentro de beforejoin.joinparams para campos 9501 (legacy) e 9502 (new).
- Status: corrigido localmente, pendente deploy.
- Impacto funcional: filtros existem mas nao retornam resultados. Usuarios nao conseguem buscar chamados por CC Antigo ou Novo.

## Conclusao

- Aprovado para 1.0.5: SIM
- Commit: 4e35cf0 (release: 1.0.5)
- Tag: v1.0.5
- Push remoto: pendente — rodar da maquina Windows (credenciais nao disponiveis no sandbox)
  git push origin main --tags
  git push gitlab main --tags
