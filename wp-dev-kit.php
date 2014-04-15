<?php
/**
 * Plugin Name: WordPress Development Kit Plugin
 * Plugin URI: http://www.charlestonsw.com/product/wordpress-development-kit-plugin/
 * Description: A plugin that works with my WP Dev Kit, plugins.json in particular, to render product and plugin metadata on a WordPress page or post.
 * Version: 0.4.0
 * Author: Charleston Software Associates
 * Author URI: http://charlestonsw.com/
 * Requires at least: 3.4
 * Tested up to : 3.9
 *
 * Text Domain: csa-wpdevkit
 * Domain Path: /languages/
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// If we have not been here before, let's get started...
//
if ( ! class_exists( 'wpdkPlugin' ) ) {

    /**
    * wpdkPlugin
    *
    * @package wpdkPlugin
    * @author Lance Cleveland <lance@charlestonsw.com>
    * @copyright 2014 Charleston Software Associates, LLC
    */
    class wpdkPlugin {

        //-------------------------------------
        // Properties
        //-------------------------------------

        /**
         *
         * @var \wpdkPlugin_Admin $Admin
         */
        public $Admin;

        /**
         *
         * @var \wpdkPlugin_Admin_UI $AdminUI
         */
        public $AdminUI;

        /**
         * The directory we live in.
         *
         * @var string $dir
         */
        public $dir;

        /**
         * Our plugin options.
         *
         * @var string[]
         */
        public $options = array(
            'list_heading_tag'     => 'h1',
            'production_directory' => '/var/www/html/wp-content/production_files/' ,
            'plugin_json_file'     => 'plugins.json' ,
            'prerelease_directory' => '/var/www/html/wp-content/prerelease_files/' ,
        );

        /**
         * Have the options been set (defaults merged with DB fetched options?)
         *
         * @var boolean $options_set
         */
        private $options_set = false;

        /**
         * Our slug.
         *
         * @var string $slug
         */
        private $slug                   = null;


        /**
         *
         * @var \wpdkPlugin_UI $UI
         */
        public $UI;

        /**
         * The url to this plugin admin features.
         *
         * @var string $url
         */
        public $url;


        //-------------------------------------
        // Methods
        //-------------------------------------

        /**
         * Invoke the plugin as singleton.
         *
         * @static
         */
        public static function init() {
            static $instance = false;
            if ( !$instance ) {
                load_plugin_textdomain( 'csa-wpdevkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
                $instance = new wpdkPlugin();
            }
            return $instance;
        }


        /**
         * Constructor.
         */
        function __construct() {

            // Set properties for this plugin.
            //
            $this->url  = plugins_url('',__FILE__);
            $this->dir  = plugin_dir_path(__FILE__);
            $this->slug = plugin_basename(__FILE__);

            add_action( 'admin_menu' , array( $this , 'admin_menu' ) );
            add_action( 'wp_enqueue_scripts', array ( $this , 'createobject_UI' ) );
        }


        //-------------------------------------------------------------
        // METHODS :: STANDARD SLP ADD ON ADMIN INIT
        //
        // All admin related hooks and filters should go in admin init.
        //
        // This saves a ton of overhead on stacking admin-only calls
        // that will never get called unless you are on the admin
        // interface.
        //-------------------------------------------------------------

        /**
         * WordPress admin_menu hook.
         *
         * Do not put any hooks/filters here other than the admin init hook.
         */
        function admin_menu(){
            $this->createobject_AdminUI();
            $this->createobject_Admin();
        }

        /**
         * Create and attach the admin processing object.
         */
        function createobject_Admin() {
            if ( ! isset ( $this->Admin  ) ) {
                require_once( $this->dir.'include/class.admin.php' );
                $this->Admin =
                    new wpdkPlugin_Admin(
                        array(
                            'addon'     => $this
                        )
                    );
            }
        }

        /**
         * Create and attach the admin processing object.
         */
        function createobject_AdminUI() {
            if ( ! isset ( $this->AdminUI  ) ) {
                require_once( $this->dir.'include/class.admin.ui.php' );
                $this->AdminUI =
                    new wpdkPlugin_Admin_UI(
                        array(
                            'addon'     => $this
                        )
                    );
            }
        }

        /**
         * Create and attach the UI processing object.
         */
        function createobject_UI() {
            if ( ! isset ( $this->UI  ) ) {
                require_once( $this->dir.'include/class.ui.php' );
                $this->UI =
                    new wpdkPlugin_UI(
                        array(
                            'addon'     => $this
                        )
                    );
            }
        }

        /**
         * Set the options by merging those from the DB with the defaults for this add-on pack.
         */
        function set_options() {
            if ( ! $this->options_set ) {
                $this->options = array_merge($this->options,get_option('wpdevkit_options',array()));
                $this->options_set = true;
            }
        }

        /**
         * Do this after SLP initiliazes.
         *
         * @return null
         */
        function wp_init() {
            if (!$this->setPlugin()) { return; }
            $this->plugin->register_addon(plugin_basename(__FILE__));
        }

        /**
         * Create the Debug My Plugin Panels
         *
         * @return null
         */
        static function create_DMPPanels() {
            if (!isset($GLOBALS['DebugMyPlugin'])) { return; }
            if (class_exists('DMPPanelWPDKPlugin') == false) {
                require_once(plugin_dir_path(__FILE__).'include/class.dmppanels.php');
            }
            $GLOBALS['DebugMyPlugin']->panels['wpdevkit.main'] = new DMPPanelWPDKPlugin();
        }

        static function download_file() {
            $wpdk = wpdkPlugin::init();
            $wpdk->set_options();
            $wpdk->createobject_UI();
            $wpdk->UI->send_file( $_REQUEST['slug'] );
            die();
        }

    }

    add_action( 'init'          , array( 'wpdkPlugin'   , 'init'                ) );
    add_action( 'dmp_addpanel'  , array( 'wpdkPlugin'   , 'create_DMPPanels'    ) );
    add_action( 'wp_ajax_wpdk_download_file'            , array('wpdkPlugin','download_file') );
    add_action( 'wp_ajax_nopriv_wpdk_download_file'     , array('wpdkPlugin','download_file') );
}