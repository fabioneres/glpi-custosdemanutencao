<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Material;
use GlpiPlugin\Maintenancecosts\Menu;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

$item = new Material();

if (isset($_POST['add'])) {
   Config::checkRight(Config::RIGHT_MATERIALS, CREATE);
   $item->add($_POST);
   Html::back();
}

if (isset($_POST['update'])) {
   Config::checkRight(Config::RIGHT_MATERIALS, UPDATE);
   $item->update($_POST);
   Html::back();
}

if (isset($_POST['delete']) || isset($_POST['purge'])) {
   Config::checkRight(Config::RIGHT_MATERIALS, PURGE);
   $item->delete($_POST, isset($_POST['purge']));
   Html::redirect(Material::getSearchURL());
}

Config::checkRight(Config::RIGHT_MATERIALS, READ);

$context = (string) ($_GET['context'] ?? $_POST['context'] ?? '');
$title = $context === 'quote'
   ? __('Material Cotação', 'maintenancecosts')
   : Material::getTypeName(1);
Html::header($title, $_SERVER['PHP_SELF'], 'plugins', Menu::class);
$item->display(['id' => (int) ($_GET['id'] ?? 0), 'context' => $context]);
Html::footer();
