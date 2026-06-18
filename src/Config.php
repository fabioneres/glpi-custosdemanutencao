<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Entity;
use Html;
use Session;

class Config extends CommonDBTM
{
   public static $rightname = 'plugin_maintenancecosts_config';
   public const CONFIG_ID = 1;

   public const RIGHT_MATERIALS   = 'plugin_maintenancecosts_materials';
   public const RIGHT_PRICES      = 'plugin_maintenancecosts_prices';
   public const RIGHT_COSTCENTERS = 'plugin_maintenancecosts_costcenters';
   public const RIGHT_CONSUMPTION = 'plugin_maintenancecosts_consumption';
   public const RIGHT_IMPORT      = 'plugin_maintenancecosts_import';
   public const RIGHT_REPORTS     = 'plugin_maintenancecosts_reports';
   public const RIGHT_CONFIG      = 'plugin_maintenancecosts_config';

   public static function getTypeName($nb = 0)
   {
      return __('Custos de Manutenção', 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-tools';
   }

   public static function getRightNames(): array
   {
      return [
         self::RIGHT_MATERIALS,
         self::RIGHT_PRICES,
         self::RIGHT_COSTCENTERS,
         self::RIGHT_CONSUMPTION,
         self::RIGHT_IMPORT,
         self::RIGHT_REPORTS,
         self::RIGHT_CONFIG,
      ];
   }

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_configs';
   }

   public static function getDefaultSettings(): array
   {
      return [
         'is_enabled'              => 1,
         'costcenter_required'     => 0,
         'allow_manual_unit_price' => 0,
         'default_competence_mode' => 'latest',
         'allowed_itilcategories'  => '',
      ];
   }

   public static function getActiveEntityId(): int
   {
      return (int) Session::getActiveEntity();
   }

   public static function ensureDefaultConfig(): void
   {
      global $DB;

      if (!$DB->tableExists(self::getTable())) {
         return;
      }

      if (countElementsInTable(self::getTable(), ['id' => self::CONFIG_ID]) > 0) {
         return;
      }

      $DB->insert(self::getTable(), self::getDefaultSettings() + [
         'id'            => self::CONFIG_ID,
         'date_creation' => date('Y-m-d H:i:s'),
         'date_mod'      => date('Y-m-d H:i:s'),
      ]);
   }

   public static function getSettings(): array
   {
      global $DB;

      self::ensureDefaultConfig();
      $defaults = self::getDefaultSettings();

      if (!$DB->tableExists(self::getTable())) {
         return $defaults;
      }

      $row = $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => ['id' => self::CONFIG_ID],
         'LIMIT' => 1,
      ])->current();

      return $row ? array_merge($defaults, $row) : $defaults;
   }

   public static function saveSettings(array $input): bool
   {
      global $DB;

      self::ensureDefaultConfig();
      if (!$DB->tableExists(self::getTable())) {
         return false;
      }

      $old = self::getSettings();
      $settings = [
         'is_enabled'              => (int) ($input['is_enabled'] ?? ($old['is_enabled'] ?? 1)) === 1 ? 1 : 0,
         'costcenter_required'     => (int) ($input['costcenter_required'] ?? 0) === 1 ? 1 : 0,
         'allow_manual_unit_price' => (int) ($input['allow_manual_unit_price'] ?? 0) === 1 ? 1 : 0,
         'default_competence_mode' => in_array(($input['default_competence_mode'] ?? 'latest'), ['latest', 'ticket_date', 'consumption_date'], true)
            ? $input['default_competence_mode']
            : 'latest',
         'allowed_itilcategories'  => trim((string) ($input['allowed_itilcategories'] ?? '')),
         'date_mod'                => date('Y-m-d H:i:s'),
      ];

      $ok = $DB->update(self::getTable(), $settings, ['id' => self::CONFIG_ID]);
      AuditLog::record(self::class, self::CONFIG_ID, 'config_update', $old, $settings, '', 0);
      self::clearMenuCache();

      return (bool) $ok;
   }

   public static function getEnabledEntityRows(): array
   {
      global $DB;

      if (!$DB->tableExists(ConfigEntity::getTable())) {
         return [];
      }

      return iterator_to_array($DB->request([
         'FROM'  => ConfigEntity::getTable(),
         'WHERE' => ['is_active' => 1],
         'ORDER' => ['entities_id ASC', 'is_recursive DESC', 'id ASC'],
      ]), false);
   }

   public static function getSelectableEntities(): array
   {
      global $DB;

      $activeEntities = array_values(array_filter(array_map('intval', $_SESSION['glpiactiveentities'] ?? [])));
      if (!$activeEntities) {
         return [];
      }

      return iterator_to_array($DB->request([
         'SELECT' => ['id', 'name', 'completename', 'level'],
         'FROM'   => Entity::getTable(),
         'WHERE'  => ['id' => $activeEntities],
         'ORDER'  => ['completename ASC'],
      ]), false);
   }

   public static function getEntityRule(int $entities_id): array
   {
      $entities_id = (int) $entities_id;
      foreach (self::getEnabledEntityRows() as $row) {
         if ((int) ($row['entities_id'] ?? 0) === $entities_id) {
            return [
               'enabled'     => true,
               'recursive'   => (int) ($row['is_recursive'] ?? 0) === 1,
               'inherited'   => false,
               'inherited_id' => 0,
            ];
         }
      }

      $ancestors = array_reverse(array_map('intval', getAncestorsOf(Entity::getTable(), $entities_id)));
      foreach (self::getEnabledEntityRows() as $row) {
         $rowEntity = (int) ($row['entities_id'] ?? 0);
         if ((int) ($row['is_recursive'] ?? 0) !== 1) {
            continue;
         }

         if (in_array($rowEntity, $ancestors, true)) {
            return [
               'enabled'      => false,
               'recursive'    => false,
               'inherited'    => true,
               'inherited_id' => $rowEntity,
            ];
         }
      }

      return [
         'enabled'      => false,
         'recursive'    => false,
         'inherited'    => false,
         'inherited_id' => 0,
      ];
   }

   public static function saveEntityRule(int $entities_id, array $input): bool
   {
      global $DB;

      $entities_id = (int) $entities_id;
      if ($entities_id < 0 || !Session::haveAccessToEntity($entities_id, true)) {
         return false;
      }

      if (!$DB->tableExists(ConfigEntity::getTable())) {
         return false;
      }

      $old = self::getEntityRule($entities_id);
      $enabled = (int) ($input['plugin_maintenancecosts_entity_enabled'] ?? 0) === 1;
      $recursive = (int) ($input['plugin_maintenancecosts_entity_recursive'] ?? 0) === 1;
      $now = date('Y-m-d H:i:s');
      $userId = (int) Session::getLoginUserID();

      $DB->delete(ConfigEntity::getTable(), ['entities_id' => $entities_id]);

      if ($enabled) {
         $DB->insert(ConfigEntity::getTable(), [
            'entities_id'   => $entities_id,
            'is_recursive'  => $recursive ? 1 : 0,
            'is_active'     => 1,
            'users_id'      => $userId,
            'date_creation' => $now,
            'date_mod'      => $now,
         ]);
      }

      AuditLog::record(
         self::class,
         self::CONFIG_ID,
         'config_entity_rule_update',
         $old,
         self::getEntityRule($entities_id),
         'entities_id=' . $entities_id,
         $entities_id
      );
      self::clearMenuCache();

      return true;
   }

   public static function saveEnabledEntities(array $input): bool
   {
      global $DB;

      if (!$DB->tableExists(ConfigEntity::getTable())) {
         return false;
      }

      $old = self::getEnabledEntityRows();
      $enabled = $input['enabled_entities'] ?? [];
      $recursive = $input['recursive_entities'] ?? [];
      $now = date('Y-m-d H:i:s');
      $userId = (int) Session::getLoginUserID();
      $selectable = self::getSelectableEntities();
      $allowedIds = [];

      foreach ($selectable as $entity) {
         $allowedIds[(int) $entity['id']] = true;
      }

      $DB->delete(ConfigEntity::getTable(), ['id' => ['>', 0]]);

      foreach ($enabled as $entityId => $flag) {
         $entityId = (int) $entityId;
         if ($entityId <= 0 || !isset($allowedIds[$entityId]) || (int) $flag !== 1) {
            continue;
         }

         $DB->insert(ConfigEntity::getTable(), [
            'entities_id'    => $entityId,
            'is_recursive'   => isset($recursive[$entityId]) && (int) $recursive[$entityId] === 1 ? 1 : 0,
            'is_active'      => 1,
            'users_id'       => $userId,
            'date_creation'  => $now,
            'date_mod'       => $now,
         ]);
      }

      AuditLog::record(self::class, self::CONFIG_ID, 'config_entities_update', $old, self::getEnabledEntityRows(), '', 0);
      self::clearMenuCache();

      return true;
   }

   public static function getEnabledEntityMap(): array
   {
      $map = [];
      foreach (self::getEnabledEntityRows() as $row) {
         $map[(int) ($row['entities_id'] ?? 0)] = (int) ($row['is_recursive'] ?? 0);
      }

      return $map;
   }

   public static function isEnabledForEntity(?int $entities_id = null): bool
   {
      $entities_id = $entities_id ?? self::getActiveEntityId();
      if ($entities_id < 0) {
         return false;
      }

      $rows = self::getEnabledEntityRows();
      if (!$rows) {
         return true;
      }

      $ancestors = getAncestorsOf(Entity::getTable(), $entities_id);
      $ancestors[] = $entities_id;
      $ancestors = array_map('intval', $ancestors);

      foreach ($rows as $row) {
         $rowEntity = (int) ($row['entities_id'] ?? -1);
         $rowRecursive = (int) ($row['is_recursive'] ?? 0) === 1;

         if ($rowEntity === $entities_id) {
            return true;
         }

         if ($rowRecursive && in_array($rowEntity, $ancestors, true)) {
            return true;
         }
      }

      return false;
   }

   public static function isEnabledForCurrentEntity(): bool
   {
      return self::isEnabledForEntity(self::getActiveEntityId());
   }

   public static function canViewAny(): bool
   {
      if (self::canAdminConfig()) {
         return true;
      }

      if (!self::isEnabledForCurrentEntity()) {
         return false;
      }

      foreach (self::getRightNames() as $right) {
         if (Session::haveRight($right, READ)
            || Session::haveRight($right, UPDATE)
            || Session::haveRight($right, CREATE)
         ) {
            return true;
         }
      }

      return false;
   }

   public static function canManageMaterials(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (Session::haveRight(self::RIGHT_MATERIALS, UPDATE)
         || self::canAdminConfig());
   }

   public static function canViewMaterials(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (
            Session::haveRight(self::RIGHT_MATERIALS, READ)
            || self::canManageMaterials()
         );
   }

   public static function canManagePrices(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (Session::haveRight(self::RIGHT_PRICES, UPDATE)
         || self::canAdminConfig());
   }

   public static function canViewPrices(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (
            Session::haveRight(self::RIGHT_PRICES, READ)
            || self::canManagePrices()
         );
   }

   public static function canManageCostCenters(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (Session::haveRight(self::RIGHT_COSTCENTERS, UPDATE)
         || self::canAdminConfig());
   }

   public static function canViewCostCenters(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (
            Session::haveRight(self::RIGHT_COSTCENTERS, READ)
            || self::canManageCostCenters()
         );
   }

   public static function canManageConsumption(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (Session::haveRight(self::RIGHT_CONSUMPTION, UPDATE)
         || self::canAdminConfig());
   }

   public static function canViewConsumption(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (
            Session::haveRight(self::RIGHT_CONSUMPTION, READ)
            || self::canManageConsumption()
         );
   }

   public static function canImport(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (Session::haveRight(self::RIGHT_IMPORT, UPDATE)
         || self::canAdminConfig());
   }

   public static function canViewReports(): bool
   {
      return self::isEnabledForCurrentEntity()
         && (
            Session::haveRight(self::RIGHT_REPORTS, READ)
            || self::canAdminConfig()
         );
   }

   public static function canAdminConfig(): bool
   {
      return Session::haveRight(self::RIGHT_CONFIG, UPDATE)
         || Session::haveRight(\Config::$rightname, UPDATE);
   }

   public static function clearMenuCache(): void
   {
      foreach (array_keys($_SESSION ?? []) as $key) {
         if (strpos((string) $key, 'plugin_maintenancecosts_menu_cache_') === 0) {
            unset($_SESSION[$key]);
         }
      }

      unset($_SESSION['glpimenu']);
   }

   public static function checkRight(string $right, int $level): void
   {
      Session::checkLoginUser();
      if ($right !== self::RIGHT_CONFIG && !self::isEnabledForCurrentEntity()) {
         Html::displayRightError(__('Plugin desabilitado para a entidade ativa.', 'maintenancecosts'));
      }
      if (!Session::haveRight($right, $level) && !self::canAdminConfig()) {
         Html::displayRightError();
      }
   }

   public static function pluginUrl(string $path = '', bool $full = true): string
   {
      global $CFG_GLPI;

      $relative = '/plugins/maintenancecosts';
      $base = $full
         ? rtrim((string) ($CFG_GLPI['root_doc'] ?? ''), '/') . $relative
         : $relative;

      if ($path === '') {
         return $base;
      }

      return $base . '/' . ltrim($path, '/');
   }

   public static function formatCurrency(float $value): string
   {
      return 'R$ ' . number_format($value, 2, ',', '.');
   }

   public static function parseDecimal($value): float
   {
      $value = trim((string) $value);
      if ($value === '') {
         return 0.0;
      }

      $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';
      if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
         $value = str_replace('.', '', $value);
         $value = str_replace(',', '.', $value);
      } elseif (substr_count($value, '.') > 1 && preg_match('/^(.*)\.(\d{1,6})$/', $value, $matches)) {
         $value = str_replace('.', '', $matches[1]) . '.' . $matches[2];
      } else {
         $value = str_replace(',', '.', $value);
      }

      return (float) $value;
   }

   public static function formatDecimalInput(float $value, int $decimals = 2): string
   {
      return number_format($value, $decimals, ',', '');
   }

   public static function normalizeCompetence(string $value): string
   {
      $value = trim($value);
      if ($value === '') {
         return '';
      }

      if (preg_match('/^(\d{4})\D*(\d{1,2})/', $value, $matches)) {
         $month = max(1, min(12, (int) $matches[2]));
         return sprintf('%04d-%02d', (int) $matches[1], $month);
      }

      $digits = preg_replace('/\D+/', '', $value) ?? '';
      if (strlen($digits) >= 6) {
         $year = (int) substr($digits, 0, 4);
         $month = max(1, min(12, (int) substr($digits, 4, 2)));
         return sprintf('%04d-%02d', $year, $month);
      }

      return substr($value, 0, 7);
   }

   public static function getPriceTypes(): array
   {
      return [
         'sinapi'          => __('Tabela SINAPI', 'maintenancecosts'),
         'cotacao_mercado' => __('Cotação/Mercado', 'maintenancecosts'),
      ];
   }

   public static function normalizePriceType(string $value): string
   {
      return array_key_exists($value, self::getPriceTypes()) ? $value : 'sinapi';
   }

   public static function getPriceTypeLabel(string $value): string
   {
      $types = self::getPriceTypes();
      return $types[self::normalizePriceType($value)] ?? $value;
   }

   public static function getPluginTabs(): array
   {
      return [
         'config' => [
            'label' => __('Configurações', 'maintenancecosts'),
            'url'   => self::pluginUrl('/front/config.form.php'),
            'icon'  => 'ti ti-settings',
            'show'  => self::canAdminConfig(),
         ],
         'materials' => [
            'label' => Material::getTypeName(2),
            'url'   => self::pluginUrl('/front/material.php'),
            'icon'  => 'ti ti-package',
            'show'  => self::canViewMaterials(),
         ],
         'quote_materials' => [
            'label' => __('Materiais Cotação', 'maintenancecosts'),
            'url'   => self::pluginUrl('/front/quotationmaterial.php'),
            'icon'  => 'ti ti-packages',
            'show'  => self::canViewMaterials(),
         ],
         'origins' => [
            'label' => MaterialOrigin::getTypeName(2),
            'url'   => self::pluginUrl('/front/materialorigin.php'),
            'icon'  => 'ti ti-map-pin',
            'show'  => self::canAdminConfig(),
         ],
         'costcenters' => [
            'label' => CostCenter::getTypeName(2),
            'url'   => self::pluginUrl('/front/costcenter.php'),
            'icon'  => 'ti ti-building-bank',
            'show'  => self::canViewCostCenters(),
         ],
         'prices' => [
            'label' => Price::getTypeName(2),
            'url'   => self::pluginUrl('/front/price.php'),
            'icon'  => 'ti ti-cash',
            'show'  => self::canViewPrices(),
         ],
         'quotes' => [
            'label' => __('Cotação/Mercado', 'maintenancecosts'),
            'url'   => self::pluginUrl('/front/quotationprice.php'),
            'icon'  => 'ti ti-receipt',
            'show'  => self::canViewPrices(),
         ],
         'reports' => [
            'label' => Report::getTypeName(2),
            'url'   => self::pluginUrl('/front/report.php'),
            'icon'  => 'ti ti-report-analytics',
            'show'  => self::canViewReports(),
         ],
         'consumption' => [
            'label' => TicketMaterial::getTypeName(2),
            'url'   => self::pluginUrl('/front/ticketmaterial.php'),
            'icon'  => 'ti ti-clipboard-list',
            'show'  => self::canViewConsumption(),
         ],
         'about' => [
            'label' => __('Sobre', 'maintenancecosts'),
            'url'   => self::pluginUrl('/front/about.php'),
            'icon'  => 'ti ti-info-circle',
            'show'  => true,
         ],
      ];
   }

   public static function renderPluginLayoutStart(string $active): void
   {
      echo "<div class='plugin-maintenancecosts-page'>";
      echo "<div class='plugin-maintenancecosts-shell'>";
      echo "<nav class='plugin-maintenancecosts-side-tabs'>";
      foreach (self::getPluginTabs() as $key => $tab) {
         if (empty($tab['show'])) {
            continue;
         }
         $class = $key === $active ? " class='active'" : '';
         echo "<a{$class} href='" . Html::clean((string) $tab['url']) . "'><i class='" . Html::clean((string) $tab['icon']) . "'></i> " . Html::clean((string) $tab['label']) . "</a>";
      }
      echo "</nav><main class='plugin-maintenancecosts-main'>";
   }

   public static function renderPluginLayoutEnd(): void
   {
      echo "</main></div></div>";
   }
}
