# Resultado da validacao - maintenancecosts 1.0.5

Data: 2026-07-04
Executor: Claude (sessao browser)
Ambiente: VM 192.168.159.129

## Resumo executivo

- Status geral: NAO APROVADA — 2 bugs encontrados, corrigidos localmente, pendentes de deploy e re-validacao
- Versao validada: 1.0.5
- Plugin ativo: SIM
- Observacao principal: Dual-CC funciona estruturalmente (vinculo + descricao), mas descricao tem prefixo "nn" para CCs do tipo Antigo (fix pronto, nao implantado). Busca nativa por CC retorna 0 resultados (fix pronto, nao implantado).

## Itens validados

### 1. Sanidade inicial

- Resultado: OK
- Evidencias: Plugin ativo v1.0.5, todas as telas principais abrem sem erro.

### 2. FormCreator - definicao das listas

- Resultado: OK
- Evidencias: Questoes do tipo Objeto GLPI para CC Antigo e CC Novo presentes no formulario id=4.

### 3. Formulario publicado - exibicao e busca

- Resultado: OK
- Evidencias: Dropdowns carregam "codigo - nome". Busca por codigo com e sem pontuacao fun