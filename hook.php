<?php
/**
 * -------------------------------------------------------------------------
 * Maintenance Costs plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Installer;
use GlpiPlugin\Maintenancecosts\Profile;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

require_once __DIR__ . '/bootstrap.php';

function plugin_maintenancecosts_install(): bool {
   return Installer::install();
}

function plugin_maintenancecosts_upgrade($old_version): bool {
   return Installer::install();
}

function plugin_maintenancecosts_uninstall(): bool {
   $result = Installer::uninstall();
   ProfileRight::deleteProfileRights(Config::getRightNames());
   Profile::removeRightsFromSession();

   return $result;
}
