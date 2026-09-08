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

$priceType = Config::normalizePriceType((string) ($_GET['price_type'] ?? 'sinapi'));
$isQuote = $priceType === 'cotacao_mercado';
$activeTab = $isQuote ? 'quotes' : 'prices';
$pageTitle = $isQuote ? __('Cotação/Mercado', 'maintenancecosts') : Price::getTypeName(Session::getPluralNumber());

Html::header($pageTitle, $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart($activeTab);

echo "<div class='center mb-3'>";
if (Config::canManagePrices()) {
   $addLabel = $isQuote ? __('Adicionar preço cotação', 'maintenancecosts') : __('Adicionar preço SINAPI', 'maintenancecosts');
   $importLabel = $isQuote ? __('Importar Cotação', 'maintenancecosts') : __('Importar SINAPI', 'maintenancecosts');
   echo "<a class='btn btn-primary' href='" . Html::clean(Price::getFormURL() . '?price_type=' . $priceType) . "'>" . Html::clean($addLabel) . "</a> ";
   echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=' . $priceType)) . "'>" . Html::clean($importLabel) . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php?price_type=' . $priceType)) . "'>" . __('Histórico de preços', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&price_type=' . $priceType)) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=prices&format=pdf&price_type=' . $priceType)) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

echo "<div class='plugin-maintenancecosts-panel mb-3'>";
if ($isQuote) {
   echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> " . Html::clean(__('Cotação/Mercado', 'maintenancecosts')) . "</div>";
   echo "<div class='plugin-maintenancecosts-panel-body'><p>" . __('A listagem mostra somente a cotação vigente de cada material. Competências anteriores permanecem disponíveis em Histórico de preços.', 'maintenancecosts') . "</p></div>";
} else {
   echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-info-circle'></i> " . Html::clean(__('Materiais SINAPI x Preços SINAPI', 'maintenancecosts')) . "</div>";
   echo "<div class='plugin-maintenancecosts-panel-body'><p><strong>" . Material::getTypeName(2) . ":</strong> " . __('cadastro do item: código, nome, unidade e categoria.', 'maintenancecosts') . "</p><p><strong>" . Price::getTypeName(2) . ":</strong> " . __('valores SINAPI por competência. Reimportações atualizam o preço vigente e registram histórico sem alterar lançamentos já gravados no chamado.', 'maintenancecosts') . "</p></div>";
}
echo "</div>";

// The table type is fixed by its menu and only its current price is shown.
$_GET['criteria'] = is_array($_GET['criteria'] ?? null) ? $_GET['criteria'] : [];
$_GET['criteria'][] = [
   'link'       => 'AND',
   'field'      => 13,
   'searchtype' => 'equals',
   'value'      => 1,
];
$_GET['criteria'][] = [
   'link'       => 'AND',
   'field'      => 4,
   'searchtype' => 'equals',
   'value'      => $priceType,
];
Search::show(Price::class);
Config::renderPluginLayoutEnd();
Html::footer();
