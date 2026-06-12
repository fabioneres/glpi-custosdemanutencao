<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\Menu;
use GlpiPlugin\Maintenancecosts\TicketMaterial;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

$item = new TicketMaterial();

if (isset($_POST['cancel'])) {
   Config::checkRight(Config::RIGHT_CONSUMPTION, UPDATE);
   TicketMaterial::cancel((int) ($_POST['id'] ?? 0), (string) ($_POST['delete_reason'] ?? ''));
   if (!empty($_POST['tickets_id'])) {
      Html::redirect($CFG_GLPI['root_doc'] . '/front/ticket.form.php?id=' . (int) $_POST['tickets_id']);
   }
   Html::redirect(TicketMaterial::getSearchURL());
}

if (isset($_POST['add'])) {
   Config::checkRight(Config::RIGHT_CONSUMPTION, CREATE);
   $item->add($_POST);
   if (!empty($_POST['tickets_id'])) {
      Html::redirect($CFG_GLPI['root_doc'] . '/front/ticket.form.php?id=' . (int) $_POST['tickets_id']);
   }
   Html::back();
}

if (isset($_POST['update'])) {
   Config::checkRight(Config::RIGHT_CONSUMPTION, UPDATE);
   $item->update($_POST);
   if (!empty($_POST['tickets_id'])) {
      Html::redirect($CFG_GLPI['root_doc'] . '/front/ticket.form.php?id=' . (int) $_POST['tickets_id']);
   }
   Html::back();
}

if (isset($_POST['delete']) || isset($_POST['purge'])) {
   Config::checkRight(Config::RIGHT_CONSUMPTION, PURGE);
   $item->delete($_POST, isset($_POST['purge']));
   Html::redirect(TicketMaterial::getSearchURL());
}

Config::checkRight(Config::RIGHT_CONSUMPTION, READ);

Html::header(TicketMaterial::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', Menu::class);
$item->showForm((int) ($_GET['id'] ?? 0), [
   'tickets_id' => (int) ($_GET['tickets_id'] ?? 0),
]);
Html::footer();
