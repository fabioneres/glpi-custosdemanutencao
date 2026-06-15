<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonDBTM;

class Menu extends CommonDBTM
{
   protected static $notable = true;

   public static function getTypeName($nb = 0)
   {
      return Config::getTypeName($nb);
   }

   public static function getIcon()
   {
      return Config::getIcon();
   }

   public static function getMenuName()
   {
      return Config::getTypeName();
   }

   public static function getMenuContent()
   {
      if (!Config::canViewAny()) {
         return false;
      }

      $menu = [
         'title' => self::getMenuName(),
         'page'  => Config::pluginUrl('/front/material.php', false),
         'icon'  => self::getIcon(),
      ];

      if (Config::canViewMaterials()) {
         $menu['options']['materials'] = [
            'title' => Material::getTypeName(2),
            'page'  => Config::pluginUrl('/front/material.php', false),
            'icon'  => Material::getIcon(),
         ];
         $menu['options']['quotationmaterials'] = [
            'title' => __('Materiais Cotação', 'maintenancecosts'),
            'page'  => Config::pluginUrl('/front/quotationmaterial.php', false),
            'icon'  => 'ti ti-packages',
         ];
         $menu['options']['materialorigins'] = [
            'title' => MaterialOrigin::getTypeName(2),
            'page'  => Config::pluginUrl('/front/materialorigin.php', false),
            'icon'  => MaterialOrigin::getIcon(),
         ];
      }

      if (Config::canViewPrices()) {
         $menu['options']['prices'] = [
            'title' => Price::getTypeName(2),
            'page'  => Config::pluginUrl('/front/price.php', false),
            'icon'  => Price::getIcon(),
         ];
         $menu['options']['quotes'] = [
            'title' => __('Cotação/Mercado', 'maintenancecosts'),
            'page'  => Config::pluginUrl('/front/quotationprice.php', false),
            'icon'  => 'ti ti-receipt',
         ];
         $menu['options']['pricehistory'] = [
            'title' => __('Histórico de preços', 'maintenancecosts'),
            'page'  => Config::pluginUrl('/front/pricehistory.php', false),
            'icon'  => 'ti ti-clock-dollar',
         ];
      }

      if (Config::canViewCostCenters()) {
         $menu['options']['costcenters'] = [
            'title' => CostCenter::getTypeName(2),
            'page'  => Config::pluginUrl('/front/costcenter.php', false),
            'icon'  => CostCenter::getIcon(),
         ];
      }

      if (Config::canViewConsumption()) {
         $menu['options']['consumption'] = [
            'title' => TicketMaterial::getTypeName(2),
            'page'  => Config::pluginUrl('/front/ticketmaterial.php', false),
            'icon'  => TicketMaterial::getIcon(),
         ];
      }

      if (Config::canViewReports()) {
         $menu['options']['reports'] = [
            'title' => Report::getTypeName(2),
            'page'  => Config::pluginUrl('/front/report.php', false),
            'icon'  => Report::getIcon(),
         ];
      }

      $menu['options']['about'] = [
         'title' => __('Sobre', 'maintenancecosts'),
         'page'  => Config::pluginUrl('/front/about.php', false),
         'icon'  => 'ti ti-info-circle',
      ];

      if (Config::canAdminConfig()) {
         $menu['links']['config'] = Config::pluginUrl('/front/config.form.php', false);
      }

      return $menu;
   }
}
