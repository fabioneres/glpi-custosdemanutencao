<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class ImportBatch extends CommonDBTM
{
   public static $rightname = Config::RIGHT_IMPORT;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_importbatches';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Importação SINAPI', 'Importações SINAPI', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-file-import';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/import.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/import.form.php', $full);
   }

   public function prepareInputForAdd($input)
   {
      if (empty($input['users_id']) && isset($_SESSION['glpiID'])) {
         $input['users_id'] = (int) $_SESSION['glpiID'];
      }
      return $this->normalizeInput($input);
   }

   public function prepareInputForUpdate($input)
   {
      return $this->normalizeInput($input);
   }

   public function post_addItem()
   {
      AuditLog::record(self::class, (int) $this->getID(), 'importbatch_add', [], $this->fields);
   }

   public function post_updateItem($history = true)
   {
      AuditLog::record(self::class, (int) $this->getID(), 'importbatch_update', [], $this->fields);
   }

   private function normalizeInput(array $input): array
   {
      foreach (['total_rows', 'imported_rows', 'error_rows'] as $field) {
         if (isset($input[$field])) {
            $input[$field] = (int) $input[$field];
         }
      }
      if (isset($input['competence'])) {
         $input['competence'] = Config::normalizeCompetence((string) $input['competence']);
      }
      if (isset($input['filename'])) {
         $input['filename'] = trim((string) $input['filename']);
      }
      if (isset($input['status'])) {
         $input['status'] = trim((string) $input['status']);
      }
      if (isset($input['price_type'])) {
         $input['price_type'] = Config::normalizePriceType((string) $input['price_type']);
      }
      return $input;
   }

   public function getSearchOptions()
   {
      $tab = [];
      $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $tab[1] = ['id' => 1, 'table' => self::getTable(), 'field' => 'filename', 'name' => __('File'), 'datatype' => 'string'];
      $tab[2] = ['id' => 2, 'table' => self::getTable(), 'field' => 'competence', 'name' => __('Competência', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[3] = ['id' => 3, 'table' => self::getTable(), 'field' => 'status', 'name' => __('Status'), 'datatype' => 'string'];
      $tab[4] = ['id' => 4, 'table' => self::getTable(), 'field' => 'imported_rows', 'name' => __('Linhas importadas', 'maintenancecosts'), 'datatype' => 'number'];
      $tab[5] = ['id' => 5, 'table' => self::getTable(), 'field' => 'error_rows', 'name' => __('Linhas com erro', 'maintenancecosts'), 'datatype' => 'number'];
      $tab[6] = ['id' => 6, 'table' => self::getTable(), 'field' => 'price_type', 'name' => __('Tipo de preÃ§o', 'maintenancecosts'), 'datatype' => 'string'];
      return $tab;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __('File') . "</td>";
      echo "<td><input type='text' name='filename' value='" . Html::cleanInputText($this->fields['filename'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Competência', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='competence' placeholder='AAAA-MM' value='" . Html::cleanInputText($this->fields['competence'] ?? '') . "' class='form-control'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Status') . "</td>";
      echo "<td><input type='text' name='status' value='" . Html::cleanInputText($this->fields['status'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Linhas', 'maintenancecosts') . "</td>";
      echo "<td><input type='number' name='total_rows' value='" . Html::cleanInputText($this->fields['total_rows'] ?? '0') . "' class='form-control'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Linhas importadas', 'maintenancecosts') . "</td>";
      echo "<td><input type='number' name='imported_rows' value='" . Html::cleanInputText($this->fields['imported_rows'] ?? '0') . "' class='form-control'></td>";
      echo "<td>" . __('Linhas com erro', 'maintenancecosts') . "</td>";
      echo "<td><input type='number' name='error_rows' value='" . Html::cleanInputText($this->fields['error_rows'] ?? '0') . "' class='form-control'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Log') . "</td>";
      echo "<td colspan='3'><textarea name='log' class='form-control' rows='6'>" . Html::cleanInputText($this->fields['log'] ?? '') . "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }
}
