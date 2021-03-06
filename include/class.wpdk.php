<?php

/**
 * wpdkPlugin
 *
 * @property        wpdkPlugin_Activation   $Activation
 * @property        wpdkPlugin_Admin        $Admin
 * @property        wpdkPlugin_Database     $Database
 * @property        wpdkPlugin              $instance
 * @property        wpdkPlugin_UpdateEngine $UpdateEngine
 * @property        wpdkPlugin_UI           $UI
 * @property        wpdkPlugin_Woo          $Woo
 *
 *
 * @package wpdkPlugin
 * @author Lance Cleveland <lance@lancecleveland.com>
 * @copyright 2014-2015 Charleston Software Associates, LLC
 */
class wpdkPlugin {
    private $Activation;
    private $Admin;
    public  $Database;
    public  $instance;
    public  $UpdateEngine;
    public  $UI;
    public  $Woo;

    /**
     * The current directory, absolute path, based on the target being processed.
     *
     * @var string $current_directory
     */
    public $current_directory = 'production';

    /**
     * The metadata for the current plugin being processed.
     *
     * @var mixed[] $current_plugin
     */
    public $current_plugin;

    /**
     * The current target build level.
     *
     * @var string production (default) || prerelease
     */
    public $current_target = 'production';

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
        'installed_version'         => '0.0',
        'list_heading_tag'          => 'h1',
        'production_directory'      => '/var/www/html/wp-content/production_files/' ,
        'plugin_json_file'          => 'plugins.json' ,
        'prerelease_directory'      => '/var/www/html/wp-content/prerelease_files/' ,
        'requires_subscription'     => '',
        'subscription_product_id'   => '',
    );

    /**
     * Have the options been set (defaults merged with DB fetched options?)
     *
     * @var boolean $options_set
     */
    private $options_set = false;

    /**
     *
     * @var \wpdkPlugin_PluginMeta $PluginMeta
     */
    public $PluginMeta;

    /**
     * @var string the requested slug
     */
    public $requested_slug = null;


    /**
     * Our slug.
     *
     * @var string $slug
     */
    private $slug                   = null;

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
            load_plugin_textdomain( 'csa-wpdevkit', false, dirname( plugin_basename( WPDK__PLUGIN_FILE ) ) . '/languages/' );
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
        $this->url  = plugins_url('',WPDK__PLUGIN_FILE);
        $this->dir  = WPDK__PLUGIN_DIR;
        $this->slug = plugin_basename(WPDK__PLUGIN_FILE);

        require_once( 'base_class.object.php' );


        add_action( 'admin_menu' , array( $this , 'admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array ( $this , 'createobject_UI' ) );
    }

    /**
     * Activate or update this plugin.
     */
    public static function plugin_activation( ) {
        $instance = wpdkPlugin::init();
        $instance->set_options();
        if ( ! isset( $instance->options['installed_version'] ) || empty( $instance->options['installed_version'] ) ||
            version_compare( $instance->options['installed_version'], WPDK__VERSION , '<' )
        ) {
            $instance->createobject_Activation();
        }
    }

    /**
     * WordPress admin_menu hook.
     *
     * Do not put any hooks/filters here other than the admin init hook.
     */
    function admin_menu(){
        $this->createobject_Admin();
    }

    /**
     * Create and attach the activation processing object.
     */
    function createobject_Activation() {
        if ( ! isset ( $this->Activation  ) ) {
            require_once( 'class.activation.php' );
            $this->Activation = new wpdkPlugin_Activation();
        }
    }

    /**
     * Create and attach the admin processing object.
     */
    function createobject_Admin() {
        if ( ! isset ( $this->Admin  ) ) {
            require_once( 'class.admin.php' );
            $this->Admin = new wpdkPlugin_Admin();
        }
    }


    /**
     * Create and attach the admin processing object.
     */
    function create_object_Database() {
        if ( ! isset ( $this->Database  ) ) {
            require_once( 'class.database.php' );
            $this->Database = new wpdkPlugin_Database();
        }
    }

    /**
     * Create and attach the UI processing object.
     */
    function create_object_PluginMeta() {
        if ( ! isset ( $this->PluginMeta  ) ) {
            require_once( 'class.plugin_meta.php' );
            $this->PluginMeta =
                new wpdkPlugin_PluginMeta(
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
            require_once( 'class.ui.php' );
            $this->UI = new wpdkPlugin_UI();
        }
    }


    /**
     * Create and attach the update engine object.
     */
    function create_object_UpdateEngine() {
        if ( ! isset ( $this->UpdateEngine  ) ) {
            require_once( 'class.update_engine.php' );
            $this->UpdateEngine =
                new wpdkPlugin_UpdateEngine(
                    array(
                        'addon'     => $this
                    )
                );
        }
    }

    /**
     * Create the Woo interface object.
     */
    function create_object_Woo() {
        if ( ! isset ( $this->Woo  ) ) {
            require_once('class.woo.php');
            $this->Woo = new  wpdkPlugin_Woo();
        }
    }

    /**
     * Set the directory based on the target.
     *
     * Default is production.
     *
     * @param string $target
     */
    function set_current_directory( $target = 'production' ) {

        $this->current_target = ( $target === 'prerelease' ) ? 'prerelease' : 'production';

        $this->current_directory =
            ( $this->current_target === 'prerelease' )                   ?
                $this->options['prerelease_directory'] :
                $this->options['production_directory'] ;
    }

    /**
     * Set the current_plugin property via a slug.
     *
     * Assumes JSON_metadata_array has already been loaded.
     *
     * @param string $slug the slug to set current plugin data from
     * @return boolean TRUE if set current plugin is OK.
     */
    function set_current_plugin( $slug = null ) {

        // Set slug
        //
        if ( $slug === null ) {
            $slug = $this->set_plugin_slug();
            if ( $slug === null ) { return false; }
        }

        // Set meta
        //
        $this->create_object_PluginMeta();
        $this->PluginMeta->set_plugin_metadata();
        $this->PluginMeta->metadata_array['pluginMeta'][$slug]['slug'] = $slug;

        // Set current plugin
        //
        $this->current_plugin = $this->PluginMeta->metadata_array['pluginMeta'][$slug];
        $this->current_plugin['slug'] = $slug;
        $this->current_plugin['zipbase'] =  ( ! empty( $this->current_plugin['zipbase'] ) ) ? $this->current_plugin['zipbase'] : $slug;
        $this->current_plugin['zipfile'] = $this->current_directory . $this->set_zip_filename();
        return true;
    }

    /**
     * Set the slug requested.
     *
     * @return mixed current slug from request if set.
     */
    function set_plugin_slug() {
        if ( $this->requested_slug === null ) {
            if (isset($_REQUEST['slug']) && (!empty($_REQUEST['slug']))) {
                $this->requested_slug = $_REQUEST['slug'];
            } else if (isset($_REQUEST['plugin']) && (!empty($_REQUEST['plugin']))) {
                $this->requested_slug =  $_REQUEST['plugin'];
            }
        }
        return $this->requested_slug;
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
     * Set a plugin base file name for a zip file.
     *
     * @param string $slug  the slug to get the zip file base for.
     * @param string $suffix what do we want to end the filename with? (default '.zip')
     * @return string
     */
    function set_zip_filename( $slug = '', $suffix = '.zip' ) {
        if ( empty ( $slug ) ) {
            $slug = $this->current_plugin['slug'];
        }
        return (
        isset( $this->PluginMeta->metadata_array['pluginMeta'][$slug]['zipbase'] )   ?
            $this->PluginMeta->metadata_array['pluginMeta'][$slug]['zipbase']        :
            $slug
        ) .
        $suffix
            ;
    }

    /**
     * AJAX: Download the referenced file.
     */
    static function download_file() {
        $wpdk = wpdkPlugin::init();
        $wpdk->set_options();
        $wpdk->send_file( $_REQUEST['slug'] );
        die();
    }


    /**
     * Send the requested file.
     *
     * @param string $slug
     */
    function send_file( $slug ) {
        if ( ! empty ($slug) ) {
            $this->set_current_directory(   $_REQUEST['target'] );
            $this->create_object_PluginMeta();
            $this->PluginMeta->set_plugin_metadata();
            $this->set_current_plugin();
            $this->send_file_header();
            print file_get_contents( $this->current_plugin['zipfile'] );
        }
    }

    /**
     * Send the file Header
     *
     */
    function send_file_header() {
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $this->set_zip_filename() );
        header( 'Content-Type: application/zip;');
        header( 'Pragma: no-cache');
        header( 'Expires: 0');
    }


    /**
     * AJAX: Handle plugin update requests.
     */
    static function updater() {
        $wpdk = wpdkPlugin::init();
        $wpdk->set_options();
        $wpdk->create_object_UpdateEngine();
        $wpdk->UpdateEngine->process_request();
        die();
    }

}

