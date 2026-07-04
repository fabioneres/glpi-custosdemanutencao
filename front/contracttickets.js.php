<?php

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

global $DB;

$where = [
   'glpi_tickets.is_deleted' => 0,
];

$active_entities = $_SESSION['glpiactiveentities'] ?? [];
if (!empty($active_entities)) {
   $entity_criteria = getEntitiesRestrictCriteria(
      'glpi_tickets',
      'entities_id',
      $active_entities,
      true
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
