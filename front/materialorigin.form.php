<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\MaterialOrigin;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

$item = new MaterialOrigin();

if (isset($_POST['add'])) {
   Config::checkRight(Config::RIGHT_CONFIG, UPDATE);
   $item->add($_POST);
   Html::redirect(MaterialOrigin::getSearchURL());
}

if (isset($_POST['update'])) {
   Config::checkRight(Config::RIGHT_CONFIG, UPDATE);
   $item->update($_POST);
   Html::redirect(MaterialOrigin::getSearchURL());
}

if (isset($_POST['delete']) || isset($_POST['purge'])) {
   Config::checkRight(Config::RIGHT_CONFIG, UPDATE);
   $item->delete($_POST, isset($_POST['purge']));
   Html::redirect(MaterialOrigin::getSearchURL());
}

Config::checkRight(Config::RIGHT_CONFIG, READ);

Html::header(MaterialOrigin::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
$item->display(['id' => (int) ($_GET['id'] ?? 0)]);
Html::footer();
