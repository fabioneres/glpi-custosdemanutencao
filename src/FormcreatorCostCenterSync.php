<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Plugin;

class FormcreatorCostCenterSync
{
   public static function itemAdded(CommonDBTM $item): void
   {
      global $DB;

      if (
         $item->getType() !== 'Item_Ticket'
         || !Plugin::isPluginActive('formcreator')
         || !class_exists('PluginFormcreatorFormAnswer')
         || !$DB->tableExists('glpi_plugin_formcreator_answers')
         || !$DB->tableExists('glpi_plugin_formcreator_questions')
      ) {
         return;
      }

      $itemtype = (string) ($item->fields['itemtype'] ?? '');
      $formAnswerId = (int) ($item->fields['items_id'] ?? 0);
      $ticketId = (int) ($item->fields['tickets_id'] ?? 0);

      if ($itemtype !== \PluginFormcreatorFormAnswer::class || $formAnswerId <= 0 || $ticketId <= 0) {
         return;
      }

      $allCCs = self::findAllSelectedCostCenters($formAnswerId);
      if (empty($allCCs)) {
         return;
      }

      $valuesBySource = [];
      foreach ($allCCs as $source => $selection) {
         $valuesBySource[$source] = (int) ($selection['id'] ?? 0);
      }

      if ($valuesBySource !== []) {
         TicketCostCenter::saveSelectionsForTicket($ticketId, $valuesBySource);
      }
   }

   /**
    * Retorna todos os centros de custo selecionados no FormAnswer, indexados por fonte ('new', 'legacy').
    *
    * @return array<string, array>
    */
   private static function findAllSelectedCostCenters(int $formAnswerId): array
   {
      global $DB;

      $answers = $DB->request([
         'SELECT' => [
            'a.answer',
            'q.id AS question_id',
            'q.name AS question_name',
            'q.itemtype',
         ],
         'FROM'   => 'glpi_plugin_formcreator_answers AS a',
         'INNER JOIN' => [
            'glpi_plugin_formcreator_questions AS q' => [
               'FKEY' => [
                  'q' => 'id',
                  'a' => 'plugin_formcreator_questions_id',
               ],
            ],
         ],
         'WHERE'  => [
            'a.plugin_formcreator_formanswers_id' => $formAnswerId,
            'q.itemtype'                          => [CostCenter::class, CostCenterLegacy::class],
         ],
         'ORDER'  => ['q.id ASC'],
      ]);

      $found = [];
      foreach ($answers as $row) {
         $selectedId = self::extractSelectedId(trim((string) ($row['answer'] ?? '')));
         if ($selectedId <= 0) {
            continue;
         }

         $source = ((string) ($row['itemtype'] ?? '')) === CostCenterLegacy::class ? 'legacy' : 'new';

         if (isset($found[$source])) {
            continue;
         }

         $found[$source] = [
            'id'     => $selectedId,
            'source' => $source,
         ];
      }

      return $found;
   }

   private static function extractSelectedId(string $rawAnswer): int
   {
      if ($rawAnswer === '') {
         return 0;
      }

      if (ctype_digit($rawAnswer)) {
         return (int) $rawAnswer;
      }

      $decoded = json_decode($rawAnswer, true);
      if (!is_array($decoded)) {
         return 0;
      }

      foreach ($decoded as $value) {
         if (!is_scalar($value)) {
            continue;
         }

         $candidate = trim((string) $value);
         if (ctype_digit($candidate)) {
            return (int) $candidate;
         }
      }

      return 0;
   }

}
