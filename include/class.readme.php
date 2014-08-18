<?php
if (! class_exists('wpdkPlugin_ReadMe')) {

    /**
     * Holds the readme file processing stuff.
     *
     * @package wpdkPlugin\ReadMe
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class wpdkPlugin_ReadMe {

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
         * The contents of the readme file.
         *
         * @var string $contents
         */
        private $contents;

        /**
         * The named array of readme file metadata.
         *
         * @var mixed[] $data
         */
        private $data;
        
        /**
         * The file name, relative, no path.
         * 
         * @property string $filename
         */
        private $filename;

        /**
         * Fully qualified filename, absolute path.
         *
         * @var string $file
         */
        private $file;
        
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
        }

        /**
         * Setter for private property.
         *
         * @param mixed $property
         * @param mixed $value
         * @return \wpdkPlugin_ReadMe
         */
        public function __set($property,$value) {
            if (property_exists($this, $property)) {
                if ($this->$property !== $value ) {
                    $this->$property = $value;
                    if ( $property === 'filename' ) {
                        $this->file = $this->addon->UI->current_directory . $this->filename;
                    }
                }
            }
            return $this;
        }

        /**
         * Return the header of the readme file as an array.
         *
         * @param boolean $extended if true read extended info (default: false)
         *
         */
        public function get_readme_data( $extended = false ) {
            $this->data = array();
            if ( file_exists( $this->file ) && is_readable( $this->file ) ) {
                $readme_headers = array(
                    'name'              => 'Plugin Name',
                    'min_wp_version'    => 'Requires at least',
                    'product_page'      => 'Donate link',
                    'tested_wp_version' => 'Tested up to',
                    'stable_version'    => 'Stable tag',
                  );

               $this->data = get_file_data( $this->file , $readme_headers );

                if ( $extended ) {
                    $this->get_extended_readme_data();
                }
            }

            return $this->data;
        }

        /**
         * Return HTML-ready content from a raw readme section string.
         * 
         * @param string $string
         * @return string
         */
        private function format_content( $string ) {
            $string = preg_replace('/\n{2,}/',"\n",$string);
            $clean_string = make_clickable( nl2br ( wp_specialchars( trim ( $string ) ) ) );
            $clean_string = preg_replace('/=== (.*?) ===/'      , '<h2>$1</h2>'         , $clean_string);
            $clean_string = preg_replace('/== (.*?) ==/'        , '<h3>$1</h3>'         , $clean_string);
            $clean_string = preg_replace('/\*\*(.*?)\*\*/'      , '<strong>$1</strong>' , $clean_string);
            $clean_string = preg_replace('/\*(.*?)\*/'          , '<em>$1</em>'         , $clean_string);
            $clean_string = preg_replace('/= (.*?) =/'          , '<h4>$1</h4>'         , $clean_string);

            // URL
            $matches = array();
            preg_match('/\[(.*?)\]\((.*?)\)/' , $clean_string , $matches );
            if ( count($matches) === 3 ) {
                $original_url = $matches[2];
                $revised_url = preg_replace('/<a(.*?)>(.*?)<\/a>/', '<a$1>'.$matches[1].'</a>' , $original_url);
                $clean_string = preg_replace('/\[(.*?)\]\((.*?)\)/' , $revised_url , $clean_string );
            }

            // LISTS
            $clean_string = preg_replace("/\*+(.*)?/i","<ul><li>$1</li></ul>",$clean_string);
            $clean_string = preg_replace("/(\<\/ul\>\n(.*)\<ul\>*)+/","",$clean_string);

            return $clean_string;
        }

        /**
         * Get the data header and some extra stuff, like description info, from the readme file.
         *
         * which_elements can be
         * o all = get everything
         *
         * @param string[] $which_elements = which extra things to get, defaults to 'all'
         * @return mixed[] named array of readme properties.
         */
        private function get_extended_readme_data( $which_elements = 'all' ) {
            $this->load_file_contents();

            // All - set up all things
            //
            if ( $which_elements === 'all' ) {
                $which_elements = array('description', 'changelog');
            }

            // Add some extra sauce, like the description.
            //
            foreach ( $which_elements as $element ) {
                switch ( $element ) {
                    case 'changelog':
                        $this->data['changelog'] = $this->get_changelog();
                        break;
                    case 'description':
                        $this->data['description'] = $this->get_short_description();
                        break;
                    default:
                        break;
                }
            }
        }

        /**
         * Fetch the changelog content.
         */
        function get_changelog() {
            $this->set_section( 'changelog' );
            return $this->format_content( $this->contents['sections']['changelog'] );
        }

        /**
         * Get the short description section out of the file contents.
         * 
         * @return string
         */
        function get_short_description() {
            $description = '';

            if ( ! empty( $this->contents['sections']['header'] ) ) {
                $matches = array();
                preg_match('/[\n\r]{2,}(.*?)$/s',$this->contents['sections']['header'],$matches);
                $description = $this->format_content( $matches[1] );
            }

            return $description;
        }

        /**
         * Load the file contents if they are not already loaded.
         */
        function load_file_contents() {
            if ( ! isset( $this->contents ) ) {
                $this->contents['raw'] = file_get_contents($this->file);
                $this->set_header_section();
            }
        }

        /**
         * Set the header section for the contents.
         */
        function set_header_section() {
            $matches = array();
            preg_match('/(=== .*? ===.*?)=/s',$this->contents['raw'],$matches);
            $this->contents['sections']['header'] = $matches[1];
        }

        /**
         * Find and set a major section as denoted by == with the specified marker text.
         *
         * @param string $marker the text between the ==
         */
        function set_section( $marker ) {
            if ( empty( $marker ) ) { return; }
            $matches = array();
            preg_match('/(== '.$marker.' ==(.*?))($|==)/is',$this->contents['raw'],$matches);
            $this->contents['sections'][$marker] = $matches[2];
        }
	}
}
