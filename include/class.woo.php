<?php
/**
 * Holds the Woocommerce Interface code.
 *
 * Text Domain: csa-wpdevkit
 *
 * @property        boolean     $woo_active                 True if any WooCommerce version is active.
 * @property        boolean     $subscriptions_active       True if any WooCommerce Subscriptions version is active.
 *
 * Text Domain: csa-wpdevkit
 *
 * @package wpdkPlugin\Woo
 * @author Lance Cleveland <lance@charlestonsw.com>
 * @copyright 2015 Charleston Software Associates, LLC
 */
class wpdkPlugin_Woo extends WPDK_BaseClass_Object {
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
        if ( ! $this->subscriptions_active )                { return new WP_Error( 'no_woo' , __('Woo Subscriptions not active.'        , 'csa-wpdevkit') ); }

        if ( ! class_exists('WC_Subscriptions_Manager') )   { return new WP_Error( 'no_woo' , __('Woo Subscription Manager is missing.' , 'csa-wpdevkit') ); }

        if ( ! WC_Subscriptions_Manager::user_has_subscription( $this->addon->UI->current_uid , $this->addon->options['subscription_product_id'] , 'active' ) ) {
            new WP_Error( 'no_woo' , sprintf( __('Not active subscription found for product id %d.', 'csa-wpdevkit') , $this->addon->options['subscription_product_id'] ) );
        }

        $subscriptions = WC_Subscriptions_Manager::get_users_subscriptions( $this->addon->UI->current_uid );
        foreach ( $subscriptions as $subscription ) {
            if ( $subscription['status'] !== 'active' ) { continue; }
            return WC_Subscriptions_Manager::get_subscription_key( $subscription['order_id'] , $subscription['product_id'] );

        }

        return   new WP_Error( 'no_woo' , __('None of your subscription orders are active.'        , 'csa-wpdevkit') );
    }

    /**
     * Validate the subscription.  Returns true if the passed in SID and UID match a valid subscription.
     *
     * @param string $uid
     * @param string $sid
     *
     * @return bool
     */
    function validate_subscription( $uid , $sid ) {

        $subscription = WC_Subscriptions_Manager::get_subscription( $sid );
        if ( ! is_array( $subscription )            ) { return false; }
        if ( ! isset( $subscription['status'    ] ) ) { return false; }
        if ( ! isset( $subscription['product_id'] ) ) { return false; }

        return WC_Subscriptions_Manager::user_has_subscription( $uid , $subscription['product_id'] , 'active' );
    }

}
