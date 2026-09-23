# Changelog

## v1.1.20 - Preservacao do rotulo do centro de custo

- Corrige a perda do rotulo do centro de custo em atualizacao parcial. O campo `name` era recomposto usando somente os campos enviados na requisicao, entao uma atualizacao que nao enviava codigo e campos organizacionais gravava o rotulo vazio. A acao em massa do campo `Recursivo`, introduzida na 1.1.19, e uma atualizacao parcial e tornou o problema facil de disparar em lote.
- O rotulo passa a ser recomposto sobre o registro completo e nunca e gravado vazio. Alterar somente o codigo ou somente o departamento continua recompondo o rotulo corretamente, sem perder a outra parte.
- Efeito pratico: centros de custo com rotulo vazio nao apareciam na descricao do chamado gerada pelo FormCreator, apesar de aparecerem corretamente na aba do plugin, porque a aba recompoe o rotulo e o dropdown nativo le o campo gravado.
- Trocar o codigo de um centro de custo sem campos organizacionais nao acumula mais o codigo antigo no rotulo. Antes, cada troca prefixava o novo codigo ao rotulo anterior.
- Limpar pelo formulario o unico campo organizacional preenchido volta a deixar o rotulo apenas com o codigo, em vez de manter o texto antigo.
- Preserva o rotulo descritivo de centros de custo sem campos organizacionais, como o centro padrao `GERAL`, ao salvar pelo formulario. O rotulo passa a ser recomposto conforme sua origem: derivado dos campos organizacionais quando o registro os possui, ou mantido como texto livre quando nao possui.
- Corrige as origens do material, que eram desativadas em qualquer atualizacao parcial. A acao em massa sobre o campo `Comentarios` desativava todas as origens selecionadas.
- A importacao de centros de custo novos e antigos passa a recusar codigo acima do tamanho da coluna, em vez de trunca-lo em silencio. A recusa ja existia na validacao, mas o codigo era cortado antes de chegar a ela.

## v1.1.19 - Isolamento por entidade e integridade da importacao

- Corrige falha de isolamento entre entidades nas telas de cadastro. As telas validavam apenas o direito do perfil e se o plugin estava habilitado, sem conferir a entidade do registro alvo. Quem tinha o direito em uma entidade conseguia alterar ou excluir registro de outra entidade informando o identificador. Passa a ser exigido que a entidade do proprio registro esteja entre as entidades ativas do usuario, em materiais, centros de custo novos e antigos, precos, origens e lancamentos de chamado.
- Impede criar registro em entidade a qual o usuario nao tem acesso.
- Corrige o vinculo de centro de custo no chamado, que aceitava chamado de outra entidade e gravava a entidade vinda do formulario. A entidade passa a ser derivada do proprio chamado.
- Corrige a habilitacao do plugin por entidade, que aceitava gravar a regra de uma entidade ancestral. Um administrador de entidade filha conseguia ligar ou desligar o plugin para toda a arvore, inclusive entidades irmas.
- Exclusao passa a exigir a entidade exata do registro. Alterar um registro compartilhado pela entidade raiz continua permitido a quem o enxerga, mas apaga-lo cabe a entidade dona.
- Corrige a importacao, que podia sobrescrever registro de outra entidade. Como o codigo e unico no sistema inteiro, um codigo existente pode pertencer a outra entidade; essas linhas passam a ser recusadas. A atualizacao de centro de custo tambem deixa de reescrever entidade e recursividade, o que movia o registro para a entidade de quem importava.
- Exibe o campo `Recursivo` como coluna e na acao em massa de Materiais SINAPI, Centros de Custo e Centros de Custo Antigo, dispensando abrir registro por registro para alterar em lote.
- Corrige atualizacoes parciais de materiais, que zeravam `Ativo` e `Recursivo` quando esses campos nao eram enviados na requisicao. Como a importacao atualiza o material sem enviar `Recursivo`, cada reimportacao desfazia o ajuste manual do usuario.
- Torna a importacao atomica: o arquivo inteiro e validado antes de qualquer gravacao e, a partir dai, tudo e gravado em transacao unica. Qualquer falha reverte a importacao completa e nenhum registro permanece pela metade. Vale para SINAPI, Cotacao, Centros de Custo e Centros de Custo Antigo.
- Recusa linhas cujo codigo ou unidade excedam o tamanho da coluna, em vez de trunca-los silenciosamente. Como o codigo identifica o material, o corte podia unir materiais distintos no mesmo registro.
- Informa na tela quando a importacao e recusada, deixando explicito que nenhum registro foi gravado e quais linhas precisam ser corrigidas.
- Torna atomico o salvamento da configuracao por entidade, que apagava os registros e reinseria sem transacao. Uma falha entre as duas etapas deixaria a configuracao vazia, sem historico para reconstruir.
- Limita no formulario o tamanho dos campos de codigo e unidade ao tamanho real da coluna.

## v1.1.18 - FormCreator para perfil solicitante

- Corrige a inicializacao dos dropdowns de Centro de Custo Antigo e Novo quando os scripts dos campos do FormCreator sao carregados depois do JavaScript do plugin, permitindo o uso por perfis solicitantes sem direito administrativo do Custos de Manutencao.
- Mantem o endpoint de consulta protegido por sessao autenticada e limita a excecao de permissao ao fluxo identificado do FormCreator; as telas administrativas continuam exigindo os direitos do plugin.
- Restringe a consulta de centros de custo do FormCreator a entidade ativa e sua arvore visivel, preservando o isolamento multi-entidade.
- Sincroniza uma unica vez os centros de custo importados cujo campo `name` continha apenas o codigo, para que o alvo do FormCreator receba diretamente o rotulo `codigo - nome`.
- Restaura a separacao de responsabilidades: o plugin grava somente o vinculo estruturado do centro de custo; a descricao do chamado continua exclusivamente sob controle do alvo do FormCreator.

## v1.1.17 - Preservacao de status dos centros de custo

- Corrige atualizacoes parciais de centros de custo novos e antigos para preservar os campos `Ativo` e `Recursivo` quando eles nao forem enviados na requisicao.
- Novos centros de custo continuam sendo criados como ativos e nao-recursivos por padrao.

## v1.1.16 - Preservacao do conteudo do FormCreator

- O sincronismo de centros de custo nao le, normaliza, complementa nem atualiza a descricao do chamado.
- O conteudo do chamado passa a ser exclusivamente aquele definido pelo alvo do FormCreator; o plugin mantem somente o vinculo interno dos centros de custo.

## v1.1.15 - Catalogo SINAPI sem filtro predefinido

- Mantem a separacao entre Materiais SINAPI e Materiais Cotacao diretamente na listagem, sem exibir um filtro fixo na pesquisa nativa do GLPI.
- Preserva todos os recursos de pesquisa, ordenacao, configuracao de colunas e acoes em massa para o usuario.

## v1.1.14 - Correcoes de centros de custo e catalogo SINAPI

- Impede que o sincronismo do plugin replique respostas de centro de custo que o FormCreator ja gravou na descricao do chamado.
- Evita a exibicao incorreta de `codigo - codigo` em centros de custo com dados legados e usa o nome organizacional disponivel.
- Exibe em Materiais SINAPI todos os materiais cujo codigo nao pertence a cotacao (`COT`), sem depender de uma marca de preco vigente que pode estar desatualizada apos importacoes.

## v1.1.13 - Otimizacao da atualizacao

- Evita reprocessar todos os precos, materiais, centros de custo e chamados durante atualizacoes que nao alteram o esquema do banco de dados.

## v1.1.12 - Hotfix do FormCreator

- Corrige o carregamento de centros de custo Antigo e Novo no FormCreator para perfis que respondem formularios sem permissao administrativa do plugin.

## Em desenvolvimento
- Amplia os campos de Centro de Custo no lancamento de materiais e na aba `Centro de Custos` do chamado, preservando a busca e selecao por Select2.
- Migra as listagens de Materiais SINAPI e Centros de Custos Antigo/Novo para o mecanismo nativo de pesquisa e listagem do GLPI, com filtros, ordenacao, configuracao de colunas e acoes em massa.
- Migra a listagem de Origens do material para a pesquisa nativa do GLPI.
- Remove a rolagem vertical duplicada das listas nativas dentro do layout do plugin.
- Permite que o layout e as tabelas do plugin ocupem toda a largura disponivel da tela.
- Migra a listagem de Precos SINAPI para o mecanismo nativo de pesquisa e listagem do GLPI, preservando o filtro fixo para a tabela SINAPI.
- Migra Cotacao/Mercado para a pesquisa nativa do GLPI e passa a identificar de forma persistente o preco vigente de cada material.
- Migra Materiais Cotacao para a pesquisa nativa do GLPI, exibindo somente materiais com cotacao vigente.
- Migra a listagem global de Materiais consumidos para a pesquisa nativa do GLPI, com filtros, ordenacao, configuracao de colunas e acoes em massa.
- Preserva no lancamento o rotulo consolidado do centro de custo (`codigo - nome`) para permitir pesquisa correta nas bases Antigo e Novo.
- Migra a tabela da aba Materiais consumidos do chamado para a pesquisa nativa do GLPI, mantendo o filtro interno pelo chamado aberto e o lancamento de materiais.
- Separa os catalogos Materiais SINAPI e Materiais Cotacao pelo preco vigente de cada tipo e restringe as tabelas de precos a competencia vigente, mantendo historico separado.
- Ajusta os campos e filtros da tela Cotacao/Mercado para usar a nomenclatura propria de cotacao.

## v1.1.0 - Aperfeicoamentos de operacao e interface

- Substitui o botao textual `Editar` nas tabelas de Centro de Custos Antigo e Novo por icone de lapis, mantendo acesso direto a edicao.
- Adiciona icone de lixeira e confirmacao antes da exclusao de um centro de custo nas duas tabelas.
- Disponibiliza a acao `Excluir permanentemente` tambem no formulario de edicao, com a mesma confirmacao de seguranca.
- Ajusta o campo de pesquisa e selecao de material em `Materiais consumidos` para ocupar toda a largura util do formulario, inclusive apos selecionar ou limpar um item.
- Reforca os scripts de deploy para publicar os assets CSS e as telas de centros de custo junto com as demais classes alteradas.
- Atualiza a versao declarada do plugin para renovar os assets em cache do GLPI apos a atualizacao.

## v1.0.12 - Hotfix de busca dos materiais SINAPI

- Ajusta a busca AJAX do lancamento de `Materiais consumidos` para consultar primeiro a tabela de materiais e apenas filtrar a existencia de preco do tipo selecionado.
- Reforca a pesquisa por codigo e nome do material, incluindo comparacao normalizada do codigo sem pontuacao.
- Corrige o cenario em que materiais SINAPI ja importados, como `10236`, `10416` e `12747`, podiam nao aparecer no dropdown de selecao durante o lancamento.
- Corrige o registro de assets JavaScript e CSS no `setup.php`, restaurando o carregamento do formulario de `Materiais consumidos` em ambientes onde o GLPI nao aceitava caminhos de hook com query string.
- Ajusta rotulos e mensagens com acentuacao quebrada em telas e validacoes do plugin.
- Corrige aliases e mensagens do importador para aceitar cabecalhos legados com acentuacao esperada.

## v1.0.11 - Hotfix de lancamento de materiais e cotacao

- Corrige o lancamento de `Materiais consumidos` em ambientes onde o deploy parcial nao copiava o arquivo `js/ticketmaterial-v3.js`, impedindo a abertura do formulario ao clicar em `Adicionar material`.
- Adiciona fallback no carregamento de JavaScript do plugin para usar `ticketmaterial-v2.js` ou `ticketmaterial.js` quando o asset mais novo nao estiver presente no servidor.
- Atualiza os scripts de deploy da VM e da producao para sempre publicar o `ticketmaterial-v3.js`.
- Corrige a listagem principal de `Cotacao/Mercado` para exibir apenas o preco vigente de cada material, mantendo competencias anteriores apenas em `Historico de precos`.

## v1.0.10 - Hotfix de instalacao e refinamentos de centros de custo

- Corrige a rotina de instalacao/upgrade em ambientes onde a camada `DB` nao expoe `listIndexes()`, usando `SHOW INDEX` para reparar o indice composto de `ticketcostcenters`.
- Alinha o schema instalado e o schema de migracao para permitir um centro de custo `Antigo` e um `Novo` por chamado, sem conflito de chave unica.
- Consolida o salvamento da aba `Centro de Custos` do chamado com suporte a selecoes separadas para as bases `Antigo` e `Novo`.
- Ajusta os scripts JavaScript e o carregamento versionado de assets para respeitar dropdowns vinculados e evitar divergencias entre a base escolhida e o centro de custo exibido.
- Aproxima os cabecalhos das tabelas do plugin ao padrao visual nativo do GLPI, incluindo listagens de materiais, precos, importacoes, relatorios e centros de custo.

## v1.0.9 - Ajuste do sync do FormCreator para centro de custo antigo e novo

- Corrige o preenchimento dos centros de custo no FormCreator para gravar corretamente as selecoes de Antigo e Novo quando ambas existem.
- Mantem o comportamento padrao de usar o centro de custo antigo como referencia principal quando necessario.
- Evita que uma unica selecao sobrescreva a outra no vinculo do chamado.

## v1.0.8 - Hotfix do botao Salvar em centros de custo

- Restaura a acao de `Salvar` na edicao dos registros de `Centro de Custos Novo` e `Centro de Custos Antigo`.
- Mantem o botao `Adicionar` no cadastro de novos centros de custo.
- Evita dependencia do rodape automatico do GLPI nesses dois formularios, garantindo consistencia no modo de edicao.

## v1.0.7 - Hotfix de empacotamento e publicacao segura

- Regenera a release a partir da arvore local funcional validada em homologacao, evitando arquivos truncados no pacote publicado.
- Corrige a publicacao de `setup.php`, `hook.php`, telas `front/` e classes `src/` que causavam tela branca ou falha de instalacao em ambiente Linux.
- Mantem os ajustes mais recentes de FormCreator, dropdowns de centros de custo e filtros/pesquisa nativa do plugin.
- Gera um novo pacote ZIP de instalacao pronto para atualizacao segura em producao.

## v1.0.4 - Preenchimento automatico da data no lancamento

- Preenche automaticamente a data do consumo com a data vigente ao abrir o formulario de lancamento em `Materiais consumidos`.
- Mantem a data editavel no formulario para permitir ajustes manuais antes de salvar.
- Garante fallback no backend para gravar a data atual quando o campo vier vazio.

## v1.0.3 - Correcao da integracao de centros de custo no FormCreator

- Faz os campos `Centro de Custos Antigo` e `Centro de Custos Novo` do FormCreator usarem o endpoint AJAX do proprio plugin.
- Exibe os itens no formato `codigo - nome` nas listas suspensas dos dois tipos de centro de custo.
- Permite buscar centros de custo por nome, por codigo formatado e por codigo sem pontuacao.
- Mantem compatibilidade com valores previamente selecionados no FormCreator, inclusive no carregamento de opcoes por ID.

## v1.0.2 - Ajuste de exibicao e busca dos centros de custo no FormCreator

- Ajusta o nome amigavel dos objetos `Centro de Custos Antigo` e `Centro de Custos Novo` para exibir `codigo - nome` nas listas suspensas do FormCreator.
- Permite pesquisar centros de custo pelo nome, pelo codigo com pontuacao e pelo codigo sem pontuacao nas listas suspensas baseadas em objetos GLPI.
- Mantem a alteracao concentrada na classe base de centros de custo para preservar compatibilidade entre o cadastro novo e o legado.

## v1.0.1 - Ajustes pos-consolidacao da 1.0.0

- Adiciona busca tolerante a pontuacao no dropdown de centro de custos, permitindo localizar codigos sem digitar separadores.
- Move o vinculo de centro de custo do chamado para uma aba propria `Centro de Custos`, separada da aba `Materiais Consumidos`.
- Remove o formulario de centro de custo da aba de materiais consumidos para evitar duplicidade de manutencao no chamado.
- Mantem o deploy homologado na VM com validacao funcional posterior via navegador.

## v1.0.0 - Consolidacao funcional para uso operacional

- Consolida o fluxo operacional do plugin para uso em producao, com foco em materiais consumidos, centros de custo, contratos e relatorios.
- Mantem o valor unitario congelado no momento do lancamento do material no chamado, mesmo com novas importacoes de preco.
- Separa claramente materiais e precos das tabelas SINAPI e Cotacao / Mercado.
- Expande a integracao com contratos, incluindo vinculo de custos a partir do chamado e remocao do vinculo quando necessario.
- Disponibiliza centros de custo Novo e Antigo com importacoes dedicadas, uso no chamado e suporte a filtros nativos do GLPI.
- Adiciona vinculacao direta de centro de custo ao chamado, impedindo divergencia entre o centro do chamado e o centro dos materiais consumidos.
- Exponibiliza `Centro de Custos Novo` e `Centro de Custos Antigo` para uso no FormCreator como objetos GLPI.
- Mantem paginacao e carregamento remoto nas tabelas grandes para preservar desempenho.
- Consolida a base local preparada para homologacao final via navegador antes da publicacao externa.

## v0.9.1 - Correcao do pacote para Linux

- Regenera o pacote de distribuicao com estrutura de caminhos compativel com servidores Linux.
- Mantem a pasta `maintenancecosts/` como raiz do artefato, pronta para extracao direta em `plugins/`.
- Preserva integralmente as funcionalidades da `v0.9.0`, sem mudancas funcionais adicionais no plugin.

## v0.9.0 - Centro de custo antigo, selecao no chamado e correcoes finais

- Duplica a area de centros de custo e separa os cadastros em `Centros de custo (Novo)` e `Centros de custo (Antigo)`.
- Adiciona a nova tabela legada de centros de custo com cadastro, listagem e importacao dedicados.
- Implementa o mapeamento de importacao do centro de custo antigo a partir da planilha institucional:
  - `CENTRO DE CUSTO` -> `Codigo`
  - `CAMPUS` -> `Campus`
  - `DEPARTAMENTO /DISC./SETOR` -> `Departamento/Disc./Setor`
  - `Tipo + Logradouro + no` -> `Endereco`
  - `Piso` -> `Piso`
  - `UTILIZACAO` -> `Utilizacao`
- Torna a importacao legada tolerante a linhas sem codigo, ignorando registros vazios em vez de abortar o lote inteiro.
- Permite escolher, na aba `Materiais consumidos` do chamado, se a pesquisa de centro de custo sera feita na base `Antigo` ou `Novo`, com padrao em `Antigo`.
- Remove o atalho de cadastro manual de material da tela de consumo no chamado.
- Persiste a origem do centro de custo consumido em `costcenter_source`, com compatibilidade para valores antigos como `novo` e `new`.
- Corrige a busca de `Materiais consumidos` para localizar tambem por nome/codigo de centro de custo.
- Corrige os relatorios para respeitarem a base de centro de custo usada no lancamento, evitando associacoes incorretas entre centro antigo e centro novo.
- Corrige o autofill do material no formulario de consumo para disparar corretamente ao selecionar itens via Select2.
- Corrige rotulos com encoding quebrado no cadastro de centros de custo antigos.

## v0.5.10 - Disponibilidade por entidade

- Move a habilitacao do plugin por entidade para uma aba propria dentro de `Administracao > Entidades`, evitando listas extensas na configuracao global.
- Permite definir disponibilidade por entidade com heranca opcional para entidades filhas.
- Mantem a configuracao global do plugin focada apenas em parametros operacionais, sem misturar escopo organizacional.
- Corrige a renderizacao dos controles da nova aba de entidade e o fluxo de salvamento correspondente.

## v0.5.9 - Preenchimento do valor unitario no chamado

- Corrige o carregamento de dados do material no formulario de consumo usando fallback de AJAX quando `fetch` nao estiver disponivel.
- Ao selecionar material, busca preco da competencia informada e, se nao existir, usa o ultimo preco cadastrado para o material e tipo de preco.
- Mantem preenchimento automatico de unidade, competencia, valor unitario aplicado e total na aba Materiais Consumidos.

## v0.5.8 - Correcao de dropdowns em GLPI instalado na raiz

- Corrige a montagem das URLs AJAX dos dropdowns em ambientes onde o GLPI roda na raiz do dominio, sem o prefixo `/glpi`.
- Mantem compatibilidade com instalacoes em subdiretorio, como `/glpi`, calculando o caminho base a partir do script carregado quando `CFG_GLPI.root_doc` nao estiver disponivel.
- Evita falha `Os resultados nao puderam ser carregados` nos dropdowns de material, centro de custo e contratos na aba Materiais Consumidos.

## v0.5.7 - Dropdowns no consumo do chamado

- Ajusta permissoes do endpoint AJAX de dropdowns para permitir selecao de materiais e centros de custo nos fluxos autorizados de consumo, relatorios e cadastros.
- Evita a mensagem `Os resultados nao puderam ser carregados` quando o usuario possui permissao para lancar consumo no chamado, mas nao administra diretamente o cadastro auxiliar.

## v0.5.6 - Paginacao das tabelas grandes

- Adiciona paginacao server-side em Materiais SINAPI, Materiais Cotacao, Precos SINAPI, Precos Cotacao, Centros de custo e Materiais consumidos.
- Substitui limites fixos e renderizacao massiva por contagem total, pagina atual e seletor de 20/50/100/200 linhas.
- Mantem busca, filtros de tipo de preco e ordenacao visual, reduzindo carga em tabelas com milhares de itens.

## v0.5.5 - Centros de custo institucionais e origem manual

- Adequa centros de custo ao layout da planilha institucional, com codigo, unidade gestora, unidade academica, departamento, divisao, secao, codigo SIORG, sigla SIORG, endereco e responsavel.
- Vincula Unidade gestora a localizacao GLPI de nivel 1 quando houver correspondencia de nome.
- Adiciona importacao XLSX/CSV de centros de custo com reconhecimento de cabecalhos acentuados em maiusculas.
- Remove a criacao automatica de origens do material; origens passam a ser somente cadastros manuais.
- Remove origens padrao legadas sem uso durante upgrade idempotente.
- Mantem a coluna `Acoes` sempre visivel, nao ocultavel e nao ordenavel nas tabelas customizaveis do plugin.
- Atualiza exportacao CSV/PDF de centros de custo para seguir a nova estrutura.

## v0.5.4 - Compatibilidade de instalacao no GLPI 10.0.24

- Corrige a instalacao/habilitacao pela interface em ambientes GLPI 10.0.24, usando a classe global `\QueryExpression` em vez do namespace `Glpi\DBAL\QueryExpression`.
- Mantem a logica de direitos de perfil inalterada; a mudanca e restrita a compatibilidade da API DBAL do GLPI 10.0.x.

## v0.5.3 - Cotacao Mercado e custos do chamado

- Separa Cotacao/Mercado em tab propria, com acoes de adicionar preco, importar e consultar historico no mesmo contexto.
- Adequa Cotacao/Mercado ao layout da planilha de cotacoes com quantidade, valor aplicado e tres cotacoes comparativas.
- Adiciona a tab Materiais Cotacao abaixo de Materiais SINAPI, listando materiais com precos de cotacao/mercado.
- Ajusta os fluxos `Adicionar preco cotacao` e `Importar Cotacao` para exibirem labels, campos e orientacoes proprias de cotacao.
- Preenche a competencia do lancamento de material com a ultima competencia cadastrada, mantendo edicao manual.
- Sincroniza materiais consumidos com a aba nativa `Chamado > Custos` por meio de `TicketCost` idempotente.

## v0.5.2 - Icone, campus e correcoes visuais

- Ajusta o cadastro de centros de custo para usar Campus como localizacao GLPI de nivel 1.
- Corrige nomes e acentuacao visivel nas telas de centro de custo e configuracao.
- Adiciona metadados e arquivos de icone/logotipo do plugin para empacotamento.
- Exibe o icone do plugin no card de Plug-ins instalados do GLPI quando o plugin foi instalado localmente.

## v0.5.1 - Performance, importacoes e relatorios

- Otimiza dropdowns grandes de materiais, centros de custo e contratos com carregamento remoto paginado.
- Move o historico de importacoes para a tela de Importar SINAPI e adiciona `Importar Cotacao`.
- Permite ordenar colunas nas visoes pessoal/global das tabelas do plugin por arrastar e soltar.
- Cria o vinculo chamado-contrato ao selecionar contrato no lancamento de material.
- Melhora exportacao PDF de relatorios com resumo, grafico e tabela em layout visual.

## v0.5.0 - Evolucao de centros, precos e relatorios

- Reorganiza centros de custo com codigo, nome, endereco, piso, campus, departamento/disciplina/setor e utilizacao.
- Adiciona importacao CSV/XLSX de centros de custo com pre-validacao.
- Move importacoes SINAPI para a area de Precos SINAPI.
- Adiciona fluxo de precos por cotacao/mercado e filtro por tipo de preco.
- Adiciona unidade e historico por item em Precos SINAPI.
- Reestrutura relatorios para exibir uma visao por vez, com graficos configuraveis.
- Adiciona relatorios por origem do material, tipo de preco e contrato.

