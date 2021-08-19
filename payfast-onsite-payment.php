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
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo-payfast_onsite_payment' ) . '">' . __( 'Configure', 'woo-payfast-onsite-payment' ) . '</a>'
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
 * @class 		WC_Payfast_Onsite_Payment_Gateway
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Jono
 */
add_action( 'plugins_loaded', 'wc_payfast_onsite_payment_gateway_init', 11 );




function wc_payfast_onsite_payment_gateway_init() {

	class WC_Payfast_Onsite_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'woo-payfast_onsite_payment';
            $this->icon                = apply_filters( 'woocommerce_pop_icon', plugins_url('/assets/payfast_logo_colour.svg', __FILE__ ) );
			$this->has_fields         = false;
			$this->method_title       = __( 'Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' );
			$this->method_description = __( 'Payfast Onsite payment for WooCommerce allows you to make payment via  Credit & Cheque Cards, MasterCard & Visa Card.
            ', 'woo-payfast-onsite-payment' );
            $this->method_merchant_id  = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');
            $this->method_merchant_key = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');
            $this->method_phrase       = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');
            $this->available_countries  = array( 'ZA' );
            $this->available_currencies = (array)apply_filters('woo_payfast_onsite_payment_available_currencies', array( 'ZAR' ) );


		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
            
             // Setup default merchant data.
             $this->merchant_id      = $this->get_option( 'merchant_id' );
             $this->merchant_key     = $this->get_option( 'merchant_key' );
             $this->pass_phrase      = $this->get_option( 'pass_phrase' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}
	
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'woo_payfast_onsite_payment_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woo-payfast-onsite-payment' ),
					'type'    => 'checkbox',
					'label'   => __( 'Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'woo-payfast-onsite-payment' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'woo-payfast-onsite-payment' ),
					'default'     => __( 'Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'woo-payfast-onsite-payment' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woo-payfast-onsite-payment' ),
					'default'     => __( 'Please remit your payment to the shop to allow for the delivery to be made', 'woo-payfast-onsite-payment' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'woo-payfast-onsite-payment' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woo-payfast-onsite-payment' ),
					'default'     => '',
					'desc_tip'    => true,
				),
                'merchant_id' => array(
                    'title'       => __( 'Merchant ID', 'woo-payfast-onsite-payment' ),
                    'type'        => 'text',
                    'description' => __( 'This is the merchant ID, received from PayFast.', 'woo-payfast-onsite-payment' ),
				    'default'     => '',
                    'desc_tip' => true,
                ),
                'merchant_key' => array(
                    'title'       => __( 'Merchant Key', 'woo-payfast-onsite-payment' ),
                    'type'        => 'text',
                    'description' => __( 'This is the merchant key, received from PayFast.', 'woo-payfast-onsite-payment' ),
                    'default'     => '',
                    'desc_tip' => true,
                ),
                'pass_phrase' => array(
                    'title'       => __( 'Passphrase', 'woo-payfast-onsite-payment' ),
                    'type'        => 'text',
                    'description' => __( '* Required. Needed to ensure the data passed through is secure.', 'woo-payfast-onsite-payment' ),
                    'default'     => '',
                    'desc_tip' => true,
                ),
			) );
		}
	
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}

        
	
  } 
}