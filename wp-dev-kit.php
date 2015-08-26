<?php
/**
 * Plugin Name: WordPress Development Kit Plugin
 * Plugin URI: http://www.charlestonsw.com/product/wordpress-development-kit-plugin/
 * Description: A plugin that works with my WP Dev Kit, plugins.json in particular, to render product and plugin metadata on a WordPress page or post.
 * Author: Charleston Software Associates
 * Author URI: http://charlestonsw.com/
 * Requires at least: 3.8
 * Tested up to : 4.3
 * Version: 1.0
 *
 * Text Domain: csa-wpdevkit
 * Domain Path: /languages/
 *
 */

define( 'WPDK__VERSION'     ,   '1.0'                       );
define( 'WPDK__PLUGIN_DIR'  ,   plugin_dir_path( __FILE__ ) );
define( 'WPDK__PLUGIN_FILE' ,   __FILE__                    );

require_once( WPDK__PLUGIN_DIR . 'include/class.wpdk.php' );

register_activation_hook( WPDK__PLUGIN_FILE , array( 'wpdkPlugin' , 'plugin_activation' ) );

add_action( 'init'                                  , array( 'wpdkPlugin'   , 'init'            ) );
add_action( 'wp_ajax_wpdk_download_file'            , array( 'wpdkPlugin'   , 'download_file'   ) );
add_action( 'wp_ajax_nopriv_wpdk_download_file'     , array( 'wpdkPlugin'   , 'download_file'   ) );
add_action( 'wp_ajax_wpdk_updater'                  , array( 'wpdkPlugin'   , 'updater'         ) );
add_action( 'wp_ajax_nopriv_wpdk_updater'           , array( 'wpdkPlugin'   , 'updater'         ) );

