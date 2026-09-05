<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\CostCenterLegacy;
use GlpiPlugin\Maintenancecosts\Importer;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_COSTCENTERS, READ);

$summary = null;
if (isset($_POST['import_costcenters_legacy'])) {
   Config::checkRight(Config::RIGHT_COSTCENTERS, UPDATE);
   $dryRun = !empty($_POST['dry_run']);
   $delimiter = (string) ($_POST['delimiter'] ?? 'auto');
   $delimiter = in_array($delimiter, ['auto', ';', ','], true) ? $delimiter : 'auto';

   if (!isset($_FILES['costcenter_legacy_file']) || !is_uploaded_file($_FILES['costcenter_legacy_file']['tmp_name'])) {
      Session::addMessageAfterRedirect(__('Selecione um arquivo CSV ou XLSX válido.', 'maintenancecosts'), false, ERROR);
   } else {
      $summary = Importer::importCostCentersLegacyFile(
         $_FILES['costcenter_legacy_file']['tmp_name'],
         (string) $_FILES['costcenter_legacy_file']['name'],
         $dryRun,
         $delimiter
      );
   }
}

Html::header(CostCenterLegacy::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('costcenterslegacy');

echo "<div class='center mb-3'>";
if (Config::canManageCostCenters()) {
   echo "<a class='btn btn-primary' href='" . Html::clean(CostCenterLegacy::getFormURL()) . "'>" . __('Adicionar', 'maintenancecosts') . "</a> ";
}
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=costcenters_legacy')) . "'>" . __('Exportar CSV', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/export.php?type=costcenters_legacy&format=pdf')) . "'>" . __('Exportar PDF', 'maintenancecosts') . "</a>";
echo "</div>";

if (Config::canManageCostCenters()) {
   echo "<div class='plugin-maintenancecosts-panel mb-3'>";
   echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-file-import'></i> " . __('Importar centros de custo (Antigo)', 'maintenancecosts') . "</div>";
   echo "<div class='plugin-maintenancecosts-panel-body'>";
   echo "<form method='post' enctype='multipart/form-data' action='" . Html::clean($_SERVER['PHP_SELF']) . "'>";
   echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
   echo "<div class='d-flex gap-2 align-items-center flex-wrap'>";
   echo "<input type='file' name='costcenter_legacy_file' accept='.csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' required>";
   echo "<select name='delimiter' class='form-select' style='max-width:120px'><option value='auto'>Auto</option><option value=';'>;</option><option value=','>,</option></select>";
   echo "<label><input type='checkbox' name='dry_run' value='1' checked> " . __('Validar sem gravar', 'maintenancecosts') . "</label>";
   echo Html::submit(__('Processar arquivo', 'maintenancecosts'), ['name' => 'import_costcenters_legacy', 'class' => 'btn btn-primary']);
   echo "</div>";
   echo "<div class='plugin-maintenancecosts-help mt-2'>" . __('Mapeamento do Excel: CENTRO DE CUSTO -> Código; CAMPUS -> Campus; DEPARTAMENTO /DISC./SETOR -> Departamento/Disc./Setor; Tipo + Logradouro + nº -> Endereço; Piso -> Piso; UTILIZAÇÃO -> Utilização.', 'maintenancecosts') . "</div>";
   Html::closeForm();
   echo "</div></div>";
}

if (is_array($summary)) {
   echo "<div class='plugin-maintenancecosts-panel mb-3'>";
   echo "<div class='plugin-maintenancecosts-panel-header'>" . ($summary['dry_run'] ? __('Prévia da importação', 'maintenancecosts') : __('Resultado da importação', 'maintenancecosts')) . "</div>";
   echo "<div class='plugin-maintenancecosts-panel-body'>";
   echo "<table class='tab_cadre_fixe'>";
   foreach ([
      'filename'            => __('Arquivo', 'maintenancecosts'),
      'total_rows'          => __('Total de linhas', 'maintenancecosts'),
      'valid_rows'          => __('Linhas válidas', 'maintenancecosts'),
      'invalid_rows'        => __('Linhas inválidas', 'maintenancecosts'),
      'new_costcenters'     => __('Novos centros de custo', 'maintenancecosts'),
      'updated_costcenters' => __('Centros de custo atualizados', 'maintenancecosts'),
   ] as $key => $label) {
      echo "<tr class='tab_bg_1'><td>" . Html::clean($label) . "</td><td>" . Html::clean((string) ($summary[$key] ?? '')) . "</td></tr>";
   }
   if (count($summary['errors'] ?? [])) {
      echo "<tr class='tab_bg_1'><td>" . __('Errors') . "</td><td><pre>" . Html::clean(implode(PHP_EOL, array_slice($summary['errors'], 0, 50))) . "</pre></td></tr>";
   }
   echo "</table></div></div>";
}

Search::show(CostCenterLegacy::class);
Config::renderPluginLayoutEnd();
Html::footer();
