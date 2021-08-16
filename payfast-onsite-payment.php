<?php

/**
 * Plugin Name: Payfast Onsite payment for Woocommerce
 * Plugin URI: hhttps://jonomuamba.co.za/
 * Author Name: Jono Muamba
 * Author URI: https://jonomuamba.co.za/
 * Description: Payfast Onsite payment for WooCommerce allows you to make payment via  Credit & Cheque Cards, MasterCard & Visa Card.

 * Version: 1.0.0
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: woo-payfast-onsite-payment
*/ 

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'payfast_onsite_payment_init', 11 );

function payfast_onsite_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
        class WC_Payfast_Onsite_Payment_Gateway extends WC_Payment_Gateway {
            public function __construct() {
                $this->id                  = 'payfast_onsite_payment';
                $this->icon                = apply_filters( 'woocommerce_pop_icon', plugins_url('/assets/payfast_logo_colour.svg', __FILE__ ) );
                $this->has_fields          = false;
                $this->method_title        = __( 'Payfast Onsite payment', 'woo-payfast-onsite-payment');
                $this->method_description  = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');
                $this->method_merchant_id  = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');
                $this->method_merchant_key = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');
                $this->method_phrase       = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment');

                $this->title        = $this->get_option( 'title' );
                $this->description  = $this->get_option( 'description' );
                $this->merchant_id  = $this->get_option( 'merchant_id' );
                $this->merchant_key = $this->get_option( 'merchant_key' );
                $this->pass_phrase  = $this->get_option( 'pass_phrase' );

                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thank_you_' . $this->id, array( $this, 'thank_you_page' ) );
            }

            public function init_form_fields() {
                $this->form_fields = apply_filters( 'pop_fields', array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'woo-payfast-onsite-payment'),
                        'type' => 'checkbox',
                        'label' => __( 'Enable or Disable Payfast Onsite payments', 'woo-payfast-onsite-payment'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'woo-payfast-onsite-payment'),
                        'type' => 'text',
                        'description' => __( 'Add a new title for the Payfast Onsite payment that customers will see when they are in the checkout page.', 'woo-payfast-onsite-payment'),
                        'default' => __( 'Payfast Onsite payment', 'woo-payfast-onsite-payment'),
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __( 'Description', 'woo-payfast-onsite-payment'),
                        'type' => 'textarea',
                        'description' => __( 'Add a new title for the Payfast Onsite payment that customers will see when they are in the checkout page.', 'woo-payfast-onsite-payment'),
                        'default' => __( 'Please remit your payment to the shop to allow for the delivery to be made', 'woo-payfast-onsite-payment'),
                        'desc_tip' => true,
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
                ));
            }

            public function process_payments( $order_id ) {
                
                $order = wc_get_order( $order_id );

                $order->update_status( 'on-hold',  __( 'Awaiting Payfast Onsite payment', 'woo-payfast-onsite-payment') );

                $order->reduce_order_stock();

                WC()->cart->empty_cart();

                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );
            }

            public function thank_you_page(){
                if( $this->description ){
                    echo wpautop( $this->description );
                }
            }
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_to_woo_payfast_onsite_payment_gateway');

function add_to_woo_payfast_onsite_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Payfast_Onsite_Payment_Gateway';
    return $gateways;
}
