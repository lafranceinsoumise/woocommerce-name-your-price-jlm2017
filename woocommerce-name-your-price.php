<?php
/*
 * Plugin Name: WooCommerce Name Your Price
 * Plugin URI: http://www.woothemes.com/products/name-your-price/
 * Description: WooCommerce Name Your Price allows customers to set their own price for products or donations.
 * Version: 2.6.0
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com
 * Woo: 18738:31b4e11696cd99a3c0572975a84f1c08
 * Requires at least: 3.8
 * Tested up to: 4.7.3
 * WC requires at least: 2.4.0    
 * WC tested up to: 3.0.0   
 *
 * Text Domain: wc_name_your_price
 * Domain Path: /languages/
 *
 * Copyright: © 2012 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

/**
 * Required functions.
 */
if ( ! function_exists( 'woothemes_queue_update' ) ){
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates.
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '31b4e11696cd99a3c0572975a84f1c08', '18738' );

// Quit right now if WooCommerce is not active
if ( ! is_woocommerce_active() ){
	return;
}


/**
 * The Main WC_Name_Your_Price class.
 **/
if ( ! class_exists( 'WC_Name_Your_Price' ) ) :

class WC_Name_Your_Price {

	/**
	 * @var WC_Name_Your_Price - the single instance of the class
	 * @since 2.0
	 */
	protected static $_instance = null;           

	/**
	 * @var plugin version
	 * @since 2.0
	 */
	public $version = '2.6.0';   

	/**
	 * @var required WooCommerce version
	 * @since 2.1
	 */
	public $required_woo = '2.4.0';

	/**
	 * Main WC_Name_Your_Price Instance.
	 *
	 * Ensures only one instance of WC_Name_Your_Price is loaded or can be loaded.
	 *
	 * @static
	 * @see WC_Name_Your_Price()
	 * @return WC_Name_Your_Price - Main instance
	 * @since 2.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc_name_your_price' ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc_name_your_price' ) );
	}
  
	/**
	 * WC_Name_Your_Price Constructor.
	 *
	 * @access public
     * @return WC_Name_Your_Price
	 * @since 1.0
	 */

	public function __construct() { 

		// Load translation files
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Include required files
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// Settings Link for Plugin page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_link' ), 10, 2 );

    }

	/*-----------------------------------------------------------------------------------*/
	/* Helper Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 * @since  2.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 * @since  2.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Required Files */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @return void
	 * @since  1.0
	 */
	public function includes(){

		// Include WC compatibility functions
		include_once( 'includes/class-wc-name-your-price-core-compatibility.php' );

		// check we're running the required version of WC
		if ( ! WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( $this->required_woo ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			return false;
		}

		// include all helper functions
		if( WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( '3.0.0' ) ) {
			include_once( 'includes/class-wc-name-your-price-helpers.php' );
		} else {
			include_once( 'includes/legacy/class-wc-name-your-price-helpers.php' );
		}

		// include admin class to handle all backend functions
		if( is_admin() ){
			$this->admin_includes();
		}

		// include the front-end functions
		if ( ! is_admin() || defined('DOING_AJAX') ) {
			include_once( 'includes/class-wc-name-your-price-display.php' );
			$this->display = new WC_Name_Your_Price_Display();

			include_once( 'includes/class-wc-name-your-price-cart.php' );
			$this->cart = new WC_Name_Your_Price_Cart();

			include_once( 'includes/class-wc-name-your-price-order.php' );
			$this->order = new WC_Name_Your_Price_Order();

		}

		include_once( 'includes/class-wc-name-your-price-compatibility.php' );
		$this->compatibility = new WC_Name_Your_Price_Compatibility();

		// Include deprecated functions.
		include_once( 'includes/wc-nyp-deprecated-functions.php' );

		do_action( 'wc_name_your_price_loaded' );

	}


	/**
	 * Displays a warning message if version check fails.
	 * @return string
	 * @since  2.1
	 */
	public function admin_notice() {
	    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Name Your Price requires at least WooCommerce %s in order to function. Please upgrade WooCommerce.', 'wc_name_your_price' ), $this->required_woo ) . '</p></div>';
	}


	/**
	 * Load the admin files.
	 * @return void
	 * @since  2.2
	 */
	public function admin_includes() {
	    if ( WC_Name_Your_Price_Core_Compatibility::is_wc_version_gte( '3.0.0' ) ) {
			include_once( 'includes/admin/class-name-your-price-admin.php' );
		} else {
			include_once( 'includes/admin/legacy/class-name-your-price-admin-legacy.php' );
		}
	}


	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wc_name_your_price/wc_name_your_price-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wc_name_your_price-LOCALE.mo
	 *      - WP_CONTENT_DIR/plugins/woocommerce-name-your-price/languages/wc_name_your_price-LOCALE.mo
	 *
	 * @return void
	 * @since  1.0
	 */
	public function load_plugin_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'wc_name_your_price' );

		load_textdomain( 'wc_name_your_price', WP_LANG_DIR . '/wc_name_your_price/wc_name_your_price-' . $locale . '.mo' );
		load_plugin_textdomain( 'wc_name_your_price', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	}

	/*-----------------------------------------------------------------------------------*/
	/* Plugins Page */
	/*-----------------------------------------------------------------------------------*/

	/*
	 * 'Settings' link on plugin page
	 *
	 * @param array $links
	 * @return array
	 * @since 1.0
	 */
	public function add_action_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=nyp' ). '" title="' . __( 'Go to the settings page', 'wc_name_your_price' ). '">'.__( 'Settings', 'wc_name_your_price' ).'</a>';
		return array_merge( (array) $settings_link, $links );

	}


	/*-----------------------------------------------------------------------------------*/
	/* Deprecated Functions */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * display_minimum_price function.
	 *
	 * @deprecated As of 2.0, function is now in display class and global replaced with instance
	 * @access public
	 */
	public function display_minimum_price() {
		_deprecated_function( 'display_minimum_price', '2.0', 'WC_Name_Your_Price()->display->display_minimum_price' );
		return $this->display->display_minimum_price();
	}

	/**
	 * display_price_input function.
	 *
	 * @deprecated As of 2.0, function is now in display class and global replaced with instance
	 * @access public
	 */
	public function display_price_input() { 
		_deprecated_function( 'display_price_input', '2.0', 'WC_Name_Your_Price()->display->display_price_input' );
		return $this->display->display_price_input();
	}

	/**
	 * nyp_style function.
	 *
	 * @deprecated As of 2.0, function is now in display class and global replaced with instance
	 * @access public
	 */
	public function nyp_style() {
		_deprecated_function( 'nyp_style', '2.0', 'WC_Name_Your_Price()->display->nyp_style' );
		return $this->display->nyp_style();
	}

} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check


/**
 * Returns the main instance of WC_Name_Your_Price to prevent the need to use globals.
 *
 * @since  2.0
 * @return WC_Name_Your_Price
 */
function WC_Name_Your_Price() {
  return WC_Name_Your_Price::instance();
}

// Launch the whole plugin w/ a little backcompat.
$GLOBALS['wc_name_your_price'] = WC_Name_Your_Price();
