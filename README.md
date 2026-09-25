# Custos de Manutencao para GLPI 11

<p align="center">
  <img src="pics/logo.png" alt="Logo do plugin Custos de Manutencao" width="180">
</p>

[![Licenca](https://img.shields.io/badge/Licenca-GPLv3%2B-orange)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Base funcional](https://img.shields.io/badge/Base-1.1.21-blue)](CHANGELOG.md)
[![GLPI](https://img.shields.io/badge/GLPI-11.0.x-green)](#compatibilidade)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](#compatibilidade)

Variante para GLPI 11 da linha funcional 1.1.21. Registra materiais
consumidos em chamados, mantem catalogos SINAPI e Cotacao/Mercado, preserva o
preco aplicado em cada lancamento e relaciona custos aos centros de custo.

> **Pacote exclusivo para GLPI 11**
>
> Esta variante exige **GLPI 11.0.7 a 11.0.99** e **PHP 8.2 ou superior**.
> Nao a instale no GLPI 10. Para GLPI 10, use a release 1.1.21 original.

## Funcionalidades

- Catalogos de materiais SINAPI e Cotacao/Mercado, precos por competencia e
  historico de preco vigente.
- Centros de Custo Antigo e Novo como dropdowns nativos do GLPI, respeitando
  entidades e direitos, inclusive nas perguntas de lista dos Formularios
  nativos do GLPI 11.
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
  exclusivo da linha GLPI 10 e nao e carregado neste pacote.

## Instalacao

1. Baixe o pacote GLPI 11 correspondente a esta variante.
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
- As tabelas e migracoes sao executadas somente na instalacao ou atualizacao;
  hooks de requisicao nao executam DDL.

## Documentacao

- [Historico de alteracoes](CHANGELOG.md)
- [Manual de uso](docs/manual-de-uso.md)

Os documentos de PRD, checklist e sessoes historicas presentes na arvore de
desenvolvimento referem-se a linha GLPI 10 e nao fazem parte do pacote GLPI 11.

## Suporte

- Autor: Fabio Neres
- Equipe responsavel: SUA UNIFESP
- Licenca: [GPLv3+](https://www.gnu.org/licenses/gpl-3.0.html)
