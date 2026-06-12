<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\Price;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_PRICES, READ);

global $DB;

$search = trim((string) ($_GET['q'] ?? ''));
$priceType = Config::normalizePriceType((string) ($_GET['price_type'] ?? 'sinapi'));
$showAllTypes = isset($_GET['price_type']) && (string) $_GET['price_type'] === 'all';

$where = [];
if (!$showAllTypes) {
   $where[Price::getTable() . '.price_type'] = $priceType;
}
if ($search !== '') {
   $where[] = [
      'OR' => [
         [Material::getTable() . '.name' => ['LIKE', '%' . $search . '%']],
         [Material::getTable() . '.code' => ['LIKE', '%' . $search . '%']],
         [Material::getTable() . '.unit' => ['LIKE', '%' . $search . '%']],
         [Price::getTable() . '.competence' => ['LIKE', '%' . $search . '%']],
         [Price::getTable() . '.source' => ['LIKE', '%' . $search . '%']],
      ],
   ];
}

$criteria = [
   'SELECT' => [
      Price::getTable() . '.*',
      Material::getTable() . '.code AS material_code',
      Material::getTable() . '.name AS material_name',
      Material::getTable() . '.unit AS material_unit',
   ],
   'FROM' => Price::getTable(),
   'LEFT JOIN' => [
      Material::getTable() => [
         'FKEY' => [Price::getTable() => 'plugin_maintenancecosts_materials_id', Material::getTable() => 'id'],
      ],
   ],
   'ORDER' => [Price::getTable() . '.competence DESC', Price::getTable() . '.id DESC'],
   'LIMIT' => 300,
];
if (count($where)) {
   $criteria['WHERE'] = $where;
}

Html::header(Price::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('prices');

echo "<div class='center mb-3'>";
if (Config::canManagePrices()) {
   echo "<a class='btn btn-primary' href='" . Html::clean(Price::getFormURL() . '?price_type=sinapi') . "'>" . __('Adicionar preço SINAPI', 'maintenancecosts') . "</a> ";
   echo "<a class='btn btn-primary' href='" . Html::clean(Price::getFormURL() . '?price_type=cotacao_mercado') . "'>" . __('Adicionar preço cotação', 'maintenancecosts') . "</a> ";
   echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=sinapi')) . "'>" . __('Importar SINAPI', 'maintenancecosts') . "</a> ";
   echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=cotacao_mercado')) . "'>" . __('Importar Cotação', 'maintenancecosts') . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php')) . "'>" . __('Histórico de preços', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&price_type=' . ($showAllTypes ? 'all' : $priceType))) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&format=pdf&price_type=' . ($showAllTypes ? 'all' : $priceType))) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

echo "<div class='plugin-maintenancecosts-panel mb-3'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> " . __('Materiais SINAPI x Preços SINAPI', 'maintenancecosts') . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<p><strong>" . Material::getTypeName(2) . ":</strong> " . __('cadastro do item: código, nome, unidade e categoria.', 'maintenancecosts') . "</p>";
echo "<p><strong>" . Price::getTypeName(2) . ":</strong> " . __('valores por competência. Aqui ficam os preços SINAPI importados e os preços por cotação de mercado cadastrados manualmente.', 'maintenancecosts') . "</p>";
echo "</div></div>";

echo "<form method='get' class='mb-3'>";
echo "<div class='d-flex gap-2 flex-wrap'>";
echo "<select name='price_type' class='form-select' style='max-width:220px'>";
foreach (['sinapi' => __('Somente SINAPI', 'maintenancecosts'), 'cotacao_mercado' => __('Somente cotação', 'maintenancecosts'), 'all' => __('Todos os tipos', 'maintenancecosts')] as $value => $label) {
   $selected = ($showAllTypes && $value === 'all') || (!$showAllTypes && $value === $priceType) ? ' selected' : '';
   echo "<option value='" . Html::clean($value) . "'{$selected}>" . Html::clean($label) . "</option>";
}
echo "</select>";
echo "<input type='text' name='q' value='" . Html::cleanInputText($search) . "' class='form-control' placeholder='" . Html::clean(__('Pesquisar por material, código, unidade, competência ou origem', 'maintenancecosts')) . "'>";
echo "<button class='btn btn-primary' type='submit'>" . __('Pesquisar', 'maintenancecosts') . "</button>";
echo "</div></form>";

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr>";
echo "<th data-sort='text'>" . __('Código SINAPI', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . Material::getTypeName(1) . "</th>";
echo "<th data-sort='text'>" . __('Unidade', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Competência', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Tipo de preço', 'maintenancecosts') . "</th>";
echo "<th data-sort='currency'>" . __('Valor unitário', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Origem', 'maintenancecosts') . "</th>";
echo "<th>" . __('Ações', 'maintenancecosts') . "</th>";
echo "</tr></thead><tbody>";

$iterator = $DB->request($criteria);
foreach ($iterator as $row) {
   $code = (string) ($row['material_code'] ?? '');
   $sortCode = preg_match('/^\d+$/', $code) ? str_pad($code, 8, '0', STR_PAD_LEFT) : $code;
   echo "<tr class='tab_bg_1'>";
   echo "<td class='center' data-value='" . Html::clean($sortCode) . "'>" . Html::clean($code) . "</td>";
   echo "<td style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($row['material_name'] ?? '') . "</td>";
   echo "<td class='center'>" . Html::clean($row['material_unit'] ?? '') . "</td>";
   echo "<td class='center'>" . Html::clean($row['competence']) . "</td>";
   echo "<td class='center'>" . Html::clean(Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi'))) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) $row['unit_price']) . "'>" . Config::formatCurrency((float) $row['unit_price']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['source']) . "</td>";
   echo "<td class='center'>";
   echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(Price::getFormURL() . '?id=' . (int) $row['id']) . "'>" . __('Editar', 'maintenancecosts') . "</a> ";
   echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php?materials_id=' . (int) $row['plugin_maintenancecosts_materials_id'])) . "'>" . __('Histórico', 'maintenancecosts') . "</a>";
   echo "</td>";
   echo "</tr>";
}

if ($iterator->count() === 0) {
   echo "<tr class='tab_bg_1'><td colspan='8' class='center'>" . __('Nenhum preço encontrado.', 'maintenancecosts') . "</td></tr>";
}

echo "</tbody></table>";
Config::renderPluginLayoutEnd();
Html::footer();
