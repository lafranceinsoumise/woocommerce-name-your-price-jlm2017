<?php
/**
 * Name Your Price Admin Legacy Class
 *
 * Adds a name your price setting tab and saves name your price meta data.
 *
 * @package		WooCommerce Name Your Price
 * @subpackage	WC_Name_Your_Price_Admin
 * @category	Class
 * @author		Kathy Darling
 * @since		1.0
 */
class WC_Name_Your_Price_Admin {

	static $simple_supported_types = array( 'simple', 'subscription', 'bundle', 'composite', 'deposit', 'mix-and-match' );

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {

		// Product Meta boxes
		add_filter( 'product_type_options', array( __CLASS__, 'product_type_options' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_to_metabox' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_meta' ), 20, 2 );

		// Variable Product
		add_action( 'woocommerce_variation_options', array( __CLASS__, 'product_variations_options' ), 10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'add_to_variations_metabox'), 10, 3 );

		// save NYP variations
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_product_variation' ), 30, 2 );

		// Variable Bulk Edit
		add_action( 'woocommerce_variable_product_bulk_edit_actions', array( __CLASS__, 'bulk_edit_actions' ) );

		// Handle bulk edits to data in WC 2.4+
		add_action( 'woocommerce_bulk_edit_variations', array( __CLASS__, 'bulk_edit_variations' ), 10, 4 );

		// Admin Scripts
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'meta_box_script'), 20 );

		// Add Help Tab
		add_action( 'admin_print_styles', array( __CLASS__, 'add_help_tab' ), 20 );

		// Edit Products screen
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'admin_price_html' ), 20, 2 );
		
		// Product Filters
		add_filter( 'woocommerce_product_filters', array( __CLASS__, 'product_filters' ) );
		add_filter( 'parse_query', array( __CLASS__, 'product_filters_query' ) );

		// Quick Edit
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'column_display'), 10, 2 );
		add_action( 'woocommerce_product_quick_edit_end',  array( __CLASS__, 'quick_edit') );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'quick_edit_scripts'), 20 );
		add_action( 'woocommerce_product_quick_edit_save', array( __CLASS__, 'quick_edit_save') );

		// Admin Settings via settings API
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );

	}


    /*-----------------------------------------------------------------------------------*/
	/* Write Panel / metabox */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Add checkbox to product data metabox title
	 *
	 * @param array $options
	 * @return array
	 * @since 1.0
	 */
	public static function product_type_options( $options ){

	  $options['nyp'] = array(
	      'id' => '_nyp',
	      'wrapper_class' => 'show_if_simple',
	      'label' => __( 'Name Your Price', 'wc_name_your_price'),
	      'description' => __( 'Customers are allowed to determine their own price.', 'wc_name_your_price'),
	      'default' => 'no'
	    );

	  return $options;

	}

	/*
	 * Add text inputs to product metabox
	 *
	 * @return print HTML
	 * @since 1.0
	 */
	public static function add_to_metabox(){
		global $post;

		// if variable billing is enabled, continue to show options. otherwise, deprecate
		$show_billing_period_options = get_post_meta( $post->ID , '_variable_billing', true ) == 'yes' ? true : false;
		
		echo '<div class="options_group show_if_nyp">';

			if( class_exists( 'WC_Subscriptions' ) && $show_billing_period_options ) {

				// make billing period variable
				woocommerce_wp_checkbox( array(
						'id' => '_variable_billing',
						'wrapper_class' => 'show_if_subscription',
						'label' => __( 'Variable Billing Period', 'wc_name_your_price' ),
						'description' => __( 'Allow the customer to set the billing period.', 'wc_name_your_price' ) ) );
			}

			// Suggested Price
			woocommerce_wp_text_input( array(
				'id' => '_suggested_price',
				'class' => 'wc_input_price short',
				'label' => __( 'Suggested Price', 'wc_name_your_price') . ' ('.get_woocommerce_currency_symbol().')' ,
				'desc_tip' => 'true',
				'description' => __( 'Price to pre-fill for customers.  Leave blank to not suggest a price.', 'wc_name_your_price' ),
				'data_type' => 'price'
			) );

			if( class_exists( 'WC_Subscriptions' ) && $show_billing_period_options ) {

				// Suggested Billing Period
				woocommerce_wp_select( array(
					'id'          => '_suggested_billing_period',
					'label'       => __( 'per', 'wc_name_your_price' ),
					'options'     => WC_Name_Your_Price_Helpers::get_subscription_period_strings()
					)
				);
			}

			// Minimum Price
			woocommerce_wp_text_input( array(
				'id' => '_min_price',
				'class' => 'wc_input_price short',
				'label' => __( 'Minimum Price', 'wc_name_your_price') . ' ('.get_woocommerce_currency_symbol().')',
				'desc_tip' => 'true',
				'description' =>  __( 'Lowest acceptable price for product. Leave blank to not enforce a minimum. Must be less than or equal to the set suggested price.', 'wc_name_your_price' ),
				'data_type' => 'price'
			) );

			if( class_exists( 'WC_Subscriptions' ) && $show_billing_period_options ) {
				// Minimum Billing Period
				woocommerce_wp_select( array(
					'id'          => '_minimum_billing_period',
					'label'       => __( 'per', 'wc_name_your_price' ),
					'options'     => WC_Name_Your_Price_Helpers::get_subscription_period_strings()
					)
				);
			}

			do_action( 'woocommerce_name_your_price_options_pricing' );

		echo '</div>';

	  }


	/*
	 * Save extra meta info
	 *
	 * @param int $post_id
	 * @param object $post
	 * @return void
	 * @since 1.0 (renamed in 2.0)
	 */
	public static function save_product_meta( $post_id, $post ) {

	   	$product_type 	= empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
	   	$suggested = '';

	   	if ( isset( $_POST['_nyp'] ) && in_array( $product_type, self::$simple_supported_types) ) {
			update_post_meta( $post_id, '_nyp', 'yes' );
			// removing the sale price removes NYP items from Sale shortcodes
			update_post_meta( $post_id, '_sale_price', '' );
			delete_post_meta( $post_id, '_has_nyp' );
		} else {
			update_post_meta( $post_id, '_nyp', 'no' );
		}

		if ( isset( $_POST['_suggested_price'] ) ) {
			$suggested = ( trim( $_POST['_suggested_price'] ) === '' ) ? '' : wc_format_decimal( $_POST['_suggested_price'] );
			update_post_meta( $post_id, '_suggested_price', $suggested );
		}

		if ( isset( $_POST['_min_price'] ) ) {
			$minimum = ( trim( $_POST['_min_price'] ) === '' ) ? '' : wc_format_decimal( $_POST['_min_price'] );
			update_post_meta( $post_id, '_min_price', $minimum );
		}

		// Variable Billing Periods

		// save whether subscription is variable billing or not (only for regular subscriptions)
		if ( isset( $_POST['_variable_billing'] ) && 'subscription' == $product_type ) {
			update_post_meta( $post_id, '_variable_billing', 'yes' );
		} else {
			update_post_meta( $post_id, '_variable_billing', 'no' );
		}

		// suggested period - don't save if no suggested price
		if ( class_exists( 'WC_Subscriptions_Manager' ) && $suggested && isset( $_POST['_suggested_billing_period'] ) && in_array( $_POST['_suggested_billing_period'], WC_Name_Your_Price_Helpers::get_subscription_period_strings() ) ){

			$suggested_period = wc_clean( $_POST['_suggested_billing_period'] );

			update_post_meta( $post_id, '_suggested_billing_period', $suggested_period );
		}

		// minimum period - don't save if no minimum price
		if ( class_exists( 'WC_Subscriptions_Manager' ) && isset( $_POST['_min_price'] ) && isset( $_POST['_minimum_billing_period'] ) && in_array( $_POST['_minimum_billing_period'], WC_Name_Your_Price_Helpers::get_subscription_period_strings() ) ){

			$minimum_period = wc_clean( $_POST['_minimum_billing_period'] );

			update_post_meta( $post_id, '_minimum_billing_period', $minimum_period );
		}

	}


	/*
	 * Add NYP checkbox to each variation
	 *
	 * @param string $loop
	 * @param array $variation_data
	 * return print HTML
	 * @since 2.0
	 */
	public static function product_variations_options( $loop, $variation_data, $variation ){ 

		$variation_is_nyp = get_post_meta( $variation->ID, '_nyp', true ); ?>

		<label><input type="checkbox" class="checkbox variation_is_nyp" name="variation_is_nyp[<?php echo $loop; ?>]" <?php checked( $variation_is_nyp, 'yes' ); ?> /> <?php _e( 'Name Your Price', 'wc_name_your_price'); ?> <a class="tips" data-tip="<?php _e( 'Customers are allowed to determine their own price.', 'wc_name_your_price'); ?>" href="#">[?]</a></label>

		<?php

	}

	/*
	 * Add NYP price inputs to each variation
	 *
	 * @param string $loop
	 * @param array $variation_data
	 * @return print HTML
	 * @since 2.0
	 */
	public static function add_to_variations_metabox( $loop, $variation_data, $variation ){

		$suggested = get_post_meta( $variation->ID, '_suggested_price', true );
		$min = get_post_meta( $variation->ID, '_min_price', true );

		if( WC_Name_Your_Price_Helpers::wc_is_version( '2.3' ) ){ ?>

			<div class="variable_nyp_pricing">
				<p class="form-row form-row-first">
					<label><?php echo __( 'Suggested Price:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
					<input type="text" size="5" class="wc_price_input" name="variation_suggested_price[<?php echo $loop; ?>]" value="<?php echo esc_attr( $suggested ); ?>" />
				</p>
				<p class="form-row form-row-last">
					<label><?php echo __( 'Minimum Price:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
					<input type="text" size="5" class="wc_price_input" name="variation_min_price[<?php echo $loop; ?>]" value="<?php echo esc_attr( $min ); ?>" />
				</p>
			</div>

		<?php 

		} else { ?>

			<tr class="variable_nyp_pricing">
				<td>
					<label><?php echo __( 'Suggested Price:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
					<input type="text" size="5" class="wc_price_input" name="variation_suggested_price[<?php echo $loop; ?>]" value="<?php echo esc_attr( $suggested ); ?>" />
				</td>
				<td>
					<label><?php echo __( 'Minimum Price:', 'wc_name_your_price' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
					<input type="text" size="5" class="wc_price_input" name="variation_min_price[<?php echo $loop; ?>]" value="<?php echo esc_attr( $min ); ?>" />
				</td>
			</tr>

		<?php

		}

	}

	/*
	 * Save extra meta info for variable products
	 *
	 * @param int $variation_id
	 * @param int $i
	 * return void
	 * @since 2.0
	 */
	public static function save_product_variation( $variation_id, $i ){

		// set NYP status
		$variation_is_nyp = isset( $_POST['variation_is_nyp'][$i] ) ? 'yes' : 'no';
		update_post_meta( $variation_id, '_nyp', $variation_is_nyp );

		// save suggested price
		if ( isset( $_POST['variation_suggested_price'][$i] ) ) {
			$variation_suggested_price = ( trim( $_POST['variation_suggested_price'][$i]  ) === '' ) ? '' : wc_format_decimal( $_POST['variation_suggested_price'][$i] );
			update_post_meta( $variation_id, '_suggested_price', $variation_suggested_price );
		}

		// save minimum price
		if ( isset( $_POST['variation_min_price'][$i] ) ) {
			$variation_min_price = ( trim( $_POST['variation_min_price'][$i]  ) === '' ) ? '' : wc_format_decimal( $_POST['variation_min_price'][$i] );
			update_post_meta( $variation_id, '_min_price', $variation_min_price );

			// if NYP, set prices to minimum
			if( $variation_is_nyp == 'yes' ){
				$new_price = $variation_min_price === '' ? 0 : wc_format_decimal( $variation_min_price );
				update_post_meta( $variation_id, '_price', $new_price );
				update_post_meta( $variation_id, '_regular_price', $new_price );
				update_post_meta( $variation_id, '_sale_price', '' );

				if( isset( $_POST['product-type'] ) && 'variable-subscription' == $_POST['product-type'] ){
					update_post_meta( $variation_id, '_subscription_price', $new_price );
				}
			} 
		}

	}

	/*
	 * Save extra meta info for variable products
	 *
	 * @param int $post_id
	 * @return void
	 * @since 2.0
	 */
	public static function save_variable_product_meta( $post_id ){

		_deprecated_function( __METHOD__, '2.4.0', 'Meta for the variable product now saved during sync' );

		$product = wc_get_product( $post_id );

		return $product->sync();
	}


	/*
	 * Add options to variations bulk edit
	 *
	 * @return print HTML
	 * @since 2.0
	 */
	public static function bulk_edit_actions(){ ?>
		<optgroup label="<?php esc_attr_e( 'Name Your Price', 'wc_name_your_price' ); ?>">
			<option value="toggle_nyp"><?php _e( 'Toggle &quot;Name Your Price&quot;', 'wc_name_your_price' ); ?></option>
			<option value="variation_suggested_price"><?php _e( 'Set suggested prices', 'wc_name_your_price' ); ?></option>
			<option value="variation_suggested_price_increase"><?php _e( 'Increase suggested prices (fixed amount or %)', 'wc_name_your_price' ); ?></option>
			<option value="variation_suggested_price_decrease"><?php _e( 'Decrease suggested prices (fixed amount or %)', 'wc_name_your_price' ); ?></option>
			<option value="variation_min_price"><?php _e( 'Set minimum prices', 'wc_name_your_price' ); ?></option>
			<option value="variation_min_price_increase"><?php _e( 'Increase minimum prices (fixed amount or %)', 'wc_name_your_price' ); ?></option>
			<option value="variation_min_price_decrease"><?php _e( 'Decrease minimum prices (fixed amount or %)', 'wc_name_your_price' ); ?></option>
		</optgroup>
		
		<?php
	}



	/**
	 * Save NYP meta data when it is bulk edited from the Edit Product screen
	 *
	 * @param string $bulk_action The bulk edit action being performed
	 * @param array $data An array of data relating to the bulk edit action. $data['value'] represents the new value for the meta.
	 * @param int $variable_product_id The post ID of the parent variable product.
	 * @param array $variation_ids An array of post IDs for the variable prodcut's variations.
	 * @since 2.3.6
	 */
	public static function bulk_edit_variations( $bulk_action, $data, $variable_product_id, $variation_ids ) {

		switch ( $bulk_action ) {
			case 'toggle_nyp':
				foreach ( $variation_ids as $variation_id ) {
					$_nyp = get_post_meta( $variation_id, '_nyp', true );
					// check for definitive 'yes' as new variations will have null values for _nyp meta key
					$is_nyp = 'yes' === $_nyp ? 'no' : 'yes';
					update_post_meta( $variation_id, '_nyp', wc_clean( $is_nyp ) );
				}
			break;
			case 'variation_suggested_price':
			case 'variation_min_price':

				$meta_key = str_replace( 'variation', '', $bulk_action );
				$price = trim( $data['value'] ) === '' ? '' : wc_format_decimal( $data['value'] );

				foreach ( $variation_ids as $variation_id ) {
					update_post_meta( $variation_id, $meta_key, wc_clean( $price ) );
				}

			break;
			case 'variation_suggested_price_increase':
			case 'variation_min_price_increase':

				$meta_key = str_replace( array('variation', '_increase' ), '', $bulk_action );
				$percentage = isset( $data['percentage'] ) && $data['percentage'] == 'yes' ? true : false;

				foreach ( $variation_ids as $variation_id ) {
					$price = get_post_meta( $variation_id, $meta_key, true );
					if( $percentage ){
						$price = $price * ( 1 + $data['value'] / 100 );
					} else {
						$price = $price + $data['value'];
					}
					update_post_meta( $variation_id, $meta_key, wc_format_decimal( $price ) );
				}

			break;
			case 'variation_suggested_price_decrease':
			case 'variation_min_price_decrease':

				$meta_key = str_replace( array('variation', '_decrease' ), '', $bulk_action );
				$percentage = isset( $data['percentage'] ) && $data['percentage'] == 'yes' ? true : false;

				foreach ( $variation_ids as $variation_id ) {
					$price = get_post_meta( $variation_id, $meta_key, true );
					if( $percentage ){
						$price = $price * ( 1 - $data['value'] / 100 );
					} else {
						$price = $price - $data['value'];
					}
					update_post_meta( $variation_id, $meta_key, wc_format_decimal( $price ) );
				}

			break;

		}

	}


	/*
	 * Javascript to handle the NYP metabox options
	 *
	 * @param string $hook
	 * @return void
	 * @since 1.0
	 */
    public static function meta_box_script( $hook ){

		// check if on Edit-Post page (post.php or new-post.php).
		if( ! in_array( $hook, array( 'post-new.php', 'post.php' ) ) ){
			return;
		}

		// now check to see if the $post type is 'product'
		global $post;
		if ( ! isset( $post ) || 'product' != $post->post_type ){
			return;
		}

		// enqueue and localize
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'woocommerce_nyp_metabox',WC_Name_Your_Price()->plugin_url() . '/includes/admin/js/nyp-metabox'. $suffix . '.js', array( 'jquery' ), WC_Name_Your_Price()->version, true );

		$strings = array ( 'enter_value' => __( 'Enter a value', 'wc_name_your_price' ),
							'price_adjust' => __( 'Enter a value (fixed or %)', 'wc_name_your_price' ) );

		wp_localize_script( 'woocommerce_nyp_metabox', 'woocommerce_nyp_metabox', $strings );

	}

	/*
	 * Add help tab for product meta
	 *
	 * @return print html
	 * @since 1.0
	 */
    public static function add_help_tab(){

    	if ( ! function_exists( 'get_current_screen' ) ){
    		return;
    	}

		$screen = get_current_screen();

		// Product/Coupon/Orders
		if ( ! in_array( $screen->id, array( 'product', 'edit-product' ) ) ){
			return;
		}

		$screen->add_help_tab( array(
	    'id'	=> 'woocommerce_nyp_tab',
	    'title'	=> __('Name Your Price', 'wc_name_your_price'),
	    'content'	=>

	    	'<h4>' . __( 'Name Your Price', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'In the "Product Meta" metabox, check the Name Your Price checkbox to allow your customers to enter their own price.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'As of Name Your Price version 2.0, this ability is available for "Simple", "Subscription", "Bundled", "Variable" and "Variable Subscriptions" Products.', 'wc_name_your_price' ) . '</p>' .

	    	'<h4>' . __( 'Suggested Price', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'This is the price you\'d like to suggest to your customers.  The Name Your Price input will be prefilled with this value.  To not suggest a price at all, you may leave this field blank.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'This value must be a positive number.', 'wc_name_your_price' ) . '</p>' .

	    	'<h4>' . __( 'Minimum Price', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'This is the lowest price you are willing to accept for this product.  To not enforce a minimum (ie: to accept any price, including zero), you may leave this field blank.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'This value must be a positive number that is less than or equal to the set suggested price.', 'wc_name_your_price' ) . '</p>' .

	    	'<h4>' . __( 'Subscriptions', 'wc_name_your_price' ) . '</h4>' .

	    	'<p>' . __( 'If you have a name your price subscription product, the subscription time period fields are still needed, but the price will be disabled in lieu of the Name Your Price suggested and minimum prices.', 'wc_name_your_price' ) . '</p>' .

	    	'<p>' . __( 'As of Name Your Price version 2.0, you can now allow variable billing periods.', 'wc_name_your_price' ) . '</p>'

	    ) );

	}

    /*-----------------------------------------------------------------------------------*/
	/* Product Overview - edit columns */
	/*-----------------------------------------------------------------------------------*/


	/*
	 * Change price in edit screen to NYP
	 *
	 * @param string $price
	 * @param object $product
	 * @return string
	 * @since 1.0
	 */
	public static function admin_price_html( $price, $product ){

		if( WC_Name_Your_Price_Helpers::is_nyp( $product ) && ! isset( $product->is_filtered_price_html ) ){
			$price = $product->get_price_html_from_text() . WC_Name_Your_Price_Helpers::get_price_string( $product, 'minimum' );
		} else if( WC_Name_Your_Price_Helpers::has_nyp( $product ) && ! isset( $product->is_filtered_price_html ) ){		
			$price = $product->get_price_html_from_text() . WC_Name_Your_Price_Helpers::get_price_string( $product, 'minimum-variation' );
		}

		return $price;

	}

	/*
	 * Add NYP as option to product filters in admin 
	 *
	 * @param string $output
	 * @return string
	 * @since 2.0.0
	 */
	public static function product_filters( $output ){
		global $wp_query;

		$pos = strpos ( $output, "</select>" );

		if ( $pos ) {

			$nyp_option = "<option value='name-your-price' ";

				if ( isset( $wp_query->query['product_type'] ) )
					$nyp_option .= selected( 'name-your-price', $wp_query->query['product_type'], false );

				$nyp_option .= "> &#42; " . __( 'Name Your Price', 'wc_name_your_price' ) . "</option>";

			$output = substr_replace( $output, $nyp_option, $pos, 0);

		}

		return $output;

	}

	/**
	 * Filter the products in admin based on options
	 *
	 * @param mixed $query
	 * @since 2.0.0
	 */
	public static function product_filters_query( $query ) {
		global $typenow;

	    if ( $typenow == 'product' ) {

	    	if ( isset( $query->query_vars['product_type'] ) ) {
		    	// Subtypes
		    	if ( $query->query_vars['product_type'] == 'name-your-price' ) {
			    	$query->query_vars['product_type']  = '';
			    	$query->is_tax = false;
			    	$meta_query = array(
			    		'relation' => 'OR',
						array(
							'key' => '_nyp',
							'value' => 'yes',
							'compare' => '=',
						),
						array(
							'key' => '_has_nyp',
							'value' => 'yes',
							'compare' => '='
						)
					);
					$query->query_vars['meta_query'] = $meta_query;
			    }
		    }
		}
	}


    /*-----------------------------------------------------------------------------------*/
	/* Quick Edit */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Display the column content
	 *
	 * @param string $column_name
	 * @param int $post_id
	 * @return print HTML
	 * @since 1.0
	 */
	public static function column_display( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'price' :

				/* Custom inline data for nyp */
				$nyp = get_post_meta( $post_id, '_nyp', true );

				$suggested = WC_Name_Your_Price_Helpers::get_suggested_price( $post_id );
				$suggested = wc_format_localized_price( $suggested );

				$min = WC_Name_Your_Price_Helpers::get_minimum_price( $post_id );
				$min = wc_format_localized_price( $min );

				$is_nyp_allowed = has_term( array( 'simple' ), 'product_type', $post_id ) ? 'yes' : 'no';

				echo '
					<div class="hidden" id="nyp_inline_' . $post_id . '">
						<div class="nyp">' . $nyp . '</div>
						<div class="suggested_price">' . $suggested . '</div>
						<div class="min_price">' . $min . '</div>
						<div class="is_nyp_allowed">' . $is_nyp_allowed . '</div>
					</div>
				';

			break;


		}

	}

	/*
	 * Add quick edit fields
	 *
	 * @return print HTML
	 * @since 1.0
	 */

	public static function quick_edit() {  ?>

		<style>
			.inline-edit-row fieldset .nyp_prices span.title { line-height: 1; }
			.inline-edit-row fieldset .nyp_prices label { overflow: hidden; }
		</style>
		    <div id="nyp-fields" class="inline-edit-col-left">

		    	<br class="clear" />

			   	<h4><?php _e( 'Name Your Price', 'wc_name_your_price' ); ?>  <input type="checkbox" name="_nyp" class="nyp" value="1" /></h4>

			    <div class="nyp_prices">
			    	<label>
			            <span class="title"><?php _e( 'Suggested Price', 'wc_name_your_price' ); ?></span>
			            <span class="input-text-wrap">
			            	<input type="text" name="_suggested_price" class="text suggested_price" placeholder="<?php _e( 'Suggested Price', 'wc_name_your_price' ); ?>" value="">
			            </span>
			        </label>
			        <label>
			            <span class="title"><?php _e( 'Minimum Price', 'wc_name_your_price' ); ?></span>
			            <span class="input-text-wrap">
			            	<input type="text" name="_min_price" class="text min_price" placeholder="<?php _e( 'Minimum price', 'wc_name_your_price' ); ?>" value="">
			        	</span>
			        </label>
			    </div>

			</div>

	  <?php
	}

	/*
	 * Load the scripts for dealing with the quick edit
	 *
	 * @param string $hook
	 * @return void
	 * @since 1.0
	 */
	public static function quick_edit_scripts( $hook ) {
		global $post_type;

		if ( $hook == 'edit.php' && $post_type == 'product' ){
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
 			wp_enqueue_script( 'nyp-quick-edit', WC_Name_Your_Price()->plugin_url() . '/includes/admin/js/nyp-quick-edit'. $suffix .'.js', array( 'jquery' ), WC_Name_Your_Price()->version, true );
		}

	}

	/*
	 * Save quick edit changes
	 *
	 * @param object $product
	 * @return void
	 * @since 1.0
	 * @since 2.0 modified to only work in WC 2.1
	 *
	 */
	public static function quick_edit_save( $product ) {
		global $wpdb;

		if( isset( $product->id ) ){
			$product_id = $product->id;
		} else {
			return $product;
		}

		// Save fields

		if( isset( $_REQUEST['_nyp'] ) ) {
			update_post_meta( $product_id, '_nyp', 'yes' );
		} else {
			update_post_meta( $product_id, '_nyp', 'no' );
		}

		if ( isset( $_REQUEST['_suggested_price'] ) ) {
			$suggested = ( trim( $_REQUEST['_suggested_price'] ) === '' ) ? '' : wc_format_decimal( $_REQUEST['_suggested_price'] );
			update_post_meta( $product_id, '_suggested_price', $suggested );
		}

		if ( isset( $_REQUEST['_min_price'] ) ) {
			$min = ( trim( $_REQUEST['_min_price'] ) === '' ) ? '' : wc_format_decimal( $_REQUEST['_min_price'] );
			update_post_meta( $product_id, '_min_price', $min );
		}

	}


	/*-----------------------------------------------------------------------------------*/
	/* Admin Settings */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * Include the settings page class
	 * compatible with WooCommerce 2.1
	 *
	 * @param array $settings ( the included settings pages )
	 * @return array
	 * @since 2.0
	 */
	public static function add_settings_page( $settings ) {

		$settings[] = include( WC_Name_Your_Price()->plugin_path() . '/includes/admin/class-wc-settings-nyp.php' );

		return $settings;
	}

}
WC_Name_Your_Price_Admin::init();