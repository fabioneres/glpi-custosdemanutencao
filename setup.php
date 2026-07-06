<?php
/**
 * -------------------------------------------------------------------------
 * Maintenance Costs plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;
use GlpiPlugin\Maintenancecosts\AuditLog;
use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\CostCenter;
use GlpiPlugin\Maintenancecosts\CostCenterLegacy;
use GlpiPlugin\Maintenancecosts\Exporter;
use GlpiPlugin\Maintenancecosts\FormcreatorCostCenterSync;
use GlpiPlugin\Maintenancecosts\ImportBatch;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\MaterialOrigin;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\Price;
use GlpiPlugin\Maintenancecosts\PriceHistory;
use GlpiPlugin\Maintenancecosts\Profile;
use GlpiPlugin\Maintenancecosts\Report;
use GlpiPlugin\Maintenancecosts\TicketCostCenter;
use GlpiPlugin\Maintenancecosts\TicketMaterial;
use GlpiPlugin\Maintenancecosts\TicketTab;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

require_once __DIR__ . '/bootstrap.php';

function plugin_init_maintenancecosts(): void {
   global $CFG_GLPI;
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['maintenancecosts'] = true;

   Plugin::loadLang('maintenancecosts');

   if (!Plugin::isPluginActive('maintenancecosts')) {
      return;
   }

   Plugin::registerClass(Profile::class, ['addtabon' => [\Profile::class]]);
   Plugin::registerClass(Material::class);
   Plugin::registerClass(MaterialOrigin::class);
   Plugin::registerClass(Price::class);
   Plugin::registerClass(PriceHistory::class);
   Plugin::registerClass(CostCenter::class);
   Plugin::registerClass(CostCenterLegacy::class);
   Plugin::registerClass(FormcreatorCostCenterSync::class);
   Plugin::registerClass(TicketCostCenter::class, ['addtabon' => [\Ticket::class]]);
   Plugin::registerClass(TicketMaterial::class);
   Plugin::registerClass(ImportBatch::class);
   Plugin::registerClass(AuditLog::class);
   Plugin::registerClass(Exporter::class);
   Plugin::registerClass(Config::class);
   Plugin::registerClass(\GlpiPlugin\Maintenancecosts\ConfigEntity::class, ['addtabon' => [\Entity::class]]);
   Plugin::registerClass(Menu::class);
   Plugin::registerClass(Report::class);
   Plugin::registerClass(TicketTab::class, ['addtabon' => [\Ticket::class]]);

   // O itemtype legado tem nome irregular para as heuristicas do GLPI
   // (CostCenterLegacy -> costcenterlegacies). Registramos o mapeamento
   // explicito para garantir dropdowns, Search e integrações como FormCreator.
   $CFG_GLPI['glpitablesitemtype'][CostCenterLegacy::class] = CostCenterLegacy::getTable();
   $CFG_GLPI['glpiitemtypetables'][CostCenterLegacy::getTable()] = CostCenterLegacy::class;

   $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['maintenancecosts'][] = 'js/ticketmaterial-v2.js';
   $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT_ANONYMOUS_PAGE]['maintenancecosts'][] = 'js/ticketmaterial-v2.js';
   $PLUGIN_HOOKS[Hooks::ADD_CSS]['maintenancecosts'][] = 'css/maintenancecosts.css';
   $PLUGIN_HOOKS[Hooks::ADD_CSS_ANONYMOUS_PAGE]['maintenancecosts'][] = 'css/maintenancecosts.css';
   $PLUGIN_HOOKS['formcreator_get_glpi_object_types']['maintenancecosts'] = 'plugin_maintenancecosts_formcreator_get_glpi_object_types';
   $PLUGIN_HOOKS['item_add']['maintenancecosts']['Item_Ticket'] = [
      FormcreatorCostCenterSync::class,
      'itemAdded',
   ];

   if (Session::getLoginUserID()) {
      Profile::initProfile();
      $is_recursive = (int) (($_SESSION['glpiactive_entity_recursive'] ?? false) ? 1 : 0);

      $menu_cache_key = 'plugin_maintenancecosts_menu_cache_'
         . PLUGIN_MAINTENANCECOSTS_VERSION
         . '_'
         . (int) Session::getActiveEntity()
         . '_'
         . $is_recursive
         . '_plugins_v2';
      if (($_SESSION[$menu_cache_key] ?? 0) !== 1) {
         unset($_SESSION['glpimenu']);
         $_SESSION[$menu_cache_key] = 1;
      }
   }

   if (Config::canViewAny()) {
      $PLUGIN_HOOKS[Hooks::MENU_TOADD]['maintenancecosts'] = [
         'plugins' => Menu::class,
      ];
   }

   if (Config::canAdminConfig()) {
      $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['maintenancecosts'] = 'front/config.form.php';
   }
}

function plugin_version_maintenancecosts(): array {
   return [
      'name'         => __('Custos de Manutenção', 'maintenancecosts'),
      'version'      => PLUGIN_MAINTENANCECOSTS_VERSION,
      'author'       => 'Fabio Neres',
      'license'      => 'GPLv3+',
      'homepage'     => '',
      'icon'         => 'pics/icon.png',
      'picture'      => 'pics/logo.png',
      'requirements' => [
         'glpi' => [
            'min' => PLUGIN_MAINTENANCECOSTS_MIN_GLPI_VERSION,
            'max' => PLUGIN_MAINTENANCECOSTS_MAX_GLPI_VERSION,
         ],
      ],
   ];
}

function plugin_maintenancecosts_check_prerequisites(): bool {
   if (version_compare(GLPI_VERSION, PLUGIN_MAINTENANCECOSTS_MIN_GLPI_VERSION, 'lt')) {
      if (method_exists(Plugin::class, 'messageIncompatible')) {
         Plugin::messageIncompatible(
            'core',
            PLUGIN_MAINTENANCECOSTS_MIN_GLPI_VERSION,
            PLUGIN_MAINTENANCECOSTS_MAX_GLPI_VERSION
         );
      }
      return false;
   }

   if (version_compare(GLPI_VERSION, PLUGIN_MAINTENANCECOSTS_MAX_GLPI_VERSION, 'gt')) {
      if (method_exists(Plugin::class, 'messageIncompatible')) {
         Plugin::messageIncompatible(
            'core',
            PLUGIN_MAINTENANCECOSTS_MIN_GLPI_VERSION,
            PLUGIN_MAINTENANCECOSTS_MAX_GLPI_VERSION
         );
      }
      return false;
   }

   return true;
}

function plugin_maintenancecosts_check_config(): bool {
   return true;
}
