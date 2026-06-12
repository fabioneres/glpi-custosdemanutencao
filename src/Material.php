<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;
use Session;

class Material extends CommonDBTM
{
   public static $rightname = Config::RIGHT_MATERIALS;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_materials';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Material SINAPI', 'Materiais SINAPI', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-package';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/material.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/material.form.php', $full);
   }

   public static function getNameField()
   {
      return 'name';
   }

   public function prepareInputForAdd($input)
   {
      $input = $this->normalizeInput($input);
      if (empty($input['entities_id']) && isset($_SESSION['glpiactive_entity'])) {
         $input['entities_id'] = (int) $_SESSION['glpiactive_entity'];
      }
      return $input;
   }

   public function prepareInputForUpdate($input)
   {
      return $this->normalizeInput($input);
   }

   public function post_addItem()
   {
      AuditLog::record(self::class, (int) $this->getID(), 'material_add', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
   }

   public function post_updateItem($history = true)
   {
      AuditLog::record(self::class, (int) $this->getID(), 'material_update', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
   }

   private function normalizeInput(array $input): array
   {
      foreach (['code', 'name', 'description', 'unit', 'category'] as $field) {
         if (isset($input[$field])) {
            $input[$field] = $this->cleanText((string) $input[$field], $field === 'name' ? 255 : 0);
         }
      }

      $input['is_active'] = isset($input['is_active']) ? (int) $input['is_active'] : 0;
      $input['is_recursive'] = isset($input['is_recursive']) ? (int) $input['is_recursive'] : 0;

      return $input;
   }

   private function cleanText(string $value, int $maxLength = 0): string
   {
      $value = trim(str_replace(["\r", "\n", "\t", "'"], ' ', $value));
      $value = (string) preg_replace('/\s+/', ' ', $value);

      if ($maxLength > 0 && strlen($value) > $maxLength) {
         $value = substr($value, 0, $maxLength);
      }

      return trim($value);
   }

   public function getSearchOptions()
   {
      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(1),
      ];

      $tab[1] = [
         'id'            => 1,
         'table'         => self::getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'massiveaction' => false,
      ];

      $tab[2] = [
         'id'       => 2,
         'table'    => self::getTable(),
         'field'    => 'code',
         'name'     => __('Código SINAPI', 'maintenancecosts'),
         'datatype' => 'string',
      ];

      $tab[3] = [
         'id'       => 3,
         'table'    => self::getTable(),
         'field'    => 'unit',
         'name'     => __('Unidade', 'maintenancecosts'),
         'datatype' => 'string',
      ];

      $tab[4] = [
         'id'       => 4,
         'table'    => self::getTable(),
         'field'    => 'category',
         'name'     => __('Categoria', 'maintenancecosts'),
         'datatype' => 'string',
      ];

      $tab[5] = [
         'id'       => 5,
         'table'    => self::getTable(),
         'field'    => 'is_active',
         'name'     => __('Active'),
         'datatype' => 'bool',
      ];

      $tab[80] = [
         'id'       => 80,
         'table'    => 'glpi_entities',
         'field'    => 'completename',
         'linkfield' => 'entities_id',
         'name'     => \Entity::getTypeName(1),
         'datatype' => 'dropdown',
      ];

      return $tab;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      if ((int) $ID > 0) {
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<a class='btn btn-secondary' href='" . Html::clean(Config::pluginUrl('/front/pricehistory.php?materials_id=' . (int) $ID)) . "'>" . __('Histórico de preços', 'maintenancecosts') . "</a>";
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Código SINAPI', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='code' value='" . Html::cleanInputText($this->fields['code'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td><input type='text' name='name' value='" . Html::cleanInputText($this->fields['name'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Unidade', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='unit' value='" . Html::cleanInputText($this->fields['unit'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Categoria', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='category' value='" . Html::cleanInputText($this->fields['category'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Active') . "</td><td>";
      \Dropdown::showYesNo('is_active', (int) ($this->fields['is_active'] ?? 1));
      echo "</td><td>" . __('Recursive') . "</td><td>";
      \Dropdown::showYesNo('is_recursive', (int) ($this->fields['is_recursive'] ?? 0));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      echo "<td colspan='3'><textarea name='description' class='form-control' rows='4'>" . Html::cleanInputText($this->fields['description'] ?? '') . "</textarea></td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }
}
