<?php
/**
 * WiPay WooCommerce – Uninstall
 *
 * This file is executed automatically by WordPress when the plugin is deleted
 * (not just deactivated) through the Plugins → Delete screen.
 *
 * It removes all plugin-specific database records so the site is left in a
 * clean state. Data includes:
 *   - The WooCommerce gateway settings option
 *   - Any transients created by the plugin
 *
 * NOTE: Order meta and order notes written during payment processing are
 * intentionally preserved because they form part of the store's financial
 * record and may be legally required to be retained.
 *
 * @package WiPay_WooCommerce
 * @since   2.0.0
 */

// WordPress sets this constant before calling uninstall.php; if it is not set
// the file is being accessed directly and we must abort.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// -------------------------------------------------------------------------
// Remove gateway settings.
// -------------------------------------------------------------------------
delete_option( 'woocommerce_wipay_settings' );

// -------------------------------------------------------------------------
// Remove any plugin transients.
// -------------------------------------------------------------------------
delete_transient( 'wipay_version_check' );

// -------------------------------------------------------------------------
// Multisite: repeat for every blog in the network.
// -------------------------------------------------------------------------
if ( is_multisite() ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( (int) $blog_id );

		delete_option( 'woocommerce_wipay_settings' );
		delete_transient( 'wipay_version_check' );

		restore_current_blog();
	}
}
