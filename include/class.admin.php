<?php
if (! class_exists('wpdkPlugin_Admin')) {

    /**
     * Holds the admin-only code.
     *
     * @package wpdkPlugin\Admin
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class wpdkPlugin_Admin {

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
         * The plugin settings page hook.
         *
         * @var string $menuHook
         */
        private $menuHook;

        //-------------------------------------
        // Methods
        //-------------------------------------
        
        /**
         * Admin interface constructor.
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

            // Plugin Menu Hook
            //
            $this->menuHook = add_options_page( 'WP Dev Kit' , 'WP Dev Kit' , 'manage_options' , 'wpdevkit' , array( $this->addon->AdminUI , 'render_SettingsPage' ) );

            // Admin Only Filters
            //
        }

        /**
         * Throw debugging output into Debug My Plugin (3rd party plugin)
         *
         * @param string $panel the panel name, default is 'main'
         * @param string $type the type 'pr' or 'msg'
         * @param string $hdr the message header
         * @param mixed $msg the variable to dump ('pr') or print ('msg')
         * @param string $file __FILE__ from calling location
         * @param string $line __LINE__ from calling location
         * @return null
         */
        function render_ToDebugBar($panel='main', $type='msg',$hdr='',$msg='',$file=null,$line=null) {
            if (!isset($GLOBALS['DebugMyPlugin'])) { return; }
            if (!isset($GLOBALS['DebugMyPlugin']->panels[$panel])) { return; }
            switch ($type) {
                case 'pr':
                    $GLOBALS['DebugMyPlugin']->panels[$panel]->addPR($hdr,$msg,$file,$line);
                    break;
                case 'msg':
                    $GLOBALS['DebugMyPlugin']->panels[$panel]->addMessage($hdr,$msg,$file,$line);
                    break;
                default:
                    break;
            }
        }
	}
}
