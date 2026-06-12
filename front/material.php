<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_MATERIALS, READ);

global $DB;

$search = trim((string) ($_GET['q'] ?? ''));
$where = [];
if ($search !== '') {
   $where[] = [
      'OR' => [
         ['name' => ['LIKE', '%' . $search . '%']],
         ['code' => ['LIKE', '%' . $search . '%']],
         ['unit' => ['LIKE', '%' . $search . '%']],
         ['category' => ['LIKE', '%' . $search . '%']],
      ],
   ];
}

$criteria = [
   'FROM'  => Material::getTable(),
   'ORDER' => ['code ASC', 'name ASC'],
   'LIMIT' => 200,
];
if (count($where)) {
   $criteria['WHERE'] = $where;
}

Html::header(Material::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('materials');

echo "<div class='center mb-3'>";
if (Config::canManageMaterials()) {
   echo "<a class='btn btn-primary' href='" . Html::clean(Material::getFormURL()) . "'>" . __('Adicionar', 'maintenancecosts') . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=materials')) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=materials&format=pdf')) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

echo "<form method='get' class='mb-3'>";
echo "<div class='d-flex gap-2'>";
echo "<input type='text' name='q' value='" . Html::cleanInputText($search) . "' class='form-control' placeholder='" . Html::clean(__('Pesquisar por codigo, nome, unidade ou categoria', 'maintenancecosts')) . "'>";
echo "<button class='btn btn-primary' type='submit'>" . __('Pesquisar', 'maintenancecosts') . "</button>";
echo "</div></form>";

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr><th data-sort='text'>" . __('Código SINAPI', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Nome', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Unidade', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Categoria', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Ativo', 'maintenancecosts') . "</th><th>" . __('Ações', 'maintenancecosts') . "</th></tr></thead><tbody>";

$iterator = $DB->request($criteria);
foreach ($iterator as $row) {
   $code = (string) ($row['code'] ?? '');
   $sortCode = preg_match('/^\d+$/', $code) ? str_pad($code, 8, '0', STR_PAD_LEFT) : $code;
   echo "<tr class='tab_bg_1'>";
   echo "<td data-value='" . Html::clean($sortCode) . "'>" . Html::clean($code) . "</td>";
   echo "<td class='text-start' style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($row['name']) . "</td>";
   echo "<td>" . Html::clean($row['unit']) . "</td>";
   echo "<td>" . Html::clean($row['category']) . "</td>";
   echo "<td class='center'>" . ((int) $row['is_active'] ? __('Yes') : __('No')) . "</td>";
   echo "<td><a class='btn btn-sm btn-secondary' href='" . Html::clean(Material::getFormURL() . '?id=' . (int) $row['id']) . "'>" . __('Edit') . "</a></td>";
   echo "</tr>";
}

if ($iterator->count() === 0) {
   echo "<tr class='tab_bg_1'><td colspan='6' class='center'>" . __('Nenhum material encontrado.', 'maintenancecosts') . "</td></tr>";
}

echo "</tbody></table>";
Config::renderPluginLayoutEnd();
Html::footer();
