<?php

use GlpiPlugin\Maintenancecosts\Config;
use GlpiPlugin\Maintenancecosts\ConfigEntity;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

// GLPI 11 executa o front dentro de uma funcao do roteador: sem global, $CFG_GLPI fica indefinido.
global $CFG_GLPI;

Session::checkLoginUser();
Config::checkRight(Config::RIGHT_CONFIG, UPDATE);

$entities_id = (int) ($_POST['entities_id'] ?? $_GET['entities_id'] ?? -1);
if ($entities_id < 0 || !Session::haveAccessToEntity($entities_id)) {
   Html::displayRightError();
}

if (isset($_POST['save_entity_rule'])) {
   if (countElementsInTable(ConfigEntity::getTable()) === 0 && !Config::canManageAllEntities()) {
      // Sem nenhuma regra, o plugin vale para todas as entidades. A primeira regra
      // passa a valer so para as listadas e desabilita as demais (F12).
      Session::addMessageAfterRedirect(__('A primeira regra de disponibilidade passa a valer só para as entidades listadas e desabilita as demais. Ela precisa ser criada por quem tem acesso a todas as entidades.', 'maintenancecosts'), false, ERROR);
   } elseif (Config::saveEntityRule($entities_id, $_POST)) {
      Session::addMessageAfterRedirect(__('Disponibilidade da entidade salva.', 'maintenancecosts'), false, INFO);
   } else {
      Session::addMessageAfterRedirect(__('Não foi possível salvar a disponibilidade da entidade.', 'maintenancecosts'), false, ERROR);
   }
}

$redirect = $CFG_GLPI['root_doc'] . '/front/entity.form.php?id=' . $entities_id . '&forcetab=' . rawurlencode('GlpiPlugin\\Maintenancecosts\\ConfigEntity$1');
Html::redirect($redirect);
