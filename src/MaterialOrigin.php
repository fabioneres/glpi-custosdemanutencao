<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class MaterialOrigin extends CommonDBTM
{
   public static $rightname = Config::RIGHT_CONFIG;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_materialorigins';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Origem do material', 'Origens do material', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-tags';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/materialorigin.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/materialorigin.form.php', $full);
   }

   public function prepareInputForAdd($input)
   {
      return $this->normalizeInput($input);
   }

   public function prepareInputForUpdate($input)
   {
      return $this->normalizeInput($input);
   }

   private function normalizeInput(array $input): array
   {
      if (isset($input['name'])) {
         $input['name'] = trim((string) $input['name']);
      }
      if (isset($input['comment'])) {
         $input['comment'] = trim((string) $input['comment']);
      }
      $input['is_active'] = isset($input['is_active']) ? (int) $input['is_active'] : 0;

      return $input;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
      echo "<td><input type='text' name='name' value='" . Html::cleanInputText($this->fields['name'] ?? '') . "' class='form-control' required></td>";
      echo "<td>" . __('Active') . "</td><td>";
      \Dropdown::showYesNo('is_active', (int) ($this->fields['is_active'] ?? 1));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
      echo "<td colspan='3'><textarea name='comment' class='form-control' rows='4'>" . Html::cleanInputText($this->fields['comment'] ?? '') . "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   public static function ensureDefaults(): void
   {
      global $DB;

      if (!$DB->tableExists(self::getTable())) {
         return;
      }

      foreach (['Almoxarifado', 'Técnico', 'Cotação/Mercado', 'Outro'] as $name) {
         $exists = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['name' => $name],
            'LIMIT' => 1,
         ])->current();

         if (!$exists) {
            $DB->insert(self::getTable(), [
               'name'          => $name,
               'is_active'     => 1,
               'comment'       => '',
               'date_creation' => date('Y-m-d H:i:s'),
               'date_mod'      => date('Y-m-d H:i:s'),
            ]);
         }
      }
   }
}
