<?php
/**
 * Interact with the WPDK data tables.
 *
 * @property        wpdb    $wpdb
 * @property-read   string  $update_history_table;
 *
 * @package wpdkPlugin\Database
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2015 Charleston Software Associates, LLC
 */
class wpdkPlugin_Database extends WPDK_BaseClass_Object {
    public $wpdb;
    private $update_history_table;

    /**
     * Create and attach the admin UI object.
     */
    function __construct( $options = array() ) {
        parent::__construct( $options ) ;
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->update_history_table = $wpdb->prefix . 'wpdk_update_history';
    }

    /**
     * Add an incoming request to the history table.
     */
    public function add_request_to_history() {
        $this->wpdb->insert(
            $this->update_history_table ,
            array(
                'action'            => $this->addon->UpdateEngine->request['fetch'],
                'current_version'   => $this->addon->UpdateEngine->request['current_version'],
                'ip_address'        => $this->addon->UpdateEngine->get_request_ip_address(),
                'new_version'       => $this->addon->UpdateEngine->current_plugin_meta[$this->addon->current_target]['new_version'],
                'sid'               => $this->addon->UpdateEngine->request['sid'],
                'site_url'          => $this->addon->UpdateEngine->request['surl'],
                'slug'              => $this->addon->current_plugin['slug'],
                'plugin_meta'       => maybe_serialize( $this->addon->UpdateEngine->current_plugin_meta ),
                'request'           => maybe_serialize( $_REQUEST ),
                'server'            => maybe_serialize( $_SERVER ),
                'target'            => $this->addon->current_target,
                'uid'               => $this->addon->UpdateEngine->request['uid'],
            )
        );
    }


}

