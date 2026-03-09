<?php
/**
 * WiPay Countries & Currencies
 *
 * Provides country-to-currency mapping and human-readable labels for all
 * territories supported by the WiPay Caribbean payment platform.
 *
 * @package WiPay_WooCommerce
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_WiPay_Countries
 *
 * Centralises all country/currency metadata so it can be consumed by the
 * gateway settings form, the payment request builder, and third-party code
 * via the `wipay_country_currencies` filter.
 */
class WC_WiPay_Countries {

	/**
	 * Returns the full map of supported countries.
	 *
	 * Each entry is keyed by the two-letter lowercase country code used in the
	 * WiPay API endpoint subdomain (e.g. "tt" → https://tt.wipayfinancial.com/…).
	 *
	 * Structure:
	 * ```
	 * [
	 *   'tt' => [
	 *     'name'     => 'Trinidad & Tobago',   // Human-readable label.
	 *     'currency' => 'TTD',                 // ISO 4217 currency code.
	 *     'flag'     => '🇹🇹',                 // Optional emoji flag.
	 *   ],
	 *   …
	 * ]
	 * ```
	 *
	 * @return array<string, array{name: string, currency: string, flag: string}>
	 */
	public static function get_countries(): array {
		$countries = array(
			'tt' => array(
				'name'     => __( 'Trinidad & Tobago', 'wipay-woocommerce' ),
				'currency' => 'TTD',
				'flag'     => 'TT',
			),
			'jm' => array(
				'name'     => __( 'Jamaica', 'wipay-woocommerce' ),
				'currency' => 'JMD',
				'flag'     => 'JM',
			),
			'bb' => array(
				'name'     => __( 'Barbados', 'wipay-woocommerce' ),
				'currency' => 'BBD',
				'flag'     => 'BB',
			),
			'lc' => array(
				'name'     => __( 'Saint Lucia', 'wipay-woocommerce' ),
				'currency' => 'XCD',
				'flag'     => 'LC',
			),
			'gd' => array(
				'name'     => __( 'Grenada', 'wipay-woocommerce' ),
				'currency' => 'XCD',
				'flag'     => 'GD',
			),
			'gy' => array(
				'name'     => __( 'Guyana', 'wipay-woocommerce' ),
				'currency' => 'GYD',
				'flag'     => 'GY',
			),
		);

		/**
		 * Filter the country-to-currency mapping.
		 *
		 * Allows third-party code to add or modify the list of supported
		 * WiPay countries and their associated currencies.
		 *
		 * @since 2.0.0
		 *
		 * @param array $countries Country data array keyed by lowercase country code.
		 */
		return (array) apply_filters( 'wipay_country_currencies', $countries );
	}

	/**
	 * Returns an associative array suitable for use as <select> options.
	 *
	 * Keys are the lowercase country codes; values are human-readable labels
	 * that include the currency code for clarity.
	 *
	 * @return array<string, string>
	 */
	public static function get_country_options(): array {
		$options   = array();
		$countries = self::get_countries();

		foreach ( $countries as $code => $data ) {
			/* translators: 1: Country name, 2: Currency code */
			$options[ $code ] = sprintf(
				'%s (%s)',
				$data['name'],
				$data['currency']
			);
		}

		return $options;
	}

	/**
	 * Returns the default currency for a given country code.
	 *
	 * @param string $country_code Lowercase two-letter country code (e.g. 'tt').
	 * @return string ISO 4217 currency code, or empty string if not found.
	 */
	public static function get_currency_for_country( string $country_code ): string {
		$countries = self::get_countries();
		$code      = strtolower( $country_code );

		return isset( $countries[ $code ] ) ? $countries[ $code ]['currency'] : '';
	}

	/**
	 * Returns a simple currency-code-to-country map (one-to-many collapsed).
	 *
	 * Useful for reverse look-ups. Where multiple countries share a currency
	 * (e.g. XCD) the first alphabetically-ordered country is returned.
	 *
	 * @return array<string, string> Currency code → country code.
	 */
	public static function get_currency_map(): array {
		$map       = array();
		$countries = self::get_countries();

		foreach ( $countries as $code => $data ) {
			if ( ! isset( $map[ $data['currency'] ] ) ) {
				$map[ $data['currency'] ] = $code;
			}
		}

		return $map;
	}

	/**
	 * Checks whether the supplied country code is supported.
	 *
	 * @param string $country_code Lowercase two-letter country code.
	 * @return bool
	 */
	public static function is_supported( string $country_code ): bool {
		$countries = self::get_countries();
		return array_key_exists( strtolower( $country_code ), $countries );
	}

	/**
	 * Returns the WiPay API base URL for a given country code.
	 *
	 * @param string $country_code Lowercase two-letter country code.
	 * @return string Full URL without trailing slash.
	 */
	public static function get_api_base_url( string $country_code ): string {
		return 'https://' . strtolower( $country_code ) . '.wipayfinancial.com';
	}
}
