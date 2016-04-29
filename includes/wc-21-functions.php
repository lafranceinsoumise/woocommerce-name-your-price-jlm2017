<?php
/**
 * NYP WC 2.1 Compatibility Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_nyp_get_product( $the_product = false, $args = array() ) {
	return get_product( $the_product, $args );
}

function wc_nyp_get_price_decimals(){
	return get_option( 'woocommerce_price_num_decimals' );
}

function wc_nyp_get_price_decimal_separator(){
	return get_option( 'woocommerce_price_decimal_sep' );
}

function wc_nyp_get_price_thousand_separator(){
	return get_option( 'woocommerce_price_thousand_sep' );
}