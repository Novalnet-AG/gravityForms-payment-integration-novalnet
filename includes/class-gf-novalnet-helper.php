<?php
/**
 * Novalnet Helper Class
 *
 * @author   Novalnet AG
 * @category Class
 * @package  gravityforms-novalnet
 * @version  2.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GF_Novalnet_Helper
 */
class GF_Novalnet_Helper {

	/**
	 * Parameters to be encoded.
	 *
	 * @var array
	 */
	static public $secure_parameters = array(
		'auth_code',
		'product',
		'tariff',
		'amount',
		'test_mode',
	);

	/**
	 * Throw exception error for database handling
	 *
	 * @since 2.0.0
	 * @param string  $query        The handled query.
	 * @param boolean $return_query The value of the handled query.
	 *
	 * @return boolean|string
	 *
	 * @throws Exception For last error.
	 */
	public static function query_handling( $query, $return_query = false ) {
		global $wpdb;

		// Checking for query error.
		if ( $wpdb->last_error ) {
			throw new Exception( $wpdb->last_error );
		}
		return $return_query ? $query : true;
	}

	/**
	 * Handles the error while exception occurs.
	 *
	 * @since 2.0.0
	 * @param string  $query        The processed query.
	 * @param boolean $return_query The value of the processed query.
	 *
	 * @return boolean
	 */
	public static function query_process( $query, $return_query = true ) {
		$query_return = '';
		try {

			// DB error handling.
			$query_return = self::query_handling( $query, $return_query );

		} catch ( Exception $e ) {
			GFCommon::log_error( 'SQL error occured: ' . __CLASS__ . '::' . __FUNCTION__ . $e->getMessage() );
		}
		return ( $return_query ) ? $query_return : true;
	}

	/**
	 * Validation for digits.
	 *
	 * @since 2.0.0
	 * @param string $input The input value.
	 *
	 * @return boolean
	 */
	public static function is_valid_digit( $input ) {
		return ( preg_match( '/^[0-9]+$/', $input ) ) ? $input : false;
	}

	/**
	 * Validation for string.
	 *
	 * @since 2.0.0
	 * @param string $input The input value.
	 *
	 * @return boolean
	 */
	public static function is_valid_string( $input ) {
		return ( preg_match( '/^[a-z0-9|]+$/i', $input ) ) ? $input : false;
	}

	/**
	 * Build customer fields value based on gravity forms.
	 *
	 * @since 2.0.0
	 *
	 * @return boolean
	 */
	public static function get_customer_fields() {
		return array(
			array(
				'name'      => 'first_name',
				'meta_name' => 'billingInformation_firstName',
			),
			array(
				'name'      => 'last_name',
				'meta_name' => 'billingInformation_lastName',
			),
			array(
				'name'      => 'email',
				'meta_name' => 'billingInformation_email',
			),
			array(
				'name'      => 'address1',
				'meta_name' => 'billingInformation_address',
			),
			array(
				'name'      => 'address2',
				'meta_name' => 'billingInformation_address2',
			),
			array(
				'name'      => 'city',
				'meta_name' => 'billingInformation_city',
			),
			array(
				'name'      => 'state',
				'meta_name' => 'billingInformation_state',
			),
			array(
				'name'      => 'zip',
				'meta_name' => 'billingInformation_zip',
			),
			array(
				'name'      => 'country',
				'meta_name' => 'billingInformation_country',
			),
			array(
				'name'      => 'birth_date',
				'meta_name' => 'billingInformation_birth_date',
			),
			array(
				'name'      => 'company',
				'meta_name' => 'billingInformation_company',
			),
			array(
				'name'      => 'phone',
				'meta_name' => 'billingInformation_phone',
			),
			array(
				'name'      => 'mobile',
				'meta_name' => 'billingInformation_mobile',
			),
		);
	}

	/**
	 * Converting the amount into cents
	 *
	 * @since 2.0.0
	 * @param float $amount The amount.
	 *
	 * @return int
	 */
	public static function formatted_amount( $amount ) {

		return str_replace( ',', '', sprintf( '%0.2f', $amount ) ) * 100;
	}

	/**
	 * Perform encoding process for the given data.
	 *
	 * @since 2.0.0
	 * @param array  $data The encode values.
	 * @param string $key  The payment access key value.
	 * @param string $salt The salt value.
	 *
	 * @return string
	 */
	public static function encrypt_data( $data, $key, $salt ) {

		try {
			$data = htmlentities(
				base64_encode(
					openssl_encrypt( $data, 'aes-256-cbc', $key, true, $salt )
				)
			);
		} catch ( Exception $e ) {

			// Error log for the exception.
			GFCommon::log_error( 'Encrypt error occured: ' . __CLASS__ . '::' . __FUNCTION__ . $e->getMessage() );
		}
			return $data;
	}

	/**
	 * Perform decoding process for the given data.
	 *
	 * @since 2.0.0
	 * @param string $data The decode values.
	 * @param string $key  The payment access key value.
	 */
	public static function decrypt_data( &$data, $key ) {

		foreach ( self::$secure_parameters as $value ) {
			if ( isset( $data[ $value ] ) ) {
				$data[ $value ] = self::decrypt( $data[ $value ], $key, $data['uniqid'] );
			}
			
		}
		$data['amount'] = $data['amount']/100;
	}

	/**
	 * Perform decoding process for the given data.
	 *
	 * @since 2.0.0
	 * @param string $data The decode values.
	 * @param string $key  The payment access key value.
	 * @param string $salt The salt value.
	 *
	 * @return string
	 */
	public static function decrypt( $data, $key, $salt ) {
		try {
			$data = openssl_decrypt(
				base64_decode( $data ),
				'aes-256-cbc',
				$key,
				true,
				$salt
			);
		} catch ( Exception $e ) {

			GFCommon::log_error( 'Decrypt error occured: ' . __CLASS__ . '::' . __FUNCTION__ . $e->getMessage() );
		}
		return $data;
	}

	/**
	 * Generate random string for hash call.
	 *
	 * @since  2.0.0
	 * @param int $length The length of the string.
	 * @return string
	 */
	public static function random_string( $length = 16 ) {

		$random_array = array(
			'8',
			'7',
			'6',
			'5',
			'4',
			'3',
			'2',
			'1',
			'9',
			'0',
			'9',
			'7',
			'6',
			'1',
			'2',
			'3',
			'4',
			'5',
			'6',
			'7',
			'8',
			'9',
			'0',
		);
		shuffle( $random_array );
		return substr( implode( $random_array, '' ), 0, $length );
	}

	/**
	 * Return server / address.
	 *
	 * @since 2.0.0
	 *
	 * @return float
	 */
	public static function get_server_address() {
		$server = $_SERVER; // input var okay.

		// Check for valid IP.
		if ( empty( $server ['SERVER_ADDR'] ) ) {
			$ip_address = gethostbyname( $server['HTTP_HOST'] );
		} else {
			$ip_address = $server ['SERVER_ADDR'];
		}
		return $ip_address;
	}

	/**
	 * Handle hash generation.
	 *
	 * @since 2.0.0
	 * @param array  $data The array values to be hashed.
	 * @param string $key  The secret key value.
	 *
	 * @return string
	 */
	public static function generate_hash( $data, $key ) {

		$string = '';

		// hash generation using md5 and encoded vendor details.
		foreach ( self::$secure_parameters as $param ) {
			if ( isset( $data[ $param ] ) ) {
				$string .= $data[ $param ];
			}
		}
		$string .= $data['uniqid'];
		$string .= strrev( $key );

		return hash( 'sha256', $string );

	}

	/**
	 * Handle server post process.
	 *
	 * @since 2.0.0
	 * @param array  $request_data The array values to be sent.
	 * @param string $url          The URL value.
	 *
	 * @return string
	 */
	public static function server_request( $request_data, $url = 'https://paygate.novalnet.de/paygate.jsp' ) {

		// Post the values to the paygate URL.
		$response = wp_remote_post(
			$url,
			array(
				'method' => 'POST',
				'body'   => $request_data,
			)
		);

		// Check for error.
		if ( is_wp_error( $response ) ) {
			GFCommon::log_error( 'While post the request error occured: ' . __CLASS__ . '::' . __FUNCTION__ . $response->get_error_message() );
			return 'tid=&status=' . $response->get_error_code() . '&status_message=' . $response->get_error_message();
		}

		// Return the response.
		return $response['body'];
	}

	/**
	 * Returns Wordpress-blog language.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public static function get_language() {
		return substr( get_bloginfo( 'language' ), 0, 2 );
	}

	/**
	 * Get the customer details.
	 *
	 * @since 2.0.0
	 * @param array $feed  Active payment feed containing all the configuration data.
	 * @param array $entry Current entry array containing entry information (i.e data submitted by users).
	 * @param array $form  Current form array containing all form settings.
	 *
	 * @return array
	 */
	public static function get_customer_data( $feed, $entry, $form ) {
		$data = array();
		foreach ( self::get_customer_fields() as $field ) {

			if ( isset( $feed['meta'][ $field['meta_name'] ] ) ) {
				$field_id = $feed['meta'][ $field['meta_name'] ];

				$value = rgar( $entry, $field_id );

				$data[ $field['name'] ] = $value;
			}
		}
		if ( array_search( '', $data, true ) ) {
			foreach ( $form['fields'] as &$field ) {
				$id         = $field->id;
				$input_type = GFFormsModel::get_input_type( $field );
				if ( 'name' === $input_type ) {
					if ( ! rgar( $data, 'first_name' ) ) {
						$data['first_name'] = trim( rgpost( "input_{$id}_3" ) );
					}
					if ( ! rgar( $data, 'last_name' ) ) {
						$data['last_name'] = trim( rgpost( "input_{$id}_6" ) );
					}
				} elseif ( 'email' === $input_type && ! rgar( $data, 'email' ) ) {
					$data['email'] = trim( rgpost( "input_{$id}" ) );
				} elseif ( 'phone' === $input_type && ! rgar( $data, 'phone' ) ) {
					$data['tel'] = trim( rgpost( "input_{$id}" ) );
				} elseif ( 'address' === $input_type ) {
					if ( ! rgar( $data, 'street' ) ) {
						$data['street'] = trim( rgpost( "input_{$id}_1" ) ) . ' ' . trim( rgpost( "input_{$id}_2" ) );
					}
					if ( ! rgar( $data, 'city' ) ) {
						$data['city'] = trim( rgpost( "input_{$id}_3" ) );
					}
					if ( ! rgar( $data, 'zip' ) ) {
						$data['zip'] = trim( rgpost( "input_{$id}_5" ) );
					}
					if ( ! rgar( $data, 'country_code' ) ) {
						$data['country'] = trim( rgpost( "input_{$id}_6" ) );
					}
				}elseif ( 'date' === $input_type && ! rgar( $data, 'birth_date' ) ) {
						$data['birth_date'] = trim( rgpost( "input_{$id}" ) );
				}elseif ( 'phone' === $input_type && ! rgar( $data, 'phone' ) ) {
					$data['phone'] = trim( rgpost( "input_{$id}" ) );
				}elseif ( 'mobile' === $input_type && ! rgar( $data, 'mobile' ) ) {
					$data['mobile'] = trim( rgpost( "input_{$id}" ) );
				}elseif ( 'text' === $input_type && ! rgar( $data, 'company' ) ) {
					$data['company'] = trim( rgpost( "input_{$id}" ) );
				}
			}
		}
		if ( rgar( $data, 'country' ) ) {
			$data['country_code'] = GFCommon::get_country_code( $data['country'] );
		}
		$data['search_in_street'] = '1';
		return $data;
	}

	/**
	 * Forms the redirection parameters.
	 *
	 * @since 2.0.0
	 * @param array $entry      Current entry array containing entry information (i.e data submitted by users).
	 * @param array $settings   The plugin settings.
	 * @param array $parameters The formed parameters.
	 */
	public static function form_redirection_parameters( $entry, $settings, &$parameters ) {
		$parameters ['uniqid']              = self::random_string();
		$parameters ['return_method']       = 'POST';
		$parameters ['error_return_method'] = 'POST';
		$parameters ['implementation']      = 'ENC';
		$parameters ['return_url']          = self::return_url( $entry['form_id'], $entry['id'] );
		$parameters ['error_return_url']    = $parameters ['return_url'];
		$parameters ['purl']                = '1';

		foreach ( self::$secure_parameters as $secure_parameter ) {
			$parameters [ $secure_parameter ] = self::encrypt_data( $parameters[ $secure_parameter ], $settings['novalnet_payment_access_key'], $parameters['uniqid'] );
		}

		$parameters ['hash'] = self::generate_hash( $parameters, $settings['novalnet_payment_access_key'] );
	}

	/**
	 * Forms the system parameters.
	 *
	 * @since 2.0.0
	 * @param array $parameters The formed parameters.
	 */
	public static function form_system_parameters( &$parameters ) {

		$parameters['system_name']    = gf_novalnet()->_system_name;
		$parameters['system_version'] = GFForms::$version . '-NN-' . GF_NOVALNET_VERSION;
		$parameters['system_ip']      = self::get_server_address();
	}
	
	/**
	 * Forms the system parameters.
	 *
	 * @since 2.0.0
	 * @param array $parameters The formed parameters.
	 */
	public static function form_motoForm_parameters( &$parameters ) {

		$parameters['address_form']   = 0;
		$parameters['skip_cfm']       = 1;
		$parameters['skip_suc']       = 1;
		$parameters['hfooter']        = 0;
		$parameters['thide']          = 1;
		$parameters['shide']          = 1;
		$parameters['lhide']          = 1;

		
	}

	/**
	 * Forms the system parameters.
	 *
	 * @since 2.0.0
	 * @param array $settings   The plugin settings.
	 * @param array $parameters The formed parameters.
	 */
	public static function form_payment_parameters( $settings, &$parameters ) {
		$parameters['cc_3d'] = ( isset( $settings['cc_3d'] ) && '1' === $settings['cc_3d'] ) ? 1: 0;
		
		if ( 'authorize' === rgar( $settings, 'transaction_type' ) && ! empty( $parameters['amount'] ) ) {
			$amount_limit = rgar( $settings, 'on_hold_limit' );
			if ( ! $amount_limit || $parameters['amount'] >= $amount_limit ) {
				$parameters['on_hold'] = 1;
			}
		}
		if ( rgar( $settings, 'due_date' ) >= 7 ) {
						$parameters['due_date'] = rgar( $settings, 'due_date' );
		}
		if ( rgar( $settings, 'sepa_due_date' ) >= 2 && rgar( $settings, 'sepa_due_date' ) <= 14 ) {
				$parameters['sepa_due_date'] = date('Y-m-d', strtotime('+ ' . rgar( $settings, 'sepa_due_date' ) . ' day'));
		}
		$parameters['cashpayment_due_date'] = date('Y-m-d', strtotime('+ ' . rgar( $settings, 'slip_expiry_date' ) . ' day'));
	}

	/**
	 * Forms the order parameters.
	 *
	 * @since 2.0.0
	 * @param array $entry      Current entry array containing entry information (i.e data submitted by users).
	 * @param array $form       Current form array containing all form settings.
	 * @param array $parameters The formed parameters.
	 */
	public static function form_order_parameters( $entry, $form, &$parameters, $novalnet_product ) {
		
		$parameters['order_no']  = rgar( $entry, 'id' );
		$parameters['remote_ip'] = rgar( $entry, 'ip' );
		$parameters['amount']    = self::formatted_amount( GFCommon::get_order_total( $form, $entry ) );
		$parameters['currency']  = rgar( $entry, 'currency' );
		$parameters['lang'] = $parameters['langauge']  = strtoupper(self::get_language());

	}

	/**
	 * Forms the customer parameters.
	 *
	 * @since 2.0.0
	 * @param array $customer   The customer data.
	 * @param array $parameters The formed parameters.
	 */
	public static function form_customer_parameters( $customer, &$parameters ) {
		$parameters['first_name']       = rgar( $customer, 'first_name' );
		$parameters['last_name']        = rgar( $customer, 'last_name' );
		$parameters['street']           = trim( rgar( $customer, 'address1' ) . ' ' . rgar( $customer, 'address2' ) );
		$parameters['city']             = rgar( $customer, 'city' );
		$parameters['zip']              = rgar( $customer, 'zip' );
		$parameters['country_code']     = rgar( $customer, 'country_code' );
		$parameters['country']     		= rgar( $customer, 'country_code' );
		$parameters['email']            = rgar( $customer, 'email' );
		$parameters['birth_date']       = rgar( $customer, 'birth_date' );
		$parameters['company']       	= rgar( $customer, 'company' );
		$parameters['tel']       		= rgar( $customer, 'phone' );
		$parameters['mobile']       	= rgar( $customer, 'mobile' );
		$parameters['search_in_street'] = rgar( $customer, 'search_in_street' );

	}

	/**
	 * Forms the Vendor parameters.
	 *
	 * @since 2.0.0
	 * @param array $settings   The plugin settings.
	 * @param array $parameters The formed parameters.
	 */
	public static function form_vendor_parameters( $settings, &$parameters ) {
		$parameters['vendor']    = $settings['novalnet_vendor'];
		$parameters['auth_code'] = $settings['novalnet_auth_code'];
		$parameters['product']   = $settings['novalnet_product'];
		$parameters['tariff']    = $settings['novalnet_tariff'];

		$parameters['test_mode'] = ( isset( $settings['test_mode'] ) && '1' === $settings['test_mode'] ) ? 1: 0;
		if ( ! empty( $settings['referrer_id'] ) ) {
			$parameters['referrer_id'] = $settings['referrer_id'];
		}
	}

	/**
	 * Build the valid return URL .
	 *
	 * @since 2.0.0
	 * @param int $form_id The form ID value.
	 * @param int $lead_id The lead ID value.
	 */
	public static function return_url( $form_id = '', $lead_id = '' ) {
		$server = $_SERVER; // input var okay.
		$url    = GFCommon::is_ssl() ? 'https://' : 'http://';

		$server_port = apply_filters( 'gform_novalnet_return_url_port', $server['SERVER_PORT'] ); // CSRF ok, Input var okay.

		if ( '80' !== $server_port ) {
			$url .= $server['SERVER_NAME'] . ':' . $server_port . $server['REQUEST_URI']; // CSRF ok, Input var okay.
		} else {
			$url .= $server['SERVER_NAME'] . $server['REQUEST_URI']; // CSRF ok, Input var okay.
		}
		$url = remove_query_arg( 'gf_novalnet_error', $url );
		if ( '' === $form_id && '' === $lead_id ) {
			return $url;
		}

		$ids_query  = "entry_id={$form_id}|{$lead_id}";
		$ids_query .= '&hash=' . wp_hash( $ids_query );

		return add_query_arg(
			array(
				'page'               => 'gf_novalnet_response',
				'gf_novalnet_return' => base64_encode( $ids_query ),
			),
			$url
		);

	}

	/**
	 * Form payment comments.
	 *
	 * @since 2.0.0
	 * @param array $data The data used to form the comments.
	 *
	 * @return string
	 */
	public static function form_payment_comments( $data, $novalnet_product ) {
		$GF_Field_Address = new GF_Field_Address();
		$comments = '';
		if ( ! empty( $data ['tid'] ) ) {
			
			if (in_array(rgar( $data, 'key' ), array(40, 41))) {
				$comments .= __('This is processed as a guarantee payment', 'gravityforms-novalnet');
			}
			$payment_name = GF_Novalnet_Helper::form_payment_name( $data );
			/* translators: %s: TID */
			$comments .= PHP_EOL . sprintf( __( 'Payment Method: %s', 'gravityforms-novalnet' ), $payment_name['payment_type'] );
			$comments .= PHP_EOL . sprintf( __( 'Novalnet transaction ID: %s', 'gravityforms-novalnet' ), $data ['tid'] );
			if ( ! empty( $data ['test_mode'] ) ) {
				$comments .= PHP_EOL . __( 'Test order', 'gravityforms-novalnet' );
			}
		}
		$payment_key = rgar( $data, 'key' ) ? rgar( $data, 'key' ) : rgar( $data, 'payment_id' );

		if ( ('27' === $payment_key || '41' === $payment_key && rgar( $data, 'status' ) == 100)   ) {
			if( empty(rgar( $data, 'invoice_ref' ))) {
				$data['invoice_ref'] = 'BNR-' . $novalnet_product . '-' . rgar( $data, 'order_no' );
			}
			$comments .= self::form_bank_comments( $data );
		} elseif ( '59' === $payment_key && rgar( $data, 'status' ) == 100) {
			if ( isset( $data['cashpayment_due_date'] ) ) {
				/* translators: %s: cashpayment_due_date */
				$comments .= sprintf( __( '<br/>Slip expiry date: %s', 'gravityforms-novalnet' ), self::formatted_date( $data ['cashpayment_due_date'] ) ) . PHP_EOL . PHP_EOL;
			}
			if ( isset( $data['nearest_store_title_1'] ) ) {
				$comments    .= __( 'Store(s) near you' ) . PHP_EOL . PHP_EOL;
				$store_values = preg_filter( '/^nearest_store_(.*)_(.*)$/', '$2', array_keys( $data ) );
				if ( $store_values ) {
					$countries = array_flip($GF_Field_Address->get_country_codes());
					$count     = max( $store_values );
					for ( $i = 1; $i <= $count; $i++ ) {
						$comments .= $data[ 'nearest_store_title_' . $i ] . '</br>';
						$comments .= $data[ 'nearest_store_street_' . $i ] . '</br>';
						$comments .= $data[ 'nearest_store_city_' . $i ] . '</br>';
						$comments .= $data[ 'nearest_store_zipcode_' . $i ] . '</br>';
						$comments .= ucwords(strtolower($countries[ $data[ 'nearest_store_country_' . $i ] ] )) . '</br></br>';
					}
				}
			}
		}
		if (rgar( $data, 'key' ) == 40 && $data['tid_status'] == 75) {
				$comments .= PHP_EOL . __('Your order is under verification and we will soon update you with the order status. Please note that this may take upto 24 hours.');
			}

		return $comments;
	}

	/**
	 * Handling db insert operation.
	 *
	 * @since 2.0.0
	 * @param array  $insert_value The values to be insert in the given table.
	 * @param string $table_name   The table name.
	 */
	public static function db_insert( $insert_value, $table_name = 'novalnet_transaction_details' ) {
		global $wpdb;

		// Perform query action.
		self::query_process( $wpdb->insert( "{$wpdb->prefix}$table_name", $insert_value ) ); // db call ok.
	}

	/**
	 * Handling db update operation.
	 *
	 * @since 11.0.0
	 * @param array  $update_value The update values.
	 * @param array  $where_array  The where condition query.
	 * @param string $table_name   The table name.
	 */
	public static function db_update( $update_value, $where_array, $table_name = 'novalnet_transaction_details' ) {
		global $wpdb;
		// Perform query action.
		self::query_process( $wpdb->update( "{$wpdb->prefix}$table_name", $update_value, $where_array ) ); // WPCS: cache ok, DB call ok.
	}

	/**
	 * Retrieves messages from server response.
	 *
	 * @since 2.0.0
	 * @param array $data The response data.
	 *
	 * @return string
	 */
	public static function get_status_description( $data ) {
		if ( isset( $data ['status_text'] ) ) {
			return $data ['status_text'];
		} elseif ( isset( $data ['status_desc'] ) ) {
			return $data ['status_desc'];
		} elseif ( isset( $data ['status_message'] ) ) {
			return $data ['status_message'];
		} elseif ( isset( $data ['subscription_pause'] ['status_message'] ) ) {
			return $data ['subscription_pause'] ['status_message'];
		} elseif ( isset( $data ['pin_status'] ['status_message'] ) ) {
			return $data ['pin_status'] ['status_message'];
		} elseif ( isset( $data ['subscription_update'] ['status_message'] ) ) {
			return $data ['subscription_update'] ['status_message'];
		}
		return __( 'Payment was not successful. An error occurred', 'gravityforms-novalnet' );
	}

	/**
	 * Format invoice details.
	 *
	 * @since 2.0.0
	 * @param array $invoice_details The invoice details.
	 */
	public static function format_invoice_details( &$invoice_details ) {
		
		$invoice_details['bank_name'] = '';
		if ( isset( $invoice_details ['invoice_bankname'] ) ) {

			$prefix                       = 'invoice';
			$invoice_details['bank_name'] = $invoice_details ['invoice_bankname'];
		} elseif ( isset( $invoice_details ['bank_name'] ) ) {
			$prefix                       = 'bank';
			$invoice_details['bank_name'] = $invoice_details ['invoice_bankname'];
		}
		if ( isset( $invoice_details ['invoice_bankplace'] ) ) {
			$invoice_details['bank_name'] .= ' ' . $invoice_details ['invoice_bankplace'];
		}

		if ( isset( $invoice_details ['due_date'] ) ) {
			$invoice_details ['due_date_formatted'] = self::formatted_date( $invoice_details ['due_date'] );
		}

		if ( empty( $invoice_details ['account_holder'] ) ) {
			$invoice_details ['account_holder'] = $invoice_details ['invoice_account_holder'];
		}

		if ( isset( $invoice_details ['amount'] ) ) {
			
			$invoice_details ['amount_formatted'] = GFCommon::to_money( $invoice_details ['amount'], $invoice_details ['currency'] );
		}

		if ( isset( $invoice_details[ $prefix . '_iban' ] ) ) {
			$invoice_details['iban'] = $invoice_details [ $prefix . '_iban' ];
		}
		if ( isset( $invoice_details[ $prefix . '_bic' ] ) ) {
			$invoice_details['bic'] = $invoice_details [ $prefix . '_bic' ];
		}
	}

	/**
	 * Form Bank details comments.
	 *
	 * @since 2.0.0
	 * @param array $invoice_details   The invoice details.
	 *
	 * @return string
	 */
	public static function form_bank_comments( $invoice_details ) {
		
		$novalnet_comments = '';
		// Call this function for getting added message for invoice/prepayment payment method.
		if($invoice_details['tid_status'] == 75 && $invoice_details['key'] == 41) {
		$novalnet_comments = '<br><br>' . __('Your order is under verification and once confirmed, we will send you our bank details to where the order amount should be transferred. Please note that this may take upto 24 hours', 'gravityforms-novalnet');		
		} 
	
		if($invoice_details['tid_status'] == 100) {
		self::format_invoice_details( $invoice_details );

		$novalnet_comments = PHP_EOL . PHP_EOL . __( 'Please transfer the amount to the below mentioned account details of our payment processor Novalnet', 'gravityforms-novalnet' ) . PHP_EOL . PHP_EOL;

		$comments_array = array(
			/* translators: %s: Due date */
			__( 'Due date: %s', 'gravityforms-novalnet' ) => 'due_date_formatted',
			/* translators: %s: Account holder */
			__( 'Account holder: %s', 'gravityforms-novalnet' ) => 'account_holder',
			/* translators: %s: Bank name */
			__( 'Bank: %s', 'gravityforms-novalnet' )     => 'bank_name',
			/* translators: %s: Bank name */
			__( 'Bank: %s', 'gravityforms-novalnet' )     => 'bank_name',
			/* translators: %s: IBAN */
			__( 'IBAN: %s', 'gravityforms-novalnet' )     => 'iban',
			/* translators: %s: BIC */
			__( 'BIC: %s', 'gravityforms-novalnet' )      => 'bic',
			/* translators: %s: Amount */
			__( 'Amount: %s', 'gravityforms-novalnet' )   => 'amount_formatted',
		);

		foreach ( $comments_array as $text => $value ) {
			if ( isset( $invoice_details [ $value ] ) ) {
				$novalnet_comments .= sprintf( $text, $invoice_details [ $value ] ) . PHP_EOL;
			}
		}

		// Form reference comments.
		$novalnet_comments .= PHP_EOL . __( 'Please use the following payment reference for your money transfer, as only through this way your payment is matched and assigned to the order: ', 'gravityforms-novalnet' ) . PHP_EOL;
		/* translators: %s: invoice_ref */
		$novalnet_comments .= PHP_EOL . sprintf( __( 'Payment Reference 1: %s', 'gravityforms-novalnet' ), $invoice_details ['invoice_ref'] );
		/* translators: %s: TID */
		$novalnet_comments .= PHP_EOL . sprintf( __( 'Payment Reference 2: %s', 'gravityforms-novalnet' ), $invoice_details ['tid'] );
		return self::format_text( $novalnet_comments );
		}

		return self::format_text( $novalnet_comments );
	}

	/**
	 * Format the text.
	 *
	 * @since 2.0.0
	 * @param string $text The test value.
	 *
	 * @return int|boolean
	 */
	public static function format_text( $text ) {
		return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Formating the date as per the
	 * shop structure.
	 *
	 * @since 2.0.0
	 * @param date $date The date value.
	 *
	 * @return string
	 */
	public static function formatted_date( $date = '' ) {
		return date_i18n( get_option( 'date_format' ), strtotime( '' === $date ? date( 'Y-m-d H:i:s' ) : $date ) );
	}

	/**
	 * Returns original post_id based on TID.
	 *
	 * @since 2.0.0
	 * @param int $tid The tid value.
	 *
	 * @return array
	 */
	public static function get_original_post_id( $tid ) {

		global $wpdb;

		// Get post id based on TID.
		$post_id = self::query_process( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} where post_excerpt LIKE %s", "%$tid%" ), ARRAY_A ) ); // db call ok; no-cache ok.
		return $post_id;

	}

	/**
	 * Returns the details to execute callback.
	 *
	 * @since 2.0.0
	 * @param int $tid     The TID value.
	 *
	 * @return array
	 */
	public static function get_callback_details( $tid ) {

		global $wpdb;

		return self::query_process( $wpdb->get_row( $wpdb->prepare( "SELECT vendor_details, entry_id, payment_type, transaction_amount, paid_amount, tid,  status FROM {$wpdb->prefix}novalnet_transaction_details WHERE tid=%s", $tid ), ARRAY_A ) );// db call ok; no-cache ok.
	}

	/**
	 * Check for server status
	 *
	 * @since 2.0.0
	 * @param array  $data   The response array.
	 * @param string $key    The parameter to be checked.
	 * @param string $status The status to be checked.
	 *
	 * @return array
	 */
	public static function status_check( $data, $key = 'status', $status = '100' ) {
		return ( ! empty( $data [ $key ] ) && $status === $data [ $key ] );
	}

	/**
	 * Get the payment name based on the payment type
	 *
	 * @since 2.0.0
	 * @param array $response
	 *
	 * @return array
	 */
	public static function form_payment_name( $response ) {
		$payment_name = array(
			'CREDITCARD'            => array( 'payment_type' => __( 'Credit Card', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_credit_card'),
			'ONLINE_TRANSFER' 		=> array( 'payment_type' => __( 'Instant Bank Transfer', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_instantbank'),
			'PAYPAL'                => array( 'payment_type' => __( 'PayPal', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_paypal'),
			'IDEAL'                 => array( 'payment_type' => __( 'iDEAL', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_ideal'),
			'GIROPAY'               => array( 'payment_type' => __( 'giropay', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_giropay'),
			'EPS'                   => array( 'payment_type' => __( 'eps', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_eps'),
			'PRZELEWY24'            => array( 'payment_type' => __( 'Przelewy24', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_przelewy24'),
			'CASHPAYMENT'           => array( 'payment_type' => __( 'Barzahlen', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_barzahlen'),
		);

		if ( 'INVOICE_START' == $response['payment_type'] ) {
			if ( 'prepayment' == strtolower( $response['invoice_type'] ) ) {
				return array( 'payment_type' => __( 'Prepayment', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_prepayment');
			}
			return array( 'payment_type' => __( 'Invoice', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_invoice');
		}elseif(( 'GUARANTEED_INVOICE' == $response['payment_type']  ) ) {
				return array( 'payment_type' => __( 'Invoice', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_invoice');
		}
		if('DIRECT_DEBIT_SEPA' == $response['payment_type']) {
			return array( 'payment_type' => __( 'Direct Debit SEPA', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_direct_debit_sepa');
		}
		
		elseif('GUARANTEED_DIRECT_DEBIT_SEPA' == $response['payment_type']) {
			 return array( 'payment_type' => __( 'Direct Debit SEPA', 'gravityforms-novalnet' ), 'payment_method'=> 'novalnet_direct_debit_sepa');
		 }
		
		return $payment_name[ $response['payment_type'] ];
	}

	/**
	 * Checks for the given string in given text.
	 *
	 * @since 2.0.0
	 * @param string $string The string value.
	 * @param string $data   The data value.
	 *
	 * @return boolean
	 */
	public static function check_string( $string, $data = 'novalnet' ) {
		return ( false !== strpos( $string, $data ) );
	}
}
