<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\Pager;
use GlpiPlugin\Maintenancecosts\Price;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_PRICES, READ);

global $DB;

$search = trim((string) ($_GET['q'] ?? ''));
$page = Pager::page();
$perPage = Pager::perPage();
$priceType = Config::normalizePriceType((string) ($_GET['price_type'] ?? 'sinapi'));
$isQuote = $priceType === 'cotacao_mercado';
$activeTab = $isQuote ? 'quotes' : 'prices';
$pageTitle = $isQuote ? __('Cotação/Mercado', 'maintenancecosts') : Price::getTypeName(Session::getPluralNumber());
$quoteNumber = static function(float $value): string {
   return abs($value - round($value)) < 0.000001
      ? (string) (int) round($value)
      : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
};

$where = [Price::getTable() . '.price_type' => $priceType];
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

$countCriteria = [
   'COUNT' => 'cpt',
   'FROM' => Price::getTable(),
   'LEFT JOIN' => [
      Material::getTable() => [
         'FKEY' => [Price::getTable() => 'plugin_maintenancecosts_materials_id', Material::getTable() => 'id'],
      ],
   ],
   'WHERE' => $where,
];
$countRow = $DB->request($countCriteria)->current();
$totalRows = (int) ($countRow['cpt'] ?? 0);
$start = Pager::start($page, $perPage, $totalRows);

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
   'WHERE' => $where,
   'ORDER' => [Price::getTable() . '.competence DESC', Price::getTable() . '.id DESC'],
   'START' => $start,
   'LIMIT' => $perPage,
];

Html::header($pageTitle, $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart($activeTab);

echo "<div class='center mb-3'>";
if (Config::canManagePrices()) {
   echo "<a class='btn btn-primary' href='" . Html::clean(Price::getFormURL() . '?price_type=' . $priceType) . "'>"
      . Html::clean($isQuote ? __('Adicionar preço cotação', 'maintenancecosts') : __('Adicionar preço SINAPI', 'maintenancecosts')) . "</a> ";
   echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=' . $priceType)) . "'>"
      . Html::clean($isQuote ? __('Importar Cotação', 'maintenancecosts') : __('Importar SINAPI', 'maintenancecosts')) . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php?price_type=' . $priceType)) . "'>" . __('Histórico de preços', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&price_type=' . $priceType)) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&format=pdf&price_type=' . $priceType)) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

echo "<div class='plugin-maintenancecosts-panel mb-3'>";
echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> "
   . Html::clean($isQuote ? __('Cotação/Mercado', 'maintenancecosts') : __('Materiais SINAPI x Preços SINAPI', 'maintenancecosts')) . "</div>";
echo "<div class='plugin-maintenancecosts-panel-body'>";
if ($isQuote) {
   echo "<p><strong>" . __('Cotação/Mercado', 'maintenancecosts') . ":</strong> "
      . __('tabela de preços obtidos por cotação ou pesquisa de mercado. O campo Valor é o preço aplicado, e Cotação 1, Cotação 2 e Cotação 3 guardam as propostas comparadas.', 'maintenancecosts') . "</p>";
} else {
   echo "<p><strong>" . Material::getTypeName(2) . ":</strong> " . __('cadastro do item: código, nome, unidade e categoria.', 'maintenancecosts') . "</p>";
   echo "<p><strong>" . Price::getTypeName(2) . ":</strong> "
      . __('valores SINAPI por competência. Reimportações atualizam o preço vigente e registram histórico sem alterar lançamentos já gravados no chamado.', 'maintenancecosts') . "</p>";
}
echo "</div></div>";

echo "<form method='get' class='mb-3'>";
echo "<div class='d-flex gap-2 flex-wrap'>";
echo Html::hidden('price_type', ['value' => $priceType]);
echo Html::hidden('per_page', ['value' => $perPage]);
echo "<input type='text' name='q' value='" . Html::cleanInputText($search) . "' class='form-control' placeholder='"
   . Html::clean(__('Pesquisar por material, código, unidade, competência ou origem', 'maintenancecosts')) . "'>";
echo "<button class='btn btn-primary' type='submit'>" . __('Pesquisar', 'maintenancecosts') . "</button>";
echo "</div></form>";

$pagerParams = ['q' => $search, 'price_type' => $priceType];
Pager::render($totalRows, $page, $perPage, $pagerParams);

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr>";
echo "<th data-sort='text'>" . Html::clean($isQuote ? __('Código', 'maintenancecosts') : __('Código SINAPI', 'maintenancecosts')) . "</th>";
echo "<th data-sort='text'>" . Html::clean($isQuote ? __('Material', 'maintenancecosts') : Material::getTypeName(1)) . "</th>";
echo "<th data-sort='text'>" . __('Unidade', 'maintenancecosts') . "</th>";
if ($isQuote) {
   echo "<th data-sort='number'>" . __('Quantidade', 'maintenancecosts') . "</th>";
   echo "<th data-sort='currency'>" . __('Valor', 'maintenancecosts') . "</th>";
   echo "<th data-sort='currency'>" . __('Cotação 1', 'maintenancecosts') . "</th>";
   echo "<th data-sort='currency'>" . __('Cotação 2', 'maintenancecosts') . "</th>";
   echo "<th data-sort='currency'>" . __('Cotação 3', 'maintenancecosts') . "</th>";
} else {
   echo "<th data-sort='text'>" . __('Tipo de preço', 'maintenancecosts') . "</th>";
   echo "<th data-sort='currency'>" . __('Valor unitário', 'maintenancecosts') . "</th>";
}
echo "<th data-sort='text'>" . __('Competência', 'maintenancecosts') . "</th>";
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
   if ($isQuote) {
      echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_quantity'] ?? 0)) . "'>" . Html::clean($quoteNumber((float) ($row['quote_quantity'] ?? 0))) . "</td>";
      echo "<td class='center' data-value='" . Html::clean((string) (float) $row['unit_price']) . "'>" . Config::formatCurrency((float) $row['unit_price']) . "</td>";
      echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_price_1'] ?? 0)) . "'>" . Config::formatCurrency((float) ($row['quote_price_1'] ?? 0)) . "</td>";
      echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_price_2'] ?? 0)) . "'>" . Config::formatCurrency((float) ($row['quote_price_2'] ?? 0)) . "</td>";
      echo "<td class='center' data-value='" . Html::clean((string) (float) ($row['quote_price_3'] ?? 0)) . "'>" . Config::formatCurrency((float) ($row['quote_price_3'] ?? 0)) . "</td>";
   } else {
      echo "<td class='center'>" . Html::clean(Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi'))) . "</td>";
      echo "<td class='center' data-value='" . Html::clean((string) (float) $row['unit_price']) . "'>" . Config::formatCurrency((float) $row['unit_price']) . "</td>";
   }
   echo "<td class='center'>" . Html::clean($row['competence']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['source']) . "</td>";
   echo "<td class='center'>";
   echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(Price::getFormURL() . '?id=' . (int) $row['id']) . "'>" . __('Editar', 'maintenancecosts') . "</a> ";
   echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php?materials_id=' . (int) $row['plugin_maintenancecosts_materials_id'] . '&price_type=' . $priceType)) . "'>" . __('Histórico', 'maintenancecosts') . "</a>";
   echo "</td>";
   echo "</tr>";
}

if ($iterator->count() === 0) {
   echo "<tr class='tab_bg_1'><td colspan='" . ($isQuote ? 11 : 8) . "' class='center'>" . __('Nenhum preço encontrado.', 'maintenancecosts') . "</td></tr>";
}

echo "</tbody></table>";
Pager::render($totalRows, $page, $perPage, $pagerParams);
Config::renderPluginLayoutEnd();
Html::footer();
