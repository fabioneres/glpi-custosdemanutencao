<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\Price;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_MATERIALS, READ);

global $DB;

$search = trim((string) ($_GET['q'] ?? ''));
$priceTable = Price::getTable();
$materialTable = Material::getTable();

$where = [$priceTable . '.price_type' => 'cotacao_mercado'];
if ($search !== '') {
   $where[] = [
      'OR' => [
         [$materialTable . '.name' => ['LIKE', '%' . $search . '%']],
         [$materialTable . '.code' => ['LIKE', '%' . $search . '%']],
         [$materialTable . '.unit' => ['LIKE', '%' . $search . '%']],
         [$materialTable . '.category' => ['LIKE', '%' . $search . '%']],
         [$priceTable . '.competence' => ['LIKE', '%' . $search . '%']],
         [$priceTable . '.source' => ['LIKE', '%' . $search . '%']],
      ],
   ];
}

$criteria = [
   'SELECT' => [
      $priceTable . '.*',
      $materialTable . '.id AS material_id',
      $materialTable . '.code AS material_code',
      $materialTable . '.name AS material_name',
      $materialTable . '.unit AS material_unit',
      $materialTable . '.is_active AS material_active',
   ],
   'FROM' => $priceTable,
   'LEFT JOIN' => [
      $materialTable => [
         'FKEY' => [$priceTable => 'plugin_maintenancecosts_materials_id', $materialTable => 'id'],
      ],
   ],
   'WHERE' => $where,
   'ORDER' => [$priceTable . '.competence DESC', $priceTable . '.id DESC'],
   'LIMIT' => 1000,
];

$materials = [];
foreach ($DB->request($criteria) as $row) {
   $materialId = (int) ($row['material_id'] ?? 0);
   if ($materialId <= 0 || isset($materials[$materialId])) {
      continue;
   }
   $materials[$materialId] = $row;
}

$formatNumber = static function(float $value): string {
   return abs($value - round($value)) < 0.000001
      ? (string) (int) round($value)
      : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
};

Html::header(__('Materiais Cotação', 'maintenancecosts'), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('quote_materials');

echo "<div class='center mb-3'>";
if (Config::canManageMaterials()) {
   echo "<a class='btn btn-primary' href='" . Html::clean(Material::getFormURL() . '?context=quote') . "'>" . Html::clean(__('Adicionar material cotação', 'maintenancecosts')) . "</a> ";
}
if (Config::canImport()) {
   echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=cotacao_mercado')) . "'>" . Html::clean(__('Importar Cotação', 'maintenancecosts')) . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/quotationprice.php')) . "'>" . Html::clean(__('Cotação/Mercado', 'maintenancecosts')) . "</a>";
echo "</div>";

echo "<div class='plugin-maintenancecosts-panel mb-3'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> " . Html::clean(__('Materiais Cotação', 'maintenancecosts')) . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
echo "<p>" . Html::clean(__('Esta aba mostra os materiais que possuem preços cadastrados por cotação ou pesquisa de mercado. A tabela usa o preço mais recente de cada material para facilitar a conferência operacional.', 'maintenancecosts')) . "</p>";
echo "</div></div>";

echo "<form method='get' class='mb-3'>";
echo "<div class='d-flex gap-2'>";
echo "<input type='text' name='q' value='" . Html::cleanInputText($search) . "' class='form-control' placeholder='" . Html::clean(__('Pesquisar por código, nome, unidade, competência ou origem', 'maintenancecosts')) . "'>";
echo "<button class='btn btn-primary' type='submit'>" . Html::clean(__('Pesquisar', 'maintenancecosts')) . "</button>";
echo "</div></form>";

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr>";
echo "<th data-sort='text'>" . Html::clean(__('Código', 'maintenancecosts')) . "</th>";
echo "<th data-sort='text'>" . Html::clean(__('Nome', 'maintenancecosts')) . "</th>";
echo "<th data-sort='text'>" . Html::clean(__('Unidade', 'maintenancecosts')) . "</th>";
echo "<th data-sort='number'>" . Html::clean(__('Quantidade', 'maintenancecosts')) . "</th>";
echo "<th data-sort='currency'>" . Html::clean(__('Valor', 'maintenancecosts')) . "</th>";
echo "<th data-sort='currency'>" . Html::clean(__('Cotação 1', 'maintenancecosts')) . "</th>";
echo "<th data-sort='currency'>" . Html::clean(__('Cotação 2', 'maintenancecosts')) . "</th>";
echo "<th data-sort='currency'>" . Html::clean(__('Cotação 3', 'maintenancecosts')) . "</th>";
echo "<th data-sort='text'>" . Html::clean(__('Última competência', 'maintenancecosts')) . "</th>";
echo "<th data-sort='text'>" . Html::clean(__('Ativo', 'maintenancecosts')) . "</th>";
echo "<th>" . Html::clean(__('Ações', 'maintenancecosts')) . "</th>";
echo "</tr></thead><tbody>";

foreach ($materials as $row) {
   $code = (string) ($row['material_code'] ?? '');
   echo "<tr class='tab_bg_1'>";
   echo "<td class='center'>" . Html::clean($code) . "</td>";
   echo "<td class='text-start' style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($row['material_name'] ?? '') . "</td>";
   echo "<td class='center'>" . Html::clean($row['material_unit'] ?? '') . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_quantity'] ?? 0)) . "'>" . Html::clean($formatNumber((float) ($row['quote_quantity'] ?? 0))) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['unit_price'] ?? 0)) . "'>" . Html::clean(Config::formatCurrency((float) ($row['unit_price'] ?? 0))) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_price_1'] ?? 0)) . "'>" . Html::clean(Config::formatCurrency((float) ($row['quote_price_1'] ?? 0))) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_price_2'] ?? 0)) . "'>" . Html::clean(Config::formatCurrency((float) ($row['quote_price_2'] ?? 0))) . "</td>";
   echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_price_3'] ?? 0)) . "'>" . Html::clean(Config::formatCurrency((float) ($row['quote_price_3'] ?? 0))) . "</td>";
   echo "<td class='center'>" . Html::clean($row['competence'] ?? '') . "</td>";
   echo "<td class='center'>" . ((int) ($row['material_active'] ?? 0) ? Html::clean(__('Yes')) : Html::clean(__('No'))) . "</td>";
   echo "<td class='center'>";
   echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(Material::getFormURL() . '?id=' . (int) $row['material_id'] . '&context=quote') . "'>" . Html::clean(__('Editar', 'maintenancecosts')) . "</a> ";
   echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php?materials_id=' . (int) $row['material_id'] . '&price_type=cotacao_mercado')) . "'>" . Html::clean(__('Histórico', 'maintenancecosts')) . "</a>";
   echo "</td>";
   echo "</tr>";
}

if (count($materials) === 0) {
   echo "<tr class='tab_bg_1'><td colspan='11' class='center'>" . Html::clean(__('Nenhum material de cotação encontrado.', 'maintenancecosts')) . "</td></tr>";
}

echo "</tbody></table>";
Config::renderPluginLayoutEnd();
Html::footer();
