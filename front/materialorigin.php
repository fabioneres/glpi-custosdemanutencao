<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\MaterialOrigin;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_CONFIG, READ);

global $DB;

Html::header(MaterialOrigin::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('origins');

echo "<div class='center mb-3'>";
echo "<a class='btn btn-primary' href='" . Html::clean(MaterialOrigin::getFormURL()) . "'>" . __('Adicionar', 'maintenancecosts') . "</a>";
echo "</div>";

$iterator = $DB->request([
   'FROM'  => MaterialOrigin::getTable(),
   'ORDER' => ['name ASC'],
]);

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr><th data-sort='text'>" . __('Nome', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Ativo', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Comentários', 'maintenancecosts') . "</th><th>" . __('Ações', 'maintenancecosts') . "</th></tr></thead><tbody>";
foreach ($iterator as $row) {
   echo "<tr class='tab_bg_1'>";
   echo "<td>" . Html::clean($row['name']) . "</td>";
   echo "<td class='center'>" . ((int) $row['is_active'] ? __('Yes') : __('No')) . "</td>";
   echo "<td>" . Html::clean($row['comment'] ?? '') . "</td>";
   echo "<td><a class='btn btn-sm btn-secondary' href='" . Html::clean(MaterialOrigin::getFormURL() . '?id=' . (int) $row['id']) . "'>" . __('Edit') . "</a></td>";
   echo "</tr>";
}
if ($iterator->count() === 0) {
   echo "<tr class='tab_bg_1'><td colspan='4' class='center'>" . __('Nenhuma origem cadastrada.', 'maintenancecosts') . "</td></tr>";
}
echo "</tbody></table>";

Config::renderPluginLayoutEnd();
Html::footer();
