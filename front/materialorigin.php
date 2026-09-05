<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\MaterialOrigin;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Config::checkRight(Config::RIGHT_CONFIG, READ);

Html::header(MaterialOrigin::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
Config::renderPluginLayoutStart('origins');

echo "<div class='center mb-3'>";
echo "<a class='btn btn-primary' href='" . Html::clean(MaterialOrigin::getFormURL()) . "'>" . __('Adicionar', 'maintenancecosts') . "</a>";
echo "</div>";

Search::show(MaterialOrigin::class);

Config::renderPluginLayoutEnd();
Html::footer();
