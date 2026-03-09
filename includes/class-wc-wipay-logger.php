<?php
/**
 * WiPay Logger
 *
 * Thin wrapper around WooCommerce's native WC_Logger that tags every entry
 * with the "wipay-woocommerce" source so log messages are easy to filter in
 * WooCommerce → Status → Logs.
 *
 * @package WiPay_WooCommerce
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_WiPay_Logger
 */
class WC_WiPay_Logger {

	/**
	 * Log source / handle identifier.
	 *
	 * @var string
	 */
	const SOURCE = 'wipay-woocommerce';

	/**
	 * Underlying WooCommerce logger instance.
	 *
	 * @var WC_Logger_Interface|null
	 */
	private static $logger = null;

	/**
	 * Whether debug logging is currently enabled.
	 *
	 * Populated lazily from the gateway settings.
	 *
	 * @var bool|null
	 */
	private static $debug_enabled = null;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Log a DEBUG-level message.
	 *
	 * Only written when the "Debug Mode" setting is active.
	 *
	 * @param string $message Human-readable message.
	 * @param array  $context Optional contextual data (order ID, amount, …).
	 */
	public static function debug( string $message, array $context = array() ): void {
		if ( self::is_debug_enabled() ) {
			self::log( $message, 'debug', $context );
		}
	}

	/**
	 * Log an INFO-level message.
	 *
	 * @param string $message Human-readable message.
	 * @param array  $context Optional contextual data.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( $message, 'info', $context );
	}

	/**
	 * Log a WARNING-level message.
	 *
	 * @param string $message Human-readable message.
	 * @param array  $context Optional contextual data.
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( $message, 'warning', $context );
	}

	/**
	 * Log an ERROR-level message.
	 *
	 * @param string $message Human-readable message.
	 * @param array  $context Optional contextual data.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( $message, 'error', $context );
	}

	/**
	 * Log a CRITICAL-level message (unrecoverable failures).
	 *
	 * @param string $message Human-readable message.
	 * @param array  $context Optional contextual data.
	 */
	public static function critical( string $message, array $context = array() ): void {
		self::log( $message, 'critical', $context );
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Write a log entry via WC_Logger.
	 *
	 * @param string $message Log message.
	 * @param string $level   WC log level constant name (debug, info, warning, error, critical).
	 * @param array  $context Optional extra data included as serialised JSON.
	 */
	private static function log( string $message, string $level, array $context = array() ): void {
		$logger  = self::get_logger();
		$context = array_merge( $context, array( 'source' => self::SOURCE ) );

		$full_message = $message;
		if ( ! empty( $context ) ) {
			// Strip 'source' from the JSON blob since WC already uses it as a handle.
			$log_context = $context;
			unset( $log_context['source'] );
			if ( ! empty( $log_context ) ) {
				$full_message .= ' | ' . wp_json_encode( $log_context );
			}
		}

		$logger->log( $level, $full_message, array( 'source' => self::SOURCE ) );
	}

	/**
	 * Lazy-load the WC_Logger instance.
	 *
	 * @return WC_Logger_Interface
	 */
	private static function get_logger(): WC_Logger_Interface {
		if ( null === self::$logger ) {
			self::$logger = wc_get_logger();
		}
		return self::$logger;
	}

	/**
	 * Returns whether debug logging is enabled in the gateway settings.
	 *
	 * Reads the option once and caches the result for the life of the request.
	 *
	 * @return bool
	 */
	private static function is_debug_enabled(): bool {
		if ( null === self::$debug_enabled ) {
			$options             = get_option( 'woocommerce_wipay_settings', array() );
			self::$debug_enabled = isset( $options['debug'] ) && 'yes' === $options['debug'];
		}
		return self::$debug_enabled;
	}

	/**
	 * Force-reset the cached debug flag (useful in tests).
	 *
	 * @internal
	 */
	public static function reset_cache(): void {
		self::$debug_enabled = null;
		self::$logger        = null;
	}
}
