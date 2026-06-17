<?php

namespace GlpiPlugin\Maintenancecosts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use Html;

class Pager
{
   public static function perPage(): int
   {
      $value = (int) ($_GET['per_page'] ?? 50);
      return in_array($value, [20, 50, 100, 200], true) ? $value : 50;
   }

   public static function page(): int
   {
      return max(1, (int) ($_GET['page'] ?? 1));
   }

   public static function start(int $page, int $perPage, int $total = 0): int
   {
      if ($total > 0) {
         $maxPage = max(1, (int) ceil($total / $perPage));
         $page = min($page, $maxPage);
      }
      return max(0, ($page - 1) * $perPage);
   }

   public static function render(int $total, int $page, int $perPage, array $params = []): void
   {
      $maxPage = max(1, (int) ceil(max(0, $total) / $perPage));
      $page = min(max(1, $page), $maxPage);
      $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
      $to = min($total, $page * $perPage);

      echo "<div class='plugin-maintenancecosts-pager d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 mb-2'>";
      echo "<div>" . Html::clean(sprintf(__('Exibindo %1$s a %2$s de %3$s registros', 'maintenancecosts'), (int) $from, (int) $to, (int) $total)) . "</div>";
      echo "<div class='d-flex align-items-center gap-2 flex-wrap'>";
      echo "<form method='get' class='d-flex align-items-center gap-2'>";
      foreach ($params as $key => $value) {
         if ($key === 'page' || $key === 'per_page' || $value === '') {
            continue;
         }
         echo Html::hidden($key, ['value' => (string) $value]);
      }
      echo Html::hidden('page', ['value' => 1]);
      echo "<select name='per_page' class='form-select' onchange='this.form.submit()'>";
      foreach ([20, 50, 100, 200] as $option) {
         echo "<option value='" . (int) $option . "' " . ($option === $perPage ? 'selected' : '') . ">" . (int) $option . "</option>";
      }
      echo "</select>";
      echo "<span>" . Html::clean(__('linhas / página', 'maintenancecosts')) . "</span>";
      echo "</form>";

      echo "<div class='btn-group'>";
      self::link($page > 1, __('Anterior', 'maintenancecosts'), $page - 1, $perPage, $params);
      echo "<span class='btn btn-outline-secondary disabled'>" . (int) $page . " / " . (int) $maxPage . "</span>";
      self::link($page < $maxPage, __('Próxima', 'maintenancecosts'), $page + 1, $perPage, $params);
      echo "</div>";
      echo "</div></div>";
   }

   private static function link(bool $enabled, string $label, int $page, int $perPage, array $params): void
   {
      if (!$enabled) {
         echo "<span class='btn btn-outline-secondary disabled'>" . Html::clean($label) . "</span>";
         return;
      }

      $params['page'] = $page;
      $params['per_page'] = $perPage;
      echo "<a class='btn btn-outline-secondary' href='?" . Html::clean(http_build_query($params)) . "'>" . Html::clean($label) . "</a>";
   }
}
