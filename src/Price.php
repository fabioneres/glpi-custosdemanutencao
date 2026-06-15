<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class Price extends CommonDBTM
{
   public static $rightname = Config::RIGHT_PRICES;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_prices';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Preço SINAPI', 'Preços SINAPI', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-cash';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/price.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/price.form.php', $full);
   }

   public function prepareInputForAdd($input)
   {
      return $this->normalizeInput($input);
   }

   public function prepareInputForUpdate($input)
   {
      return $this->normalizeInput($input);
   }

   public function post_addItem()
   {
      AuditLog::record(self::class, (int) $this->getID(), 'price_add', [], $this->fields);
      PriceHistory::record([
         'plugin_maintenancecosts_materials_id'     => (int) ($this->fields['plugin_maintenancecosts_materials_id'] ?? 0),
         'plugin_maintenancecosts_prices_id'        => (int) $this->getID(),
         'plugin_maintenancecosts_importbatches_id' => (int) ($this->fields['plugin_maintenancecosts_importbatches_id'] ?? 0),
         'competence'                               => (string) ($this->fields['competence'] ?? ''),
         'price_type'                               => (string) ($this->fields['price_type'] ?? 'sinapi'),
         'old_unit_price'                           => 0,
         'new_unit_price'                           => (float) ($this->fields['unit_price'] ?? 0),
         'source'                                   => (string) ($this->fields['source'] ?? ''),
         'users_id'                                 => (int) ($this->fields['users_id'] ?? ($_SESSION['glpiID'] ?? 0)),
         'justification'                            => (string) ($this->fields['comment'] ?? ''),
      ]);
   }

   public function post_updateItem($history = true)
   {
      AuditLog::record(self::class, (int) $this->getID(), 'price_update', [], $this->fields);
      $oldPrice = $this->oldvalues['unit_price'] ?? ($this->fields['unit_price'] ?? null);
      $oldCompetence = $this->oldvalues['competence'] ?? ($this->fields['competence'] ?? '');
      $oldMaterial = $this->oldvalues['plugin_maintenancecosts_materials_id'] ?? ($this->fields['plugin_maintenancecosts_materials_id'] ?? 0);
      $quoteChanged = false;
      foreach (['quote_quantity', 'quote_price_1', 'quote_price_2', 'quote_price_3'] as $quoteField) {
         if (array_key_exists($quoteField, $this->oldvalues)
            && abs((float) $this->oldvalues[$quoteField] - (float) ($this->fields[$quoteField] ?? 0)) > 0.000001
         ) {
            $quoteChanged = true;
            break;
         }
      }

      if (($oldPrice !== null && abs((float) $oldPrice - (float) ($this->fields['unit_price'] ?? 0)) > 0.000001) || $quoteChanged) {
         PriceHistory::record([
            'plugin_maintenancecosts_materials_id'     => (int) $oldMaterial,
            'plugin_maintenancecosts_prices_id'        => (int) $this->getID(),
            'plugin_maintenancecosts_importbatches_id' => (int) ($this->fields['plugin_maintenancecosts_importbatches_id'] ?? 0),
            'competence'                               => (string) $oldCompetence,
            'price_type'                               => (string) ($this->fields['price_type'] ?? 'sinapi'),
            'old_unit_price'                           => (float) $oldPrice,
            'new_unit_price'                           => (float) ($this->fields['unit_price'] ?? 0),
            'source'                                   => (string) ($this->fields['source'] ?? ''),
            'users_id'                                 => (int) ($this->fields['users_id'] ?? ($_SESSION['glpiID'] ?? 0)),
            'justification'                            => (string) ($this->fields['comment'] ?? ''),
         ]);
      }
   }

   public static function getLatestForMaterial(int $materials_id): ?array
   {
      return self::getLatestForMaterialAndType($materials_id, '');
   }

   public static function getLatestForMaterialAndType(int $materials_id, string $price_type): ?array
   {
      global $DB;

      $where = ['plugin_maintenancecosts_materials_id' => $materials_id];
      if ($price_type !== '') {
         $where['price_type'] = Config::normalizePriceType($price_type);
      }

      $row = $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => $where,
         'ORDER' => 'competence DESC, id DESC',
         'LIMIT' => 1,
      ])->current();

      return $row ?: null;
   }

   public static function getLatestCompetence(string $price_type = ''): string
   {
      global $DB;

      $where = [];
      if ($price_type !== '') {
         $where['price_type'] = Config::normalizePriceType($price_type);
      }

      $criteria = [
         'SELECT' => ['competence'],
         'FROM'   => self::getTable(),
         'ORDER'  => 'competence DESC, id DESC',
         'LIMIT'  => 1,
      ];
      if (count($where)) {
         $criteria['WHERE'] = $where;
      }

      $row = $DB->request($criteria)->current();
      return $row && !empty($row['competence']) ? (string) $row['competence'] : date('Y-m');
   }

   public static function getForMaterialAndCompetence(int $materials_id, string $competence): ?array
   {
      return self::getForMaterialCompetenceAndType($materials_id, $competence, '');
   }

   public static function getForMaterialCompetenceAndType(int $materials_id, string $competence, string $price_type): ?array
   {
      global $DB;

      $competence = substr(trim($competence), 0, 7);
      if ($materials_id <= 0 || $competence === '') {
         return null;
      }

      $where = [
         'plugin_maintenancecosts_materials_id' => $materials_id,
         'competence'                          => $competence,
      ];
      if ($price_type !== '') {
         $where['price_type'] = Config::normalizePriceType($price_type);
      }

      $row = $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => $where,
         'ORDER' => 'id DESC',
         'LIMIT' => 1,
      ])->current();

      return $row ?: null;
   }

   private function normalizeInput(array $input): array
   {
      if (isset($input['plugin_maintenancecosts_materials_id'])) {
         $input['plugin_maintenancecosts_materials_id'] = (int) $input['plugin_maintenancecosts_materials_id'];
      }
      foreach (['unit_price', 'quote_quantity', 'quote_price_1', 'quote_price_2', 'quote_price_3'] as $decimalField) {
         if (isset($input[$decimalField])) {
            $input[$decimalField] = Config::parseDecimal($input[$decimalField]);
         }
      }
      if (isset($input['price_type'])) {
         $input['price_type'] = Config::normalizePriceType((string) $input['price_type']);
      }
      if (isset($input['competence'])) {
         $input['competence'] = Config::normalizeCompetence((string) $input['competence']);
      }
      if (isset($input['source'])) {
         $input['source'] = trim((string) $input['source']);
      }
      if (empty($input['users_id']) && isset($_SESSION['glpiID'])) {
         $input['users_id'] = (int) $_SESSION['glpiID'];
      }

      return $input;
   }

   public function getSearchOptions()
   {
      $tab = [];
      $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $tab[1] = ['id' => 1, 'table' => Material::getTable(), 'field' => 'name', 'linkfield' => 'plugin_maintenancecosts_materials_id', 'name' => Material::getTypeName(1), 'datatype' => 'dropdown'];
      $tab[2] = ['id' => 2, 'table' => self::getTable(), 'field' => 'competence', 'name' => __('Competência', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[3] = ['id' => 3, 'table' => self::getTable(), 'field' => 'unit_price', 'name' => __('Valor unitário', 'maintenancecosts'), 'datatype' => 'decimal'];
      $tab[4] = ['id' => 4, 'table' => self::getTable(), 'field' => 'price_type', 'name' => __('Tipo de preço', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[5] = ['id' => 5, 'table' => self::getTable(), 'field' => 'source', 'name' => __('Origem', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[6] = ['id' => 6, 'table' => 'glpi_users', 'field' => 'name', 'linkfield' => 'users_id', 'name' => \User::getTypeName(1), 'datatype' => 'dropdown'];
      $tab[7] = ['id' => 7, 'table' => self::getTable(), 'field' => 'quote_quantity', 'name' => __('Quantidade cotada', 'maintenancecosts'), 'datatype' => 'decimal'];
      $tab[8] = ['id' => 8, 'table' => self::getTable(), 'field' => 'quote_price_1', 'name' => __('Cotação 1', 'maintenancecosts'), 'datatype' => 'decimal'];
      $tab[9] = ['id' => 9, 'table' => self::getTable(), 'field' => 'quote_price_2', 'name' => __('Cotação 2', 'maintenancecosts'), 'datatype' => 'decimal'];
      $tab[10] = ['id' => 10, 'table' => self::getTable(), 'field' => 'quote_price_3', 'name' => __('Cotação 3', 'maintenancecosts'), 'datatype' => 'decimal'];
      return $tab;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $priceType = Config::normalizePriceType((string) ($this->fields['price_type'] ?? $options['price_type'] ?? $_GET['price_type'] ?? 'sinapi'));
      $isQuote = $priceType === 'cotacao_mercado';
      if ((int) $ID <= 0) {
         $this->fields['price_type'] = $priceType;
         if (empty($this->fields['source'])) {
            $this->fields['source'] = $isQuote
               ? __('Cotação/Mercado manual', 'maintenancecosts')
               : __('Manual', 'maintenancecosts');
         }
      }
      $this->showFormHeader($options);
      echo Html::hidden('price_type', ['value' => $priceType]);

      echo "<tr class='tab_bg_1'><td>" . ($isQuote ? __('Material Cotação', 'maintenancecosts') : Material::getTypeName(1)) . "</td><td>";
      $this->showMaterialDropdown((int) ($this->fields['plugin_maintenancecosts_materials_id'] ?? 0));
      echo "</td><td>" . __('Competência', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='competence' placeholder='AAAA-MM' maxlength='7' value='" . Html::cleanInputText(Config::normalizeCompetence((string) ($this->fields['competence'] ?? ''))) . "' class='form-control plugin-maintenancecosts-competence'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . ($isQuote ? __('Valor aplicado', 'maintenancecosts') : __('Valor unitário', 'maintenancecosts')) . "</td>";
      echo "<td><input type='text' inputmode='decimal' name='unit_price' value='" . Html::cleanInputText(Config::formatDecimalInput((float) ($this->fields['unit_price'] ?? 0))) . "' class='form-control plugin-maintenancecosts-money'></td>";
      echo "<td>" . __('Tipo de preço', 'maintenancecosts') . "</td><td><strong>" . Html::clean(Config::getPriceTypeLabel($priceType)) . "</strong></td></tr>";

      if ($isQuote) {
         echo "<tr class='tab_bg_1'><td>" . __('Quantidade cotada', 'maintenancecosts') . "</td>";
         echo "<td><input type='text' inputmode='decimal' name='quote_quantity' value='" . Html::cleanInputText(Config::formatDecimalInput((float) ($this->fields['quote_quantity'] ?? 0))) . "' class='form-control'></td>";
         echo "<td>" . __('Cotações de mercado', 'maintenancecosts') . "</td><td><div class='d-flex gap-2'>";
         echo "<input type='text' inputmode='decimal' name='quote_price_1' value='" . Html::cleanInputText(Config::formatDecimalInput((float) ($this->fields['quote_price_1'] ?? 0))) . "' class='form-control plugin-maintenancecosts-money' placeholder='" . Html::clean(__('Cotação 1', 'maintenancecosts')) . "'>";
         echo "<input type='text' inputmode='decimal' name='quote_price_2' value='" . Html::cleanInputText(Config::formatDecimalInput((float) ($this->fields['quote_price_2'] ?? 0))) . "' class='form-control plugin-maintenancecosts-money' placeholder='" . Html::clean(__('Cotação 2', 'maintenancecosts')) . "'>";
         echo "<input type='text' inputmode='decimal' name='quote_price_3' value='" . Html::cleanInputText(Config::formatDecimalInput((float) ($this->fields['quote_price_3'] ?? 0))) . "' class='form-control plugin-maintenancecosts-money' placeholder='" . Html::clean(__('Cotação 3', 'maintenancecosts')) . "'>";
         echo "</div></td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>" . __('Origem', 'maintenancecosts') . "</td>";
      echo "<td colspan='3'><input type='text' name='source' value='" . Html::cleanInputText($this->fields['source'] ?? '') . "' class='form-control'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Justificativa/Comentários', 'maintenancecosts') . "</td>";
      echo "<td colspan='3'><textarea name='comment' class='form-control' rows='3'>" . Html::cleanInputText($this->fields['comment'] ?? '') . "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   private function showMaterialDropdown(int $value): void
   {
      global $DB;

      echo "<select name='plugin_maintenancecosts_materials_id' class='form-control plugin-maintenancecosts-dropdown'>";
      echo "<option value='0'>-----</option>";

      foreach ($DB->request([
         'SELECT' => ['id', 'code', 'name', 'unit'],
         'FROM'   => Material::getTable(),
         'WHERE'  => ['is_active' => 1],
         'ORDER'  => ['code ASC', 'name ASC'],
      ]) as $row) {
         $label = trim((string) ($row['code'] ?? '')) !== ''
            ? $row['code'] . ' - ' . $row['name']
            : $row['name'];
         if (trim((string) ($row['unit'] ?? '')) !== '') {
            $label .= ' (' . $row['unit'] . ')';
         }
         echo "<option value='" . (int) $row['id'] . "' " . ((int) $row['id'] === $value ? 'selected' : '') . ">" . Html::clean($label) . "</option>";
      }

      echo "</select>";
   }
}
