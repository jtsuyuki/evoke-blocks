<?php
namespace Evo\UI;
use WP_Query;

abstract class Core  {


  public static $block_template_path = 'templates/%s.php';
  public static $block_and_name_template_path = 'templates/%s-%s.php';

  public static $types = array();

  public static function types_init($types) {
    foreach ($types as $type) {
        self::$types[$type] = self::build_cpt_info($type);
    }
  }

  public static function cpt_init() {
    foreach (self::$types as $type) {
      self::evo_custom_post_type($type['type']);
    }
  }

  public static function metabox_init($object) {
    foreach (self::$types as $type) {
      add_meta_box($type['meta_box_key'], $type['cpt_text'], function($object) use ($type) { 
        self::cpt_meta_box_markup($object, $type['type']); }, ["page"], "side", "high", null);
    }
  }

  public static function metabox_save_init($post_id, $post, $update) {
    foreach (self::$types as $type) {
      self::evo_save_cpt_meta_box($post_id, $post, $update, $type['type']);
    }
  }

  public static function enqueue_css_and_js_admin(){
    wp_register_style('evo_css', plugins_url('../css/style.css',__FILE__ ));
    wp_enqueue_style( 'evo_css' );
    wp_register_script('evo_js', plugins_url('../js/main.js',__FILE__ ));
    wp_enqueue_script('evo_js', false, ['jquery-ui-sortable']);
  }

  public static function display_cpt_by_page_type_and_number($page_id, $type, $number) {
    $cpts_on_page = self::get_cpts_by_page_and_type($page_id, $type);
    if($cpts_on_page) {
      $number--;
      if ($cpts_on_page[$number]) {
        self::display_cpt_by_cpt( get_post($cpts_on_page[$number]) , self::$types[$type]['cpt_type']);
      }
    } else {
      echo "<!-- No CPTs of type: {$type} Found on Page id: {$page_id}  -->";
    }
  }

  public static function display_cpt_by_type_and_slug($type, $slug) {
    $type_meta = self::build_cpt_info($type);
    $block = self::get_cpt_by_type_and_slug($type, $slug);    
    self::display_cpt($block, $type, $slug, "cpt of type: {$type} and slug: {$slug} is not defined" );
  }

  public static function display_cpt_by_cpt($cpt) {
    self::log_obect([$cpt, self::get_type_by_post_type($cpt->post_type), $cpt->post_name]);
    self::display_cpt($cpt, self::get_type_by_post_type($cpt->post_type), $cpt->post_name);
  }

  public static function display_cpt($block, $type, $slug=false, $error_text = "cpt was not found") {
    $$type = $block;
    if($$type) {
      if( $slug && $tmpl_with_name = locate_template(sprintf(self::$block_and_name_template_path, $type, $slug)) ) {
        include($tmpl_with_name);
      }
      elseif ( $tmpl = locate_template(sprintf(self::$block_template_path, $type)) ) {
        include($tmpl);
      }
      else {
        echo $$type->post_content;
      }
    } elseif($error_text) {
      echo "<!-- {$error_text} --> ";
    }
  }

  public static function get_cpts_by_page_and_type($page_id, $type) {
    $block_ids = get_post_meta($page_id, self::$types[$type]['meta_key'], true);

    return $block_ids;
  }

  public static function get_cpt_by_type_and_slug($type, $slug) {
    $type_meta = self::build_cpt_info($type);
    $block = get_posts(array(
      'post_type' => $type_meta['cpt_type'],
      'name' => $slug,
      'post_status' => 'publish',
      'posts_per_page' => -1
    )); 
    //print_r($block);
    if ($block ) {
      return $block[0];
    } else {
      return null;
    }
  }

  public static function get_cpt_by_type_and_id($type, $evo_callout_post_id) {
    $callout = get_post($evo_callout_post_id);
    if($callout && $callout->post_type === $type) {
      return $callout;
    } else {
      return null;
    }
  }

  public static function get_all_cpt($type) {
    $cpts = get_posts(array(
      'post_type' => self::$types[$type]['cpt_type'],
      'post_status' => 'publish',
      'posts_per_page' => -1
    )); 
    //error_log(print_r($cpts,true) );
    $cpt_array = array();
    foreach ($cpts as $cpt) {
      $cpt_array[$cpt->ID] = $cpt;
    }
    //error_log(print_r($cpt_array,true) );
    return $cpt_array;
  }

  public static function build_cpt_info($item) {
    $info  = array(
      'type' => $item,
      'meta_key' => "evo_{$item}_ids",
      'meta_box_key' => "evo_{$item}-meta-box",
      'nonce_key' => "evo_{$item}-meta-box-nonce", 
      'cpt_type' => "evo_{$item}", 
      'cpt_form_field' => "evo_{$item}_ids",
      'cpt_text' => ucfirst($item)
    );
    return $info;
  }

  public static function get_type_by_post_type($cpt_type) {
    foreach (self::$types as $type=> $meta) {
      if ( $meta['cpt_type']==$cpt_type) {
        return $type;
      }
    }
    return null;
  }

  // generic function for metabox markup
  public static function cpt_meta_box_markup($object, $item) 
  {
      $info = self::build_cpt_info($item);
      $meta_key = $info["meta_key"];
      $nonce_key = $info["nonce_key"];
      $cpt_type = $info["cpt_type"];
      $cpt_form_field = $info["cpt_form_field"] . "[]";
      $current_ids = self::get_cpts_by_page_and_type($object->ID, $item);
      //$current_ids = get_post_meta($object->ID, $meta_key, true);
      $all_items = self::get_all_cpt($item);

      wp_nonce_field(basename(__FILE__), $nonce_key); 
      echo "<div class='evo_cpt_list'>";
      self::log_obect($current_ids);
      if($current_ids) {
          foreach($current_ids as $id) {
            echo sprintf("<div class=\"current_cpts\">%s<input type=\"hidden\" name=\"{$cpt_form_field}\" value=\"%s\" /><div class=\"evo_remove\"></div></div>\n", $all_items[$id]->post_title , $id);   
          }
          
      } 
      echo "</div>";
      echo '<div class="evo_select_callout">';
      echo "<select name='evo_new_{$item}_id'>";
      echo "<option value='select'>Select a {$info["cpt_text"]}</option>";
      if($all_items) {
        foreach($all_items as $item) {
          echo sprintf("<option value=\"%s\">%s</option>", $item->ID, $item->post_title );   
        }       
      }

      echo '</select>';
      echo "<div data-field-name='{$cpt_form_field}' class='evo_add_cpt'></div>";
      echo '</div>';
  }

  public static function evo_save_cpt_meta_box($post_id, $post, $update, $item)
  {
      $info = self::build_cpt_info($item);
      $meta_key = $info["meta_key"];
      $nonce_key = $info["nonce_key"];
      $cpt_type = $info["cpt_type"];
      $cpt_form_field = $info["cpt_form_field"];
      // error_log(print_r($info, true));
      // error_log(print_r($_POST, true));
      // error_log(print_r($_POST[$nonce_key], true));
      // error_log(print_r($_POST[$cpt_form_field], true));
      if (!isset($_POST[$nonce_key]) || !wp_verify_nonce($_POST[$nonce_key], basename(__FILE__))) { 
          error_log("failed nonce");
          return $post_id;
      }

      if(!current_user_can("edit_post", $post_id)) {
          error_log("cant edit");
          return $post_id;
      }

      if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
          error_log("failed autosave");
          return $post_id;
      }

      $slug = "page";
      if($slug != $post->post_type) {
          error_log("failed slug:".$slug);
          return $post_id;
      }

      $meta_box_text_value = Array();

      if(isset($_POST[$cpt_form_field]))
      {   
          $meta_box_text_value = $_POST[$cpt_form_field];
      }   

      update_post_meta($post_id, $meta_key, $meta_box_text_value);

  }

  public static function evo_custom_post_type($item) {
      $info = self::build_cpt_info($item);
      $meta_key = $info["meta_key"];
      $nonce_key = $info["nonce_key"];
      $cpt_type = $info["cpt_type"];
      $cpt_form_field = $info["cpt_form_field"];
      $cpt_text = $info["cpt_text"];

    $labels = array(
      'name'                  => _x( "{$cpt_text}s", 'Post Type General Name', 'text_domain' ),
      'singular_name'         => _x( "{$cpt_text}", 'Post Type Singular Name', 'text_domain' ),
      'menu_name'             => __( "{$cpt_text}s", 'text_domain' ),
      'name_admin_bar'        => __( "{$cpt_text}", 'text_domain' ),
      'archives'              => __( 'Item Archives', 'text_domain' ),
      'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
      'all_items'             => __( "All {$cpt_text}", 'text_domain' ),
      'add_new_item'          => __( "Add New {$cpt_text}", 'text_domain' ),
      'add_new'               => __( "Add {$cpt_text}", 'text_domain' ),
      'new_item'              => __( "New {$cpt_text}", 'text_domain' ),
      'edit_item'             => __( "Edit {$cpt_text}", 'text_domain' ),
      'update_item'           => __( "Update {$cpt_text}", 'text_domain' ),
      'view_item'             => __( "View {$cpt_text}", 'text_domain' ),
      'search_items'          => __( 'Search Item', 'text_domain' ),
      'not_found'             => __( 'Not found', 'text_domain' ),
      'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
      'featured_image'        => __( 'Featured Image', 'text_domain' ),
      'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
      'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
      'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
      'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
      'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
      'items_list'            => __( 'Items list', 'text_domain' ),
      'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
      'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
    );
    $args = array(
      'label'                 => __( "{$cpt_text}", 'text_domain' ),
      'description'           => __( 'A small snippet of html for a shared callout', 'text_domain' ),
      'labels'                => $labels,
      'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields', ),
      'taxonomies'            => array( 'post_tag', 'category' ),
      'hierarchical'          => false,
      'public'                => true,
      'show_ui'               => true,
      'show_in_menu'          => true,
      'menu_position'         => 5,
      'show_in_admin_bar'     => true,
      'show_in_nav_menus'     => false,
      'can_export'            => true,
      'has_archive'           => false,    
      'exclude_from_search'   => true,
      'publicly_queryable'    => false,
      'capability_type'       => 'page',
    );
    register_post_type( $cpt_type, $args );
  }

  public static function log_msg($message) {
    error_log($message);
  }

  public static function log_msg_obect($message, $object) {
    error_log("$message \n"  . print_r($object, true));
  }

  public static function log_obect($object) {
    error_log(print_r($object, true));
  }
}