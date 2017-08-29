;(function($){

	$.extend({
		moveNYPmetaFields: function(){
			$( '.options_group.show_if_nyp' ).insertBefore( '.options_group.pricing' );
		},
		addClasstoRegularPrice: function(){
			$( '.options_group.pricing' ).addClass( 'hide_if_nyp' );
		},
		toggleRegularPriceClass: function( is_nyp ){
			if( is_nyp ){
				$( '.options_group.pricing' ).removeClass( 'show_if_simple' );
			} else {
				$( '.options_group.pricing' ).addClass( 'show_if_simple' );
			}
		},
		showHideNYPelements: function(){
			var product_type = $( 'select#product-type' ).val();
			var is_nyp = $( '#_nyp' ).prop( 'checked' );

			$.toggleRegularPriceClass( is_nyp );

			switch ( product_type ) {
				case 'subscription' :
						$.showHideNYPprices( is_nyp, true );
						$.enableDisableSubscriptionPrice( is_nyp );
						var is_variable_billing = $( '#_variable_billing' ).prop( 'checked' );
						$.showHideNYPvariablePeriods( is_variable_billing );
						$.enableDisableSubscriptionPeriod( is_variable_billing );
					break;
				case 'simple':
				case 'bundle':
				case 'bto':
				case 'composite':
				case 'deposit':
				case 'mix-and-match':
					$.showHideNYPprices( is_nyp, true );
					$.showHideNYPvariablePeriods( false )
					break;
				case 'variable':
					$.showHideNYPprices( false );
					$.moveNYPvariationFields();
					$.showHideNYPmetaforVariableProducts();
					break;
				case 'variable-subscription':
					$.showHideNYPprices( false );
					$.moveNYPvariationFields();
					$.showHideNYPmetaforVariableSubscriptions();
					break;
				default :
					$.showHideNYPprices( false );
				break;
			}
		},
		showHideNYPprices: function( show, restore ) {
			// For simple and sub types we'll want to restore the regular price inputs.
			restore = typeof restore !== 'undefined' ? restore : false;

			if ( show ) {
				$( '.show_if_nyp' ).show();
				$( '.hide_if_nyp' ).hide();
			} else {
				$( '.show_if_nyp' ).hide();
				if ( restore )
					$( '.hide_if_nyp' ).show();
			}
		},
		enableDisableSubscriptionPrice: function( enable ){
			if( enable ){
				$( '#_subscription_price' ).prop( 'disabled', true ).css( 'background', '#CCC' );
			} else {
				$( '#_subscription_price' ).prop( 'disabled', false ).css( 'background', '#FFF' );
			}
		},
		showHideNYPvariablePeriods: function( show ) {
			$variable_periods = $( '._suggested_billing_period_field, ._minimum_billing_period_field' );
			if( show ){
				$variable_periods.show();
			} else {
				$variable_periods.hide();
			}
		},
		enableDisableSubscriptionPeriod: function( disable ){
			$subscription_period = $( '#_subscription_period_interval, #_subscription_period' );
			if( disable ){
				$subscription_period.prop( 'disabled', true ).css( 'background','#CCC' );
			} else {
				$subscription_period.prop( 'disabled', false ).css( 'background', '#FFF' );
			}
		},
		addClasstoVariablePrice: function(){
			$( '.woocommerce_variation .variable_pricing' ).addClass( 'hide_if_variable_nyp' );
		},
		moveNYPvariationFields: function(){
			$( '#variable_product_options .variable_nyp_pricing' ).not( '.nyp_moved' ).each(function(){
				$(this).insertAfter($(this).siblings( '.variable_pricing' )).addClass( 'nyp_moved' );
			});
		},
		showHideNYPvariableMeta: function(){
			if ( $( '#product-type' ).val() == 'variable-subscription' ) {
				$.showHideNYPmetaforVariableSubscriptions();
			} else {
				$.showHideNYPmetaforVariableProducts();
			}
		},
		showHideNYPmetaforVariableProducts: function(){

			$( '.variation_is_nyp' ).each( function( index ) {

				var $variable_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_pricing' );

				var $nyp_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_nyp_pricing' );

				// Hide or display on load.
				if ( $(this).prop( 'checked' ) ) {
					$nyp_pricing.show();
					$variable_pricing.hide();

				} else {
					$nyp_pricing.hide();
					$variable_pricing.removeAttr( 'style' );

				}

			});

		},
		showHideNYPmetaforVariableSubscriptions: function(){

			$( '.variation_is_nyp' ).each( function( index ) {
				var $variable_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_pricing' );
				var $variable_subscription_price = $(this).closest( '.woocommerce_variation' ).find( '.wc_input_subscription_price' );

				var $nyp_pricing = $(this).closest( '.woocommerce_variation' ).find( '.variable_nyp_pricing' );

				if ( $(this).prop( 'checked' ) ) {
					$nyp_pricing.show();
					$variable_subscription_price.prop( 'disabled', true ).css( 'background','#CCC' );
					$variable_pricing.children().not( '.show_if_variable-subscription' ).hide();
				} else {
					$nyp_pricing.hide();
					$variable_subscription_price.prop( 'disabled', false ).css( 'background', '#FFF' );
					$variable_pricing.children().not( '.hide_if_variable-subscription' ).show();
				}

			});

		}, 
		getNYPVariationBulkEditValue: function( variation_action ){
			var value;

			switch( variation_action ) {
				case 'variation_suggested_price':
				case 'variation_minimum_price':
					value = window.prompt( woocommerce_nyp_metabox.enter_value );
					value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );
					break;
				case 'variation_suggested_price_increase':
				case 'variation_min_price_increase':
					value = window.prompt( woocommerce_nyp_metabox.price_adjust );
					break;
				case 'variation_suggested_price_increase':
				case 'variation_min_price_increase':
					value = window.prompt( woocommerce_nyp_metabox.price_adjust );
					break;
			}
			return value;
		}

	} ); //end extend


	// Magically move the simple inputs into the sample location as the normal pricing section.
	if( $( '.options_group.pricing' ).length > 0) {
		$.moveNYPmetaFields();
		$.addClasstoRegularPrice();
		$.showHideNYPelements();
	}

	// Adjust fields when the product type is changed.
	$( 'body' ).on( 'woocommerce-product-type-change',function(){
		$.showHideNYPelements();
	});

	// Adjust the fields when NYP status is changed.
	$( 'input#_nyp' ).on( 'change', function(){
		$.showHideNYPelements();
	});

	// Adjust the fields when variable billing period status is changed.
	$( '#_variable_billing' ).on( 'change', function(){
		$.showHideNYPvariablePeriods( this.checked );
		$.enableDisableSubscriptionPeriod( this.checked );
	});

	// WC 2.4 compat: handle variable products on load.
	$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(){
		$.addClasstoVariablePrice();
		$.moveNYPvariationFields();
		$.showHideNYPvariableMeta();	
	} );
	
	// When a variation is added.
	$( '#variable_product_options' ).on( 'woocommerce_variations_added',function(){
		$.addClasstoVariablePrice();
		$.moveNYPvariationFields();
		$.showHideNYPvariableMeta();
	});

	// Hide/display variable nyp prices on single nyp checkbox change.
	$( '#variable_product_options' ).on( 'change', '.variation_is_nyp', function(event){
		$.showHideNYPvariableMeta();
	});

	// Hide/display variable nyp prices on bulk nyp checkbox change.
	$( 'select.variation_actions' ).on( 'woocommerce_variable_bulk_nyp_toggle', function(event){
		$.showHideNYPvariableMeta();
	});

	/*
	* Bulk Edit callbacks
	*/

	// WC 2.4+ variation bulk edit handling.
	$( 'select.variation_actions' ).on( 'variation_suggested_price_ajax_data variation_suggested_price_increase_ajax_data variation_suggested_price_decrease_ajax_data variation_min_price_ajax_data variation_min_price_increase_ajax_data variation_min_price_decrease_ajax_data', function(event, data) {
		
		variation_action = event.type.replace(/_ajax_data/g,'');

		switch( variation_action ) {
			case 'variation_suggested_price':
			case 'variation_min_price':
				value = window.prompt( woocommerce_nyp_metabox.enter_value );
				// unformat
				value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );
				break;
			case 'variation_suggested_price_increase':
			case 'variation_suggested_price_decrease':
			case 'variation_min_price_increase':
			case 'variation_min_price_decrease':
				value = window.prompt( woocommerce_nyp_metabox.price_adjust );

				// Is it a percentage change?
				data.percentage = value.indexOf("%") >= 0 ? 'yes' : 'no';

				// Unformat.
				value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );

		}

		if ( value != null ) {
			data.value = value;
		}
		return data;
	});

})(jQuery); //end