# Custos de Manutencao para GLPI 11

<p align="center">
  <img src="public/pics/logo.png" alt="Logo do plugin Custos de Manutencao" width="180">
</p>

[![Licenca](https://img.shields.io/badge/Licenca-GPLv3%2B-orange)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Versao](https://img.shields.io/badge/Versao-2.0.0--alpha.1-blue)](CHANGELOG.md)
[![GLPI](https://img.shields.io/badge/GLPI-11.0.x-green)](#compatibilidade)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](#compatibilidade)

Linha GLPI 11 do plugin, a partir da versao 2.0.0, com a base funcional da
1.1.21. Registra materiais consumidos em chamados, mantem catalogos SINAPI e
Cotacao/Mercado, preserva o preco aplicado em cada lancamento e relaciona custos
aos centros de custo.

> **Pacote exclusivo para GLPI 11**
>
> Esta linha exige **GLPI 11.0.7 a 11.0.99** e **PHP 8.2 ou superior**.
> Nao a instale no GLPI 10. Para GLPI 10, use a serie **1.1.x**, mantida na
> branch [`glpi10`](../../tree/glpi10) e publicada nas releases 1.1.x.

## Funcionalidades

- Catalogos de materiais SINAPI e Cotacao/Mercado, precos por competencia e
  historico de preco vigente.
- Centros de Custo Antigo e Novo como dropdowns nativos do GLPI, respeitando
  entidades e direitos, inclusive nas perguntas de lista dos Formularios
  nativos do GLPI 11. As respostas validas sao vinculadas automaticamente ao
  ticket criado, sem alterar a descricao configurada no destino do formulario.
- Lancamento de materiais consumidos na aba do chamado, com quantidade,
  unidade, preco aplicado, origem, competencia, data e comentario.
- Edicao, cancelamento logico, auditoria, relatorios e exportacoes CSV/PDF.
- Pesquisa, ordenacao, colunas configuraveis e acoes em massa nas listagens
  administrativas do plugin.

## Compatibilidade

- GLPI: **11.0.7 a 11.0.99**.
- PHP: **8.2 ou superior**.
- Banco de dados: MySQL/MariaDB suportado pelo GLPI 11.
- Formularios: usa os objetos de lista nativos do GLPI 11. O FormCreator e
  exclusivo da linha GLPI 10 e nao e carregado neste pacote. Um formulario
  pode informar somente o centro Antigo, somente o Novo ou ambos.

## Instalacao

1. Baixe o pacote da serie 2.x, exclusiva para GLPI 11.
2. Extraia a pasta `maintenancecosts` em `GLPI_ROOT/plugins/`.
3. Em **Configurar > Plugins**, instale ou atualize e ative **Custos de
   Manutencao**.
4. Limpe o cache do GLPI quando a instalacao solicitar.
5. Em **Administracao > Perfis**, conceda os direitos necessarios e configure
   as entidades que usarao o plugin.

Antes de uma atualizacao, faca backup do banco e da pasta atual do plugin. Nao
sobrescreva uma instalacao GLPI 10 com este pacote.

## Seguranca

- Acoes de escrita usam a protecao CSRF declarada pelo plugin e validacao de
  sessao, direito e entidade no servidor.
- A integracao e a excecao de autorizacao exclusivas do FormCreator foram
  removidas desta variante.
- O vinculo pelo formulario nativo aceita apenas centros de custo ativos da
  entidade do ticket ou de entidade ancestral marcada como recursiva. Respostas
  inconsistentes nao alteram o ticket nem sua descricao.
- As tabelas e migracoes sao executadas somente na instalacao ou atualizacao;
  hooks de requisicao nao executam DDL.

## Documentacao

- [Historico de alteracoes](CHANGELOG.md)
- [Manual de uso](docs/manual-de-uso.md)

## Suporte

- Autor: Fabio Neres
- Equipe responsavel: SUA UNIFESP
- Licenca: [GPLv3+](https://www.gnu.org/licenses/gpl-3.0.html)
