<?php
/**
 * Fyfy currency and currency symbol
 */

add_filter( 'woocommerce_currencies', 'add_fyfy_currency' );
add_filter( 'woocommerce_currency_symbol', 'add_fyfy_currency_symbol', 10, 2 );

function add_fyfy_currency( $currencies ) {
	$currencies['FYFY'] = 'Fyfy';
	return $currencies;
}

function add_fyfy_currency_symbol( $currency_symbol, $currency ) {
	switch( $currency ) {
		 case 'FYFY': $currency_symbol = 'FYFY&nbsp;'; break;
	}
	return $currency_symbol;
}
