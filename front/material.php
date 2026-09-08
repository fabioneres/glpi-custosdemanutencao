<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_MATERIALS, READ);

Html::header(Material::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('materials');

echo "<div class='center mb-3'>";
if (Config::canManageMaterials()) {
   echo "<a class='btn btn-primary' href='" . Html::clean(Material::getFormURL()) . "'>" . __('Adicionar', 'maintenancecosts') . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=materials')) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=materials&format=pdf')) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

// Keep the SINAPI restriction internal to this dedicated view. The user can
// still use all native GLPI search controls without seeing a fixed criterion.
$searchParams = Search::manageParams(Material::class, $_GET);
$searchParams['display_type'] = Search::HTML_OUTPUT;
$searchParams['criteria'] = array_values(array_filter(
   $searchParams['criteria'] ?? [],
   static function (array $criterion): bool {
      return !(
         (int) ($criterion['field'] ?? 0) === 2
         && (string) ($criterion['searchtype'] ?? '') === 'notcontains'
         && strtoupper(trim((string) ($criterion['value'] ?? ''))) === 'COT'
      );
   }
));

echo "<div class='search_page row'><div class='col search-container'>";
Search::showGenericSearch(Material::class, $searchParams);

$listParams = $searchParams;
$listParams['criteria'][] = [
   'link'       => 'AND',
   'field'      => 2,
   'searchtype' => 'notcontains',
   'value'      => 'COT',
];
Search::showList(Material::class, $listParams);
echo "</div></div>";
Config::renderPluginLayoutEnd();
Html::footer();
