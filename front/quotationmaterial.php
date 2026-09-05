<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_MATERIALS, READ);

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
echo "<div class='plugin-maintenancecosts-panel-body'><p>" . Html::clean(__('Esta aba mostra os materiais que possuem cotação vigente. As informações de quantidade, valores e competência correspondem sempre ao preço vigente.', 'maintenancecosts')) . "</p></div></div>";

// Restrict this view to materials with exactly one current quotation.
$_GET['criteria'] = is_array($_GET['criteria'] ?? null) ? $_GET['criteria'] : [];
$_GET['criteria'][] = [
   'link'       => 'AND',
   'field'      => 20,
   'searchtype' => 'equals',
   'value'      => 1,
];

Search::show(Material::class);
Config::renderPluginLayoutEnd();
Html::footer();
