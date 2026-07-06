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
   $redirectType = 'sinapi';
   if (!empty($_POST['id']) && $item->getFromDB((int) $_POST['id'])) {
      $redirectType = Config::normalizePriceType((string) ($item->fields['price_type'] ?? 'sinapi'));
   }
   $item->delete($_POST, isset($_POST['purge']));
   Html::redirect($redirectType === 'cotacao_mercado'
      ? Config::pluginUrl('/front/quotationprice.php')
      : Price::getSearchURL());
}

Config::checkRight(Config::RIGHT_PRICES, READ);

$id = (int) ($_GET['id'] ?? 0);
$priceType = Config::normalizePriceType((string) ($_GET['price_type'] ?? 'sinapi'));
if ($id > 0 && $item->getFromDB($id)) {
   $priceType = Config::normalizePriceType((string) ($item->fields['price_type'] ?? 'sinapi'));
   $item = new Price();
}

$title = $priceType === 'cotacao_mercado'
   ? __('Preço Cotação/Mercado', 'maintenancecosts')
   : Price::getTypeName(1);
Html::header($title, $_SERVER['PHP_SELF'], 'plugins', Menu::class);
$item->display(['id' => $id, 'price_type' => $priceType]);
Html::footer();
