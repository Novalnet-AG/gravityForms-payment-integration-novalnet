<?php
/**
 * Plugin Name: GravityForms-NovalnetGateway
 * Plugin URI: https://www.novalnet.de
 * Description: Adds Novalnet Payment Gateway to Gravity Forms plugin
 * Version: 1.0.0
 * Author: Novalnet AG
 * Author URI: https://www.novalnet.de
 * Text Domain: gravityforms_novalnet
 * Domain Path: /langauges/
 **/

if ( !defined( "NOVALNET_BASE_PATH" ) )
  define( "NOVALNET_BASE_PATH", WP_PLUGIN_DIR . "/" . basename( dirname( __FILE__ ) ) );

if ( !defined( "NOVALNET_PLUGIN_PATH" ) )
  define( "NOVALNET_PLUGIN_PATH", dirname( plugin_basename( __FILE__ ) ) );

if ( !defined( "NOVALNET_PLUGIN_BASE_URL" ) )
  define( "NOVALNET_PLUGIN_BASE_URL", plugins_url( null, __FILE__ ) );

add_action( 'wp', array( 'Novalnet_core', 'novalnet_thankyou_page' ), 5 );
add_action( 'init', array( 'Novalnet_core', 'novalnet_init' ) );
add_action( 'gravity_forms_novalnet_callback', array( 'Novalnet_core', 'novalnet_callback_handler' ) );
register_activation_hook( __FILE__, array( 'Novalnet_core', 'novalnet_registration' ) );
register_deactivation_hook( __FILE__ , array( 'Novalnet_core', 'novalnet_uninstall' ) );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_novalnet_action_links' );

function add_novalnet_action_links( $links ) {
  $mylinks = array( '<a href="' . admin_url() . 'admin.php?page=gf_settings&subview=Novalnet">'.__( "Settings", "gravityforms_novalnet" ).'</a>' );
  return array_merge( $mylinks, $links );
}

include_once( NOVALNET_BASE_PATH. '/class/class-novalnet-interface.php' );
if ( method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
    GFForms::include_payment_addon_framework();

    class Novalnet_core extends GFPaymentAddOn {
        protected $_path                 = NOVALNET_PLUGIN_PATH;
        protected $_full_path            = __FILE__;
        protected $_title                = 'Gravity Forms-NovalnetGateway';
        protected $_short_title          = 'Novalnet';
        protected $_supports_callbacks   = true;
        protected $_requires_credit_card = false;

        public static function novalnet_init(){
            global $wpdb;
            $request = $_REQUEST;
            // Initialize vendorscript
            Novalnet_interface::novalnet_callback_request( $request );

            load_plugin_textdomain( 'gravityforms_novalnet', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
            if ( is_admin() ){
                add_filter( 'members_get_capabilities', array( "Novalnet_core", "novalnet_members_get_capabilities" ) );

                if ( Novalnet_interface::is_novalnet_page() ) {
                    $novalnet_addon = new Novalnet_core;
                    $novalnet_addon->setup();
                }
                // Adding Novalnet in setting page
                if ( RGForms::get( "page" ) == "gf_settings" ){
                    RGForms::add_settings_page( 'Novalnet', array( "Novalnet_core", "novalnet_admin_template" ), NOVALNET_PLUGIN_BASE_URL . " /images/Novalnet.png " );
                }
            } else {
                $backend_values = get_option( "gf_novalnet_settings" );
                // Validating Novalnet configuration
                if ( ! empty( $backend_values ) && Novalnet_interface::validate_backend( $backend_values ) && 1 == rgar( $backend_values, 'enable_module' ) ) {
                    add_filter( "gform_confirmation", array( "Novalnet_core", "send_to_novalnet_server" ), 1000, 4 );
                } else {
                    return true;
                }
            }
        }

        /**
         * Add action link
         * @param $links
         *
         * @return array
         **/
        function novalnet_action_links ( $links ) {
            Novalnet_interface::add_novalnet_action_links( $links );
        }

        /**
         * Handling Novalnet Vendorscript
         * @param $request
         *
         * @return NULL
         **/
        static function novalnet_callback_handler( $request ) {
            Novalnet_interface::novalnet_callback_process( $request );
        }

        /**
         * Handling Novalnet Vendorscript
         * @param $request
         *
         * @return NULL
         **/
        static function novalnet_admin_template() {
            Novalnet_interface::novalnet_admin();
        }

        /**
         * Adding Novalnet permisson to access as administrator
         *
         * @return NULL
         **/
        static function novalnet_registration() {
            global $wpdb, $wp_roles;
            $charset_collate = $wpdb->get_charset_collate();
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            $callback_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rg_novalnet_callback (
                id bigint( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                order_id varchar( 200 ) NOT NULL ,
                callback_amount int( 11 ) NOT NULL ,
                total_amount int( 11 ) NOT NULL ,
                reference_tid text NOT NULL,
                callback_datetime datetime NOT NULL,
                callback_tid bigint( 20 ) DEFAULT NULL,
                callback_log text ,
                class_name text ) $charset_collate COMMENT='Novalnet callback table'";
            dbDelta( $callback_table );

            $affiliate_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rg_novalnet_aff_account_detail (
                `id` bigint( 20 ) NOT NULL AUTO_INCREMENT COMMENT 'Auto Increment ID',
                `vendor_id` bigint( 11 ) unsigned NOT NULL,
                `vendor_authcode` varchar( 40 ) NOT NULL,
                `product_id` bigint( 11 ) unsigned NOT NULL,
                `product_url` varchar( 200 ) NOT NULL,
                `activation_date` datetime NOT NULL,
                `aff_id` bigint( 11 ) unsigned DEFAULT NULL,
                `aff_authcode` varchar( 40 ) DEFAULT NULL,
                `aff_accesskey` varchar( 40 ) DEFAULT NULL,
                PRIMARY KEY ( `id` ),
                INDEX `vendor_id` ( `vendor_id` ),
                INDEX `aff_id` ( `aff_id` )
                ) $charset_collate COMMENT='Novalnet merchant / affiliate account information';";
            dbDelta( $affiliate_sql );

            $aff_user_table = " CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rg_novalnet_aff_user_detail (
                `id` bigint( 20 ) NOT NULL AUTO_INCREMENT COMMENT 'Auto Increment ID',
                `aff_id` bigint( 11 ) unsigned NOT NULL COMMENT 'Affiliate merchant ID',
                `customer_id` bigint( 20 ) unsigned NOT NULL COMMENT 'Affiliate Customer ID',
                `aff_shop_id` bigint( 20 ) unsigned NOT NULL COMMENT 'Post ID for the order in shop',
                `aff_order_no` varchar( 20 ) NOT NULL,
                PRIMARY KEY ( `id` ),
                INDEX `aff_id` ( `aff_id` ),
                INDEX `customer_id` ( `customer_id` ),
                INDEX `aff_order_no` ( `aff_order_no` )
                ) $charset_collate COMMENT='Novalnet affiliate customer account information';";
          dbDelta( $aff_user_table );

          $wp_roles->add_cap( 'administrator', 'gravityforms_novalnet' );
          $wp_roles->add_cap( 'administrator', 'gravityforms_novalnet_uninstall' );
        }

        /**
         * Checking for Novalnet capabilities
         * $param $caps
         *
         * @return NULL
         **/
        static function novalnet_members_get_capabilities( $caps ) {
            Novalnet_interface::novalnet_capabilities( $caps );
        }

        /**
         * Sending the params to Novalnet server
         * @param $confirmation
         * @param $form
         * @param $entry
         *
         * @return NULL
         **/
        static function send_to_novalnet_server( $confirmation, $form, $entry ){
            return Novalnet_interface::prepare_novalnet_params( $confirmation, $form, $entry );
        }

        /**
         * Displays the Thankyou page
         *
         * @return NULL
         **/
        static function novalnet_thankyou_page () {
            Novalnet_interface::novalnet_thankyou_page();
        }

        /**
         * Novalnet_uninstall
         *
         * @return NULL
         **/
        static function novalnet_uninstall () {
            Novalnet_interface::novalnet_plugin_uninstall();
        }
    }
}
