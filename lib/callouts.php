<?php
namespace Evo\Block;

abstract class Callouts extends Core {
  public static $type = 'callout';

  public static function get_page_callout_ids($post_id) {
    $current_ids = parent::get_by_type_and_page( parent::$types[self::$type]['cpt_type'], $post_id);

    return $current_ids;
  }


  public static function display_callout_number($post_id, $number) {
    parent::display_by_type_page_and_number($post_id, self::$type, $number);
  }

  public static function get_all_callouts() {
    return parent::get_all(self::$type);
  }
}
