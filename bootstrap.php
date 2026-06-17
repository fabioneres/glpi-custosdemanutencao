<?php
/**
 * -------------------------------------------------------------------------
 * Maintenance Costs plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

if (!defined('PLUGIN_MAINTENANCECOSTS_VERSION')) {
   define('PLUGIN_MAINTENANCECOSTS_VERSION', '0.5.6');
}

if (!defined('PLUGIN_MAINTENANCECOSTS_SCHEMA_VERSION')) {
   define('PLUGIN_MAINTENANCECOSTS_SCHEMA_VERSION', '0.5.6');
}

if (!defined('PLUGIN_MAINTENANCECOSTS_MIN_GLPI_VERSION')) {
   define('PLUGIN_MAINTENANCECOSTS_MIN_GLPI_VERSION', '10.0.18');
}

if (!defined('PLUGIN_MAINTENANCECOSTS_MAX_GLPI_VERSION')) {
   define('PLUGIN_MAINTENANCECOSTS_MAX_GLPI_VERSION', '10.0.99');
}

if (!defined('PLUGIN_MAINTENANCECOSTS_DIR')) {
   define('PLUGIN_MAINTENANCECOSTS_DIR', __DIR__);
}

if (!defined('PLUGIN_MAINTENANCECOSTS_AUTOLOADER')) {
   define('PLUGIN_MAINTENANCECOSTS_AUTOLOADER', true);

   spl_autoload_register(static function(string $class): void {
      $prefix = 'GlpiPlugin\\Maintenancecosts\\';
      if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
         return;
      }

      $relative = substr($class, strlen($prefix));
      $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
      if (file_exists($file)) {
         require_once $file;
      }
   });
}
