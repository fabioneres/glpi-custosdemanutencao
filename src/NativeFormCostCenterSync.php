<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AnswersSet_FormDestinationItem;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Ticket;
use Toolbox;

/**
 * Associates cost center answers from a native GLPI 11 form with its ticket.
 *
 * This hook runs only after GLPI has persisted the answers-to-destination
 * relation. It deliberately does not update Ticket::content.
 */
final class NativeFormCostCenterSync
{
   /** @var array<string, class-string<CostCenter>> */
   private const ITEMTYPE_BY_SOURCE = [
      'legacy' => CostCenterLegacy::class,
      'new'    => CostCenter::class,
   ];

   public static function itemAdded(CommonDBTM $item): void
   {
      if (!$item instanceof AnswersSet_FormDestinationItem) {
         return;
      }

      if ((string) ($item->fields['itemtype'] ?? '') !== Ticket::class) {
         return;
      }

      $answersSetId = self::positiveId($item->fields['forms_answerssets_id'] ?? null);
      $ticketId = self::positiveId($item->fields['items_id'] ?? null);
      if ($answersSetId === 0 || $ticketId === 0) {
         self::log('Ignoring native form relation with invalid identifiers.');
         return;
      }

      $ticket = new Ticket();
      $answersSet = new AnswersSet();
      if (!$ticket->getFromDB($ticketId) || !$answersSet->getFromDB($answersSetId)) {
         self::log(sprintf('Unable to load native form relation (answers_set=%d, ticket=%d).', $answersSetId, $ticketId));
         return;
      }

      $ticketEntityId = (int) ($ticket->fields['entities_id'] ?? 0);
      if (!Config::isEnabledForEntity($ticketEntityId)) {
         return;
      }

      $selections = self::getValidatedSelections($answersSet, $ticketEntityId);
      if ($selections === []) {
         return;
      }

      // The native AnswersHandler already runs in a transaction. Do not open a
      // nested transaction here: returning false must not roll back the ticket.
      if (!TicketCostCenter::saveSelectionsForTicket($ticketId, $selections, $ticketEntityId)) {
         self::log(sprintf('Could not save validated cost centers for native-form ticket %d.', $ticketId));
      }
   }

   /**
    * @return array{'legacy'?: int, 'new'?: int}
    */
   private static function getValidatedSelections(AnswersSet $answersSet, int $ticketEntityId): array
   {
      $selections = [];
      $conflictedSources = [];
      $questionType = new QuestionTypeItemDropdown();

      foreach ($answersSet->getAnswers() as $answer) {
         if ($answer->getRawType() !== QuestionTypeItemDropdown::class) {
            continue;
         }

         $question = new Question();
         if (!$question->getFromDB($answer->getQuestionId())) {
            self::log(sprintf('Ignoring native form answer for missing question %d.', $answer->getQuestionId()));
            continue;
         }

         $configuredItemtype = $questionType->getDefaultValueItemtype($question);
         $source = array_search($configuredItemtype, self::ITEMTYPE_BY_SOURCE, true);
         if ($source === false) {
            continue;
         }

         $rawAnswer = $answer->getRawAnswer();
         if (!is_array($rawAnswer)) {
            self::log(sprintf('Ignoring malformed cost center answer for question %d.', $question->getID()));
            continue;
         }

         // The persisted question configuration is authoritative. The raw
         // itemtype must agree with it, otherwise the submitted answer is not
         // accepted as a cost-center selection.
         if (($rawAnswer['itemtype'] ?? null) !== $configuredItemtype) {
            self::log(sprintf('Ignoring cost center answer with mismatched itemtype for question %d.', $question->getID()));
            continue;
         }

         $costCenterId = self::positiveId($rawAnswer['items_id'] ?? null);
         if ($costCenterId === 0) {
            continue;
         }

         if (!self::isSelectableForTicket($configuredItemtype, $costCenterId, $ticketEntityId)) {
            self::log(sprintf('Ignoring unavailable cost center %d for native-form ticket entity %d.', $costCenterId, $ticketEntityId));
            continue;
         }

         if (isset($conflictedSources[$source])) {
            continue;
         }

         if (isset($selections[$source]) && $selections[$source] !== $costCenterId) {
            unset($selections[$source]);
            $conflictedSources[$source] = true;
            self::log(sprintf('Ignoring ambiguous %s cost center answers in one native form submission.', $source));
            continue;
         }

         $selections[$source] = $costCenterId;
      }

      return $selections;
   }

   /** @param class-string<CostCenter> $itemtype */
   private static function isSelectableForTicket(string $itemtype, int $costCenterId, int $ticketEntityId): bool
   {
      if (!in_array($itemtype, self::ITEMTYPE_BY_SOURCE, true)) {
         return false;
      }

      $costCenter = new $itemtype();
      if (!$costCenter->getFromDB($costCenterId) || (int) ($costCenter->fields['is_active'] ?? 0) !== 1) {
         return false;
      }

      $ownerEntityId = (int) ($costCenter->fields['entities_id'] ?? 0);
      if ($ownerEntityId === $ticketEntityId) {
         return true;
      }

      if ((int) ($costCenter->fields['is_recursive'] ?? 0) !== 1) {
         return false;
      }

      return in_array($ticketEntityId, getSonsOf(\Entity::getTable(), $ownerEntityId), true);
   }

   private static function positiveId(mixed $value): int
   {
      if (is_int($value)) {
         return $value > 0 ? $value : 0;
      }

      if (is_string($value) && ctype_digit($value)) {
         $id = (int) $value;
         return $id > 0 ? $id : 0;
      }

      return 0;
   }

   private static function log(string $message): void
   {
      Toolbox::logInFile('maintenancecosts-native-form', $message . PHP_EOL);
   }
}
