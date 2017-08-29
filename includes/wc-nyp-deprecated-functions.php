<?php
/**
 * Deprecated Functions 
 */

function wc_nyp_get_product( $the_product = false, $args = array() ) {
	_deprecated_function( 'wc_nyp_get_product', '2.5.0', 'wc_get_product' );
	return wc_get_product( $the_product, $args );
}

function wc_nyp_get_price_decimals(){
	_deprecated_function( 'wc_nyp_get_price_decimals', '2.5.0', 'wc_get_product' );
	return wc_get_price_decimals();
}

function wc_nyp_get_price_decimal_separator(){
	_deprecated_function( 'wc_nyp_get_price_decimal_separator', '2.5.0', 'wc_get_product' );
	return wc_get_price_decimal_separator();
}

function wc_nyp_get_price_thousand_separator(){
	_deprecated_function( 'wc_nyp_get_price_thousand_separator', '2.5.0', 'wc_get_product' );
	return wc_get_price_thousand_separator();
}