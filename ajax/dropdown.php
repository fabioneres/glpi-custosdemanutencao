<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\CostCenter;
use GlpiPlugin\Maintenancecosts\CostCenterLegacy;
use GlpiPlugin\Maintenancecosts\Material;

ob_start();
if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
if (ob_get_length() !== false) {
   ob_clean();
}

header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

$type = (string) ($_GET['type'] ?? $_POST['type'] ?? '');
if ($type === 'material'
   && !Config::canViewConsumption()
   && !Config::canViewReports()
   && !Config::canViewMaterials()
) {
   http_response_code(403);
   echo json_encode(['results' => []]);
   exit;
}
if (($type === 'costcenter' || $type === 'costcenter_legacy')
   && !Config::canViewConsumption()
   && !Config::canViewReports()
   && !Config::canViewCostCenters()
) {
   http_response_code(403);
   echo json_encode(['results' => []]);
   exit;
}
if ($type === 'contract' && !Config::canViewConsumption() && !Config::canViewReports()) {
   http_response_code(403);
   echo json_encode(['results' => []]);
   exit;
}

$search = trim((string) ($_GET['q'] ?? $_POST['q'] ?? $_GET['term'] ?? $_POST['term'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

if ($type === 'material') {
   show_materials($search, $limit, $offset);
}

if ($type === 'costcenter') {
   show_costcenters($search, $limit, $offset);
}

if ($type === 'costcenter_legacy') {
   show_costcenters_legacy($search, $limit, $offset);
}

if ($type === 'contract') {
   show_contracts($search, $limit, $offset);
}

http_response_code(400);
echo json_encode(['results' => []]);

function show_materials(string $search, int $limit, int $offset): void
{
   global $DB;

   $where = ['is_active' => 1];
   if ($search !== '') {
      $like = '%' . $search . '%';
      $where[] = [
         'OR' => [
            'code' => ['LIKE', $like],
            'name' => ['LIKE', $like],
            'description' => ['LIKE', $like],
         ],
      ];
   }

   $rows = $DB->request([
      'SELECT' => ['id', 'code', 'name', 'unit'],
      'FROM'   => Material::getTable(),
      'WHERE'  => $where,
      'ORDER'  => ['code ASC', 'name ASC'],
      'START'  => $offset,
      'LIMIT'  => $limit + 1,
   ]);

   $results = [];
   $fetched = 0;
   foreach ($rows as $row) {
      $fetched++;
      if (count($results) >= $limit) {
         break;
      }
      $label = trim((string) $row['code']) !== ''
         ? sprintf('%s - %s', $row['code'], $row['name'])
         : (string) $row['name'];
      if (trim((string) $row['unit']) !== '') {
         $label .= ' (' . $row['unit'] . ')';
      }
      $results[] = ['id' => (int) $row['id'], 'text' => $label];
   }

   echo json_encode([
      'results'    => $results,
      'pagination' => ['more' => $fetched > $limit],
   ]);
   exit;
}

function show_costcenters(string $search, int $limit, int $offset): void
{
   global $DB;

   $where = ['is_active' => 1];
   if ($search !== '') {
      $like = '%' . $search . '%';
      $where[] = [
         'OR' => [
            'code' => ['LIKE', $like],
            'name' => ['LIKE', $like],
         ],
      ];
   }

   $rows = $DB->request([
      'SELECT' => ['id', 'code', 'name'],
      'FROM'   => CostCenter::getTable(),
      'WHERE'  => $where,
      'ORDER'  => ['name ASC'],
      'START'  => $offset,
      'LIMIT'  => $limit + 1,
   ]);

   $results = [];
   $fetched = 0;
   foreach ($rows as $row) {
      $fetched++;
      if (count($results) >= $limit) {
         break;
      }
      $label = trim((string) $row['code']) !== ''
         ? sprintf('%s - %s', $row['code'], $row['name'])
         : (string) $row['name'];
      $results[] = ['id' => (int) $row['id'], 'text' => $label];
   }

   echo json_encode([
      'results'    => $results,
      'pagination' => ['more' => $fetched > $limit],
   ]);
   exit;
}

function show_costcenters_legacy(string $search, int $limit, int $offset): void
{
   global $DB;

   $where = [];
   if ($search !== '') {
      $like = '%' . $search . '%';
      $where[] = [
         'OR' => [
            'code'       => ['LIKE', $like],
            'name'       => ['LIKE', $like],
            'campus'     => ['LIKE', $like],
            'department' => ['LIKE', $like],
         ],
      ];
   }

   $rows = $DB->request([
      'SELECT' => ['id', 'code', 'name', 'campus', 'department'],
      'FROM'   => CostCenterLegacy::getTable(),
      'WHERE'  => $where,
      'ORDER'  => ['code ASC', 'name ASC'],
      'START'  => $offset,
      'LIMIT'  => $limit + 1,
   ]);

   $results = [];
   $fetched = 0;
   foreach ($rows as $row) {
      $fetched++;
      if (count($results) >= $limit) {
         break;
      }
      $label = trim((string) $row['code']) !== ''
         ? sprintf('%s - %s', $row['code'], trim((string) ($row['department'] ?: $row['name'])))
         : (string) $row['name'];
      $results[] = ['id' => (int) $row['id'], 'text' => $label];
   }

   echo json_encode([
      'results'    => $results,
      'pagination' => ['more' => $fetched > $limit],
   ]);
   exit;
}

function show_contracts(string $search, int $limit, int $offset): void
{
   global $DB;

   if (!$DB->tableExists('glpi_contracts')) {
      echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
      exit;
   }

   $where = ['is_deleted' => 0];
   if ($search !== '') {
      $like = '%' . $search . '%';
      $where[] = [
         'OR' => [
            'name' => ['LIKE', $like],
            'num'  => ['LIKE', $like],
         ],
      ];
   }

   $rows = $DB->request([
      'SELECT' => ['id', 'name', 'num'],
      'FROM'   => 'glpi_contracts',
      'WHERE'  => $where,
      'ORDER'  => ['name ASC'],
      'START'  => $offset,
      'LIMIT'  => $limit + 1,
   ]);

   $results = [];
   $fetched = 0;
   foreach ($rows as $row) {
      $fetched++;
      if (count($results) >= $limit) {
         break;
      }
      $label = trim((string) ($row['num'] ?? '')) !== ''
         ? sprintf('%s - %s', $row['num'], $row['name'])
         : sprintf('%d - %s', (int) $row['id'], $row['name']);
      $results[] = ['id' => (int) $row['id'], 'text' => $label];
   }

   echo json_encode([
      'results'    => $results,
      'pagination' => ['more' => $fetched > $limit],
   ]);
   exit;
}
