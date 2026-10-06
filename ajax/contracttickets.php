<?php

use GlpiPlugin\Maintenancecosts\Config;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Session::checkLoginUser();
if (!Config::canViewConsumption() && !Config::canViewReports() && !Config::canAdminConfig()) {
   http_response_code(403);
   header('Content-Type: application/json; charset=UTF-8');
   echo json_encode(['error' => 'forbidden']);
   exit;
}

global $DB;

$search = trim((string) ($_GET['q'] ?? ''));
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

if ($search !== '') {
   $where[] = [
      'OR' => [
         ['glpi_tickets.name' => ['LIKE', '%' . $search . '%']],
         ['glpi_tickets.id' => (int) $search],
      ],
   ];
}

$rows = [];
$iterator = $DB->request([
   'SELECT' => ['id', 'name', 'date_creation'],
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

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($rows);
