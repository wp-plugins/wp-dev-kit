<?php
if (! class_exists('wpdkPlugin_Activation')) {

    /**
     * Holds the activation code.
     *
     * @package wpdkPlugin\Activation
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2014 Charleston Software Associates, LLC
     */
    class wpdkPlugin_Activation extends WPDK_BaseClass_Object {

        /**
         * @param array $options
         */
        function __construct( $options = array() ) {
            parent::__construct( $options );
            $this->update();
        }

        /**
         * Create the update history table structure.
         */
        private function create_update_history_table() {
            $field_sql = '';

            // Version 1.0 Database Structure
            //
            if ( version_compare( $this->addon->options['installed_version'], '1.0' , '<' ) ) {
                $field_sql =
                    "id bigint(20) unsigned NOT NULL auto_increment, "  .
                    "slug varchar(255) NOT NULL, "                      .
                    "current_version varchar(12) NOT NULL, "            .
                    "new_version varchar(12) NOT NULL, "                .
                    "action varchar(12) NULL, "                         .
                    "target varchar(12) NULL, "                         .
                    "site_url varchar(255) NULL, "                      .
                    "uid varchar(255) NULL, "                           .
                    "sid varchar(255) NULL, "                           .
                    "ip_address varchar(45) NULL, "                     .
                    "plugin_meta longtext NULL, "                       .
                    "server longtext NULL, "                            .
                    "request longtext NULL, "                           .
                    "lastupdated timestamp NOT NULL default CURRENT_TIMESTAMP, " .
                    "PRIMARY KEY  (id)"
                    ;
            }

            if ( ! empty( $field_sql ) ) {
                $this->update_history_table( $field_sql );
            }
        }

        /**
         * Update this plugin.
         */
        private function update() {
            $this->create_update_history_table();
            $this->addon->options['installed_version'] = WPDK__VERSION;
            update_option( 'wpdevkit_options' , $this->addon->options );
        }

        /**
         * Update the history table structure.
         *
         * @param string $field_sql
         */
        private function update_history_table( $field_sql ) {
            global $wpdb;

            $charset_collate = '';
            if (!empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if (!empty($wpdb->collate)) {
                $charset_collate .= " COLLATE $wpdb->collate";
            }

            $sql = "CREATE TABLE {$wpdb->prefix}wpdk_update_history ({$field_sql}) {$charset_collate}";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $were_showing_errors = $wpdb->show_errors;
            $wpdb->hide_errors();
            dbDelta($sql);
            global $EZSQL_ERROR;
            $EZSQL_ERROR=array();
            if ( $were_showing_errors ) {
                $wpdb->show_errors();
            }
        }
	}
}
