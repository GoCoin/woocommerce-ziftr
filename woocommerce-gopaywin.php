<?php
/*
 * Plugin Name: GoPayWin for WooCommerce
 * Plugin URI: http://www.gopaywin.com/
 * Description: Bring GoPayWin platform and GoPayWin functionality to WooCommerce
 * Author: GoPayWin and contributors
 * Author URI: http://www.gopaywin.com
 * Version: 0.1.0
 * 
 * Copyright: © 2014-2015 GoPayWin, LLC
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'GOPAYWIN_API_DOMAIN' ) ) {
  define('GOPAYWIN_API_DOMAIN', 'fpa.bz');
}

if ( ! defined( 'GOPAYWIN_API_PROTOCOL' ) ) {
  define('GOPAYWIN_API_PROTOCOL', 'https');
}

//check if woocommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	if( !class_exists( 'WC_GoPayWin' ) ){
		require('vendor/autoload.php');

		class WC_GoPayWin
		{

			public $plugin_id = "woocommerce_gopaywin";

			public function __construct()
			{
				// this is called before the checkout form submit
				add_action( 'woocommerce_checkout_before_customer_details',array( $this,'woocommerce_checkout_before_customer_details' ) );

				// adding gopaywin checkout along with  regular woocommerce checkout
				add_filter( 'woocommerce_proceed_to_checkout', array( $this,'add_gopaywin_checkout_after_reqular_checkout' ) );

				// Add IPC
				add_action( 'woocommerce_api_wc_gateway_gopaywin', array( $this, 'check_ipn_response' ) );


				add_filter( 'woocommerce_payment_gateways', array( $this,'add_gopaywin' ) );

				add_action( 'wp_enqueue_scripts', array( $this,'enqueue_style' ) );
			}

			function enqueue_style() {
				wp_register_style( 'wc-gopaywin-admin', plugins_url( '/includes/assets/css/admin.css', __FILE__ ) );
				wp_enqueue_style( 'wc-gopaywin-admin' );
			}


			public function check_ipn_response() {

				if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $body = file_get_contents('php://input') ) {
					$data = file_get_contents('php://input');

					if ( $data && ($jdata = json_decode($data)) && $jdata->order && $jdata->order->id ) {
						include('includes/class-wc-gateway-gopaywin-order.php');

						$order = WC_Gateway_GoPayWin_Order::from_gopaywin_id($this->get_gateway_instance(), $jdata->order->id);
						if ( $order ) {
							echo json_encode($order);
							exit;
						}
					}
				}

				wp_die( 'GoPayWin IPN Request Failure', 'GoPayWin IPN', array( 'response' => 500 ) );
			}

			public function get_configuration() {
				$configuration = new \GoPayWin\ApiClient\Configuration();

				$gateway = $this->get_gateway_instance();

				$config = array(
					'endpoint'        => GOPAYWIN_API_PROTOCOL . '://' . ($gateway->sandbox ? 'sandbox' : 'api' ) . '.' . GOPAYWIN_API_DOMAIN . '',
					'private_key'     => $gateway->private_key,
					'publishable_key' => $gateway->publishable_key
				);

				$configuration->load_from_array($config);


				return $configuration;
			}

			/**
			 * Include Payment Gateway
			 */
			function get_gateway() {
				$c = 'WC_GoPayWin_Gateway';

				if ( !class_exists($c) ) {
					include('includes/class-wc-gateway-gopaywin.php');
				}

				return $c;
			}

			/**
			 * Gets an instance of the payment gateway
			 */
			function get_gateway_instance() {
				static $i = null;

				if ( !$i ) {
					$c = $this->get_gateway();
					$i = new $c();
				}

				return $i;
			}

			/**
			 * Add GoPayWin as a gateway
			 */
			function add_gopaywin( $methods ) {
				$methods[] = $this->get_gateway(); 
				return $methods;
			}

			/**
			 * Logging method
			 * @param  string $message
			 */
			public function log( $message ) {
				if ( $this->debug ) {
					if ( empty( $this->log ) ) {
						$this->log = new WC_Logger();
					}
					$this->log->add( 'gopaywin', $message );
				}
			}

			/**
			 * Do anything on the before the checkout form
			 **/
			public function woocommerce_checkout_before_customer_details( $product ){
				if ( $this->show_above_checkout ) {
					echo '<div class="woocommerce-info gopaywin-info">Have a GoPayWin account? Use your saved details and skip the line <a href="' . $this->redirect_url() . '">Click here to checkout with GoPayWin</a></div>';
				}
			}

			public function add_gopaywin_checkout_after_reqular_checkout(){
				$gateway = $this->get_gateway_instance();

				// Disabled completely for now since it doens't handle shipping corretcly yet
				if ( false && $gateway->get_option('enabled') === 'yes' && $gateway->get_option('show_on_cart') === 'yes' ) {
					$redirecturl = $this->redirect_url();
					$logo = plugins_url( '/includes/assets/images/button_logo.png', __FILE__ );
					echo '<a href="' . $redirecturl . '" class="checkout-button gopaywin-checkout-button button alt"><img src="'.$logo.'" /> Checkout using GoPayWin</a>';
				}
			}

			public function redirect_url() {
				return plugins_url( '/cart-redirect.php', __FILE__ );
			}

			public function redirect_cart() {
				include('includes/class-wc-gateway-gopaywin-order.php');

 				$configuration = $this->get_configuration();

				$order = WC_Gateway_GoPayWin_Order::from_cart($this->get_gateway_instance(), WC()->cart);

				wp_redirect($order->get_checkout_url());
				exit;
			}

		}

		/**
		 * instantiating Class
		 **/
		$GLOBALS['wc_gopaywin'] = new WC_GoPayWin();
		

	}//END if ( !class_exists( WC_GoPayWin ) )
}
