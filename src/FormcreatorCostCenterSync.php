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

      $content = self::normalizeTicketContent((string) ($ticket->fields['content'] ?? ''));
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

      $updated = self::rewriteQuestionLine($content, $questionName, $friendlyLabel, $plainLabel);

      if ($updated === $content && mb_strpos($updated, $friendlyLabel) === false) {
         $updated = rtrim($updated);
         if ($updated !== '') {
            $updated .= "\n\n";
         }
         $updated .= $questionName . ': ' . $friendlyLabel;
      }

      if ($updated === $content) {
         return;
      }

      $updated = preg_replace("/\r\n|\r|\n/", '<br>', $updated) ?? $updated;

      $ticket->update([
         'id'      => $ticketId,
         'content' => $updated,
      ]);
   }

   private static function normalizeTicketContent(string $content): string
   {
      if ($content === '') {
         return '';
      }

      $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $decoded = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $decoded) ?? $decoded;
      $decoded = preg_replace('/<\/\s*(div|p|li|tr|td|th|table)\s*>/i', "\n", $decoded) ?? $decoded;
      $decoded = strip_tags($decoded);
      $decoded = preg_replace("/\r\n|\r/", "\n", $decoded) ?? $decoded;
      $decoded = preg_replace('/[ \t]+/u', ' ', $decoded) ?? $decoded;
      $decoded = preg_replace('/\n{3,}/', "\n\n", $decoded) ?? $decoded;

      return trim($decoded);
   }

   private static function rewriteQuestionLine(
      string $content,
      string $questionName,
      string $friendlyLabel,
      string $plainLabel
   ): string {
      $segments = preg_split("/(\r\n|\n|\r)/", $content);
      if (!is_array($segments) || $segments === []) {
         return $content;
      }

      $updated = $segments;
      $questionNeedle = self::normalizeText($questionName);
      $friendlyNeedle = self::normalizeText($friendlyLabel);
      $plainNeedle = self::normalizeText($plainLabel);

      foreach ($updated as $index => $segment) {
         $decoded = html_entity_decode($segment, ENT_QUOTES | ENT_HTML5, 'UTF-8');
         $plainSegment = self::normalizeText(strip_tags($decoded));

         if ($plainSegment === '' || mb_strpos($plainSegment, $questionNeedle) === false) {
            continue;
         }

         if (
            $friendlyNeedle !== ''
            && mb_strpos($plainSegment, $friendlyNeedle) === false
            && $plainNeedle !== ''
            && mb_strpos($plainSegment, $plainNeedle) === false
         ) {
            continue;
         }

         $updated[$index] = self::buildQuestionLineLike($segment, $questionName, $friendlyLabel);
         return implode("\n", $updated);
      }

      return $content;
   }

   private static function buildQuestionLineLike(string $originalSegment, string $questionName, string $friendlyLabel): string
   {
      // Keep the line as plain text to avoid inheriting editor-specific
      // wrappers or inline styles that can render as a bordered block in the
      // ticket description.
      return $questionName . ': ' . $friendlyLabel;
   }

   private static function normalizeText(string $value): string
   {
      $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $value = strip_tags($value);
      $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
      return trim($value);
   }

}
