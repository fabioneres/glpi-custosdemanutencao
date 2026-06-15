<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Exporter
{
   private static function sendTable(string $baseFilename, array $headers, array $rows, string $format): void
   {
      if ($format === 'pdf') {
         self::sendPdf($baseFilename . '.pdf', $headers, $rows);
         return;
      }

      self::sendCsv($baseFilename . '.csv', $headers, $rows);
   }

   public static function sendCsv(string $filename, array $headers, array $rows): void
   {
      header('Content-Type: text/csv; charset=UTF-8');
      header('Content-Disposition: attachment; filename="' . $filename . '"');

      $out = fopen('php://output', 'wb');
      fwrite($out, "\xEF\xBB\xBF");
      fputcsv($out, $headers, ';');
      foreach ($rows as $row) {
         fputcsv($out, $row, ';');
      }
      fclose($out);
      exit;
   }

   public static function sendPdf(string $filename, array $headers, array $rows): void
   {
      $lines = [implode(' | ', $headers)];
      $lines[] = str_repeat('-', 100);
      foreach ($rows as $row) {
         $lines[] = implode(' | ', array_map(static function($value) {
            $value = preg_replace('/\s+/', ' ', (string) $value) ?? '';
            return mb_substr($value, 0, 120);
         }, $row));
      }

      $pages = array_chunk($lines, 42);
      $objects = [];
      $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
      $kids = [];
      $contentIds = [];
      foreach ($pages as $index => $pageLines) {
         $pageObj = 3 + ($index * 2);
         $contentObj = $pageObj + 1;
         $kids[] = $pageObj . ' 0 R';
         $contentIds[] = [$pageObj, $contentObj, $pageLines];
      }
      $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';

      foreach ($contentIds as [$pageObj, $contentObj, $pageLines]) {
         $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /Contents ' . $contentObj . ' 0 R >>';
         $stream = "BT /F1 8 Tf 36 560 Td 12 TL\n";
         foreach ($pageLines as $line) {
            $stream .= '(' . self::pdfEscape($line) . ") Tj T*\n";
         }
         $stream .= 'ET';
         $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
      }

      $pdf = "%PDF-1.4\n";
      $offsets = [0];
      foreach ($objects as $index => $object) {
         $offsets[] = strlen($pdf);
         $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
      }
      $xref = strlen($pdf);
      $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
      for ($i = 1; $i <= count($objects); $i++) {
         $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
      }
      $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      echo $pdf;
      exit;
   }

   private static function pdfEscape(string $value): string
   {
      $value = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $value);
      return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $value);
   }

   public static function exportMaterials(string $format = 'csv'): void
   {
      global $DB;
      Config::checkRight(Config::RIGHT_MATERIALS, READ);

      $rows = [];
      $iterator = $DB->request(['FROM' => Material::getTable(), 'ORDER' => 'name ASC']);
      foreach ($iterator as $row) {
         $rows[] = [$row['code'], $row['name'], $row['unit'], $row['category'], (int) $row['is_active']];
      }
      self::sendTable('maintenancecosts-materiais', ['codigo', 'nome', 'unidade', 'categoria', 'ativo'], $rows, $format);
   }

   public static function exportPrices(int $materials_id = 0, string $format = 'csv', string $priceType = 'all'): void
   {
      global $DB;
      Config::checkRight(Config::RIGHT_PRICES, READ);

      $criteria = ['FROM' => Price::getTable(), 'ORDER' => 'competence DESC, id DESC'];
      $where = [];
      if ($materials_id > 0) {
         $where['plugin_maintenancecosts_materials_id'] = $materials_id;
      }
      if ($priceType !== 'all' && $priceType !== '') {
         $where['price_type'] = Config::normalizePriceType($priceType);
      }
      if (count($where)) {
         $criteria['WHERE'] = $where;
      }

      $rows = [];
      $iterator = $DB->request($criteria);
      foreach ($iterator as $row) {
         $material = new Material();
         $materialName = '';
         $materialUnit = '';
         $materialCode = '';
         if ($material->getFromDB((int) $row['plugin_maintenancecosts_materials_id'])) {
            $materialName = $material->getName();
            $materialUnit = (string) ($material->fields['unit'] ?? '');
            $materialCode = (string) ($material->fields['code'] ?? '');
         }
         if (Config::normalizePriceType((string) ($row['price_type'] ?? 'sinapi')) === 'cotacao_mercado') {
            $rows[] = [
               $materialCode,
               $materialName,
               $materialUnit,
               self::formatPlainNumber((float) ($row['quote_quantity'] ?? 0)),
               Config::formatCurrency((float) $row['unit_price']),
               Config::formatCurrency((float) ($row['quote_price_1'] ?? 0)),
               Config::formatCurrency((float) ($row['quote_price_2'] ?? 0)),
               Config::formatCurrency((float) ($row['quote_price_3'] ?? 0)),
               $row['competence'],
               $row['source'],
               $row['date_creation'],
            ];
            continue;
         }

         $rows[] = [$materialCode, $materialName, $materialUnit, $row['competence'], Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi')), Config::formatCurrency((float) $row['unit_price']), $row['source'], $row['date_creation']];
      }
      $headers = $priceType === 'cotacao_mercado'
         ? ['codigo', 'material', 'unidade', 'quantidade', 'valor', 'cotacao_1', 'cotacao_2', 'cotacao_3', 'competencia', 'fonte', 'data_criacao']
         : ['codigo_sinapi', 'material', 'unidade', 'competencia', 'tipo_preco', 'valor_unitario', 'fonte', 'data_criacao'];
      self::sendTable('maintenancecosts-precos', $headers, $rows, $format);
   }

   private static function formatPlainNumber(float $value): string
   {
      return abs($value - round($value)) < 0.000001
         ? (string) (int) round($value)
         : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
   }

   public static function exportCostCenters(string $format = 'csv'): void
   {
      global $DB;
      Config::checkRight(Config::RIGHT_COSTCENTERS, READ);

      $rows = [];
      $iterator = $DB->request(['FROM' => CostCenter::getTable(), 'ORDER' => 'name ASC']);
      foreach ($iterator as $row) {
         $rows[] = [$row['code'], $row['name'], $row['address'] ?? '', $row['floor'] ?? '', $row['campus'] ?? '', $row['department'] ?? '', $row['usage_type'] ?? '', (int) $row['is_active']];
      }
      self::sendTable('maintenancecosts-centros-custo', ['codigo', 'nome', 'endereco', 'piso', 'campus', 'departamento_disciplina_setor', 'utilizacao', 'ativo'], $rows, $format);
   }

   public static function exportReport(array $filters, string $format = 'csv'): void
   {
      Config::checkRight(Config::RIGHT_REPORTS, READ);
      if ($format === 'pdf') {
         self::sendReportPdf($filters);
         return;
      }

      $rows = [];
      foreach (Report::getRows($filters) as $row) {
         $rows[] = [
            $row['tickets_id'],
            $row['ticket_name'] ?? '',
            $row['material_code'] ?? '',
            $row['material_name'] ?? '',
            $row['quantity'],
            $row['unit'],
            Config::formatCurrency((float) $row['unit_price_applied']),
            Config::formatCurrency((float) $row['total_price']),
            $row['costcenter_code'] ?? '',
            $row['costcenter_name'] ?? '',
            $row['price_type_label'] ?? Config::getPriceTypeLabel((string) ($row['price_type'] ?? 'sinapi')),
            $row['contract_label'] ?? '',
            $row['location_name'] ?? '',
            $row['consumption_date'],
            $row['users_id'],
         ];
      }
      self::sendTable(
         'maintenancecosts-relatorio',
         ['chamado_id', 'chamado', 'codigo_sinapi', 'material', 'quantidade', 'unidade', 'valor_unitario', 'total', 'codigo_centro_custo', 'centro_custo', 'origem_preco', 'contrato', 'localizacao', 'data_consumo', 'usuario_id'],
         $rows,
         $format
      );
   }

   private static function sendReportPdf(array $filters): void
   {
      $rows = Report::getRows($filters);
      $reportType = (string) ($filters['report_type'] ?? 'costcenter');
      $limit = self::reportLimit($filters);
      $field = [
         'costcenter' => 'costcenter_label',
         'category'   => 'itilcategory_name',
         'location'   => 'location_name',
         'origin'     => 'materialorigin_name',
         'price_type' => 'price_type_label',
         'contract'   => 'contract_label',
      ][$reportType] ?? 'costcenter_label';

      $title = [
         'costcenter' => 'Custos por centro de custo',
         'category'   => 'Custos por categoria ITIL',
         'location'   => 'Custos por localizacao',
         'origin'     => 'Gastos por origem do material',
         'price_type' => 'Gastos por tipo de preco',
         'contract'   => 'Gastos por contrato',
         'materials'  => 'Materiais mais utilizados',
         'tickets'    => 'Custo por chamado',
         'monthly'    => 'Evolucao mensal de custos',
      ][$reportType] ?? 'Relatorio de custos';

      $groups = self::reportPdfGroups($rows, $reportType, $field);
      $limitedGroups = self::limitRows($groups, $limit);
      $total = 0.0;
      $tickets = [];
      $contracts = [];
      foreach ($rows as $row) {
         $total += (float) ($row['total_price'] ?? 0);
         $tickets[(int) ($row['tickets_id'] ?? 0)] = true;
         if (!empty($row['contracts_id'])) {
            $contracts[(int) $row['contracts_id']] = true;
         }
      }

      $content = [];
      self::pdfText($content, 28, 565, 'Custos de Manutencao - ' . $title, 14, true);
      $cards = [
         ['Custo total', Config::formatCurrency($total)],
         ['Lancamentos', (string) count($rows)],
         ['Chamados', (string) max(0, count($tickets) - (isset($tickets[0]) ? 1 : 0))],
         ['Contratos', (string) count($contracts)],
      ];
      $x = 28;
      foreach ($cards as $card) {
         self::pdfRect($content, $x, 510, 180, 38, false);
         self::pdfText($content, $x + 8, 532, $card[0], 7);
         self::pdfText($content, $x + 8, 516, $card[1], 12, true);
         $x += 196;
      }

      self::pdfRect($content, 28, 320, 786, 170, false);
      self::pdfText($content, 365, 474, $title, 9, true);
      $max = 0.0;
      foreach (array_slice($limitedGroups, 0, 8) as $group) {
         $max = max($max, (float) $group['total']);
      }
      $y = 448;
      foreach (array_slice($limitedGroups, 0, 8) as $group) {
         $width = $max > 0 ? (int) round(((float) $group['total'] / $max) * 455) : 0;
         self::pdfText($content, 48, $y, self::shortText((string) $group['name'], 44), 7);
         self::pdfFilledRect($content, 230, $y - 5, max(2, $width), 7, 0.32, 0.43, 0.66);
         self::pdfText($content, 704, $y, Config::formatCurrency((float) $group['total']), 7, true);
         $y -= 18;
      }

      self::pdfText($content, 28, 290, $title, 10, true);
      self::pdfText($content, 28, 270, 'Nome', 8, true);
      self::pdfText($content, 555, 270, 'Itens', 8, true);
      self::pdfText($content, 635, 270, 'Chamados', 8, true);
      self::pdfText($content, 735, 270, 'Total', 8, true);
      $y = 252;
      foreach (array_slice($limitedGroups, 0, 14) as $group) {
         self::pdfText($content, 28, $y, self::shortText((string) $group['name'], 84), 7);
         self::pdfText($content, 565, $y, (string) (int) $group['items'], 7);
         self::pdfText($content, 655, $y, (string) (int) $group['tickets_count'], 7);
         self::pdfText($content, 730, $y, Config::formatCurrency((float) $group['total']), 7);
         $y -= 14;
      }

      self::sendRawPdf('maintenancecosts-relatorio.pdf', implode("\n", $content));
   }

   private static function reportLimit(array $filters): int
   {
      $mode = (string) ($filters['report_limit'] ?? '10');
      if ($mode === 'all') {
         return 0;
      }
      if ($mode === 'custom') {
         $value = (int) ($filters['report_limit_custom'] ?? 10);
         return $value > 0 ? min($value, 500) : 10;
      }
      return in_array($mode, ['5', '10', '20'], true) ? (int) $mode : 10;
   }

   private static function limitRows(array $rows, int $limit): array
   {
      if ($limit <= 0) {
         return $rows;
      }
      return array_slice($rows, 0, $limit);
   }

   private static function reportPdfGroups(array $rows, string $reportType, string $field): array
   {
      $groups = [];
      foreach ($rows as $row) {
         if ($reportType === 'tickets') {
            $name = (string) ($row['tickets_id'] ?? '') . ' - ' . (string) ($row['ticket_name'] ?? '');
         } elseif ($reportType === 'materials') {
            $name = trim((string) ($row['material_code'] ?? '') . ' - ' . (string) ($row['material_name'] ?? ''));
         } elseif ($reportType === 'monthly') {
            $name = substr((string) (($row['consumption_date'] ?? '') ?: ($row['date_creation'] ?? '')), 0, 7);
         } else {
            $name = trim((string) ($row[$field] ?? ''));
         }
         if ($name === '') {
            $name = 'Not defined';
         }
         if (!isset($groups[$name])) {
            $groups[$name] = ['name' => $name, 'items' => 0, 'tickets' => [], 'total' => 0.0];
         }
         $groups[$name]['items']++;
         $groups[$name]['tickets'][(int) ($row['tickets_id'] ?? 0)] = true;
         $groups[$name]['total'] += (float) ($row['total_price'] ?? 0);
      }
      foreach ($groups as &$group) {
         unset($group['tickets'][0]);
         $group['tickets_count'] = count($group['tickets']);
      }
      unset($group);
      usort($groups, static function($a, $b) { return $b['total'] <=> $a['total']; });
      return $groups;
   }

   private static function pdfText(array &$content, int $x, int $y, string $text, int $size = 8, bool $bold = false): void
   {
      $font = $bold ? 'F2' : 'F1';
      $content[] = sprintf('BT /%s %d Tf %d %d Td (%s) Tj ET', $font, $size, $x, $y, self::pdfEscape($text));
   }

   private static function pdfRect(array &$content, int $x, int $y, int $w, int $h, bool $fill): void
   {
      $content[] = sprintf('0.82 0.86 0.92 RG %d %d %d %d re %s', $x, $y, $w, $h, $fill ? 'f' : 'S');
   }

   private static function pdfFilledRect(array &$content, int $x, int $y, int $w, int $h, float $r, float $g, float $b): void
   {
      $content[] = sprintf('%.2f %.2f %.2f rg %d %d %d %d re f', $r, $g, $b, $x, $y, $w, $h);
   }

   private static function shortText(string $text, int $length): string
   {
      $text = preg_replace('/\s+/', ' ', $text) ?? '';
      return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 3) . '...' : $text;
   }

   private static function sendRawPdf(string $filename, string $stream): void
   {
      $objects = [
         '<< /Type /Catalog /Pages 2 0 R >>',
         '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
         '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >> /Contents 4 0 R >>',
         '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
      ];
      $pdf = "%PDF-1.4\n";
      $offsets = [0];
      foreach ($objects as $index => $object) {
         $offsets[] = strlen($pdf);
         $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
      }
      $xref = strlen($pdf);
      $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
      for ($i = 1; $i <= count($objects); $i++) {
         $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
      }
      $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      echo $pdf;
      exit;
   }
}
