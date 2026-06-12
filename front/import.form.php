<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\ImportBatch;
use GlpiPlugin\Maintenancecosts\Importer;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_IMPORT, UPDATE);

$priceType = Config::normalizePriceType((string) ($_POST['price_type'] ?? $_GET['price_type'] ?? 'sinapi'));
$isQuote = $priceType === 'cotacao_mercado';
$summary = null;

if (isset($_POST['import_csv'])) {
   $dryRun = !empty($_POST['dry_run']);
   $delimiter = (string) ($_POST['delimiter'] ?? 'auto');
   $delimiter = in_array($delimiter, ['auto', ';', ','], true) ? $delimiter : 'auto';

   if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
      Session::addMessageAfterRedirect(__('Selecione um arquivo CSV ou XLSX valido.', 'maintenancecosts'), false, ERROR);
   } else {
      $summary = Importer::importFile(
         $_FILES['csv_file']['tmp_name'],
         (string) $_FILES['csv_file']['name'],
         (string) ($_POST['competence'] ?? ''),
         $dryRun,
         $delimiter,
         $priceType
      );
   }
}

Html::header(ImportBatch::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('prices');

echo "<div class='center mb-3'>";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=sinapi')) . "'>" . __('Importar SINAPI', 'maintenancecosts') . "</a> ";
echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/import.form.php?price_type=cotacao_mercado')) . "'>" . __('Importar Cotação', 'maintenancecosts') . "</a>";
echo "</div>";

echo "<div class='spaced'>";
echo "<form method='post' enctype='multipart/form-data' action='" . Html::clean($_SERVER['PHP_SELF']) . "'>";
echo Html::hidden('price_type', ['value' => $priceType]);
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_2'><th colspan='4'>" . Html::clean($isQuote ? __('Importar Cotação', 'maintenancecosts') : __('Importar tabela SINAPI', 'maintenancecosts')) . "</th></tr>";
echo "<tr class='tab_bg_1'><td>" . __('Arquivo CSV/XLSX', 'maintenancecosts') . "</td><td><input type='file' name='csv_file' accept='.csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' required></td>";
echo "<td>" . __('Competência', 'maintenancecosts') . "</td><td><input type='text' name='competence' placeholder='AAAA-MM' value='" . Html::cleanInputText($_POST['competence'] ?? date('Y-m')) . "' class='form-control plugin-maintenancecosts-competence' required></td></tr>";
echo "<tr class='tab_bg_1'><td>" . __('Separador CSV', 'maintenancecosts') . "</td><td><select name='delimiter' class='form-select'><option value='auto'>Auto</option><option value=';'>;</option><option value=','>,</option></select></td>";
echo "<td>" . __('Validação', 'maintenancecosts') . "</td><td><label><input type='checkbox' name='dry_run' value='1' checked> " . __('Validar sem gravar', 'maintenancecosts') . "</label></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
echo Html::submit(__('Processar arquivo', 'maintenancecosts'), ['name' => 'import_csv', 'class' => 'btn btn-primary']);
echo "</td></tr>";
echo "<tr class='tab_bg_1'><td colspan='4'>" . __('Use Validar sem gravar para conferir a prévia. Para confirmar, envie o mesmo arquivo novamente com a opção desmarcada.', 'maintenancecosts') . "</td></tr>";
echo "</table>";
Html::closeForm();
echo "</div>";

if (is_array($summary)) {
   echo "<div class='spaced'>";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr class='tab_bg_2'><th colspan='2'>" . ($summary['dry_run'] ? __('Prévia da importação', 'maintenancecosts') : __('Resultado da importação', 'maintenancecosts')) . "</th></tr>";
   foreach ([
      'filename' => __('Arquivo', 'maintenancecosts'),
      'competence' => __('Competência', 'maintenancecosts'),
      'price_type' => __('Tipo de preço', 'maintenancecosts'),
      'total_rows' => __('Total de linhas', 'maintenancecosts'),
      'valid_rows' => __('Linhas válidas', 'maintenancecosts'),
      'invalid_rows' => __('Linhas inválidas', 'maintenancecosts'),
      'new_materials' => __('Novos materiais', 'maintenancecosts'),
      'updated_materials' => __('Materiais existentes', 'maintenancecosts'),
      'new_prices' => __('Preços novos/atualizados', 'maintenancecosts'),
      'repeated_prices' => __('Preços repetidos', 'maintenancecosts'),
   ] as $key => $label) {
      $value = (string) ($summary[$key] ?? '');
      if ($key === 'price_type') {
         $value = Config::getPriceTypeLabel($value);
      }
      echo "<tr class='tab_bg_1'><td>" . Html::clean($label) . "</td><td>" . Html::clean($value) . "</td></tr>";
   }
   if (count($summary['errors'] ?? [])) {
      echo "<tr class='tab_bg_1'><td>" . __('Errors') . "</td><td><pre>" . Html::clean(implode(PHP_EOL, array_slice($summary['errors'], 0, 50))) . "</pre></td></tr>";
   }
   echo "</table></div>";
}

global $DB;
$history = $DB->request([
   'FROM'  => ImportBatch::getTable(),
   'WHERE' => ['price_type' => $priceType],
   'ORDER' => ['id DESC'],
   'LIMIT' => 100,
]);

echo "<div class='spaced'>";
echo "<table class='tab_cadre_fixehov plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
echo "<thead><tr class='tab_bg_2'><th colspan='9'>" . Html::clean($isQuote ? __('Histórico de importações de cotação', 'maintenancecosts') : __('Histórico de importações SINAPI', 'maintenancecosts')) . "</th></tr>";
echo "<tr><th data-sort='text'>" . __('Arquivo', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Competência', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Tipo de preço', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Status') . "</th><th data-sort='number'>" . __('Linhas', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Linhas importadas', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Linhas com erro', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Usuário', 'maintenancecosts') . "</th><th data-sort='text'>" . __('Data') . "</th></tr></thead><tbody>";
foreach ($history as $row) {
   echo "<tr class='tab_bg_1'>";
   echo "<td class='text-start' style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($row['filename']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['competence']) . "</td>";
   echo "<td class='center'>" . Html::clean(Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi'))) . "</td>";
   echo "<td class='center'>" . Html::clean($row['status']) . "</td>";
   echo "<td data-value='" . (int) $row['total_rows'] . "'>" . (int) $row['total_rows'] . "</td>";
   echo "<td data-value='" . (int) $row['imported_rows'] . "'>" . (int) $row['imported_rows'] . "</td>";
   echo "<td data-value='" . (int) $row['error_rows'] . "'>" . (int) $row['error_rows'] . "</td>";
   echo "<td>" . getUserName((int) $row['users_id']) . "</td>";
   echo "<td class='center'>" . Html::clean($row['date_creation'] ?? '') . "</td>";
   echo "</tr>";
}
if ($history->count() === 0) {
   echo "<tr class='tab_bg_1'><td colspan='9' class='center'>" . __('Nenhuma importação encontrada.', 'maintenancecosts') . "</td></tr>";
}
echo "</tbody></table></div>";

Config::renderPluginLayoutEnd();
Html::footer();
