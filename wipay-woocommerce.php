<?php
/**
 * Plugin Name:       WiPay WooCommerce Payment Gateway
 * Plugin URI:        https://wipaycaribbean.com
 * Description:       Accept credit card payments in Trinidad, Jamaica, Barbados, Saint Lucia, Grenada, and Guyana via WiPay Caribbean's hosted checkout.
 * Version:           2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            WiPay Caribbean
 * Author URI:        https://wipaycaribbean.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wipay-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to:   9.0
 *
 * @package WiPay_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WIPAY_VERSION', '2.0.0' );
define( 'WIPAY_PLUGIN_FILE', __FILE__ );
define( 'WIPAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WIPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WIPAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WIPAY_MIN_WC_VERSION', '5.0' );
define( 'WIPAY_MIN_PHP_VERSION', '7.4' );

/**
 * Main WiPay plugin class (singleton).
 */
final class WiPay_WooCommerce {

	/**
	 * Single instance of this class.
	 *
	 * @var WiPay_WooCommerce|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance, creating it on first call.
	 *
	 * @return WiPay_WooCommerce
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: wire up all hooks.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
	}

	/**
	 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WIPAY_PLUGIN_FILE,
				true
			);
		}
	}

	/**
	 * Initialize plugin after all plugins are loaded.
	 */
	public function init(): void {
		// Bail if WooCommerce is not active.
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'notice_woocommerce_required' ) );
			return;
		}

		// Bail if WooCommerce version is too old.
		if ( ! $this->is_woocommerce_version_ok() ) {
			add_action( 'admin_notices', array( $this, 'notice_woocommerce_version' ) );
			return;
		}

		// Load text domain.
		load_plugin_textdomain(
			'wipay-woocommerce',
			false,
			dirname( WIPAY_PLUGIN_BASENAME ) . '/languages'
		);

		// Autoload plugin classes.
		$this->includes();

		// Register the payment gateway with WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );

		// Add settings link on plugins page.
		add_filter( 'plugin_action_links_' . WIPAY_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Load required plugin files.
	 */
	private function includes(): void {
		require_once WIPAY_PLUGIN_DIR . 'includes/class-wc-wipay-countries.php';
		require_once WIPAY_PLUGIN_DIR . 'includes/class-wc-wipay-logger.php';
		require_once WIPAY_PLUGIN_DIR . 'includes/class-wc-wipay-webhook.php';
		require_once WIPAY_PLUGIN_DIR . 'includes/class-wc-wipay-gateway.php';

		// Boot webhook handler (registers REST/query-var routes).
		WC_WiPay_Webhook::instance();
	}

	/**
	 * Add the WiPay gateway class to WooCommerce's list of gateways.
	 *
	 * @param array $gateways Existing gateway classes.
	 * @return array
	 */
	public function register_gateway( array $gateways ): array {
		$gateways[] = 'WC_WiPay_Gateway';
		return $gateways;
	}

	/**
	 * Add a "Settings" shortcut link on the Plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wipay' );
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $settings_url ),
			esc_html__( 'Settings', 'wipay-woocommerce' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	// -------------------------------------------------------------------------
	// Dependency checks
	// -------------------------------------------------------------------------

	/**
	 * Check whether WooCommerce is active.
	 */
	private function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check whether the installed WooCommerce meets the minimum version.
	 */
	private function is_woocommerce_version_ok(): bool {
		if ( ! defined( 'WC_VERSION' ) ) {
			return false;
		}
		return version_compare( WC_VERSION, WIPAY_MIN_WC_VERSION, '>=' );
	}

	// -------------------------------------------------------------------------
	// Admin notices
	// -------------------------------------------------------------------------

	/**
	 * Admin notice: WooCommerce must be installed and active.
	 */
	public function notice_woocommerce_required(): void {
		echo '<div class="error"><p>' .
			esc_html__(
				'WiPay WooCommerce requires WooCommerce to be installed and active.',
				'wipay-woocommerce'
			) .
		'</p></div>';
	}

	/**
	 * Admin notice: WooCommerce version is too old.
	 */
	public function notice_woocommerce_version(): void {
		/* translators: %s: minimum WooCommerce version */
		echo '<div class="error"><p>' .
			sprintf(
				esc_html__(
					'WiPay WooCommerce requires WooCommerce %s or higher.',
					'wipay-woocommerce'
				),
				esc_html( WIPAY_MIN_WC_VERSION )
			) .
		'</p></div>';
	}
}

/**
 * Returns the main plugin instance.
 *
 * @return WiPay_WooCommerce
 */
function wipay_woocommerce(): WiPay_WooCommerce {
	return WiPay_WooCommerce::instance();
}

// Kick things off.
wipay_woocommerce();
