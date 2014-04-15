<?php
if (! class_exists('wpdkPlugin_UI')) {

    /**
     * Holds the ui-only code.
     *
     * @package wpdkPlugin\UI
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class wpdkPlugin_UI {

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
         * The current directory, absolute path, based on the target being processed.
         * 
         * @var string $current_directory
         */
        public $current_directory;
        
        /**
         * The metadata for the current plugin being processed.
         * 
         * @var mixed[] $current_plugin
         */
        private $current_plugin;

        /**
         * The current build target.
         *
         * @var string $current_target
         */
        public $current_target;

        /**
         * The plugins.json and readme data as a named array of slug=>mixed[] properties
         * @var mixed[] $metadata_array
         */
        private $metadata_array;

        /**
         * The readme file processor object.
         *
         * @var \wpdkPlugin_ReadMe $readme
         */
        private $readme;

        /**
         * The style handle.
         *
         * @var string $styleHandle
         */
        private $styleHandle            = 'wpdevkitCSS';

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

            // UI (WP Front End) Only Processing
            //
            add_shortcode( 'wpdevkit' , array( $this , 'process_wpdevkit_shortcode' ) );

            // Admin CSS
            // attach to the Intel settings page.
            //
            if (file_exists($this->addon->dir.'/ui.css')) {
                wp_register_style($this->styleHandle, $this->addon->url .'/ui.css');
            }
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
         * Add the extended readme output to the formatted data layout.
         *
         * @return string
         */
        function createstring_formatted_extendeddata() {
            $output = '';

            $output .= $this->createstring_metadata_property_div( __('Description', 'csa-wpdevkit') , 'description' ) ;
            $output .= $this->createstring_metadata_property_div( __('Change Log', 'csa-wpdevkit')  , 'changelog'   ) ;

            return $output;
        }

        /**
         * Create a formatted file list.
         * 
         * @return string
         */
        function createstring_formatted_filelist() {
            $output = '';

            $onClick_orig =
                'jQuery(document).ready(function($) { '     .
                    'var data = {'                          .
                        "action: 'wpdk_download_file', "    .
                        "slug: '" . $this->current_plugin['slug'] . "', ".
                        "target: '" . $this->current_target . "' ".
                    '}; ' .
                    '$.post(ajaxurl, data, function(response) { alert( \'got back\' + response); });' .
                '});'
                ;

            $onClick =
               "jQuery('#secretIFrame').attr('src', '".admin_url('admin-ajax.php') ."?' + jQuery.param(" .
                        "{".
                            "action: 'wpdk_download_file', "    .
                            "slug: '" . $this->current_plugin['slug'] . "', ".
                            "target: '" . $this->current_target . "' ".
                        "}".
                    ")".
                ");"
                ;

            $altitle =
                sprintf( '%s %.2fk' ,
                        $this->current_plugin['slug'] ,
                        ( filesize( $this->current_plugin['zipfile'] )  / 1024 )
                    );

            $output .=
                sprintf('<a id="%s" class="wpdk-filelink" href="#" download="%s" onClick="%s" alt="%s" title="%s">',
                        $this->current_plugin['slug'],
                        $this->current_plugin['zipbase'],
                        $onClick,
                        $altitle,
                        $altitle
                ) .
                $this->createstring_fileinfo_div() .
                '</a>'
                ;

            // FILTER: wpdevkit_format_filelist
            // @param string $output the current HTML output
            // @param mixed $current_plugin the current plugin metadata
            // @return string
            return apply_filters('wpdevkit_format_filelist',$output,$this->current_plugin);
        }

        function createstring_fileinfo_div() {
            $output =
                '<div class="wpdk-listitem">' .

                    // listicon
                    '<span class="wpdk-fileicon"></span>' .

                    // filename
                    sprintf('<span class="wpdk-filename">%s</span>',
                        ( ( ! empty( $this->current_plugin['name'] ) ) ? $this->current_plugin['name'] : $this->current_plugin['slug'] )
                        ).

                    // version
                    sprintf('<span class="wpdk-filesize">%s</span>',
                        $this->current_plugin[$this->current_target]['new_version']
                        ) .

                '</div>';

            return $output;
        }

        /**
         * Create a formatted HTML output string for the plugin metadata from a plugins.json file.
         *
         * Assumes current_plugin has been set form the JSON metadata array.
         *
         * @param boolean $extended true to show extra readme details
         * @return string the HTML string to output
         */
        function createstring_formatted_metadata( $extended = false  ) {
            $header = isset( $this->current_plugin['name'] ) ? $this->current_plugin['name'] : $this->current_plugin['slug'];

            if ( ! empty( $this->current_plugin['product_page'] ) ) {
                $header =
                    sprintf('<%s><a href="%s" alt="%s" title="%s">%s</a></%s>',
                        $this->addon->options['list_heading_tag'],
                        $this->current_plugin['product_page'],
                        $header,
                        $header,
                        $header,
                        $this->addon->options['list_heading_tag']
                        );
            }

            $this->current_plugin['wp_versions'] =
                ( ( ! empty( $this->current_plugin['tested_wp_version'] ) ) ? 'Tested ' . $this->current_plugin['tested_wp_version'] : '' ) .
                ( ( ! empty( $this->current_plugin['min_wp_version']    ) ) ? ' , Min ' . $this->current_plugin['min_wp_version']    : '' )
                ;

            $output =
                "<div class='wpdevkit_plugin_metadata' " .
                    "id='wpdevkit_{$this->current_plugin['slug']}_info' name='wpdevkit_{$this->current_plugin['slug']}_info'>" .
                    $header
                ;

             // Standard Output
             //
             $output .= $this->createstring_metadata_property_div( __('Version', 'csa-wpdevkit')            , 'new_version'  , $this->current_target ) ;
             $output .= $this->createstring_metadata_property_div( __('Updated', 'csa-wpdevkit')            , 'last_updated' , $this->current_target ) ;
             $output .= $this->createstring_metadata_property_div( __('Directory', 'csa-wpdevkit')          , 'slug'                        ) ;
             $output .= $this->createstring_metadata_property_div( __('WP Versions', 'csa-wpdevkit')        , 'wp_versions'                 ) ;
             
             if ( $extended ) {
                $output .= $this->createstring_formatted_extendeddata();
             }

            $output .= '</div>';

            // FILTER: wpdevkit_format_metadata
            // @param string $output the current HTML output
            // @param mixed $current_plugin the current plugin metadata
            // @param string $section the sub-array index in the metadata
            // @return string
            return apply_filters('wpdevkit_format_metadata',$output,$this->current_plugin);
        }

        function createstring_metadata_property_div( $label , $property, $section = '' ) {
            $property_value =
                empty ( $section )                                                                                        ?
                ( isset( $this->current_plugin[$property] )           ? $this->current_plugin[$property]           : '' ) :
                ( isset( $this->current_plugin[$section][$property] ) ? $this->current_plugin[$section][$property] : '' )
                ;

             if ( ! empty ($property_value) ) {
                $return_string =
                    "<div class='wpdevkit_metadata_line' " .
                        "id='wpdevkit_line_{$this->current_plugin['slug']}_{$property}' name='wpdevkit_line_{$this->current_plugin['slug']}_{$property}'>" .
                        "<div class='wpdevkit_metadata_label' " .
                            "id='wpdevkit_label_{$this->current_plugin['slug']}_{$property}' name='wpdevkit_label_{$this->current_plugin['slug']}_{$property}'>" .
                            $label .
                        '</div>' .
                        "<div class='wpdevkit_metadata_value' " .
                            "id='wpdevkit_value_{$this->current_plugin['slug']}_{$property}' name='wpdevkit_value_{$this->current_plugin['slug']}_{$property}'>" .
                            $property_value .
                        '</div>' .
                    '</div>'
                    ;
             } else {
                 $return_string = '';
             }

            return $return_string;
        }

        /**
         * Set the JSON and readme metadata array for the plugins.
         *
         * @param string $slug slug for a specific product
         * @param boolean $extended show extra readme data
         */
        function set_plugin_metadata( $slug , $extended = false ) {
            $this->set_plugin_metadata_json();
            $this->set_plugin_metadata_readme( $slug , $extended );
        }

        /**
         * Set the plugins.json properties for the metadata array for the plugins.
         */
        function set_plugin_metadata_json() {
            $plugin_file = $this->current_directory . $this->addon->options['plugin_json_file'];

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

                $this->readme->filename = $this->set_zip_filename( $plugin_slug, '_readme.txt' );

                $this->metadata_array['pluginMeta'][$plugin_slug] =
                    array_merge(
                        $this->metadata_array['pluginMeta'][$plugin_slug] ,
                        $this->readme->get_readme_data( $extended )
                    );
            }
        }

        /**
         * Create the HTML for downloadable files.
         *
         * @return string
         */
        function list_files( ) {
            $this->set_plugin_metadata( '' );
            if ( ! $this->check_plugin_meta() ) { return ''; }

            // List all files
            //
            $output = '';
            foreach (array_keys($this->metadata_array['pluginMeta']) as $current_slug ) {
                $this->set_currentplugin( $current_slug );

                if ( isset( $this->current_plugin[$this->current_target] ) ) {
                    if ( file_exists( $this->current_plugin['zipfile'] ) && is_readable( $this->current_plugin['zipfile'] ) ) {
                        $output .= $this->createstring_formatted_filelist();
                    }
                }
            }

            if ( ! empty( $output ) ) {
                $output .= '<iframe id="secretIFrame" src="" style="display:none; visibility:hidden;"></iframe>';
            }

            return $output;
        }

        /**
         * Dump out the production metadata where the shortcode used to be.
         *
         * @param string $slug slug for a specific product
         * @param boolean $extended show extra readme data
         */
        function list_production_metadata( $slug , $extended = false ) {
            $this->set_plugin_metadata( $slug , $extended );
            $output = '';
            if ( ! $this->check_plugin_meta() ) { return ''; }

            // List all
            //
            if ( empty( $slug ) ) {
                foreach (array_keys($this->metadata_array['pluginMeta']) as $current_slug ) {
                    $this->set_currentplugin( $current_slug );
                    $output .= $this->createstring_formatted_metadata( $extended , $this->current_target );
                }
                
            // List one
            //
            } else {
                $this->set_currentplugin($slug);
                $output .= $this->createstring_formatted_metadata( $extended , $this->current_target );
            }

            return $output;
        }

        /**
         * Dump out the production metadata where the shortcode used to be.
         *
         * @param string $slug slug for a specific product
         */
        function list_production_metadata_raw( $slug ) {
            $this->set_plugin_metadata( $slug );
            return '<pre>' . print_r($this->metadata_array,true) . '</pre>';
        }

        /**
         * Process the wpdevkit shortcode.
         *
         * Actions (default: list)
         * o [wpdevkit action='list'] list details about all plugins
         * o [wpdevkit action='filelist'] list files for download
         *
         * Styles (default: formatted)
         * o [wpdevkit action='list' style='formatted'] list the details in an HTML formatted layout
         * o [wpdevkit action='list' style='raw'] list the details in a print_r raw format
         *
         * Types (default: basic)
         * o [wpdevkit action='list' type='basic'] list basic details = version, updated, directory, wp versions
         * o [wpdevkit action='list' type='detailed'] list all details = version, updated, directory, wp versions, description
         *
         * Slug (default: none = list ALL)
         * o [wpdevkit action='list' slug='wordpress-dev-kit-plugin'] list details about a specific plugin
         *
         * Target (default: production )
         * o [wpdevkit action='list' slug='wordpress-dev-kit-plugin' target="production"] list details about a specific plugin production info
         * o [wpdevkit action='list' slug='wordpress-dev-kit-plugin' target="prerelease"] list details about a specific plugin prerelease info
         *
         * @param mixed[] $atts incoming attributes
         */
        function process_wpdevkit_shortcode( $atts ) {
            if ( ! isset( $atts['action'] ) ) { $atts['action'] = 'list';       }
            if ( ! isset( $atts['style']  ) ) { $atts['style']  = 'formatted';  }
            if ( ! isset( $atts['slug']   ) ) { $atts['slug']   = '';           }
            if ( ! isset( $atts['type']   ) ) { $atts['type']   = 'basic';      }
            if ( ! isset( $atts['target'] ) ) { $atts['target'] = 'production'; }
            $this->addon->set_options();

            $this->current_target =  ( ( $atts['target'] === 'prerelease' ) ? 'prerelease' : 'production' );
            $this->set_current_directory( $atts['target'] );

            // Decide what to show based on the action
            //
            switch ( $atts['action'] ) {
                case 'list':
                    $output =
                        ( $atts['style'] === 'formatted' )                  ?
                        $this->list_production_metadata( $atts['slug'] , ( $atts['type'] !== 'basic' ) , $atts['target'] )    :
                        $this->list_production_metadata_raw( $atts['slug'] );
                    break;
                case 'filelist':
                    $output = $this->list_files( $atts['target'] );
                    break;
                default:
                    $output = '';
                    break;
            }
            
            if ( ! empty( $output ) ) {
                wp_enqueue_style($this->styleHandle);
            }

            return $output;
        }

        /**
         * Set the current_plugin property via a slug.
         *
         * Assumes JSON_metadata_array has already been loaded.
         * 
         * @param string $slug the slug to set current plugin data from
         */
        function set_currentplugin( $slug ) {
            $this->metadata_array['pluginMeta'][$slug]['slug'] = $slug;
            $this->current_plugin = $this->metadata_array['pluginMeta'][$slug];
            $this->current_plugin['slug'] = $slug;
            $this->current_plugin['zipbase'] =  ( ! empty( $this->current_plugin['zipbase'] ) ) ? $this->current_plugin['zipbase'] : $slug;
            $this->current_plugin['zipfile'] = $this->current_directory . $this->set_zip_filename();
        }

        /**
         * Send the requested file.
         *
         * @param string $slug
         */
        function send_file( $slug ) {
            if ( ! empty ($slug) ) {
                $this->set_current_directory(   $_REQUEST['target'] );
                $this->set_plugin_metadata( $_REQUEST['slug'] );
                $this->set_currentplugin(   $_REQUEST['slug']   );
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
         * Set the directory based on the target.
         *
         * @param string $target
         */
        function set_current_directory( $target = 'production' ) {
            $this->current_directory =
                    ( $target === 'production' )                   ?
                    $this->addon->options['production_directory'] :
                    $this->addon->options['prerelease_directory'] ;
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
                     isset( $this->metadata_array['pluginMeta'][$slug]['zipbase'] )   ?
                        $this->metadata_array['pluginMeta'][$slug]['zipbase']        :
                        $slug
                    ) .
                    $suffix
                    ;
        }

	}
}
