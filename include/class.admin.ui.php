<?php
if (! class_exists('wpdkPlugin_Admin_UI')) {

    /**
     * Holds the admin-only UI code.
     *
     * @package wpdkPlugin\Admin\UI
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class wpdkPlugin_Admin_UI extends WPDK_BaseClass_Object {

        /**
         * Option meta data stdClass objects.
         *
         * @var \stdClass[] $optionMeta
         */
        private $optionMeta;

        /**
         * The admin style handle.
         *
         * @var string $styleHandle
         */
        private $styleHandle            = 'wpdevkitAdminCSS';

        /**
         * Admin interface constructor.
         */
        function __construct( $options = array() ) {
            parent::__construct( $options );

            // Admin CSS
            // attach to the Intel settings page.
            //
            if (file_exists($this->addon->dir.'/admin.css')) {
                wp_register_style($this->styleHandle, $this->addon->url .'/admin.css');
            }
            add_action('admin_enqueue_scripts',array($this,'enqueue_admin_stylesheet'));

            // Initialize the options meta data
            //
            $this->initOptions();

            // Register the Settings
            //
            $this->register_Settings();

            // Admin UI Only Filters
            //
        }


        /**
         * Create a new option meta object.
         *
         * $type can be
         *    'text'   - simple text input
         *    'slider' - checkbox rendered as a slider
         *
         * @param string $slug
         * @param string $label
         * @param string $desc
         * @param string $type
         * @param int    $order
         * @return \stdClass
         */
        function create_OptionMeta($slug,$label,$desc,$type='text',$order=10) {
            $optionMeta = new stdClass();
            $optionMeta->slug           = $slug;
            $optionMeta->label          = $label;
            $optionMeta->description    = $desc;
            $optionMeta->type           = $type;
            $optionMeta->order          = $order;
            return $optionMeta;
        }

        /**
         * Setup the options meta data.
         */
        function initOptions() {
            $this->optionMeta['production_directory'] =
                $this->create_OptionMeta(
                        'production_directory',
                        __('Production Directory','csa-wpdevkit'),
                        __('Absolute path to your production directory.', 'csa-wpdevkit')
                        );
            $this->optionMeta['prerelease_directory'] =
                $this->create_OptionMeta(
                        'prerelease_directory',
                        __('Prerelease Directory','csa-wpdevkit'),
                        __('Absolute path to your prerelease directory.', 'csa-wpdevkit')
                        );
            $this->optionMeta['plugin_json_file'] =
                $this->create_OptionMeta(
                        'plugin_json_file',
                        __('Plugin JSON File','csa-wpdevkit'),
                        __('Relative path, from production/prelease directories, to your plugin metadata JSON file (plugins.json).', 'csa-wpdevkit')
                        );
            $this->optionMeta['list_heading_tag'] =
                $this->create_OptionMeta(
                        'list_heading_tag',
                        __('List Heading HTML','csa-wpdevkit'),
                        __('Wrap the formatted list headings in this HTML tag.', 'csa-wpdevkit')
                        );
            $this->optionMeta['subscription_product_id'] =
                $this->create_OptionMeta(
                    'subscription_product_id',
                    __('Subscription Product ID','csa-wpdevkit'),
                    __('The product ID for an WooCommerce subscription product the user must have active to retrieve product updates.', 'csa-wpdevkit')
                );
            $this->optionMeta['requires_subscription'] =
                $this->create_OptionMeta(
                    'requires_subscription',
                    __('Requires Subscription','csa-wpdevkit'),
                    __('A product slug (or slugs, comma separated) that require a subsciprtion to the Subscription Product ID in order to retrieve udpate files.', 'csa-wpdevkit')
                );
        }

        /**
         * Enqueue the admin stylesheet when needed.
         *
         * Currently only on the plugin-install.php pages.
         *
         * @var string $hook
         */
        function enqueue_admin_stylesheet($hook) {
            switch ($hook) {
                case 'plugins_page_wpdevkit':
                    wp_enqueue_style($this->styleHandle);
                    break;
                default:
                    break;
            }
        }

        /**
         * Figure out which type of input to render.
         *
         * @param mixed[] $args
         */
        function render_Input($args) {
            switch ($args['type']) {
                case 'pre':
                    $this->render_pre_input($args);
                    break;
                case 'read-only':
                    $this->render_read_only($args);
                    break;
                case 'text':
                    $this->render_TextInput($args);
                    break;
                case 'slider':
                    $this->render_SliderInput($args);
                    break;
                default:
                    break;
            }
        }


        /**
         * Register the settings.
         *
         */
        function register_Settings() {

            // Load options from WPDB, default values to the array at the top of this class.
            //
            // loading the options this way (2 steps with array_merge) ensures that the serialized data
            // from the database does not obliterate defaults when new options are added.  Those new options
            // would be blank in the database.   Using get_option('plugintel_options',$this->options) does
            // not have the desired effect with serialized data as it is loaded as a single blob, thus the
            // original parameter will have data and the second parameter is ignored.
            //
            $this->addon->set_options();
            register_setting( 'wpdevkit_options' , 'wpdevkit_options' , array( $this , 'validate_Options' ) );

            // Main Settings Section
            //
            add_settings_section( 'wpdevkit_main' , __('Settings','csa-wpdevkit') , array( $this , 'render_MainSettings' ) , 'wpdevkit'        );

            // Show all options from the option meta array.
            //
            foreach ($this->optionMeta as $option) {
                add_settings_field(
                        $option->slug ,
                        $option->label,
                        array($this,'render_Input')    ,'wpdevkit', 'wpdevkit_main',
                        array(
                            'id'            => $option->slug,
                            'description'   => $option->description,
                            'type'          => $option->type,
                            )
                        );
            }
        }


        /**
         * Render the pre for a settings field.
         *
         * @param mixed[] $args
         */
        function render_pre_input($args) {
            if (!empty($args['description'])) {
                print "<p class='description'>{$args['description']}</p>";
            }
            print "<pre ".
                "id='wpdevkit_options[{$args['id']}]' ".
                "name='wpdevkit_options[{$args['id']}]' ".
                ">";

            foreach ( (array) $this->addon->options[$args['id']] as $request_data) {
                print $request_data . "\n";
            }
            print '</pre>';
        }

        /**
         * Render the read only output for a settings field.
         *
         * @param mixed[] $args
         */
        function render_read_only($args) {
            print "<p ".
                "id='wpdevkit_options[{$args['id']}]' ".
                "name='wpdevkit_options[{$args['id']}]' ".
                ">" .
                 $this->addon->options[$args['id']]  .
                '</p>'
            ;
            if (!empty($args['description'])) {
                print "<p class='description'>{$args['description']}</p>";
            }
        }

        /**
         * Render the slider input.
         *
         * @param mixed[] $args
         */
        function render_SliderInput($args) {
            $checked = (($this->addon->options[$args['id']]==1)?'checked':'');
            $onClick = 'onClick="'.
                "jQuery('input[id={$args['id']}]').prop('checked',".
                    "!jQuery('input[id={$args['id']}]').prop('checked')" .
                    ");".
                '" ';

            echo
                "<input type='checkbox' id='{$args['id']}' name='wpdevkit_options[{$args['id']}]' value='1' style='display:none;' $checked>" .
                "<div id='{$args['id']}_div' class='onoffswitch-block'>" .
                "<div class='onoffswitch'>" .
                "<input type='checkbox' name='onoffswitch' class='onoffswitch-checkbox' value='1' id='{$args['id']}-checkbox' $checked>" .
                "<label class='onoffswitch-label' for='{$args['id']}-checkbox'  $onClick>" .
                '<div class="onoffswitch-inner"></div>'.
                "<div class='onoffswitch-switch'></div>".
                '</label>'.
                '</div>' .
                '</div>'
                ;

            if (!empty($args['description'])) {
                print "<p class='description'>{$args['description']}</p>";
            }
        }

        /**
         * Render the text input for a settings field.
         *
         * @param mixed[] $args
         */
        function render_TextInput($args) {
            print "<input ".
                    "id='wpdevkit_options[{$args['id']}]' ".
                    "name='wpdevkit_options[{$args['id']}]' ".
                    "size='20' ".
                    "type='text' " .
                    "value='{$this->addon->options[$args['id']]}' ".
                   "/>"
                   ;
            if (!empty($args['description'])) {
                print "<p class='description'>{$args['description']}</p>";
            }
        }

        /**
         * Render the main settings panel inputs.
         */
        function render_MainSettings() {
            print '<p>'.
                  __('Use these settings tell the WP Dev Kit plugin where to find your product metadata.','csa-wpdevkit').
                 '</p>';
        }

        /**
         * Render the settings page.
         */
        function render_SettingsPage() {
            print
                '<div class="wrap">' .
                    '<h2>WP Dev Kit '.__('Settings','csa-wpdevkit').'</h2>'.
                    '<form method="post" action="options.php">'
                    ;
            settings_fields('wpdevkit_options');
            do_settings_sections('wpdevkit');
            submit_button();
            print
                    '</form>'.
                '</div>'
                ;
        }

        /**
         * Validate the options we get.
         *
         * @param array $optionsRcvd
         * @return array
         */
        function validate_Options($optionsRcvd) {
            if (!is_array($optionsRcvd)) { return; }

            $validOptions = array();
            foreach ($optionsRcvd as $optionName=>$optionValue) {

                // Option exists in our properties array, let it in.
                //
                if (isset($this->addon->options[$optionName])) {
                    $validOptions[$optionName]=$optionValue;
                }
            }

            // Check for empty checkboxes
            //
            foreach ($this->optionMeta as $option) {
                if (isset($validOptions[$option->slug])) { continue; }
                if (($option->type == 'checkbox') || ($option->type == 'slider')) {
                    $validOptions[$option->slug] = '0';
                }
            }

            return $validOptions;
        }
	}
}
