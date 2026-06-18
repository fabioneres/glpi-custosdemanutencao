<?php

use GlpiPlugin\Maintenancecosts\Config;

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';

Session::checkLoginUser();
Config::checkRight(Config::RIGHT_CONFIG, UPDATE);

$entities_id = (int) ($_POST['entities_id'] ?? $_GET['entities_id'] ?? -1);
if ($entities_id < 0 || !Session::haveAccessToEntity($entities_id, true)) {
   Html::displayRightError();
}

if (isset($_POST['save_entity_rule'])) {
   if (Config::saveEntityRule($entities_id, $_POST)) {
      Session::addMessageAfterRedirect(__('Disponibilidade da entidade salva.', 'maintenancecosts'), false, INFO);
   } else {
      Session::addMessageAfterRedirect(__('Não foi possível salvar a disponibilidade da entidade.', 'maintenancecosts'), false, ERROR);
   }
}

$redirect = $CFG_GLPI['root_doc'] . '/front/entity.form.php?id=' . $entities_id . '&forcetab=' . rawurlencode('GlpiPlugin\\Maintenancecosts\\ConfigEntity$1');
Html::redirect($redirect);
