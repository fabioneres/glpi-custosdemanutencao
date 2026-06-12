<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\Price;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

$item = new Price();

if (isset($_POST['add'])) {
   Config::checkRight(Config::RIGHT_PRICES, CREATE);
   $item->add($_POST);
   Html::back();
}

if (isset($_POST['update'])) {
   Config::checkRight(Config::RIGHT_PRICES, UPDATE);
   $item->update($_POST);
   Html::back();
}

if (isset($_POST['delete']) || isset($_POST['purge'])) {
   Config::checkRight(Config::RIGHT_PRICES, PURGE);
   $item->delete($_POST, isset($_POST['purge']));
   Html::redirect(Price::getSearchURL());
}

Config::checkRight(Config::RIGHT_PRICES, READ);

Html::header(Price::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
$item->display(['id' => (int) ($_GET['id'] ?? 0)]);
Html::footer();
