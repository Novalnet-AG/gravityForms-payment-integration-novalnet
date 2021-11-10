<?php
/**
 * Novalnet API callback.
 *
 * @class    NN_Callback_Api
 * @version  2.0.0
 * @package  Novalnet-gateway/API
 * @category Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Novalnet Callback Api Class.
 *
 * @class   Novalnet
 * @version 2.0.0
 */
class GF_Novalnet_Callback {


	/**
	 * Level - 0 Initial payment types.
	 *
	 * @var array
	 */
	protected $payments = array(
		'CREDITCARD',
		'INVOICE_START',
		'DIRECT_DEBIT_SEPA',
		'GUARANTEED_INVOICE',
		'GUARANTEED_DIRECT_DEBIT_SEPA',
		'PAYPAL',
		'PRZELEWY24',
		'ONLINE_TRANSFER',
		'IDEAL',
		'GIROPAY',
		'EPS',
		'CASHPAYMENT',
	);

	/**
	 * Level - 1 Chargeback/ refund/ book back payment types.
	 *
	 * @var array
	 */
	protected $chargebacks = array(
		'RETURN_DEBIT_SEPA',
		'REVERSAL',
		'CREDITCARD_BOOKBACK',
		'CREDITCARD_CHARGEBACK',
		'PAYPAL_BOOKBACK',
		'REFUND_BY_BANK_TRANSFER_EU',
		'PRZELEWY24_REFUND',
		'CASHPAYMENT_REFUND',
		'GUARANTEED_INVOICE_BOOKBACK',
		'GUARANTEED_SEPA_BOOKBACK',
	);

	/**
	 * Level - 2 Credit/ collection payment types.
	 *
	 * @var array
	 */
	protected $collections = array(
		'INVOICE_CREDIT',
		'CREDIT_ENTRY_CREDITCARD',
		'CREDIT_ENTRY_SEPA',
		'DEBT_COLLECTION_SEPA',
		'DEBT_COLLECTION_CREDITCARD',
		'ONLINE_TRANSFER_CREDIT',
		'CASHPAYMENT_CREDIT',
	);

	/**
	 * Subscription payment types.
	 *
	 * @var array
	 */
	protected $subscriptions = array(
		'SUBSCRIPTION_STOP',
		'SUBSCRIPTION_REACTIVATE',

	);

	/**
	 * Transaction cancelation payment type.
	 *
	 * @var array
	 */
	protected $transaction_cancellation = array(
		'TRANSACTION_CANCELLATION',

	);

	/**
	 * Payments types.
	 *
	 * @var array
	 */
	protected $payment_groups = array(
		'novalnet_credit_card'       => array(
			'CREDITCARD',
			'CREDITCARD_BOOKBACK',
			'CREDITCARD_CHARGEBACK',
			'CREDIT_ENTRY_CREDITCARD',
			'DEBT_COLLECTION_CREDITCARD',
			'SUBSCRIPTION_STOP',
			'SUBSCRIPTION_REACTIVATE',
		),
		'novalnet_direct_debit_sepa' => array(
			'DIRECT_DEBIT_SEPA',
			'RETURN_DEBIT_SEPA',
			'CREDIT_ENTRY_SEPA',
			'DEBT_COLLECTION_SEPA',
			'GUARANTEED_DIRECT_DEBIT_SEPA',
			'GUARANTEED_SEPA_BOOKBACK',
			'REFUND_BY_BANK_TRANSFER_EU',
			'SUBSCRIPTION_STOP',
			'SUBSCRIPTION_REACTIVATE',
			'TRANSACTION_CANCELLATION',
		),
		'novalnet_ideal'             => array(
			'IDEAL',
			'REFUND_BY_BANK_TRANSFER_EU',
			'ONLINE_TRANSFER_CREDIT',
			'REVERSAL',
		),
		'novalnet_eps'               => array(
			'EPS',
			'REFUND_BY_BANK_TRANSFER_EU',
			'ONLINE_TRANSFER_CREDIT',
			'REVERSAL',
		),
		'novalnet_giropay'           => array(
			'GIROPAY',
			'REFUND_BY_BANK_TRANSFER_EU',
			'ONLINE_TRANSFER_CREDIT',
			'REVERSAL',
		),
		'novalnet_instantbank'       => array(
			'ONLINE_TRANSFER',
			'REFUND_BY_BANK_TRANSFER_EU',
			'ONLINE_TRANSFER_CREDIT',
			'REVERSAL',
		),
		'novalnet_paypal'            => array(
			'PAYPAL',
			'PAYPAL_BOOKBACK',
			'SUBSCRIPTION_STOP',
			'SUBSCRIPTION_REACTIVATE',
		),
		'novalnet_prepayment'        => array(
			'INVOICE_START',
			'INVOICE_CREDIT',
			'SUBSCRIPTION_STOP',
			'SUBSCRIPTION_REACTIVATE',
			'REFUND_BY_BANK_TRANSFER_EU',
		),
		'novalnet_invoice'     => array(
			'INVOICE_START',
			'GUARANTEED_INVOICE',
			'GUARANTEED_INVOICE_BOOKBACK',
			'INVOICE_CREDIT',
			'SUBSCRIPTION_STOP',
			'SUBSCRIPTION_REACTIVATE',
			'REFUND_BY_BANK_TRANSFER_EU',
			'TRANSACTION_CANCELLATION',
		),
		'novalnet_przelewy24'        => array(
			'PRZELEWY24',
			'PRZELEWY24_REFUND',
		),
		'novalnet_barzahlen'         => array(
			'CASHPAYMENT',
			'CASHPAYMENT_REFUND',
			'CASHPAYMENT_CREDIT',
		),
	);

	/**
	 * Mandatory parameters.
	 *
	 * @var array
	 */
	protected $required_params = array(
		'vendor_id',
		'status',
		'payment_type',
		'tid_status',
		'tid',
	);

	/**
	 * Success transaction codes.
	 *
	 * @var array
	 */
	protected $success_code = array(
		'PAYPAL'                       => array(
			'100',
			'90',
			'85',
		),
		'INVOICE_START'                => array(
			'100',
			'91',
		),
		'GUARANTEED_INVOICE'           => array(
			'100',
			'91',
		),
		'CREDITCARD'                   => array(
			'100',
			'98',
		),
		'DIRECT_DEBIT_SEPA'            => array(
			'100',
			'99',
		),
		'GUARANTEED_DIRECT_DEBIT_SEPA' => array(
			'100',
			'99',
		),
		'ONLINE_TRANSFER'              => array(
			'100',
		),
		'GIROPAY'                      => array(
			'100',
		),
		'IDEAL'                        => array(
			'100',
		),
		'EPS'                          => array(
			'100',
		),
		'PRZELEWY24'                   => array(
			'100',
			'86',
		),
		'CASHPAYMENT'                  => array(
			'100',
		),
	);

	/**
	 * Request parameters.
	 *
	 * @var array
	 */
	protected $server_request = array();

	/**
	 * Order reference values.
	 *
	 * @var array
	 */
	protected $order_reference = array();

	/**
	 * Success status values.
	 *
	 * @var boolean
	 */
	protected $success_status;

	/**
	 * Test mode value.
	 *
	 * @var boolean
	 */
	protected $test_mode;

	/**
	 * Callback api process.
	 *
	 * @since 2.0.0
	 */
	public function callback_api_process() {

		$this->server_request = array_map( 'trim', gf_novalnet()->request );
		$this->settings       = gf_novalnet()->get_plugin_settings();

		// Backend callback option.
		$this->test_mode = $this->settings['callback_test_mode'];

		// Authenticating the server request based on IP.
		$client_ip_addr = GFFormsModel::get_ip();
		$get_host_name  = gethostbyname( 'pay-nn.de' );

		if ( empty( $get_host_name ) ) {
			$this->display_message( 'Novalnet HOST IP missing' );
		}
		if ( $get_host_name !== $client_ip_addr && ! $this->test_mode ) {
			$this->display_message( 'Novalnet callback received. Unauthorised access from the IP ' . $client_ip_addr );
		}
		
		// Get request parameters.
		$this->validate_server_request();

		// Check for success status.
		$this->success_status = ( GF_Novalnet_Helper::status_check( $this->server_request ) && GF_Novalnet_Helper::status_check( $this->server_request, 'tid_status' ) );

		// Get order reference.
		$this->order_reference = $this->get_order_reference();

		// Create order object.
		$this->gf_entry = GFFormsModel::get_lead( $this->order_reference ['entry_id'] );

		// Transaction cancellation process.
		$this->transaction_cancellation();

		// level 0 payments - Initial payments.
		$this->zero_level_process();

		// level 1 payments - Type of charge backs.
		$this->first_level_process();

		// level 2 payments - Type of credit entry.
		$this->second_level_process();

		if ( ! $this->success_status ) {
			$this->display_message( 'Novalnet callback received. Status is not valid: Only 100 is allowed' );
		}
		$this->display_message( 'Novalnet callback script executed already' );
	}

	/**
	 * Validate required fields
	 *
	 * @since 2.0.0
	 * @param array $required_params Required params.
	 */
	public function validate_required_fields( $required_params ) {

		foreach ( $required_params as $params ) {
			if ( empty( $this->server_request [ $params ] ) ) {
				$this->display_message( "Required param ( $params ) missing!" );
			} elseif ( in_array( $params, array( 'tid', 'tid_payment', 'signup_tid' ), true ) && ! preg_match( '/^\d{17}$/', $this->server_request [ $params ] ) ) {
				$this->display_message( 'Novalnet callback received. Invalid TID [ ' . $this->server_request [ $params ] . ' ] for Order.' );
			}
		}
	}

	/**
	 * Get the required TID parameter.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_required_tid() {

		$shop_tid = 'tid';
		if ( ! empty( $this->server_request ['payment_type'] ) && in_array( $this->server_request ['payment_type'], array_merge( $this->chargebacks, $this->collections ), true ) ) {
			$shop_tid = 'tid_payment';
		} elseif ( isset( $this->server_request ['subs_billing'] ) && '1' === $this->server_request ['subs_billing'] ) {
			$shop_tid = 'signup_tid';
		}
		return $shop_tid;
	}

	/**
	 * Validate and set the server request.
	 *
	 * @since 2.0.0
	 */
	public function validate_server_request() {
	   global $wpdb;
		$shop_tid_key             = $this->get_required_tid();
		$this->required_params [] = $shop_tid_key;
		// Validate the callback mandatory request parameters.
		$this->validate_required_fields( $this->required_params );
		
		$this->server_request ['shop_tid'] = $this->server_request [ $shop_tid_key ];

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT payment_method FROM {$wpdb->prefix}novalnet_transaction_details WHERE tid=%s", $this->server_request ['shop_tid'] ), ARRAY_A );
		
		if ( ! empty( $this->server_request ['payment_type'] ) && ! in_array( $this->server_request ['payment_type'], array_merge( $this->payments, $this->chargebacks, $this->collections, $this->subscriptions, $this->transaction_cancellation ), true) ) {
			$this->display_message( 'Novalnet callback received. Payment type ( ' . $this->server_request ['payment_type'] . ' ) is mismatched!' );
		}
	}




	/**
	 * Get order reference.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function get_order_reference() {
		$gf_entry_id = '';
		if ( ! empty( $this->server_request ['order_no'] ) ) {
			$gf_entry_id = $this->server_request ['order_no'];
		}
		$transaction_details = array();

		$transaction_details = GF_Novalnet_Helper::get_callback_details( $this->server_request ['shop_tid'] );

		if ( empty( $transaction_details ) ) {

			$is_failed_txn = gform_get_meta( $gf_entry_id, '_novalnet_transaction_comments' );

			if ( ! empty( $is_failed_txn ) && GF_Novalnet_Helper::check_string( $is_failed_txn, $this->server_request ['shop_tid'] )) {
				$this->update_initial_payment( $gf_entry_id, false);
				$transaction_details = GF_Novalnet_Helper::get_callback_details( $this->server_request ['shop_tid'] );
			} else {
				$this->update_initial_payment( $gf_entry_id,  true);
			}
		}
		return $transaction_details;
	}

	/**
	 * Callback API Level zero process.
	 *
	 * @since 2.0.0
	 */
	public function zero_level_process() {

		$entry_id = $this->gf_entry['id'];
		if ( in_array( $this->server_request ['payment_type'], $this->payments, true ) && GF_Novalnet_Helper::status_check( $this->server_request ) && in_array( $this->server_request ['tid_status'], $this->success_code [ $this->server_request ['payment_type'] ], true ) ) {

			// Check for Success transaction.
			if ( ($this->success_status && ! GF_Novalnet_Helper::status_check( $this->order_reference )) || ( in_array($this->server_request ['payment_type'], array( 'GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA'), true ) && in_array($this->server_request['tid_status'], array('91', '99')))) {
				/* translators: 1: TID 2: Amount 3: Date */
				$callback_comments = GF_Novalnet_Helper::format_text( sprintf( __( 'Novalnet Callback Script executed successfully for the TID: %1$s with amount %2$s on %3$s.', 'gravityforms-novalnet' ), $this->server_request ['tid'], GFCommon::to_money( $this->server_request ['amount'] / 100, $this->server_request ['currency'] ), GF_Novalnet_Helper::formatted_date() ) );
				if ( in_array( $this->server_request ['payment_type'], array('GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'INVOICE_START' ), true ) && in_array($this->order_reference['status'], array('75', '91', '99')) && $this->server_request ['tid_status'] == '100') {
					if($this->server_request['payment_type'] == 'GUARANTEED_INVOICE') {
						$data = $this->server_request;
						$data['amount'] = $data['amount']/100;
						$transaction_comments .= GF_Novalnet_Helper::form_payment_comments( $data, $this->settings['novalnet_product'] );
						$transaction_comments.= GF_Novalnet_Helper::form_bank_comments( $data ); 
						gform_update_meta( $entry_id, '_novalnet_transaction_comments', $transaction_comments );
					}
					// Update order status.
					$action['note']           = $callback_comments;
					$action['payment_status'] = ($this->server_request ['payment_type'] == 'GUARANTEED_INVOICE') ? $this->settings ['payment_callback_status'] : $this->settings ['payment_completion_status'];
					$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
					if($this->server_request ['payment_type'] == 'GUARANTEED_INVOICE' && '75' === $this->order_reference['status'] && $this->server_request ['tid_status'] == '100') {
						$action['payment_status'] = $this->settings ['payment_callback_status'];
					} 
					$entry = GFFormsModel::get_lead( $entry_id );
					if($this->server_request ['payment_type'] == 'GUARANTEED_INVOICE') {
						$data = $this->server_request;
						$data['amount'] = $data['amount']/100;
						$transaction_comments = GF_Novalnet_Helper::form_payment_comments( $data, $this->settings['novalnet_product']);
						$transaction_comments.= GF_Novalnet_Helper::form_bank_comments( $data ); 
						self::guaranteeOrderConfirmationMail(nl2br($transaction_comments));
						gform_update_meta( $entry_id, '_novalnet_transaction_comments', $transaction_comments );
					}
					gf_novalnet()->complete_payment( $this->gf_entry, $action );
				}
				elseif( in_array($this->server_request ['payment_type'], array( 'GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA'), true ) && '75' === $this->order_reference['status'] && in_array( $this->server_request ['tid_status'], array( '91','99' ), true ) ) {

				 /* translators: 1: TID 2: Date */
				  $callback_comments = GF_Novalnet_Helper::format_text( sprintf( __( 'Novalnet callback received. The transaction status has been changed from pending to on hold for the TID: %1$s on %2$s.', 'gravityforms-novalnet' ), $this->server_request ['tid'],GF_Novalnet_Helper::formatted_date()) );
				  GF_Novalnet_Helper::db_update(
							array(
								'status'      => $this->server_request ['tid_status'],
							),
							array(
								'entry_id' => $entry_id,
							)
						);
					$action['note']           = $callback_comments;
					$action['payment_status'] = $this->settings ['payment_on_hold_status'];
					$action['transaction_id'] = $this->server_request ['shop_tid'];
					$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
					gf_novalnet()->complete_payment( $this->gf_entry, $action );
				}
				elseif ( in_array( $this->server_request ['payment_type'], array( 'CREDITCARD', 'DIRECT_DEBIT_SEPA', 'PAYPAL', 'PRZELEWY24' ), true ) && ( (int) $this->order_reference ['paid_amount'] < (int) $this->order_reference ['transaction_amount'] ) ) {
					// Update order status.
					$action['note']           = $callback_comments;
					$action['payment_status'] = $this->settings ['payment_completion_status'];
					$action['transaction_id'] = $this->server_request ['shop_tid'];
					$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
					gf_novalnet()->complete_payment( $this->gf_entry, $action );
				}
				
				// Update transaction details.
				GF_Novalnet_Helper::db_update(
					array(
						'paid_amount' => ($this->server_request ['payment_type'] == 'INVOICE_START') ? 0 :$this->order_reference ['paid_amount'] + $this->server_request ['amount'],
						'status'      => $this->server_request ['tid_status'],
					),
					array(
						'entry_id' => $entry_id,
					)
				);

				// Log callback process.
				$this->log_callback_details( $entry_id );
				
				// send notification mail.
				$this->send_notification_mail( $callback_comments );
				
				// After execution.
				$this->display_message( $callback_comments );
			}
		

			// After execution.
			$this->display_message( 'Novalnet Callbackscript received. Payment type ( ' . $this->server_request ['payment_type'] . ' ) is not applicable for this process!' );
		}
	}

	/**
	 * Callback API Level 1 process.
	 *
	 * @since 2.0.0
	 */
	public function first_level_process() {

		$entry_id = $this->gf_entry['id'];

		if ( in_array( $this->server_request ['payment_type'], $this->chargebacks, true ) && $this->success_status ) {
			// Prepare callback comments.
			if ( in_array( $this->server_request ['payment_type'], array( 'PAYPAL_BOOKBACK', 'CREDITCARD_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'PRZELEWY24_REFUND', 'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK' ), true ) ) {
				/* translators: 1: Original TID 2: Amount 3: Date 4: TID */
				$comments = __( 'Novalnet callback received. Refund/Bookback executed successfully for the TID: %1$s amount: %2$s on %3$s. The subsequent TID: %4$s.', 'gravityforms-novalnet' );
			} else {
				/* translators: 1: Original TID 2: Amount 3: Date 4: TID */
				$comments = __( 'Novalnet callback received. Chargeback executed successfully for the TID: %1$s amount: %2$s on %3$s. The subsequent TID: %4$s.', 'gravityforms-novalnet' );
			}
			$callback_comments = GF_Novalnet_Helper::format_text( sprintf( $comments, $this->server_request ['shop_tid'], GFCommon::to_money( $this->server_request ['amount'] / 100 ), GF_Novalnet_Helper::formatted_date(), $this->server_request ['tid'] ) );

			$action['note'] = $callback_comments;
			gf_novalnet()->refund_payment( $this->gf_entry, $action );

			// Log callback process.
			$this->log_callback_details( $entry_id );
			
			// send notification mail.
			$this->send_notification_mail( $callback_comments );

			$this->display_message( $callback_comments );
		}
	}

	/**
	 * Callback API Level 2 process.
	 *
	 * @since 2.0.0
	 */
	public function second_level_process() {

		$entry_id             = $this->gf_entry['id'];

		if ( in_array( $this->server_request ['payment_type'], $this->collections, true ) && $this->success_status ) {

			/* translators: 1: Original TID 2: Amount 3: Date 4: TID */
			$callback_comments = GF_Novalnet_Helper::format_text( sprintf( __( 'Novalnet Callback Script executed successfully for the TID: %1$s with amount %2$s on %3$s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %4$s. ', 'gravityforms-novalnet' ), $this->server_request ['shop_tid'], ( GFCommon::to_money( $this->server_request ['amount'] / 100 ) ), GF_Novalnet_Helper::formatted_date(), $this->server_request ['tid'] ) );
			if ( in_array( $this->server_request ['payment_type'], array( 'INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT' ), true ) ) {

				if ( (int) $this->order_reference ['paid_amount'] < (int) $this->order_reference ['transaction_amount'] ) {

					// Calculate total amount.
					$paid_amount = $this->order_reference ['paid_amount'] + $this->server_request ['amount'];

					// Check for full payment.
					if ( (int) $paid_amount >= $this->order_reference ['transaction_amount'] ) {

						if ( 'ONLINE_TRANSFER_CREDIT' === $this->server_request ['payment_type'] ) {

							// Update callback comments.
							/* translators: 1: Amount 3: Date */
							$additional_comments = sprintf( __( 'The amount of %1$s for the order %2$s has been paid. Please verify received amount and TID details, and update the order status accordingly.', 'gravityforms-novalnet' ), GFCommon::to_money( $this->server_request ['amount'] / 100 ) , $this->server_request ['order_no']   );
							$action['note'] = $callback_comments . PHP_EOL . $additional_comments;
							$action['payment_status'] = $this->settings ['payment_completion_status'];
							$action['transaction_id'] = $this->server_request ['shop_tid'];
							$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
						    gf_novalnet()->complete_payment( $this->gf_entry, $action ); 
															
							// Update Callback amount.
							GF_Novalnet_Helper::db_update(
								array(
									'paid_amount' => $paid_amount,
								),
								array(
									'entry_id' => $entry_id,
								)
							);

							// Log callback process.
							$this->log_callback_details( $entry_id );
							$this->display_message( $callback_comments . PHP_EOL . $additional_comments);

						}
						// Update order comments.
						$action['note'] = $callback_comments;
						$action['transaction_id'] = $this->server_request ['shop_tid'];
						$action['payment_status'] = $this->settings ['payment_callback_status'];
						$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
						gf_novalnet()->complete_payment( $this->gf_entry, $action );

						// Update Callback amount.
						GF_Novalnet_Helper::db_update(
							array(
								'paid_amount' => $paid_amount,
							),
							array(
								'entry_id' => $entry_id,
							)
						);

						// Log callback process.
						$this->log_callback_details( $entry_id );
						
						// send notification mail.
						$this->send_notification_mail( $callback_comments );

						$this->display_message( $callback_comments );
					}

					$action['note']           = $callback_comments;
					$action['payment_status'] = $this->settings ['payment_completion_status'];
					$action['transaction_id'] = $this->server_request ['shop_tid'];
					$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
					gf_novalnet()->complete_payment( $this->gf_entry, $action );

					GF_Novalnet_Helper::db_update(
						array(
							'paid_amount' => $paid_amount,
						),
						array(
							'entry_id' => $entry_id,
						)
					);
					
					$this->log_callback_details( $entry_id );
					
					// send notification mail.
					$this->send_notification_mail( $callback_comments );
					
					$this->display_message( $callback_comments );
				}

				// After execution.
				$this->display_message( 'Novalnet callback script executed already' );
			} else {
				/* translators: 1: Original TID 2: Amount 3: Date 4: TID */
				$callback_comments = GF_Novalnet_Helper::format_text( sprintf( __( 'Novalnet Callback Script executed successfully for the TID: %1$s with amount %2$s on %3$s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %4$s', 'gravityforms-novalnet' ), $this->server_request ['shop_tid'], ( GFCommon::to_money( $this->server_request ['amount'] / 100 ) ), GF_Novalnet_Helper::formatted_date(), $this->server_request ['tid'] ) );

				$action['note']           = $callback_comments;
				$action['payment_status'] = $this->settings ['payment_completion_status'];
				$action['transaction_id'] = $this->server_request ['shop_tid'];
				$action['amount']		  = GFCommon::to_number( $this->server_request ['amount']/100 );
				gf_novalnet()->complete_payment( $this->gf_entry, $action );

				GF_Novalnet_Helper::db_update(
					array(
						'paid_amount' => $paid_amount,
					),
					array(
						'entry_id' => $entry_id,
					)
				);
				$this->send_notification_mail( $callback_comments );
				$this->log_callback_details( $entry_id );
				$this->display_message( $callback_comments );
			}
			// After execution.
			$this->display_message( 'Novalnet Callbackscript received. Payment type ( ' . $this->server_request ['payment_type'] . ' ) is not applicable for this process!' );
		}
	}

	/**
	 * Transaction cancellation process.
	 *
	 * @since 2.0.0
	 */
	public function transaction_cancellation() {
		$entry_id = $this->gf_entry['id'];
		if ( ( 'TRANSACTION_CANCELLATION' === $this->server_request ['payment_type'] && in_array( $this->order_reference['status'], array( '75', '91', '99', '98', '85'), true ) ) || ( 'PRZELEWY24' === $this->server_request ['payment_type'] && ! $this->success_status && '86' !== $this->order_reference ['tid_status'] ) ) { 

			/* translators: %s: date */
			$comments = GF_Novalnet_Helper::format_text( sprintf( __( 'Novalnet callback received. The transaction has been canceled on %s.', 'gravityforms-novalnet' ), GF_Novalnet_Helper::formatted_date() ) );
			$action['payment_status'] = $this->settings ['payment_cancel_status'];
			$action['note']           = $comments;
			gf_novalnet()->fail_payment( $this->gf_entry, $action );

			// Update gateway status.
			GF_Novalnet_Helper::db_update(
				array(
					'status' => $this->server_request['tid_status'],
				),
				array(
					'entry_id' => $entry_id,
				)
			);
			
			// send notification mail.
			$this->send_notification_mail( $comments );

			// Log callback process.
			$this->log_callback_details( $entry_id );
			
			$this->display_message( 'Novalnet callback received. The transaction has been canceled on ' . GF_Novalnet_Helper::formatted_date() );
		}
	}

	/**
	 * Update / initialize the payment.
	 *
	 * @since 2.0.0
	 * @param int   $entry_id              The order id of the processing order.
	 * @param array $communication_failure Check for communication failure process.
	 */
	public function update_initial_payment( $entry_id, $communication_failure ) {

		$entry = GFFormsModel::get_lead( $entry_id );

		$form   = GFAPI::get_form( $entry['form_id'] );
		$action = array();

		// Forming comments.
		$transaction_comments = GF_Novalnet_Helper::form_payment_comments( $this->server_request, '' );

		gform_update_meta( $entry_id, '_novalnet_transaction_comments', $transaction_comments );
		$this->server_request['amount'] = $this->server_request['amount']/100;
		gf_novalnet()->handle_payment_status( $this->server_request, $entry, $this->settings, $action );

		gf_novalnet()->transaction_post_process( $entry, $form, $this->settings );
		if ( $this->success_status ) {

			if ( ! class_exists( 'GFFormDisplay' ) ) {
				require_once GFCommon::get_base_path() . '/form_display.php';
			}
			GFFormDisplay::handle_confirmation( $form, $entry, false );
		}
		if ( $communication_failure ) {
			// send notification mail.
			$this->send_notification_mail( $transaction_comments );
			$this->display_message( $transaction_comments );
		}
	}

	/**
	 * Log callback process.
	 *
	 * @since 2.0.0
	 *
	 * @param int $entry_id The post id of the processing entry.
	 */
	public function log_callback_details( $entry_id ) {

		GF_Novalnet_Helper::db_insert(
			array(
				'payment_type' => $this->server_request ['payment_type'],
				'status'       => $this->server_request ['status'],
				'callback_tid' => $this->server_request ['tid'],
				'original_tid' => $this->server_request ['shop_tid'],
				'amount'       => $this->server_request ['amount'],
				'entry_id'     => $entry_id,
			),
			'novalnet_callback_history'
		);
	}

	/**
	 * Display the callback messages.
	 *
	 * @since 2.0.0
	 * @param string $message Message for the executed process.
	 * @param int    $entry_id get current order number.
	 */
	public function display_message( $message, $entry_id = '' ) {
		if ( empty( $entry_id ) ) {
			wp_die(
				wp_kses( 'message= ' . $message, array() ),
				'Novalnet Callback',
				array(
					'response' => '200',
				)
			);
		}
		wp_die(
			wp_kses( 'message= ' . $message . '&order_no=' . $entry_id, array() ),
			'Novalnet Callback',
			array(
				'response' => '200',
			)
		);
	}
	
	/**
	 * Send notification mail.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message The message to send in mail.
	 */
	public function send_notification_mail( $message ) {

			if($this->settings['callback_email'] == 1) {
				GFCommon::send_email( '', $this->settings['callback_email_to'], $this->settings['callback_email_bcc'], '', get_option( 'blogname' ).' Novalnet Callback Script Access Report - GravityForms', $message, $from_name = '', 'text', '', false, false, null);
				return;
			}
			return;
    }
  
  /**
   * To form guarantee payment order confirmation mail.
   *
   * @param string $comments
   *   The order related information.
   */
  public function guaranteeOrderConfirmationMail($comments) {
     global $wpdb;
     $result = $wpdb->get_row( $wpdb->prepare( "SELECT email FROM {$wpdb->prefix}novalnet_transaction_details WHERE tid=%s", $this->server_request ['tid'] ), ARRAY_A );
    $message = '<body style="background:#F6F6F6; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px; margin:0; padding:0;"><div style="width:55%;height:auto;margin: 0 auto;background:rgb(247, 247, 247);border: 2px solid rgb(223, 216, 216);border-radius: 5px;box-shadow: 1px 7px 10px -2px #ccc;"><div style="min-height: 300px;padding:20px;"><b>Dear Mr./Ms. ' . $this->server_request['firstname'].' ' .$this->server_request ['lastname']. '<br><br>';
    $message .= __('We are pleased to inform you that your order has been confirmed.', 'gravityforms-novalnet');
    $message .= '<br><br><b>Payment Information:</b><br>' . $comments . '</div><div style="width:100%;height:20px;background:#00669D;"></div></div></body>';

    $subject = sprintf( __('Order Confirmation - Your Order %1$s with %2$s_name has been confirmed!', 'gravityforms-novalnet' ), $this->server_request ['order_no'], get_option( 'blogname' ) );
    GFCommon::send_email( '', $result['email'], $this->settings['callback_email_bcc'], '', $subject, $message, $from_name = '', 'html', '', false, false, null);
  }  
}
