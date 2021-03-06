<?php

/**
 * WC_Name_Your_Price_Helpers class.
 */
class WC_Name_Your_Price_Helpers {

	/**
	 * Supported product types.
	 * The nyp product type is how the ajax add to cart functionality is disabled in old version of WC.
	 *
	 * @var array
	 */
	public static $supported_types = array( 'simple', 'subscription', 'bundle', 'composite', 'variation', 'subscription_variation', 'deposit', 'mix-and-match', 'nyp' );


	/**
	 * Supported variable product types.
	 *
	 * @var array
	 */
	public static $supported_variable_types = array( 'variable', 'variable-subscription' );


	/*
	 * Verify this is a Name Your Price product.
	 *
	 * @param 	mixed int|obj $product product/variation ID or object
	 * @return 	return boolean
	 * @access 	public
	 * @since 	1.0
	 */
	public static function is_nyp( $product ){

		// Get the product object.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		if ( ! $product ){
			return FALSE;
		}
		
		$is_nyp = $product && $product->is_type( self::$supported_types ) && $product->get_meta( '_nyp' ) == 'yes' ? true : false;

		return apply_filters ( 'woocommerce_is_nyp', $is_nyp, $product->get_id(), $product );

	}


	/*
	 * Get the suggested price.
	 *
	 * @param 	mixed $product product/variation object or product/variation ID
	 * @return 	return number or FALSE
	 * @access 	public
	 * @since 2.0
	 */
	public static function get_suggested_price( $product ) {

		// Get the product object.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		if ( ! $product ){
			return FALSE;
		}

		$suggested = $product->get_meta( '_suggested_price', true, 'edit' ); 

		// Filter the raw suggested price @since 1.2.
		return apply_filters ( 'woocommerce_raw_suggested_price', $suggested, $product->get_id() );

	}


	/*
	 * Get the minimum price.
	 *
	 * @param int|WC_Product $product Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_minimum_price( $product ){
	
		if( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		if ( ! $product ){
			return FALSE;
		}

		$minimum = $product->get_meta( '_min_price', true, 'edit' );

		// Filter the raw minimum price @since 1.2.
		return apply_filters ( 'woocommerce_raw_minimum_price', $minimum, $product->get_id() );

	}


	/*
	 * Get the minimum price for a variable product.
	 *
	 * @param int|WC_Product $product Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.3
	 */
	public static function get_minimum_variation_price( $product ){

		if( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		if ( ! $product ){
			return FALSE;
		}

		$minimum = $product->get_variation_price( 'min' );

		// Filter the raw minimum price @since 1.2.
		return apply_filters ( 'woocommerce_raw_minimum_variation_price', $minimum, $product->get_id() );

	}

	/*
	 * Check if Subscriptions plugin is installed and this is a subscription product.
	 *
	 * @param int|WC_Product $product Either a product object or product's post ID.
	 * @access 	public
	 * @return 	return boolean returns true for subscription, variable-subscription and subsctipion_variation
	 * @since 	2.0
	 */
	public static function is_subscription( $product ){

		return class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product );

	}


	/*
	 * Is the billing period variable.
	 *
	 * @param int|WC_Product $product Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function is_billing_period_variable( $product ) {

		// Get the product object.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		if ( ! $product ){
			return false;
		}

		$variable = $product->is_type( 'subscription' ) && $product->get_meta( '_variable_billing' ) == 'yes' ? true : false;

		return apply_filters ( 'woocommerce_is_billing_period_variable', $variable, $product->get_id() );
	}


	/*
	 * Get the Suggested Billing Period for subscription.
	 *
	 * @param int|WC_Product $product Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_suggested_billing_period( $product ) {

		// Get the product object.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		// Set month as the default billing period.
		if ( ! ( $period = $product->get_meta( '_suggested_billing_period' ) ) ){
		 	$period = 'month';
		}

		// Filter the raw minimum price @since 1.2.
		return apply_filters ( 'woocommerce_suggested_billing_period', $period, $product->get_id() );

	}


	/*
	 * Get the Minimum Billing Period for subscriptsion
	 *
	 * @param int|WC_Product $product Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_minimum_billing_period( $product ) {

		// Get the product object.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		// Set month as the default billing period.
		if ( ! ( $period = $product->get_meta( '_minimum_billing_period' ) ) ){
		 	$period = 'month';
		}

		// Filter the raw minimum price @since 1.2.
		return apply_filters ( 'woocommerce_minimum_billing_period', $period, $product->get_id() );

	}


	/*
	 * Determine if variable has NYP variations.
	 *
	 * @param mixed $product int|WC_Product Either a product object or product's post ID.
	 * @return 	return string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function has_nyp( $product ) {

		// Get the product object.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		if ( ! $product ){
			return FALSE;
		}

		$has_nyp = $product->is_type( self::$supported_variable_types ) && $product->get_meta( '_has_nyp', true, 'edit' ) == 'yes' ? true : false;

		return apply_filters( 'woocommerce_has_nyp_variations', $has_nyp, $product );

	}


	/*
	 * Standardize number.
	 *
	 * Switch the configured decimal and thousands separators to PHP default
	 *
	 * @return 	return string
	 * @access 	public
	 * @since 	1.2.2
	 */
	public static function standardize_number( $value ){

		$value = trim( str_replace( wc_get_price_thousand_separator(), '', stripslashes( $value ) ) );

		return wc_format_decimal( $value );

	}


	/*
	 * Annualize Subscription Price.
	 * convert price to "per year" so that prices with different billing periods can be compared
	 *
	 * @return 	woo formatted number
	 * @access 	public
	 * @since 	2.0
	 */
	public static function annualize_price( $price = false, $period = null ){

		$factors = self::annual_price_factors();

		if( isset( $factors[$period] ) ) {
			$price = $factors[$period] * self::standardize_number( $price );
		}

		return wc_format_decimal( $price );

	}


	/*
	 * Annualize Subscription Price.
	 * convert price to "per year" so that prices with different billing periods can be compared
	 *
	 * @return 	woo formatted number
	 * @access 	public
	 * @since 	2.0
	 */
	public static function annual_price_factors(){

		return array_map( 'esc_attr', apply_filters( 'woocommerce_nyp_annual_factors' ,
							array ( 'day' => 365,
										'week' => 52,
										'month' => 12,
										'year' => 1 ) ) );

	}


	/*
	 * Get the "Minimum Price: $10" minimum string.
	 *
	 * @param obj $product ( or int $product_id )
	 * @return 	$price string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_minimum_price_html( $product ) {

		// Start the price string.
		$html = '';

		// If not nyp quit early.
		if ( ! self::is_nyp( $product ) ){
			return $html;
		}

		// Get the minimum price.
		$minimum = self::get_minimum_price( $product ); 

		if( $minimum > 0 ){

			// Get the minimum: text option.
			$minimum_text = stripslashes( get_option( 'woocommerce_nyp_minimum_text', __( 'Minimum Price:', 'wc_name_your_price' ) ) );

			// Formulate a price string.
			$price_string = self::get_price_string( $product, 'minimum' );

			$html .= sprintf( '<span class="minimum-text">%s</span> <span class="amount">%s</span>', $minimum_text, $price_string );


		} 

		return apply_filters( 'woocommerce_nyp_minimum_price_html', $html, $product );

	}


	/*
	 * Get the "Suggested Price: $10" price string.
	 *
	 * @param	obj $product
	 * @return 	string
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_suggested_price_html( $product ) {

		// Start the price string.
		$html = '';

		// If not nyp quit early.
		if ( ! self::is_nyp( $product ) ){
			return $html;
		}

		// Get suggested price.
		$suggested = self::get_suggested_price( $product ); 

		if ( $suggested > 0 ) {

			// Get the suggested: text option.
			$suggested_text = stripslashes( get_option( 'woocommerce_nyp_suggested_text', __( 'Suggested Price:', 'wc_name_your_price' ) ) );

			// Formulate a price string.
			$price_string = self::get_price_string( $product );

			// Put it all together.
			$html .= sprintf( '<span class="suggested-text">%s</span> %s', $suggested_text, $price_string );

		} 

		return apply_filters( 'woocommerce_nyp_suggested_price_html', $html, $product );

	}


	/*
	 * Format a price string.
	 *
	 * @since 	2.0
	 * @param	object $product
	 * @param	string $type ( minimum or suggested )
	 * @param 	bool $show_null_as_zero in the admin you may wish to have a null string display as $0.00
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_price_string( $product, $type = 'suggested', $show_null_as_zero = false ) {

		// Start the price string.
		$html = '';

		// Need to catch the product ID from minimum price template - must've forgotten to change that.
		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		// Switch the second parameter and deprecate the old array.
		if( is_array( $type ) ){
			$defaults = array( 'price' => false, 'period' => false );
			$args = wp_parse_args( $type, $defaults );
			$price = $args['price'];
			$period = $args['period'];
			_deprecated_argument( 'WC_Name_Your_Price_Helpers::get_price_string()', '2.2', 'Instead of an array, the second argument should be a string stating whether this is the "minimum" or "suggested" price string that you are creating.' );
		}

		// Minimum or suggested price.
		switch( $type ){
			case 'minimum-variation':
				$price = self::get_minimum_variation_price( $product );
				break;
			case 'minimum':
				$price = self::get_minimum_price( $product );
				break;
			default:
				$price = self::get_suggested_price( $product );
				break;
		}

		if( $show_null_as_zero || $price != '' ){

			// Get subscription price string.
			if( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $product ) && 'woocommerce_get_price_html' != current_filter() ) {

				// If is a variable billing product we need to create our own string.
				if( self::is_billing_period_variable( $product ) ) { 

					// Minimum or suggested period.
					if( 'minimum' == $type ){
						$period = self::get_minimum_billing_period( $product );
					} else {
						$period = self::get_suggested_billing_period( $product );
					}

					$html = sprintf( _x( ' %s / %s', 'Variable subscription price, ex: $10 / day', 'wc_name_your_price' ), wc_price( $price ), self::get_subscription_period_strings( 1, $period ) );

				} else {

					$include = array( 
						'price' => wc_price( $price ),
						'subscription_length' => false,
						'sign_up_fee'         => false,
						'trial_length'        => false );
					
					$html = WC_Subscriptions_Product::get_price_string( $product, $include );

				} 

			// Non-subscription products.
			} else { 
				$html = wc_price( $price );
			}

		}

		return apply_filters( 'woocommerce_nyp_price_string', $html, $product, $price );

	}


	/**
	 * Get Price Value Attribute.
	 * 
	 * @param	mixed int|object $product
	 * @return	string
	 * @access	public
	 * @since	2.1
	 */
	public static function get_price_value_attr( $product, $prefix = false ) {

		if ( ( $posted = self::get_posted_price( $product, $prefix ) ) != '' ) {
			$price = $posted;
		} else {
			$price = self::get_initial_price( $product );
		}

		return $price;
	}


	/**
	 * Get Posted Price.
	 * 
	 * @param 	mixed int|WC_Product $product_id Either a product object or product's post ID.
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_posted_price( $product, $prefix = false ) {
		// The $product is now useless, so we can deprecate that in the future
		return isset( $_REQUEST['nyp' . $prefix] ) ? self::standardize_number( $_REQUEST['nyp' . $prefix] ) : '';
	}


	/**
	 * Get Initial Price - Suggested, then minimum, then null.
	 * 
	 * @param	mixed int|object $product
	 * @return	string
	 * @access	public
	 * @since	2.1
	 */
	public static function get_initial_price( $product ) {

		if ( ( $suggested = self::get_suggested_price( $product ) ) != '' ) {
			$price = $suggested;
		} elseif ( ( $minimum = self::get_minimum_price( $product ) ) != '' ) {
			$price =  $minimum;
		} else {
			$price = '';
		}

		return apply_filters( 'woocommerce_nyp_get_initial_price', $price, $product );
	}

	/**
	 * Get Posted Billing Period.
	 * 
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_posted_period( $product_id, $prefix = false ) {

		// Go through a few options to find the $period we should display.
		if ( isset( $_REQUEST['nyp-period' . $prefix] ) && array_key_exists( $_REQUEST['nyp-period' . $prefix], self::get_subscription_period_strings() ) ) {
			$period = $_REQUEST['nyp-period' . $prefix];
		} elseif ( $suggested_period = self::get_suggested_billing_period( $product_id ) ) {
			$period = $suggested_period;
		} elseif ( $minimum_period = self::get_minimum_billing_period( $product_id ) ) {
			$period = $minimum_period;
		} else {
			$period = 'month';
		}
		return $period;
	}


	/*
	 * Generate markup for NYP Price input.
	 * Returns a text input with formatted value.
	 * 
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_price_input( $product_id, $prefix = null ) {

		$price = self::get_price_value_attr( $product_id, $prefix );

		$return = sprintf( '<input id="nyp%s" name="nyp%s" type="text" value="%s" size="6" title="nyp" class="input-text amount nyp-input text" />', esc_attr( $prefix ), esc_attr( $prefix ), esc_attr( self::format_price( $price ) ) );

		return apply_filters( 'woocommerce_get_price_input', $return, $product_id, $prefix );

	}

	/*
	 * Format price with local decimal point.
	 * Similar to wc_price(). 
	 * 
	 * @param	string $price
	 * @return	string
	 * @access	public
	 * @since	2.1
	 */
	public static function format_price( $price ){ 

		$decimals    = wc_get_price_decimals();
		$decimal_separator     = wc_get_price_decimal_separator();
		$thousand_separator   = wc_get_price_thousand_separator();

		if( $price != "" ) {

			$price           = apply_filters( 'raw_woocommerce_price', floatval( $price ) );
			$price           = apply_filters( 'formatted_woocommerce_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

			if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $decimals > 0 ) {
				$price = wc_trim_zeros( $price );
			}
			
		}

		return $price;
	}


	/**
	 * Generate Markup for Subscription Periods.
	 * 
	 * @param	string $input
	 * @param	obj $product
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_subscription_terms( $input = '', $product ) {

		$terms = '&nbsp;';

		if ( ! is_object( $product ) ){
			$product = wc_get_product( $product );
		}

		// Parent variable subscriptions don't have a billing period, so we get a array to string notice. therefore only apply to simple subs and sub variations.
		
		if( $product->is_type( 'subscription' ) || $product->is_type( 'subscription_variation' ) ) {

			if( self::is_billing_period_variable( $product ) ) {
				// Don't display the subscription price, period or length.
				$include = array(
					'price' => '',
					'subscription_price'  => false,
					'subscription_period' => false
				);

			} else {
				$include = array( 'price' => '', 
					'subscription_price'  => false );
				// If we don't show the price we don't get the "per" backslash so add it back.
				if( WC_Subscriptions_Product::get_interval( $product ) == 1 )
					$terms .= '<span class="per">/ </span>';
			}

			$terms .= WC_Subscriptions_Product::get_price_string( $product, $include );

		} 	

		// Piece it all together - JS needs a span with this class to change terms on variation found event.
		// Use details class to mimic Subscriptions plugin, leave terms class for backcompat.
		if( 'woocommerce_get_price_input' == current_filter() )
			$terms = '<span class="subscription-details subscription-terms">' . $terms . '</span>';

		return $input . $terms;

	}


	/*
	 * Get data attributes for use in name-your-price.js
	 *
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 */
	public static function get_data_attributes( $product_id, $prefix  = null ) {

		$price = self::get_price_value_attr( $product_id, $prefix );

		$minimum = self::get_minimum_price( $product_id ); 
		
		$data_string = sprintf( 'data-price="%s"', (double) $price );

		if( self::is_subscription( $product_id ) && self::is_billing_period_variable( $product_id ) ){

				$period = self::get_posted_period( $product_id, $prefix );
				$minimum_period = self::get_minimum_billing_period( $product_id );

				$annualized_minimum = self::annualize_price( $minimum, $minimum_period );

				$data_string .= sprintf( ' data-period="%s"', ( esc_attr( $period ) ) ? esc_attr( $period ) : 'month' );
				$data_string .= sprintf( ' data-annual-minimum="%s"', $annualized_minimum > 0  ? (double) $annualized_minimum : 0 );

		} else {

			$data_string .= sprintf( ' data-min-price="%s"', ( $minimum && $minimum > 0 ) ? (double) $minimum : 0 );

		}

		return $data_string;

	}


	/*
	 * The error message template.
	 *
	 * @param 	string $id selects which message to use
	 * @return 	return string
	 * @access 	public
	 * @since 	2.1
	 */
	public static function get_error_message_template( $id = null ){

		$errors = apply_filters( 'woocommerce_nyp_error_message_templates', 
			array( 
				'invalid' => __( '&quot;%%TITLE%%&quot; could not be added to the cart: Please enter a valid, positive number.', 'wc_name_your_price' ), 
				'minimum' => __( '&quot;%%TITLE%%&quot; could not be added to the cart: Please enter at least %%MINIMUM%%.', 'wc_name_your_price' ),
				'minimum_js' => __( 'Please enter at least %%MINIMUM%%', 'wc_name_your_price' )
			) 
		);

		return isset( $errors[$id] ) ? $errors[ $id ] : '';

	}


	/*
	 * Get error message.
	 *
	 * @param 	string $id - the error template to use
	 * @param 	array $tags - array of tags and their respective replacement values
	 * @param 	obj $_product - the relevant product object
	 * @return 	return string
	 * @access 	public
	 * @since 	2.1
	 */
	public static function error_message( $id, $tags = array(), $_product = null ){

		$message = self::get_error_message_template( $id );

		foreach( $tags as $tag => $value ){
			$message = str_replace( $tag, $value, $message );
		}
				
		return apply_filters ( 'woocommerce_nyp_error_message', $message, $id, $tags, $_product );

	}


	/**
	 * Return an i18n'ified associative array of all possible subscription periods.
	 * Ready for Subs 2.0 but with backcompat.
	 *
	 * @since 2.2.8
	 */
	public static function get_subscription_period_strings( $number = 1, $period = '' ) {
		if( function_exists( 'wcs_get_subscription_period_strings' ) ) {
			return wcs_get_subscription_period_strings( $number, $period );
		} else {
			return WC_Subscriptions_Manager::get_subscription_period_strings( $number, $period );
		}
	}


	/*-----------------------------------------------------------------------------------*/
	/* Deprecated Functions */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Check the installed version of WooCommerce is greater than $version argument.
	 *
	 * @param   $version
	 * @return	boolean
	 * @access 	public
	 * @since   2.4.0
	 */
	public static function wc_is_version( $version = '2.6' ) {
		_deprecated_function( __METHOD__, '2.4.0', 'WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte()' );
		return WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( $version );
	}


	/**
	 * Check is the installed version of WooCommerce is 2.3 or newer.
	 * props to Brent Shepard
	 *
	 * @return	boolean
	 * @access 	public
	 * @since 2.1
	 */
	public static function is_woocommerce_2_3() {
		_deprecated_function( __METHOD__, '2.4.0', 'WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte()' );
		return WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( '2.3' );
	}


	/**
	 * Check is the installed version of WooCommerce is 2.5 or newer.
	 *
	 * @return	boolean
	 * @access 	public
	 * @since 	2.3.4
	 */
	public static function is_woocommerce_2_5() {
		_deprecated_function( __METHOD__, '2.4.0', 'WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte()' );
		return WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( '2.5' );
	}


	/*
	 *  Get the product ID or variation ID if object is a variation.
	 *
	 * @param   object $product product/variation object 
	 * @return	integer
	 * @access 	public
	 * @since 	2.0
	 */
	public static function get_id( $product ){
		_deprecated_function( __METHOD__, '2.5.0', 'WC_Name_Your_Price_Core_Compatibility::get_id()' );
		return WC_Name_Your_Price_Core_Compatibility::get_id( $product );
	}


	/*
	 * Get the price HTML.
	 *
	 * @param	object $product 
	 * @return 	string
	 * @access 	public
	 * @since 	2.0
	 * @deprecated handled directly in display class, no need for this
	 */

	public static function get_price_html( $product ) { 

		_deprecated_function( 'WC_Name_Your_Price_Helpers::get_price_html()', '2.0.4', 'WC_Name_Your_Price()->display->nyp_price_html()' );

		$html = '';

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) ){
			$html = self::get_suggested_price_html( $product );
		}

		return apply_filters( 'woocommerce_nyp_html', $html, $product );

	}

	/**
	 * Generate Markup for Subscription Period Input.
	 * 
	 * @param	string $input
	 * @param	string $product_id
	 * @param	string $prefix - needed for composites and bundles
	 * @return	string
	 * @access	public
	 * @since	2.0
	 * @deprecated 2.4.0
	 */
	public static function get_subscription_period_input( $input, $product_id, $prefix ) {

		_deprecated_function( __METHOD__, '2.4.0', 'Variable subscriptions' );

		// Create the dropdown select element.
		$period = self::get_posted_period( $product_id, $prefix );

		// The pre-selected value.
		$selected = $period ? $period : 'month';

		// Get list of available periods from Subscriptions plugin
		$periods = self::get_subscription_period_strings();

		if( $periods ) :

			$period_input = sprintf( '<span class="per">/ </span><select id="nyp-period%s" name="nyp-period%s" class="nyp-period" />', $prefix, $prefix );

			foreach ( $periods as $i => $period ) :
				$period_input .= sprintf( '<option value="%s" %s>%s</option>', $i, selected( $i, $selected, false ), $period );
			endforeach;

			$period_input .= '</select>';

			$period_input = '<span class="nyp-billing-period"> ' . $period_input . '</span>';

		endif;

    	return $input . $period_input;

	}


	/*
	 * Sync variable product prices against NYP minimum prices.
	 * @param	string $product_id
	 * @param	array $children - the ids of the variations
	 * @return	void
	 * @access	public
	 * @since	2.0
	 */
	public static function variable_product_sync( $product_id, $children ){ 

		_deprecated_function( __METHOD__, '2.4.0', 'Moved method to the WC_Name_Your_Price_Compatibility class' );
		return WC_Name_Your_Price()->compatibility->variable_product_sync( $product_id, $children );

	}


} //end class