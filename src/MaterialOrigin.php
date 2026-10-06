<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class MaterialOrigin extends CommonDBTM
{
   public static $rightname = Config::RIGHT_CONFIG;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_materialorigins';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Origem do material', 'Origens do material', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-tags';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/materialorigin.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/materialorigin.form.php', $full);
   }

   /**
    * A origem de material e um catalogo global (vale para todas as entidades):
    * so altera quem alcanca todas elas.
    */
   private function canChangeCatalog(): bool
   {
      if (Config::hasUserSession() && !Config::canManageAllEntities()) {
         \Session::addMessageAfterRedirect(__('A origem de material vale para todas as entidades e só pode ser alterada por quem tem acesso a todas elas.', 'maintenancecosts'), false, ERROR);
         return false;
      }

      return true;
   }

   public function prepareInputForAdd($input)
   {
      if (!$this->canChangeCatalog()) {
         return false;
      }

      $input = $this->normalizeInput($input);
      // Origem nova nasce ativa quando o campo nao vem (A5).
      $input['is_active'] = isset($input['is_active']) ? (int) $input['is_active'] : 1;
      return $input;
   }

   public function prepareInputForUpdate($input)
   {
      if (!$this->canChangeCatalog()) {
         return false;
      }

      return $this->normalizeInput($input);
   }

   private function normalizeInput(array $input): array
   {
      if (isset($input['name'])) {
         $input['name'] = trim((string) $input['name']);
      }
      if (isset($input['comment'])) {
         $input['comment'] = trim((string) $input['comment']);
      }
      // Atualizacao parcial, como a acao em massa no Comentario, nao envia o
      // flag e antes disso desativava a origem.
      if (isset($input['is_active'])) {
         $input['is_active'] = (int) $input['is_active'];
      }

      return $input;
   }

   public function rawSearchOptions()
   {
      $tab = [];
      $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $tab[] = [
         'id'            => 1,
         'table'         => self::getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'massiveaction' => false,
      ];
      $tab[] = [
         'id'       => 2,
         'table'    => self::getTable(),
         'field'    => 'is_active',
         'name'     => __('Active'),
         'datatype' => 'bool',
      ];
      $tab[] = [
         'id'       => 3,
         'table'    => self::getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'text',
      ];

      return $tab;
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
      echo "<td><input type='text' name='name' value='" . Html::cleanInputText($this->fields['name'] ?? '') . "' class='form-control' required></td>";
      echo "<td>" . __('Active') . "</td><td>";
      // getEmpty() preenche '' e (int) '' seria 0: registro novo nasce ativo (A5).
      $isActive = ($this->fields['is_active'] ?? '') === '' ? 1 : (int) $this->fields['is_active'];
      \Dropdown::showYesNo('is_active', $isActive);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
      echo "<td colspan='3'><textarea name='comment' class='form-control' rows='4'>" . Html::cleanInputText($this->fields['comment'] ?? '') . "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   public static function ensureDefaults(): void
   {
      // Origens devem ser cadastradas manualmente conforme a regra operacional local.
   }

   public static function removeLegacyDefaults(): void
   {
      global $DB;

      if (!$DB->tableExists(self::getTable()) || !$DB->tableExists(TicketMaterial::getTable())) {
         return;
      }

      foreach (['Almoxarifado', 'Técnico', 'Cotação/Mercado', 'Outro'] as $name) {
         $row = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['name' => $name],
            'LIMIT' => 1,
         ])->current();
         if (!$row) {
            continue;
         }

         $used = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => TicketMaterial::getTable(),
            'WHERE' => ['plugin_maintenancecosts_materialorigins_id' => (int) $row['id']],
         ])->current();
         if ((int) ($used['cpt'] ?? 0) === 0) {
            $DB->delete(self::getTable(), ['id' => (int) $row['id']]);
         }
      }
   }
}
