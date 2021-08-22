<?php
/**
 * Plugin Name: Woo Payfast Onsite payment for Woocommerce
 * Plugin URI: hhttps://jonomuamba.co.za/
 * Author Name: Jono Muamba
 * Author URI: https://jonomuamba.co.za/
 * Description: Payfast Onsite payment for WooCommerce allows you to make payment via  Credit & Cheque Cards, MasterCard & Visa Card.

 * Version: 1.0.0
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: woo-payfast-onsite-payment
 */

defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Woo Payfast Onsite Payment
 */
function woo_payfast_onsite_payment_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Payfast_Onsite_Payment_Gateway';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'woo_payfast_onsite_payment_add_to_gateways' );


/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_offline_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo-payfast_onsite_payment' ) . '">' . __( 'Configure', 'woo-payfast-onsite-payment' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_offline_gateway_plugin_links' );


/**
 * Woo Payfast Onsite Payment Gateway
 *
 * Provides an Woo Payfast Onsite Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Payfast_Onsite_Payment_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Jono
 */
add_action( 'plugins_loaded', 'wc_payfast_onsite_payment_gateway_init', 11 );




function wc_payfast_onsite_payment_gateway_init() {
	require_once plugin_basename( 'includes/class-woocommerce-payfast-onsite-payment-gateway.php' );
	require_once plugin_basename( 'includes/class-woocommerce-payfast-onsite-payment-gateway.php' );
	load_plugin_textdomain( 'woo-payfast-onsite-payment', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
}
