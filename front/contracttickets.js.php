<?php

use GlpiPlugin\Maintenancecosts\Config;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

// Mesma guarda do ajax/contracttickets.php: este e o fallback do mesmo dado e
// nao pode ser mais permissivo que ele (F04).
Session::checkLoginUser();
if (!Config::canViewConsumption() && !Config::canViewReports() && !Config::canAdminConfig()) {
   http_response_code(403);
   header('Content-Type: application/javascript; charset=UTF-8');
   echo 'window.maintenanceCostsContractTickets = [];';
   exit;
}

global $DB;

$where = [
   'glpi_tickets.is_deleted' => 0,
];

$active_entities = $_SESSION['glpiactiveentities'] ?? [];
if (!empty($active_entities)) {
   // Chamado nao tem is_recursive: com true o criterio usava a coluna
   // inexistente glpi_tickets.is_recursive (erro SQL; HTTP 500 no GLPI 11).
   // As entidades ativas ja incluem as filhas (A17).
   $entity_criteria = getEntitiesRestrictCriteria(
      'glpi_tickets',
      'entities_id',
      $active_entities,
      false
   );
   if (count($entity_criteria)) {
      $where[] = $entity_criteria;
   }
}

$rows = [];
$iterator = $DB->request([
   'SELECT' => ['id', 'name'],
   'FROM'   => 'glpi_tickets',
   'WHERE'  => $where,
   'ORDER'  => ['id DESC'],
   'LIMIT'  => 500,
]);

foreach ($iterator as $row) {
   $name = trim((string) ($row['name'] ?? ''));
   $rows[] = [
      'id'   => (int) $row['id'],
      'text' => '#' . (int) $row['id'] . ' - ' . ($name !== '' ? $name : __('Sem título', 'maintenancecosts')),
   ];
}

header('Content-Type: application/javascript; charset=UTF-8');
echo 'window.maintenanceCostsContractTickets = ' . json_encode($rows) . ';';
