<?php

/**
 * WC_Name_Your_Price_Display class.
 */
class WC_Name_Your_Price_Display {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Single Product Display.
		add_action( 'wp_enqueue_scripts', array( $this, 'nyp_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 20 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_price_input' ), 9 );
		add_action( 'woocommerce_nyp_after_price_input', array( $this, 'display_minimum_price' ) );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'single_add_to_cart_text' ), 10, 2 );

		// Display NYP Prices.
		add_filter( 'woocommerce_get_price_html', array( $this, 'nyp_price_html' ), 10, 2 );
		add_filter( 'woocommerce_variable_subscription_price_html', array( $this, 'variable_subscription_nyp_price_html' ), 10, 2 );

		// Loop Display.
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'add_to_cart_url' ), 10, 2 );
		// Kill AJAX add to cart WC2.5+.
		add_filter( 'woocommerce_product_supports', array( $this, 'supports_ajax_add_to_cart' ), 10, 3 );

		// If quick-view is enabled then we need the style and scripts everywhere.
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'nyp_scripts' ) );
		add_action( 'wc_quick_view_enqueue_scripts', array( $this, 'nyp_style' ) );

		// Post class.
		add_filter( 'post_class', array( $this, 'add_post_class' ), 30, 3 );

		// Variable products.
		add_filter( 'woocommerce_variation_is_visible', array( $this, 'variation_is_visible' ), 10, 3 );
		add_filter( 'woocommerce_available_variation', array( $this, 'available_variation' ), 10, 3 );
		add_filter( 'woocommerce_get_variation_price', array( $this, 'get_variation_price' ), 10, 4 );
		add_filter( 'woocommerce_get_variation_regular_price', array( $this, 'get_variation_price' ), 10, 4 );

	}



	/*-----------------------------------------------------------------------------------*/
	/* Single Product Display Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Load a little stylesheet.
	 *
	 * @return void
	 * @since 1.0
	 */
	public function nyp_style(){

		if ( get_option( 'woocommerce_nyp_disable_css', 'no' ) == 'no' )
			wp_enqueue_style( 'woocommerce-nyp', WC_Name_Your_Price()->plugin_url() . '/assets/css/name-your-price.css', false, WC_Name_Your_Price()->version );

	}


	/**
	 * Register the price input script.
	 *
	 * @return void
	 */
	function register_scripts() {
		wp_register_script( 'accounting', WC_Name_Your_Price()->plugin_url() . '/assets/js/accounting.js', array( 'jquery' ), '0.4.2', true );

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script( 'woocommerce-nyp', WC_Name_Your_Price()->plugin_url() . '/assets/js/name-your-price'. $suffix . '.js', array( 'jquery', 'accounting' ), WC_Name_Your_Price()->version, true );
	}


	/**
	 * Load price input script.
	 *
	 * @return void
	 */
	function nyp_scripts() {

		wp_enqueue_script( 'accounting' );
		wp_enqueue_script( 'woocommerce-nyp' );

		$params = array(
			'currency_format_num_decimals'  => esc_attr( wc_get_price_decimals() ),
			'currency_format_symbol'        => get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'   => esc_attr( wc_get_price_decimal_separator() ),
			'currency_format_thousand_sep'  => esc_attr( wc_get_price_thousand_separator() ),
			'currency_format'               => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting.js
			'annual_price_factors' =>  WC_Name_Your_Price_Helpers::annual_price_factors(),
			'minimum_error' => WC_Name_Your_Price_Helpers::error_message( 'minimum_js' ),
		);

		wp_localize_script( 'woocommerce-nyp', 'woocommerce_nyp_params', $params );

	}


	/**
	 * Call the Price Input Template.
	 *
	 * @param int $product_id
	 * @param string $prefix - prefix is key to integration with Bundles
	 * @return  void
	 * @since 1.0
	 */
	public function display_price_input( $product_id = false, $prefix = false ){

		if( ! $product_id ){
			global $product;
			$product_id = WC_Name_Your_Price_Core_Compatibility::get_id( $product );
		}

		// If not NYP quit right now.
		if( ! WC_Name_Your_Price_Helpers::is_nyp( $product_id ) && ! WC_Name_Your_Price_Helpers::has_nyp( $product_id ) ){
			return;
		}

		// Load up the NYP scripts.
		$this->nyp_scripts();

		// If the product is a subscription add some items to the price input.
		if( WC_Name_Your_Price_Helpers::is_subscription( $product_id ) ){

			// Add the billing period input.
			if( WC_Name_Your_Price_Helpers::is_billing_period_variable( $product_id ) ){
				add_filter( 'woocommerce_get_price_input', array( 'WC_Name_Your_Price_Helpers', 'get_subscription_period_input' ), 10, 3 );
			}

			// Add the price terms.
			add_filter( 'woocommerce_get_price_input', array( 'WC_Name_Your_Price_Helpers', 'get_subscription_terms' ), 10, 2 );

		}

		// Get the price input template.
		wc_get_template(
			'single-product/price-input.php',
			array( 'product_id' => $product_id,
					'prefix' 	=> $prefix ),
			FALSE,
			WC_Name_Your_Price()->plugin_path() . '/templates/' );

	}

	/**
	 * Call the Minimum Price Template.
	 *
	 * @param int $product_id
	 * @return  void
	 * @since 1.0
	 */
	public function display_minimum_price( $product_id ){

		if( ! $product_id ){
			global $product;
			$product_id = WC_Name_Your_Price_Core_Compatibility::get_id( $product );
		}

		// If not NYP quit right now.
		if( ! WC_Name_Your_Price_Helpers::is_nyp( $product_id ) && ! WC_Name_Your_Price_Helpers::has_nyp( $product_id ) ){
			return;
		}

		// Get the minimum price.
		$minimum = WC_Name_Your_Price_Helpers::get_minimum_price( $product_id );

		if( $minimum > 0 || WC_Name_Your_Price_Helpers::has_nyp( $product_id )){

			// Get the minimum price template.
			wc_get_template(
				'single-product/minimum-price.php',
				array( 'product_id' => $product_id ),
				FALSE,
				WC_Name_Your_Price()->plugin_path() . '/templates/' );

		}

	}


	/*
	 * If NYP change the single item's add to cart button text.
	 * Don't include on variations as you can't be sure all the variations are NYP.
	 * Variations will be handled via JS.
	 *
	 * @param string $text
	 * @param object $product
	 * @return string
	 * @since 2.0
	 */
	public function single_add_to_cart_text( $text, $product ) {

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) ){
			$text = get_option( 'woocommerce_nyp_button_text_single', __( 'Add to Cart', 'wc_name_your_price' ) );
		} 

		return $text;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Display NYP Price HTML */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Filter the Price HTML.
	 *
	 * @param string $html
	 * @param object $product
	 * @return string
	 * @since 1.0
	 * @renamed in 2.0
	 */
	function nyp_price_html( $html, $product ){

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) ){
			$html =  apply_filters( 'woocommerce_nyp_html', WC_Name_Your_Price_Helpers::get_suggested_price_html( $product ),  $product );
		} else if( WC_Name_Your_Price_Helpers::has_nyp( $product ) ){ 	
			$min_variation_string = WC_Name_Your_Price_Helpers::get_price_string( $product, 'minimum-variation' );
			$html = $min_variation_string != '' ? WC_Name_Your_Price_Core_Compatibility::get_price_html_from_text( $product ) . $min_variation_string : '';	
			$html = apply_filters( 'woocommerce_variable_nyp_html', $html, $product );
		}

		return $html;

	}

	/**
	 * Filter the Price HTML for Variable Subscriptions.
	 *
	 * @param string $html
	 * @param object $product
	 * @return string
	 * @since 1.0
	 * @renamed in 2.0
	 */
	function variable_subscription_nyp_price_html( $html, $product ){

		if( WC_Name_Your_Price_Helpers::has_nyp( $product ) && WC_Name_Your_Price_Helpers::get_minimum_variation_price( $product ) === '' && intval( WC_Subscriptions_Product::get_sign_up_fee( $product ) ) === 0 && intval( WC_Subscriptions_Product::get_trial_length( $product ) ) === 0 ){ 	
			$html = '';
		}

		return apply_filters( 'woocommerce_variable_subscription_nyp_html', $html, $product );

	}



	/*-----------------------------------------------------------------------------------*/
	/* Loop Display Functions */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * If NYP change the loop's add to cart button text.
	 *
	 * @param string $text
	 * @return string
	 * @since 1.0
	 */
	public function add_to_cart_text( $text, $product ) {

		if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) ){
			$text = get_option( 'woocommerce_nyp_button_text', __( 'Set Price', 'wc_name_your_price' ) );
		}

		return $text;

	}

	/*
	 * If NYP change the loop's add to cart button URL.
	 * Disable ajax add to cart and redirect to product page.
	 * Supported by WC<2.5.
	 *
	 * @param string $url
	 * @return string
	 * @since 1.0
	 */
	public function add_to_cart_url( $url, $product = null ) {

		if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) ) {
			$url = get_permalink( WC_Name_Your_Price_Core_Compatibility::get_id( $product ) );
			// Disables the ajax add to cart for WC<2.5.
			if( ! WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( '2.5' ) ){
				$product->product_type = 'nyp'; 
			}
		}

		return $url;

	}


	/*
	 * Disable ajax add to cart and redirect to product page.
	 * Supported by WC2.5+
	 *
	 * @param string $url
	 * @return string
	 * @since 1.0
	 */
	public function supports_ajax_add_to_cart( $supports_ajax, $feature, $product ) {

		if ( 'ajax_add_to_cart' == $feature && WC_Name_Your_Price_Helpers::is_nyp( $product ) ) {
			$supports_ajax = false;
		}

		return $supports_ajax;

	}


	/*-----------------------------------------------------------------------------------*/
	/* Post Class */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add nyp to post class.
	 *
	 * @param  array $classes - post classes
	 * @param  string $class
	 * @param  int $post_id
	 * @return array
	 * @since 2.0
	 */
	public function add_post_class( $classes, $class = '', $post_id = '' ) {
		if ( ! $post_id || get_post_type( $post_id ) !== 'product' ){
			return $classes;
		}

		if ( WC_Name_Your_Price_Helpers::is_nyp( $post_id ) || WC_Name_Your_Price_Helpers::has_nyp( $post_id ) ){
			$classes[] = 'nyp-product';
		}

		return $classes;

	}

	/*-----------------------------------------------------------------------------------*/
	/* Variable Product Display Functions */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Make NYP variations visible.
	 *
	 * @param  boolean $visible - whether to display this variation or not
	 * @param  int $variation_id
	 * @param  int $product_id
	 * @return boolean
	 * @since 2.0
	 */
	public function variation_is_visible( $visible, $variation_id, $product_id ){

		if( WC_Name_Your_Price_Helpers::is_nyp( $variation_id ) )
			$visible = TRUE;

		return $visible;
	}

	/*
	 * Add nyp data to json encoded variation form.
	 *
	 * @param  array $data - this is the variation's json data
	 * @param  object $product
	 * @param  object $variation
	 * @return array
	 * @since 2.0
	 */
	public function available_variation( $data, $product, $variation ){

		$is_nyp = WC_Name_Your_Price_Helpers::is_nyp( $variation );

		$nyp_data = array ( 'is_nyp' => $is_nyp );

		if( $is_nyp ){
			$nyp_data['minimum_price'] = WC_Name_Your_Price_Helpers::get_minimum_price( $variation );
			$nyp_data['initial_price'] =  WC_Name_Your_Price_Helpers::get_initial_price( $variation );
			$nyp_data['posted_price'] =  WC_Name_Your_Price_Helpers::get_posted_price( $variation );
			$nyp_data['price_html'] = '<span class="price">' . WC_Name_Your_Price_Helpers::get_suggested_price_html( $variation ) . '</span>';
			$nyp_data['minimum_price_html'] = WC_Name_Your_Price_Helpers::get_minimum_price_html( $variation );
			$nyp_data['add_to_cart_text'] = $variation->single_add_to_cart_text();
			if( $product->is_type( 'variable-subscription' ) ){
				$nyp_data['subscription_terms'] = WC_Name_Your_Price_Helpers::get_subscription_terms( '', $variation );
			}

		}

		return array_merge( $data, $nyp_data );

	}

	/**
	 * Get the NYP min price of the lowest-priced variation.
	 *
	 * @param  string $price
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string
	 * @since  2.0
	 */
	public function get_variation_price( $price, $product, $min_or_max, $display ) {

		if ( WC_Name_Your_Price_Helpers::has_nyp( $product ) && 'min' == $min_or_max ){

			$prices = $product->get_variation_prices();

			if( is_array( $prices ) && isset( $prices['price'] ) ){
				
				// Get the ID of the variation with the minimum price.
				reset( $prices['price'] );
				$min_id = key( $prices['price'] );

				// If the minimum variation is an NYP variation then get the minimum price. This lets you distinguish between 0 and null.
				if( WC_Name_Your_Price_Helpers::is_nyp( $min_id ) ){
					$price = WC_Name_Your_Price_Helpers::get_minimum_price( $min_id );
				}
			}

		}

		return $price;
	}

} //end class
