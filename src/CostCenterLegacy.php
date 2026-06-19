<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use Html;

class CostCenterLegacy extends CostCenter
{
   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_costcenters_legacy';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Centro de custo (Antigo)', 'Centros de custo (Antigo)', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-building-estate';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/costcenterlegacy.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/costcenterlegacy.form.php', $full);
   }

   public function getSearchOptions()
   {
      $options = [];
      $options[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $options[1] = ['id' => 1, 'table' => self::getTable(), 'field' => 'code', 'name' => __('Código', 'maintenancecosts'), 'datatype' => 'itemlink'];
      $options[2] = ['id' => 2, 'table' => self::getTable(), 'field' => 'campus', 'name' => __('Campus', 'maintenancecosts'), 'datatype' => 'string'];
      $options[3] = ['id' => 3, 'table' => self::getTable(), 'field' => 'department', 'name' => __('Departamento/Disc./Setor', 'maintenancecosts'), 'datatype' => 'string'];
      $options[4] = ['id' => 4, 'table' => self::getTable(), 'field' => 'address', 'name' => __('Endereço', 'maintenancecosts'), 'datatype' => 'text'];
      $options[5] = ['id' => 5, 'table' => self::getTable(), 'field' => 'floor', 'name' => __('Piso', 'maintenancecosts'), 'datatype' => 'string'];
      $options[6] = ['id' => 6, 'table' => self::getTable(), 'field' => 'usage_type', 'name' => __('Utilização', 'maintenancecosts'), 'datatype' => 'string'];
      $options[7] = ['id' => 7, 'table' => self::getTable(), 'field' => 'is_active', 'name' => __('Ativo', 'maintenancecosts'), 'datatype' => 'bool'];
      $options[80] = ['id' => 80, 'table' => 'glpi_entities', 'field' => 'completename', 'linkfield' => 'entities_id', 'name' => \Entity::getTypeName(1), 'datatype' => 'dropdown'];
      return $options;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Código', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='code' value='" . Html::cleanInputText($this->fields['code'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Campus', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='campus' value='" . Html::cleanInputText($this->fields['campus'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Departamento/Disc./Setor', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='department' value='" . Html::cleanInputText($this->fields['department'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Piso', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='floor' value='" . Html::cleanInputText($this->fields['floor'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Utilização', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='usage_type' value='" . Html::cleanInputText($this->fields['usage_type'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Ativo', 'maintenancecosts') . "</td><td>";
      \Dropdown::showYesNo('is_active', (int) ($this->fields['is_active'] ?? 1));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Endereço', 'maintenancecosts') . "</td>";
      echo "<td colspan='3'><textarea name='address' class='form-control' rows='2'>" . Html::cleanInputText($this->fields['address'] ?? '') . "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Recursive') . "</td><td>";
      \Dropdown::showYesNo('is_recursive', (int) ($this->fields['is_recursive'] ?? 0));
      echo "</td><td></td><td></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
      echo "<td colspan='3'><textarea name='comment' class='form-control' rows='3'>" . Html::cleanInputText($this->fields['comment'] ?? '') . "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   public function post_addItem()
   {
      AuditLog::record(self::class, (int) $this->getID(), 'costcenter_legacy_add', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
   }

   public function post_updateItem($history = true)
   {
      AuditLog::record(self::class, (int) $this->getID(), 'costcenter_legacy_update', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
   }
}
