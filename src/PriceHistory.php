<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;

class PriceHistory extends CommonDBTM
{
   public static $rightname = Config::RIGHT_PRICES;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_pricehistories';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Histórico de preço', 'Históricos de preços', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-clock-dollar';
   }

   public static function record(array $input): bool
   {
      $history = new self();
      $materials_id = (int) ($input['plugin_maintenancecosts_materials_id'] ?? 0);
      $competence = Config::normalizeCompetence((string) ($input['competence'] ?? ''));

      if ($materials_id <= 0 || $competence === '') {
         return false;
      }

      return (bool) $history->add([
         'plugin_maintenancecosts_materials_id'     => $materials_id,
         'plugin_maintenancecosts_prices_id'        => (int) ($input['plugin_maintenancecosts_prices_id'] ?? 0),
         'plugin_maintenancecosts_importbatches_id' => (int) ($input['plugin_maintenancecosts_importbatches_id'] ?? 0),
         'competence'                               => $competence,
         'price_type'                               => Config::normalizePriceType((string) ($input['price_type'] ?? 'sinapi')),
         'old_unit_price'                           => Config::parseDecimal($input['old_unit_price'] ?? 0),
         'new_unit_price'                           => Config::parseDecimal($input['new_unit_price'] ?? 0),
         'source'                                   => trim((string) ($input['source'] ?? '')),
         'users_id'                                 => (int) ($input['users_id'] ?? ($_SESSION['glpiID'] ?? 0)),
         'justification'                            => trim((string) ($input['justification'] ?? '')),
         'date_creation'                            => date('Y-m-d H:i:s'),
      ]);
   }
}
