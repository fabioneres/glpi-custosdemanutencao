# Auditoria de Release 1.1.18

## Identificacao

- Plugin: Custos de Manutencao
- Versao: 1.1.18
- Artefato: `dist/maintenancecosts-1.1.18.zip`
- SHA-256: `556FE34CCE9D6557DC3DD655DB1AE59623371DB5768DA86C301FC834F8954DFE`
- Data: 2026-09-14

## Status Final

**Aprovada.**

O pacote possui uma unica raiz `maintenancecosts/`, contem somente arquivos operacionais do plugin e passou no lint de todos os arquivos PHP na VM de validacao.

## Evidencias

- 67 entradas no ZIP, sem `.git`, `dist`, documentacao interna, scripts de deploy, logs, arquivos temporarios ou outros pacotes compactados.
- `node --check` executado para `js/ticketmaterial-v3.js`.
- `php -l` executado para todos os PHP extraidos do pacote na VM `192.168.159.129`, sem erros.
- Plugin instalado, ativado e cache limpo na VM apos a atualizacao para 1.1.18.

## Revisao de Seguranca

- Endpoints administrativos continuam protegidos por direitos especificos do plugin.
- O endpoint de dropdown exige sessao autenticada. A excecao para FormCreator permanece apenas para consulta, sem escrita.
- A consulta de centros de custo aplica agora o escopo da entidade ativa e sua arvore visivel.
- O sincronismo do FormCreator grava somente a relacao estrutural com o chamado e nao altera `Ticket.content`.
- Nenhuma credencial ou segredo foi incluido no ZIP distribuivel.

## Teste Pendente

- Submeter um formulario com perfil solicitante sem direitos administrativos do plugin, selecionando centros Antigo e Novo, em uma entidade filha. Conferir os rotulos no formulario, o vinculo estruturado e a descricao definida pelo alvo do FormCreator.

## Decisao

Pode publicar.
