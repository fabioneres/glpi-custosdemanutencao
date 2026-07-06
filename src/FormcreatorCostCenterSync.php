<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Dropdown;
use Plugin;
use Ticket;

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

      foreach ($allCCs as $cc) {
         self::syncTicketDescription($ticketId, $cc);
      }

      $toLink = $allCCs['legacy'] ?? $allCCs['new'] ?? null;
      if ($toLink !== null) {
         TicketCostCenter::saveForTicket($ticketId, (int) $toLink['id'], (string) $toLink['source']);
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

         $friendlyLabel = TicketMaterial::getCostCenterDisplayName($selectedId, $source);
         if ($friendlyLabel === '') {
            continue;
         }

         $table = $source === 'legacy' ? CostCenterLegacy::getTable() : CostCenter::getTable();
         $plainLabel = (string) Dropdown::getDropdownName($table, $selectedId, false, true, false, '');

         $found[$source] = [
            'id'             => $selectedId,
            'source'         => $source,
            'friendly_label' => $friendlyLabel,
            'plain_label'    => $plainLabel !== '' ? $plainLabel : $friendlyLabel,
            'question_id'    => (int) ($row['question_id'] ?? 0),
            'question_name'  => trim((string) ($row['question_name'] ?? '')),
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

   private static function syncTicketDescription(int $ticketId, array $selection): void
   {
      $ticket = new Ticket();
      if (!$ticket->getFromDB($ticketId)) {
         return;
      }

      $content = (string) ($ticket->fields['content'] ?? '');
      if ($content === '') {
         return;
      }

      $questionName = trim((string) ($selection['question_name'] ?? ''));
      if ($questionName === '') {
         $questionName = __('Centro de custo', 'maintenancecosts');
      }

      $plainLabel = trim((string) ($selection['plain_label'] ?? ''));
      $friendlyLabel = trim((string) ($selection['friendly_label'] ?? ''));
      if ($friendlyLabel === '') {
         return;
      }

      $updated = $content;
      $patterns = [];
      if ($plainLabel !== '') {
         $patterns[] = [$questionName . ': ' . $plainLabel, $questionName . ': ' . $friendlyLabel];
         $patterns[] = [$questionName . ' : ' . $plainLabel, $questionName . ' : ' . $friendlyLabel];
         $patterns[] = [$plainLabel, $friendlyLabel];
      }

      foreach ($patterns as [$from, $to]) {
         if ($from !== '' && mb_strpos($updated, $from) !== false) {
            $updated = preg_replace('/' . preg_quote($from, '/') . '/u', $to, $updated, 1) ?? $updated;
            break;
         }
      }

      if ($updated === $content && mb_strpos($updated, $friendlyLabel) === false) {
         // O GLPI pode persistir HTML literal, entidades nomeadas (&lt;)
         // ou entidades numericas (&#60;). Mantemos o mesmo formato para
         // evitar lixo visual na descricao do ticket.
         if (mb_strpos($updated, '&#60;') !== false) {
            $lineBreak = '&#60;br&#62;&#60;br&#62;';
         } elseif (mb_strpos($updated, '&lt;') !== false) {
            $lineBreak = '&lt;br&gt;&lt;br&gt;';
         } elseif (mb_strpos($updated, '<') !== false) {
            $lineBreak = '<br><br>';
         } else {
            $lineBreak = "\n\n";
         }
         $updated = rtrim($updated) . $lineBreak . $questionName . ': ' . $friendlyLabel;
      }

      if ($updated === $content) {
         return;
      }

      $ticket->update([
         'id'      => $ticketId,
         'content' => $updated,
      ]);
   }
}
