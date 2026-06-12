<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\PriceHistory;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_PRICES, READ);

global $DB;

$materials_id = (int) ($_GET['materials_id'] ?? 0);

Html::header(__('Histórico de preços', 'maintenancecosts'), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('prices');

echo "<div class='spaced'>";
echo "<form method='get' action='" . Html::clean($_SERVER['PHP_SELF']) . "'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Histórico de preços', 'maintenancecosts') . "</th></tr>";
echo "<tr class='tab_bg_1'><td>" . Material::getTypeName(1) . "</td><td>";
echo "<select name='materials_id' class='form-control plugin-maintenancecosts-dropdown'>";
echo "<option value='0'>-----</option>";
foreach ($DB->request(['SELECT' => ['id', 'code', 'name', 'unit'], 'FROM' => Material::getTable(), 'WHERE' => ['is_active' => 1], 'ORDER' => ['code ASC', 'name ASC']]) as $materialRow) {
   $label = trim((string) $materialRow['code']) !== ''
      ? $materialRow['code'] . ' - ' . $materialRow['name']
      : $materialRow['name'];
   if (trim((string) ($materialRow['unit'] ?? '')) !== '') {
      $label .= ' (' . $materialRow['unit'] . ')';
   }
   echo "<option value='" . (int) $materialRow['id'] . "' " . ((int) $materialRow['id'] === $materials_id ? 'selected' : '') . ">" . Html::clean($label) . "</option>";
}
echo "</select> ";
echo Html::submit(__('Filtrar', 'maintenancecosts'), ['class' => 'btn btn-primary']);
echo "</td></tr></table>";
Html::closeForm();
echo "</div>";

$where = [];
if ($materials_id > 0) {
   $where['plugin_maintenancecosts_materials_id'] = $materials_id;
}

echo "<div class='center mb-3'>";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&materials_id=' . $materials_id)) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&format=pdf&materials_id=' . $materials_id)) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

echo "<div class='spaced'><table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr>";
echo "<th data-sort='text'>" . Material::getTypeName(1) . "</th>";
echo "<th data-sort='text'>" . __('Competência', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Tipo de preço', 'maintenancecosts') . "</th>";
echo "<th data-sort='currency'>" . __('Valor anterior', 'maintenancecosts') . "</th>";
echo "<th data-sort='currency'>" . __('Valor novo', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Origem', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Usuário', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Justificativa', 'maintenancecosts') . "</th>";
echo "<th data-sort='text'>" . __('Data', 'maintenancecosts') . "</th>";
echo "</tr></thead><tbody>";

$criteria = [
   'FROM'  => PriceHistory::getTable(),
   'ORDER' => ['date_creation DESC', 'id DESC'],
   'LIMIT' => 300,
];
if (count($where)) {
   $criteria['WHERE'] = $where;
}

foreach ($DB->request($criteria) as $row) {
   $material = new Material();
   $materialName = $material->getFromDB((int) $row['plugin_maintenancecosts_materials_id']) ? $material->getName() : '';
   echo "<tr class='tab_bg_1'>";
   echo "<td class='text-start'>" . Html::clean($materialName) . "</td>";
   echo "<td class='center'>" . Html::clean($row['competence']) . "</td>";
   echo "<td class='center'>" . Html::clean(Config::getPriceTypeLabel((string) $row['price_type'])) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) $row['old_unit_price']) . "'>" . Config::formatCurrency((float) $row['old_unit_price']) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) $row['new_unit_price']) . "'>" . Config::formatCurrency((float) $row['new_unit_price']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['source']) . "</td>";
   echo "<td class='center'>" . getUserName((int) $row['users_id']) . "</td>";
   echo "<td class='text-start'>" . Html::clean($row['justification'] ?? '') . "</td>";
   echo "<td class='center'>" . Html::clean($row['date_creation'] ?? '') . "</td>";
   echo "</tr>";
}

echo "</tbody></table></div>";
Config::renderPluginLayoutEnd();
Html::footer();
