<?php

use GlpiPlugin\Maintenancecosts\Exporter;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

$type = (string) ($_GET['type'] ?? '');
$format = (string) ($_GET['format'] ?? 'csv');
switch ($type) {
   case 'materials':
      Exporter::exportMaterials($format);
      break;
   case 'prices':
      Exporter::exportPrices((int) ($_GET['materials_id'] ?? 0), $format, (string) ($_GET['price_type'] ?? 'all'));
      break;
   case 'costcenters':
      Exporter::exportCostCenters($format);
      break;
   case 'report':
      Exporter::exportReport($_GET, $format);
      break;
   default:
      Html::displayErrorAndDie(__('Exportação inválida.', 'maintenancecosts'));
}
