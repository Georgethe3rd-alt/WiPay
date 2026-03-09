<?php
/**
 * WiPay Payment Gateway
 *
 * Integrates WiPay Caribbean's hosted checkout with WooCommerce by extending
 * WC_Payment_Gateway. The flow is:
 *
 *   1. Customer places order → WooCommerce calls process_payment().
 *   2. Plugin renders a self-submitting HTML form that POSTs the customer to
 *      WiPay's hosted payment page.
 *   3. After payment WiPay GETs the customer back to our `url` (return URL).
 *   4. WiPay also POSTs server-to-server to our `response_url` (webhook).
 *
 * @package WiPay_WooCommerce
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_WiPay_Gateway
 */
class WC_WiPay_Gateway extends WC_Payment_Gateway {

	/**
	 * WiPay API endpoint path (appended to the country-specific base URL).
	 *
	 * @var string
	 */
	const API_PATH = '/plugins/payments/request';

	/**
	 * WooCommerce endpoint query var used for the return (thank-you) callback.
	 *
	 * @var string
	 */
	const RETURN_ENDPOINT = 'wipay-return';

	/**
	 * Origin identifier sent to WiPay so they can track plugin usage.
	 *
	 * @var string
	 */
	const ORIGIN = 'WiPay_WooCommerce_v2';

	// -------------------------------------------------------------------------
	// Constructor
	// -------------------------------------------------------------------------

	/**
	 * Set up gateway properties and wire hooks.
	 */
	public function __construct() {
		$this->id                 = 'wipay';
		$this->method_title       = __( 'WiPay Caribbean', 'wipay-woocommerce' );
		$this->method_description = __( 'Accept credit card payments across the Caribbean via WiPay's secure hosted checkout.', 'wipay-woocommerce' );
		$this->has_fields         = false;
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load settings.
		$this->init_form_fields();
		$this->init_settings();

		// Map settings to properties.
		$this->title         = $this->get_option( 'title', __( 'Pay with Card (WiPay)', 'wipay-woocommerce' ) );
		$this->description   = $this->get_option( 'description', __( 'You will be redirected to WiPay\'s secure payment page to complete your purchase.', 'wipay-woocommerce' ) );
		$this->account_number = $this->get_option( 'account_number' );
		$this->environment   = $this->get_option( 'environment', 'sandbox' );
		$this->country_code  = $this->get_option( 'country_code', 'tt' );
		$this->currency      = $this->get_option( 'currency', 'TTD' );
		$this->fee_structure = $this->get_option( 'fee_structure', 'customer_pay' );
		$this->debug         = 'yes' === $this->get_option( 'debug', 'no' );

		// Gateway icon.
		$this->icon = $this->get_gateway_icon();

		// Hooks.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'on_settings_saved' ) );
		add_action( 'woocommerce_api_wipay_return', array( $this, 'handle_return' ) );

		// Enqueue checkout assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Render the redirect form on the pay-for-order page.
		add_action( 'woocommerce_receipt_wipay', array( $this, 'receipt_page' ) );

		// Customise thank-you page text.
		add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'thankyou_page' ), 10, 2 );
	}

	// -------------------------------------------------------------------------
	// Settings
	// -------------------------------------------------------------------------

	/**
	 * Define the admin settings form fields.
	 */
	public function init_form_fields(): void {
		$country_options = WC_WiPay_Countries::get_country_options();

		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable / Disable', 'wipay-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable WiPay Caribbean payment gateway', 'wipay-woocommerce' ),
				'default' => 'no',
			),
			'title'          => array(
				'title'       => __( 'Title', 'wipay-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Payment method name shown to the customer at checkout.', 'wipay-woocommerce' ),
				'default'     => __( 'Pay with Card (WiPay)', 'wipay-woocommerce' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'wipay-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Short description shown beneath the payment method title at checkout.', 'wipay-woocommerce' ),
				'default'     => __( 'You will be redirected to WiPay\'s secure payment page to complete your purchase.', 'wipay-woocommerce' ),
				'desc_tip'    => true,
			),
			'account_number' => array(
				'title'       => __( 'WiPay Account Number', 'wipay-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your WiPay merchant account number. Find this in your WiPay dashboard.', 'wipay-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'environment'    => array(
				'title'       => __( 'Environment', 'wipay-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Use Sandbox for testing. Switch to Live when ready to accept real payments.', 'wipay-woocommerce' ),
				'options'     => array(
					'sandbox' => __( 'Sandbox (testing)', 'wipay-woocommerce' ),
					'live'    => __( 'Live (production)', 'wipay-woocommerce' ),
				),
				'default'     => 'sandbox',
				'desc_tip'    => true,
			),
			'country_code'   => array(
				'title'       => __( 'Country', 'wipay-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Your WiPay-registered country. This determines which WiPay API endpoint is used.', 'wipay-woocommerce' ),
				'options'     => $country_options,
				'default'     => 'tt',
				'desc_tip'    => true,
			),
			'currency'       => array(
				'title'       => __( 'Currency', 'wipay-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Currency code sent to WiPay (e.g. TTD, JMD, USD). Auto-set when you change the country above.', 'wipay-woocommerce' ),
				'default'     => 'TTD',
				'desc_tip'    => true,
			),
			'fee_structure'  => array(
				'title'       => __( 'Fee Structure', 'wipay-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Decide who bears the WiPay processing fee.', 'wipay-woocommerce' ),
				'options'     => array(
					'customer_pay'    => __( 'Customer pays the fee', 'wipay-woocommerce' ),
					'merchant_absorb' => __( 'Merchant absorbs the fee', 'wipay-woocommerce' ),
				),
				'default'     => 'customer_pay',
				'desc_tip'    => true,
			),
			'debug'          => array(
				'title'       => __( 'Debug Mode', 'wipay-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging (WooCommerce → Status → Logs → wipay-woocommerce)', 'wipay-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'Logs payment requests and responses. Disable in production unless troubleshooting.', 'wipay-woocommerce' ),
			),
		);
	}

	/**
	 * Called after settings are saved in the admin: auto-sync currency to country.
	 */
	public function on_settings_saved(): void {
		$country  = $this->get_option( 'country_code', 'tt' );
		$currency = WC_WiPay_Countries::get_currency_for_country( $country );
		if ( $currency ) {
			$this->update_option( 'currency', $currency );
		}
		// Reset logger cache so new debug flag takes effect immediately.
		WC_WiPay_Logger::reset_cache();
	}

	/**
	 * Custom admin options HTML – adds a JS snippet to auto-fill currency when
	 * the country dropdown changes.
	 */
	public function admin_options(): void {
		$currencies = array();
		foreach ( WC_WiPay_Countries::get_countries() as $code => $data ) {
			$currencies[ $code ] = $data['currency'];
		}
		?>
		<h2><?php echo esc_html( $this->method_title ); ?></h2>
		<p><?php echo wp_kses_post( $this->method_description ); ?></p>
		<?php if ( 'sandbox' === $this->environment ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Sandbox mode is active.', 'wipay-woocommerce' ); ?></strong>
					<?php esc_html_e( 'No real payments will be processed. Switch to Live when ready.', 'wipay-woocommerce' ); ?>
				</p>
			</div>
		<?php endif; ?>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<script>
		(function($){
			var map = <?php echo wp_json_encode( $currencies ); ?>;
			$('#woocommerce_wipay_country_code').on('change', function(){
				var currency = map[$(this).val()];
				if (currency) {
					$('#woocommerce_wipay_currency').val(currency);
				}
			});
		})(jQuery);
		</script>
		<?php
	}

	// -------------------------------------------------------------------------
	// Checkout & receipt page
	// -------------------------------------------------------------------------

	/**
	 * Render the self-submitting redirect form on the WooCommerce receipt page.
	 *
	 * Hooked to: woocommerce_receipt_wipay
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function receipt_page( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'wipay-woocommerce' ) . '</p>';
			return;
		}

		$args     = $this->build_payment_args( $order );
		$endpoint = $this->get_api_endpoint();
		$gateway  = $this;

		WC_WiPay_Logger::debug(
			'Rendering payment form.',
			array(
				'order_id' => $order_id,
				'endpoint' => $endpoint,
			)
		);

		$template = WIPAY_PLUGIN_DIR . 'templates/payment-form.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Enqueue frontend CSS (checkout page only).
	 */
	public function enqueue_scripts(): void {
		if ( ! is_checkout() ) {
			return;
		}
		wp_enqueue_style(
			'wipay-checkout',
			WIPAY_PLUGIN_URL . 'assets/css/wipay-checkout.css',
			array(),
			WIPAY_VERSION
		);
	}

	/**
	 * Returns the URL to the gateway icon, or empty string if not available.
	 *
	 * @return string
	 */
	protected function get_gateway_icon(): string {
		$icon_url = WIPAY_PLUGIN_URL . 'assets/images/wipay-logo.svg';

		/**
		 * Filter the WiPay gateway icon URL.
		 *
		 * Return an empty string to hide the icon entirely.
		 *
		 * @since 2.0.0
		 *
		 * @param string $icon_url Absolute URL to the icon image.
		 */
		return (string) apply_filters( 'wipay_gateway_icon', $icon_url );
	}

	// -------------------------------------------------------------------------
	// Payment processing
	// -------------------------------------------------------------------------

	/**
	 * Process the payment for a given order.
	 *
	 * WooCommerce calls this when the customer submits the checkout form.
	 * Because WiPay is a hosted checkout we simply mark the order pending,
	 * store the redirect URL, and tell WooCommerce to redirect the customer.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array{result: string, redirect: string}|void
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found. Please try again.', 'wipay-woocommerce' ), 'error' );
			return;
		}

		/**
		 * Action fired immediately before a WiPay payment is initiated.
		 *
		 * @since 2.0.0
		 *
		 * @param WC_Order $order The WooCommerce order being paid.
		 */
		do_action( 'wipay_before_process_payment', $order );

		// Validate required settings.
		if ( empty( $this->account_number ) ) {
			WC_WiPay_Logger::error( 'WiPay account number is not configured.', array( 'order_id' => $order_id ) );
			wc_add_notice(
				__( 'Payment configuration error. Please contact the store owner.', 'wipay-woocommerce' ),
				'error'
			);
			return;
		}

		// Mark order as pending payment.
		$order->update_status( 'pending', __( 'Awaiting WiPay payment.', 'wipay-woocommerce' ) );

		WC_WiPay_Logger::info(
			'Payment initiated – redirecting customer to WiPay.',
			array(
				'order_id'    => $order_id,
				'environment' => $this->environment,
				'country'     => $this->country_code,
			)
		);

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart contents.
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Builds the full array of POST parameters for the WiPay API request.
	 *
	 * @param WC_Order $order The order being paid.
	 * @return array<string, string>
	 */
	public function build_payment_args( WC_Order $order ): array {
		$return_url  = $this->get_return_url_for_order( $order );
		$webhook_url = WC_WiPay_Webhook::get_webhook_url();

		$args = array(
			'account_number' => $this->account_number,
			'country_code'   => strtoupper( $this->country_code ),
			'currency'       => $this->currency,
			'environment'    => $this->environment,
			'fee_structure'  => $this->fee_structure,
			'method'         => 'credit_card',
			'order_id'       => (string) $order->get_id(),
			'origin'         => self::ORIGIN,
			'total'          => number_format( (float) $order->get_total(), 2, '.', '' ),
			'addr_email'     => $order->get_billing_email(),
			'url'            => $return_url,
			'response_url'   => $webhook_url,
		);

		/**
		 * Filter the payment request arguments sent to WiPay.
		 *
		 * @since 2.0.0
		 *
		 * @param array    $args  Payment arguments array.
		 * @param WC_Order $order The WooCommerce order.
		 */
		$args = (array) apply_filters( 'wipay_payment_args', $args, $order );

		WC_WiPay_Logger::debug( 'Payment args built.', $args );

		return $args;
	}

	/**
	 * Returns the WiPay API endpoint for the configured country.
	 *
	 * @return string
	 */
	public function get_api_endpoint(): string {
		return WC_WiPay_Countries::get_api_base_url( $this->country_code ) . self::API_PATH;
	}

	// -------------------------------------------------------------------------
	// Return URL callback (GET – customer redirect)
	// -------------------------------------------------------------------------

	/**
	 * Handle the customer being redirected back from WiPay after payment.
	 *
	 * Hooked to: woocommerce_api_wipay_return
	 *
	 * WiPay sends the following query params:
	 *   status, order_id, transaction_id, reasonDescription
	 */
	public function handle_return(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status         = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$raw_order_id   = isset( $_GET['order_id'] ) ? sanitize_text_field( wp_unslash( $_GET['order_id'] ) ) : '';
		$transaction_id = isset( $_GET['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_GET['transaction_id'] ) ) : '';
		$reason         = isset( $_GET['reasonDescription'] ) ? sanitize_text_field( wp_unslash( $_GET['reasonDescription'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$order_id = absint( $raw_order_id );
		$order    = wc_get_order( $order_id );

		WC_WiPay_Logger::info(
			'Return callback received.',
			array(
				'order_id'       => $order_id,
				'status'         => $status,
				'transaction_id' => $transaction_id,
			)
		);

		if ( ! $order ) {
			WC_WiPay_Logger::error( 'Return callback – order not found.', array( 'order_id' => $order_id ) );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		if ( 'success' === strtolower( $status ) ) {
			$this->payment_complete( $order, $transaction_id, 'return' );
			wp_safe_redirect( $this->get_return_url( $order ) );
		} else {
			$this->payment_failed( $order, $reason, $transaction_id, 'return' );
			wc_add_notice(
				__( 'Payment was not completed. Please try again or choose another payment method.', 'wipay-woocommerce' ),
				'error'
			);
			wp_safe_redirect( wc_get_checkout_url() );
		}
		exit;
	}

	// -------------------------------------------------------------------------
	// Payment state helpers
	// -------------------------------------------------------------------------

	/**
	 * Mark an order as successfully paid.
	 *
	 * Safe to call from both the return URL and the webhook handler – the
	 * payment_complete() call is idempotent in WooCommerce.
	 *
	 * @param WC_Order $order          The order being paid.
	 * @param string   $transaction_id WiPay transaction ID.
	 * @param string   $source         'return' or 'webhook' (for the order note).
	 */
	public function payment_complete( WC_Order $order, string $transaction_id, string $source ): void {
		// Avoid double-processing an already-completed order.
		if ( $order->is_paid() ) {
			WC_WiPay_Logger::debug(
				'payment_complete called on already-paid order – skipping.',
				array( 'order_id' => $order->get_id() )
			);
			return;
		}

		$order->payment_complete( $transaction_id );

		/* translators: 1: WiPay transaction ID, 2: callback source (return/webhook) */
		$order->add_order_note(
			sprintf(
				__( 'WiPay payment successful. Transaction ID: %1$s (via %2$s).', 'wipay-woocommerce' ),
				esc_html( $transaction_id ),
				esc_html( $source )
			)
		);

		WC_WiPay_Logger::info(
			'Payment complete.',
			array(
				'order_id'       => $order->get_id(),
				'transaction_id' => $transaction_id,
				'source'         => $source,
			)
		);

		/**
		 * Action fired after a WiPay payment is successfully completed.
		 *
		 * @since 2.0.0
		 *
		 * @param WC_Order $order          The completed order.
		 * @param string   $transaction_id WiPay transaction ID.
		 * @param string   $source         'return' or 'webhook'.
		 */
		do_action( 'wipay_after_payment_complete', $order, $transaction_id, $source );
	}

	/**
	 * Mark an order as failed.
	 *
	 * @param WC_Order $order          The order.
	 * @param string   $reason         Failure reason from WiPay.
	 * @param string   $transaction_id WiPay transaction ID (may be empty).
	 * @param string   $source         'return' or 'webhook'.
	 */
	public function payment_failed( WC_Order $order, string $reason, string $transaction_id, string $source ): void {
		$order->update_status(
			'failed',
			/* translators: 1: Reason, 2: Transaction ID, 3: Source */
			sprintf(
				__( 'WiPay payment failed. Reason: %1$s. Transaction ID: %2$s (via %3$s).', 'wipay-woocommerce' ),
				$reason ? $reason : __( 'Unknown', 'wipay-woocommerce' ),
				$transaction_id ? $transaction_id : __( 'N/A', 'wipay-woocommerce' ),
				$source
			)
		);

		WC_WiPay_Logger::warning(
			'Payment failed.',
			array(
				'order_id'       => $order->get_id(),
				'reason'         => $reason,
				'transaction_id' => $transaction_id,
				'source'         => $source,
			)
		);

		/**
		 * Action fired after a WiPay payment fails.
		 *
		 * @since 2.0.0
		 *
		 * @param WC_Order $order          The failed order.
		 * @param string   $reason         Failure reason from WiPay.
		 * @param string   $transaction_id WiPay transaction ID (may be empty).
		 * @param string   $source         'return' or 'webhook'.
		 */
		do_action( 'wipay_after_payment_failed', $order, $reason, $transaction_id, $source );
	}

	// -------------------------------------------------------------------------
	// Refunds
	// -------------------------------------------------------------------------

	/**
	 * Process a refund.
	 *
	 * WiPay does not currently expose a programmatic refund API. This method
	 * returns a WP_Error instructing the merchant to issue refunds manually
	 * through the WiPay merchant portal.
	 *
	 * @param int        $order_id WooCommerce order ID.
	 * @param float|null $amount   Refund amount.
	 * @param string     $reason   Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return new WP_Error(
			'wipay_refund_unavailable',
			__(
				'WiPay refunds must be processed manually through your WiPay merchant portal at wipaycaribbean.com. Please log in to your WiPay account to issue the refund.',
				'wipay-woocommerce'
			)
		);
	}

	// -------------------------------------------------------------------------
	// URL helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns the return URL that WiPay GETs after payment.
	 *
	 * Uses the WooCommerce API endpoint so WordPress can handle it without
	 * requiring a specific page ID.
	 *
	 * @param WC_Order $order The order.
	 * @return string
	 */
	public function get_return_url_for_order( WC_Order $order ): string {
		return add_query_arg(
			array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
			),
			WC()->api_request_url( 'wipay_return' )
		);
	}

	// -------------------------------------------------------------------------
	// Thank-you page
	// -------------------------------------------------------------------------

	/**
	 * Customise the order-received (thank you) page text.
	 *
	 * @param string   $text  Default WooCommerce text.
	 * @param WC_Order $order The order.
	 * @return string
	 */
	public function thankyou_page( $text, $order ): string {
		if ( $order && $this->id === $order->get_payment_method() ) {
			$text = __( 'Thank you for your order. Your payment has been received and is being processed by WiPay Caribbean.', 'wipay-woocommerce' );

			/**
			 * Filter the WiPay order-received page text.
			 *
			 * @since 2.0.0
			 *
			 * @param string   $text  Customised thank-you text.
			 * @param WC_Order $order The order.
			 */
			$text = (string) apply_filters( 'wipay_order_received_text', $text, $order );
		}
		return $text;
	}
}
