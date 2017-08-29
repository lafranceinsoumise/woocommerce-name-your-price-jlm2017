<?php
/**
 * WC_Name_Your_Price_Core_Compatibility class
 *
 * @author   Kathy Darling <helgatheviking@gmail.com>
 * @package  WooCommerce Name Your Price
 * @since    1.5.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions for WC core back-compatibility.
 *
 * @class  WC_Name_Your_Price_Core_Compatibility
 * @since  1.5.0
 */
class WC_Name_Your_Price_Core_Compatibility {

	/**
	 * Cache 'gte' comparison results.
	 * @var array
	 */
	private static $is_wc_version_gte = array();

	/**
	 * Cache 'gt' comparison results.
	 * @var array
	 */
	private static $is_wc_version_gt = array();

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @since  1.5.0
	 *
	 * @return string
	 */
	private static function get_wc_version() {
		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @since  1.5.0
	 *
	 * @param  string  $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is >= $version
	 */
	public static function is_wc_version_gte( $version ) {
		if ( ! isset( self::$is_wc_version_gte[ $version ] ) ) {
			self::$is_wc_version_gte[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
		}
		return self::$is_wc_version_gte[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @since  1.5.0
	 *
	 * @param  string  $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
		}
		return self::$is_wc_version_gt[ $version ];
	}

	/**
	 * Back-compat wrapper for 'get_parent_id'.
	 *
	 * @param  WC_Product  $product
	 * @return mixed
	 * @since  2.5.0 
	 */
	public static function get_parent_id( $product ) {
		if ( self::is_wc_version_gte( '3.0.0' ) ) {
			return $product->get_parent_id();
		} else {
			return $product->is_type( 'variation' ) ? absint( $product->id ) : 0;
		}
	}

	/**
	 * Back-compat wrapper for 'get_id'.
	 *
	 * @since  2.5.0 
	 *
	 * @param  WC_Product  $product
	 * @return mixed
	 */
	public static function get_id( $product ) {
		if ( is_object( $product ) ){
			if ( self::is_wc_version_gte( '3.0.0' ) ) {
				$product_id = $product->get_id();
			} else {
				$product_id = $product->is_type( 'variation' ) ? $product->variation_id : $product->id;
			}
		} else {
			$product_id = $product;
		}
		return absint( $product_id );
	}

	/**
	 * Back-compat wrapper for getting CRUD object props directly.
	 *
	 * @since  2.5.0 
	 *
	 * @param  object  $obj
	 * @param  string  $prop
	 * @param  string  $context
	 * @return mixed
	 */
	public static function get_prop( $obj, $prop, $context = 'view' ) {
		if ( self::is_wc_version_gte( '3.0.0' ) ) {
			$get_fn = 'get_' . $prop;
			return is_callable( array( $obj, $get_fn ) ) ? $obj->$get_fn( $context ) : null;
		} else {

			if ( 'status' === $prop ) {
				$value = isset( $obj->post->post_status ) ? $obj->post->post_status : null;
			} elseif ( 'short_description' === $prop ) {
				$value = isset( $obj->post->post_excerpt ) ? $obj->post->post_excerpt : null;
			} else {
				$value = $obj->$prop;
			}

			return $value;
		}
	}

	/**
	 * Back-compat wrapper for setting CRUD object props directly.
	 *
	 * @since  2.5.0 
	 *
	 * @param  WC_Product  $product
	 * @param  string      $prop
	 * @param  mixed       $value
	 * @return void
	 */
	public static function set_prop( $obj, $prop, $value ) {
		if ( self::is_wc_version_gte( '3.0.0' ) ) {
			$set_fn = 'set_' . $prop;
			if ( is_callable( array( $obj, $set_fn ) ) ) {
				$obj->$set_fn( $value );
			}
		} else {
			$obj->$prop = $value;
		}
	}

	/**
	 * Get the "From" text
	 *
	 * @param int $product
	 * @return  string
	 * @since 2.5.0
	 */
	public static function get_price_html_from_text( $product ) {
		return self::is_wc_version_gte( '3.0.0' ) ? wc_get_price_html_from_text() : $product->get_price_html_from_text();
	}

}
