<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use CommonGLPI;
use CommonDBTM;
use Dropdown;
use Entity;
use Html;
use Session;

class ConfigEntity extends CommonDBTM
{
   public static function getTypeName($nb = 0)
   {
      return __('Entidade habilitada', 'maintenancecosts');
   }

   public static function getTable($classname = null)
   {
      return 'glpi_plugin_maintenancecosts_configentities';
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item instanceof Entity && $item->getID() >= 0 && Config::canAdminConfig()) {
         return self::createTabEntry(Config::getTypeName(), 0, $item::getType(), Config::getIcon());
      }

      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item instanceof Entity) {
         self::showForEntity($item);
      }

      return true;
   }

   public static function showForEntity(Entity $entity): void
   {
      $entities_id = (int) $entity->getID();
      if (!Config::canAdminConfig() || !Session::haveAccessToEntity($entities_id, true)) {
         Html::displayRightError();
      }

      $rule = Config::getEntityRule($entities_id);
      $inherited_name = '';

      if ((int) ($rule['inherited_id'] ?? 0) > 0) {
         $parent = new Entity();
         if ($parent->getFromDB((int) $rule['inherited_id'])) {
            $inherited_name = (string) ($parent->fields['completename'] ?? $parent->fields['name'] ?? '');
         }
      }

      echo "<div class='spaced'>";
      echo "<form method='post' action='" . Html::clean(Config::pluginUrl('/front/entityconfig.form.php')) . "' data-track-changes='true'>";
      echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
      echo Html::hidden('entities_id', ['value' => $entities_id]);

      echo "<div class='plugin-maintenancecosts-panel'>";
      echo "<div class='plugin-maintenancecosts-panel-header'><i class='ti ti-building-bank'></i> " . __('Disponibilidade do plugin na entidade', 'maintenancecosts') . "</div>";
      echo "<div class='plugin-maintenancecosts-panel-body'>";

      echo "<div class='plugin-maintenancecosts-grid'>";

      echo "<div class='plugin-maintenancecosts-option'>";
      echo "<div class='plugin-maintenancecosts-option-title'><i class='ti ti-toggle-left'></i> " . __('Habilitar nesta entidade', 'maintenancecosts') . "</div>";
      echo "<input type='hidden' name='plugin_maintenancecosts_entity_enabled' value='0'>";
      Dropdown::showYesNo('plugin_maintenancecosts_entity_enabled', (int) ($rule['enabled'] ? 1 : 0));
      echo "<div class='plugin-maintenancecosts-help'>" . __('Quando habilitado, o plugin poderá ser usado nesta entidade. Se nenhuma entidade tiver regra própria, o plugin continua disponível em todas por compatibilidade.', 'maintenancecosts') . "</div>";
      echo "</div>";

      echo "<div class='plugin-maintenancecosts-option'>";
      echo "<div class='plugin-maintenancecosts-option-title'><i class='ti ti-hierarchy-2'></i> " . __('Aplicar às entidades filhas', 'maintenancecosts') . "</div>";
      echo "<input type='hidden' name='plugin_maintenancecosts_entity_recursive' value='0'>";
      Dropdown::showYesNo('plugin_maintenancecosts_entity_recursive', (int) ($rule['recursive'] ? 1 : 0));
      echo "<div class='plugin-maintenancecosts-help'>" . __('Quando habilitado, a disponibilidade desta entidade também valerá para as entidades descendentes.', 'maintenancecosts') . "</div>";
      echo "</div>";

      echo "</div>";

      if (!empty($rule['inherited']) && $inherited_name !== '') {
         echo "<div class='plugin-maintenancecosts-help mt-3'><strong>" . __('Herança ativa:', 'maintenancecosts') . "</strong> "
            . Html::clean(sprintf(__('esta entidade já está coberta por uma regra recursiva definida em %s.', 'maintenancecosts'), $inherited_name))
            . "</div>";
      }

      echo "<div class='mt-3'>";
      echo Html::submit(__('Salvar disponibilidade da entidade', 'maintenancecosts'), [
         'name'  => 'save_entity_rule',
         'class' => 'btn btn-primary',
      ]);
      echo "</div>";

      echo "</div></div>";
      Html::closeForm();
      echo "</div>";
   }
}
