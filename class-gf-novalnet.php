<?php
/**
 * Novalnet Class
 *
 * @author   Novalnet
 * @category Class
 * @package  gravity-forms-novalnet
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GF_Novalnet
 */
class GF_Novalnet extends GFPaymentAddOn {

	/**
	 * Version number of the Add-On
	 *
	 * @var string
	 */
	protected $_version = GF_NOVALNET_VERSION;

	/**
	 * Gravity Forms minimum version requirement
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '1.9';

	/**
	 * URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 *
	 * @var string
	 */
	protected $_slug = 'gravityforms-novalnet';

	/**
	 * Relative path to the plugin from the plugins folder.
	 *
	 * @var string
	 */
	protected $_path = 'gravity-forms-novalnet/novalnet.php';

	/**
	 * Full path the the plugin.
	 *
	 * @var string
	 */
	public $_full_path = __FILE__;

	/**
	 * URL to the Gravity Forms website.
	 *
	 * @var string
	 */
	protected $_url = 'https://www.novalnet.de';

	/**
	 * Title of the plugin to be used on the settings page, form settings and plugins page.
	 *
	 * @var string
	 */
	protected $_title = 'Gravity Forms Novalnet Add-on';

	/**
	 * Short version of the plugin title to be used on menus and other places where a less verbose string is useful.
	 *
	 * @var string
	 */
	protected $_short_title = 'Novalnet';

	/**
	 * System name to be sent in request to Novalnet
	 *
	 * @var string
	 */
	public $_system_name = 'gravityforms-novalnet';

	/**
	 * The add-on supports callbacks.
	 *
	 * @var bool
	 */
	public $_supports_callbacks = true;

	/**
	 * Payment completed status of Novalnet.
	 *
	 * @var array
	 */
	protected $_payment_complete_status = array(
		'100',
	);

	/**
	 * Payment pending status of Novalnet.
	 *
	 * @var array
	 */
	protected $_payment_pending_status = array(
		'75',
		'86',
		'90',
	);

	/**
	 * Payment on-hold status of Novalnet.
	 *
	 * @var array
	 */
	protected $_payment_on_hold_status = array(
		'85',
		'91',
		'98',
		'99',
	);

	/**
	 * Redirect Payment type of Novalnet.
	 *
	 * @var array
	 */
	protected $_redirect_payment_types = array(
		'PAYPAL',
		'IDEAL',
		'ONLINE_TRANSFER',
		'GIROPAY',
		'EPS',
		'PRZELEWY24',
	);

	/**
	 * The request data.
	 *
	 * @var array
	 */
	public $request = array();

	/**
	 * The single instance of the class.
	 *
	 * @var GF_Novalnet
	 */
	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GF_Novalnet
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 *
	 * @since 2.0.0s
	 */
	public function init() {
		parent::init();

		$this->request = $_REQUEST; // CSRF ok, Input var okay.

		include_once 'includes/class-gf-novalnet-setup.php';
		include_once 'includes/class-gf-novalnet-helper.php';
		include_once 'includes/class-gf-novalnet-configuration.php';

		$this->load_text_domain();

		add_filter( 'plugin_action_links_' . plugin_basename( GF_NOVALNET_FILE ), array( 'GF_Novalnet_Configuration', 'plugin_action_links' ) );

		add_action( 'admin_init', array( 'GF_Novalnet_Setup', 'install' ) );
		register_deactivation_hook( GF_NOVALNET_FILE, array( 'GF_Novalnet_Setup', 'uninstall' ) );

		add_filter( 'gform_form_tag', array( $this, 'show_error_message' ), 10);

		add_filter( 'wp_ajax_get_novalnet_vendor_details', array( 'GF_novalnet_Configuration', 'send_config_hash_call' ) );

		add_filter( 'gform_replace_merge_tags', array( $this, 'add_transaction_info' ), 10, 7 );

		add_filter( 'gform_disable_notification', array( $this, 'delay_instant_notification' ), 10, 2 );
		add_filter( 'gform_custom_merge_tags', array( $this, 'add_transaction_notes_short_code' ) );

		add_action( 'gform_payment_details', array( $this, 'show_payment_details' ), 10, 2 );

	}

	/**
	 * Called when the user chooses to uninstall the Add-On  - after permissions have been checked and before removing
	 * all Add-On settings and Form settings.
	 *
	 * @since 2.0.0
	 */
	public function uninstall() {
		parent::uninstall();
		return GF_Novalnet_Setup::uninstall();
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		return array_merge( parent::scripts(), GF_Novalnet_Configuration::add_scripts() );
	}

	/**
	 * Check for valid URL call
	 *
	 * @since 2.0.0
	 */
	public function is_callback_valid() {
		if ( ! in_array( rgget( 'page' ), array( 'gf_novalnet_response', 'gf_novalnet_callback' ), true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check for response from the Novalnet server.
	 *
	 * @since 2.0.0
	 */
	public function callback() {
		if ( 'gf_novalnet_response' === rgget( 'page' ) ) {
			$this->handle_post_process();
		} elseif ( 'gf_novalnet_callback' === rgget( 'page' ) ) {
			include_once 'includes/class-gf-novalnet-callback.php';
			$callback = new GF_Novalnet_Callback();
			$callback->callback_api_process();
		}
	}

	/**
	 * Handles the callback process.
	 *
	 * @since 2.0.0
	 */
	public function maybe_process_callback() {

		// Ignoring requests that are not this addon's callbacks.
		if ( ! $this->is_callback_valid() ) {
			return;
		}
		$callback_action = $this->callback();
		$result          = true;
		$this->post_callback( $callback_action, $result );
	}

	/**
	 * Handles the after callback process.
	 *
	 * @since 2.0.0
	 * @param array $callback_action The performed action.
	 * @param array $callback_result The result of the action.
	 *
	 * @return bool
	 */
	public function post_callback( $callback_action, $callback_result ) {
		if ( is_wp_error( $callback_action ) || ! $callback_action ) {
			return false;
		}
		return true;
	}

	/**
	 * Handles the reposne form the Novalnet server.
	 *
	 * @since 2.0.0
	 */
	public function handle_post_process() {
		self::get_instance();

		if ( rgget( 'gf_novalnet_return' ) ) {

			$str = base64_decode( rgget( 'gf_novalnet_return' ) );

			parse_str( $str, $query );

			if ( wp_hash( 'entry_id=' . $query['entry_id'] ) === $query['hash'] ) {

				list( $form_id, $entry_id ) = explode( '|', $query['entry_id'] );

				$form     = GFAPI::get_form( $form_id );
				$entry    = GFAPI::get_entry( $entry_id );
				$settings = $this->get_plugin_settings();
				$action   = array();
				$success_status = in_array( rgpost( 'status' ), array( '100', '90' ), true );

				if ( in_array( rgpost( 'payment_type' ), $this->_redirect_payment_types, true ) || rgpost( 'cc_3d' ) ) {
					GF_Novalnet_Helper::decrypt_data( $this->request, $settings['novalnet_payment_access_key'], rgpost( 'uniqid' ) );
				}
				$transaction_comments = GF_Novalnet_Helper::form_payment_comments( $this->request, $settings['novalnet_product'] );
				gform_update_meta( $entry_id, '_novalnet_transaction_comments', $transaction_comments );
				$this->handle_payment_status( $this->request, $entry, $settings, $action );
				$message ='';
				if ( $success_status ) {
					
					$message = __('Dear client,', 'gravityforms-novalnet') . PHP_EOL . PHP_EOL;
					$message .= sprintf(__('We would like to inform you that test order (%s) has been placed in your shop recently. Please make sure your project is in LIVE mode at Novalnet administration portal and Novalnet payments are enabled in your shop system. Please ignore this email if the order has been placed by you for testing purpose.' , 'gravityforms-novalnet'), $entry_id). PHP_EOL . PHP_EOL;
					$message .= __('Regards', 'gravityforms-novalnet') . PHP_EOL; 
					$message .= __('Novalnet AG', 'gravityforms-novalnet'); 
					
					$this->transaction_post_process( $entry, $form, $settings );
					if ( ! class_exists( 'GFFormDisplay' ) ) {
						require_once GFCommon::get_base_path() . '/form_display.php';
					}
					$result = GFFormDisplay::handle_confirmation( $form, $entry, false );
					if ( is_array( $result ) && isset( $result['redirect'] ) ) {
						$redirect_url = $result['redirect'];
					}
				} else {
					$this->transaction_post_process( $entry, $form, $settings );
					$redirect_url = explode( '?', GF_Novalnet_Helper::return_url( true ) );
					$redirect_url = $redirect_url['0'];
					$redirect_url = add_query_arg( 'gf_novalnet_error', rawurlencode( GF_Novalnet_Helper::get_status_description( $this->request ) ), $redirect_url );
				}
				if ( ! empty( $redirect_url ) ) {
					wp_safe_redirect( $redirect_url );
					exit();
				}
				GFFormDisplay::$submission[ $form_id ] = array(
					'is_confirmation'      => true,
					'confirmation_message' => nl2br( $result ),
					'form'                 => $form,
					'lead'                 => $entry,
				);

			}
		}
	}

	/**
	 * Handles the payment status update process.
	 *
	 * @since  2.0.0
	 *
	 * @param array $request  The request data.
	 * @param array $entry    Current entry array containing entry information (i.e data submitted by users).
	 * @param array $settings The plugin settings.
	 * @param array $action   The actions to be performed.
	 */
	public function handle_payment_status( $request, $entry, $settings, &$action ) {
		$payment_name = GF_Novalnet_Helper::form_payment_name( $request );
		$currency_code = '';
		$action['transaction_type'] = 'payment';
		$action['currency'] = rgar( $request, 'currency' );
		$action['transaction_id']   = rgar( $request, 'tid' );
		$action['amount_formatted'] = rgar( $request, 'amount' );
		$action['amount']           = GFCommon::to_number( $action['amount_formatted'] );
		$action['payment_amount']   = $action['amount'];
		$action['amount_formatted'] = GFCommon::to_money( $action['amount'], $action['currency'] );
		$this->_short_title         = $payment_name['payment_type'];
		if ( in_array( rgar( $request, 'tid_status' ), $this->_payment_complete_status, true ) ) {
			$action['payment_status'] = $settings['payment_completion_status'];
			$this->complete_payment( $entry, $action );
		} elseif ( in_array( rgar( $request, 'tid_status' ), $this->_payment_pending_status, true ) ) {
			$action['payment_status'] = $settings['payment_pending_status'];
			$this->add_pending_payment( $entry, $action );
		} elseif ( in_array( rgar( $request, 'tid_status' ), $this->_payment_on_hold_status, true ) ) {
			$action['payment_status'] = $settings['payment_on_hold_status'];
			$this->complete_authorization( $entry, $action );
		}
		else {
			$action ['payment_status'] = $settings['payment_cancel_status'];
			$this->fail_payment( $entry, $action );
		}
	}


	/**
	 * Handles the transaction post process.
	 *
	 * @since  2.0.0
	 *
	 * @param array $entry    Current entry array containing entry information (i.e data submitted by users).
	 * @param array $form     Current form array containing all form settings.
	 * @param array $settings The plugin settings.
	 */
	public function transaction_post_process( $entry, $form, $settings ) {

		if ( ! empty( $form ['notifications'] ) ) {
			foreach ( $form ['notifications'] as $notification ) {
				if ( rgar( $notification, 'event' ) === 'form_submission' ) {
					$notifications_to_send [] = $notification['id'];
				}
			}
			GFCommon::send_notifications( $notifications_to_send, $form, $entry, true, 'form_submission' );
		}

		$vendor_details = array();
		GF_Novalnet_Helper::form_vendor_parameters( $settings, $vendor_details );
		$payment_method = GF_Novalnet_Helper::form_payment_name( $this->request );
		$insert_data                = array(
			'vendor_details'     => wp_json_encode( $vendor_details ),
			'entry_id'           => $entry['id'],
			'status'             => rgpost( 'tid_status' ),
			'transaction_amount' => GF_Novalnet_Helper::formatted_amount( GFCommon::get_order_total( $form, $entry ) ),
			'refunded_amount'    => 0,
			'payment_type'       => rgpost( 'payment_type' ),
			'payment_method'     => $payment_method['payment_method'],
			'email'              => rgpost( 'email' ),
			'date'               => date( 'Y-m-d H:i:s' ),
			'tid'                => rgpost( 'tid' ),
		);
		$insert_data['paid_amount'] = ( in_array( $insert_data ['status'], $this->_payment_complete_status, true ) && ! in_array( rgpost( 'payment_type' ), array( 'INVOICE_START', 'CASHPAYMENT' ), true ) ) ? $insert_data ['transaction_amount'] : 0;

		GF_Novalnet_Helper::db_insert( $insert_data );
	}

	/**
	 * Disable the instant notification since the payment not success yet.
	 *
	 * @since  2.0.0
	 *
	 * @param boolean $is_disabled            Active payment feed containing all the configuration data.
	 * @param array   $notification The current notification object.
	 *
	 * @return boolean
	 */
	public function delay_instant_notification( $is_disabled, $notification ) {

		if ( rgar( $notification, 'event' ) === 'form_submission' ) {

			return true;
		}
		return $is_disabled;
	}

	/**
	 * Handles the formation of payment parameters and the URL.
	 *
	 * @since  2.0.0
	 *
	 * @param array $feed            Active payment feed containing all the configuration data.
	 * @param array $submission_data Contains form field data submitted by the user as well as payment information (i.e. payment amount, setup fee, line items, etc...).
	 * @param array $form            Current form array containing all form settings.
	 * @param array $entry           Current entry array containing entry information (i.e data submitted by users).
	 *
	 * @return string
	 */
	public function redirect_url( $feed, $submission_data, $form, $entry ) {
		$parameters = array();
		$settings   = $this->get_plugin_settings();

		$customer_data = GF_Novalnet_Helper::get_customer_data( $feed, $entry, $form );

		GF_Novalnet_Helper::form_vendor_parameters( $settings, $parameters );

		GF_Novalnet_Helper::form_customer_parameters( $customer_data, $parameters );

		GF_Novalnet_Helper::form_order_parameters( $entry, $form, $parameters, $settings['novalnet_product'] );

		GF_Novalnet_Helper::form_payment_parameters( $settings, $parameters );

		GF_Novalnet_Helper::form_redirection_parameters( $entry, $settings, $parameters );

		GF_Novalnet_Helper::form_system_parameters( $parameters );

		$parameters = array_filter( $parameters );
		$response = GF_Novalnet_Helper::server_request( $parameters );
		wp_parse_str( $response, $response );
		if ( ! empty( $response['status'] ) && '100' === $response['status'] && ! empty( $response['url'] ) ) {
			return $response['url'];
		} else {
			$redirect_url = explode( '?', GF_Novalnet_Helper::return_url( true ) );
			$redirect_url = $redirect_url['0'];
			$redirect_url = add_query_arg( 'gf_novalnet_error', rawurlencode( GF_Novalnet_Helper::get_status_description( $response ) ), $redirect_url );
			if ( ! empty( $redirect_url ) ) {
				wp_safe_redirect( $redirect_url );
				exit();
			}
		}
	}

	/**
	 * Build Plugins settings field.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return GF_Novalnet_Configuration::plugin_settings_fields();
	}

	/**
	 * Configures the settings which should be rendered on the Form.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		$default_settings = parent::feed_settings_fields();
		
		//--get billing info section and add customer first/last name
		$billing_info   = parent::get_field( 'billingInformation', $default_settings );
		$billing_fields = $billing_info['field_map'];
		$add_first_name = true;
		$add_last_name  = true;
		foreach ( $billing_fields as $mapping ) {
			//add first/last name if it does not already exist in billing fields
			if ( $mapping['name'] == 'firstName' ) {
				$add_first_name = false;
			} else if ( $mapping['name'] == 'lastName' ) {
				$add_last_name = false;
			}
		}

		if ( $add_last_name ) {
			//add last name
			array_unshift( $billing_info['field_map'], array( 'name' => 'lastName', 'label' => esc_html__( 'Last Name', 'gravityforms-novalnet' ), 'required' => false ) );
		}
		if ( $add_first_name ) {
			array_unshift( $billing_info['field_map'], array( 'name' => 'firstName', 'label' => esc_html__( 'First Name', 'gravityforms-novalnet' ), 'required' => false ) );
		}
		$default_settings = parent::replace_field( 'billingInformation', $billing_info, $default_settings );

		return apply_filters( 'novalnet_feed_settings_fields', $default_settings, $this->get_current_form() );
	}

	/**
	 * Add Novalnet transaction info in mail.
	 *
	 * @since 2.0.0
	 * @param array $tags The existing tags.
	 *
	 * @return array
	 */
	public function add_transaction_notes_short_code( $tags ) {
		$tags [] = array(
			'tag'   => '{novalnet_transaction_notes}',
			'label' => esc_html__( 'Novalnet Transaction Notes', 'gravityforms-novalnet' ),
		);
		return $tags;
	}

	/**
	 * Add Novalnet transaction info in mail.
	 *
	 * @since 2.0.0
	 * @param string $text       The existing mail content.
	 * @param array  $form       Current form array containing all form settings.
	 * @param array  $entry      Current entry array containing entry information (i.e data submitted by users).
	 * @param string $url_encode Need to process url encoding.
	 * @param string $esc_html   Need to escape the html.
	 * @param string $nl2br      Need to convert new line to break.
	 * @param string $format     The mail format.
	 *
	 * @return string
	 */
	public function add_transaction_info( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		if ( empty( $form ) || empty( $entry ) ) {
			return $text;
		}

		$transaction_details = gform_get_meta( $entry['id'], '_novalnet_transaction_comments', true );

		if ( $transaction_details ) {

			if ( 'html' === $format && ! $esc_html ) {
				$transaction_details = GFCommon::format_variable_value( $transaction_details, $url_encode, $esc_html, $format );
			}
			$text = str_replace( '{novalnet_transaction_notes}', $transaction_details, $text );

		}

		return $text;
	}

	/**
	 * Shows the payment details in Admin meta.
	 *
	 * @since 2.0.0
	 * @param int   $form_id The form ID value.
	 * @param array $entry   Current entry array containing entry information (i.e data submitted by users).
	 */
	public function show_payment_details( $form_id, $entry ) {
		$transaction_details = gform_get_meta( $entry['id'], '_novalnet_transaction_comments', true );
		if ( ! rgblank( $entry ['payment_method'] ) ) {
			?>
			<div id="gf_novalnet_payment_method" class="gf_payment_detail">
				<?php echo esc_html__( 'Payment Method:', 'gravityforms-novalnet' ); ?><br/>
				<span id='gf_novalnet_payment_method_value'><strong><?php echo esc_html( str_replace( PHP_EOL, '<br/>', $entry ['payment_method'] ) ); ?></strong></span>
			</div>
			<?php
		}
		if ( ! rgblank( $transaction_details ) ) {
			?>
			<div id="gf_novalnet_transaction_details" class="gf_payment_detail">
				<?php echo __( 'Novalnet Transaction Details:', 'gravityforms-novalnet' ); ?><br/>
				<span id='gf_novalnet_transaction_details_value'><?php echo nl2br( $transaction_details ); ?></span>
			</div>
			<?php
		}
	}

	/**
	 * Shows the error message inside the form.
	 *
	 * @since 2.0.0
	 * @param string $content The content of the page.
	 *
	 * @return string
	 */
	public function show_error_message( $content ) {

		if ( isset( $this->request['gf_novalnet_error'] ) ) {

			$content .= sprintf(
				'<div class="validation_error">%s</div>',
				$this->request['gf_novalnet_error']
			);
		}
		return $content;
	}
}
