jQuery( document ).ready( function($) {

	/*
	 * woocommerce_nyp_update function
	 * wraps all important nyp callbacks for plugins that maybe don't have elements available on load
	 * ie: quickview, bundles, etc
	 */

	$.fn.woocommerce_nyp_update = function() {

		/*
		 * Name Your Price Handler for individual items
		 */

		$( this ).on( 'woocommerce-nyp-update', function() {

			// some important objects
			var $cart 			= $( this );
			var $nyp 			= $cart.find( '.nyp' );
			var $nyp_input 		= $cart.find( '.nyp-input' );
			var $submit 		= $cart.find(':submit');

			// the current price
			var form_price 	= $nyp_input.val();

			// add a div to hold the error message
			var $error = $cart.find( '.woocommerce-nyp-message' );

			if ( ! $error.length ){
				$nyp.prepend( '<div class="woocommerce-nyp-message woocommerce-error" style="display:none"></div>' );
			}

			// the default error message
			var error_message = woocommerce_nyp_params.minimum_error;
			var error = false;
			var error_price = ''; // this will hold the formatted price for the error message

			// convert price to default decimal setting for calculations
			var form_price_num 	= woocommerce_nyp_unformat_price( form_price );

			var min_price 			= parseFloat( $nyp.data( 'min-price' ) );
			var annual_minimum	= parseFloat( $nyp.data( 'annual-minimum' ) );

			// get variable billing period data
			var $nyp_period		= $cart.find( '.nyp-period' );
			var form_period		= $nyp_period.val();

			// if has variable billing period AND a minimum then we need to annulalize min price for comparison
			if ( annual_minimum > 0 ){

				// calculate the price over the course of a year for comparison
				form_annulualized_price = form_price_num * woocommerce_nyp_params.annual_price_factors[form_period];

				// if the calculated annual price is less than the annual minimum
				if( form_annulualized_price < annual_minimum ){

					error = annual_minimum / woocommerce_nyp_params.annual_price_factors[form_period];

					// in the case of variable period we need to adjust the error message a bit
					error_price = woocommerce_nyp_format_price( error, woocommerce_nyp_params.currency_format_symbol ) + ' / ' + $nyp_period.find('option[value="' + form_period + '"]').text();


				}

			// otherwise a regular product or subscription with non-variable periods
			// compare price directly
			} else if ( form_price_num < min_price ) {

				error = min_price;
				error_price = woocommerce_nyp_format_price( error, woocommerce_nyp_params.currency_format_symbol );

			}

			// maybe auto-format the input
			if( $.trim( form_price ) != '' ){
				$nyp_input.val( woocommerce_nyp_format_price( form_price_num ) );
			}

			// if we've set an error, show message and prevent submit
			if ( error ){

				// disable submit
				$submit.prop( 'disabled', true );

				// show error
				error_message = error_message.replace( "%%MINIMUM%%", error_price );

				$error.html(error_message).slideDown();

				// focus on the input
				$nyp_input.focus();

			// otherwise allow submit and update
			} else {

				// allow submit
				$submit.prop( 'disabled', false );

				// remove error
				$error.slideUp();

				// product add ons compatibility
				$(this).find( '#product-addons-total' ).data( 'price', form_price_num );
				$cart.trigger( 'woocommerce-product-addons-update' );

				// bundles compatibility
				$nyp.data( 'price', form_price_num );
				$cart.trigger( 'woocommerce-nyp-updated-item' );
				$( 'body' ).trigger( 'woocommerce-nyp-updated' );

			}

		} ); // end woocommerce-nyp-update handler

		// nyp update on change to any nyp input
		$( this ).on( 'change', '.nyp-input, .nyp-period', function() {
			var $cart = $(this).closest( '.cart' );
			$cart.trigger( 'woocommerce-nyp-update' );
		} );

		// trigger right away
		$( this ).find( 'input.nyp-input' ).trigger( 'change' );

		/*
		 * Handle NYP Variations
		 */

		if ( $( this ).hasClass( 'variations_form' ) ) {

			// some important objects
			var $variation_form 	= $(this);
			var $add_to_cart 		= $(this).find( 'button.single_add_to_cart_button' );
			var $nyp 				= $(this).find( '.nyp' );
			var $nyp_input 			= $nyp.find( '.nyp-input' );
			var $minimum 			= $nyp.find( '.minimum-price' );
			var $subscription_terms = $nyp.find( '.subscription-details' );

			// the add to cart text
			var default_add_to_cart_text 	= $add_to_cart.html();

			// hide the nyp form by default
			$nyp.hide();
			$minimum.hide();

			// Listeners

			// when variation is found, decide if it is NYP or not
			$variation_form

			.on( 'found_variation', function( event, variation ) {

				// if NYP show the price input and tweak the data attributes
				if ( typeof variation.is_nyp != undefined && variation.is_nyp == true ) {

					// switch add to cart button text if variation is NYP
					$add_to_cart.html( variation.add_to_cart_text );

					// get the posted value out of data attributes
					posted_price = variation.posted_price;

					// get the initial value out of data attributes
					initial_price = variation.initial_price;

					// get the minimum price
					minimum_price = variation.minimum_price;

					// maybe auto-format the input
					if( $.trim( posted_price ) != '' ){
						$nyp_input.val( woocommerce_nyp_format_price( posted_price ) );
					} else if( $.trim( initial_price ) != '' ){
						$nyp_input.val( woocommerce_nyp_format_price( initial_price ) );
					} else {
						$nyp_input.val( '' );
					}

					// maybe show subscription terms
					if( $subscription_terms.length && variation.subscription_terms ){
						$subscription_terms.html( variation.subscription_terms );
					}

					// maybe show minimum price html
					if( variation.minimum_price_html ){
						$minimum.html ( variation.minimum_price_html ).show();
					} else {
						$minimum.hide();
					}

					// set the NYP data attributes for JS validation on submit
					$nyp.data( 'min-price', minimum_price ).slideDown('200');

					// product add ons compatibility
					$(this).find( '#product-addons-total' ).data( 'price', minimum_price );
					$(this).trigger( 'woocommerce-product-addons-update' );

				// if not NYP, hide the price input
				} else {

					// use default add to cart button text if variation is not NYP
					$add_to_cart.html( default_add_to_cart_text );

					// hide
					$nyp.slideUp( '200' );

				}

			} )

			.on( 'reset_image', function( event ) {

				$add_to_cart.html( default_add_to_cart_text );
				$nyp.slideUp('200');

			} )

			// hide the price input when reset is clicked
			.on( 'click', '.reset_variations', function( event ) {

				$add_to_cart.html( default_add_to_cart_text );
				$nyp.slideUp( '200' );

			} );


			// need to re-trigger some things on load since Woo unbinds the found_variation event
			$( this ).find( '.variations select' ).trigger( 'change' );
		}


	} // end woocommerce_nyp_update()

	/*
	 * run when Quick view item is launched
	 */
	$( 'body' ).on( 'quick-view-displayed', function() {
		$( 'body' ).find( '.cart:not(.cart_group)' ).each( function() {
			$( this ).woocommerce_nyp_update();
		} );
	} );

	/*
	 * run when a Composite component is re-loaded
	 */
	$( 'body .component' ).on( 'wc-composite-component-loaded', function() {
		$( this ).find( '.cart:not(.cart_group)' ).each( function() {
			$( this ).woocommerce_nyp_update();
		} );
	} );

	/*
	 * run on load
	 */
	$( 'body' ).find( '.cart:not(.cart_group)' ).each( function() {
		$( this ).woocommerce_nyp_update();
	} );

	/*
	 * helper functions
	 */
	// format the price with accounting
	function woocommerce_nyp_format_price( price, currency_symbol ){

		if ( typeof currency_symbol === 'undefined' )
			currency_symbol = '';

		return accounting.formatMoney( price, {
				symbol : currency_symbol,
				decimal : woocommerce_nyp_params.currency_format_decimal_sep,
				thousand: woocommerce_nyp_params.currency_format_thousand_sep,
				precision : woocommerce_nyp_params.currency_format_num_decimals,
				format: woocommerce_nyp_params.currency_format
		}).trim();

	}

	// get absolute value of price and turn price into float decimal
	function woocommerce_nyp_unformat_price( price ){

		return Math.abs( parseFloat( accounting.unformat( price, woocommerce_nyp_params.currency_format_decimal_sep ) ) );

	}

} );
