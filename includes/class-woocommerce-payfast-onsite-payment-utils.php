<?php

define( 'PAYFAST_ENDPOINT_URL', 'https://www.payfast.co.za/onsite/process' );


class WC_Payfast_OnSite_Payment_Utils {

	/**
	 * Generate a hash to be used for the transaction.
	 * @param array $data
	 * @param string|null $passPhrase
	 * 
	 * @return string
	 */
	public static function generateSignature( $data, $passPhrase = null ) {
		// Create parameter string
		$pfOutput = '';
		foreach ( $data as $key => $val ) {
			if ( $val !== '' && $key !== 'signature' ) {
				$pfOutput .= $key . '=' . urlencode( trim( $val ) ) . '&';
			}
		}
		// Remove last ampersand
		$getString = substr( $pfOutput, 0, -1 );
		if ( $passPhrase !== null ) {
			$getString .= '&passphrase=' . urlencode( trim( $passPhrase ) );
		}
		return md5( $getString );
	}

	/**
	 * Convert data array to query strings.
	 * @param array $dataArray
	 * @return string
	 */
	public static function dataToString( $dataArray ) {
		// Create parameter string
		$pfOutput = '';
		foreach ( $dataArray as $key => $val ) {
			if ( $val !== '' ) {
				$pfOutput .= $key . '=' . urlencode( trim( $val ) ) . '&';
			}
		}
		// Remove last ampersand
		return substr( $pfOutput, 0, -1 );
	}

	/**
	 * Generates a payment ID.
	 * @param string $pfParamString
	 * @param string|null $pfProxy
	 * 
	 * @return string
	 */
	public static function generatePaymentIdentifier( $pfParamString, $pfProxy = null ) {
		// Use cURL (if available)
		if ( in_array( 'curl', get_loaded_extensions(), true ) ) {
			// Create default cURL object
			$ch = curl_init();

			// Set cURL options - Use curl_setopt for greater PHP compatibility
			// Base settings
			curl_setopt( $ch, CURLOPT_USERAGENT, null );  // Set user agent
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );      // Return output as string rather than outputting it
			curl_setopt( $ch, CURLOPT_HEADER, false );             // Don't include header in output
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );

			// Standard settings
			curl_setopt( $ch, CURLOPT_URL, PAYFAST_ENDPOINT_URL );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $pfParamString );

			if ( ! empty( $pfProxy ) ) {
				curl_setopt( $ch, CURLOPT_PROXY, $pfProxy );
			}

			// Execute cURL
			$response = curl_exec( $ch );
			curl_close( $ch );

			// echo $response;
			$rsp = json_decode( $response, true );
			if ( isset( $rsp['uuid'] ) ) {
				return $rsp['uuid'];
			}
		}
		return null;
	}

	/**
	 * Validates signature.
	 * @param array $data
	 * @param string $signature
	 * 
	 * @return bool
	 */
	public static function validate_signature( $data, $signature ) {
	    $result = $data['signature'] === $signature;
	    return $result;
	}

}
