<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonGLPI;
use DBmysql;
use DbUtils;
use Glpi\DBAL\QueryExpression;
use Html;
use Profile as GlpiProfile;
use ProfileRight;
use Session;

class Profile extends GlpiProfile
{
   public static $rightname = 'profile';

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item instanceof GlpiProfile && $item->getID()) {
         return self::createTabEntry(Config::getTypeName(), 0, $item::getType(), Config::getIcon());
      }

      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item instanceof GlpiProfile) {
         self::addDefaultProfileInfos((int) $item->getID(), self::getDefaultRights());

         $profile = new self();
         $profile->showForm((int) $item->getID());
      }

      return true;
   }

   public static function getAllRights($all = false)
   {
      return [
         ['itemtype' => Material::class, 'label' => __('Visualizar/Gerenciar materiais', 'maintenancecosts'), 'field' => Config::RIGHT_MATERIALS],
         ['itemtype' => Price::class, 'label' => __('Visualizar/Gerenciar preços SINAPI', 'maintenancecosts'), 'field' => Config::RIGHT_PRICES],
         ['itemtype' => CostCenter::class, 'label' => __('Visualizar/Gerenciar centros de custo', 'maintenancecosts'), 'field' => Config::RIGHT_COSTCENTERS],
         ['itemtype' => TicketMaterial::class, 'label' => __('Visualizar/Lancar consumo em chamados', 'maintenancecosts'), 'field' => Config::RIGHT_CONSUMPTION],
         ['itemtype' => ImportBatch::class, 'label' => __('Importar tabela SINAPI', 'maintenancecosts'), 'field' => Config::RIGHT_IMPORT],
         ['itemtype' => Report::class, 'label' => __('Visualizar relatorios', 'maintenancecosts'), 'field' => Config::RIGHT_REPORTS],
         ['itemtype' => Config::class, 'label' => __('Administrar configurações do plugin', 'maintenancecosts'), 'field' => Config::RIGHT_CONFIG],
      ];
   }

   public static function getDefaultRights(): array
   {
      return [
         Config::RIGHT_MATERIALS   => 0,
         Config::RIGHT_PRICES      => 0,
         Config::RIGHT_COSTCENTERS => 0,
         Config::RIGHT_CONSUMPTION => 0,
         Config::RIGHT_IMPORT      => 0,
         Config::RIGHT_REPORTS     => 0,
         Config::RIGHT_CONFIG      => 0,
      ];
   }

   public static function createFirstAccess(int $profiles_id): void
   {
      self::addDefaultProfileInfos($profiles_id, [
         Config::RIGHT_MATERIALS   => ALLSTANDARDRIGHT,
         Config::RIGHT_PRICES      => ALLSTANDARDRIGHT,
         Config::RIGHT_COSTCENTERS => ALLSTANDARDRIGHT,
         Config::RIGHT_CONSUMPTION => ALLSTANDARDRIGHT,
         Config::RIGHT_IMPORT      => READ | UPDATE,
         Config::RIGHT_REPORTS     => READ,
         Config::RIGHT_CONFIG      => UPDATE,
      ], true);
   }

   public static function installRights(): void
   {
      foreach (Config::getRightNames() as $right) {
         self::addMissingRightForProfiles($right, ALLSTANDARDRIGHT, [\Config::$rightname => UPDATE]);
      }

      ProfileRight::cleanAllPossibleRights();
   }

   private static function addMissingRightForProfiles(string $name, int $rights, array $requiredrights): void
   {
      /** @var DBmysql $DB */
      global $DB;

      if (!$DB->tableExists('glpi_profiles') || !$DB->tableExists('glpi_profilerights')) {
         return;
      }

      $profile_iterator = $DB->request([
         'SELECT'    => 'glpi_profiles.id',
         'FROM'      => 'glpi_profiles',
         'LEFT JOIN' => [
            'glpi_profilerights' => [
               'ON' => [
                  'glpi_profilerights' => 'profiles_id',
                  'glpi_profiles'      => 'id',
                  [
                     'AND' => ['glpi_profilerights.name' => $name],
                  ],
               ],
            ],
         ],
         'WHERE'     => [
            'glpi_profilerights.id' => null,
         ],
      ]);

      if ($profile_iterator->count() === 0) {
         return;
      }

      $where = [];
      foreach ($requiredrights as $reqright => $reqvalue) {
         $where['OR'][] = [
            'name' => $reqright,
            new QueryExpression($DB->quoteName('rights') . " & $reqvalue = $reqvalue"),
         ];
      }

      foreach ($profile_iterator as $profile) {
         if (empty($requiredrights)) {
            $required_met = true;
         } else {
            $iterator = $DB->request([
               'SELECT' => ['name', 'rights'],
               'FROM'   => 'glpi_profilerights',
               'WHERE'  => $where + ['profiles_id' => (int) $profile['id']],
            ]);

            $required_met = count($iterator) === count($requiredrights);
         }

         $DB->insertOrDie(
            'glpi_profilerights',
            [
               'id'          => null,
               'profiles_id' => (int) $profile['id'],
               'name'        => $name,
               'rights'      => $required_met ? $rights : 0,
            ],
            sprintf('%1$s add right for %2$s', PLUGIN_MAINTENANCECOSTS_VERSION, $name)
         );
      }
   }

   public static function initProfile(): void
   {
      /** @var DBmysql $DB */
      global $DB;

      self::installRights();

      if (!isset($_SESSION['glpiactiveprofile']['id'])
         || !$DB->tableExists('glpi_profilerights')
      ) {
         return;
      }

      $profiles_id = (int) $_SESSION['glpiactiveprofile']['id'];
      self::addDefaultProfileInfos($profiles_id, self::getDefaultRights());

      $iterator = $DB->request([
         'SELECT' => ['name', 'rights'],
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => [
            'profiles_id' => $profiles_id,
            'name'        => Config::getRightNames(),
         ],
      ]);

      foreach ($iterator as $row) {
         $_SESSION['glpiactiveprofile'][$row['name']] = (int) $row['rights'];
      }
   }

   public static function removeRightsFromSession(): void
   {
      foreach (Config::getRightNames() as $right) {
         unset($_SESSION['glpiactiveprofile'][$right]);
      }
   }

   public static function addDefaultProfileInfos(int $profiles_id, array $rights, bool $upgrade = false): void
   {
      /** @var DBmysql $DB */
      global $DB;

      if (!$DB->tableExists('glpi_profilerights')) {
         return;
      }

      $profileRight = new ProfileRight();
      $dbu = new DbUtils();

      foreach ($rights as $right => $value) {
         $criteria = [
            'profiles_id' => $profiles_id,
            'name'        => $right,
         ];

         $row = null;
         $iterator = $DB->request([
            'SELECT' => ['id', 'rights'],
            'FROM'   => 'glpi_profilerights',
            'WHERE'  => $criteria,
            'LIMIT'  => 1,
         ]);
         if ($dbu->countElementsInTable('glpi_profilerights', $criteria) > 0) {
            $row = $iterator->current();
         }

         $session_value = (int) $value;
         if (!$row) {
            $profileRight->add([
               'profiles_id' => $profiles_id,
               'name'        => $right,
               'rights'      => $value,
            ]);
         } elseif ($upgrade) {
            $current = (int) ($row['rights'] ?? 0);
            $new = $current | (int) $value;
            if ($new !== $current) {
               $DB->update('glpi_profilerights', ['rights' => $new], ['id' => (int) $row['id']]);
            }
            $session_value = $new;
         } else {
            $session_value = (int) ($row['rights'] ?? 0);
         }

         if (isset($_SESSION['glpiactiveprofile']['id'])
            && (int) $_SESSION['glpiactiveprofile']['id'] === $profiles_id
         ) {
            $_SESSION['glpiactiveprofile'][$right] = $session_value;
         }
      }
   }

   public function showForm($ID, $options = [])
   {
      if (!self::canView()) {
         return false;
      }

      $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
      $profile = new GlpiProfile();
      if (!$profile->getFromDB((int) $ID)) {
         return false;
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' action='" . GlpiProfile::getFormURL() . "' data-track-changes='true'>";
      }

      $profile->displayRightsChoiceMatrix(self::getAllRights(), [
         'title'         => Config::getTypeName(),
         'canedit'       => $canedit,
         'default_class' => 'tab_bg_2',
      ]);

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), [
            'name'  => 'update',
            'class' => 'btn btn-primary mt-2',
         ]);
         echo "</div>";
         Html::closeForm();
      }
      echo "</div>";

      return true;
   }
}
