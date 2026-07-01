<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Session;
use Ticket;

class TicketCostCenter extends CommonDBTM
{
   public static $rightname = Config::RIGHT_CONSUMPTION;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_ticketcostcenters';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Centro de custo do chamado', 'Centros de custo do chamado', $nb, 'maintenancecosts');
   }

   public static function getSelection(int $tickets_id): array
   {
      global $DB;

      $selection = self::getEmptySelection($tickets_id);
      if ($tickets_id <= 0 || !$DB->tableExists(self::getTable())) {
         return $selection;
      }

      $row = $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => ['tickets_id' => $tickets_id],
         'LIMIT' => 1,
      ])->current();

      if (!is_array($row)) {
         return $selection;
      }

      $selection = array_merge($selection, $row);
      $selection['id'] = (int) ($selection['id'] ?? 0);
      $selection['tickets_id'] = (int) ($selection['tickets_id'] ?? 0);
      $selection['entities_id'] = (int) ($selection['entities_id'] ?? 0);
      $selection['plugin_maintenancecosts_costcenters_id'] = (int) ($selection['plugin_maintenancecosts_costcenters_id'] ?? 0);
      $selection['costcenter_source'] = TicketMaterial::normalizeCostCenterSource((string) ($selection['costcenter_source'] ?? 'legacy'));

      return $selection;
   }

   public static function getDisplayLabel(int $tickets_id): string
   {
      $selection = self::getSelection($tickets_id);
      return TicketMaterial::getCostCenterDisplayName(
         (int) ($selection['plugin_maintenancecosts_costcenters_id'] ?? 0),
         (string) ($selection['costcenter_source'] ?? 'legacy')
      );
   }

   public static function saveForTicket(int $tickets_id, int $costcenter_id, string $source, int $entities_id = 0): bool
   {
      global $DB;

      $tickets_id = (int) $tickets_id;
      $costcenter_id = (int) $costcenter_id;
      $source = TicketMaterial::normalizeCostCenterSource($source);

      if ($tickets_id <= 0) {
         return false;
      }

      if ($costcenter_id <= 0) {
         return self::clearForTicket($tickets_id);
      }

      if (!self::validateSelectionChange($tickets_id, $costcenter_id, $source)) {
         return false;
      }

      $ticket = new Ticket();
      if (!$ticket->getFromDB($tickets_id)) {
         return false;
      }

      $entities_id = $entities_id > 0 ? $entities_id : (int) ($ticket->fields['entities_id'] ?? 0);
      $current = self::getSelection($tickets_id);
      $payload = [
         'tickets_id'                              => $tickets_id,
         'entities_id'                             => $entities_id,
         'plugin_maintenancecosts_costcenters_id' => $costcenter_id,
         'costcenter_source'                       => $source,
         'users_id'                                => (int) ($_SESSION['glpiID'] ?? 0),
         'date_mod'                                => date('Y-m-d H:i:s'),
      ];

      $ok = false;
      if ((int) ($current['id'] ?? 0) > 0) {
         $ok = (bool) $DB->update(self::getTable(), $payload, ['id' => (int) $current['id']]);
         $itemId = (int) $current['id'];
      } else {
         $payload['date_creation'] = date('Y-m-d H:i:s');
         $ok = (bool) $DB->insert(self::getTable(), $payload);
         $itemId = $ok ? (int) $DB->insertId() : 0;
      }

      if ($ok && $itemId > 0) {
         AuditLog::record(
            self::class,
            $itemId,
            'ticket_costcenter_save',
            $current,
            $payload,
            __('Centro de custo vinculado ao chamado.', 'maintenancecosts'),
            $entities_id
         );
      }

      return $ok;
   }

   public static function clearForTicket(int $tickets_id): bool
   {
      global $DB;

      $tickets_id = (int) $tickets_id;
      if ($tickets_id <= 0) {
         return false;
      }

      if (count(self::getMaterialCostCenters($tickets_id)) > 0) {
         Session::addMessageAfterRedirect(
            __('Não é possível remover o centro de custo do chamado enquanto houver materiais consumidos ativos vinculados.', 'maintenancecosts'),
            false,
            ERROR
         );
         return false;
      }

      $current = self::getSelection($tickets_id);
      if ((int) ($current['id'] ?? 0) <= 0) {
         return true;
      }

      $ok = (bool) $DB->delete(self::getTable(), ['id' => (int) $current['id']]);
      if ($ok) {
         AuditLog::record(
            self::class,
            (int) $current['id'],
            'ticket_costcenter_clear',
            $current,
            [],
            __('Centro de custo removido do chamado.', 'maintenancecosts'),
            (int) ($current['entities_id'] ?? 0)
         );
      }

      return $ok;
   }

   public static function validateMaterialSelection(int $tickets_id, int $costcenter_id, string $source, int $ignore_ticketmaterial_id = 0): bool
   {
      $tickets_id = (int) $tickets_id;
      $costcenter_id = (int) $costcenter_id;
      $source = TicketMaterial::normalizeCostCenterSource($source);

      if ($tickets_id <= 0) {
         return true;
      }

      $selection = self::getSelection($tickets_id);
      $linkedId = (int) ($selection['plugin_maintenancecosts_costcenters_id'] ?? 0);
      if ($linkedId > 0) {
         if ($costcenter_id <= 0 || $linkedId !== $costcenter_id || (string) ($selection['costcenter_source'] ?? 'legacy') !== $source) {
            Session::addMessageAfterRedirect(
               __('O centro de custo do material deve ser o mesmo centro de custo vinculado ao chamado.', 'maintenancecosts'),
               false,
               ERROR
            );
            return false;
         }

         return true;
      }

      $summaries = self::getMaterialCostCenters($tickets_id, $ignore_ticketmaterial_id);
      if (!count($summaries)) {
         return true;
      }

      if (count($summaries) > 1) {
         Session::addMessageAfterRedirect(
            __('Este chamado possui materiais ativos vinculados a centros de custo diferentes. Revise os lancamentos antes de continuar.', 'maintenancecosts'),
            false,
            ERROR
         );
         return false;
      }

      $existing = $summaries[0];
      if (
         $costcenter_id <= 0
         || (int) $existing['plugin_maintenancecosts_costcenters_id'] !== $costcenter_id
         || TicketMaterial::normalizeCostCenterSource((string) ($existing['costcenter_source'] ?? 'legacy')) !== $source
      ) {
         Session::addMessageAfterRedirect(
            __('Todos os materiais ativos do chamado devem permanecer no mesmo centro de custo.', 'maintenancecosts'),
            false,
            ERROR
         );
         return false;
      }

      return true;
   }

   public static function syncFromTicketMaterials(int $tickets_id = 0): void
   {
      global $DB;

      if (!$DB->tableExists(self::getTable()) || !$DB->tableExists(TicketMaterial::getTable())) {
         return;
      }

      $tickets = [];
      if ($tickets_id > 0) {
         $tickets[] = $tickets_id;
      } else {
         $iterator = $DB->request([
            'SELECT'   => ['tickets_id'],
            'DISTINCT' => true,
            'FROM'     => TicketMaterial::getTable(),
            'WHERE'    => [
               'tickets_id'                              => ['>', 0],
               'is_deleted'                              => 0,
               'plugin_maintenancecosts_costcenters_id' => ['>', 0],
            ],
         ]);

         foreach ($iterator as $row) {
            $ticketId = (int) ($row['tickets_id'] ?? 0);
            if ($ticketId > 0) {
               $tickets[] = $ticketId;
            }
         }
      }

      foreach (array_unique($tickets) as $ticketId) {
         $summary = self::getMaterialCostCenters($ticketId);
         if (count($summary) !== 1) {
            continue;
         }

         $current = self::getSelection($ticketId);
         if ((int) ($current['plugin_maintenancecosts_costcenters_id'] ?? 0) > 0) {
            continue;
         }

         $ticket = new Ticket();
         if (!$ticket->getFromDB($ticketId)) {
            continue;
         }

         self::saveForTicket(
            $ticketId,
            (int) $summary[0]['plugin_maintenancecosts_costcenters_id'],
            (string) $summary[0]['costcenter_source'],
            (int) ($ticket->fields['entities_id'] ?? 0)
         );
      }
   }

   public static function getSearchOptionsForTicket(): array
   {
      return [
         [
            'id'   => 'maintenancecosts',
            'name' => __('Custos de Manutenção', 'maintenancecosts'),
         ],
         [
            'id'            => '9501',
            'table'         => CostCenterLegacy::getTable(),
            'field'         => 'name',
            'name'          => __('Centro de Custos Antigo', 'maintenancecosts'),
            'datatype'      => 'dropdown',
            'searchtype'    => ['equals', 'notequals'],
            'forcegroupby'  => true,
            'massiveaction' => false,
            'linkfield'     => 'plugin_maintenancecosts_costcenters_id',
            'joinparams'    => [
               'beforejoin' => [
                  'table'      => self::getTable(),
                  'joinparams' => [
                     'jointype' => 'child',
                  ],
               ],
               'condition'  => [
                  'NEWTABLE.costcenter_source' => 'legacy',
               ],
            ],
         ],
         [
            'id'            => '9502',
            'table'         => CostCenter::getTable(),
            'field'         => 'name',
            'name'          => __('Centro de Custos Novo', 'maintenancecosts'),
            'datatype'      => 'dropdown',
            'searchtype'    => ['equals', 'notequals'],
            'forcegroupby'  => true,
            'massiveaction' => false,
            'linkfield'     => 'plugin_maintenancecosts_costcenters_id',
            'joinparams'    => [
               'beforejoin' => [
                  'table'      => self::getTable(),
                  'joinparams' => [
                     'jointype' => 'child',
                  ],
               ],
               'condition'  => [
                  'NEWTABLE.costcenter_source' => 'new',
               ],
            ],
         ],
      ];
   }

   private static function validateSelectionChange(int $tickets_id, int $costcenter_id, string $source): bool
   {
      $summaries = self::getMaterialCostCenters($tickets_id);
      if (!count($summaries)) {
         return true;
      }

      if (count($summaries) > 1) {
         Session::addMessageAfterRedirect(
            __('Este chamado possui materiais ativos vinculados a centros de custo diferentes. Não é seguro alterar o vínculo direto do chamado agora.', 'maintenancecosts'),
            false,
            ERROR
         );
         return false;
      }

      $existing = $summaries[0];
      if (
         (int) $existing['plugin_maintenancecosts_costcenters_id'] !== $costcenter_id
         || TicketMaterial::normalizeCostCenterSource((string) ($existing['costcenter_source'] ?? 'legacy')) !== $source
      ) {
         Session::addMessageAfterRedirect(
            __('O centro de custo do chamado deve ser igual ao centro de custo dos materiais ativos ja lancados.', 'maintenancecosts'),
            false,
            ERROR
         );
         return false;
      }

      return true;
   }

   private static function getMaterialCostCenters(int $tickets_id, int $ignore_ticketmaterial_id = 0): array
   {
      global $DB;

      if ($tickets_id <= 0 || !$DB->tableExists(TicketMaterial::getTable())) {
         return [];
      }

      $where = [
         'tickets_id'                              => $tickets_id,
         'is_deleted'                              => 0,
         'plugin_maintenancecosts_costcenters_id' => ['>', 0],
      ];
      if ($ignore_ticketmaterial_id > 0) {
         $where[] = [
            'NOT' => [
               'id' => $ignore_ticketmaterial_id,
            ],
         ];
      }

      $rows = [];
      $iterator = $DB->request([
         'SELECT'   => ['plugin_maintenancecosts_costcenters_id', 'costcenter_source'],
         'DISTINCT' => true,
         'FROM'     => TicketMaterial::getTable(),
         'WHERE'    => $where,
      ]);

      foreach ($iterator as $row) {
         $rows[] = [
            'plugin_maintenancecosts_costcenters_id' => (int) ($row['plugin_maintenancecosts_costcenters_id'] ?? 0),
            'costcenter_source'                      => TicketMaterial::normalizeCostCenterSource((string) ($row['costcenter_source'] ?? 'legacy')),
         ];
      }

      return $rows;
   }

   private static function getEmptySelection(int $tickets_id): array
   {
      return [
         'id'                                      => 0,
         'tickets_id'                              => $tickets_id,
         'entities_id'                             => 0,
         'plugin_maintenancecosts_costcenters_id' => 0,
         'costcenter_source'                       => 'legacy',
         'users_id'                                => 0,
      ];
   }
}
