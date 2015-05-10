<?php
if (! class_exists('wpdkPlugin_UI')) {

    /**
     * Holds the update engine code.
     *
     * @package wpdkPlugin\UpdateEngine
     * @author Lance Cleveland <lance@lancecleveland.com>
     * @copyright 2014 - 2015 Charleston Software Associates, LLC
     */
    class wpdkPlugin_UpdateEngine {

        //-------------------------------------
        // Properties
        //-------------------------------------

        /**
         * Pointer to the parent addon object.
         *
         * @var \wpdkPlugin $addon
         */
        private $addon;

        /**
         * The current Plugin metadata.
         *
         * @var string[] $current_plugin_meta
         */
        private $current_plugin_meta;

        /**
         * The current slug being requested.
         *
         * @var $current_slug
         */
        private $current_slug;

        /**
         * The target 'production' or 'prerelease'
         * @var string
         */
        private $target = 'production';

        //-------------------------------------
        // Methods
        //-------------------------------------
        
        /**
         * UI handler constructor.
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

            $this->target = isset( $_REQUEST['target'] ) ? $_REQUEST['target'] : 'production';
            $this->addon->set_current_directory( $this->target );
        }

        /**
         * Process the incoming update request.
         */
        function process_request() {
            $item_to_return =  ( isset( $_REQUEST['fetch'] ) ) ? $_REQUEST['fetch'] : 'file';
            if ( ! $this->addon->set_current_plugin( ) ) { return; }

            $this->current_plugin_meta = $this->addon->PluginMeta->metadata_array['pluginMeta'][$this->addon->current_plugin['slug']];
            $this->update_last_ten( $item_to_return );

            // Process The Request
            //
            switch( $item_to_return ) {

                case 'file':
                    $this->send_file();
                    break;

                case 'version':
                    $this->send_current_version();
                    break;

                case 'info':
                    $this->send_info();
                    break;
            }
        }

        /**
         * Log the requested item to the plugin options and update options.
         *
         * @param $requested_item
         */
        function update_last_ten( $requested_item ) {
            $last_ten = $this->addon->options['last_ten_requests'];
            if ( ! is_array( $last_ten ) ) { $last_ten = array(); }
            if ( count( $last_ten ) > 9 ) {
                $deleted_element = array_shift( $last_ten );
            }
            $last_ten[] =
                sprintf(
                    __('Slug %s request for %s %s <pre>%s</pre> FROM: <pre>%s</pre> REQUEST: <pre>%s</pre>'),
                    $this->addon->current_plugin['slug'],
                    $this->addon->current_target ,
                    $requested_item ,
                    print_r($this->current_plugin_meta,true),
                    print_r($_SERVER , true ),
                    print_r($_REQUEST , true )
                );
            $this->addon->options['last_ten_requests'] = $last_ten;
            update_option('wpdevkit_options' , $this->addon->options );
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
