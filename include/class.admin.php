<?php
if (! class_exists('wpdkPlugin_Admin')) {

    /**
     * Holds the admin-only code.
     *
     * @property        wpdkPlugin_Admin_UI $UI
     * @property-read   string              $menuHook
     *
     * @package wpdkPlugin\Admin
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class wpdkPlugin_Admin extends WPDK_BaseClass_Object {
        private $UI;
        private $menuHook;

        /**
         * Admin interface constructor.
         */
        function __construct( $options = array() ) {
            parent::__construct( $options );

            $this->init_UI();

            $this->menuHook = add_options_page( 'WP Dev Kit' , 'WP Dev Kit' , 'manage_options' , 'wpdevkit' , array( $this->UI , 'render_SettingsPage' ) );
        }


        /**
         * Create and attach the admin UI object.
         */
        function init_UI() {
            if ( ! isset ( $this->UI  ) ) {
                require_once( 'class.admin.ui.php' );
                $this->UI = new wpdkPlugin_Admin_UI();
            }
        }

	}
}
