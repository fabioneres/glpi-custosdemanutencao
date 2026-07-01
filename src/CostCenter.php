<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class CostCenter extends CommonDBTM
{
   public static $rightname = Config::RIGHT_COSTCENTERS;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_costcenters';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Centro de Custos Novo', 'Centro de Custos Novo', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-building-bank';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/costcenter.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/costcenter.form.php', $full);
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
      AuditLog::record(self::class, (int) $this->getID(), 'costcenter_add', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
   }

   public function post_updateItem($history = true)
   {
      AuditLog::record(self::class, (int) $this->getID(), 'costcenter_update', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
   }

   private function normalizeInput(array $input): array
   {
      foreach ([
         'code',
         'name',
         'address',
         'floor',
         'campus',
         'academic_unit',
         'department',
         'division',
         'section',
         'siorg_code',
         'siorg_acronym',
         'responsible',
         'usage_type',
      ] as $field) {
         if (isset($input[$field])) {
            $input[$field] = trim((string) $input[$field]);
         }
      }

      foreach (['locations_id', 'users_id'] as $field) {
         if (isset($input[$field])) {
            $input[$field] = (int) $input[$field];
         }
      }

      if (isset($input['locations_id'])) {
         $input['locations_id'] = self::getRootLocationId((int) $input['locations_id']);
         $locationLabel = self::getLocationLabel((int) $input['locations_id']);
         if ($locationLabel !== '') {
            $input['campus'] = $locationLabel;
         }
      } elseif (!empty($input['campus'])) {
         $input['locations_id'] = self::getRootLocationIdByLabel((string) $input['campus']);
      }

      $input['name'] = self::deriveDisplayName($input);
      $input['is_active'] = isset($input['is_active']) ? (int) $input['is_active'] : 0;
      $input['is_recursive'] = isset($input['is_recursive']) ? (int) $input['is_recursive'] : 0;

      return $input;
   }

   public function getSearchOptions()
   {
      $tab = [];
      $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $tab[1] = ['id' => 1, 'table' => self::getTable(), 'field' => 'code', 'name' => __('Código', 'maintenancecosts'), 'datatype' => 'itemlink'];
      $tab[2] = ['id' => 2, 'table' => self::getTable(), 'field' => 'campus', 'name' => __('Unidade gestora', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[3] = ['id' => 3, 'table' => self::getTable(), 'field' => 'academic_unit', 'name' => __('Unidade acadêmica', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[4] = ['id' => 4, 'table' => self::getTable(), 'field' => 'department', 'name' => __('Departamento', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[5] = ['id' => 5, 'table' => self::getTable(), 'field' => 'division', 'name' => __('Divisão', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[6] = ['id' => 6, 'table' => self::getTable(), 'field' => 'section', 'name' => __('Seção', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[7] = ['id' => 7, 'table' => self::getTable(), 'field' => 'siorg_code', 'name' => __('Código SIORG', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[8] = ['id' => 8, 'table' => self::getTable(), 'field' => 'siorg_acronym', 'name' => __('Sigla SIORG', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[9] = ['id' => 9, 'table' => self::getTable(), 'field' => 'address', 'name' => __('Endereço', 'maintenancecosts'), 'datatype' => 'text'];
      $tab[10] = ['id' => 10, 'table' => self::getTable(), 'field' => 'responsible', 'name' => __('Responsável', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[11] = ['id' => 11, 'table' => self::getTable(), 'field' => 'is_active', 'name' => __('Ativo', 'maintenancecosts'), 'datatype' => 'bool'];
      $tab[80] = ['id' => 80, 'table' => 'glpi_entities', 'field' => 'completename', 'linkfield' => 'entities_id', 'name' => \Entity::getTypeName(1), 'datatype' => 'dropdown'];
      return $tab;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $locationId = self::getRootLocationId((int) ($this->fields['locations_id'] ?? 0));

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Código', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='code' value='" . Html::cleanInputText($this->fields['code'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Unidade gestora', 'maintenancecosts') . " <span class='plugin-maintenancecosts-help'>(" . __('Localização nível 1', 'maintenancecosts') . ")</span></td><td>";
      self::showRootLocationDropdown('locations_id', $locationId);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Unidade acadêmica', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='academic_unit' value='" . Html::cleanInputText($this->fields['academic_unit'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Departamento', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='department' value='" . Html::cleanInputText($this->fields['department'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Divisão', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='division' value='" . Html::cleanInputText($this->fields['division'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Seção', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='section' value='" . Html::cleanInputText($this->fields['section'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Código SIORG', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='siorg_code' value='" . Html::cleanInputText($this->fields['siorg_code'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Sigla SIORG', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='siorg_acronym' value='" . Html::cleanInputText($this->fields['siorg_acronym'] ?? '') . "' class='form-control'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Endereço', 'maintenancecosts') . "</td>";
      echo "<td colspan='3'><textarea name='address' class='form-control' rows='2'>" . Html::cleanInputText($this->fields['address'] ?? '') . "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Responsável', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='responsible' value='" . Html::cleanInputText($this->fields['responsible'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Ativo', 'maintenancecosts') . "</td><td>";
      \Dropdown::showYesNo('is_active', (int) ($this->fields['is_active'] ?? 1));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Recursive') . "</td><td>";
      \Dropdown::showYesNo('is_recursive', (int) ($this->fields['is_recursive'] ?? 0));
      echo "</td><td></td><td></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Description') . "</td>";
      echo "<td colspan='3'><textarea name='description' class='form-control' rows='3'>" . Html::cleanInputText($this->fields['description'] ?? '') . "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
      echo "<td colspan='3'><textarea name='comment' class='form-control' rows='3'>" . Html::cleanInputText($this->fields['comment'] ?? '') . "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   public static function getRootLocationIdByLabel(string $label): int
   {
      global $DB;
      $label = trim($label);
      if ($label === '' || !$DB->tableExists('glpi_locations')) {
         return 0;
      }

      $row = $DB->request([
         'SELECT' => ['id'],
         'FROM'   => 'glpi_locations',
         'WHERE'  => [
            'locations_id' => 0,
            'OR'           => [
               ['name' => $label],
               ['completename' => $label],
            ],
         ],
         'LIMIT'  => 1,
      ])->current();

      return is_array($row) ? (int) $row['id'] : 0;
   }

   private static function deriveDisplayName(array $input): string
   {
      foreach (['section', 'division', 'department', 'academic_unit', 'campus', 'name', 'code'] as $field) {
         $value = trim((string) ($input[$field] ?? ''));
         if ($value !== '') {
            return $value;
         }
      }
      return '';
   }

   private static function showRootLocationDropdown(string $name, int $value): void
   {
      echo "<select name='" . Html::cleanInputText($name) . "' class='form-select plugin-maintenancecosts-dropdown'>";
      echo "<option value='0'>-----</option>";
      foreach (self::rootLocationOptions($value) as $id => $label) {
         echo "<option value='" . (int) $id . "' " . ((int) $id === $value ? 'selected' : '') . ">" . Html::clean($label) . "</option>";
      }
      echo "</select>";
   }

   private static function rootLocationOptions(int $includeId = 0): array
   {
      global $DB;
      if (!$DB->tableExists('glpi_locations')) {
         return [];
      }

      $options = [];
      foreach ($DB->request([
         'SELECT' => ['id', 'name', 'completename'],
         'FROM'   => 'glpi_locations',
         'WHERE'  => ['locations_id' => 0],
         'ORDER'  => 'name ASC',
      ]) as $row) {
         $label = self::locationLabelFromRow($row);
         if ($label !== '') {
            $options[(int) $row['id']] = $label;
         }
      }

      if ($includeId > 0 && !isset($options[$includeId])) {
         $label = self::getLocationLabel($includeId);
         if ($label !== '') {
            $options[$includeId] = $label;
            asort($options, SORT_NATURAL | SORT_FLAG_CASE);
         }
      }

      return $options;
   }

   private static function getRootLocationId(int $locationsId): int
   {
      global $DB;
      if ($locationsId <= 0 || !$DB->tableExists('glpi_locations')) {
         return 0;
      }

      $current = $locationsId;
      $guard = 0;
      while ($current > 0 && $guard < 50) {
         $row = $DB->request([
            'SELECT' => ['id', 'locations_id'],
            'FROM'   => 'glpi_locations',
            'WHERE'  => ['id' => $current],
            'LIMIT'  => 1,
         ])->current();
         if (!is_array($row)) {
            return 0;
         }

         $parent = (int) ($row['locations_id'] ?? 0);
         if ($parent <= 0) {
            return (int) $row['id'];
         }

         $current = $parent;
         $guard++;
      }

      return 0;
   }

   private static function getLocationLabel(int $locationsId): string
   {
      global $DB;
      if ($locationsId <= 0 || !$DB->tableExists('glpi_locations')) {
         return '';
      }

      $row = $DB->request([
         'SELECT' => ['name', 'completename'],
         'FROM'   => 'glpi_locations',
         'WHERE'  => ['id' => $locationsId],
         'LIMIT'  => 1,
      ])->current();

      return is_array($row) ? self::locationLabelFromRow($row) : '';
   }

   private static function locationLabelFromRow(array $row): string
   {
      $label = trim((string) ($row['name'] ?? ''));
      if ($label === '') {
         $label = trim((string) ($row['completename'] ?? ''));
      }
      return $label;
   }
}
