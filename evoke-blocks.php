<?php
/*
Plugin Name:        Evoke Blocks
Plugin URI:         https://roots.io/plugins/soil/
Description:        A plugin to simplify creating custom post types (CPTs) and metaboxes to assign the CPTs to pages.
Version:            1
Author:             Evoke health
Author URI:         http://www.evokehealth.com

License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/
use Evo\UI\Core as EvoCore;

$evo_custom_type_names = ['callout', 'block'];

add_action('after_setup_theme', function() use ($evo_custom_type_names) {
    $evoincludes = [
      'lib/core.php',
      'lib/callouts.php'
    ];

    foreach ($evoincludes as $file) {
      if (!$filepath = __DIR__ . '/' . $file) {
        trigger_error(sprintf(__('Error locating %s for inclusion', 'evoke-health'), $file), E_USER_ERROR);
      }
      require_once $filepath;
    }

    EvoCore::types_init($evo_custom_type_names);

} , 100);


// Register Custom Post Type
add_action( 'init', function() { EvoCore::cpt_init(); }, 0 );

add_action("add_meta_boxes", function($object) {
    EvoCore::metabox_init($object);
} );

add_action('admin_enqueue_scripts', function() {EvoCore::enqueue_css_and_js_admin(); } );

add_action("save_post", function($post_id, $post, $update){ EvoCore::metabox_save_init($post_id, $post, $update); }, 10, 3);

// Add metabox to evo_callback
function evo_custom_meta_box_markup()
{
    wp_nonce_field(basename(__FILE__), "evo_id-meta-box");
    //error_log( print_r(get_post(get_the_ID()),true) );
    ?>
    <div>
    ID: <?php the_ID() ?><br />
    slug: <?php echo (get_post_field( 'post_name', get_post(get_the_ID()) )); ?>
    </div>
    <?php
}

function evo_add_custom_meta_box()
{
    $types_to_add_box_to[] = "page";
    foreach (EvoCore::$types as $type) {
        $types_to_add_box_to[] = $type['cpt_type'];
    }
    add_meta_box("evo_id-meta-box", "Info", "evo_custom_meta_box_markup", $types_to_add_box_to, "side", "high", null);
}
add_action("add_meta_boxes", "evo_add_custom_meta_box");

 
function evo_install()
{
    // trigger our function that registers the custom post type
    //evo_custom_post_types();
 
    // clear the permalinks after the post type has been registered
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'evo_install');

function evo_deactivation()
{
    // our post type will be automatically removed, so no need to unregister it
 
    // clear the permalinks to remove our post type's rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'evo_deactivation');