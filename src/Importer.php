<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Importer
{
   private const MAX_CODE_LENGTH = 64;
   private const MAX_UNIT_LENGTH = 32;

   public static function importCostCentersFile(string $path, string $filename, bool $dryRun = true, string $delimiter = 'auto'): array
   {
      $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
      if ($extension === 'xlsx') {
         $csv = self::xlsxToTemporaryCsv($path);
         if ($csv === null) {
            return self::emptyCostCenterSummary($filename, $dryRun, [__('Não foi possível ler o arquivo XLSX.', 'maintenancecosts')]);
         }

         $summary = self::importCostCentersCsv($csv, $filename, $dryRun, ';');
         @unlink($csv);
         return $summary;
      }

      return self::importCostCentersCsv($path, $filename, $dryRun, $delimiter);
   }

   public static function importCostCentersCsv(string $path, string $filename, bool $dryRun = true, string $delimiter = 'auto'): array
   {
      global $DB;

      if ($delimiter === 'auto') {
         $delimiter = self::detectDelimiter($path);
      }

      $summary = self::emptyCostCenterSummary($filename, $dryRun);
      if (!is_readable($path)) {
         $summary['errors'][] = __('Arquivo indisponível para leitura.', 'maintenancecosts');
         return $summary;
      }

      // A gravacao so comeca depois que o arquivo inteiro passa na validacao.
      if (!$dryRun) {
         $preview = self::importCostCentersCsv($path, $filename, true, $delimiter);
         if (!self::previewIsClean($preview)) {
            return self::rejectedSummary($preview);
         }
      }

      $handle = fopen($path, 'rb');
      if (!$handle) {
         $summary['errors'][] = __('Falha ao abrir arquivo.', 'maintenancecosts');
         return $summary;
      }

      $header = fgetcsv($handle, 0, $delimiter);
      if (!$header) {
         fclose($handle);
         $summary['errors'][] = __('Arquivo vazio.', 'maintenancecosts');
         return $summary;
      }

      $map = self::buildCostCenterHeaderMap($header);
      foreach (['code'] as $required) {
         if (!isset($map[$required])) {
            fclose($handle);
            $summary['errors'][] = sprintf(__('Coluna obrigatória ausente: %s', 'maintenancecosts'), $required);
            return $summary;
         }
      }

      if (!$dryRun) {
         @set_time_limit(0);
         $DB->beginTransaction();
      }

      try {
         while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $summary['total_rows']++;
            $data = self::costCenterRowToData($row, $map);
            if (self::isEmptyCostCenterRow($data)) {
               $summary['total_rows']--;
               continue;
            }
            $error = self::validateCostCenterRow($data);
            if ($error !== '') {
               $summary['invalid_rows']++;
               $summary['errors'][] = sprintf('Linha %d: %s', $summary['total_rows'] + 1, $error);
               continue;
            }

            $summary['valid_rows']++;
            $existing = self::findCostCenterByCode($data['code']);
            $summary[$existing ? 'updated_costcenters' : 'new_costcenters']++;

            if (!$dryRun && !self::saveCostCenterRow($data, $existing)) {
               throw new \RuntimeException(sprintf(
                  __('Linha %d: falha ao gravar o registro.', 'maintenancecosts'),
                  $summary['total_rows'] + 1
               ));
            }
         }

         if (!$dryRun) {
            $DB->commit();
            AuditLog::record(CostCenter::class, 0, 'costcenter_import', [], $summary);
         }
      } catch (\Throwable $e) {
         if (!$dryRun && $DB->inTransaction()) {
            $DB->rollBack();
         }
         fclose($handle);
         return self::failedSummary($summary, $e->getMessage());
      }

      fclose($handle);

      return $summary;
   }

   public static function importCostCentersLegacyFile(string $path, string $filename, bool $dryRun = true, string $delimiter = 'auto'): array
   {
      $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
      if ($extension === 'xlsx') {
         $csv = self::xlsxToTemporaryCsv($path);
         if ($csv === null) {
            return self::emptyCostCenterSummary($filename, $dryRun, [__('Não foi possível ler o arquivo XLSX.', 'maintenancecosts')]);
         }

         $summary = self::importCostCentersLegacyCsv($csv, $filename, $dryRun, ';');
         @unlink($csv);
         return $summary;
      }

      return self::importCostCentersLegacyCsv($path, $filename, $dryRun, $delimiter);
   }

   public static function importCostCentersLegacyCsv(string $path, string $filename, bool $dryRun = true, string $delimiter = 'auto'): array
   {
      global $DB;

      if ($delimiter === 'auto') {
         $delimiter = self::detectDelimiter($path);
      }

      $summary = self::emptyCostCenterSummary($filename, $dryRun);
      if (!is_readable($path)) {
         $summary['errors'][] = __('Arquivo indisponível para leitura.', 'maintenancecosts');
         return $summary;
      }

      // A gravacao so comeca depois que o arquivo inteiro passa na validacao.
      if (!$dryRun) {
         $preview = self::importCostCentersLegacyCsv($path, $filename, true, $delimiter);
         if (!self::previewIsClean($preview)) {
            return self::rejectedSummary($preview);
         }
      }

      $handle = fopen($path, 'rb');
      if (!$handle) {
         $summary['errors'][] = __('Falha ao abrir arquivo.', 'maintenancecosts');
         return $summary;
      }

      $header = fgetcsv($handle, 0, $delimiter);
      if (!$header) {
         fclose($handle);
         $summary['errors'][] = __('Arquivo vazio.', 'maintenancecosts');
         return $summary;
      }

      $map = self::buildCostCenterLegacyHeaderMap($header);
      foreach (['code'] as $required) {
         if (!isset($map[$required])) {
            fclose($handle);
            $summary['errors'][] = sprintf(__('Coluna obrigatória ausente: %s', 'maintenancecosts'), $required);
            return $summary;
         }
      }

      if (!$dryRun) {
         @set_time_limit(0);
         $DB->beginTransaction();
      }

      try {
         while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $summary['total_rows']++;
            $data = self::costCenterLegacyRowToData($row, $map);
            if (self::isEmptyCostCenterRow($data)) {
               $summary['total_rows']--;
               continue;
            }
            if (trim((string) ($data['code'] ?? '')) === '') {
               $summary['total_rows']--;
               continue;
            }
            $error = self::validateCostCenterRow($data);
            if ($error !== '') {
               $summary['invalid_rows']++;
               $summary['errors'][] = sprintf('Linha %d: %s', $summary['total_rows'] + 1, $error);
               continue;
            }

            $summary['valid_rows']++;
            $existing = self::findCostCenterLegacyByCode($data['code']);
            $summary[$existing ? 'updated_costcenters' : 'new_costcenters']++;

            if (!$dryRun && !self::saveCostCenterLegacyRow($data, $existing)) {
               throw new \RuntimeException(sprintf(
                  __('Linha %d: falha ao gravar o registro.', 'maintenancecosts'),
                  $summary['total_rows'] + 1
               ));
            }
         }

         if (!$dryRun) {
            $DB->commit();
            AuditLog::record(CostCenterLegacy::class, 0, 'costcenter_legacy_import', [], $summary);
         }
      } catch (\Throwable $e) {
         if (!$dryRun && $DB->inTransaction()) {
            $DB->rollBack();
         }
         fclose($handle);
         return self::failedSummary($summary, $e->getMessage());
      }

      fclose($handle);

      return $summary;
   }

   public static function importFile(string $path, string $filename, string $competence, bool $dryRun = true, string $delimiter = 'auto', string $priceType = 'sinapi'): array
   {
      $priceType = Config::normalizePriceType($priceType);
      $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
      if ($extension === 'xlsx') {
         $csv = self::xlsxToTemporaryCsv($path);
         if ($csv === null) {
            return self::emptySummary($filename, $competence, $dryRun, [__('Não foi possível ler o arquivo XLSX.', 'maintenancecosts')], $priceType);
         }

         $summary = self::importCsv($csv, $filename, $competence, $dryRun, ';', $priceType);
         @unlink($csv);
         return $summary;
      }

      return self::importCsv($path, $filename, $competence, $dryRun, $delimiter, $priceType);
   }

   public static function importCsv(string $path, string $filename, string $competence, bool $dryRun = true, string $delimiter = 'auto', string $priceType = 'sinapi'): array
   {
      global $DB;

      $priceType = Config::normalizePriceType($priceType);
      if ($delimiter === 'auto') {
         $delimiter = self::detectDelimiter($path);
      }

      $summary = self::emptySummary($filename, $competence, $dryRun, [], $priceType);

      if (!is_readable($path)) {
         $summary['errors'][] = __('Arquivo indisponivel para leitura.', 'maintenancecosts');
         return $summary;
      }

      // A gravacao so comeca depois que o arquivo inteiro passa na validacao,
      // para a importacao nunca ficar pela metade por causa de uma linha ruim.
      if (!$dryRun) {
         $preview = self::importCsv($path, $filename, $competence, true, $delimiter, $priceType);
         if (!self::previewIsClean($preview)) {
            $rejected = self::rejectedSummary($preview);
            self::recordFailedBatch($filename, $competence, $priceType, $rejected);
            return $rejected;
         }
      }

      $handle = fopen($path, 'rb');
      if (!$handle) {
         $summary['errors'][] = __('Falha ao abrir arquivo.', 'maintenancecosts');
         return $summary;
      }

      $header = fgetcsv($handle, 0, $delimiter);
      if (!$header) {
         fclose($handle);
         $summary['errors'][] = __('Arquivo vazio.', 'maintenancecosts');
         return $summary;
      }

      $map = self::buildHeaderMap($header);
      if ($priceType === 'cotacao_mercado' && !self::hasRequiredPriceColumns($map)) {
         $map = self::buildDefaultQuoteMap();
      }
      foreach (['code', 'name', 'unit', 'unit_price'] as $required) {
         if (!isset($map[$required])) {
            fclose($handle);
            $summary['errors'][] = sprintf(__('Coluna obrigatoria ausente: %s', 'maintenancecosts'), $required);
            return $summary;
         }
      }

      if (!$dryRun) {
         @set_time_limit(0);
         $DB->beginTransaction();
      }

      $importBatchId = 0;

      try {
         if (!$dryRun) {
            $batch = new ImportBatch();
            $importBatchId = (int) $batch->add([
               'filename'      => $filename,
               'competence'    => $summary['competence'],
               'price_type'    => $priceType,
               'total_rows'    => 0,
               'imported_rows' => 0,
               'error_rows'    => 0,
               'users_id'      => (int) ($_SESSION['glpiID'] ?? 0),
               'status'        => 'processing',
               'log'           => '',
            ]);
            if ($importBatchId <= 0) {
               throw new \RuntimeException(__('Falha ao registrar o lote de importacao.', 'maintenancecosts'));
            }
         }

         while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $summary['total_rows']++;
            $data = self::rowToData($row, $map, $summary['competence']);
            // Linha em branco e rodape nao sao erro: sao ignorados, como ja
            // acontece na importacao de centros de custo. Sem isso, uma linha
            // vazia recusaria o arquivo inteiro.
            if (self::isEmptyRow($data)) {
               $summary['total_rows']--;
               continue;
            }
            if (self::isRepeatedQuoteHeader($data)) {
               continue;
            }
            $error = self::validateRow($data);
            if ($error !== '') {
               $summary['invalid_rows']++;
               $summary['errors'][] = sprintf('Linha %d: %s', $summary['total_rows'] + 1, $error);
               continue;
            }

            $summary['valid_rows']++;
            $state = self::classifyRow($data, $priceType);
            $summary[$state['material_status'] === 'new' ? 'new_materials' : 'updated_materials']++;
            $summary[$state['price_status'] === 'repeated' ? 'repeated_prices' : 'new_prices']++;

            if (!$dryRun && !self::saveRow($data, $importBatchId, $priceType)) {
               throw new \RuntimeException(sprintf(
                  __('Linha %d: falha ao gravar o registro.', 'maintenancecosts'),
                  $summary['total_rows'] + 1
               ));
            }
         }

         if (!$dryRun) {
            $batch = new ImportBatch();
            $batch->update([
               'id'            => $importBatchId,
               'total_rows'    => $summary['total_rows'],
               'imported_rows' => $summary['valid_rows'],
               'error_rows'    => $summary['invalid_rows'],
               'status'        => 'completed',
               'log'           => implode(PHP_EOL, $summary['errors']),
            ]);
            $DB->commit();
            AuditLog::record(ImportBatch::class, $importBatchId, $priceType === 'sinapi' ? 'sinapi_import' : 'quote_import', [], $summary);
         }
      } catch (\Throwable $e) {
         if (!$dryRun && $DB->inTransaction()) {
            $DB->rollBack();
         }
         fclose($handle);
         $failed = self::failedSummary($summary, $e->getMessage());
         self::recordFailedBatch($filename, $competence, $priceType, $failed);
         return $failed;
      }

      fclose($handle);

      return $summary;
   }

   private static function previewIsClean(array $preview): bool
   {
      return (int) ($preview['invalid_rows'] ?? 0) === 0
         && count($preview['errors'] ?? []) === 0;
   }

   private static function rejectedSummary(array $summary): array
   {
      $summary['dry_run'] = false;
      $summary['aborted'] = true;
      array_unshift(
         $summary['errors'],
         __('Importação cancelada: nenhum registro foi gravado. Corrija as linhas indicadas e envie o arquivo novamente.', 'maintenancecosts')
      );

      return $summary;
   }

   private static function failedSummary(array $summary, string $message): array
   {
      $summary['dry_run'] = false;
      $summary['aborted'] = true;
      array_unshift(
         $summary['errors'],
         sprintf(
            __('Importação revertida: nenhum registro foi gravado. Tente novamente. Detalhe: %s', 'maintenancecosts'),
            $message
         )
      );

      return $summary;
   }

   private static function recordFailedBatch(string $filename, string $competence, string $priceType, array $summary): void
   {
      $batch = new ImportBatch();
      $batch->add([
         'filename'      => $filename,
         'competence'    => Config::normalizeCompetence($competence),
         'price_type'    => Config::normalizePriceType($priceType),
         'total_rows'    => (int) ($summary['total_rows'] ?? 0),
         'imported_rows' => 0,
         'error_rows'    => (int) ($summary['invalid_rows'] ?? 0),
         'users_id'      => (int) ($_SESSION['glpiID'] ?? 0),
         'status'        => 'failed',
         'log'           => implode(PHP_EOL, array_slice($summary['errors'] ?? [], 0, 200)),
      ]);
   }

   private static function emptySummary(string $filename, string $competence, bool $dryRun, array $errors = [], string $priceType = 'sinapi'): array
   {
      return [
         'dry_run'           => $dryRun,
         'aborted'           => false,
         'filename'          => $filename,
         'competence'        => Config::normalizeCompetence($competence),
         'price_type'        => Config::normalizePriceType($priceType),
         'total_rows'        => 0,
         'valid_rows'        => 0,
         'invalid_rows'      => 0,
         'new_materials'     => 0,
         'updated_materials' => 0,
         'new_prices'        => 0,
         'repeated_prices'   => 0,
         'errors'            => $errors,
      ];
   }

   private static function emptyCostCenterSummary(string $filename, bool $dryRun, array $errors = []): array
   {
      return [
         'dry_run'             => $dryRun,
         'aborted'             => false,
         'filename'            => $filename,
         'total_rows'          => 0,
         'valid_rows'          => 0,
         'invalid_rows'        => 0,
         'new_costcenters'     => 0,
         'updated_costcenters' => 0,
         'errors'              => $errors,
      ];
   }

   private static function buildCostCenterHeaderMap(array $header): array
   {
      $aliases = [
         'code'          => ['codigo', 'código', 'codigo centro de custo', 'código centro de custo', 'centro de custo', 'code'],
         'name'          => ['nome', 'local', 'nome do local', 'name'],
         'campus'        => ['unidade gestora', 'unidade', 'campus'],
         'academic_unit' => ['unidade academica', 'unidade acadêmica'],
         'department'    => ['departamento'],
         'division'      => ['divisao', 'divisão'],
         'section'       => ['secao', 'seção'],
         'siorg_code'    => ['codigo siorg', 'código siorg'],
         'siorg_acronym' => ['sigla siorg'],
         'address'       => ['endereco', 'endereço', 'address'],
         'responsible'   => ['responsavel', 'responsável'],
         'floor'         => ['piso', 'andar', 'floor'],
         'usage_type'    => ['utilizacao', 'utilização', 'uso', 'usage', 'utilization'],
      ];

      $map = [];
      foreach ($header as $index => $column) {
         $normalized = self::normalizeHeader((string) $column);
         foreach ($aliases as $field => $names) {
            if (in_array($normalized, array_map([self::class, 'normalizeHeader'], $names), true)) {
               $map[$field] = $index;
            }
         }
      }

      return $map;
   }

   private static function buildCostCenterLegacyHeaderMap(array $header): array
   {
      $aliases = [
         'code'           => ['centro de custo', 'codigo', 'código', 'code'],
         'campus'         => ['campus'],
         'department'     => ['departamento /disc./setor', 'departamento/disc./setor', 'departamento / disc. / setor', 'departamento'],
         'address_type'   => ['tipo'],
         'address_street' => ['logradouro'],
         'address_number' => ['nº', 'n°', 'nÂº', 'número', 'numero', 'nÂ°', 'no'],
         'floor'          => ['piso', 'andar', 'floor'],
         'usage_type'     => ['utilizacao', 'utilização', 'uso', 'usage', 'utilization'],
      ];

      $map = [];
      foreach ($header as $index => $column) {
         $normalized = self::normalizeHeader((string) $column);
         foreach ($aliases as $field => $names) {
            if (in_array($normalized, array_map([self::class, 'normalizeHeader'], $names), true)) {
               $map[$field] = $index;
               break;
            }
         }
      }

      return $map;
   }

   private static function costCenterRowToData(array $row, array $map): array
   {
      $get = static function(string $field) use ($row, $map): string {
         return isset($map[$field], $row[$map[$field]]) ? trim((string) $row[$map[$field]]) : '';
      };

      $data = [
         'code'          => self::cleanImportedText($get('code'), 64),
         'name'          => self::cleanImportedText($get('name'), 255),
         'campus'        => self::cleanImportedText($get('campus'), 255),
         'academic_unit' => self::cleanImportedText($get('academic_unit'), 255),
         'department'    => self::cleanImportedText($get('department'), 255),
         'division'      => self::cleanImportedText($get('division'), 255),
         'section'       => self::cleanImportedText($get('section'), 255),
         'siorg_code'    => self::cleanImportedText($get('siorg_code'), 64),
         'siorg_acronym' => self::cleanImportedText($get('siorg_acronym'), 64),
         'address'       => self::cleanImportedText($get('address')),
         'responsible'   => self::cleanImportedText($get('responsible'), 255),
         'floor'         => self::cleanImportedText($get('floor'), 64),
         'usage_type'    => self::cleanImportedText($get('usage_type'), 255),
      ];
      $data['locations_id'] = $data['campus'] !== '' ? CostCenter::getRootLocationIdByLabel($data['campus']) : 0;

      return $data;
   }

   private static function costCenterLegacyRowToData(array $row, array $map): array
   {
      $get = static function(string $field) use ($row, $map): string {
         return isset($map[$field], $row[$map[$field]]) ? trim((string) $row[$map[$field]]) : '';
      };

      $type = self::cleanImportedText($get('address_type'), 64);
      $street = self::cleanImportedText($get('address_street'), 255);
      $number = self::cleanImportedText($get('address_number'), 64);

      $address = trim(implode(' ', array_filter([$type, $street], static function($value) {
         return trim((string) $value) !== '';
      })));
      if ($number !== '') {
         $address .= ($address !== '' ? ', ' : '') . $number;
      }

      $data = [
         'code'          => self::cleanImportedText($get('code'), 64),
         'name'          => '',
         'campus'        => self::cleanImportedText($get('campus'), 255),
         'academic_unit' => '',
         'department'    => self::cleanImportedText($get('department'), 255),
         'division'      => '',
         'section'       => '',
         'siorg_code'    => '',
         'siorg_acronym' => '',
         'address'       => self::cleanImportedText($address),
         'responsible'   => '',
         'floor'         => self::cleanImportedText($get('floor'), 64),
         'usage_type'    => self::cleanImportedText($get('usage_type'), 255),
      ];
      $data['locations_id'] = $data['campus'] !== '' ? CostCenter::getRootLocationIdByLabel($data['campus']) : 0;

      return $data;
   }

   private static function validateCostCenterRow(array $data): string
   {
      if ($data['code'] === '') {
         return __('código vazio', 'maintenancecosts');
      }
      if (mb_strlen((string) $data['code']) > self::MAX_CODE_LENGTH) {
         return sprintf(__('código excede %d caracteres', 'maintenancecosts'), self::MAX_CODE_LENGTH);
      }
      return '';
   }

   private static function isEmptyCostCenterRow(array $data): bool
   {
      foreach ($data as $key => $value) {
         if ($key === 'locations_id') {
            continue;
         }
         if (trim((string) $value) !== '') {
            return false;
         }
      }
      return true;
   }
   private static function saveCostCenterRow(array $data, ?array $existing): bool
   {
      $costCenter = new CostCenter();
      $input = $data + [
         'entities_id'  => (int) ($_SESSION['glpiactive_entity'] ?? 0),
         'is_recursive' => 1,
         'is_active'    => 1,
      ];

      if ($existing) {
         return (bool) $costCenter->update(['id' => (int) $existing['id']] + $input);
      }

      return (int) $costCenter->add($input) > 0;
   }

   private static function saveCostCenterLegacyRow(array $data, ?array $existing): bool
   {
      $costCenter = new CostCenterLegacy();
      $input = $data + [
         'entities_id'  => (int) ($_SESSION['glpiactive_entity'] ?? 0),
         'is_recursive' => 1,
         'is_active'    => 1,
      ];

      if ($existing) {
         return (bool) $costCenter->update(['id' => (int) $existing['id']] + $input);
      }

      return (int) $costCenter->add($input) > 0;
   }

   private static function buildHeaderMap(array $header): array
   {
      $aliases = [
         'code'       => ['codigo', 'codigo sinapi', 'code', 'sinapi'],
         'name'       => ['descricao', 'description', 'material', 'name', 'nome'],
         'unit'       => ['unidade', 'un', 'unit', 'unidade de medida'],
         'unit_price' => ['valor', 'valor unitario', 'preco', 'price', 'unit_price'],
         'quote_quantity' => ['qdt', 'qtd', 'quantidade', 'quantity'],
         'quote_price_1' => ['cot1', 'cot 1', 'cotacao 1', 'cotacao1'],
         'quote_price_2' => ['cot 2', 'cot. 2', 'cotacao 2', 'cotacao2'],
         'quote_price_3' => ['cot 3', 'cot. 3', 'cotacao 3', 'cotacao3'],
         'competence' => ['competencia', 'mes', 'month'],
         'category'   => ['categoria', 'category'],
      ];

      $map = [];
      foreach ($header as $index => $column) {
         $normalized = self::normalizeHeader((string) $column);
         foreach ($aliases as $field => $names) {
            if (in_array($normalized, array_map([self::class, 'normalizeHeader'], $names), true)) {
               $map[$field] = $index;
            }
         }
      }

      return $map;
   }

   private static function hasRequiredPriceColumns(array $map): bool
   {
      foreach (['code', 'name', 'unit', 'unit_price'] as $required) {
         if (!isset($map[$required])) {
            return false;
         }
      }
      return true;
   }

   private static function buildDefaultQuoteMap(): array
   {
      return [
         'code'           => 0,
         'name'           => 1,
         'unit'           => 2,
         'quote_quantity' => 3,
         'unit_price'     => 4,
         'quote_price_1'  => 5,
         'quote_price_2'  => 6,
         'quote_price_3'  => 7,
      ];
   }

   private static function normalizeHeader(string $value): string
   {
      $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
      $value = str_replace(
         ['á', 'à', 'ã', 'â', 'ä', 'é', 'ê', 'ë', 'í', 'ó', 'ô', 'õ', 'ö', 'ú', 'ü', 'ç'],
         ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'c'],
         $value
      );
      $value = strtr($value, [
         'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
         'é' => 'e', 'ê' => 'e', 'ë' => 'e',
         'í' => 'i',
         'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
         'ú' => 'u', 'ü' => 'u',
         'ç' => 'c',
      ]);
      return preg_replace('/\s+/', ' ', $value);
   }

   private static function rowToData(array $row, array $map, string $defaultCompetence): array
   {
      $get = static function(string $field) use ($row, $map): string {
         return isset($map[$field], $row[$map[$field]]) ? trim((string) $row[$map[$field]]) : '';
      };

      $description = self::cleanImportedText($get('name'));

      return [
         // Codigo e unidade nao sao truncados aqui: o codigo identifica o
         // material e cortar silenciosamente juntaria materiais distintos no
         // mesmo registro. O tamanho e recusado em validateRow().
         'code'        => self::cleanImportedText($get('code')),
         'name'        => self::cleanImportedText($description, 255),
         'description' => $description,
         'unit'        => self::cleanImportedText($get('unit')),
         'unit_price' => self::parseDecimal($get('unit_price')),
         'quote_quantity' => self::parseDecimal($get('quote_quantity')),
         'quote_price_1'  => self::parseDecimal($get('quote_price_1')),
         'quote_price_2'  => self::parseDecimal($get('quote_price_2')),
         'quote_price_3'  => self::parseDecimal($get('quote_price_3')),
         'competence' => Config::normalizeCompetence($get('competence') !== '' ? $get('competence') : $defaultCompetence),
         'category'   => self::cleanImportedText($get('category'), 255),
      ];
   }

   private static function isEmptyRow(array $data): bool
   {
      foreach (['code', 'name', 'description', 'unit', 'category'] as $field) {
         if (trim((string) ($data[$field] ?? '')) !== '') {
            return false;
         }
      }

      foreach (['unit_price', 'quote_quantity', 'quote_price_1', 'quote_price_2', 'quote_price_3'] as $field) {
         if (abs((float) ($data[$field] ?? 0)) > 0.000001) {
            return false;
         }
      }

      return true;
   }

   private static function isRepeatedQuoteHeader(array $data): bool
   {
      $code = strtoupper(trim((string) ($data['code'] ?? '')));
      $name = strtoupper(trim((string) ($data['name'] ?? '')));
      return (strpos($code, 'DIGO') !== false && strpos($name, 'DESCRI') !== false)
         || self::normalizeHeader((string) ($data['code'] ?? '')) === 'codigo'
         || self::normalizeHeader((string) ($data['name'] ?? '')) === 'descricao produto';
   }

   private static function cleanImportedText(string $value, int $maxLength = 0): string
   {
      $value = trim(str_replace(["\r", "\n", "\t", "'"], ' ', $value));
      $value = (string) preg_replace('/\s+/', ' ', $value);

      // As colunas contam caracteres, nao bytes. Cortar por byte parte um
      // caractere acentuado ao meio e gera UTF-8 invalido, recusado pelo MySQL.
      if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
         $value = mb_substr($value, 0, $maxLength);
      }

      return trim($value);
   }

   private static function parseDecimal(string $value): float
   {
      $value = trim(str_replace(['R$', ' '], '', $value));
      if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
         $value = str_replace('.', '', $value);
      } elseif (substr_count($value, '.') > 1 && preg_match('/^(.*)\.(\d{1,6})$/', $value, $matches)) {
         $value = str_replace('.', '', $matches[1]) . '.' . $matches[2];
      }
      return (float) str_replace(',', '.', $value);
   }

   private static function validateRow(array $data): string
   {
      if ($data['code'] === '') {
         return __('codigo vazio', 'maintenancecosts');
      }
      // O banco corta valores maiores que a coluna sem avisar. Como o codigo e a
      // chave de identidade do material, truncar aqui juntaria materiais
      // diferentes no mesmo registro.
      if (mb_strlen($data['code']) > self::MAX_CODE_LENGTH) {
         return sprintf(__('código excede %d caracteres', 'maintenancecosts'), self::MAX_CODE_LENGTH);
      }
      if ($data['name'] === '') {
         return __('descricao vazia', 'maintenancecosts');
      }
      if ($data['unit'] === '') {
         return __('unidade vazia', 'maintenancecosts');
      }
      if (mb_strlen($data['unit']) > self::MAX_UNIT_LENGTH) {
         return sprintf(__('unidade excede %d caracteres', 'maintenancecosts'), self::MAX_UNIT_LENGTH);
      }
      if ($data['competence'] === '' || !preg_match('/^\d{4}-\d{2}$/', $data['competence'])) {
         return __('competencia invalida', 'maintenancecosts');
      }
      if ((float) $data['unit_price'] < 0) {
         return __('valor unitário inválido', 'maintenancecosts');
      }
      return '';
   }

   private static function classifyRow(array $data, string $priceType): array
   {
      $material = self::findMaterialByCode($data['code']);
      $price = $material ? Price::getForMaterialCompetenceAndType((int) $material['id'], $data['competence'], Config::normalizePriceType($priceType)) : null;

      return [
         'material_status' => $material ? 'existing' : 'new',
         'price_status'    => ($price && !self::priceRowChanged($price, $data)) ? 'repeated' : 'new',
      ];
   }

   private static function priceRowChanged(array $price, array $data): bool
   {
      foreach (['unit_price', 'quote_quantity', 'quote_price_1', 'quote_price_2', 'quote_price_3'] as $field) {
         if (abs((float) ($price[$field] ?? 0) - (float) ($data[$field] ?? 0)) > 0.000001) {
            return true;
         }
      }
      return false;
   }

   private static function saveRow(array $data, int $importBatchId, string $priceType): bool
   {
      $priceType = Config::normalizePriceType($priceType);
      $materialRow = self::findMaterialByCode($data['code']);
      $material = new Material();

      if ($materialRow) {
         $materialId = (int) $materialRow['id'];
         if (!$material->update([
            'id'          => $materialId,
            'name'        => $data['name'],
            'description' => $data['description'],
            'unit'        => $data['unit'],
            'category'    => $data['category'],
            'is_active'   => 1,
         ])) {
            return false;
         }
      } else {
         $materialId = (int) $material->add([
            'entities_id'  => (int) ($_SESSION['glpiactive_entity'] ?? 0),
            'is_recursive' => 1,
            'code'         => $data['code'],
            'name'         => $data['name'],
            'description'  => $data['description'],
            'unit'         => $data['unit'],
            'category'     => $data['category'],
            'is_active'    => 1,
         ]);
      }

      if ($materialId <= 0) {
         return false;
      }

      $price = Price::getForMaterialCompetenceAndType($materialId, $data['competence'], $priceType);
      if ($price && !self::priceRowChanged($price, $data)) {
         return true;
      }

      $priceObj = new Price();
      $priceInput = [
         'plugin_maintenancecosts_materials_id'     => $materialId,
         'competence'                               => $data['competence'],
         'unit_price'                               => $data['unit_price'],
         'quote_quantity'                           => $data['quote_quantity'] ?? 0,
         'quote_price_1'                            => $data['quote_price_1'] ?? 0,
         'quote_price_2'                            => $data['quote_price_2'] ?? 0,
         'quote_price_3'                            => $data['quote_price_3'] ?? 0,
         'price_type'                               => $priceType,
         'source'                                   => $priceType === 'sinapi' ? 'CSV/XLSX SINAPI' : 'CSV/XLSX Cotação/Mercado',
         'plugin_maintenancecosts_importbatches_id' => $importBatchId,
         'users_id'                                 => (int) ($_SESSION['glpiID'] ?? 0),
         'comment'                                  => $priceType === 'sinapi'
            ? __('Atualização por importação SINAPI.', 'maintenancecosts')
            : __('Atualização por importação de cotação/mercado.', 'maintenancecosts'),
      ];

      if ($price) {
         return (bool) $priceObj->update(['id' => (int) $price['id']] + $priceInput);
      }

      return (int) $priceObj->add($priceInput) > 0;
   }

   private static function findMaterialByCode(string $code): ?array
   {
      global $DB;

      $row = $DB->request([
         'FROM'  => Material::getTable(),
         'WHERE' => ['code' => $code],
         'LIMIT' => 1,
      ])->current();

      return $row ?: null;
   }

   private static function findCostCenterByCode(string $code): ?array
   {
      global $DB;

      $row = $DB->request([
         'FROM'  => CostCenter::getTable(),
         'WHERE' => ['code' => $code],
         'LIMIT' => 1,
      ])->current();

      return $row ?: null;
   }

   private static function findCostCenterLegacyByCode(string $code): ?array
   {
      global $DB;

      $row = $DB->request([
         'FROM'  => CostCenterLegacy::getTable(),
         'WHERE' => ['code' => $code],
         'LIMIT' => 1,
      ])->current();

      return $row ?: null;
   }

   private static function detectDelimiter(string $path): string
   {
      $line = '';
      $handle = fopen($path, 'rb');
      if ($handle) {
         $line = (string) fgets($handle);
         fclose($handle);
      }

      return substr_count($line, ',') > substr_count($line, ';') ? ',' : ';';
   }

   private static function xlsxToTemporaryCsv(string $path): ?string
   {
      if (!class_exists('ZipArchive')) {
         return null;
      }

      $zip = new \ZipArchive();
      if ($zip->open($path) !== true) {
         return null;
      }

      $shared = self::readSharedStrings($zip);
      $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
      $zip->close();

      if ($sheetXml === false) {
         return null;
      }

      $xml = simplexml_load_string($sheetXml);
      if (!$xml || !isset($xml->sheetData->row)) {
         return null;
      }

      $tmp = tempnam(sys_get_temp_dir(), 'mc_xlsx_');
      if ($tmp === false) {
         return null;
      }

      $handle = fopen($tmp, 'wb');
      if (!$handle) {
         return null;
      }

      foreach ($xml->sheetData->row as $row) {
         $values = [];
         foreach ($row->c as $cell) {
            $attrs = $cell->attributes();
            $ref = (string) ($attrs['r'] ?? '');
            $index = self::columnIndexFromReference($ref);
            $type = (string) ($attrs['t'] ?? '');
            $value = isset($cell->v) ? (string) $cell->v : '';
            if ($type === 's') {
               $value = $shared[(int) $value] ?? '';
            } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
               $value = (string) $cell->is->t;
            }
            $values[$index] = $value;
         }
         if (!count($values)) {
            continue;
         }
         ksort($values);
         $line = [];
         $max = max(array_keys($values));
         for ($i = 0; $i <= $max; $i++) {
            $line[] = $values[$i] ?? '';
         }
         fputcsv($handle, $line, ';');
      }

      fclose($handle);
      return $tmp;
   }

   private static function readSharedStrings(\ZipArchive $zip): array
   {
      $xml = $zip->getFromName('xl/sharedStrings.xml');
      if ($xml === false) {
         return [];
      }

      $sx = simplexml_load_string($xml);
      if (!$sx || !isset($sx->si)) {
         return [];
      }

      $strings = [];
      foreach ($sx->si as $si) {
         if (isset($si->t)) {
            $strings[] = (string) $si->t;
            continue;
         }
         $text = '';
         if (isset($si->r)) {
            foreach ($si->r as $run) {
               $text .= (string) ($run->t ?? '');
            }
         }
         $strings[] = $text;
      }

      return $strings;
   }

   private static function columnIndexFromReference(string $reference): int
   {
      $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
      $index = 0;
      for ($i = 0; $i < strlen($letters); $i++) {
         $index = ($index * 26) + (ord($letters[$i]) - 64);
      }
      return max(0, $index - 1);
   }
}
