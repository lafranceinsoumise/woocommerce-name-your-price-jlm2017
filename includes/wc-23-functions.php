<?php
/**
 * NYP WC 2.3 Compatibility Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
function wc_nyp_get_product( $the_product = false, $args = array() ) {
	return wc_get_product( $the_product, $args );
}

function wc_nyp_get_price_decimals(){
	return wc_get_price_decimals();
}

function wc_nyp_get_price_decimal_separator(){
	return wc_get_price_decimal_separator();
}

function wc_nyp_get_price_thousand_separator(){
	return wc_get_price_thousand_separator();
}