<?php
/**
 *
 * This script is used for real time capturing of
 * parameters passed from Novalnet AG after Payment
 * processing of customers.
 *
 * Copyright ( c ) Novalnet AG
 *
 * This script is only free to the use for Merchants of
 * Novalnet AG
 *
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Version : 1.0.0
 *
 * Please contact sales@novalnet.de for enquiry or Info
 */

include_once( NOVALNET_BASE_PATH. '/class/class-novalnet-interface.php' );
class Novalnet_vendor_script {
    // @Array Type of payment available - Level : 0
    protected $ary_payments = array( 'INVOICE_START','PAYPAL','ONLINE_TRANSFER','CREDITCARD','CREDITCARD_BOOKBACK', 'IDEAL', 'EPS', 'DIRECT_DEBIT_SEPA' );
    // @Array Type of Chargebacks available - Level : 1
    protected $ary_chargebacks = array( 'RETURN_DEBIT_SEPA','CREDITCARD_BOOKBACK','CREDITCARD_CHARGEBACK' );
    protected $ary_subscription = array( 'SUBSCRIPTION_STOP' );
    // @Array Type of CreditEntry payment and Collections available - Level : 2
    protected $ary_collection = array( 'INVOICE_CREDIT','INVOICE_START' );
    protected $ary_payment_groups = array(
                                'novalnet_creditcard'         => array( 'CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK', 'SUBSCRIPTION_STOP' ),
                                'novalnet_sepa'               => array( 'DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'SUBSCRIPTION_STOP' ),
                                'novalnet_ideal'              => array( 'IDEAL' ),
                                'novalnet_online_transfer'    => array( 'ONLINE_TRANSFER' ),
                                'novalnet_paypal'             => array( 'PAYPAL' ),
                                'novalnet_eps'             	  => array( 'EPS' ),
                                'novalnet_prepayment'         => array( 'INVOICE_START','INVOICE_CREDIT', 'SUBSCRIPTION_STOP' ),
                                'novalnet_invoice'            => array( 'INVOICE_START',  'INVOICE_CREDIT', 'SUBSCRIPTION_STOP' ),
                                'novalnet_invoice_prepayment' => array( 'INVOICE_START',  'INVOICE_CREDIT', 'SUBSCRIPTION_STOP' ) );

    protected $get_payment_method = array(
                                'CREDITCARD'            => 'creditcard',
                                'CREDITCARD_BOOKBACK'   => 'creditcard',
                                'CREDITCARD_CHARGEBACK' => 'creditcard',
                                'SUBSCRIPTION_STOP'     => 'creditcard',
                                'DIRECT_DEBIT_SEPA'     => 'sepa',
                                'RETURN_DEBIT_SEPA'     => 'sepa',
                                'SUBSCRIPTION_STOP'     => 'sepa',
                                'IDEAL'                 => 'ideal',
                                'EPS'                 	=> 'eps',
                                'PAYPAL'                => 'paypal',
                                'ONLINE_TRANSFER'       => 'online_transfer',
                                'INVOICE_START'         => 'invoice_prepayment',
                                'INVOICE_CREDIT'        => 'invoice_prepayment' );

    protected $get_payment_key = array(
                                'CREDITCARD'            => 6,
                                'CREDITCARD_BOOKBACK'   => 6,
                                'CREDITCARD_CHARGEBACK' => 6,
                                'SUBSCRIPTION_STOP'     => 6,
                                'DIRECT_DEBIT_SEPA'     => 37,
                                'RETURN_DEBIT_SEPA'     => 37,
                                'SUBSCRIPTION_STOP'     => 37,
                                'IDEAL'                 => 49,
                                'EPS'                 	=> 50,
                                'PAYPAL'                => 34,
                                'ONLINE_TRANSFER'       => 33,
                                'INVOICE_START'         => 27,
                                'INVOICE_CREDIT'        => 27 );
    // @Array callback capture parameters
    protected $arycaptureparams = array();
    protected $params_required = array();
    // @IP-ADDRESS Novalnet IP, is a fixed value, DO NOT CHANGE!!!!!
    protected $ipAllowed = array( '195.143.189.210', '195.143.189.214' );

    function __construct( $ary_capture = array() ) {
        $this->process_test_mode   = false; // Update into false when switch into LIVE
        $this->process_debug_mode  = false; // Update into true to debug mode
        if ( isset( $ary_capture['debug_mode'] ) && 1 == $ary_capture['debug_mode'] ){
            $this->process_test_mode = $this->process_debug_mode = true;
        }
        self::validateIpAddress();
        if ( empty( $ary_capture ) ) {
            self::debug_error( 'Novalnet callback received. No params passed over!' );
        }
        $this->params_required = array( 'vendor_id', 'tid', 'payment_type', 'status', 'amount' );
        $this->aff_account_activation_params_required = array( 'vendor_id', 'vendor_authcode', 'product_id', 'aff_id', 'aff_accesskey', 'aff_authcode' );
        if ( isset( $ary_capture['subs_billing'] ) && 1 == $ary_capture['subs_billing'] ) {
            array_push( $this->params_required, 'signup_tid' );
        }elseif ( isset( $ary_capture['payment_type'] ) && in_array( $ary_capture['payment_type'], array_merge( $this->ary_chargebacks, array( 'INVOICE_CREDIT' ) ) ) ) {
            array_push( $this->params_required, 'tid_payment' );
        }
        $this->arycaptureparams = self::validate_capture_params( $ary_capture );
    }

    /*
    * Return capture parameters
    *
    * @return Array
    */
    function get_capture_params() {
        return $this->arycaptureparams;
    }

    /*
    * Perform parameter validation process
    * Set Empty value if not exist in ary_capture
    *
    * @return Array
    */
    function validate_capture_params( $ary_capture ) {
        $arySetNullvalueIfnotExist = array( 'reference', 'vendor_id', 'tid', 'status', 'status_messge', 'payment_type', 'signup_tid' );
        foreach( $arySetNullvalueIfnotExist as $value ) {
            if ( !isset( $ary_capture[ $value ] ) ) {
                $ary_capture[ $value ] = '';
            }
        }
        if ( !isset( $ary_capture['vendor_activation'] ) ) {
            foreach ( $this->params_required as $v ) {
                if ( empty( $ary_capture[ $v ] ) ) {
                      self::debug_error( 'Required param ( ' . $v . '  ) missing!' );
                }
                if ( in_array( $v, array( 'tid', 'tid_payment', 'signup_tid' ) ) && !preg_match( '/^\d{17}$/', $ary_capture[ $v ] ) ) {
                    self::debug_error( 'Novalnet callback received. Invalid TID ['. $v . '] for Order.' );
                }
            }
            if ( !in_array( $ary_capture['payment_type'], array_merge( $this->ary_payments, $this->ary_chargebacks, $this->ary_collection,$this->ary_subscription ) ) ) {
                self::debug_error( 'Novalnet callback received. Payment type ( ' . $ary_capture['payment_type'] . ' ) is mismatched!' );
            }
            if ( isset( $ary_capture['signup_tid'] ) && $ary_capture['signup_tid'] != '' ) { // Subscription
                $ary_capture['shop_tid'] = $ary_capture['signup_tid'];
            }
            else if ( in_array( $ary_capture['payment_type'], array_merge( $this->ary_chargebacks, array( 'INVOICE_CREDIT' ) ) ) ) {
                $ary_capture['shop_tid'] = $ary_capture['tid_payment'];
            }
            else if ( isset( $ary_capture['tid'] ) && $ary_capture['tid'] != '' ) {
                $ary_capture['shop_tid'] = $ary_capture['tid'];
            }
        }else {
            foreach ( $this->aff_account_activation_params_required as $v ) {
                if ( empty( $ary_capture[ $v ] ) ) {
                    self::debug_error( 'Required param ( ' . $v . '  ) missing!' );
                }
            }
        }
        return $ary_capture;
    }

    /*
    * Function to return the client IP address
    *
    * @return string
    */
    function get_client_ip() {
        $ipaddress = '';
        if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) )
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if ( isset( $_SERVER['HTTP_X_FORWARDED'] ) )
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) )
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if ( isset( $_SERVER['HTTP_FORWARDED'] ) )
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if ( isset( $_SERVER['REMOTE_ADDR'] ) )
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    /*
    * Get given payment_type level for process
    *
    * @return mixed
    */
    function get_payment_type_level() {
        return in_array( $this->arycaptureparams['payment_type'], $this->ary_payments ) ? 0 : ( in_array( $this->arycaptureparams['payment_type'], $this->ary_chargebacks ) ? 1 : ( in_array( $this->arycaptureparams['payment_type'], $this->ary_collection ) ? 2 : false ) );
    }

    /*
    * Get order reference from the novalnet_transaction_detail table on shop database
    *
    * @return array
    */
    function get_order_reference() {
        global $wpdb;
        $tid = $this->arycaptureparams['shop_tid'];
        $backend_values = get_option( "gf_novalnet_settings" );
        $dbValue = self::getIncrementId( $this->arycaptureparams, $tid );
        $order_id = $dbValue['order_id'];
        if ( $order_id ) {
            $novalnet_response['amount'] = $wpdb->get_var( $wpdb->prepare( "SELECT payment_amount FROM {$wpdb->prefix}rg_lead WHERE id = %s", $order_id ) );
            $dbValue['tid']                    = $tid;
            $dbValue['order_current_status']   = self::get_order_current_status( $order_id );
            $dbValue['callback_script_status'] = rgar( $backend_values, 'callback_order_status' );
            $dbValue['payment_type']           = $this->arycaptureparams['payment_type'];
            //Collect paid amount information from the novalnet_callback_history
            $dbValue['order_paid_amount']       = 0;
            $payment_type_level = self::get_payment_type_level();
            if ( in_array( $payment_type_level,array( 1,2 ) ) ) {
                $orderTotalQry = $wpdb->get_var( $wpdb->prepare( "SELECT sum( callback_amount ) as callback_amount FROM {$wpdb->prefix}rg_novalnet_callback WHERE order_id = %s  ORDER BY id DESC LIMIT 1", $order_id ) );
                $dbCallbackTotalVal = $orderTotalQry;
                $dbValue['order_paid_amount'] = ( ( isset( $orderTotalQry ) ) ? $orderTotalQry : 0 );
            }
        } else {
            self::debug_error( 'Transaction mapping failed' );
        }
        return $dbValue;
    }

    /*
    * Get orders_status from the orders table on shop database
    * Table : usces_order
    *
    * @return integer
    */
    function get_order_current_status( $order_id = '' ) {
        global $wpdb;
        $dbVal = $wpdb->get_row( $wpdb->prepare( "SELECT payment_status FROM {$wpdb->prefix}rg_lead WHERE id = %s", $order_id ) );
        return $dbVal->payment_status;
    }
    /*
    * Update Callback comments in shop order tables
    * Table : usces_order
    * @param $datas
    *
    * @return boolean
    */
    function update_callback_comments( $datas, $update_callback_status = false ) {
        global $wpdb;
            $order_id = $datas['order_no'];
            $comments = ( ( isset( $datas['comments'] ) && $datas['comments'] != '' ) ? $datas['comments'] : '' );
            if ( $update_callback_status ) {
                $where =array( 'id' => $order_id );
                $param['payment_status'] = $datas['orders_status_id'];
                $wpdb->update( $wpdb->prefix.'rg_lead', $param, $where );
            }
            $where =array( 'lead_id' => $datas['order_no'] );
            $dbVal = $wpdb->get_row( $wpdb->prepare( "SELECT user_name, user_id FROM {$wpdb->prefix}rg_lead_notes WHERE lead_id = %s ORDER BY id DESC limit 1", $order_id ) );
            $db_val['value'] = $datas['comments'];
            $db_val['note_type'] = 'success';
            $db_val['lead_id'] = $order_id;
            $db_val['date_created'] = date( 'Y-m-d H:i:s' );
            $wpdb->insert( $wpdb->prefix.'rg_lead_notes', $db_val );
            return true;
    }

  /*
  * Display the error message
  * @param $errorMsg
  *
  * @return void
  */
    function debug_error( $errorMsg ) {
        if ( $this->process_debug_mode )
            echo $errorMsg;
        exit;
    }

    /**
     * Update affiliate account activation details in novalnet_aff_account_detail table
     *
     * @access public
     * @param array $ary_activation_params
     * @return boolean
     */
    public function update_aff_account_activation_detail( $ary_activation_params ) {
        global $wpdb;
        $new_line = "\n";
        $wpdb->insert( "{$wpdb->prefix}rg_novalnet_aff_account_detail",
            array(
                'vendor_id'         => $ary_activation_params['vendor_id'],
                'vendor_authcode'   => $ary_activation_params['vendor_authcode'],
                'product_id'        => $ary_activation_params['product_id'],
                'product_url'       => $ary_activation_params['product_url'],
                'activation_date'   => isset( $ary_activation_params['activation_date'] ) ? date( 'Y-m-d H:i:s', strtotime( $ary_activation_params['activation_date'] ) ) : '',
                'aff_id'            => $ary_activation_params['aff_id'],
                'aff_authcode'      => $ary_activation_params['aff_authcode'],
                'aff_accesskey'     => $ary_activation_params['aff_accesskey'],
            )
        );

        $callback_comments =  $new_line . 'Novalnet callback script executed successfully with Novalnet account activation information.' . $new_line;

        //Send notification mail to Merchant
        self::send_notify_mail( array(
            'comments' => $callback_comments,
            'order_no' => '-',
        ), $this->arycaptureparams );
        self::debug_error( $callback_comments );

        return true;
    }

  /*
  * Log callback process in novalnet_callback_history table
  * Table : novalnet_callback
  * @param $datas
  * @param $request
  * @param $order_id
  *
  * @return boolean
  */
    function log_callback_process( $datas,$request, $order_id ){
        global $wpdb;
            $param['order_id'] = $order_id;
            $param['callback_amount'] = $request['amount'];
            $param['reference_tid'] = $datas['tid'];
            $param['callback_datetime'] = date( 'Y-m-d H:i:s' );
            $param['callback_tid'] = $request['tid'];
            $param['callback_log'] = get_site_url();
            $param['total_amount'] = $datas['total_amount'];
            $param['class_name'] = $datas['class_name'];
            $wpdb->insert( $wpdb->prefix.'rg_novalnet_callback', $param );
            return true;
    }

    /*
    * Send notification mail to Merchant
    *
    * @return boolean
    */
    function send_notify_mail( $datas = array(), $ary_captureParams ) {
        $email_from         = '';
        $email_from_name    = '';
        $email_to           = '';
        $email_to_name      = '';
        if ( isset( $ary_captureParams['debug_mode'] ) && 1 == $ary_captureParams['debug_mode'] ) {
            $email_from      = 'testadmin@novalnet.de';
            $email_from_name = 'Novalnet test';
            $email_to        = 'test@novalnet.de';
            $email_to_name   = 'Novalnet';
        }
        $email_subject  = 'Novalnet Vendor script notification';
        $email_body     = 'Order:' . $datas['order_no'] . PHP_EOL . ' Message : ' . $datas['comments'];
        $headers        = 'From: ' . $email_from . PHP_EOL;
        if ( is_email( $email_to ) && is_email( $email_from ) ) {
            wp_mail( $email_to, $email_subject, $email_body, $headers ); // Sending Mail Function
            echo 'Mail sent!';
        } else { echo 'Mail not sent!'; }
        return true;
    }

    /*
    * Validate IP address
    *
    * @return Array
    */
    function validateIpAddress() {
        $client_ip = self::get_client_ip();
        if ( !in_array( $client_ip, $this->ipAllowed ) && !$this->process_test_mode ) {
            echo "Novalnet callback received. Unauthorised access from the IP $client_ip";exit;
        }
    }

    /**
    * Get order no and handle communication failure
    * @param $request
    * @param $tid
    *
    * @return order_no
    */
    function getIncrementId( $request, $tid ) {
        global $wpdb;
        $order_no = ( isset( $request['order_no'] ) && !empty( $request['order_no'] ) ? $request['order_no'] : '' );

        $query_order_id = $wpdb->get_row( $wpdb->prepare( "SELECT reference_tid, order_id, callback_amount, total_amount, class_name  FROM {$wpdb->prefix}rg_novalnet_callback WHERE reference_tid = %s  ORDER BY id DESC LIMIT 1", $tid ) );
        if ( !empty( $query_order_id)  && ( ( $order_no != '' && $query_order_id->order_id != '' && $order_no != $query_order_id->order_id ) || ( empty( $order_no ) && empty( $query_order_id->order_id ) ) ) ) {
            self::debug_error( 'Order no is not valid!' );
        }
        $order_no = ( empty( $order_no ) && !empty($query_order_id->order_id) ) ? $query_order_id->order_id : $order_no;
        if ( empty( $query_order_id->order_id ) ) {
            if ( empty( $order_no ) ) {
                self::debug_error( 'Novalnet callback received. Transaction mapping failed. No order data found!' );
            }else {
                $backend_values = get_option( 'gf_novalnet_settings' );
                $novalnet_response 	= $request;

                $novalnet_response['amount']= $wpdb->get_var( $wpdb->prepare( "SELECT payment_amount FROM {$wpdb->prefix}rg_lead WHERE id = %s", $order_no ) );

                $novalnet_response['tid'] 			= $request['shop_tid'];
                $novalnet_response['payment_type'] 	= $this->get_payment_method[ $request['payment_type'] ];
                $novalnet_response['key'] 			= $this->get_payment_key[ $request['payment_type'] ];

                $order_status =  ( 100 == rgar( $novalnet_response, 'status' ) ) ? rgar( $backend_values,'order_completion_status' ) : rgar( $backend_values,'failed_order_status' );

                $entry = ( 100 != $request['status'] ) ? '' : GFAPI::get_entry( $order_no );
                $Novalnet_core 	= new Novalnet_interface();
                $novalnet_response['payment_name']  = $Novalnet_core->get_novalnet_payment_details( rgar( $novalnet_response, 'key' ), rgar( $novalnet_response, 'payment_type' ) );
                $novalnet_order_comments 			= $Novalnet_core->novalnet_comments( $novalnet_response, rgar( $request,'test_mode' ), rgar( $backend_values,'product_id' ), $order_status );

                $Novalnet_core->novalnet_db_update( $order_no, $order_status, $novalnet_order_comments, $novalnet_response, $entry );
                $emailBody 		= $comments = '';
                $requestAmount 	= $request['amount'];
                $tid 			= $request['shop_tid'];
                $class_name 	= 'novalnet_' . $novalnet_response['payment_type'];
                $total_amount 	= $novalnet_response['amount'] * 100;

                $wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'rg_novalnet_callback SET order_id = "' . $order_no . '", reference_tid = "' . $tid . '",callback_datetime =  NOW(), callback_amount = "' . $requestAmount . '",callback_log = "' . $_SERVER['REQUEST_URI'] . '",class_name = "' .  $class_name . '",total_amount = "' . $total_amount . '"' );
                self::send_notify_mail( array( 'comments' => $novalnet_order_comments, 'order_no' => $order_no ), $request );
                self::debug_error( $novalnet_order_comments );
            }
        } else {
            if ( ( $order_no != '' && $query_order_id->order_id && $order_no != $query_order_id->order_id ) || ( !empty( $query_order_id->order_id ) && !empty( $order_no ) && ( $query_order_id->order_id != $order_no ) ) ) {
                self::debug_error( 'Novalnet Callbackscript received. Order no is not valid' );
            }
            if ( !in_array( $request['payment_type'],  $this->ary_payment_groups[ $query_order_id->class_name ] ) ) {
                self::debug_error( 'Novalnet callback received.Payment type [' . $request['payment_type'] . '] is mismatched!' );
            }
            if ( isset( $request['status'] ) && 100 != $request['status'] )  {
                self::debug_error( 'Novalnet callback received. Status ( ' . $request['status'] . ' ) is not valid: Only 100 is allowed' );
            }
            return ( array )$query_order_id;
        }
    }
}
?>
