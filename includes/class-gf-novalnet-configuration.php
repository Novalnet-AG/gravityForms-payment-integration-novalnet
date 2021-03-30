<?php
/**
 * Novalnet Configuration Class
 *
 * @author   Novalnet AG
 * @category Admin
 * @package  gravityforms-novalnet
 * @version  2.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GF_Novalnet_Configuration
 */
class GF_Novalnet_Configuration {

	/**
	 * The URL to load the vendor details.
	 *
	 * @var string
	 */
	static protected $_api_config_endpoint = 'https://payport.novalnet.de/autoconfig';

	/**
	 * Add a link to this plugin's settings page
	 *
	 * @since 2.0.0
	 *
	 * @param string $links The existing links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . gf_novalnet()->get_plugin_settings_url() . '">' . __( 'Settings', 'gravityforms-novalnet' ) . '</a>',
			),
			$links
		);
	}

	/**
	 * Handle config hash call
	 *
	 * @since 2.0.0
	 */
	public function send_config_hash_call() {

		$error = '';
		if ( ! empty( gf_novalnet()->request ['novalnet_api_key'] ) ) {
			$request  = array(
				'lang' => GF_Novalnet_Helper::get_language(),
				'hash' => trim( gf_novalnet()->request ['novalnet_api_key'] ),
			);
			$response = GF_Novalnet_Helper::server_request( $request, self::$_api_config_endpoint );

			$result = json_decode( $response );

			if ( ! empty( $result->status ) && '100' === $result->status ) {
					wp_send_json_success( $result );
			} else {

				if ( '106' === $result->status ) {
					/* translators: %s: Server Address */
					$error = sprintf( __( 'You need to configure your outgoing server IP address ( %s ) at Novalnet. Please configure it in Novalnet admin portal or contact technic@novalnet.de', 'gravityforms-novalnet' ), GF_Novalnet_Helper::get_server_address() );
				} else {
					$error = $result->config_result;
				}
			}
		} else {
			$error = __( 'Please fill mandatory details', 'gravityforms-novalnet' );

		}

		wp_send_json_error(
			array(
				'error' => $error,
			)
		);
	}

	/**
	 * The function to specify the settings fields to be rendered on the plugin settings page
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function plugin_settings_fields() {
		
		$status_choices = array();
		foreach ( self::get_entry_payment_statuses() as $id => $text ) {
			$status_choices [] = array(
				'label' => $text,
				'name'  => $id,
			);
		}

		return array(
			array(
			
				'title'  => esc_html__( 'Novalnet Global Configuration', 'gravityforms-novalnet' ),
				'description'  => sprintf( esc_html__( 'For additional configurations login to %sNovalnet administration portal%s. To login to the Portal you need to have an account at Novalnet. If you don\'t have one yet, please contact %ssales@novalnet.de%s / tel. +49 (089) 923068320', 'gravityforms-novalnet' ), '<a href="https://admin.novalnet.de/" target="_new">', '</a>', '<a href="mailto:sales@novalnet.de">', '</a>' ),

				'fields' => array(
					array(
						'name'    => 'test_mode',
						'tooltip' => esc_html__( 'The payment will be processed in the test mode therefore amount for this transaction will not be charged.', 'gravityforms-novalnet' ),
						'label'   => esc_html__( 'Enable test mode', 'gravityforms-novalnet' ),
						'type'    => 'checkbox',
						'class'   => 'small',
						'choices' => array(
							array(
								'label' => '',
								'name'  => 'test_mode',
							),
						),
					),
					array(
						'name'              => 'novalnet_public_key',
						'description'      => sprintf( __( '<small>To get the Product Activation Key, go to <a href="https://admin.novalnet.de/" target="blank">Novalnet administration portal</a> - <strong>PROJECTS</strong>: Project Information - <strong>Shop Parameters</strong>: <strong>API Signature (Product activation key)</strong></small>.', 'gravityforms-novalnet' ) ),
						'tooltip'           => esc_html__( 'Enter Novalnet Product activation key', 'gravityforms-novalnet' ),
						'label'             => esc_html__( 'Product activation key', 'gravityforms-novalnet' ),
						'type'              => 'text',
						'class'             => 'medium',
						'required'          => true,
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_string' ),
					),
					array(
						'name'              => 'novalnet_vendor',
						'tooltip'           => esc_html__( 'Enter Novalnet merchant ID', 'gravityforms-novalnet' ),
						'label'             => esc_html__( 'Merchant ID', 'gravityforms-novalnet' ),
						'type'              => 'hidden',
						'class'             => 'small',
						'required'          => true,
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_digit' ),
					),
					array(
						'name'              => 'novalnet_auth_code',
						'tooltip'           => esc_html__( 'Enter Novalnet authentication code.', 'gravityforms-novalnet' ),
						'label'             => esc_html__( 'Authentication code', 'gravityforms-novalnet' ),
						'type'              => 'hidden',
						'class'             => 'medium',
						'required'          => true,
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_string' ),
					),
					array(
						'name'              => 'novalnet_product',
						'tooltip'           => esc_html__( 'Enter Novalnet project ID.', 'gravityforms-novalnet' ),
						'label'             => esc_html__( 'Project ID', 'gravityforms-novalnet' ),
						'type'              => 'hidden',
						'class'             => 'small',
						'required'          => true,
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_digit' ),
					),
					array(
						'name'              => 'novalnet_tariff',
						'tooltip'           => esc_html__( 'Select Novalnet tariff ID.', 'gravityforms-novalnet' ),
						'label'             => esc_html__( 'Tariff ID', 'gravityforms-novalnet' ),
						'type'              => 'text',
						'required'          => true,
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_digit' ),
					),
					array(
						'name'              => 'novalnet_payment_access_key',
						'tooltip'           => esc_html__( 'Enter the Novalnet payment access key.', 'gravityforms-novalnet' ),
						'label'             => esc_html__( 'Payment Access Key', 'gravityforms-novalnet' ),
						'type'              => 'hidden',
						'class'             => 'medium',
						'required'          => true,
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_string' ),
					),
					array(
						'name'    => 'referrer_id',
						'tooltip' => esc_html__( 'Enter the referrer ID of the person/company who recommended you Novalnet.', 'gravityforms-novalnet' ),
						'label'   => esc_html__( 'Referrer ID', 'gravityforms-novalnet' ),
						'type'    => 'text',
						'class'   => 'medium',
						'feedback_callback' => array( 'GF_Novalnet_Helper', 'is_valid_digit' ),
					),
					array(
						'name'     => 'transaction_type',
						'tooltip'  => '',
						'label'    => esc_html__( 'On-hold Payment Action', 'gravityforms-novalnet' ),
						'type'     => 'select',
						'class'    => 'small',
						'default_value' => 'capture',
						'onchange' => "jQuery(this).parents('form').submit();",
						'choices'  => array(
							array(
								'label' => esc_html__('Capture', 'gravityforms-novalnet'),
								'value' => 'capture',
							),
							array(
								'label' => esc_html__('Authorize', 'gravityforms-novalnet'),
								'value' => 'authorize',
							),
						),
					),
					array(
						'name'       => 'on_hold_limit',
						'description'      => esc_html__( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'gravityforms-novalnet' ),
						'tooltip'    => esc_html__( 'In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction', 'gravityforms-novalnet' ),
						'label'      => esc_html__( 'Set a limit for on-hold transaction', 'gravityforms-novalnet' ),
						'type'       => 'text',
						'class'      => 'small',
						'dependency' => array(
							'field'  => 'transaction_type',
							'values' => array( 'authorize' ),
						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Credit Card', 'gravityforms-novalnet' ),
				'fields' => array(
					array(
						'name'    => 'cc_3d',
						'tooltip' => esc_html__( 'The 3D-Secure will be activated for credit cards. The issuing bank prompts the buyer for a password what, in turn, help to prevent a fraudulent payment. It can be used by the issuing bank as evidence that the buyer is indeed their card holder. This is intended to help decrease a risk of charge-back.', 'gravityforms-novalnet' ),
						'label'   => esc_html__( 'Enable 3D secure', 'gravityforms-novalnet' ),
						'type'    => 'checkbox',
						'class'   => 'small',
						'choices' => array(
							array(
								'label' => '',
								'name'  => 'cc_3d',
							),
						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Direct Debit Sepa', 'gravityforms-novalnet' ),
				'fields' => array(
					array(
						'name'       => 'sepa_due_date',
						'tooltip'    => esc_html__( ' Enter the number of days after which the payment should be processed (must be between 2 and 14 days)', 'gravityforms-novalnet' ),
						'label'      => esc_html__( 'SEPA payment duration (in days)', 'gravityforms-novalnet' ),
						'type'       => 'text',
						'class'      => 'small',
						'input_type' => 'number',
					),
				),
			),
			array(
				'title'  => esc_html__( 'Invoice', 'gravityforms-novalnet' ),
				'fields' => array(
					array(
						'name'       => 'due_date',
						'tooltip'    => esc_html__( 'Enter the number of days to transfer the payment amount to Novalnet (must be greater than 7 days). In case if the field is empty, 14 days will be set as due date by default', 'gravityforms-novalnet' ),
						'label'      => esc_html__( 'Payment due date (in days)', 'gravityforms-novalnet' ),
						'type'       => 'text',
						'class'      => 'small',
						'input_type' => 'number',
					),
				),
			),
			array(
				'title'  => esc_html__( 'Barzahlen', 'gravityforms-novalnet' ),
				'fields' => array(
					array(
						'name'       => 'slip_expiry_date',
						'tooltip'    => esc_html__( 'Enter the number of days to pay the amount at store near you. If the field is empty, 14 days will be set as default.', 'gravityforms-novalnet' ),
						'label'      => esc_html__( 'Slip expiry date (in days)', 'gravityforms-novalnet' ),
						'type'       => 'text',
						'class'      => 'small',
						'input_type' => 'number',
					),
				),
			),
			array(
				'title'  => esc_html__( 'Order status management', 'gravityforms-novalnet' ),
				'fields' => array(
					array(
						'name'          => 'payment_completion_status',
						'tooltip'       => '',
						'label'         => esc_html__( 'Order completion status', 'gravityforms-novalnet' ),
						'type'          => 'select',
						'class'         => 'small',
						'default_value' => 'Processing',
						'choices'       => $status_choices,
					),
					array(
						'name'          => 'payment_on_hold_status',
						'tooltip'       => '',
						'label'         => esc_html__( 'Onhold order status', 'gravityforms-novalnet' ),
						'type'          => 'select',
						'class'         => 'small',
						'default_value' => 'Authorize',
						'choices'       => $status_choices,
					),
					array(
						'name'          => 'payment_pending_status',
						'tooltip'       => '',
						'label'         => esc_html__( 'Order status for the pending payment', 'gravityforms-novalnet' ),
						'type'          => 'select',
						'class'         => 'small',
						'default_value' => 'Pending',
						'choices'       => $status_choices,
					),
					array(
						'name'          => 'payment_callback_status',
						'tooltip'       => '',
						'label'         => esc_html__( 'Callback order status', 'gravityforms-novalnet' ),
						'type'          => 'select',
						'class'         => 'small',
						'default_value' => 'Paid',
						'choices'       => $status_choices,
					),
					array(
						'name'          => 'payment_cancel_status',
						'tooltip'       => '',
						'label'         => esc_html__( 'Cancellation order status', 'gravityforms-novalnet' ),
						'type'          => 'select',
						'class'         => 'small',
						'default_value' => 'Cancelled',
						'choices'       => $status_choices,
					),
				),
			),
			array(
				'title'  => esc_html__( 'Merchant script management', 'gravityforms-novalnet' ),
				'fields' => array(
					array(
						'name'    => 'callback_test_mode',
						'tooltip' => 'This option will allow performing a manual execution. Please disable this option before setting your shop to LIVE mode, to avoid unauthorized calls from external parties (excl. Novalnet).',
						'label'   => esc_html__( 'Deactivate IP address control (for test purpose only)', 'gravityforms-novalnet' ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'label' => '',
								'name'  => 'callback_test_mode',
							),
						),
					),
					array(
						'name'    => 'callback_email',
						'tooltip' => '',
						'label'   => esc_html__( 'Enable E-mail notification for callback', 'gravityforms-novalnet' ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'label' => '',
								'name'  => 'callback_email',
							),
						),
					),
					array(
						'name'          => 'callback_email_to',
						'tooltip'       => esc_html__( 'E-mail address of the recipient', 'gravityforms-novalnet' ),
						'default_value' => get_bloginfo( 'admin_email' ),
						'label'         => esc_html__( 'E-mail address (To)', 'gravityforms-novalnet' ),
						'type'          => 'text',
						'class'         => 'medium',
					),
					array(
						'name'    => 'callback_email_bcc',
						'tooltip' => esc_html__( 'E-mail address of the recipient for BCC', 'gravityforms-novalnet' ),
						'label'   => esc_html__( 'E-mail address (Bcc)', 'gravityforms-novalnet' ),
						'type'    => 'text',
						'class'   => 'medium',
					),
				   array(
						'name'    => 'notification_url',
						'tooltip' => esc_html__( 'The notification URL is used to keep your database/system actual and synchronizes with the Novalnet transaction status.', 'gravityforms-novalnet' ),
						'label'   => esc_html__( 'Notification URL', 'gravityforms-novalnet' ),
						'type'    => 'text',
						'class'   => 'medium',
						'value'   => esc_url( add_query_arg( 'page', 'gf_novalnet_callback', get_bloginfo( 'url' ) . '/' ) ),
					),
				),
			),
		);
	}

	/**
	 * Return the scripts which should be enqueued.
	 * 
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function add_scripts() {
		$scripts = array(
			array(
				'handle'  => 'gf_novalnet_admin',
				'src'     => plugins_url( null, gf_novalnet()->_full_path ) . '/js/novalnet-admin.js',
				'version' => GF_NOVALNET_VERSION,
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
					),
				),
			),
		);

		return $scripts;
	}
	
	/**
	 * Returns an array of supported entry payment statuses.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public static function get_entry_payment_statuses() {
		$payment_statuses = array(
			'Authorized' => esc_html__( 'Authorized', 'gravityforms' ),
			'Paid'       => esc_html__( 'Paid', 'gravityforms' ),
			'Processing' => esc_html__( 'Processing', 'gravityforms' ),
			'Failed'     => esc_html__( 'Failed', 'gravityforms' ),
			'Active'     => esc_html__( 'Active', 'gravityforms' ),
			'Cancelled'  => esc_html__( 'Cancelled', 'gravityforms' ),
			'Pending'    => esc_html__( 'Pending', 'gravityforms' ),
			'Refunded'   => esc_html__( 'Refunded', 'gravityforms' ),
			'Voided'     => esc_html__( 'Voided', 'gravityforms' ),
		);

		/**
		 * Allow custom payment statuses to be defined.
		 *
		 * @since 2.0.0
		 *
		 * @param array $payment_statuses An array of entry payment statuses with the entry value as the key (15 char max) to the text for display.
		 */
		$payment_statuses = apply_filters( 'gform_payment_statuses', $payment_statuses );

		return $payment_statuses;
	}
}
