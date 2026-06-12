<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;
use Session;
use Ticket;

class TicketMaterial extends CommonDBTM
{
   public static $rightname = Config::RIGHT_CONSUMPTION;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_ticketmaterials';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Material consumido', 'Materiais consumidos', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-clipboard-list';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/ticketmaterial.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/ticketmaterial.form.php', $full);
   }

   public function prepareInputForAdd($input)
   {
      $input = $this->normalizeInput($input);
      if (!count($input)) {
         return false;
      }
      if (empty($input['itemtype'])) {
         $input['itemtype'] = Ticket::class;
      }
      if (empty($input['items_id']) && !empty($input['tickets_id'])) {
         $input['items_id'] = (int) $input['tickets_id'];
      }
      if (empty($input['tickets_id']) && $input['itemtype'] === Ticket::class) {
         $input['tickets_id'] = (int) ($input['items_id'] ?? 0);
      }
      if (empty($input['entities_id']) && isset($_SESSION['glpiactive_entity'])) {
         $input['entities_id'] = (int) $_SESSION['glpiactive_entity'];
      }
      if (empty($input['users_id']) && isset($_SESSION['glpiID'])) {
         $input['users_id'] = (int) $_SESSION['glpiID'];
      }
      return $input;
   }

   public function prepareInputForUpdate($input)
   {
      $input = $this->normalizeInput($input);
      return count($input) ? $input : false;
   }

   public function post_addItem()
   {
      AuditLog::record(self::class, (int) $this->getID(), 'consumption_add', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
      $this->ensureTicketContractLink();
      $this->syncContractCost();
   }

   public function post_updateItem($history = true)
   {
      AuditLog::record(self::class, (int) $this->getID(), 'consumption_update', [], $this->fields, '', (int) ($this->fields['entities_id'] ?? 0));
      $this->ensureTicketContractLink();
      $this->syncContractCost();
   }

   private function normalizeInput(array $input): array
   {
      $settings = Config::getSettings();
      if (!(int) $settings['is_enabled']) {
         Session::addMessageAfterRedirect(__('O plugin Custos de Manutenção está desabilitado.', 'maintenancecosts'), false, ERROR);
         return [];
      }

      foreach (['tickets_id', 'items_id', 'plugin_maintenancecosts_materials_id', 'plugin_maintenancecosts_costcenters_id', 'plugin_maintenancecosts_materialorigins_id', 'contracts_id'] as $field) {
         if (isset($input[$field])) {
            $input[$field] = (int) $input[$field];
         }
      }

      if (isset($input['price_type'])) {
         $input['price_type'] = Config::normalizePriceType((string) $input['price_type']);
      } else {
         $input['price_type'] = Config::normalizePriceType((string) ($this->fields['price_type'] ?? 'sinapi'));
      }

      foreach (['quantity', 'unit_price_applied'] as $field) {
         if (isset($input[$field])) {
            $input[$field] = Config::parseDecimal($input[$field]);
         }
      }

      if (isset($input['quantity'])) {
         $input['quantity'] = max(0, round((float) $input['quantity']));
      }

      if (isset($input['quantity']) || isset($input['unit_price_applied'])) {
         $quantity = (float) ($input['quantity'] ?? ($this->fields['quantity'] ?? 0));
         $unit_price = (float) ($input['unit_price_applied'] ?? ($this->fields['unit_price_applied'] ?? 0));
         $input['total_price'] = $quantity * $unit_price;
      }

      if (isset($input['competence'])) {
         $input['competence'] = Config::normalizeCompetence((string) $input['competence']);
      }

      if (!empty($input['tickets_id']) && !$this->ticketCategoryAllowed((int) $input['tickets_id'], (string) ($settings['allowed_itilcategories'] ?? ''))) {
         Session::addMessageAfterRedirect(__('Categoria do chamado nao permitida para lancamento de materiais.', 'maintenancecosts'), false, ERROR);
         return [];
      }

      if ((int) $settings['costcenter_required'] && empty($input['plugin_maintenancecosts_costcenters_id'])) {
         Session::addMessageAfterRedirect(__('Centro de custo e obrigatorio.', 'maintenancecosts'), false, ERROR);
         return [];
      }

      if (empty($input['competence'])) {
         $input['competence'] = $this->resolveDefaultCompetence($input, (string) ($settings['default_competence_mode'] ?? 'latest'));
      }

      if (!empty($input['plugin_maintenancecosts_materials_id'])) {
         if (empty($input['unit'])) {
            $material = new Material();
            if ($material->getFromDB((int) $input['plugin_maintenancecosts_materials_id'])) {
               $input['unit'] = (string) ($material->fields['unit'] ?? '');
            }
         }

         $price = !empty($input['competence'])
            ? Price::getForMaterialCompetenceAndType((int) $input['plugin_maintenancecosts_materials_id'], (string) $input['competence'], (string) $input['price_type'])
            : Price::getLatestForMaterialAndType((int) $input['plugin_maintenancecosts_materials_id'], (string) $input['price_type']);

         if ($price) {
            if ($input['price_type'] === 'sinapi' && !(int) $settings['allow_manual_unit_price']) {
               $input['unit_price_applied'] = (float) $price['unit_price'];
            }
            if (empty($input['competence'])) {
               $input['competence'] = (string) $price['competence'];
            }
         } elseif ($input['price_type'] === 'sinapi' && !(int) $settings['allow_manual_unit_price']) {
            Session::addMessageAfterRedirect(__('Não há preço cadastrado para o material/competência selecionado.', 'maintenancecosts'), false, ERROR);
            return [];
         }
      }

      if (isset($input['is_deleted'])) {
         $input['is_deleted'] = (int) $input['is_deleted'];
      }

      if (isset($input['quantity']) || isset($input['unit_price_applied'])) {
         $quantity = (float) ($input['quantity'] ?? ($this->fields['quantity'] ?? 0));
         $unit_price = (float) ($input['unit_price_applied'] ?? ($this->fields['unit_price_applied'] ?? 0));
         $input['total_price'] = $quantity * $unit_price;
      }

      return $input;
   }

   private function ticketCategoryAllowed(int $tickets_id, string $allowed): bool
   {
      $allowed = trim($allowed);
      if ($allowed === '' || $tickets_id <= 0) {
         return true;
      }

      $ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', $allowed)));
      if (!count($ids)) {
         return true;
      }

      $ticket = new Ticket();
      if (!$ticket->getFromDB($tickets_id)) {
         return true;
      }

      return in_array((int) ($ticket->fields['itilcategories_id'] ?? 0), $ids, true);
   }

   private function resolveDefaultCompetence(array $input, string $mode): string
   {
      if ($mode === 'consumption_date' && !empty($input['consumption_date'])) {
         return substr((string) $input['consumption_date'], 0, 7);
      }

      if ($mode === 'ticket_date' && !empty($input['tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB((int) $input['tickets_id']) && !empty($ticket->fields['date'])) {
            return substr((string) $ticket->fields['date'], 0, 7);
         }
      }

      $price = !empty($input['plugin_maintenancecosts_materials_id'])
         ? Price::getLatestForMaterial((int) $input['plugin_maintenancecosts_materials_id'])
         : null;

      return $price ? (string) $price['competence'] : date('Y-m');
   }

   public static function cancel(int $id, string $reason): bool
   {
      $item = new self();
      if (!$item->getFromDB($id)) {
         return false;
      }

      $old = $item->fields;
      $ok = $item->update([
         'id'            => $id,
         'is_deleted'    => 1,
         'deleted_at'    => date('Y-m-d H:i:s'),
         'deleted_by'    => (int) ($_SESSION['glpiID'] ?? 0),
         'delete_reason' => $reason,
      ]);

      if ($ok) {
         AuditLog::record(self::class, $id, 'consumption_cancel', $old, ['delete_reason' => $reason], $reason, (int) ($old['entities_id'] ?? 0));
      }

      return (bool) $ok;
   }

   private function syncContractCost(): void
   {
      global $DB;

      if (!class_exists('ContractCost') || !class_exists('Ticket_Contract')) {
         return;
      }

      $tickets_id = (int) ($this->fields['tickets_id'] ?? 0);
      if ($tickets_id <= 0 || !$DB->tableExists(\Ticket_Contract::getTable())) {
         return;
      }

      $contracts_id = (int) ($this->fields['contracts_id'] ?? 0);
      if ($contracts_id <= 0) {
         $link = $DB->request([
            'SELECT' => ['contracts_id'],
            'FROM'   => \Ticket_Contract::getTable(),
            'WHERE'  => ['tickets_id' => $tickets_id],
            'ORDER'  => 'id ASC',
            'LIMIT'  => 1,
         ])->current();
         $contracts_id = (int) ($link['contracts_id'] ?? 0);
      }

      if ($contracts_id <= 0) {
         return;
      }

      $contract = new \Contract();
      if (!$contract->getFromDB($contracts_id)) {
         return;
      }

      $date = (string) ($this->fields['consumption_date'] ?? date('Y-m-d'));
      if ($date === '') {
         $date = date('Y-m-d');
      }

      $material = new Material();
      $materialName = $material->getFromDB((int) ($this->fields['plugin_maintenancecosts_materials_id'] ?? 0))
         ? $material->getName()
         : Material::getTypeName(1);

      $isDeleted = (int) ($this->fields['is_deleted'] ?? 0) === 1;
      $cost = $isDeleted ? 0 : (float) ($this->fields['total_price'] ?? 0);
      $comment = sprintf(
         '%s #%d | %s | %s: %s | %s: %s',
         self::getTypeName(1),
         (int) $this->getID(),
         sprintf(__('Chamado %d', 'maintenancecosts'), $tickets_id),
         __('Material', 'maintenancecosts'),
         $materialName,
         __('Status', 'maintenancecosts'),
         $isDeleted ? __('Cancelado', 'maintenancecosts') : __('Ativo', 'maintenancecosts')
      );

      if ($isDeleted && !empty($this->fields['delete_reason'])) {
         $comment .= ' | ' . __('Motivo', 'maintenancecosts') . ': ' . (string) $this->fields['delete_reason'];
      }

      $input = [
         'contracts_id' => $contracts_id,
         'entities_id'  => (int) ($this->fields['entities_id'] ?? ($contract->fields['entities_id'] ?? 0)),
         'name'         => sprintf(__('Consumo de material - chamado %d - item %d', 'maintenancecosts'), $tickets_id, (int) $this->getID()),
         'cost'         => $cost,
         'begin_date'   => $date,
         'end_date'     => $date,
         'comment'      => $comment,
      ];

      $contractCost = new \ContractCost();
      $contractcosts_id = (int) ($this->fields['contractcosts_id'] ?? 0);
      if ($contractcosts_id > 0 && $contractCost->getFromDB($contractcosts_id)) {
         $contractCost->update(['id' => $contractcosts_id] + $input);
         return;
      }

      $newId = (int) $contractCost->add($input);
      if ($newId > 0) {
         $DB->update(self::getTable(), ['contractcosts_id' => $newId], ['id' => (int) $this->getID()]);
         $this->fields['contractcosts_id'] = $newId;
      }
   }

   private function ensureTicketContractLink(): void
   {
      global $DB;

      $tickets_id = (int) ($this->fields['tickets_id'] ?? 0);
      $contracts_id = (int) ($this->fields['contracts_id'] ?? 0);
      if ($tickets_id <= 0 || $contracts_id <= 0 || !class_exists('Ticket_Contract') || !$DB->tableExists(\Ticket_Contract::getTable())) {
         return;
      }

      $existing = $DB->request([
         'FROM'  => \Ticket_Contract::getTable(),
         'WHERE' => [
            'tickets_id'   => $tickets_id,
            'contracts_id' => $contracts_id,
         ],
         'LIMIT' => 1,
      ])->current();
      if ($existing) {
         return;
      }

      $link = new \Ticket_Contract();
      $link->add([
         'tickets_id'   => $tickets_id,
         'contracts_id' => $contracts_id,
      ]);
   }

   public function getSearchOptions()
   {
      $tab = [];
      $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $tab[1] = ['id' => 1, 'table' => 'glpi_tickets', 'field' => 'id', 'linkfield' => 'tickets_id', 'name' => Ticket::getTypeName(1), 'datatype' => 'number'];
      $tab[2] = ['id' => 2, 'table' => Material::getTable(), 'field' => 'name', 'linkfield' => 'plugin_maintenancecosts_materials_id', 'name' => Material::getTypeName(1), 'datatype' => 'dropdown'];
      $tab[3] = ['id' => 3, 'table' => self::getTable(), 'field' => 'quantity', 'name' => __('Quantidade', 'maintenancecosts'), 'datatype' => 'decimal'];
      $tab[4] = ['id' => 4, 'table' => self::getTable(), 'field' => 'unit_price_applied', 'name' => __('Valor unitário', 'maintenancecosts'), 'datatype' => 'decimal'];
      $tab[5] = ['id' => 5, 'table' => self::getTable(), 'field' => 'total_price', 'name' => __('Total'), 'datatype' => 'decimal'];
      $tab[6] = ['id' => 6, 'table' => CostCenter::getTable(), 'field' => 'name', 'linkfield' => 'plugin_maintenancecosts_costcenters_id', 'name' => CostCenter::getTypeName(1), 'datatype' => 'dropdown'];
      $tab[7] = ['id' => 7, 'table' => MaterialOrigin::getTable(), 'field' => 'name', 'linkfield' => 'plugin_maintenancecosts_materialorigins_id', 'name' => MaterialOrigin::getTypeName(1), 'datatype' => 'dropdown'];
      $tab[8] = ['id' => 8, 'table' => self::getTable(), 'field' => 'price_type', 'name' => __('Tipo de preço', 'maintenancecosts'), 'datatype' => 'string'];
      $tab[9] = ['id' => 9, 'table' => self::getTable(), 'field' => 'is_deleted', 'name' => __('Canceled', 'maintenancecosts'), 'datatype' => 'bool'];
      return $tab;
   }

   public static function getTicketTotal(int $tickets_id): float
   {
      global $DB;

      $result = $DB->request([
         'SELECT' => ['SUM' => 'total_price AS total'],
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'tickets_id'  => $tickets_id,
            'is_deleted' => 0,
         ],
      ])->current();

      return (float) ($result['total'] ?? 0);
   }

   public static function showForTicket(Ticket $ticket): void
   {
      global $DB;

      Config::checkRight(Config::RIGHT_CONSUMPTION, READ);
      $tickets_id = (int) $ticket->getID();

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe plugin-maintenancecosts-consumption-table' style='table-layout:fixed; width:100%;'>";
      echo "<colgroup><col style='width:28%'><col style='width:6%'><col style='width:5%'><col style='width:8%'><col style='width:8%'><col style='width:13%'><col style='width:10%'><col style='width:9%'><col style='width:6%'><col style='width:5%'><col style='width:8%'></colgroup>";
      echo "<tr class='tab_bg_2'><th colspan='11'>" . self::getTypeName(2) . "</th></tr>";
      echo "<tr class='tab_bg_1'><td colspan='11'><strong>" . __('Total') . ":</strong> " . Config::formatCurrency(self::getTicketTotal($tickets_id)) . "</td></tr>";
      if (Config::canManageConsumption()) {
         echo "<tr class='tab_bg_1'><td colspan='11'>";
         echo "<button type='button' class='btn btn-primary' data-maintenancecosts-toggle-add>" . __('Adicionar material', 'maintenancecosts') . "</button>";
         echo "<div data-maintenancecosts-add-form style='display:none; margin-top:12px;'>";
         $form = new self();
         $form->showForm(0, ['tickets_id' => $tickets_id, 'embedded' => true]);
         echo "</div>";
         echo "</td></tr>";
      }
      echo "<tr><th>" . Material::getTypeName(1) . "</th><th class='center'>" . __('Quantidade', 'maintenancecosts') . "</th><th>" . __('Unidade', 'maintenancecosts') . "</th><th class='center'>" . __('Valor unitário', 'maintenancecosts') . "</th><th class='center'>" . __('Total') . "</th><th>" . CostCenter::getTypeName(1) . "</th><th>" . MaterialOrigin::getTypeName(1) . "</th><th>" . __('Tipo de preço', 'maintenancecosts') . "</th><th>" . __('Data', 'maintenancecosts') . "</th><th>" . __('Técnico', 'maintenancecosts') . "</th><th>" . __('Ações', 'maintenancecosts') . "</th></tr>";

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'tickets_id'  => $tickets_id,
            'is_deleted' => 0,
         ],
         'ORDER'  => 'consumption_date DESC, id DESC',
      ]);

      foreach ($iterator as $row) {
         $material = new Material();
         $material_name = $material->getFromDB((int) $row['plugin_maintenancecosts_materials_id'])
            ? $material->getName()
            : '';
         $costcenter = new CostCenter();
         $costcenter_name = $costcenter->getFromDB((int) $row['plugin_maintenancecosts_costcenters_id'])
            ? $costcenter->getName()
            : '';
         $origin = new MaterialOrigin();
         $origin_name = $origin->getFromDB((int) ($row['plugin_maintenancecosts_materialorigins_id'] ?? 0))
            ? $origin->getName()
            : '';

         echo "<tr class='tab_bg_1'>";
         echo "<td style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($material_name) . "</td>";
         echo "<td class='center'>" . self::formatQuantity((float) $row['quantity']) . "</td>";
         echo "<td>" . Html::clean($row['unit']) . "</td>";
         echo "<td class='center'>" . Config::formatCurrency((float) $row['unit_price_applied']) . "</td>";
         echo "<td class='center'>" . Config::formatCurrency((float) $row['total_price']) . "</td>";
         echo "<td style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($costcenter_name) . "</td>";
         echo "<td style='white-space:normal; overflow-wrap:anywhere;'>" . Html::clean($origin_name) . "</td>";
         echo "<td>" . Html::clean(Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi'))) . "</td>";
         echo "<td>" . Html::clean($row['consumption_date']) . "</td>";
         echo "<td>" . getUserName((int) $row['users_id']) . "</td><td>";
         if (Config::canManageConsumption()) {
            echo "<a class='btn btn-sm btn-secondary' href='" . Html::clean(self::getFormURL() . '?id=' . (int) $row['id']) . "'>" . __('Edit') . "</a> ";
            echo "<form method='post' action='" . Html::clean(self::getFormURL()) . "' style='display:inline'>";
            echo Html::hidden('id', ['value' => (int) $row['id']]);
            echo Html::hidden('tickets_id', ['value' => $tickets_id]);
            echo "<input type='text' name='delete_reason' placeholder='" . Html::clean(__('Motivo', 'maintenancecosts')) . "'>";
            echo Html::submit(__('Cancel', 'maintenancecosts'), ['name' => 'cancel', 'class' => 'btn btn-sm btn-warning']);
            Html::closeForm();
         }
         echo "</td>";
         echo "</tr>";
      }

      echo "</table></div>";
   }

   public function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $settings = Config::getSettings();
      $tickets_id = (int) ($this->fields['tickets_id'] ?? ($options['tickets_id'] ?? 0));
      $is_new = (int) $ID <= 0;
      $embedded = !empty($options['embedded']);

      echo "<div class='" . ($embedded ? 'plugin-maintenancecosts-inline-form' : 'asset') . "'>";
      echo "<form name='plugin_maintenancecosts_ticketmaterial_form' method='post' action='" . self::escape(self::getFormURL()) . "' data-submit-once>";
      echo "<input type='hidden' name='_glpi_csrf_token' value='" . self::escape(Session::getNewCSRFToken()) . "'>";
      echo "<input type='hidden' name='entities_id' value='" . (int) ($this->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)) . "'>";
      if ($tickets_id > 0) {
         echo "<input type='hidden' name='tickets_id' value='" . (int) $tickets_id . "'>";
      }
      if (!$is_new) {
         echo "<input type='hidden' name='id' value='" . (int) $ID . "'>";
      }

      echo "<div class='card'>";
      if (!$embedded) {
         echo "<div class='card-header'><h3 class='card-title'>" . self::escape($is_new ? __('Novo item - Material consumido', 'maintenancecosts') : self::getTypeName(1)) . "</h3></div>";
      }
      echo "<div class='card-body'>";
      echo "<table class='tab_cadre_fixe'>";

      if ($tickets_id <= 0) {
         echo "<tr class='tab_bg_1'><td>" . Ticket::getTypeName(1) . "</td>";
         echo "<td><input type='number' name='tickets_id' value='" . self::escape((string) $tickets_id) . "' class='form-control'></td>";
         echo "<td>" . Material::getTypeName(1) . "</td><td>";
      } else {
         echo "<tr class='tab_bg_1'><td style='width:160px'>" . Material::getTypeName(1) . "</td><td colspan='3'>";
      }
      $this->showPluginDropdown('material', 'plugin_maintenancecosts_materials_id', (int) ($this->fields['plugin_maintenancecosts_materials_id'] ?? 0));
      echo " <a class='btn btn-sm btn-secondary ms-2' href='" . self::escape(Material::getFormURL() . '?_in_modal=0') . "' target='_blank'>" . self::escape(__('Cadastrar material manual', 'maintenancecosts')) . "</a>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . MaterialOrigin::getTypeName(1) . "</td><td>";
      $this->showOriginDropdown((int) ($this->fields['plugin_maintenancecosts_materialorigins_id'] ?? 0));
      echo "</td><td>" . __('Tipo de preço', 'maintenancecosts') . "</td><td>";
      echo "<select name='price_type' class='form-select'>";
      foreach (Config::getPriceTypes() as $value => $label) {
         $selected = Config::normalizePriceType((string) ($this->fields['price_type'] ?? 'sinapi')) === $value ? ' selected' : '';
         echo "<option value='" . self::escape($value) . "'$selected>" . self::escape($label) . "</option>";
      }
      echo "</select></td></tr>";

      if ($tickets_id > 0) {
         echo "<tr class='tab_bg_1'><td>" . \Contract::getTypeName(1) . "</td><td colspan='3'>";
         $this->showContractDropdown($tickets_id, (int) ($this->fields['contracts_id'] ?? 0));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>" . CostCenter::getTypeName(1) . "</td><td>";
      $this->showPluginDropdown('costcenter', 'plugin_maintenancecosts_costcenters_id', (int) ($this->fields['plugin_maintenancecosts_costcenters_id'] ?? 0));
      echo "</td><td>" . __('Competência', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='competence' placeholder='AAAA-MM' maxlength='7' value='" . self::escape(Config::normalizeCompetence((string) ($this->fields['competence'] ?? ''))) . "' class='form-control plugin-maintenancecosts-competence'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Quantidade', 'maintenancecosts') . "</td>";
      echo "<td><input type='number' step='1' min='0' name='quantity' value='" . self::escape(self::formatQuantity((float) ($this->fields['quantity'] ?? 0))) . "' class='form-control'></td>";
      echo "<td>" . __('Valor unitário aplicado', 'maintenancecosts') . "</td>";
      $readonly = (int) $settings['allow_manual_unit_price'] ? '' : ' readonly';
      echo "<td><input type='text' inputmode='decimal' name='unit_price_applied' value='" . self::escape(Config::formatDecimalInput((float) ($this->fields['unit_price_applied'] ?? 0))) . "' class='form-control plugin-maintenancecosts-money'" . $readonly . " data-readonly-for-sinapi='" . ((int) $settings['allow_manual_unit_price'] ? '0' : '1') . "'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Unidade', 'maintenancecosts') . "</td>";
      echo "<td><input type='text' name='unit' value='" . self::escape((string) ($this->fields['unit'] ?? '')) . "' class='form-control'></td>";
      echo "<td>" . __('Data', 'maintenancecosts') . "</td>";
      echo "<td><input type='date' name='consumption_date' value='" . self::escape((string) ($this->fields['consumption_date'] ?? date('Y-m-d'))) . "' class='form-control'></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
      echo "<td colspan='3'><textarea name='comment' class='form-control' rows='3'>" . self::escape((string) ($this->fields['comment'] ?? '')) . "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Total') . "</td><td colspan='3'><strong data-maintenancecosts-total>" . Config::formatCurrency((float) ($this->fields['total_price'] ?? 0)) . "</strong></td></tr>";

      echo "</table>";
      echo "</div>";
      echo "<div class='card-footer d-flex justify-content-end'>";
      if ($is_new) {
         echo "<button type='submit' name='add' value='1' class='btn btn-primary'>" . self::escape(_x('button', 'Add')) . "</button>";
      } else {
         echo "<button type='submit' name='update' value='1' class='btn btn-primary'>" . self::escape(_x('button', 'Update')) . "</button>";
      }
      echo "</div>";
      echo "</div>";
      echo "</form>";
      echo "</div>";
      return true;
   }

   private function showPluginDropdown(string $type, string $name, int $value): void
   {
      echo "<select name='" . Html::cleanInputText($name) . "' class='form-control plugin-maintenancecosts-dropdown' data-dropdown-type='" . Html::cleanInputText($type) . "'>";
      echo "<option value='0'>-----</option>";
      if ($value > 0) {
         $label = self::getAsyncDropdownLabel($type, $value);
         if ($label !== '') {
            echo "<option value='" . (int) $value . "' selected>" . self::escape($label) . "</option>";
         }
      }
      echo "</select>";
   }

   private function showOriginDropdown(int $value): void
   {
      global $DB;

      echo "<select name='plugin_maintenancecosts_materialorigins_id' class='form-control plugin-maintenancecosts-dropdown'>";
      echo "<option value='0'>-----</option>";

      if ($DB->tableExists(MaterialOrigin::getTable())) {
         $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => MaterialOrigin::getTable(),
            'WHERE'  => ['is_active' => 1],
            'ORDER'  => ['name ASC'],
         ]);

         foreach ($iterator as $row) {
            echo "<option value='" . (int) $row['id'] . "' " . ((int) $row['id'] === $value ? 'selected' : '') . ">" . self::escape((string) $row['name']) . "</option>";
         }
      }

      echo "</select>";
   }

   private function showContractDropdown(int $tickets_id, int $value): void
   {
      echo "<select name='contracts_id' class='form-control plugin-maintenancecosts-dropdown' data-dropdown-type='contract'>";
      echo "<option value='0'>-----</option>";
      if ($value > 0) {
         $label = self::getAsyncDropdownLabel('contract', $value);
         if ($label !== '') {
            echo "<option value='" . (int) $value . "' selected>" . self::escape($label) . "</option>";
         }
      }
      echo "</select>";
   }

   private static function getAsyncDropdownLabel(string $type, int $id): string
   {
      $id = (int) $id;
      if ($id <= 0) {
         return '';
      }

      if ($type === 'material') {
         $item = new Material();
         if ($item->getFromDB($id)) {
            $label = trim((string) ($item->fields['code'] ?? '')) !== ''
               ? (string) $item->fields['code'] . ' - ' . $item->getName()
               : $item->getName();
            if (trim((string) ($item->fields['unit'] ?? '')) !== '') {
               $label .= ' (' . (string) $item->fields['unit'] . ')';
            }
            return $label;
         }
      }

      if ($type === 'costcenter') {
         $item = new CostCenter();
         if ($item->getFromDB($id)) {
            return trim((string) ($item->fields['code'] ?? '')) !== ''
               ? (string) $item->fields['code'] . ' - ' . $item->getName()
               : $item->getName();
         }
      }

      if ($type === 'contract') {
         $item = new \Contract();
         if ($item->getFromDB($id)) {
            return trim((string) ($item->fields['num'] ?? '')) !== ''
               ? (string) $item->fields['num'] . ' - ' . $item->getName()
               : (string) $id . ' - ' . $item->getName();
         }
      }

      return '';
   }

   private static function escape(string $value): string
   {
      return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
   }

   public static function formatQuantity(float $value): string
   {
      return (string) (int) round($value);
   }
}
