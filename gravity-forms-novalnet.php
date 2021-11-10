<?php
/**
 * Plugin Name: Novalnet payment plugin - Gravity Forms
 * Plugin URI:  https://www.novalnet.de/modul/gravityforms
 * Description: Plug-in to process payments in Gravity Forms through Novalnet Gateway
 * Author:      Novalnet
 * Author URI:  https://www.novalnet.de
 * Version:     2.0.0
 * Requires at least: 4.1
 * Tested up to: 4.8.2
 * Text Domain: gravityforms-novalnet
 * Domain Path: /i18n/languages/
 * License:     GPLv2
 *
 * @package Novalnet payment plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'GF_Novalnet' ) ) :
	define( 'GF_NOVALNET_VERSION', '2.0.0' );

	define( 'GF_NOVALNET_FILE', __FILE__ );

	define( 'GF_NOVALNET_PATH', plugin_dir_path( __FILE__ ) );

	define( 'GF_NOVALNET_URL', plugin_dir_url( __FILE__ ) );

	GFForms::include_payment_addon_framework();
	GFForms::include_addon_framework();

	include_once 'class-gf-novalnet.php';

	GFAddOn::register( 'GF_Novalnet' );

endif;



/**
 * Returns the main instance of GF_Novalnet.
 *
 * @since 2.0.0
 *
 * @return GF_Novalnet
 */
function gf_novalnet() {
	return GF_Novalnet::get_instance();
}
add_action( 'gform_loaded', 'gf_novalnet', 5 );
