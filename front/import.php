<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\ImportBatch;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_IMPORT, READ);

global $DB;

Html::header(ImportBatch::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('prices');

echo "<div class='center mb-3'>";
echo "<a class='btn btn-primary' href='" . Html::clean(ImportBatch::getFormURL()) . "'>" . __('Importar CSV SINAPI', 'maintenancecosts') . "</a>";
echo "</div>";

$iterator = $DB->request([
   'FROM'  => ImportBatch::getTable(),
   'ORDER' => ['id DESC'],
   'LIMIT' => 100,
]);

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr><th data-sort='text'>" . __('Arquivo', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Competência', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Status', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Linhas', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Linhas importadas', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Linhas com erro', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Usuário', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Data', 'maintenancecosts') . "</th><th>" . __('Ações', 'maintenancecosts') . "</th></tr></thead><tbody>";

foreach ($iterator as $row) {
   echo "<tr class='tab_bg_1'>";
   echo "<td class='text-start' style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($row['filename']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['competence']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['status']) . "</td>";
   echo "<td data-value='" . (int) $row['total_rows'] . "'>" . (int) $row['total_rows'] . "</td>";
   echo "<td data-value='" . (int) $row['imported_rows'] . "'>" . (int) $row['imported_rows'] . "</td>";
   echo "<td data-value='" . (int) $row['error_rows'] . "'>" . (int) $row['error_rows'] . "</td>";
   echo "<td>" . getUserName((int) $row['users_id']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['date_creation'] ?? '') . "</td>";
   echo "<td><a class='btn btn-sm btn-secondary' href='" . Html::clean(ImportBatch::getFormURL() . '?id=' . (int) $row['id']) . "'>" . __('Visualizar', 'maintenancecosts') . "</a></td>";
   echo "</tr>";
}

if ($iterator->count() === 0) {
   echo "<tr class='tab_bg_1'><td colspan='9' class='center'>" . __('Nenhuma importação encontrada.', 'maintenancecosts') . "</td></tr>";
}

echo "</tbody></table>";
Config::renderPluginLayoutEnd();
Html::footer();
