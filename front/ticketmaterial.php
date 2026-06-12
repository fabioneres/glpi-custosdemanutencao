<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\CostCenter;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\MaterialOrigin;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\TicketMaterial;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_CONSUMPTION, READ);

global $DB;

$search = trim((string) ($_GET['q'] ?? ''));
$where = [TicketMaterial::getTable() . '.is_deleted' => 0];
if ($search !== '') {
   $where[] = [
      'OR' => [
         [Material::getTable() . '.name' => ['LIKE', '%' . $search . '%']],
         [Material::getTable() . '.code' => ['LIKE', '%' . $search . '%']],
         [CostCenter::getTable() . '.name' => ['LIKE', '%' . $search . '%']],
         [CostCenter::getTable() . '.code' => ['LIKE', '%' . $search . '%']],
         ['glpi_tickets.name' => ['LIKE', '%' . $search . '%']],
         ['glpi_contracts.name' => ['LIKE', '%' . $search . '%']],
      ],
   ];
}

$iterator = $DB->request([
   'SELECT' => [
      TicketMaterial::getTable() . '.*',
      'glpi_tickets.name AS ticket_name',
      Material::getTable() . '.code AS material_code',
      Material::getTable() . '.name AS material_name',
      CostCenter::getTable() . '.code AS costcenter_code',
      CostCenter::getTable() . '.name AS costcenter_name',
      MaterialOrigin::getTable() . '.name AS origin_name',
      'glpi_contracts.name AS contract_name',
   ],
   'FROM' => TicketMaterial::getTable(),
   'LEFT JOIN' => [
      'glpi_tickets' => [
         'FKEY' => [TicketMaterial::getTable() => 'tickets_id', 'glpi_tickets' => 'id'],
      ],
      Material::getTable() => [
         'FKEY' => [TicketMaterial::getTable() => 'plugin_maintenancecosts_materials_id', Material::getTable() => 'id'],
      ],
      CostCenter::getTable() => [
         'FKEY' => [TicketMaterial::getTable() => 'plugin_maintenancecosts_costcenters_id', CostCenter::getTable() => 'id'],
      ],
      MaterialOrigin::getTable() => [
         'FKEY' => [TicketMaterial::getTable() => 'plugin_maintenancecosts_materialorigins_id', MaterialOrigin::getTable() => 'id'],
      ],
      'glpi_contracts' => [
         'FKEY' => [TicketMaterial::getTable() => 'contracts_id', 'glpi_contracts' => 'id'],
      ],
   ],
   'WHERE' => $where,
   'ORDER' => [TicketMaterial::getTable() . '.consumption_date DESC', TicketMaterial::getTable() . '.id DESC'],
   'LIMIT' => 300,
]);

Html::header(TicketMaterial::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('consumption');

echo "<form method='get' class='mb-3'>";
echo "<div class='d-flex gap-2'>";
echo "<input type='text' name='q' value='" . Html::cleanInputText($search) . "' class='form-control' placeholder='" . Html::clean(__('Pesquisar por chamado, material, centro de custo ou contrato', 'maintenancecosts')) . "'>";
echo "<button class='btn btn-primary' type='submit'>" . __('Pesquisar', 'maintenancecosts') . "</button>";
echo "</div></form>";

echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr>";
foreach ([
   ['number', __('Chamado', 'maintenancecosts')],
   ['text', __('Título', 'maintenancecosts')],
   ['text', __('Código SINAPI', 'maintenancecosts')],
   ['text', Material::getTypeName(1)],
   ['number', __('Quantidade', 'maintenancecosts')],
   ['text', __('Unidade', 'maintenancecosts')],
   ['currency', __('Valor unitário', 'maintenancecosts')],
   ['currency', __('Total', 'maintenancecosts')],
   ['text', CostCenter::getTypeName(1)],
   ['text', MaterialOrigin::getTypeName(1)],
   ['text', __('Tipo de preço', 'maintenancecosts')],
   ['text', \Contract::getTypeName(1)],
   ['text', __('Data', 'maintenancecosts')],
   ['text', __('Técnico', 'maintenancecosts')],
] as $header) {
   echo "<th data-sort='" . Html::clean($header[0]) . "'>" . Html::clean($header[1]) . "</th>";
}
echo "</tr></thead><tbody>";

foreach ($iterator as $row) {
   $costcenter = trim((string) ($row['costcenter_code'] ?? '')) !== ''
      ? $row['costcenter_code'] . ' - ' . $row['costcenter_name']
      : (string) ($row['costcenter_name'] ?? '');
   $code = (string) ($row['material_code'] ?? '');
   $sortCode = preg_match('/^\d+$/', $code) ? str_pad($code, 8, '0', STR_PAD_LEFT) : $code;

   echo "<tr class='tab_bg_1'>";
   echo "<td data-value='" . (int) $row['tickets_id'] . "'>" . (int) $row['tickets_id'] . "</td>";
   echo "<td class='text-start'>" . Html::clean($row['ticket_name'] ?? '') . "</td>";
   echo "<td data-value='" . Html::clean($sortCode) . "'>" . Html::clean($code) . "</td>";
   echo "<td class='text-start' style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($row['material_name'] ?? '') . "</td>";
   echo "<td data-value='" . Html::clean((string) (float) $row['quantity']) . "'>" . TicketMaterial::formatQuantity((float) $row['quantity']) . "</td>";
   echo "<td>" . Html::clean($row['unit'] ?? '') . "</td>";
   echo "<td data-value='" . Html::clean((string) (float) $row['unit_price_applied']) . "'>" . Config::formatCurrency((float) $row['unit_price_applied']) . "</td>";
   echo "<td data-value='" . Html::clean((string) (float) $row['total_price']) . "'>" . Config::formatCurrency((float) $row['total_price']) . "</td>";
   echo "<td class='text-start'>" . Html::clean($costcenter) . "</td>";
   echo "<td>" . Html::clean($row['origin_name'] ?? '') . "</td>";
   echo "<td>" . Html::clean(Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi'))) . "</td>";
   echo "<td>" . Html::clean($row['contract_name'] ?? '') . "</td>";
   echo "<td>" . Html::clean($row['consumption_date'] ?? '') . "</td>";
   echo "<td>" . getUserName((int) $row['users_id']) . "</td>";
   echo "</tr>";
}

if ($iterator->count() === 0) {
   echo "<tr class='tab_bg_1' data-no-sort='1'><td colspan='14' class='center'>" . __('Nenhum material consumido encontrado.', 'maintenancecosts') . "</td></tr>";
}

echo "</tbody></table>";
Config::renderPluginLayoutEnd();
Html::footer();
