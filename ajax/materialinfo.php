<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Price;

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
if (!Config::canViewConsumption()) {
   http_response_code(403);
   echo json_encode(['error' => 'forbidden']);
   exit;
}

$materials_id = (int) ($_GET['materials_id'] ?? 0);
$competence = Config::normalizeCompetence((string) ($_GET['competence'] ?? ''));
$price_type = Config::normalizePriceType((string) ($_GET['price_type'] ?? 'sinapi'));

$material = new Material();
if ($materials_id <= 0 || !$material->getFromDB($materials_id)) {
   http_response_code(404);
   echo json_encode(['error' => 'material_not_found']);
   exit;
}

$price = $competence !== ''
   ? Price::getForMaterialCompetenceAndType($materials_id, $competence, $price_type)
   : Price::getLatestForMaterialAndType($materials_id, $price_type);

echo json_encode([
   'id'         => $materials_id,
   'code'       => $material->fields['code'] ?? '',
   'name'       => $material->fields['name'] ?? '',
   'unit'       => $material->fields['unit'] ?? '',
   'competence' => $price['competence'] ?? $competence,
   'unit_price' => isset($price['unit_price']) ? (float) $price['unit_price'] : null,
   'has_price'  => (bool) $price,
]);
