<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;
use Html;

class CostCenterLegacy extends CommonDBTM
{
   public static $rightname = Config::RIGHT_COSTCENTERS;

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_costcenters_legacy';
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Centro de custo (Imóveis)', 'Centros de custo (Imóveis)', $nb, 'maintenancecosts');
   }

   public static function getIcon()
   {
      return 'ti ti-building-estate';
   }

   public static function getSearchURL($full = true)
   {
      return Config::pluginUrl('/front/costcenterlegacy.php', $full);
   }

   public static function getFormURL($full = true)
   {
      return Config::pluginUrl('/front/costcenterlegacy.php', $full);
   }

   public static function getNameField()
   {
      return 'name';
   }

   public function getSearchOptions()
   {
      $tab = [];
      $tab[] = ['id' => 'common', 'name' => self::getTypeName(1)];
      $tab[1] = ['id' => 1, 'table' => self::getTable(), 'field' => 'code',        'name' => __('Código', 'maintenancecosts'),                     'datatype' => 'itemlink'];
      $tab[2] = ['id' => 2, 'table' => self::getTable(), 'field' => 'campus',      'name' => __('Campus', 'maintenancecosts'),                     'datatype' => 'string'];
      $tab[3] = ['id' => 3, 'table' => self::getTable(), 'field' => 'department',  'name' => __('Departamento/Disc./Setor', 'maintenancecosts'),    'datatype' => 'string'];
      $tab[4] = ['id' => 4, 'table' => self::getTable(), 'field' => 'address',     'name' => __('Endereço', 'maintenancecosts'),                   'datatype' => 'text'];
      $tab[5] = ['id' => 5, 'table' => self::getTable(), 'field' => 'floor',       'name' => __('Piso', 'maintenancecosts'),                       'datatype' => 'string'];
      $tab[6] = ['id' => 6, 'table' => self::getTable(), 'field' => 'usage_type',  'name' => __('Utilização', 'maintenancecosts'),                 'datatype' => 'string'];
      $tab[80] = ['id' => 80, 'table' => 'glpi_entities', 'field' => 'completename', 'linkfield' => 'entities_id', 'name' => \Entity::getTypeName(1), 'datatype' => 'dropdown'];
      return $tab;
   }
}
