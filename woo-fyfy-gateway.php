<?php
/**
 * Plugin Name: FYFY Pay Checkout
 * Plugin URI: https://github.com/fyfylian/woocommerce-gateway-fyfy
 * Description: Let customers pay with Bitcoin, Ethereum and Fyfy
 * Author: Fyfy.io
 * Author URI: https://fyfy.io
 * Version: 3.3.1
 * Text Domain: wc-gateway-fyfy
 * Domain Path: /languages
 * Requires at least: 4.9
 * Tested up to: 5.6
 * WC requires at least: 3.5
 * WC tested up to: 4.9
 *
 * Copyright: (c) 2021 FYFY.IO / FYFY Network Ltd., and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-Fyfy
 * @author    Fyfy
 * @category  Admin
 * @copyright Copyright (c) 2021 FYFY.IO / FYFY Network Ltd., and WooCommerce
 *
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This Fyfy gateway forks the WooCommerce core "Cheque" payment gateway to create another payment method.
 */

defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function nq_show_no_woocommerce_warning() {
		echo '<div class="notice notice-error"><p>'. __( 'To use <strong>Fyfy Pay Checkout</strong>, you must have WooCommerce installed!', 'wc-gateway-fyfy' ) .'</p></div>';
	}
	add_action( 'admin_notices', 'nq_show_no_woocommerce_error' );
	return;
}

// Make sure the shop is running on PHP >= 7.1
if ( !defined('PHP_VERSION_ID') || PHP_VERSION_ID < 70100 ) {
	function nq_show_insufficient_php_version_error() {
		echo '<div class="notice notice-error"><p>'. __( 'To use <strong>Fyfy Pay Checkout</strong>, you need to use PHP >= 7.1.', 'wc-gateway-fyfy' ) .'</p></div>';
	}
	add_action( 'admin_notices', 'nq_show_insufficient_php_version_error' );
	return;
}

// Include FYFY currency
include_once( plugin_dir_path( __FILE__ ) . 'includes/fyfy_currency.php' );

$woo_fyfy_has_fiat = get_option( 'woocommerce_currency' ) !== 'FYFY';

// Extra checks when shop is using a FIAT currency
if ( $woo_fyfy_has_fiat ) {
	$woo_fyfy_has_https    = (!empty($_SERVER[ 'HTTPS' ]) && $_SERVER[ 'HTTPS' ] !== 'off') || $_SERVER[ 'SERVER_PORT' ] === 443;
	$woo_fyfy_is_localhost = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

	// Make sure the shop is on HTTPS
	if ( !( $woo_fyfy_has_https || $woo_fyfy_is_localhost ) ) {
		function nq_show_no_https_error() {
			/* translators: %s: Email address */
			echo '<div class="notice notice-error"><p>'. __( 'To use <strong>Fyfy Pay Checkout</strong>, your store must run under HTTPS (SSL encrypted).', 'wc-gateway-fyfy' ) . '</p><em>' . sprintf( __( 'If you believe this error is a mistake, contact us at %s.', 'wc-gateway-fyfy' ), '<a href="mailto:info@shop.fyfy.io">info@shop.fyfy.io</a>' ) .'</em></p></div>';
		}
		add_action( 'admin_notices', 'nq_show_no_https_error' );
		return;
	}

	// Make sure the shop is using a supported currency
	// $fastspot_currencies = [ 'usd', 'eur' ]; // Already contained in Coingecko array
	// https://api.coingecko.com/api/v3/simple/supported_vs_currencies + 'fyfy'
	$supported_exchange_currencies = [ 'btc', 'eth', 'ltc', 'bch', 'bnb', 'eos', 'xrp', 'xlm', 'usd', 'aed', 'ars', 'aud', 'bdt', 'bhd', 'bmd', 'brl', 'cad', 'chf', 'clp', 'cny', 'czk', 'dkk', 'eur', 'gbp', 'hkd', 'huf', 'idr', 'ils', 'inr', 'jpy', 'krw', 'kwd', 'lkr', 'mmk', 'mxn', 'myr', 'nok', 'nzd', 'php', 'pkr', 'pln', 'rub', 'sar', 'sek', 'sgd', 'thb', 'try', 'twd', 'uah', 'vef', 'vnd', 'zar', 'xdr', 'xag', 'xau' ];

	$supported_exchange_currencies[] = 'fyfy';

	if ( !in_array( strtolower( get_option( 'woocommerce_currency' ) ), $supported_exchange_currencies ) ) {;
		function nq_show_currency_not_supported_error() {
			echo '<div class="notice notice-error"><p>'
				. __( 'Your store uses a currency that is currently not supported by the <strong>Fyfy Pay Checkout</strong>.', 'wc-gateway-fyfy' )
				. ' <a href="https://api.coingecko.com/api/v3/simple/supported_vs_currencies">'
				. __( 'Find out which currencies are supported.', 'wc-gateway-fyfy' )
				. '</a></p></div>';
		}
		add_action( 'admin_notices', 'nq_show_currency_not_supported_error' );
		return;
	}
}

// Un-hide Wordpress' regular Custom Fields
// TODO: Integrate with ACF to display plugin data instead
add_filter('acf/settings/remove_wp_meta_box', '__return_false');


/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Fyfy gateway
 */
function wc_fyfy_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway_Fyfy';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_fyfy_add_to_gateways' );


/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_fyfy_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fyfy_gateway' ) . '">' . __( 'Settings', 'wc-gateway-fyfy' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_fyfy_gateway_plugin_links' );


// We load the plugin later to ensure WC is loaded first since we're extending it.
add_action( 'plugins_loaded', 'wc_fyfy_gateway_init', 11 );

/**
 * Initializes plugin
 *
 * @since 1.0.0
 */
function wc_fyfy_gateway_init() {

	class WC_Gateway_Fyfy extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			$this->id                 = 'fyfy_gateway';
			$this->has_fields         = true;
			$this->method_title       = __( 'Fyfy Pay Checkout', 'wc-gateway-fyfy' );
			$this->method_description = __( 'Receive payments in Bitcoin, Ethereum, and Fyfy. If you would like to be guided through the setup process, follow <a href="https://fyfy.github.io/tutorials/wordpress-payment-plugin-installation">this tutorial.</a>', 'wc-gateway-fyfy' );

			$this->DEFAULTS = [
				'margin' => 0,
				'validation_interval' => 5,
				'fee_fyfy' => 1,
				'fee_btc' => 40,
				'fee_eth' => 8,
				'tx_wait_duration' => 120, // 2 hours
				'confirmations_fyfy' => 10, // ~ 10 minutes
				'confirmations_btc' => 2,  // ~ 20 minutes
				'confirmations_eth' => 45, // ~ 10 minutes
			];

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->instructions = $this->get_option( 'instructions' );

			// Instantiate utility classes
			$this->crypto_manager = new Crypto_Manager( $this );

			// Define display texts
			$this->title       = __( 'Fyfy Pay Checkout', 'wc-gateway-fyfy' );
			$cfd = $this->get_currencies_for_description();
			$this->description = count( $cfd ) === 1
				/* translators: %s: Cryptocurrency name */
				? sprintf( __( 'Pay with %s.', 'wc-gateway-fyfy' ), $cfd[ 0 ] )
				/* translators: %1$s: Two cryptocurrency names separated by comma, %2$s: Cryptocurrency name */
				: sprintf( __( 'Pay with %1$s or %2$s.', 'wc-gateway-fyfy' ), $cfd[ 0 ], $cfd[ 1 ] );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'before_woocommerce_pay', array ($this, 'add_payment_button'));
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'add_instructions' ) );
			add_action( 'woocommerce_api_wc_gateway_fyfy', array( $this, 'handle_payment_response' ) );
			add_action( 'admin_notices', array( $this, 'do_settings_check' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_settings_script' ) );

			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

			// Add style, so it can be loaded in the header of the page
			wp_enqueue_style('FyfyPayment', plugin_dir_url( __FILE__ ) . 'styles.css');
		}

		/**
		 * Returns current plugin version
		 */
		public function version() {
			return get_file_data( __FILE__, [ 'Version' ], 'plugin')[ 0 ];
		}

		public function get_icon() {
			$currencies = $this->crypto_manager->get_accepted_cryptos();

			// Widths of the source icons
			$WIDTHS = [
				'fyfy' => 26,
				'btc' => 26,
				'eth' => 18,
			];

			$SPACING = 12;

			$image_width = array_reduce( array_map( function( $crypto ) use ( $WIDTHS ) {
				return $WIDTHS[ $crypto ];
			}, $currencies ), function( $acc, $width ) {
				return $acc + $width;
			}, 0 ) + $SPACING * ( count( $currencies ) - 1 );

			$offsets = [
				'fyfy' => 0,
				'btc' => $WIDTHS[ 'fyfy' ] + $SPACING,
				'eth' => in_array( 'btc', $currencies )
					? $WIDTHS[ 'fyfy' ] + $WIDTHS[ 'btc' ] + $SPACING * 2
					: $WIDTHS[ 'fyfy' ] + $SPACING,
			];

			$alt = implode( ', ', array_map( function( $crypto ) {
				return ucfirst( Crypto_Manager::iso_to_name( $crypto ) );
			}, $currencies ) );

			/**
			 * Data URLs need to be escaped like this:
			 * - all # must be %23
			 * - all double quotes (") must be single quotes (')
			 * - :// must be %3A%2F%2F
			 * - all slashes in attributes (/) must be %2F
			 */

			$defs = "<radialGradient id='fyfy-radial-gradient' cx='-166.58' cy='275.96' r='1.06' gradientTransform='matrix(-24.62, 0, 0, 21.78, -4075.39, -5984.84)' gradientUnits='userSpaceOnUse'><stop offset='0' stop-color='%23ec991c'/><stop offset='1' stop-color='%23e9b213'/></radialGradient>";

			$logo_fyfy = "<path fill='url(%23fyfy-radial-gradient)' d='M25.71,12.92,20.29,3.58A2.16,2.16,0,0,0,18.42,2.5H7.58A2.15,2.15,0,0,0,5.71,3.58L.29,12.92a2.14,2.14,0,0,0,0,2.16l5.42,9.34A2.15,2.15,0,0,0,7.58,25.5H18.42a2.16,2.16,0,0,0,1.87-1.08l5.42-9.34A2.14,2.14,0,0,0,25.71,12.92Z'/>";

			$logo_bitcoin = in_array( 'btc', $currencies ) ? "<g transform='translate(" . $offsets[ 'btc' ] . " 0)'><path fill='%23f7931a' d='M25.61,17.15A13,13,0,1,1,16.14,1.39,13,13,0,0,1,25.61,17.15Z'/><path fill='%23fff' d='M18.73,12.15c.26-1.73-1.06-2.66-2.86-3.28l.59-2.35L15,6.17l-.57,2.28-1.14-.27.57-2.3-1.42-.35-.59,2.34L11,7.66h0L9,7.16,8.63,8.68s1,.24,1,.26a.77.77,0,0,1,.67.83l-.67,2.67s0,0,0,0l-.94,3.74a.52.52,0,0,1-.65.34s-1-.26-1-.26l-.7,1.63,1.85.46,1,.27L8.61,21l1.42.35L10.62,19l1.14.29-.59,2.34L12.6,22l.59-2.36c2.43.46,4.26.27,5-1.93a2.5,2.5,0,0,0-1.31-3.46A2.26,2.26,0,0,0,18.73,12.15Zm-3.26,4.57c-.44,1.77-3.42.81-4.39.57l.79-3.14C12.83,14.39,15.93,14.87,15.47,16.72Zm.44-4.6c-.4,1.61-2.88.79-3.69.59l.71-2.84C13.74,10.07,16.33,10.44,15.91,12.12Z'/></g>" : "";

			$logo_ethereum = in_array( 'eth', $currencies ) ? "<g transform='translate(" . $offsets[ 'eth' ] . " 0)'><path class='cls-1' d='M9,21v7l9-12.08Z'/><path d='M9,10.36v9l9-5.09Z'/><path fill='%232f3030' d='M9,0V10.37l9,3.9Z'/><path fill='%23828384' d='M9,21v7L0,15.92Z'/><path fill='%23343535' d='M9,10.36v9L0,14.27Z'/><path fill='%23828384' d='M9,0V10.37l-9,3.9Z'/></g>" : "";

			$icon_src = "data:image/svg+xml,<svg xmlns='http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg' width='" . $image_width . "' height='28' viewBox='0 0 " . $image_width . " 28'><defs>" . $defs . "</defs>" . $logo_fyfy . $logo_bitcoin . $logo_ethereum . "</svg>";

			return '<img src="' . $icon_src . '" alt="' . $alt . '">';
		}

		public function get_mono_icon() {
			$currencies = $this->crypto_manager->get_accepted_cryptos();

			// Widths of the source icons
			$WIDTHS = [
				'fyfy' => 25,
				'btc' => 24,
				'eth' => 15,
			];

			$SPACING = 12;

			$image_width = array_reduce( array_map( function( $crypto ) use ( $WIDTHS ) {
				return $WIDTHS[ $crypto ];
			}, $currencies ), function( $acc, $width ) {
				return $acc + $width;
			}, 0 ) + $SPACING * ( count( $currencies ) - 1 );

			$offsets = [
				'fyfy' => 0,
				'btc' => $WIDTHS[ 'fyfy' ] + $SPACING,
				'eth' => in_array( 'btc', $currencies )
					? $WIDTHS[ 'fyfy' ] + $WIDTHS[ 'btc' ] + $SPACING * 2
					: $WIDTHS[ 'fyfy' ] + $SPACING,
			];

			$alt = implode( ', ', array_map( function( $crypto ) {
				return ucfirst( Crypto_Manager::iso_to_name( $crypto ) );
			}, $currencies ) );

			/**
			 * Data URLs need to be escaped like this:
			 * - all # must be %23
			 * - all double quotes (") must be single quotes (')
			 * - :// must be %3A%2F%2F
			 * - all slashes in attributes (/) must be %2F
			 */

			$logo_fyfy = "<path transform='translate(0 1)' d='M24.71,10,19.51,1a2.09,2.09,0,0,0-1.8-1H7.29a2.09,2.09,0,0,0-1.8,1L.29,10A2,2,0,0,0,.29,12L5.49,21a2.09,2.09,0,0,0,1.8,1H17.71a2.09,2.09,0,0,0,1.8-1L24.71,12A2,2,0,0,0,24.71,10Z'/>";

			$logo_bitcoin = in_array( 'btc', $currencies ) ? "<path transform='translate(" . $offsets[ 'btc' ] . " 0)' d='M9.1,23.64A12,12,0,1,0,.36,9.1,12,12,0,0,0,9.1,23.64ZM14.65,7.27c1.67.57,2.88,1.43,2.64,3h0a2.09,2.09,0,0,1-1.68,1.93,2.31,2.31,0,0,1,1.21,3.19c-.71,2-2.4,2.2-4.64,1.78l-.55,2.18-1.31-.32.53-2.16-1-.27-.54,2.16L8,18.47l.54-2.19-2.65-.67L6.5,14.1s1,.26,1,.24A.49.49,0,0,0,8.06,14L9.53,8.1a.7.7,0,0,0-.61-.77S8,7.1,8,7.1l.36-1.41L11,6.35l.54-2.16,1.31.32L12.3,6.63l1,.25.53-2.1,1.31.32Zm-4.16,7.84c1.07.28,3.42.9,3.8-.6h0c.38-1.53-1.9-2-3-2.29L11,12.14,10.23,15Zm1-4.24c.9.24,2.85.77,3.19-.6h0c.35-1.39-1.55-1.81-2.48-2l-.27-.06-.65,2.63Z'/>" : "";

			$logo_ethereum = in_array( 'eth', $currencies ) ? "<g transform='translate(" . $offsets[ 'eth' ] . " 0)'><path opacity='.7' d='M7.24.18v15.39l6.8-4.04L7.25.18z'/><path opacity='.5' d='M7.23.18L.43 11.53l6.8 4.04V.18z'/><path opacity='.7' d='M7.24 16.87v5.6l6.81-9.64-6.81 4.04z'/><path opacity='.5' d='M7.23 22.46v-5.6l-6.8-4.03 6.8 9.63z'/><path d='M7.24 15.57l6.8-4.05-6.8-3.1v7.15z'/><path opacity='.6' d='M.43 11.52l6.8 4.05V8.42l-6.8 3.1z'/></g>" : "";

			$icon_src = "data:image/svg+xml,<svg xmlns='http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg' fill='%23fff' width='" . $image_width . "' height='24' viewBox='0 0 " . $image_width . " 24'>" . $logo_fyfy . $logo_bitcoin . $logo_ethereum . "</svg>";

			return '<img src="' . $icon_src . '" alt="' . $alt . '">';
		}

		public function get_setting( $key ) {
			return $this->get_option( $key, isset( $this->DEFAULTS[ $key ] ) ? $this->DEFAULTS[ $key ] : null );
		}

		public function get_currencies_for_description() {
			$names = array_map( function( $iso ) {
				return ucfirst( Crypto_Manager::iso_to_name( $iso ) )/* . ' (' . strtoupper( $iso ) . ')'*/;
			}, $this->crypto_manager->get_accepted_cryptos() );
			// Join all names except the last one, but at least the first one
			$first = implode( ', ', array_slice( $names, 0, -1 ) ) ?: $names[ 0 ];
			// Get the last name, or false if there is only one
			$second = count( $names ) > 1 ? end( $names ) : false;
			// Create result array
			$result = [ $first ];
			// Only return the second string if it exists
			if ( $second ) $result[] = $second;
			return $result;
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
			// include_once() does not work here, as when saving the settings the file needs to be included twice
			include( plugin_dir_path( __FILE__ ) . 'settings.php' );
			$this->form_fields = $woo_fyfy_checkout_settings;
		}

		public function get_payment_request( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order->get_status() !== 'pending' ) {
				return new WP_Error( 'order_state_invalid', 'Order is not in pending state and thus cannot be paid.' );
			}

			$order_total = floatval( $order->get_total() );
			$order_currency = $order->get_currency();

			// To uniquely identify the payment transaction, we add a shortened hash of
			// the order details to the transaction message.
			$tx_message = ( !empty( $this->get_option( 'message' ) ) ? $this->get_option( 'message' ) . ' ' : '' )
				. '(' . $this->get_short_order_key( $order->get_order_key() ) . ')';

			$tx_message_bytes = unpack('C*', $tx_message); // Convert to byte array

			$fees = $this->crypto_manager->get_fees( count( $tx_message_bytes ) );

			// Collect common request properties used in both request types
			$request = [
				'appName' => get_bloginfo( 'name' ) ?: 'Shop',
				'shopLogoUrl' => $this->get_option( 'shop_logo_url' )
					?: get_site_icon_url()
					?: get_site_url() . '/wp-content/plugins/woocommerce-gateway-fyfy/assets/icon.svg',
			];

			if ( $order_currency === 'FYFY') {
				$order->update_meta_data( 'order_crypto_currency', 'fyfy' );
				$order->update_meta_data( 'order_total_fyfy', $order_total );

				// Use FyfyCheckoutRequest (version 1)
				$request = array_merge( $request, [
					'version' => 1,
					'recipient' => Order_Utils::get_order_recipient_address( $order, $this ),
					'value' => intval( Order_Utils::get_order_total_crypto( $order ) ),
					'fee' => $fees[ 'fyfy' ],
					'extraData' => $tx_message,
				] );
			} else {
				// Check if the order already has a payment or an unexpired quote
				$transaction_hash = $order->get_meta( 'transaction_hash' ) ?: null;
				$expires = $order->get_meta( 'crypto_rate_expires' ) ?: 0;
				$stored_request = $order->get_meta( 'nc_payment_request' ) ?: null;
				// Send the old quote if a tx for the order has already been found, or
				// the expiry is still more than 3 minutes (180 seconds) away
				if ( ( $transaction_hash || ( $expires - 180 ) > time() ) && $stored_request ) {
					// Send stored request
					$request = json_decode( $stored_request, true );
					// The 'time' property is not updated on purpose, so that the Hub displays a run-down timer instead of a full timer
				} else {
					$price_service = $this->get_option( 'price_service' );
					include_once( dirname( __FILE__ ) . '/price_services/' . $price_service . '.php' );
					$class = 'WC_Gateway_Fyfy_Price_Service_' . ucfirst( $price_service );
					$price_service = new $class( $this );

					$accepted_cryptos = $this->crypto_manager->get_accepted_cryptos();

					// Apply margin to order value
					$margin = floatval( $this->get_setting( 'margin' ) ) / 100;
					$effective_order_total = $order_total * ( 1 + $margin );

					// Get pricing info from price service
					$pricing_info = $price_service->get_prices( $accepted_cryptos, $order_currency, $effective_order_total );

					if ( is_wp_error( $pricing_info ) ) {
						$order->update_meta_data( 'conversion_error', $pricing_info->get_error_message() );
						return $pricing_info;
					}

					// Set quote expirery
					$expires = strtotime( '+15 minutes' );
					// $order_expiry = Order_Utils::get_order_hold_expiry( $order );
					// if ( $order_expiry ) $expires = min( $expires, $order_expiry );
					$order->update_meta_data( 'crypto_rate_expires', $expires );

					// Process result from price service
					// Price service can either return prices or quotes, requiring different handling
					$order_totals_crypto = [];
					if ( array_key_exists( 'prices', $pricing_info ) ) {
						$prices = $pricing_info[ 'prices' ];
						$order_totals_crypto = Crypto_Manager::calculate_quotes( $effective_order_total, $prices );
					}
					else if ( array_key_exists( 'quotes', $pricing_info ) ) {
						$quotes = $pricing_info[ 'quotes' ];
						$order_totals_crypto = Crypto_Manager::format_quotes( $effective_order_total, $quotes );
					}
					else {
						return new WP_Error( 'service',  __( 'Pay Checkout is temporarily not available. Please try reloading this page. (Issue: price service did not return any pricing information.)', 'wc-gateway-fyfy' ));
					}

					$fees = array_key_exists( 'fees', $pricing_info )
						? $pricing_info[ 'fees' ]
						: $fees;

					$fees_per_byte = array_key_exists( 'fees_per_byte', $pricing_info )
						? $pricing_info[ 'fees_per_byte' ]
						: $this->crypto_manager->get_fees_per_byte();

					foreach ( $accepted_cryptos as $crypto ) {
						$order->update_meta_data( 'order_total_' . $crypto, $order_totals_crypto[ $crypto ] );
					}

					// Convert coins into smallest units
					$order_totals_unit = Crypto_Manager::coins_to_units( $order_totals_crypto );

					// Generate CSRF token
					function generateRandomString( $length ) {
						$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						$randomString = '';
						for ( $i = 0; $i < $length; $i++ ) {
							$randomString .= $characters[ rand( 0, strlen ($characters ) - 1 ) ];
						}
						return $randomString;
					}
					$csrf_token = generateRandomString( 32 );
					$order->update_meta_data( 'checkout_csrf_token', $csrf_token );

					// Generate callback URL
					$callback_url = $this->get_fyfy_callback_url( 'fyfy_checkout_callback', $order_id );

					// Use MultiCurrencyCheckoutRequest (version 2)
					$payment_options = [];
					foreach ( $accepted_cryptos as $crypto ) {
						$amount = $order_totals_unit[ $crypto ];
						$fee = $fees[ $crypto ];
						$fee_per_byte = $fees_per_byte[ $crypto ];

						$protocolSpecific = $crypto === 'eth' ? [
							'gasLimit' => $fee[ 'gas_limit' ],
							'gasPrice' => strval( $fee[ 'gas_price' ] ),
						] : [
							'fee' => $fee,
							'feePerByte' => $fee_per_byte,
						];

						if ( $crypto === 'fyfy' ) {
							$protocolSpecific[ 'extraData' ] = $tx_message;
							$protocolSpecific[ 'recipient' ] = Order_Utils::get_order_recipient_addresses( $order, $this )[ 'fyfy' ];
						}

						$payment_option = [
							'type' => 0, // 0 = DIRECT
							'currency' => $crypto,
							'expires' => $expires,
							'amount' => $amount,
							'protocolSpecific' => $protocolSpecific,
						];

						if ( $margin !== 0.0 ) { // $margin is typed as a float, so need to compare to a float
							$payment_option[ 'vendorMarkup' ] = $margin;
						}

						$payment_options[] = $payment_option;
					};

					$request = array_merge( $request, [
						'version' => 2,
						'callbackUrl' => $callback_url,
						'csrf' => $csrf_token,
						'time' => time(),
						'fiatAmount' => $order_total,
						'fiatCurrency' => $order_currency,
						'paymentOptions' => $payment_options,
					] );

					$order->update_meta_data( 'nc_payment_request', json_encode( $request ) );
				}
			}

			$order->save();

			return $request;
		}

		public function handle_payment_response() {
			$order_id = $this->get_param( 'id' );

			if ( empty( $order_id ) ) {
				// Redirect to main site
				wp_redirect( get_site_url() );
				exit;
			}

			$is_valid = $this->validate_response( $order_id );

			if ( !$is_valid ) {
				// Redirect to payment page
				$order = wc_get_order( $order_id );
				wp_redirect( $order->get_checkout_payment_url( $on_checkout = true ) );
				exit;
			}

			$redirect = $this->process_payment( $order_id );

			wp_redirect( $redirect[ 'redirect' ] );
			exit;
		}

		/**
		 * Defines the text displayed in the payment gateway box on
		 * the payment method selection screen (checkout page).
		 */
		public function payment_fields() {
			$description = $this->get_description();
			if ( $description ) {
				echo wpautop( wptexturize( $description ) );
				echo '<p><a href="https://shop.fyfy.io" class="about_fyfy" target="_blank">' . esc_html__( 'What is Fyfy?', 'wc-gateway-fyfy' ) . '</a></p>';
			}
		}

		public function add_payment_button() {
			$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$order = wc_get_order( $order_id );

			// Don't show our button for payments with another method
			if ( !$order || $order->get_payment_method() !== $this->id ) return;

			$request = $this->get_option( 'rpc_behavior' ) === 'popup' ? $this->get_payment_request( $order_id ) : [];

			if ( is_wp_error( $request ) ) {
				// The invalid state error is handled by WooCommerce itself already
				if ( $request->get_error_code() === 'order_state_invalid' ) return;

				wc_print_notice( $request->get_error_message(), 'error' );
				return;
			}

			// These scripts are enqueued at the end of the page
			wp_enqueue_script('HubApi', plugin_dir_url( __FILE__ ) . 'js/HubApi.standalone.umd.js', [], $this->version(), true );

			wp_register_script( 'FyfyCheckout', plugin_dir_url( __FILE__ ) . 'js/checkout.js', [ 'jquery', 'HubApi' ], $this->version(), true );
			wp_localize_script( 'FyfyCheckout', 'CONFIG', array(
				'HUB_URL'      => $this->get_option( 'network' ) === 'main' ? 'https://hub.fyfy.io' : 'https://hub.fyfy-testnet.io',
				'RPC_BEHAVIOR' => $this->get_option( 'rpc_behavior' ),
				'REQUEST'      => json_encode( $request ),
			) );
			wp_enqueue_script( 'FyfyCheckout' );

			$submitUrl = $this->get_option( 'rpc_behavior' ) === 'popup' ? $this->get_fyfy_hub_return_url( $order_id ) : '';
			?>
			<form id="pay_with_fyfy" method="POST" action="<?php echo $submitUrl; ?>">
				<div id="fyfy_gateway_info_block">
					<?php if ( $this->get_option( 'rpc_behavior' ) === 'popup' ) { ?>
						<noscript>
							<strong>
								<?php _e( 'Javascript is required to pay with cryptocurrency. Please activate Javascript to continue.', 'wc-gateway-fyfy' ); ?>
							</strong>
						</noscript>

						<input type="hidden" name="status" id="status" value="">
						<input type="hidden" name="result" id="result" value="">
					<?php } ?>

					<?php if ( $this->get_option( 'rpc_behavior' ) === 'redirect' ) { ?>
						<?php // https://github.com/woocommerce/woocommerce/blob/master/templates/checkout/payment-method.php#L23 ?>
						<input type="hidden" name="payment_method" value="<?php echo $this->id; ?>">

						<?php // https://github.com/woocommerce/woocommerce/blob/master/templates/checkout/form-pay.php#L85 ?>
						<input type="hidden" name="woocommerce_pay" value="1">

						<?php // https://github.com/woocommerce/woocommerce/blob/master/templates/checkout/form-pay.php#L95 ?>
						<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
					<?php } ?>

					<button type="submit" class="button" id="fyfy_pay_button">
						<span><?php
							/* translators: Used on the payment button: "PAY WITH <crypto icons>" */
							_e( 'PAY WITH', 'wc-gateway-fyfy' );
						?></span>
						<?php echo $this->get_mono_icon( $monocolor = true ); ?>
					</button>
				</div>

				<div id="fyfy_payment_received_block" class="hidden">
					<i class="fas fa-check-circle" style="color: seagreen;"></i>
					<?php _e( 'Payment received', 'wc-gateway-fyfy' ); ?>
				</div>
			</form>
			<?php
		}

		public function get_short_order_key( $order_key ) {
			return strtoupper( substr( $order_key, -6 ) );
		}

		public function get_param( $key, $data = 'request' ) {
			if ( is_string( $data ) ) {
				switch ( $data ) {
					case 'get': $data = $_GET; break;
					case 'post': $data = $_POST; break;
					case 'request': $data = $_REQUEST; break;
					default: throw new Exception( 'Unknown data type: ' . htmlspecialchars( $data ) ); break;
				}
			}

			if ( !isset( $data[ $key ] ) ) return null;
			return sanitize_text_field( $data[ $key ] );
		}

		/**
		 * Response validation to be used by custom integrations
		 *
		 * @param {number} $order_id
		 * @param {object|string} [$response]
		 */
		public function validate_response( $order_id, $response = 'request' ) {
			$status = $this->get_param( 'status', $response );
			if ( $status !== 'OK' ) {
				/* translators: %s: Error message */
				wc_add_notice( sprintf( __( 'Fyfy Payment failed. (%s).', 'wc-gateway-fyfy' ), __( 'Response code not "OK"', 'wc-gateway-fyfy' ) ), 'error' );
				return false;
			}

			$result = $this->get_param( 'result', $response );
			$result = str_replace( "\\", "", $result ); // JSON string may be "sanitized" with backslashes, which is a JSON syntax error
			try {
				$result = json_decode( $result );
			} catch (Exception $e) {
				wc_add_notice( sprintf( __( 'Fyfy Payment failed. (%s).', 'wc-gateway-fyfy' ), __( 'Could not decode Hub result', 'wc-gateway-fyfy' ) ) . '[' . $e->getMessage(). ']', 'error' );
			}
			if ( empty( $result ) ) {
				wc_add_notice( sprintf( __( 'Fyfy Payment failed. (%s).', 'wc-gateway-fyfy' ), __( 'Hub result is empty', 'wc-gateway-fyfy' ) ), 'error' );
				return false;
			}

			$order = wc_get_order( $order_id );

			$currency = Order_Utils::get_order_currency( $order, false );

			if ( $currency === 'fyfy' ) {
				$transaction_hash = $result->hash;
				$customer_fyfy_address = $result->raw->sender;

				if ( !$transaction_hash ) {
					wc_add_notice( __( 'You need to confirm the Fyfy payment first.', 'wc-gateway-fyfy' ), 'error' );
					return false;
				}

				if ( strlen( $transaction_hash) !== 64 ) {
					wc_add_notice( __( 'Invalid transaction hash.', 'wc-gateway-fyfy' ) . ' (' . $transaction_hash . '). ' . __( 'Please contact support with this error message.', 'wc-gateway-fyfy' ), 'error' );
					return false;
				}

				$order->update_meta_data( 'transaction_hash', $transaction_hash );
				$order->update_meta_data( 'customer_fyfy_address', $customer_fyfy_address );
				$order->save();

				return true;
			} else {
				return $result->success ?: false;
			}
		}

		/**
		 * Output for the order received page.
		 */
		public function add_instructions() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}

		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && $order->get_payment_method() === $this->id && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {

			$order = wc_get_order( $order_id );

			if ( is_checkout() ) {
				// Remove cart
				WC()->cart->empty_cart();

				if ( $this->get_option( 'rpc_behavior' ) === 'redirect' ) {
					// Redirect to Hub for payment

					$target = $this->get_option( 'network' ) === 'main' ? 'https://hub.fyfy.io' : 'https://hub.fyfy-testnet.io';
					$id = 42;
					$returnUrl = $this->get_fyfy_hub_return_url( $order_id );
					$command = 'checkout';
					$args = [ $this->get_payment_request( $order_id ) ];
					$responseMethod = 'http-post';

					include_once( plugin_dir_path( __FILE__ ) . 'fyfy-utils/RpcUtils.php' );

					$url = Fyfy\Utils\RpcUtils::prepareRedirectInvocation(
						$target,
						$id,
						$returnUrl,
						$command,
						$args,
						$responseMethod
					);

					return [
						'result'   => 'success',
						'redirect' => $url
					];
				}

				// Return payment-page redirect from where the Hub popup is opened
				return array(
					'result' 	=> 'success',
					'redirect'	=> $order->get_checkout_payment_url( $on_checkout = true )
				);
			}

			// Mark as on-hold (we're awaiting transaction validation)
			$order->update_status( 'on-hold', __( 'Waiting for transaction to be validated.', 'wc-gateway-fyfy' ) );

			// Return thank-you redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_order_received_url(),
			);
		}

		public function do_settings_check() {
			if( $this->enabled === "yes" ) {
				$this->do_store_fyfy_address_check();
			}

			$this->display_errors();
		}

		// Check if the store FYFY address is set and show admin notice otherwise
		public function do_store_fyfy_address_check() {
			if( empty( $this->get_option( 'fyfy_address' ) ) ) {
				$plugin_settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fyfy_gateway' );
				echo '<div class="error notice"><p>'
					. __( 'You must fill in your store\'s Fyfy address to be able to accept payments in FYFY.', 'wc-gateway-fyfy' )
					. ' <a href="' . $plugin_settings_url . '">'
					. __( 'Set your Fyfy address here.', 'wc-gateway-fyfy' )
					. '</a>'
				. '</p></div>';
			}
		}

		/**
		 * Enqueue script on WooCommerce settings pages
		 *
		 * @since 2.2.1
		 * @param string $hook - Name of the current admin page.
		 */
		public function enqueue_admin_settings_script( $hook ) {
			if ( $hook !== 'woocommerce_page_wc-settings' ) return;
			wp_enqueue_style( 'FyfySettings', plugin_dir_url( __FILE__ ) . 'css/settings.css', [], $this->version());
			wp_enqueue_script( 'FyfySettings', plugin_dir_url( __FILE__ ) . 'js/settings.js', [ 'jquery' ], $this->version(), true );
		}

		public function validate_shop_logo_url_field( $key, $value ) {
			// Skip validation & reset when value is empty
			if ( empty( $value ) ) return $value;

			$parsed_shop_url = parse_url( get_site_url() );
			$parsed_logo_url = parse_url( $value );

			if ( !isset( $parsed_shop_url[ 'scheme' ] ) || !isset( $parsed_shop_url[ 'host' ] ) ) {
				// Comparison impossible
				$this->add_error( 'Cannot validate Shop Logo URL because your WordPress Site URL is not set or invalid.', 'wc-gateway-fyfy' );
				return $value;
			}

			if ( !isset( $parsed_logo_url[ 'scheme' ] ) || !isset( $parsed_logo_url[ 'host' ] ) ) {
				$this->add_error( 'Invalid Shop Logo URL.', 'wc-gateway-fyfy' );
				return;
			}

			$shop_origin = $parsed_shop_url[ 'scheme' ] . '://'
				. $parsed_shop_url[ 'host' ]
				. ( isset($parsed_shop_url[ 'port' ]) ? ':' . $parsed_shop_url[ 'port' ] : '' );
			$logo_origin = $parsed_logo_url[ 'scheme' ] . '://'
				. $parsed_logo_url[ 'host' ]
				. ( isset($parsed_logo_url[ 'port' ]) ? ':' . $parsed_logo_url[ 'port' ] : '' );

			if ( $shop_origin !== $logo_origin ) {
				$this->add_error( 'The Shop Logo must be hosted on the same domain as the shop.', 'wc-gateway-fyfy' );
				return;
			}

			return $value;
		}

		public function validate_bitcoin_xpub_field( $key, $value ) {
			return $this->parse_xpub( $value, 'btc', 'Bitcoin' );
		}

		public function validate_ethereum_xpub_field( $key, $value ) {
			return $this->parse_xpub( $value, 'eth', 'Ethereum' );
		}

		public function parse_xpub( $value, $currency_code, $currency_name ) {
			// Skip validation & reset when value is empty
			if ( empty( $value ) ) return $value;

			// Or when it didn't change
			$old_value = $this->get_option( strtolower( $currency_name ) . '_xpub' );
			if ( $value === $old_value ) return $old_value;

			include_once( dirname( __FILE__ ) . '/fyfy-xpub/vendor/autoload.php' );

			try {
				Fyfy\XPub::fromString( $value );
			} catch (Exception $error) {
				/* translators: 1: Currency full name (e.g. 'Bitcoin'), 2: Setting name */
				$this->add_error( sprintf( __( '<strong>%1$s %2$s</strong> was not saved:', 'wc-gateway-fyfy' ), $currency_name, __( 'Wallet Account Public Key', 'wc-gateway-fyfy' ) ) . ' ' . $error->getMessage() );
				return $old_value;
			}

			// Reset address index
			$this->update_option( 'current_address_index_' . $currency_code, -1 );

			return $value;
		}

		private function get_fyfy_hub_return_url( $id ) {
			return $this->get_fyfy_callback_url( 'WC_Gateway_Fyfy', $id );
		}

		private function get_fyfy_callback_url($action, $id) {
			// Test if the REST API is available
			$has_rest_api = count( rest_get_server()->get_routes() ) > 0;
			// TODO: Test by sending a request to the REST API?

			if ( $has_rest_api ) {
				return get_site_url() . '/' . 'wc-api/' . $action . '?id=' . $id;
			}

			return admin_url('admin-ajax.php') . '?action=' . strtolower( $action ) . '&id=' . $id;
		}

	} // end WC_Gateway_Fyfy class

	// Handle admin-ajax.php return URLs
	function woo_fyfy_handle_payment_response() {
		$gateway = new WC_Gateway_Fyfy();
		$gateway->handle_payment_response();
	}
	add_action( 'wp_ajax_wc_gateway_fyfy', 'woo_fyfy_handle_payment_response' );
	add_action( 'wp_ajax_nopriv_wc_gateway_fyfy', 'woo_fyfy_handle_payment_response' );

} // end wc_fyfy_gateway_init()

// Includes that register actions and filters and are thus self-calling
include_once( plugin_dir_path( __FILE__ ) . 'includes/bulk_actions.php' );
include_once( plugin_dir_path( __FILE__ ) . 'includes/validation_scheduler.php' );
include_once( plugin_dir_path( __FILE__ ) . 'includes/webhook.php' );

// Utility classes called from other code
include_once( plugin_dir_path( __FILE__ ) . 'includes/order_utils.php' );
include_once( plugin_dir_path( __FILE__ ) . 'includes/crypto_manager.php' );
