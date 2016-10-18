<?php
namespace Evo\UI;
use WP_Query;

abstract class Callouts extends Core {
  public static $type = 'callout';

  public static function get_page_callout_ids($post_id) {
    $current_ids = parent::get_cpts_by_page_and_type($post_id, parent::$types[self::$type]['cpt_type']);

    return $current_ids;
  }


  public static function display_callout_number($post_id, $number) {
    parent::display_cpt_by_page_type_and_number($post_id, self::$type, $number);
  }

  public static function get_all_callouts() {
    return parent::get_all_cpt(self::$type);
  }
}
