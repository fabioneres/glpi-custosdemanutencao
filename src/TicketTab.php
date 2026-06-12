<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use CommonGLPI;
use Session;
use Ticket;

class TicketTab extends CommonDBTM
{
   protected static $notable = true;

   public static function getTypeName($nb = 0)
   {
      return __('Materiais Consumidos', 'maintenancecosts');
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if (!$item instanceof Ticket || $withtemplate) {
         return '';
      }

      if (!Config::canViewConsumption()) {
         return '';
      }

      $count = 0;
      if (!empty($_SESSION['glpishow_count_on_tabs'])) {
         $count = countElementsInTable(TicketMaterial::getTable(), [
            'tickets_id'  => (int) $item->getID(),
            'is_deleted' => 0,
         ]);
      }

      return self::createTabEntry(self::getTypeName(), $count, $item::getType(), TicketMaterial::getIcon());
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item instanceof Ticket) {
         TicketMaterial::showForTicket($item);
      }

      return true;
   }
}
