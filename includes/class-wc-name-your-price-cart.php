<?php

/**
 * WC_Name_Your_Price_Cart class.
 */
class WC_Name_Your_Price_Cart {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Functions for cart actions - ensure they have a priority before addons (10)
		add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), 5, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 5, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 11, 2 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 11, 1 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_cart_item' ), 5, 5 );

	}

	/*-----------------------------------------------------------------------------------*/
	/* Cart Filters */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * override woo's is_purchasable in cases of nyp products
	 * @since 1.0
	 */
	public function is_purchasable( $purchasable , $product ) {
		if( ( $product->is_type( WC_Name_Your_Price_Helpers::$supported_types ) && WC_Name_Your_Price_Helpers::is_nyp( $product ) ) || ( $product->is_type( WC_Name_Your_Price_Helpers::$supported_variable_types ) && WC_Name_Your_Price_Helpers::has_nyp( $product ) ) ) {
			$purchasable = true;
		}
		return $purchasable;
	}

	/*
	 * add cart session data
	 * @since 1.0
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {

		if ( $variation_id ){
			$product_id = $variation_id;
		}

		$posted_nyp_field = 'nyp' . apply_filters( 'nyp_field_prefix', '', $product_id );

		// no need to check is_nyp b/c this has already been validated by validate_add_cart_item()
		if( isset( $_REQUEST[ $posted_nyp_field ] ) ) {
			$cart_item_data['nyp'] = ( double ) WC_Name_Your_Price_Helpers::standardize_number( $_REQUEST[ $posted_nyp_field ] );
		}

		// add the subscription billing period (the input name is nyp-period)
		$posted_nyp_period = 'nyp-period' . apply_filters( 'nyp_field_prefix', '', $product_id );

		if ( WC_Name_Your_Price_Helpers::is_subscription( $product_id ) && isset( $_REQUEST[ $posted_nyp_period ] ) && in_array( $_REQUEST[ $posted_nyp_period ], WC_Name_Your_Price_Helpers::get_subscription_period_strings() ) ) {
			$cart_item_data['nyp_period'] = $_REQUEST[ $posted_nyp_period ];
		}

		return $cart_item_data;
	}

	/*
	 * adjust the product based on cart session data
	 * @since 1.0
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {

		//no need to check is_nyp b/c this has already been validated by validate_add_cart_item()
		if ( isset( $values['nyp'] ) ) {
			$cart_item['nyp'] = $values['nyp'];

			// add the subscription billing period
			if ( WC_Name_Your_Price_Helpers::is_subscription( $values['product_id'] ) && isset( $values['nyp_period'] ) && in_array( $values['nyp_period'], WC_Name_Your_Price_Helpers::get_subscription_period_strings() ) )
				$cart_item['nyp_period'] = $values['nyp_period'];

			$cart_item = $this->add_cart_item( $cart_item );
		}

		return $cart_item;
	}

	/*
	 * change the price of the item in the cart
	 * @since 1.0
	 */
	public function add_cart_item( $cart_item ) {

		$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

		// Adjust price in cart if nyp is set
		if ( WC_Name_Your_Price_Helpers::is_nyp( $product_id ) ) {

			if ( isset( $cart_item['nyp'] ) ) {
				$cart_item['data']->price = $cart_item['nyp'];
				$cart_item['data']->subscription_price = $cart_item['nyp'];
				$cart_item['data']->sale_price =  $cart_item['nyp'];
				$cart_item['data']->regular_price = $cart_item['nyp'];
			}

			if ( isset( $cart_item['nyp_period'] ) ) {
				$cart_item['data']->subscription_period = $cart_item['nyp_period'];
				// variable billing period is always a "per" interval
				$cart_item['data']->subscription_period_interval =  '1';
			}

		}
		return $cart_item;
	}

	/*
	 * check this is a NYP product before adding to cart
	 * @since 1.0
	 */
	public function validate_add_cart_item( $passed, $product_id, $quantity, $variation_id = '', $variations= '' ) {

		if( $variation_id ){
			$product_id = $variation_id;
		}

		// skip if not a nyp product - send original status back
		if ( ! WC_Name_Your_Price_Helpers::is_nyp( $product_id ) ){
			return $passed;
		}

		$prefix = apply_filters( 'nyp_field_prefix', '', $product_id );

		// get the posted price (can be null string)
		$input = WC_Name_Your_Price_Helpers::get_posted_price( $product_id, $prefix );

		// get minimum price
		$minimum = WC_Name_Your_Price_Helpers::get_minimum_price( $product_id );

		// get maximum price
		$maximum = WC_Name_Your_Price_Helpers::get_maximum_price( $product_id );

		// null error message
		$error_message = '';

		// the product title
		$the_product = wc_nyp_get_product( $product_id );
		$product_title = $the_product->get_title();

		// check that it is a positive numeric value
		if ( ! is_numeric( $input ) || is_infinite( $input ) || floatval( $input ) < 0 ) {
			$passed = false;
			$error_message = WC_Name_Your_Price_Helpers::error_message( 'invalid', array( '%%TITLE%%' => $product_title ) );
		// check that it is greater than minimum price for variable billing subscriptions
		} elseif ( $minimum && WC_Name_Your_Price_Helpers::is_subscription( $product_id ) && WC_Name_Your_Price_Helpers::is_billing_period_variable( $product_id ) ) {

			// get the posted billing period, defaults to 'month'
			$period = WC_Name_Your_Price_Helpers::get_posted_period( $product_id, $prefix );

			// minimum billing period
			$minimum_period = WC_Name_Your_Price_Helpers::get_minimum_billing_period( $product_id );

			// annual minimum
			$minimum_annual = WC_Name_Your_Price_Helpers::annualize_price( $minimum, $minimum_period );

			// annual input
			$input_annual = WC_Name_Your_Price_Helpers::annualize_price( $input, $period );

			// by standardizing the prices over the course of a year we can safely compare them
			if ( $input_annual < $minimum_annual ) {
				$passed = false;

				$factors = WC_Name_Your_Price_Helpers::annual_price_factors();

				// If set period is in the $factors array we can calc the min price shown in the error according to entered period
				if ( isset( $factors[$period] ) ){
					$error_price = $minimum_annual / $factors[$period];
					$error_period = $period;
				// otherwise, just show the saved minimum price and period
				} else {
					$error_price = $minimum;
					$error_period = $minimum_period;
				}

				// the minimum is a combo of price and period
				$minimum_error = wc_price( $error_price ) . ' / ' . $error_period;
				$error_message = WC_Name_Your_Price_Helpers::error_message( 'minimum', array( '%%TITLE%%' => $product_title, '%%MINIMUM%%' => $minimum_error ), $the_product );

			}

		// check that it is greater than minimum price
		} elseif ( $minimum && floatval( WC_Name_Your_Price_Helpers::standardize_number( $input ) ) < floatval( $minimum ) ) {
			$passed = false;
			$minimum_error = wc_price( $minimum );
			$error_message = WC_Name_Your_Price_Helpers::error_message( 'minimum', array( '%%TITLE%%' => $product_title, '%%MINIMUM%%' => $minimum_error ), $the_product );
		} elseif ( $maximum && floatval( WC_Name_Your_Price_Helpers::standardize_number( $input ) ) > floatval( $maximum ) ) {
			$passed = false;
			$maximum_error = wc_price( $maximum );
			$error_message = WC_Name_Your_Price_Helpers::error_message( 'maximum', array( '%%TITLE%%' => $product_title, '%%MAXIMUM%%' => $maximum_error ), $the_product );
		}

		// show the error message
		if( $error_message ){
			wc_add_notice( $error_message, 'error' );
		}
		return $passed;
	}

} //end class
