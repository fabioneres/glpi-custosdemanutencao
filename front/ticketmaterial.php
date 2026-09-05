<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\TicketMaterial;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_CONSUMPTION, READ);

Html::header(TicketMaterial::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('consumption');

// The global listing presents active consumptions. Canceled records remain available by filter.
$_GET['criteria'] = is_array($_GET['criteria'] ?? null) ? $_GET['criteria'] : [];
$_GET['criteria'][] = [
   'link'       => 'AND',
   'field'      => 18,
   'searchtype' => 'equals',
   'value'      => 0,
];

Search::show(TicketMaterial::class);

Config::renderPluginLayoutEnd();
Html::footer();
