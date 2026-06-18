<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\CostCenter;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_CONFIG, UPDATE);

if (isset($_POST['save'])) {
   Config::saveSettings($_POST);
   Session::addMessageAfterRedirect(__('Configurações salvas.', 'maintenancecosts'), false, INFO);
   Html::redirect($_SERVER['PHP_SELF']);
}

$settings = Config::getSettings();

Html::header(Config::getTypeName(), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('config');

echo "<form method='post' action='" . Html::clean($_SERVER['PHP_SELF']) . "'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-settings'></i> " . __('Configuração - Custos de Manutenção', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";

echo "<div class='plugin-maintenancecosts-grid'>";

echo "<div class='plugin-maintenancecosts-option'>";
echo "<div class='plugin-maintenancecosts-option-title'><i class='ti ti-building-bank'></i> " . __('Centro de custo obrigatório', 'maintenancecosts') . "</div>";
echo "<input type='hidden' name='costcenter_required' value='0'>";
Dropdown::showYesNo('costcenter_required', (int) $settings['costcenter_required']);
echo "<div class='plugin-maintenancecosts-help'>" . __('Exige centro de custo ao registrar material consumido em chamado.', 'maintenancecosts') . "</div>";
echo "</div>";

echo "<div class='plugin-maintenancecosts-option'>";
echo "<div class='plugin-maintenancecosts-option-title'><i class='ti ti-edit'></i> " . __('Edição manual do valor unitário', 'maintenancecosts') . "</div>";
echo "<input type='hidden' name='allow_manual_unit_price' value='0'>";
Dropdown::showYesNo('allow_manual_unit_price', (int) $settings['allow_manual_unit_price']);
echo "<div class='plugin-maintenancecosts-help'>" . __('Permite exceções quando o preço usado vier de cotação ou ajuste autorizado.', 'maintenancecosts') . "</div>";
echo "</div>";

echo "<div class='plugin-maintenancecosts-option'>";
echo "<div class='plugin-maintenancecosts-option-title'><i class='ti ti-calendar-dollar'></i> " . __('Competência padrão de preço', 'maintenancecosts') . "</div>";
echo "<select name='default_competence_mode' class='form-select'>";
foreach ([
   'latest'           => __('Última competência disponível', 'maintenancecosts'),
   'ticket_date'      => __('Conforme data do chamado', 'maintenancecosts'),
   'consumption_date' => __('Conforme data do consumo', 'maintenancecosts'),
] as $value => $label) {
   echo "<option value='" . Html::clean($value) . "' " . ($settings['default_competence_mode'] === $value ? 'selected' : '') . ">" . Html::clean($label) . "</option>";
}
echo "</select>";
echo "<div class='plugin-maintenancecosts-help'>" . __('Define qual competência será sugerida ao selecionar um material.', 'maintenancecosts') . "</div>";
echo "</div>";

echo "</div>";

echo "<div class='plugin-maintenancecosts-section-title'>" . __('Categorias ITIL permitidas', 'maintenancecosts') . "</div>";
echo "<input type='text' name='allowed_itilcategories' value='" . Html::cleanInputText($settings['allowed_itilcategories']) . "' class='form-control' placeholder='" . Html::clean(__('IDs separados por vírgula; vazio permite todas', 'maintenancecosts')) . "'>";
echo "<div class='plugin-maintenancecosts-help'>" . __('Use este campo somente quando o consumo de materiais deve ficar restrito a categorias específicas de chamados.', 'maintenancecosts') . "</div>";

echo "<div class='mt-3'>";
echo Html::submit(__('Salvar configurações', 'maintenancecosts'), ['name' => 'save', 'class' => 'btn btn-primary']);
echo "</div>";
echo "</div></div>";

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-route'></i> " . __('Fluxo operacional recomendado', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<div class='plugin-maintenancecosts-grid'>";
foreach ([
   ['ti ti-file-import', __('Importar SINAPI', 'maintenancecosts'), __('Em Preços SINAPI, importe uma competência por vez. Reimportações atualizam o preço vigente e registram histórico.', 'maintenancecosts')],
   ['ti ti-building-bank', __('Importar centros de custo', 'maintenancecosts'), __('Mantenha código, nome, endereço, piso, campus, departamento/setor e utilização conforme a planilha institucional.', 'maintenancecosts')],
   ['ti ti-clipboard-list', __('Lançar consumo no chamado', 'maintenancecosts'), __('O valor aplicado fica congelado no item consumido e não muda com novas importações.', 'maintenancecosts')],
   ['ti ti-report-analytics', __('Acompanhar relatórios', 'maintenancecosts'), __('Escolha uma visão de relatório por vez para analisar custo por contrato, origem, material ou centro de custo.', 'maintenancecosts')],
] as $item) {
   echo "<div class='plugin-maintenancecosts-option'>";
   echo "<div class='plugin-maintenancecosts-option-title'><i class='" . Html::clean($item[0]) . "'></i> " . Html::clean($item[1]) . "</div>";
   echo "<div class='plugin-maintenancecosts-help'>" . Html::clean($item[2]) . "</div>";
   echo "</div>";
}
echo "</div></div></div>";

echo "<div class='plugin-maintenancecosts-panel'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> " . __('Materiais SINAPI x Preços SINAPI', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<p><strong>" . __('Materiais SINAPI', 'maintenancecosts') . ":</strong> " . __('cadastro do item em si: código, nome, unidade, categoria e status.', 'maintenancecosts') . "</p>";
echo "<p><strong>" . __('Preços SINAPI', 'maintenancecosts') . ":</strong> " . __('valores do material por competência e tipo de preço. Um mesmo material pode ter vários preços ao longo dos meses, incluindo SINAPI e cotação de mercado.', 'maintenancecosts') . "</p>";
echo "</div></div>";

Html::closeForm();
Config::renderPluginLayoutEnd();

Html::footer();
