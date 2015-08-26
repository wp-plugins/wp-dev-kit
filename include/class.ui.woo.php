<?php
/**
 * Holds the Woocommerce Interface code.
 *
 * Text Domain: csa-wpdevkit
 *
 * @property        boolean     $woo_active                 True if any WooCommerce version is active.
 * @property        boolean     $subscriptions_active       True if any WooCommerce Subscriptions version is active.
 *
 * @package wpdkPlugin\UI\Woo
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2015 Charleston Software Associates, LLC
 */
class wpdkPlugin_UI_Woo extends WPDK_BaseClass_Object {
    public  $woo_active;
    public  $subscriptions_active;

    /**
     * Make Me.
     *
     * @param array $options
     */
    function __construct( $options = array() ) {
        parent::__construct( $options );
        $this->woo_active = class_exists( 'WooCommerce' );
        $this->subscriptions_active = class_exists( 'WC_Subscriptions' );
    }

    /**
     * Display the subscription info block.
     */
    function get_subscription_id() {
        if ( ! $this->subscriptions_active )                { return ''; }
        if ( ! class_exists('WC_Subscriptions_Manager') )   { return ''; }
        if ( ! WC_Subscriptions_Manager::user_has_subscription( $this->addon->UI->current_uid , $this->addon->options['subscription_product_id'] , 'active' ) ) {
            return '';
        }

        return maybe_serialize(WC_Subscriptions_Manager::get_users_subscriptions( $this->addon->UI->current_uid ) );

    }


}
