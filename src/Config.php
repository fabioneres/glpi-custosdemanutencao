<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
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

      return (bool) $ok;
   }

   public static function canViewAny(): bool
   {
      foreach (self::getRightNames() as $right) {
         if (Session::haveRight($right, READ)
            || Session::haveRight($right, UPDATE)
            || Session::haveRight($right, CREATE)
         ) {
            return true;
         }
      }

      return self::canAdminConfig();
   }

   public static function canManageMaterials(): bool
   {
      return Session::haveRight(self::RIGHT_MATERIALS, UPDATE)
         || self::canAdminConfig();
   }

   public static function canViewMaterials(): bool
   {
      return Session::haveRight(self::RIGHT_MATERIALS, READ)
         || self::canManageMaterials();
   }

   public static function canManagePrices(): bool
   {
      return Session::haveRight(self::RIGHT_PRICES, UPDATE)
         || self::canAdminConfig();
   }

   public static function canViewPrices(): bool
   {
      return Session::haveRight(self::RIGHT_PRICES, READ)
         || self::canManagePrices();
   }

   public static function canManageCostCenters(): bool
   {
      return Session::haveRight(self::RIGHT_COSTCENTERS, UPDATE)
         || self::canAdminConfig();
   }

   public static function canViewCostCenters(): bool
   {
      return Session::haveRight(self::RIGHT_COSTCENTERS, READ)
         || self::canManageCostCenters();
   }

   public static function canManageConsumption(): bool
   {
      return Session::haveRight(self::RIGHT_CONSUMPTION, UPDATE)
         || self::canAdminConfig();
   }

   public static function canViewConsumption(): bool
   {
      return Session::haveRight(self::RIGHT_CONSUMPTION, READ)
         || self::canManageConsumption();
   }

   public static function canImport(): bool
   {
      return Session::haveRight(self::RIGHT_IMPORT, UPDATE)
         || self::canAdminConfig();
   }

   public static function canViewReports(): bool
   {
      return Session::haveRight(self::RIGHT_REPORTS, READ)
         || self::canAdminConfig();
   }

   public static function canAdminConfig(): bool
   {
      return Session::haveRight(self::RIGHT_CONFIG, UPDATE)
         || Session::haveRight(\Config::$rightname, UPDATE);
   }

   public static function checkRight(string $right, int $level): void
   {
      Session::checkLoginUser();
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
