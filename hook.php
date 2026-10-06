<?php
/**
 * -------------------------------------------------------------------------
 * Maintenance Costs plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\CostCenter;
use GlpiPlugin\Maintenancecosts\CostCenterLegacy;
use GlpiPlugin\Maintenancecosts\Installer;
use GlpiPlugin\Maintenancecosts\Profile;
use GlpiPlugin\Maintenancecosts\TicketCostCenter;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

require_once __DIR__ . '/bootstrap.php';

function plugin_maintenancecosts_install(): bool {
   return Installer::install();
}

function plugin_maintenancecosts_upgrade($old_version): bool {
   return Installer::install();
}

function plugin_maintenancecosts_uninstall(): bool {
   $result = Installer::uninstall();
   ProfileRight::deleteProfileRights(Config::getRightNames());
   Profile::removeRightsFromSession();

   return $result;
}

function plugin_maintenancecosts_getDropdown(): array {
   $plugin = new Plugin();

   if (!$plugin->isActivated('maintenancecosts')) {
      return [];
   }

   return [
      CostCenter::class       => CostCenter::getTypeName(Session::getPluralNumber()),
      CostCenterLegacy::class => CostCenterLegacy::getTypeName(Session::getPluralNumber()),
   ];
}

function plugin_maintenancecosts_getAddSearchOptionsNew($itemtype): array {
   if ($itemtype !== \Ticket::class && $itemtype !== 'Ticket') {
      return [];
   }

   return TicketCostCenter::getSearchOptionsForTicket();
}

/**
 * Chamado excluido definitivamente: remove lancamentos, vinculos e custos
 * nativos criados pelo plugin (F11).
 */
function plugin_maintenancecosts_item_purge($item) {
   if ($item instanceof \Ticket) {
      \GlpiPlugin\Maintenancecosts\TicketMaterial::purgeForTicket((int) $item->getID());
   }
}

/**
 * Chamado cuja entidade mudou por atualizacao (o hook de transferencia nao
 * dispara quando o chamado acompanha um ativo): os lancamentos seguem (F11).
 */
function plugin_maintenancecosts_item_update($item) {
   if ($item instanceof \Ticket && is_array($item->updates ?? null) && in_array('entities_id', $item->updates, true)) {
      \GlpiPlugin\Maintenancecosts\TicketMaterial::transferForTicket(
         (int) $item->getID(),
         (int) ($item->fields['entities_id'] ?? 0)
      );
   }
}

/**
 * Chamado transferido: os lancamentos seguem a entidade do chamado (F11).
 */
function plugin_maintenancecosts_item_transfer($parm) {
   if (is_array($parm) && ($parm['type'] ?? '') === 'Ticket') {
      \GlpiPlugin\Maintenancecosts\TicketMaterial::transferForTicket(
         (int) ($parm['newID'] ?? $parm['id'] ?? 0),
         (int) ($parm['entities_id'] ?? 0)
      );
   }
}

/**
 * Precos nao tem entidade propria: a pesquisa nativa restringe pela entidade
 * do material (A1, PRS-007/PRC-005). Formato de criterios do GLPI 11: o
 * parser de JOIN em string do core descarta o alias.
 */
function plugin_maintenancecosts_addDefaultJoin($itemtype, $ref_table, &$already_link_tables) {
   if ($itemtype !== \GlpiPlugin\Maintenancecosts\Price::class) {
      return [];
   }

   return [
      'LEFT JOIN' => [
         'glpi_plugin_maintenancecosts_materials AS mc_price_material' => [
            'FKEY' => [
               'mc_price_material' => 'id',
               $ref_table          => 'plugin_maintenancecosts_materials_id',
            ],
         ],
      ],
   ];
}

function plugin_maintenancecosts_addDefaultWhere($itemtype) {
   if ($itemtype !== \GlpiPlugin\Maintenancecosts\Price::class) {
      return [];
   }

   return getEntitiesRestrictCriteria('mc_price_material', 'entities_id', '', true);
}
