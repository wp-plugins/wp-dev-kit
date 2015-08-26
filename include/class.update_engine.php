<?php
if (! class_exists('wpdkPlugin_UI')) {

    /**
     * Holds the update engine code.
     *
     * @property-read   wpdkPlugin  $addon                  Pointer to the parent addon object.
     * @property        array       $current_plugin_meta    The current Plugin metadata.
     * @property-read   string      $current_slug           The current slug being requested.
     * @property-read   string      $ip_address             True IP of incoming request.
     * @property        array       $request                The request in a parsed named array.
     * @property-read   string      $target                 The target 'production' or 'prerelease'
     * @property-read   array       $default_requeset       Default settings for update request queries.
     *
     * @package wpdkPlugin\UpdateEngine
     * @author Lance Cleveland <lance@lancecleveland.com>
     * @copyright 2014 - 2015 Charleston Software Associates, LLC
     */
    class wpdkPlugin_UpdateEngine {
        private $addon;
        private $current_slug;
        public $current_plugin_meta;
        private $ip_address = null;
        private $target     = 'production';
        public  $request    = array();

        private $default_request = array(
            'current_version'   => '0.0',
            'fetch'     => '',
            'sid'       => '',
            'slug'      => '',
            'surl'      => '',
            'target'    => 'production',
            'uid'       => '',
        );

        //-------------------------------------
        // Methods
        //-------------------------------------
        
        /**
         * UI handler constructor.
         *
         * @param array $params
         */
        function __construct($params) {

            // Set properties based on constructor params,
            // if the property named in the params array is well defined.
            //
            if ($params !== null) {
                foreach ($params as $property => $value) {
                    if (property_exists($this, $property)) {
                        $this->$property = $value;
                    }
                }
            }

            $this->request = wp_parse_args( $_SERVER['QUERY_STRING'] , $this->default_request );
            $this->target = $this->request['target'];
            $this->addon->set_current_directory( $this->target );
        }


        /**
         * Return the requesting IP address, accounting for Proxy servers.
         *
         * @return string
         */
        public function get_request_ip_address() {
            if ( is_null( $this->ip_address ) ) {
                if (isset($_SERVER['HTTP_X_REAL_IP'])) {
                    $this->ip_address = $_SERVER['HTTP_X_REAL_IP'];
                } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                    $this->ip_address = $_SERVER['REMOTE_ADDR'];
                } else {
                    $this->ip_address = '0.0.0.0';
                }
            }

            return $this->ip_address;
        }

        /**
         * Process the incoming update request.
         */
        function process_request() {
            $item_to_return =  ( isset( $_REQUEST['fetch'] ) ) ? $_REQUEST['fetch'] : 'file';
            if ( ! $this->addon->set_current_plugin( ) ) { return; }

            $this->current_plugin_meta = $this->addon->PluginMeta->metadata_array['pluginMeta'][$this->addon->current_plugin['slug']];

            // Process The Request
            //
            switch( $item_to_return ) {

                case 'file':
                    $this->send_file();
                    break;

                case 'version':
                    $this->log_request_to_database( );
                    $this->send_current_version();
                    break;

                case 'info':
                    $this->log_request_to_database( );
                    $this->send_info();
                    break;
            }
        }

        /**
         * Log the requested item to the plugin options and update options.
         *
         */
        function log_request_to_database( ) {
            $this->addon->create_object_Database();
            $this->addon->Database->add_request_to_history();
        }

        /**
         * Send the current production version number.
         */
        function send_current_version() {
            print $this->current_plugin_meta[$this->target]['new_version'];
        }

        /**
         * Send the requested file.
         */
        function send_file() {
            $this->addon->send_file( $_REQUEST['slug'] );
        }

        /**
         * Send the latest plugin info.
         */
        function send_info() {
            $obj = new stdClass();
            if ( ! is_object( $obj ) ) { return null; }
            $obj->author        = 'charlestonsw';
            $obj->downloaded    = '999';

            // From plugins.json file
            //
            $obj->slug          = $this->current_plugin_meta['slug'];
            $obj->plugin        = $this->current_plugin_meta['slug'];
            $obj->version       = $this->current_plugin_meta[$this->target]['new_version'];
            $obj->new_version   = $this->current_plugin_meta[$this->target]['new_version'];
            $obj->download_link = get_home_url() . $_SERVER['SCRIPT_NAME'] . '?action=wpdk_download_file&slug=' . $this->current_plugin_meta['slug'];
            $obj->last_updated  = $this->current_plugin_meta[$this->target]['last_updated'];
            $obj->sections      = $this->set_info_sections();

            // From Readme, may not be in directory
            //
            $obj->name          = isset( $this->current_plugin_meta['name'] ) ? $this->current_plugin_meta['name'] : $this->current_plugin_meta['slug'];
            $obj->plugin_name   = $obj->name;
            $obj->homepage      = isset( $this->current_plugin_meta['product_page']     ) ? $this->current_plugin_meta['product_page']      : '';
            $obj->requires      = isset( $this->current_plugin_meta['min_wp_version']   ) ? $this->current_plugin_meta['min_wp_version']    : '3.8';
            $obj->tested        = isset( $this->current_plugin_meta['tested_wp_version']) ? $this->current_plugin_meta['tested_wp_version'] : $obj->requires;

            print serialize($obj);
        }

        /**
         * Set the info sections for the current plugin.
         */
        function set_info_sections() {
            $this->addon->PluginMeta->set_plugin_metadata_readme( $this->current_plugin_meta['slug'] , true );

            $sections = array();
            $sections['Description'] = $this->addon->PluginMeta->readme->get_description();
            $sections['Installation'] = $this->addon->PluginMeta->readme->get_installation();
            $sections['FAQ'] = $this->addon->PluginMeta->readme->get_faq();
            $sections['Changelog'] = $this->addon->PluginMeta->readme->get_changelog();

            return $sections;
        }

	}
}
