<?php

define( 'PAYFAST_ENDPOINT_URL', 'https://www.payfast.co.za/onsite/process' );


class WC_Payfast_OnSite_Payment_Utils {

	public static function generateSignature( $data, $passPhrase = null ) {
		// Create parameter string
		$pfOutput = '';
		foreach ( $data as $key => $val ) {
			if ( $val !== '' ) {
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
			if ( $rsp['uuid'] ) {
				return $rsp['uuid'];
			}
		}
		return null;
	}

}
