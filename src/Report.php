<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class Report extends CommonDBTM
{
   protected static $notable = true;
   public static $rightname = Config::RIGHT_REPORTS;

   public static function getTypeName($nb = 0)
   {
      return _n('Relatório de custos', 'Relatórios de custos', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-report-analytics';
   }

   public static function showDashboard(array $filters = []): void
   {
      Config::checkRight(Config::RIGHT_REPORTS, READ);

      $rows = self::loadRows($filters);
      $reportType = self::getReportType($filters);
      $chartType = self::getChartType($filters);
      $limit = self::getReportLimit($filters);

      self::showFilters($filters);
      self::showSummaryCards($rows);

      $field = self::chartField($reportType);
      if ($field !== '') {
         self::showDashboardChart(self::reportTypes()[$reportType], self::groupRows($rows, $field), $limit, $chartType);
      }

      switch ($reportType) {
         case 'tickets':
            self::showTicketCosts($rows, $limit);
            break;
         case 'category':
            self::showGroupedTotals(__('Custos por categoria ITIL', 'maintenancecosts'), $rows, 'itilcategory_name', $limit);
            break;
         case 'location':
            self::showGroupedTotals(__('Custos por localização', 'maintenancecosts'), $rows, 'location_name', $limit);
            break;
         case 'origin':
            self::showGroupedTotals(__('Gastos por origem do material', 'maintenancecosts'), $rows, 'materialorigin_name', $limit);
            break;
         case 'price_type':
            self::showGroupedTotals(__('Gastos por tipo de preço', 'maintenancecosts'), $rows, 'price_type_label', $limit);
            break;
         case 'contract':
            self::showGroupedTotals(__('Gastos por contrato', 'maintenancecosts'), $rows, 'contract_label', $limit);
            break;
         case 'materials':
            self::showTopMaterials($rows, $limit);
            break;
         case 'monthly':
            self::showMonthlyEvolution($rows, $limit);
            break;
         case 'costcenter':
         default:
            self::showGroupedTotals(__('Custo por centro de custo', 'maintenancecosts'), $rows, 'costcenter_label', $limit);
            break;
      }
   }

   public static function getRows(array $filters = []): array
   {
      return self::loadRows($filters);
   }

   private static function showFilters(array $filters): void
   {
      echo "<div class='spaced'>";
      echo "<form method='get' action='" . Html::clean($_SERVER['PHP_SELF']) . "'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='4'>" . __('Filtros', 'maintenancecosts') . "</th></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Data inicial', 'maintenancecosts') . "</td><td><input type='date' name='date_start' value='" . Html::cleanInputText($filters['date_start'] ?? '') . "' class='form-control'></td>";
      echo "<td>" . __('Data final', 'maintenancecosts') . "</td><td><input type='date' name='date_end' value='" . Html::cleanInputText($filters['date_end'] ?? '') . "' class='form-control'></td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Relatório', 'maintenancecosts') . "</td><td>";
      self::showStringSelect('report_type', self::reportTypes(), self::getReportType($filters));
      echo "</td><td>" . __('Tipo de gráfico', 'maintenancecosts') . "</td><td>";
      self::showStringSelect('chart_type', self::chartTypes(), self::getChartType($filters));
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Quantidade exibida', 'maintenancecosts') . "</td><td>";
      self::showStringSelect('report_limit', self::limitOptions(), self::getReportLimitMode($filters));
      echo "</td><td>" . __('Personalizado', 'maintenancecosts') . "</td><td><input type='number' name='report_limit_custom' min='1' max='500' value='" . Html::cleanInputText((string) self::getCustomLimitValue($filters)) . "' class='form-control'></td></tr>";
      echo "<tr class='tab_bg_1'><td>" . \Entity::getTypeName(1) . "</td><td>";
      self::showLocalSelect('entities_id', self::entityOptions(), (int) ($filters['entities_id'] ?? -1), __('All'));
      echo "</td><td>" . \ITILCategory::getTypeName(1) . "</td><td>";
      self::showLocalSelect('itilcategories_id', self::simpleOptions('glpi_itilcategories', 'completename'), (int) ($filters['itilcategories_id'] ?? 0));
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . CostCenter::getTypeName(1) . "</td><td>";
      self::showAsyncSelect('costcenters_id', 'costcenter', (int) ($filters['costcenters_id'] ?? 0));
      echo "</td><td>" . \Location::getTypeName(1) . "</td><td>";
      self::showLocalSelect('locations_id', self::rootLocationOptions(), (int) ($filters['locations_id'] ?? 0));
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . Material::getTypeName(1) . "</td><td>";
      self::showAsyncSelect('materials_id', 'material', (int) ($filters['materials_id'] ?? 0));
      echo "</td><td>" . __('Origem do material', 'maintenancecosts') . "</td><td>";
      self::showLocalSelect('materialorigins_id', self::materialOriginOptions(), (int) ($filters['materialorigins_id'] ?? 0));
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Tipo de preço', 'maintenancecosts') . "</td><td>";
      self::showStringSelect('price_type', ['' => '-----'] + Config::getPriceTypes(), (string) ($filters['price_type'] ?? ''));
      echo "</td><td>" . \Contract::getTypeName(1) . "</td><td>";
      self::showAsyncSelect('contracts_id', 'contract', (int) ($filters['contracts_id'] ?? 0));
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>" . Html::submit(__('Filtrar', 'maintenancecosts'), ['class' => 'btn btn-primary']) . "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }

   private static function reportTypes(): array
   {
      return [
         'costcenter' => __('Custos por centro de custo', 'maintenancecosts'),
         'category'   => __('Custos por categoria ITIL', 'maintenancecosts'),
         'location'   => __('Custos por localização', 'maintenancecosts'),
         'origin'     => __('Gastos por origem do material', 'maintenancecosts'),
         'price_type' => __('Gastos por tipo de preço', 'maintenancecosts'),
         'contract'   => __('Gastos por contrato', 'maintenancecosts'),
         'materials'  => __('Materiais mais utilizados', 'maintenancecosts'),
         'tickets'    => __('Custo por chamado', 'maintenancecosts'),
         'monthly'    => __('Evolução mensal de custos', 'maintenancecosts'),
      ];
   }

   private static function chartTypes(): array
   {
      return [
         'horizontal' => __('Barras horizontais', 'maintenancecosts'),
         'vertical'   => __('Barras verticais', 'maintenancecosts'),
         'pie'        => __('Pizza', 'maintenancecosts'),
      ];
   }

   private static function limitOptions(): array
   {
      return [
         '5'      => __('Top 5', 'maintenancecosts'),
         '10'     => __('Top 10', 'maintenancecosts'),
         '20'     => __('Top 20', 'maintenancecosts'),
         'all'    => __('Todos', 'maintenancecosts'),
         'custom' => __('Personalizado', 'maintenancecosts'),
      ];
   }

   private static function getReportType(array $filters): string
   {
      $type = (string) ($filters['report_type'] ?? 'costcenter');
      return array_key_exists($type, self::reportTypes()) ? $type : 'costcenter';
   }

   private static function getChartType(array $filters): string
   {
      $type = (string) ($filters['chart_type'] ?? 'horizontal');
      return array_key_exists($type, self::chartTypes()) ? $type : 'horizontal';
   }

   private static function getReportLimitMode(array $filters): string
   {
      $mode = (string) ($filters['report_limit'] ?? '10');
      return array_key_exists($mode, self::limitOptions()) ? $mode : '10';
   }

   private static function getCustomLimitValue(array $filters): int
   {
      $value = (int) ($filters['report_limit_custom'] ?? 10);
      if ($value <= 0) {
         return 10;
      }
      return min($value, 500);
   }

   private static function getReportLimit(array $filters): int
   {
      $mode = self::getReportLimitMode($filters);
      if ($mode === 'all') {
         return 0;
      }
      if ($mode === 'custom') {
         return self::getCustomLimitValue($filters);
      }
      return (int) $mode;
   }

   private static function chartField(string $reportType): string
   {
      return [
         'costcenter' => 'costcenter_label',
         'category'   => 'itilcategory_name',
         'location'   => 'location_name',
         'origin'     => 'materialorigin_name',
         'price_type' => 'price_type_label',
         'contract'   => 'contract_label',
      ][$reportType] ?? '';
   }

   private static function showStringSelect(string $name, array $options, string $value): void
   {
      echo "<select name='" . Html::cleanInputText($name) . "' class='form-select plugin-maintenancecosts-dropdown' style='max-width:100%; min-width:260px;'>";
      foreach ($options as $key => $label) {
         echo "<option value='" . Html::cleanInputText((string) $key) . "' " . ((string) $key === $value ? 'selected' : '') . ">" . Html::clean($label) . "</option>";
      }
      echo "</select>";
   }

   private static function showLocalSelect(string $name, array $options, int $value, string $emptyLabel = '-----'): void
   {
      echo "<select name='" . Html::cleanInputText($name) . "' class='form-select plugin-maintenancecosts-dropdown' style='max-width:100%; min-width:260px;'>";
      echo "<option value='0'>" . Html::clean($emptyLabel) . "</option>";
      foreach ($options as $id => $label) {
         echo "<option value='" . (int) $id . "' " . ((int) $id === $value ? 'selected' : '') . ">" . Html::clean($label) . "</option>";
      }
      echo "</select>";
   }

   private static function showAsyncSelect(string $name, string $type, int $value, string $emptyLabel = '-----'): void
   {
      echo "<select name='" . Html::cleanInputText($name) . "' class='form-select plugin-maintenancecosts-dropdown' data-dropdown-type='" . Html::cleanInputText($type) . "' style='max-width:100%; min-width:260px;'>";
      echo "<option value='0'>" . Html::clean($emptyLabel) . "</option>";
      if ($value > 0) {
         $label = self::getAsyncSelectLabel($type, $value);
         if ($label !== '') {
            echo "<option value='" . (int) $value . "' selected>" . Html::clean($label) . "</option>";
         }
      }
      echo "</select>";
   }

   private static function getAsyncSelectLabel(string $type, int $id): string
   {
      if ($id <= 0) {
         return '';
      }

      if ($type === 'material') {
         $item = new Material();
         if ($item->getFromDB($id)) {
            return self::labelWithCode($item->fields['code'] ?? '', $item->fields['name'] ?? '');
         }
      }

      if ($type === 'costcenter') {
         $item = new CostCenter();
         if ($item->getFromDB($id)) {
            return self::labelWithCode($item->fields['code'] ?? '', $item->fields['name'] ?? '');
         }
      }

      if ($type === 'contract') {
         $item = new \Contract();
         if ($item->getFromDB($id)) {
            return self::labelWithCode($item->fields['num'] ?: $id, $item->fields['name'] ?? '');
         }
      }

      return '';
   }

   private static function simpleOptions(string $table, string $labelField): array
   {
      global $DB;
      if (!$DB->tableExists($table)) {
         return [];
      }

      $options = [];
      foreach ($DB->request(['SELECT' => ['id', $labelField], 'FROM' => $table, 'ORDER' => $labelField . ' ASC']) as $row) {
         $label = trim((string) ($row[$labelField] ?? ''));
         if ($label !== '') {
            $options[(int) $row['id']] = $label;
         }
      }
      return $options;
   }

   private static function rootLocationOptions(): array
   {
      global $DB;
      if (!$DB->tableExists('glpi_locations')) {
         return [];
      }

      $options = [];
      foreach ($DB->request([
         'SELECT' => ['id', 'name', 'completename'],
         'FROM'   => 'glpi_locations',
         'WHERE'  => ['locations_id' => 0],
         'ORDER'  => 'name ASC',
      ]) as $row) {
         $label = trim((string) ($row['name'] ?? ''));
         if ($label === '') {
            $label = trim((string) ($row['completename'] ?? ''));
         }
         if ($label !== '') {
            $options[(int) $row['id']] = $label;
         }
      }
      return $options;
   }

   private static function entityOptions(): array
   {
      return [-1 => __('All')] + self::simpleOptions('glpi_entities', 'completename');
   }

   private static function costCenterOptions(): array
   {
      global $DB;
      $options = [];
      foreach ($DB->request(['SELECT' => ['id', 'code', 'name'], 'FROM' => CostCenter::getTable(), 'WHERE' => ['is_active' => 1], 'ORDER' => 'name ASC']) as $row) {
         $options[(int) $row['id']] = self::labelWithCode($row['code'] ?? '', $row['name'] ?? '');
      }
      return $options;
   }

   private static function materialOptions(): array
   {
      global $DB;
      $options = [];
      foreach ($DB->request(['SELECT' => ['id', 'code', 'name'], 'FROM' => Material::getTable(), 'WHERE' => ['is_active' => 1], 'ORDER' => ['code ASC', 'name ASC']]) as $row) {
         $options[(int) $row['id']] = self::labelWithCode($row['code'] ?? '', $row['name'] ?? '');
      }
      return $options;
   }

   private static function materialOriginOptions(): array
   {
      global $DB;
      $options = [];
      foreach ($DB->request(['SELECT' => ['id', 'name'], 'FROM' => MaterialOrigin::getTable(), 'WHERE' => ['is_active' => 1], 'ORDER' => 'name ASC']) as $row) {
         $options[(int) $row['id']] = (string) $row['name'];
      }
      return $options;
   }

   private static function contractOptions(): array
   {
      global $DB;
      if (!$DB->tableExists('glpi_contracts')) {
         return [];
      }

      $options = [];
      foreach ($DB->request(['SELECT' => ['id', 'name', 'num'], 'FROM' => 'glpi_contracts', 'WHERE' => ['is_deleted' => 0], 'ORDER' => 'name ASC']) as $row) {
         $options[(int) $row['id']] = self::labelWithCode($row['num'] ?: $row['id'], $row['name'] ?? '');
      }
      return $options;
   }

   private static function loadRows(array $filters): array
   {
      global $DB;

      $where = [TicketMaterial::getTable() . '.is_deleted' => 0];
      if (!empty($filters['date_start'])) {
         $where[] = [TicketMaterial::getTable() . '.consumption_date' => ['>=', $filters['date_start']]];
      }
      if (!empty($filters['date_end'])) {
         $where[] = [TicketMaterial::getTable() . '.consumption_date' => ['<=', $filters['date_end']]];
      }
      foreach ([
         'costcenters_id' => 'plugin_maintenancecosts_costcenters_id',
         'materials_id' => 'plugin_maintenancecosts_materials_id',
         'materialorigins_id' => 'plugin_maintenancecosts_materialorigins_id',
         'contracts_id' => 'contracts_id',
      ] as $filter => $field) {
         if (!empty($filters[$filter])) {
            $where[TicketMaterial::getTable() . '.' . $field] = (int) $filters[$filter];
         }
      }
      if (!empty($filters['price_type']) && array_key_exists((string) $filters['price_type'], Config::getPriceTypes())) {
         $where[TicketMaterial::getTable() . '.price_type'] = (string) $filters['price_type'];
      }
      if (isset($filters['entities_id']) && (int) $filters['entities_id'] >= 0) {
         $where[TicketMaterial::getTable() . '.entities_id'] = (int) $filters['entities_id'];
      }

      $iterator = $DB->request([
         'SELECT' => [
            TicketMaterial::getTable() . '.*',
            'glpi_tickets.name AS ticket_name',
            'glpi_tickets.locations_id',
            'glpi_tickets.itilcategories_id',
            Material::getTable() . '.name AS material_name',
            Material::getTable() . '.code AS material_code',
            MaterialOrigin::getTable() . '.name AS materialorigin_name',
            'glpi_locations.completename AS location_name',
            'glpi_itilcategories.completename AS itilcategory_name',
         ],
         'FROM' => TicketMaterial::getTable(),
         'LEFT JOIN' => [
            'glpi_tickets' => [
               'FKEY' => [TicketMaterial::getTable() => 'tickets_id', 'glpi_tickets' => 'id'],
            ],
            Material::getTable() => [
               'FKEY' => [TicketMaterial::getTable() => 'plugin_maintenancecosts_materials_id', Material::getTable() => 'id'],
            ],
            MaterialOrigin::getTable() => [
               'FKEY' => [TicketMaterial::getTable() => 'plugin_maintenancecosts_materialorigins_id', MaterialOrigin::getTable() => 'id'],
            ],
            'glpi_locations' => [
               'FKEY' => ['glpi_tickets' => 'locations_id', 'glpi_locations' => 'id'],
            ],
            'glpi_itilcategories' => [
               'FKEY' => ['glpi_tickets' => 'itilcategories_id', 'glpi_itilcategories' => 'id'],
            ],
         ],
         'WHERE' => $where,
         'ORDER' => TicketMaterial::getTable() . '.consumption_date DESC, ' . TicketMaterial::getTable() . '.id DESC',
         'LIMIT' => 5000,
      ]);

      $rows = [];
      foreach ($iterator as $row) {
         if (!empty($filters['locations_id']) && !self::locationBelongsToRoot((int) ($row['locations_id'] ?? 0), (int) $filters['locations_id'])) {
            continue;
         }
         if (!empty($filters['itilcategories_id']) && (int) ($row['itilcategories_id'] ?? 0) !== (int) $filters['itilcategories_id']) {
            continue;
         }
         $row['costcenter_label'] = TicketMaterial::getCostCenterDisplayName(
            (int) ($row['plugin_maintenancecosts_costcenters_id'] ?? 0),
            (string) ($row['costcenter_source'] ?? 'new')
         );
         $row['price_type_label'] = Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi'));
         $row['contract_label'] = self::getContractLabel((int) ($row['contracts_id'] ?? 0), (int) $row['tickets_id']);
         if (!empty($filters['costcenters_id']) && (int) ($row['plugin_maintenancecosts_costcenters_id'] ?? 0) !== (int) $filters['costcenters_id']) {
            continue;
         }
         $rows[] = $row;
      }

      return $rows;
   }

   private static function locationBelongsToRoot(int $locations_id, int $root_id): bool
   {
      global $DB;
      static $parents = [];

      if ($root_id <= 0) {
         return true;
      }
      if ($locations_id <= 0 || !$DB->tableExists('glpi_locations')) {
         return false;
      }

      $current = $locations_id;
      while ($current > 0) {
         if ($current === $root_id) {
            return true;
         }

         if (!array_key_exists($current, $parents)) {
            $row = $DB->request([
               'SELECT' => ['locations_id'],
               'FROM'   => 'glpi_locations',
               'WHERE'  => ['id' => $current],
               'LIMIT'  => 1,
            ])->current();
            $parents[$current] = is_array($row) ? (int) ($row['locations_id'] ?? 0) : 0;
         }

         $current = (int) $parents[$current];
      }

      return false;
   }

   private static function getContractLabel(int $contracts_id, int $tickets_id): string
   {
      global $DB;
      if ($contracts_id <= 0 && $tickets_id > 0 && class_exists('Ticket_Contract') && $DB->tableExists(\Ticket_Contract::getTable())) {
         $link = $DB->request([
            'SELECT' => ['contracts_id'],
            'FROM'   => \Ticket_Contract::getTable(),
            'WHERE'  => ['tickets_id' => $tickets_id],
            'ORDER'  => 'id ASC',
            'LIMIT'  => 1,
         ])->current();
         $contracts_id = (int) ($link['contracts_id'] ?? 0);
      }

      $contract = new \Contract();
      if ($contracts_id <= 0 || !$contract->getFromDB($contracts_id)) {
         return '';
      }

      return self::labelWithCode((string) $contracts_id, (string) ($contract->fields['name'] ?? ''));
   }

   private static function showSummaryCards(array $rows): void
   {
      $total = 0.0;
      $tickets = [];
      $contracts = [];
      foreach ($rows as $row) {
         $total += (float) $row['total_price'];
         $tickets[(int) $row['tickets_id']] = true;
         if (!empty($row['contracts_id'])) {
            $contracts[(int) $row['contracts_id']] = true;
         }
      }

      echo "<div class='plugin-maintenancecosts-summary'>";
      foreach ([
         __('Custo total', 'maintenancecosts') => Config::formatCurrency($total),
         __('Lançamentos', 'maintenancecosts') => (string) count($rows),
         \Ticket::getTypeName(2) => (string) count($tickets),
         \Contract::getTypeName(2) => (string) count($contracts),
      ] as $label => $value) {
         echo "<div class='plugin-maintenancecosts-summary-card'><span>" . Html::clean($label) . "</span><strong>" . Html::clean($value) . "</strong></div>";
      }
      echo "</div>";
   }

   private static function showGroupedTotals(string $title, array $rows, string $field, int $limit = 10): void
   {
      $grouped = self::limitRows(self::groupRows($rows, $field), $limit);
      echo "<div class='spaced'><table class='tab_cadre_fixe plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
      echo "<thead><tr class='tab_bg_2'><th colspan='4'>" . Html::clean($title) . "</th></tr>";
      echo "<tr><th data-sort='text'>" . __('Nome', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Itens', 'maintenancecosts') . "</th><th data-sort='number'>" . \Ticket::getTypeName(2) . "</th><th data-sort='currency'>" . __('Total') . "</th></tr></thead><tbody>";
      foreach ($grouped as $row) {
         echo "<tr class='tab_bg_1'><td class='text-start'>" . Html::clean($row['name']) . "</td><td data-value='" . (int) $row['items'] . "'>" . (int) $row['items'] . "</td><td data-value='" . (int) $row['tickets_count'] . "'>" . (int) $row['tickets_count'] . "</td><td data-value='" . Html::clean((string) (float) $row['total']) . "'>" . Config::formatCurrency((float) $row['total']) . "</td></tr>";
      }
      echo "</tbody></table></div>";
   }

   private static function showTicketCosts(array $rows, int $limit = 10): void
   {
      $grouped = [];
      foreach ($rows as $row) {
         $id = (int) $row['tickets_id'];
         if (!isset($grouped[$id])) {
            $grouped[$id] = ['ticket' => $id, 'name' => $row['ticket_name'] ?? '', 'total' => 0.0, 'items' => 0];
         }
         $grouped[$id]['total'] += (float) $row['total_price'];
         $grouped[$id]['items']++;
      }
      usort($grouped, static function($a, $b) { return $b['total'] <=> $a['total']; });
      $grouped = self::limitRows($grouped, $limit);
      echo "<div class='spaced'><table class='tab_cadre_fixe plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
      echo "<thead><tr class='tab_bg_2'><th colspan='4'>" . __('Custo por chamado', 'maintenancecosts') . "</th></tr>";
      echo "<tr><th data-sort='number'>" . \Ticket::getTypeName(1) . "</th><th data-sort='text'>" . __('Título', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Itens', 'maintenancecosts') . "</th><th data-sort='currency'>" . __('Total') . "</th></tr></thead><tbody>";
      foreach ($grouped as $row) {
         echo "<tr class='tab_bg_1'><td data-value='" . (int) $row['ticket'] . "'>" . (int) $row['ticket'] . "</td><td class='text-start'>" . Html::clean($row['name']) . "</td><td data-value='" . (int) $row['items'] . "'>" . (int) $row['items'] . "</td><td data-value='" . Html::clean((string) (float) $row['total']) . "'>" . Config::formatCurrency((float) $row['total']) . "</td></tr>";
      }
      echo "</tbody></table></div>";
   }

   private static function showTopMaterials(array $rows, int $limit = 10): void
   {
      $grouped = [];
      foreach ($rows as $row) {
         $key = (string) ($row['material_code'] ?? '') . '|' . (string) ($row['material_name'] ?? '');
         if (!isset($grouped[$key])) {
            $grouped[$key] = ['code' => $row['material_code'] ?? '', 'name' => $row['material_name'] ?? '', 'quantity' => 0.0, 'total' => 0.0, 'tickets' => []];
         }
         $grouped[$key]['quantity'] += (float) $row['quantity'];
         $grouped[$key]['total'] += (float) $row['total_price'];
         $grouped[$key]['tickets'][(int) $row['tickets_id']] = true;
      }
      usort($grouped, static function($a, $b) { return $b['total'] <=> $a['total']; });
      $grouped = self::limitRows($grouped, $limit);
      echo "<div class='spaced'><table class='tab_cadre_fixe plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
      echo "<thead><tr class='tab_bg_2'><th colspan='5'>" . __('Materiais mais utilizados', 'maintenancecosts') . "</th></tr>";
      echo "<tr><th data-sort='text'>" . __('Código SINAPI', 'maintenancecosts') . "</th><th data-sort='text'>" . Material::getTypeName(1) . "</th><th data-sort='number'>" . __('Quantidade', 'maintenancecosts') . "</th><th data-sort='number'>" . \Ticket::getTypeName(2) . "</th><th data-sort='currency'>" . __('Total') . "</th></tr></thead><tbody>";
      foreach ($grouped as $row) {
         $sortCode = preg_match('/^\d+$/', (string) $row['code']) ? str_pad((string) $row['code'], 8, '0', STR_PAD_LEFT) : (string) $row['code'];
         echo "<tr class='tab_bg_1'><td data-value='" . Html::clean($sortCode) . "'>" . Html::clean($row['code']) . "</td><td class='text-start'>" . Html::clean($row['name']) . "</td><td data-value='" . Html::clean((string) (float) $row['quantity']) . "'>" . TicketMaterial::formatQuantity((float) $row['quantity']) . "</td><td data-value='" . count($row['tickets']) . "'>" . count($row['tickets']) . "</td><td data-value='" . Html::clean((string) (float) $row['total']) . "'>" . Config::formatCurrency((float) $row['total']) . "</td></tr>";
      }
      echo "</tbody></table></div>";
   }

   private static function showMonthlyEvolution(array $rows, int $limit = 10): void
   {
      $grouped = [];
      foreach ($rows as $row) {
         $month = substr((string) ($row['consumption_date'] ?: $row['date_creation']), 0, 7);
         if (!isset($grouped[$month])) {
            $grouped[$month] = ['month' => $month, 'total' => 0.0, 'items' => 0, 'tickets' => []];
         }
         $grouped[$month]['total'] += (float) $row['total_price'];
         $grouped[$month]['items']++;
         $grouped[$month]['tickets'][(int) $row['tickets_id']] = true;
      }
      ksort($grouped);
      $grouped = self::limitRows(array_values($grouped), $limit);
      echo "<div class='spaced'><table class='tab_cadre_fixe plugin-maintenancecosts-table plugin-maintenancecosts-sortable'>";
      echo "<thead><tr class='tab_bg_2'><th colspan='4'>" . __('Evolução mensal de custos', 'maintenancecosts') . "</th></tr>";
      echo "<tr><th data-sort='text'>" . __('Mês', 'maintenancecosts') . "</th><th data-sort='number'>" . __('Itens', 'maintenancecosts') . "</th><th data-sort='number'>" . \Ticket::getTypeName(2) . "</th><th data-sort='currency'>" . __('Total') . "</th></tr></thead><tbody>";
      foreach ($grouped as $row) {
         echo "<tr class='tab_bg_1'><td>" . Html::clean($row['month']) . "</td><td data-value='" . (int) $row['items'] . "'>" . (int) $row['items'] . "</td><td data-value='" . count($row['tickets']) . "'>" . count($row['tickets']) . "</td><td data-value='" . Html::clean((string) (float) $row['total']) . "'>" . Config::formatCurrency((float) $row['total']) . "</td></tr>";
      }
      echo "</tbody></table></div>";
   }

   private static function groupRows(array $rows, string $field): array
   {
      $grouped = [];
      foreach ($rows as $row) {
         $name = trim((string) ($row[$field] ?? ''));
         if ($name === '') {
            $name = __('Not defined');
         }
         if (!isset($grouped[$name])) {
            $grouped[$name] = ['name' => $name, 'total' => 0.0, 'items' => 0, 'tickets' => []];
         }
         $grouped[$name]['total'] += (float) $row['total_price'];
         $grouped[$name]['items']++;
         $grouped[$name]['tickets'][(int) $row['tickets_id']] = true;
      }
      foreach ($grouped as &$row) {
         $row['tickets_count'] = count($row['tickets']);
      }
      unset($row);
      usort($grouped, static function($a, $b) { return $b['total'] <=> $a['total']; });
      return $grouped;
   }

   private static function limitRows(array $rows, int $limit): array
   {
      if ($limit <= 0) {
         return $rows;
      }
      return array_slice($rows, 0, $limit);
   }

   private static function showDashboardChart(string $title, array $rows, int $limit, string $chartType): void
   {
      $rows = self::limitRows($rows, $limit);
      $max = 0.0;
      $total = 0.0;
      foreach ($rows as $row) {
         $max = max($max, (float) $row['total']);
         $total += (float) $row['total'];
      }

      echo "<div class='spaced'><div class='card' style='padding:14px;'>";
      echo "<h3 style='margin:0 0 12px 0; text-align:center;'>" . Html::clean($title) . "</h3>";
      if (!count($rows)) {
         echo "<div class='text-muted center'>" . __('No item found') . "</div>";
      } elseif ($chartType === 'vertical') {
         self::showVerticalChart($rows, $max);
      } elseif ($chartType === 'pie') {
         self::showPieChart($rows, $total);
      } else {
         self::showHorizontalChart($rows, $max);
      }
      echo "</div></div>";
   }

   private static function showHorizontalChart(array $rows, float $max): void
   {
      foreach ($rows as $row) {
         $percent = $max > 0 ? max(2, (int) round(((float) $row['total'] / $max) * 100)) : 0;
         echo "<div class='plugin-maintenancecosts-bar-row'><div>" . Html::clean($row['name']) . "</div><div><span style='width:" . $percent . "%'></span></div><strong>" . Config::formatCurrency((float) $row['total']) . "</strong></div>";
      }
   }

   private static function showVerticalChart(array $rows, float $max): void
   {
      echo "<div class='plugin-maintenancecosts-vertical-chart'>";
      foreach ($rows as $row) {
         $height = $max > 0 ? max(12, (int) round(((float) $row['total'] / $max) * 180)) : 12;
         echo "<div><strong>" . Config::formatCurrency((float) $row['total']) . "</strong><span style='height:" . $height . "px'></span><small>" . Html::clean($row['name']) . "</small></div>";
      }
      echo "</div>";
   }

   private static function showPieChart(array $rows, float $total): void
   {
      $colors = ['#4f6fa8', '#f0ad4e', '#5cb85c', '#d9534f', '#5bc0de', '#8f6bb3', '#6c757d', '#2f9e44', '#7952b3', '#20c997'];
      $segments = [];
      $position = 0.0;
      foreach (array_values($rows) as $index => $row) {
         $share = $total > 0 ? ((float) $row['total'] / $total) * 100 : 0;
         $start = $position;
         $position += $share;
         $segments[] = $colors[$index % count($colors)] . ' ' . round($start, 4) . '% ' . round($position, 4) . '%';
      }

      echo "<div class='plugin-maintenancecosts-pie-wrap'><div class='plugin-maintenancecosts-pie' style='background:conic-gradient(" . Html::clean(implode(', ', $segments)) . ");'></div><div>";
      foreach (array_values($rows) as $index => $row) {
         $color = $colors[$index % count($colors)];
         $share = $total > 0 ? ((float) $row['total'] / $total) * 100 : 0;
         echo "<div class='plugin-maintenancecosts-pie-item'><span style='background:" . Html::clean($color) . "'></span><div>" . Html::clean($row['name']) . "</div><strong>" . Config::formatCurrency((float) $row['total']) . "</strong><em>" . Html::formatNumber($share, true, 1) . "%</em></div>";
      }
      echo "</div></div>";
   }

   private static function labelWithCode($code, $name): string
   {
      $code = trim((string) $code);
      $name = trim((string) $name);
      if ($code !== '' && $name !== '') {
         return $code . ' - ' . $name;
      }
      return $code !== '' ? $code : $name;
   }
}
