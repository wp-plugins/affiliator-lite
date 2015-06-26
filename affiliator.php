<?php

/*
Plugin Name: Affiliator Lite
Plugin URI: http://affiliator.binpress.com
Description: Affiliate plugin that allows you to create a fully powered affiliate site.
Author: Vasilis Kerasiotis
Version: 1.6
Author URI: http://www.anastasia-app.com
*/

require_once __DIR__.'/lib/xmlParser.php';
require_once __DIR__.'/lib/wp_settings.php';
require_once __DIR__.'/lib/affiliator.php';

$affiliator = new affiliator();

register_activation_hook(__FILE__, array($affiliator,'activate'));
register_deactivation_hook( __FILE__, array($affiliator,'deactivate') );

add_action('admin_menu', array($affiliator,'plugin_menu'));
$type = get_option('products_post_type_name')?get_option('products_post_type_name'):'products';
add_filter( $type.'_rewrite_rules', array($affiliator,'add_permastruct') );

add_action( 'pre_get_posts', array($affiliator,'pre_get_posts') );
// parse the generated links
add_filter( 'post_type_link', array($affiliator,'custom_post_permalink'), 10, 4);

add_action('init', array($affiliator,'check_is_post'));
add_action('init', array($affiliator,'create_post_types'));
add_action('wp_footer', array($affiliator,'check_post'));
add_filter( 'template_include', array($affiliator,'custom_template'));