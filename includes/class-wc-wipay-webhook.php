<?php
/**
 * WiPay Webhook Handler
 *
 * Handles the server-to-server POST notification that WiPay sends after a
 * payment attempt. Registers a REST API endpoint that WiPay calls directly
 * (bypassing the browser) to confirm payment status.
 *
 * Endpoint: POST /wp-json/wipay/v1/webhook
 *
 * WiPay POST body fields:
 *   status            - 'success' or 'failed'
 *   order_id          - WooCommerce order ID
 *   transaction_id    - WiPay transaction reference
 *   reasonDescription - Human-readable failure reason
 *
 * @package WiPay_WooCommerce
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_WiPay_Webhook
 */
class WC_WiPay_Webhook {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'wipay/v1';

	/**
	 * REST API route for the webhook.
	 *
	 * @var string
	 */
	const REST_ROUTE = '/webhook';

	/**
	 * Single instance.
	 *
	 * @var WC_WiPay_Webhook|null
	 */
	private static $instance = null;

	/**
	 * Returns or creates the singleton instance.
	 *
	 * @return WC_WiPay_Webhook
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor – register REST routes.
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	/**
	 * Register the WiPay webhook REST route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE, // POST
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true', // WiPay signs requests differently; we validate internally.
				'args'                => array(
					'status'            => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'order_id'          => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'transaction_id'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'reasonDescription' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Webhook handler
	// -------------------------------------------------------------------------

	/**
	 * Handle an incoming WiPay webhook POST.
	 *
	 * @param WP_REST_Request $request Incoming request object.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ): WP_REST_Response {
		$status         = (string) $request->get_param( 'status' );
		$order_id       = absint( $request->get_param( 'order_id' ) );
		$transaction_id = (string) $request->get_param( 'transaction_id' );
		$reason         = (string) $request->get_param( 'reasonDescription' );

		WC_WiPay_Logger::info(
			'Webhook received.',
			array(
				'order_id'       => $order_id,
				'status'         => $status,
				'transaction_id' => $transaction_id,
			)
		);

		// Validate we have an order ID.
		if ( ! $order_id ) {
			WC_WiPay_Logger::error( 'Webhook missing order_id.' );
			return new WP_REST_Response( array( 'error' => 'Missing order_id' ), 400 );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			WC_WiPay_Logger::error( 'Webhook – order not found.', array( 'order_id' => $order_id ) );
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		// Verify the order was placed via WiPay.
		if ( 'wipay' !== $order->get_payment_method() ) {
			WC_WiPay_Logger::warning(
				'Webhook received for non-WiPay order.',
				array(
					'order_id'       => $order_id,
					'payment_method' => $order->get_payment_method(),
				)
			);
			return new WP_REST_Response( array( 'error' => 'Payment method mismatch' ), 400 );
		}

		// Add a note that the webhook was received regardless of outcome.
		$order->add_order_note(
			sprintf(
				/* translators: 1: Status, 2: Transaction ID */
				__( 'WiPay webhook received. Status: %1$s. Transaction ID: %2$s.', 'wipay-woocommerce' ),
				esc_html( $status ),
				esc_html( $transaction_id ? $transaction_id : __( 'N/A', 'wipay-woocommerce' ) )
			)
		);

		// Retrieve the gateway instance to call shared payment state helpers.
		$gateway = $this->get_gateway();

		if ( ! $gateway ) {
			WC_WiPay_Logger::critical( 'Could not load WC_WiPay_Gateway instance.' );
			return new WP_REST_Response( array( 'error' => 'Gateway unavailable' ), 500 );
		}

		if ( 'success' === strtolower( $status ) ) {
			$gateway->payment_complete( $order, $transaction_id, 'webhook' );
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		$gateway->payment_failed( $order, $reason, $transaction_id, 'webhook' );
		return new WP_REST_Response( array( 'success' => false, 'status' => 'failed' ), 200 );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns the full webhook URL to send to WiPay.
	 *
	 * @return string
	 */
	public static function get_webhook_url(): string {
		return rest_url( self::REST_NAMESPACE . self::REST_ROUTE );
	}

	/**
	 * Load and return the WiPay gateway instance from WooCommerce's registry.
	 *
	 * @return WC_WiPay_Gateway|null
	 */
	private function get_gateway(): ?WC_WiPay_Gateway {
		if ( ! function_exists( 'WC' ) ) {
			return null;
		}

		$gateways = WC()->payment_gateways();

		if ( ! $gateways ) {
			return null;
		}

		$all = $gateways->payment_gateways();

		return isset( $all['wipay'] ) && $all['wipay'] instanceof WC_WiPay_Gateway
			? $all['wipay']
			: null;
	}
}
