<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;

class AuditLog extends CommonDBTM
{
   public static $rightname = Config::RIGHT_CONFIG;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_auditlogs';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Log de auditoria', 'Logs de auditoria', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-history';
   }

   public static function record(string $itemtype, int $items_id, string $action, array $old = [], array $new = [], string $comment = '', int $entities_id = 0): void
   {
      global $DB;

      if (!$DB->tableExists(self::getTable())) {
         return;
      }

      $log = new self();
      $log->add([
         'itemtype'      => $itemtype,
         'items_id'      => $items_id,
         'entities_id'   => $entities_id,
         'action'        => $action,
         'users_id'      => (int) ($_SESSION['glpiID'] ?? 0),
         'old_value'     => count($old) ? json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
         'new_value'     => count($new) ? json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
         'comment'       => $comment,
         'date_creation' => date('Y-m-d H:i:s'),
      ]);
   }
}
