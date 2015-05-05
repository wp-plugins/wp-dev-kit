<?php
if (! class_exists('wpdkPlugin_PluginMeta')) {

    /**
     * Holds the ui-only code.
     *
     * @package wpdkPlugin\PluginMeta
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2015 Charleston Software Associates, LLC
     */
    class wpdkPlugin_PluginMeta {

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
         * The plugins.json and readme data as a named array of slug=>mixed[] properties
         * @var mixed[] $metadata_array
         */
        public $metadata_array;

        /**
         * Has the metadata been set already?
         *
         * @var bool
         */
        private $meta_set;

        /**
         * The readme file processor object.
         *
         * @var \wpdkPlugin_ReadMe $readme
         */
        public $readme;

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
                foreach ($params as $property=>$value) {
                    if (property_exists($this,$property)) { $this->$property = $value; }
                }
            }
            $this->meta_set['production'] = false;
            $this->meta_set['prerelease'] = false;
        }

        /**
         * Return false if there is no plugin meta data.  Try to read the JSON file first and set it up.
         * 
         * @return boolean 
         */
        function check_plugin_meta() {
            if ( count( $this->metadata_array['pluginMeta'] ) < 1 ) { $this->set_plugin_metadata_json(); }
            if ( ! empty ( $this->metadata_array['pluginMeta']['error']  ) ) { return false; } 
            return ( count( $this->metadata_array['pluginMeta'] ) > 0 );
        }

        /**
         * Setup the readme file parser object.
         */
        function createobject_ReadMe() {
            if ( ! is_a( $this->readme , '' ) ) {
                require_once('class.readme.php');
                $this->readme =
                    new wpdkPlugin_ReadMe(
                        array(
                            'addon'     => $this->addon
                        )
                    );
            }
        }

        /**
         * Set the JSON and readme metadata array for the plugins.
         *
         * @param string $slug slug for a specific product
         * @param boolean $extended show extra readme data
         */
        function set_plugin_metadata( $slug = null, $extended = false ) {
            if ( ! $this->meta_set[$this->addon->current_target] ) {
                if ($slug === null) {
                    $slug = $this->addon->set_plugin_slug();
                    if ($slug === null) {
                        return;
                    }
                }
                $this->set_plugin_metadata_json();
                $this->set_plugin_metadata_readme($slug, $extended);
                $this->meta_set[$this->addon->current_target] = true;
            }
        }

        /**
         * Set the plugins.json properties for the metadata array for the plugins.
         */
        function set_plugin_metadata_json() {
            $plugin_file = $this->addon->current_directory . $this->addon->options['plugin_json_file'];

            if ( file_exists( $plugin_file ) && is_readable( $plugin_file ) ) {
                $this->metadata_array = json_decode( file_get_contents( $plugin_file ), true );

            } else {
                $this->metadata_array['pluginMeta']['error'] =
                    file_exists( $plugin_file )                 ?
                        ' Could not read ' . $plugin_file . '.' :
                        $plugin_file . ' does not exist.'       ;
            }
        }

        /**
         * Set the readme properties for the metadata array for the plugins.
         *
         * @param string $slug slug for a specific product
         * @param boolean $extended true to get more data from the readme file
         */
        function set_plugin_metadata_readme( $slug, $extended = false ) {
            if ( ! $this->check_plugin_meta() ) { return ''; }
            $this->createobject_ReadMe();

            foreach ( $this->metadata_array['pluginMeta'] as $plugin_slug => $plugin_details ) {
                if ( ! empty( $slug ) && ( $plugin_slug !== $slug) ) {
                    continue;
                }

                // Drop Aliases From Plugin Info
                //
                if ( !is_array( $this->metadata_array['pluginMeta'][$plugin_slug] ) ) {
                    unset( $this->metadata_array['pluginMeta'][$plugin_slug] );
                    continue;
                }


                // Load data for non-aliased plugins.
                //
                $this->readme->filename = $this->addon->set_zip_filename( $plugin_slug, '_readme.txt' );

                $this->metadata_array['pluginMeta'][$plugin_slug] =
                    array_merge(
                        $this->metadata_array['pluginMeta'][$plugin_slug],
                        $this->readme->get_readme_data($extended)
                    );
            }
        }
	}
}
