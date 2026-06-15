<?php
/**
 * -------------------------------------------------------------------------
 * Maintenance Costs plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use DBmysql;
use Session;
use Throwable;
use Toolbox;

class Installer
{
   public static function install(): bool
   {
      /** @var DBmysql $DB */
      global $DB;

      try {
         $sqlfile = PLUGIN_MAINTENANCECOSTS_DIR . '/install/install.sql';
         if (file_exists($sqlfile)) {
         $DB->runFile($sqlfile);
         }

         self::ensureSchema();
         Config::ensureDefaultConfig();
         self::ensureDefaultCostCenter();
         TicketMaterial::syncAllTicketCosts();
      } catch (Throwable $e) {
         Toolbox::logInFile(
            'plugin_maintenancecosts',
            'Install failed: ' . $e->getMessage() . PHP_EOL
         );
         Session::addMessageAfterRedirect(
            __('Custos de Manutenção: falha ao criar tabelas. Verifique os logs do GLPI.', 'maintenancecosts'),
            false,
            ERROR
         );
         return false;
      }

      Profile::installRights();
      Profile::initProfile();

      if (isset($_SESSION['glpiactiveprofile']['id'])) {
         Profile::createFirstAccess((int) $_SESSION['glpiactiveprofile']['id']);
      }

      \Config::setConfigurationValues('plugin:maintenancecosts', [
         'dbversion' => PLUGIN_MAINTENANCECOSTS_SCHEMA_VERSION,
      ]);

      return true;
   }

   private static function ensureSchema(): void
   {
      /** @var DBmysql $DB */
      global $DB;

      $migration = new \Migration(PLUGIN_MAINTENANCECOSTS_SCHEMA_VERSION);

      if ($DB->tableExists(Material::getTable())) {
         self::ensureField($migration, Material::getTable(), 'entities_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, Material::getTable(), 'is_recursive', 'tinyint NOT NULL DEFAULT 0');
         self::ensureField($migration, Material::getTable(), 'is_active', 'tinyint NOT NULL DEFAULT 1');
         self::ensureField($migration, Material::getTable(), 'date_creation', 'timestamp NULL DEFAULT NULL');
         self::ensureField($migration, Material::getTable(), 'date_mod', 'timestamp NULL DEFAULT NULL');
      }

      if ($DB->tableExists(Price::getTable())) {
         self::ensureField($migration, Price::getTable(), 'comment', 'text NULL');
         self::ensureField($migration, Price::getTable(), 'quote_quantity', 'decimal(20,6) NOT NULL DEFAULT 0.000000');
         self::ensureField($migration, Price::getTable(), 'quote_price_1', 'decimal(20,6) NOT NULL DEFAULT 0.000000');
         self::ensureField($migration, Price::getTable(), 'quote_price_2', 'decimal(20,6) NOT NULL DEFAULT 0.000000');
         self::ensureField($migration, Price::getTable(), 'quote_price_3', 'decimal(20,6) NOT NULL DEFAULT 0.000000');
         self::ensureField($migration, Price::getTable(), 'price_type', "varchar(32) NOT NULL DEFAULT 'sinapi'");
         self::ensureField($migration, Price::getTable(), 'date_mod', 'timestamp NULL DEFAULT NULL');
      }

      if ($DB->tableExists(ImportBatch::getTable())) {
         self::ensureField($migration, ImportBatch::getTable(), 'price_type', "varchar(32) NOT NULL DEFAULT 'sinapi'");
      }

      self::ensurePriceHistoryTable();

      self::ensureMaterialOriginTable();
      MaterialOrigin::ensureDefaults();

      if ($DB->tableExists(CostCenter::getTable())) {
         self::ensureField($migration, CostCenter::getTable(), 'entities_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, CostCenter::getTable(), 'is_recursive', 'tinyint NOT NULL DEFAULT 0');
         self::ensureField($migration, CostCenter::getTable(), 'address', 'text NULL');
         self::ensureField($migration, CostCenter::getTable(), 'floor', "varchar(64) NOT NULL DEFAULT ''");
         self::ensureField($migration, CostCenter::getTable(), 'campus', "varchar(255) NOT NULL DEFAULT ''");
         self::ensureField($migration, CostCenter::getTable(), 'department', "varchar(255) NOT NULL DEFAULT ''");
         self::ensureField($migration, CostCenter::getTable(), 'usage_type', "varchar(255) NOT NULL DEFAULT ''");
         self::ensureField($migration, CostCenter::getTable(), 'comment', 'text NULL');
         self::ensureField($migration, CostCenter::getTable(), 'date_creation', 'timestamp NULL DEFAULT NULL');
         self::ensureField($migration, CostCenter::getTable(), 'date_mod', 'timestamp NULL DEFAULT NULL');
      }

      if ($DB->tableExists(TicketMaterial::getTable())) {
         self::ensureField($migration, TicketMaterial::getTable(), 'entities_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'itemtype', "varchar(100) NOT NULL DEFAULT 'Ticket'");
         self::ensureField($migration, TicketMaterial::getTable(), 'items_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'plugin_maintenancecosts_materialorigins_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'contracts_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'contractcosts_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'ticketcosts_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'price_type', "varchar(32) NOT NULL DEFAULT 'sinapi'");
         self::ensureField($migration, TicketMaterial::getTable(), 'deleted_at', 'timestamp NULL DEFAULT NULL');
         self::ensureField($migration, TicketMaterial::getTable(), 'deleted_by', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, TicketMaterial::getTable(), 'delete_reason', 'text NULL');
      }

      self::ensureConfigTable();
      self::ensureAuditLogTable();

      if ($DB->tableExists(Config::getTable())) {
         self::ensureField($migration, Config::getTable(), 'is_enabled', 'tinyint NOT NULL DEFAULT 1');
         self::ensureField($migration, Config::getTable(), 'costcenter_required', 'tinyint NOT NULL DEFAULT 0');
         self::ensureField($migration, Config::getTable(), 'allow_manual_unit_price', 'tinyint NOT NULL DEFAULT 0');
         self::ensureField($migration, Config::getTable(), 'default_competence_mode', "varchar(32) NOT NULL DEFAULT 'latest'");
         self::ensureField($migration, Config::getTable(), 'allowed_itilcategories', 'text NULL');
      }

      if ($DB->tableExists(AuditLog::getTable())) {
         self::ensureField($migration, AuditLog::getTable(), 'itemtype', "varchar(100) NOT NULL DEFAULT ''");
         self::ensureField($migration, AuditLog::getTable(), 'items_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, AuditLog::getTable(), 'entities_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, AuditLog::getTable(), 'action', "varchar(64) NOT NULL DEFAULT ''");
         self::ensureField($migration, AuditLog::getTable(), 'users_id', 'int unsigned NOT NULL DEFAULT 0');
         self::ensureField($migration, AuditLog::getTable(), 'old_value', 'longtext NULL');
         self::ensureField($migration, AuditLog::getTable(), 'new_value', 'longtext NULL');
         self::ensureField($migration, AuditLog::getTable(), 'comment', 'text NULL');
         self::ensureField($migration, AuditLog::getTable(), 'date_creation', 'timestamp NULL DEFAULT NULL');
      }

      $migration->executeMigration();
   }

   private static function ensureConfigTable(): void
   {
      global $DB;

      if ($DB->tableExists(Config::getTable())) {
         return;
      }

      $DB->doQuery(
         "CREATE TABLE IF NOT EXISTS `" . Config::getTable() . "` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `is_enabled` tinyint NOT NULL DEFAULT '1',
            `costcenter_required` tinyint NOT NULL DEFAULT '0',
            `allow_manual_unit_price` tinyint NOT NULL DEFAULT '0',
            `default_competence_mode` varchar(32) NOT NULL DEFAULT 'latest',
            `allowed_itilcategories` text NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
      );
   }

   private static function ensureAuditLogTable(): void
   {
      global $DB;

      if ($DB->tableExists(AuditLog::getTable())) {
         return;
      }

      $DB->doQuery(
         "CREATE TABLE IF NOT EXISTS `" . AuditLog::getTable() . "` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `itemtype` varchar(100) NOT NULL DEFAULT '',
            `items_id` int unsigned NOT NULL DEFAULT '0',
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `action` varchar(64) NOT NULL DEFAULT '',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `old_value` longtext NULL,
            `new_value` longtext NULL,
            `comment` text NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_item` (`itemtype`, `items_id`),
            KEY `idx_entity` (`entities_id`),
            KEY `idx_action` (`action`),
            KEY `idx_user` (`users_id`),
            KEY `idx_date_creation` (`date_creation`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
      );
   }

   private static function ensureMaterialOriginTable(): void
   {
      global $DB;

      if ($DB->tableExists(MaterialOrigin::getTable())) {
         return;
      }

      $DB->doQuery(
         "CREATE TABLE IF NOT EXISTS `" . MaterialOrigin::getTable() . "` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `is_active` tinyint NOT NULL DEFAULT '1',
            `comment` text NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_active` (`is_active`),
            KEY `idx_name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
      );
   }

   private static function ensurePriceHistoryTable(): void
   {
      global $DB;

      if ($DB->tableExists(PriceHistory::getTable())) {
         return;
      }

      $DB->doQuery(
         "CREATE TABLE IF NOT EXISTS `" . PriceHistory::getTable() . "` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_maintenancecosts_materials_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_maintenancecosts_prices_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_maintenancecosts_importbatches_id` int unsigned NOT NULL DEFAULT '0',
            `competence` varchar(7) NOT NULL DEFAULT '',
            `price_type` varchar(32) NOT NULL DEFAULT 'sinapi',
            `old_unit_price` decimal(20,6) NOT NULL DEFAULT '0.000000',
            `new_unit_price` decimal(20,6) NOT NULL DEFAULT '0.000000',
            `source` varchar(255) NOT NULL DEFAULT '',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `justification` text NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_material` (`plugin_maintenancecosts_materials_id`),
            KEY `idx_price` (`plugin_maintenancecosts_prices_id`),
            KEY `idx_importbatch` (`plugin_maintenancecosts_importbatches_id`),
            KEY `idx_competence` (`competence`),
            KEY `idx_price_type` (`price_type`),
            KEY `idx_user` (`users_id`),
            KEY `idx_date_creation` (`date_creation`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
      );
   }

   private static function ensureDefaultCostCenter(): void
   {
      global $DB;

      if (!$DB->tableExists(CostCenter::getTable())) {
         return;
      }

      $existing = $DB->request([
         'FROM'  => CostCenter::getTable(),
         'WHERE' => ['code' => 'GERAL'],
         'LIMIT' => 1,
      ])->current();

      if ($existing) {
         if (!(int) ($existing['is_active'] ?? 0)) {
            $DB->update(CostCenter::getTable(), ['is_active' => 1], ['id' => (int) $existing['id']]);
         }
         return;
      }

      $DB->insert(CostCenter::getTable(), [
         'entities_id'   => 0,
         'is_recursive'  => 1,
         'code'          => 'GERAL',
         'name'          => 'Centro de custo geral',
         'address'       => '',
         'description'   => 'Centro de custo padrão criado automaticamente pelo plugin.',
         'locations_id'  => 0,
         'users_id'      => 0,
         'is_active'     => 1,
         'comment'       => '',
         'date_creation' => date('Y-m-d H:i:s'),
         'date_mod'      => date('Y-m-d H:i:s'),
      ]);
   }

   private static function ensureField(\Migration $migration, string $table, string $field, string $definition): void
   {
      /** @var DBmysql $DB */
      global $DB;

      if (!$DB->fieldExists($table, $field)) {
         $migration->addField($table, $field, $definition);
      }
   }

   public static function uninstall(): bool
   {
      /** @var DBmysql $DB */
      global $DB;

      try {
         $sqlfile = PLUGIN_MAINTENANCECOSTS_DIR . '/install/uninstall.sql';
         if (file_exists($sqlfile)) {
            $DB->runFile($sqlfile);
         }
      } catch (Throwable $e) {
         Toolbox::logInFile(
            'plugin_maintenancecosts',
            'Uninstall failed: ' . $e->getMessage() . PHP_EOL
         );
         return false;
      }

      \Config::deleteConfigurationValues('plugin:maintenancecosts', ['dbversion']);
      return true;
   }
}
