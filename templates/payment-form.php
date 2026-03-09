<?php
/**
 * WiPay payment redirect form template.
 *
 * This template is rendered on the WooCommerce "pay for order" page when the
 * customer is ready to be sent to WiPay's hosted checkout. It renders a hidden
 * HTML form and auto-submits it via JavaScript, providing a clean loading
 * screen while the browser posts to WiPay.
 *
 * Available variables (set by the gateway before loading this template):
 *   $gateway      WC_WiPay_Gateway   – the gateway instance
 *   $order        WC_Order           – the order being paid
 *   $args         array              – pre-built payment request fields
 *   $endpoint     string             – full WiPay API endpoint URL
 *
 * @package WiPay_WooCommerce
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

// Bail if the required variables are missing (direct file access guard).
if ( ! isset( $gateway, $order, $args, $endpoint ) ) {
	return;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Redirecting to WiPay…', 'wipay-woocommerce' ); ?></title>
	<style>
		/* Minimal inline styles so this page looks presentable even if the
		   main stylesheet hasn't loaded. The checkout CSS handles the rest. */
		*, *::before, *::after { box-sizing: border-box; }
		body {
			margin: 0;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: #f7f7f7;
			color: #333;
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
		}
		.wipay-redirect-wrap {
			text-align: center;
			padding: 2rem;
			max-width: 480px;
		}
		.wipay-redirect-wrap img {
			max-width: 180px;
			margin-bottom: 1.5rem;
		}
		.wipay-redirect-wrap h2 {
			font-size: 1.4rem;
			margin: 0 0 .75rem;
			color: #1a1a2e;
		}
		.wipay-redirect-wrap p {
			font-size: .95rem;
			color: #555;
			margin: 0 0 1.5rem;
		}
		.wipay-spinner {
			display: inline-block;
			width: 48px;
			height: 48px;
			border: 4px solid #e0e0e0;
			border-top-color: #005baa;
			border-radius: 50%;
			animation: wipay-spin 0.8s linear infinite;
			margin-bottom: 1.5rem;
		}
		@keyframes wipay-spin {
			to { transform: rotate(360deg); }
		}
		.wipay-manual-submit {
			display: inline-block;
			margin-top: 1rem;
			padding: .6rem 1.4rem;
			background: #005baa;
			color: #fff;
			border: none;
			border-radius: 4px;
			font-size: .95rem;
			cursor: pointer;
			text-decoration: none;
			transition: background .2s;
		}
		.wipay-manual-submit:hover { background: #004a8a; }
	</style>
</head>
<body>
	<div class="wipay-redirect-wrap" role="main">

		<?php
		$icon_url = WIPAY_PLUGIN_URL . 'assets/images/wipay-logo.svg';
		if ( $icon_url ) :
		?>
			<img
				src="<?php echo esc_url( $icon_url ); ?>"
				alt="<?php esc_attr_e( 'WiPay Caribbean', 'wipay-woocommerce' ); ?>"
				width="180"
				height="60"
			>
		<?php endif; ?>

		<div class="wipay-spinner" aria-hidden="true"></div>

		<h2><?php esc_html_e( 'Redirecting to WiPay…', 'wipay-woocommerce' ); ?></h2>
		<p>
			<?php
			esc_html_e(
				'You are being securely redirected to the WiPay payment page. Please do not close this window.',
				'wipay-woocommerce'
			);
			?>
		</p>

		<form
			id="wipay-payment-form"
			action="<?php echo esc_url( $endpoint ); ?>"
			method="POST"
			aria-label="<?php esc_attr_e( 'WiPay payment form', 'wipay-woocommerce' ); ?>"
		>
			<?php foreach ( $args as $key => $value ) : ?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( (string) $value ); ?>"
				>
			<?php endforeach; ?>

			<noscript>
				<button type="submit" class="wipay-manual-submit">
					<?php esc_html_e( 'Click here to pay', 'wipay-woocommerce' ); ?>
				</button>
			</noscript>
		</form>

	</div>

	<script>
	/* Auto-submit the payment form as soon as the DOM is ready. */
	(function () {
		'use strict';
		var form = document.getElementById('wipay-payment-form');
		if (form) {
			form.submit();
		}
	})();
	</script>
</body>
</html>
