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
		$this->url              = 'https://www.payfast.co.za/onsite/process';
		$this->title            = $this->get_option( 'title' );
		$this->response_url	    = add_query_arg( 'wc-api', 'WC_Payfast_Onsite_Payment_Gateway', home_url( '/' ) );
		$this->description      = $this->get_option( 'description' );
		$this->enabled          = 'yes' === $this->get_option( 'enabled' ) ? 'yes' : 'no';

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'check_itn_response' ) );
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
		$this->log( PHP_EOL
			. '----------'
			. PHP_EOL . 'PayFast ITN call received'
			. PHP_EOL . '----------'
		);
		$this->log( 'Get posted data' );
		$this->log( 'PayFast Data: ' . print_r( $data, true ) );

		$payfast_error  = false;
		$payfast_done   = false;
		$debug_email    = $this->get_option( 'debug_email', get_option( 'admin_email' ) );
		$session_id     = $data['custom_str1'];
		$vendor_name    = get_bloginfo( 'name', 'display' );
		$vendor_url     = home_url( '/' );
		$order_id       = absint( $data['custom_str3'] );
		$order_key      = wc_clean( $session_id );
		$order          = wc_get_order( $order_id );
		$original_order = $order;

		if ( false === $data ) {
			$payfast_error  = true;
			$payfast_error_message = PF_ERR_BAD_ACCESS;
		}

		// Verify security signature
		if ( ! $payfast_error && ! $payfast_done ) {
			$this->log( 'Verify security signature' );
			$signature = md5( $this->_generate_parameter_string( $data, false, false ) ); // false not to sort data
			// If signature different, log for debugging
			if ( ! $this->validate_signature( $data, $signature ) ) {
				$payfast_error         = true;
				$payfast_error_message = PF_ERR_INVALID_SIGNATURE;
			}
		}

		// Verify source IP (If not in debug mode)
		if ( ! $payfast_error && ! $payfast_done
			&& $this->get_option( 'testmode' ) != 'yes' ) {
			$this->log( 'Verify source IP' );

			if ( ! $this->is_valid_ip( $_SERVER['REMOTE_ADDR'] ) ) {
				$payfast_error  = true;
				$payfast_error_message = PF_ERR_BAD_SOURCE_IP;
			}
		}

		// Verify data received
		if ( ! $payfast_error ) {
			$this->log( 'Verify data received' );
			$validation_data = $data;
			unset( $validation_data['signature'] );
			$has_valid_response_data = $this->validate_response_data( $validation_data );

			if ( ! $has_valid_response_data ) {
				$payfast_error = true;
				$payfast_error_message = PF_ERR_BAD_ACCESS;
			}
		}

		// Check data against internal order
		if ( ! $payfast_error && ! $payfast_done ) {
			$this->log( 'Check data against internal order' );

			// Check order amount
			if ( ! $this->amounts_equal( $data['amount_gross'], self::get_order_prop( $order, 'order_total' ) )
				 && ! $this->order_contains_pre_order( $order_id )
				 && ! $this->order_contains_subscription( $order_id ) ) {
				$payfast_error  = true;
				$payfast_error_message = PF_ERR_AMOUNT_MISMATCH;
			} elseif ( strcasecmp( $data['custom_str1'], self::get_order_prop( $order, 'order_key' ) ) != 0 ) {
				// Check session ID
				$payfast_error  = true;
				$payfast_error_message = PF_ERR_SESSIONID_MISMATCH;
			}
		}

		// alter order object to be the renewal order if
		// the ITN request comes as a result of a renewal submission request
		$description = json_decode( $data['item_description'] );

		if ( ! empty( $description->renewal_order_id ) ) {
			$order = wc_get_order( $description->renewal_order_id );
		}

		// Get internal order and verify it hasn't already been processed
		if ( ! $payfast_error && ! $payfast_done ) {
			$this->log_order_details( $order );

			// Check if order has already been processed
			if ( 'completed' === self::get_order_prop( $order, 'status' ) ) {
				$this->log( 'Order has already been processed' );
				$payfast_done = true;
			}
		}

		// If an error occurred
		if ( $payfast_error ) {
			$this->log( 'Error occurred: ' . $payfast_error_message );

			if ( $this->send_debug_email ) {
				$this->log( 'Sending email notification' );

				 // Send an email
				$subject = 'PayFast ITN error: ' . $payfast_error_message;
				$body =
					"Hi,\n\n" .
					"An invalid PayFast transaction on your website requires attention\n" .
					"------------------------------------------------------------\n" .
					'Site: ' . esc_html( $vendor_name ) . ' (' . esc_url( $vendor_url ) . ")\n" .
					'Remote IP Address: ' . $_SERVER['REMOTE_ADDR'] . "\n" .
					'Remote host name: ' . gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) . "\n" .
					'Purchase ID: ' . self::get_order_prop( $order, 'id' ) . "\n" .
					'User ID: ' . self::get_order_prop( $order, 'user_id' ) . "\n";
				if ( isset( $data['pf_payment_id'] ) ) {
					$body .= 'PayFast Transaction ID: ' . esc_html( $data['pf_payment_id'] ) . "\n";
				}
				if ( isset( $data['payment_status'] ) ) {
					$body .= 'PayFast Payment Status: ' . esc_html( $data['payment_status'] ) . "\n";
				}

				$body .= "\nError: " . $payfast_error_message . "\n";

				switch ( $payfast_error_message ) {
					case PF_ERR_AMOUNT_MISMATCH:
						$body .=
							'Value received : ' . esc_html( $data['amount_gross'] ) . "\n"
							. 'Value should be: ' . self::get_order_prop( $order, 'order_total' );
						break;

					case PF_ERR_ORDER_ID_MISMATCH:
						$body .=
							'Value received : ' . esc_html( $data['custom_str3'] ) . "\n"
							. 'Value should be: ' . self::get_order_prop( $order, 'id' );
						break;

					case PF_ERR_SESSIONID_MISMATCH:
						$body .=
							'Value received : ' . esc_html( $data['custom_str1'] ) . "\n"
							. 'Value should be: ' . self::get_order_prop( $order, 'id' );
						break;

					// For all other errors there is no need to add additional information
					default:
						break;
				}

				wp_mail( $debug_email, $subject, $body );
			} // End if().
		} elseif ( ! $payfast_done ) {

			$this->log( 'Check status and update order' );

			if ( self::get_order_prop( $original_order, 'order_key' ) !== $order_key ) {
				$this->log( 'Order key does not match' );
				exit;
			}

			$status = strtolower( $data['payment_status'] );

			$subscriptions = array();
			if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) && function_exists( 'wcs_get_subscriptions_for_order' ) ) {
				$subscriptions = array_merge(
					wcs_get_subscriptions_for_renewal_order( $order_id ),
					wcs_get_subscriptions_for_order( $order_id )
				);
			}

			if ( 'complete' !== $status && 'cancelled' !== $status ) {
				foreach ( $subscriptions as $subscription ) {
					$this->_set_renewal_flag( $subscription );
				}
			}

			if ( 'complete' === $status ) {
				$this->handle_itn_payment_complete( $data, $order, $subscriptions );
			} elseif ( 'failed' === $status ) {
				$this->handle_itn_payment_failed( $data, $order );
			} elseif ( 'pending' === $status ) {
				$this->handle_itn_payment_pending( $data, $order );
			} elseif ( 'cancelled' === $status ) {
				$this->handle_itn_payment_cancelled( $data, $order, $subscriptions );
			}
		} // End if().

		$this->log( PHP_EOL
			. '----------'
			. PHP_EOL . 'End ITN call'
			. PHP_EOL . '----------'
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

		$payfast_args_array = array();
		$sign_strings       = array();
		foreach ( $this->data_to_send as $key => $value ) {
			if ( $key !== 'source' ) {
				$sign_strings[] = esc_attr( $key ) . '=' . urlencode( str_replace( '&amp;', '&', trim( $value ) ) );
			}
			$payfast_args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}

		if ( ! empty( $this->pass_phrase ) ) {
			$payfast_args_array[] = '<input type="hidden" name="signature" value="' . md5( implode( '&', $sign_strings ) . '&passphrase=' . urlencode( $this->pass_phrase ) ) . '" />';
		} else {
			$payfast_args_array[] = '<input type="hidden" name="signature" value="' . md5( implode( '&', $sign_strings ) ) . '" />';
		}

		return '<form action="' . esc_url( $this->url ) . '" method="post" id="payfast_payment_form">
				' . implode( '', $payfast_args_array ) . '
				<input type="submit" class="button-alt" id="submit_payfast_payment_form" value="' . __( 'Pay via PayFast', 'woo-payfast-onsite-payment' ) . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-gateway-payfast' ) . '</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{
								message: "' . __( 'Thank you for your order. We are now redirecting you to PayFast to make payment.', 'woo-payfast-onsite-payment' ) . '",
								overlayCSS:
								{
									background: "#fff",
									opacity: 0.6
								},
								css: {
									padding:        20,
									textAlign:      "center",
									color:          "#555",
									border:         "3px solid #aaa",
									backgroundColor:"#fff",
									cursor:         "wait"
								}
							});
						jQuery( "#submit_payfast_payment_form" ).click();
					});
				</script>
			</form>';
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
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
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

}