<?php

class WC_Payfast_Onsite_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		$this->id                   = 'woo-payfast_onsite_payment';
		$this->icon                 = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/assets/payfast_logo_colour.svg';
		$this->has_fields           = false;
		$this->method_title         = __( 'Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' );
		$this->method_description   = __(
			'Payfast Onsite payment for WooCommerce allows you to make payment via  Credit & Cheque Cards, MasterCard & Visa Card.
        ',
			'woo-payfast-onsite-payment'
		);
		$this->method_merchant_id   = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment' );
		$this->method_merchant_key  = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment' );
		$this->method_phrase        = __( 'Payfast Onsite payment.', 'woo-payfast-onsite-payment' );
		$this->available_countries  = array( 'ZA' );
		$this->available_currencies = (array) apply_filters( 'woo_payfast_onsite_payment_available_currencies', array( 'ZAR' ) );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Setup default merchant data.
		$this->merchant_id  = $this->get_option( 'merchant_id' );
		$this->merchant_key = $this->get_option( 'merchant_key' );
		$this->pass_phrase  = $this->get_option( 'pass_phrase' );

		// Setup default merchant data.
		$this->url          = 'https://www.payfast.co.za/onsite/process';
		$this->title        = $this->get_option( 'title' );
		$this->response_url = add_query_arg( 'wc-api', 'WC_Payfast_Onsite_Payment_Gateway', home_url( '/' ) );
		$this->description  = $this->get_option( 'description' );
		$this->enabled      = 'yes' === $this->get_option( 'enabled' ) ? 'yes' : 'no';

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_wc_payfast_onsite_payment_gateway', array( $this, 'check_itn_response' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		// Add fees to order
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_fee' ) );
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_net' ), 20 );
	}


	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {

		$this->form_fields = apply_filters(
			'woo_payfast_onsite_payment_form_fields',
			array(

				'enabled'      => array(
					'title'   => __( 'Enable/Disable', 'woo-payfast-onsite-payment' ),
					'type'    => 'checkbox',
					'label'   => __( 'Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' ),
					'default' => 'yes',
				),

				'title'        => array(
					'title'       => __( 'Title', 'woo-payfast-onsite-payment' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'woo-payfast-onsite-payment' ),
					'default'     => __( 'Woo Payfast Onsite Payment', 'woo-payfast-onsite-payment' ),
					'desc_tip'    => true,
				),

				'description'  => array(
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
				'merchant_id'  => array(
					'title'       => __( 'Merchant ID', 'woo-payfast-onsite-payment' ),
					'type'        => 'text',
					'description' => __( 'This is the merchant ID, received from PayFast.', 'woo-payfast-onsite-payment' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'merchant_key' => array(
					'title'       => __( 'Merchant Key', 'woo-payfast-onsite-payment' ),
					'type'        => 'text',
					'description' => __( 'This is the merchant key, received from PayFast.', 'woo-payfast-onsite-payment' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'pass_phrase'  => array(
					'title'       => __( 'Passphrase', 'woo-payfast-onsite-payment' ),
					'type'        => 'text',
					'description' => __( '* Required. Needed to ensure the data passed through is secure.', 'woo-payfast-onsite-payment' ),
					'default'     => '',
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * Generate the PayFast button link.
	 *
	 * @since 1.0.0
	 */
	public function generate_payfast_form( $order_id ) {

		$order = wc_get_order( $order_id );

		// Construct variables for post
		$this->data_to_send = array(
			// Merchant details
			'merchant_id'      => $this->merchant_id,
			'merchant_key'     => $this->merchant_key,
			'return_url'       => $this->get_return_url( $order ),
			'cancel_url'       => $order->get_cancel_order_url(),
			'notify_url'       => $this->response_url,

			// Billing details
			'name_first'       => self::get_order_prop( $order, 'billing_first_name' ),
			'name_last'        => self::get_order_prop( $order, 'billing_last_name' ),
			'email_address'    => self::get_order_prop( $order, 'billing_email' ),

			// Item details
			'm_payment_id'     => ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce-gateway-payfast' ) ),
			'amount'           => $order->get_total(),
			'item_name'        => get_bloginfo( 'name' ) . ' - ' . $order->get_order_number(),
			/* translators: 1: blog info name */
			'item_description' => sprintf( __( 'New order from %s', 'woocommerce-gateway-payfast' ), get_bloginfo( 'name' ) ),

			// Custom strings
			'custom_str1'      => self::get_order_prop( $order, 'order_key' ),
			'custom_str2'      => 'WooCommerce/' . WC_VERSION . '; ' . get_site_url(),
			'custom_str3'      => self::get_order_prop( $order, 'id' ),
			'source'           => 'WooCommerce-Free-Plugin',
		);

		$this->data_to_send['signature'] = WC_Payfast_OnSite_Payment_Utils::generateSignature( $this->data_to_send, $this->pass_phrase );

		$payfast_param_string = WC_Payfast_OnSite_Payment_Utils::dataToString( $this->data_to_send );

		$identifier = WC_Payfast_OnSite_Payment_Utils::generatePaymentIdentifier( $payfast_param_string );

		if ( $identifier === null ) {
			 wc_add_notice( 'Error loading the payment form.', 'error' );
			 return;
		}

		// Launch modal
		return '<script type="text/javascript">

		window.payfast_do_onsite_payment({
			"uuid":"' . $identifier . '",
			"return_url":"' . $this->data_to_send['return_url'] . '&payment-successful=1",
			"cancel_url":"' . $this->data_to_send['cancel_url'] . '",
			"notify_url":"' . $this->data_to_send['notify_url'] . '"
		});

		</script>';

	}

	private function _process_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->payment_complete();
		}
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page( $order_id ) {

		if ( $this->instructions ) {
			echo wpautop( wptexturize( $this->instructions ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order
	 * @param bool     $sent_to_admin
	 * @param bool     $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

	/**
	 * Reciept page.
	 *
	 * Display text and a button to direct the user to PayFast.
	 *
	 * @since 1.0.0
	 */
	public function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with PayFast.', 'woo-payfast-onsite-payment' ) . '</p>';
		echo $this->generate_payfast_form( $order );
	}

	/**
	 * Check PayFast ITN response.
	 *
	 * @since 1.0.0
	 */
	public function check_itn_response() {
		$this->handle_itn_request( stripslashes_deep( $_POST ) );

		// Notify PayFast that information has been received
		header( 'HTTP/1.0 200 OK' );
		flush();
	}

	/**
	 * Check PayFast ITN validity.
	 *
	 * @param array $data
	 * @since 1.0.0
	 */
	public function handle_itn_request( $data ) {

		$order_id       = absint( $data['custom_str3'] );
		$order          = wc_get_order( $order_id );

		if ( false === $data ) {
			return false;
		}

		if ( floatval( $data['amount_gross'] ) != floatval( $order->get_total() ) ) {
			return false;
		}

		$signature = md5( $this->_generate_parameter_string( $data, false, false ) ); // false not to sort data

		// If signature different, return false
		if ( ! WC_Payfast_OnSite_Payment_Utils::validate_signature( $data, $signature ) ) {
			return false;
		}

		$status = strtolower( $data['payment_status'] );

		if ( 'complete' === $status ) {
			$this->_process_order( $order_id );
		}

	}


	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {

		if ( $this->order_contains_pre_order( $order_id )
			&& $this->order_requires_payment_tokenization( $order_id ) ) {
				throw new Exception( 'PayFast Onsite does not support transactions without any upfront costs or fees. Please select another gateway' );
		}

		$order = wc_get_order( $order_id );
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Displays the amount_fee as returned by payfast.
	 *
	 * @param int $order_id The ID of the order.
	 */
	public function display_order_fee( $order_id ) {

		$order = wc_get_order( $order_id );
		$fee   = get_post_meta( self::get_order_prop( $order, 'id' ), 'payfast_amount_fee', true );

		if ( ! $fee ) {
			return;
		}
		?>

		<tr>
			<td class="label payfast-fee">
				<?php echo wc_help_tip( __( 'This represents the fee Payfast collects for the transaction.', 'woocommerce-gateway-payfast' ) ); ?>
				<?php esc_html_e( 'Payfast Fee:', 'woocommerce-gateway-payfast' ); ?>
			</td>
			<td width="1%"></td>
			<td class="total">
				<?php echo wc_price( $fee, array( 'decimals' => 2 ) ); ?>
			</td>
		</tr>

		<?php
	}

	/**
	 * Displays the amount_net as returned by payfast.
	 *
	 * @param int $order_id The ID of the order.
	 */
	public function display_order_net( $order_id ) {

		$order = wc_get_order( $order_id );
		$net   = get_post_meta( self::get_order_prop( $order, 'id' ), 'payfast_amount_net', true );

		if ( ! $net ) {
			return;
		}

		?>

		<tr>
			<td class="label payfast-net">
				<?php echo wc_help_tip( __( 'This represents the net total that was credited to your Payfast account.', 'woocommerce-gateway-payfast' ) ); ?>
				<?php esc_html_e( 'Amount Net:', 'woocommerce-gateway-payfast' ); ?>
			</td>
			<td width="1%"></td>
			<td class="total">
				<?php echo wc_price( $net, array( 'decimals' => 2 ) ); ?>
			</td>
		</tr>

		<?php
	}


	/**
	 * Get order property with compatibility check on order getter introduced
	 * in WC 3.0.
	 *
	 * @since 1.4.1
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $prop  Property name.
	 *
	 * @return mixed Property value
	 */
	public static function get_order_prop( $order, $prop ) {
		switch ( $prop ) {
			case 'order_total':
				$getter = array( $order, 'get_total' );
				break;
			default:
				$getter = array( $order, 'get_' . $prop );
				break;
		}

		return is_callable( $getter ) ? call_user_func( $getter ) : $order->{ $prop };
	}

	/**
	 * @param string $order_id
	 * @return bool
	 */
	public function order_contains_pre_order( $order_id ) {
		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			return WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
		}
		return false;
	}

	/**
	 * @param string $order_id
	 *
	 * @return bool
	 */
	public function order_requires_payment_tokenization( $order_id ) {
		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			return WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id );
		}
		return false;
	}

	/**
	 * @since 1.0.0 introduced.
	 * @param      $api_data
	 * @param bool $sort_data_before_merge? default true.
	 * @param bool $skip_empty_values Should key value pairs be ignored when generating signature?  Default true.
	 *
	 * @return string
	 */
	protected function _generate_parameter_string( $api_data, $sort_data_before_merge = true, $skip_empty_values = true ) {

		// if sorting is required the passphrase should be added in before sort.
		if ( ! empty( $this->pass_phrase ) && $sort_data_before_merge ) {
			$api_data['passphrase'] = $this->pass_phrase;
		}

		if ( $sort_data_before_merge ) {
			ksort( $api_data );
		}

		// concatenate the array key value pairs.
		$parameter_string = '';
		foreach ( $api_data as $key => $val ) {

			if ( $skip_empty_values && empty( $val ) ) {
				continue;
			}

			if ( 'signature' !== $key ) {
				$val = urlencode( $val );
				$parameter_string .= "$key=$val&";
			}
		}
		// when not sorting passphrase should be added to the end before md5
		if ( $sort_data_before_merge ) {
			$parameter_string = rtrim( $parameter_string, '&' );
		} elseif ( ! empty( $this->pass_phrase ) ) {
			$parameter_string .= 'passphrase=' . urlencode( $this->pass_phrase );
		} else {
			$parameter_string = rtrim( $parameter_string, '&' );
		}

		return $parameter_string;
	}


}
