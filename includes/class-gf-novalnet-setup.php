<?php
/**
 * Novalnet Setup Class
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
 * GF_Novalnet_Setup
 */
class GF_Novalnet_Setup {

	/**
	 * Handle installation process.
	 *
	 * @since 2.0.0
	 */
	public static function install() {
		if ( GFForms::get_wp_option( 'gf_novalnet_version' ) !== GF_NOVALNET_VERSION ) {
				self::create_tables();
				update_option( 'gf_novalnet_version', GF_NOVALNET_VERSION );
		}
	}

	/**
	 * Handle uninstallation process.
	 *
	 * @since 2.0.0
	 */
	public static function uninstall() {
		delete_option( 'gf_novalnet_version' );
		delete_option( 'gravityformsaddon_' . gf_novalnet()->get_slug() . '_settings' );
		return true;
	}

	/**
	 * Handle table creation process.
	 *
	 * @since 2.0.0
	 */
	private static function create_tables() {
		global $wpdb;
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = $wpdb->get_charset_collate();
		// Creating transaction details table to maintain the transaction log.
		Gf_Novalnet_Helper::query_process(
			dbDelta(
				"CREATE TABLE {$wpdb->prefix}novalnet_transaction_details (
				id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
				`date` datetime COMMENT 'Execution date and time',
				vendor_details text COMMENT 'Vendor Credetials',
				entry_id int(11) unsigned COMMENT 'Post ID for the entry in shop',
				tid bigint(20) COMMENT 'Transaction ID',
				payment_type varchar(50) COMMENT 'Executed Payment Type',
				payment_method varchar(50) COMMENT 'Executed Payment Method',
				status int(11) unsigned COMMENT 'Callback Status',
				transaction_amount int(11) unsigned COMMENT 'Transaction Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00', 
				refunded_amount int(11) unsigned COMMENT 'Refunded Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00', 
				paid_amount int(11) unsigned COMMENT 'Paid Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00', 
				email varchar(255) COMMENT 'Customer Email from shop',
				PRIMARY KEY  (id),
				KEY tid (tid),
				KEY entry_id (entry_id)
				) $collate COMMENT='Novalnet Transaction History';"
			)
		);

		// Creating callback table to maintain callback log.
		Gf_Novalnet_Helper::query_process(
			dbDelta(
				"CREATE TABLE {$wpdb->prefix}novalnet_callback_history (
				id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
				`date` datetime COMMENT 'Callback execution date and time',
				payment_type varchar(50) COMMENT 'Callback Payment Type',
				status int(11) unsigned COMMENT 'Callback Status',
				callback_tid bigint(20) unsigned COMMENT 'Callback Reference ID',
				original_tid bigint(20) unsigned COMMENT 'Original Transaction ID',
				amount int(11) unsigned COMMENT 'Amount in minimum unit of currency. E.g. enter 100 which is equal to 1.00',
				entry_id int(11) unsigned COMMENT 'Post ID for the order in shop',
				PRIMARY KEY  (id)
				) $collate COMMENT='Novalnet callback history';"
			)
		);
	}
}
