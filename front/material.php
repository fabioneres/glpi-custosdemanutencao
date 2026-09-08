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

// The SINAPI catalog contains only materials that currently have a SINAPI price.
$_GET['criteria'] = is_array($_GET['criteria'] ?? null) ? $_GET['criteria'] : [];
$_GET['criteria'][] = [
   'link'       => 'AND',
   'field'      => 28,
   'searchtype' => 'equals',
   'value'      => 1,
];

Search::show(Material::class);
Config::renderPluginLayoutEnd();
Html::footer();
