<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Session::checkLoginUser();

Html::header(__('Sobre', 'maintenancecosts') . ' - ' . Config::getTypeName(), $_SERVER['PHP_SELF'], 'plugins', Menu::class);

Config::renderPluginLayoutStart('about');

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> " . __('Como funciona', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<div class='plugin-maintenancecosts-section-title'>" . __('Problema que resolve', 'maintenancecosts') . "</div>";
echo "<p>" . __('O plugin centraliza o custo de materiais usados em chamados de manutenção, vinculando cada lançamento ao material, centro de custo, origem do preço, competência, técnico e chamado.', 'maintenancecosts') . "</p>";
echo "<div class='plugin-maintenancecosts-compare'>";
echo "<div class='plugin-maintenancecosts-warn'><strong>" . __('Sem Custos de Manutenção', 'maintenancecosts') . "</strong><ul>";
echo "<li>" . __('Custos ficam espalhados em comentários ou planilhas.', 'maintenancecosts') . "</li>";
echo "<li>" . __('Atualizações de preço podem confundir valores antigos.', 'maintenancecosts') . "</li>";
echo "<li>" . __('Relatórios por centro de custo e contrato exigem consolidação manual.', 'maintenancecosts') . "</li>";
echo "</ul></div>";
echo "<div class='plugin-maintenancecosts-good'><strong>" . __('Com Custos de Manutenção', 'maintenancecosts') . "</strong><ul>";
echo "<li>" . __('O valor aplicado fica congelado no chamado.', 'maintenancecosts') . "</li>";
echo "<li>" . __('Importações e alterações de preço geram histórico.', 'maintenancecosts') . "</li>";
echo "<li>" . __('Relatórios consolidam custos por chamado, contrato, origem, material e centro de custo.', 'maintenancecosts') . "</li>";
echo "</ul></div>";
echo "</div>";
echo "</div></div>";

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-list-check'></i> " . __('Fluxo operacional', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<table class='tab_cadre_fixe plugin-maintenancecosts-table'>";
echo "<tr><th>#</th><th>" . __('Etapa', 'maintenancecosts') . "</th><th>" . __('O que acontece', 'maintenancecosts') . "</th></tr>";
foreach ([
   [1, __('Configuração', 'maintenancecosts'), __('Habilite o plugin, defina se centro de custo é obrigatório e revise permissões por perfil.', 'maintenancecosts')],
   [2, __('Centros de custo', 'maintenancecosts'), __('Cadastre ou importe centros de custo com código, nome, endereço, entidade e status ativo.', 'maintenancecosts')],
   [3, __('Materiais e preços', 'maintenancecosts'), __('Importe SINAPI ou cadastre manualmente materiais e preços de cotação/mercado.', 'maintenancecosts')],
   [4, __('Lançamento no chamado', 'maintenancecosts'), __('Na aba Materiais Consumidos, selecione material, origem, tipo de preço, centro de custo e quantidade.', 'maintenancecosts')],
   [5, __('Auditoria', 'maintenancecosts'), __('O preço aplicado, competência, usuário e total ficam gravados no item consumido.', 'maintenancecosts')],
   [6, __('Relatórios', 'maintenancecosts'), __('Acompanhe custos por contrato, origem do preço, material, centro de custo, categoria e mês.', 'maintenancecosts')],
] as $row) {
   echo "<tr class='tab_bg_1'><td class='center'><span class='badge bg-blue'>" . (int) $row[0] . "</span></td><td>" . Html::clean($row[1]) . "</td><td class='text-start'>" . Html::clean($row[2]) . "</td></tr>";
}
echo "</table>";
echo "</div></div>";

echo "<div class='plugin-maintenancecosts-grid'>";
foreach ([
   ['ti ti-lock-dollar', __('Congelamento do valor aplicado', 'maintenancecosts'), __('Ao adicionar material no chamado, o valor unitário aplicado é gravado no lançamento. Novas importações SINAPI ou alterações posteriores de preço não alteram itens já registrados.', 'maintenancecosts')],
   ['ti ti-clock-dollar', __('Histórico de preços', 'maintenancecosts'), __('Cada preço novo ou alterado registra material, competência, tipo de preço, valor anterior, valor novo, usuário, origem e justificativa.', 'maintenancecosts')],
   ['ti ti-file-import', __('Importação SINAPI', 'maintenancecosts'), __('O material é identificado pelo código SINAPI. Reimportar outra competência não duplica o material; cria ou atualiza o preço vigente da competência e mantém histórico.', 'maintenancecosts')],
   ['ti ti-receipt', __('Cotação/Mercado', 'maintenancecosts'), __('Quando o item não existir na tabela SINAPI, cadastre o material manualmente e registre o preço como cotação/mercado.', 'maintenancecosts')],
   ['ti ti-file-certificate', __('Contratos', 'maintenancecosts'), __('Se o chamado estiver vinculado a contrato, o consumo cria ou atualiza custo no contrato nativo do GLPI, preservando rastreabilidade.', 'maintenancecosts')],
   ['ti ti-chart-bar', __('Relatórios', 'maintenancecosts'), __('Os relatórios permitem analisar valores por origem de preço, contrato, centro de custo, categoria, localização, material e evolução mensal.', 'maintenancecosts')],
] as $card) {
   echo "<div class='plugin-maintenancecosts-panel'>";
   echo "<div class='plugin-maintenancecosts-panel-header'><i class='" . Html::clean($card[0]) . "'></i> " . Html::clean($card[1]) . "</div>";
   echo "<div class='plugin-maintenancecosts-panel-body'>" . Html::clean($card[2]) . "</div>";
   echo "</div>";
}
echo "</div>";

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-help-circle'></i> " . __('Perguntas frequentes', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<p><strong>" . __('Atualizar SINAPI muda chamados antigos?', 'maintenancecosts') . "</strong><br>" . __('Não. O valor do chamado fica congelado no lançamento.', 'maintenancecosts') . "</p>";
echo "<p><strong>" . __('Posso usar preço fora da SINAPI?', 'maintenancecosts') . "</strong><br>" . __('Sim. Use o tipo de preço Cotação/Mercado e registre origem e justificativa.', 'maintenancecosts') . "</p>";
echo "<p><strong>" . __('O que preciso conferir antes de lançar consumo?', 'maintenancecosts') . "</strong><br>" . __('Material, origem do material, tipo de preço, competência, quantidade, unidade, valor aplicado e centro de custo.', 'maintenancecosts') . "</p>";
echo "<p><strong>" . __('Onde vejo a auditoria de preço?', 'maintenancecosts') . "</strong><br>" . __('Use a tela Histórico de preços para consultar mudanças por material.', 'maintenancecosts') . "</p>";
echo "</div></div>";

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-id'></i> " . __('Informações da versão', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<table class='tab_cadre_fixe plugin-maintenancecosts-table'>";
echo "<tr><th>" . __('Status', 'maintenancecosts') . "</th><td><span class='badge bg-green'>" . __('Ativo', 'maintenancecosts') . "</span></td></tr>";
echo "<tr><th>" . __('Nome', 'maintenancecosts') . "</th><td>" . Html::clean(Config::getTypeName()) . "</td></tr>";
echo "<tr><th>" . __('Versão', 'maintenancecosts') . "</th><td>" . Html::clean(PLUGIN_MAINTENANCECOSTS_VERSION) . "</td></tr>";
echo "<tr><th>" . __('Autor', 'maintenancecosts') . "</th><td>Fabio Neres</td></tr>";
echo "<tr><th>" . __('Escopo', 'maintenancecosts') . "</th><td>" . __('Custos de materiais em chamados de manutenção no GLPI.', 'maintenancecosts') . "</td></tr>";
echo "<tr><th>" . __('Fora do escopo atual', 'maintenancecosts') . "</th><td>" . __('Estoque, compras, nota fiscal, empenho, aprovação de consumo e integrações financeiras.', 'maintenancecosts') . "</td></tr>";
echo "</table>";
echo "</div></div>";

Config::renderPluginLayoutEnd();
Html::footer();
