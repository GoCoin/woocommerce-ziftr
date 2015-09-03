<?php
/*
 * Plugin Name: Ziftr for WooCommerce
 * Plugin URI: http://www.ziftr.com/
 * Description: Bring Ziftr platform and ziftrPAY functionality to WooCommerce
 * Author: Ziftr and contributors
 * Author URI: http://www.ziftr.com
 * Version: 0.1.0
 * 
 * Copyright: © 2014-2015 Ziftr, LLC
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//check if woocommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	if( !class_exists( 'WC_Ziftr' ) ){
		require('vendor/autoload.php');

		class WC_Ziftr
		{

			public $plugin_id = "woocommerce_ziftrpay";

			public function __construct()
			{
				// this is called before the checkout form submit
				add_action( 'woocommerce_checkout_before_customer_details',array( $this,'woocommerce_checkout_before_customer_details' ) );

				// adding ziftr checkout along with  regular woocommerce checkout
				add_filter( 'woocommerce_proceed_to_checkout', array( $this,'add_ziftr_checkout_after_reqular_checkout' ) );


				add_filter( 'woocommerce_payment_gateways', array( $this,'add_ziftrpay' ) );

				wp_register_style( 'wc-ziftr-admin', plugins_url( '/includes/assets/css/admin.css', __FILE__ ) );
				wp_enqueue_style( 'wc-ziftr-admin' );
			}


			public function get_configuration() {
				$configuration = new \Ziftr\ApiClient\Configuration();

				$gateway = $this->get_gateway_instance();

				$configuration->load_from_array(array(
							'host'            => ($gateway->sandbox ? 'sandbox' : 'api' ) . '.fpa.bz',
							'port'            => 443,
							'private_key'     => $gateway->private_key,
							'publishable_key' => $gateway->publishable_key
							));

				return $configuration;
			}

			/**
			 * Include Payment Gateway
			 */
			function get_gateway() {
				$c = 'WC_Ziftrpay_Gateway';

				if ( !class_exists($c) ) {
					include('includes/class-wc-gateway-ziftrpay.php');
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
			 * Add ZiftrPAY as a gateway
			 */
			function add_ziftrpay( $methods ) {
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
					$this->log->add( 'ziftrpay', $message );
				}
			}

			/**
			 * Do anything on the before the checkout form
			 **/
			public function woocommerce_checkout_before_customer_details( $product ){
				if ( $this->show_above_checkout ) {
					echo '<div class="woocommerce-info ziftrpay-info">Have a ZiftrPAY account? Use your saved details and skip the line <a href="' . $this->redirect_url() . '">Click here to checkout with ZiftrPAY</a></div>';
				}
			}

			public function add_ziftr_checkout_after_reqular_checkout(){
				if ( true || $this->get_settings()->show_on_cart ) {
					$redirecturl = $this->redirect_url();
					$logo = plugins_url( '/includes/assets/images/button_logo.png', __FILE__ );
					echo '<a href="' . $redirecturl . '" class="checkout-button ziftrpay-checkout-button button alt"><img src="'.$logo.'" /> Checkout using ZiftrPAY</a>';
				}
			}

			public function redirect_url() {
				return plugins_url( '/cart-redirect.php', __FILE__ );
			}

			public function redirect_cart() {
				include('includes/class-wc-gateway-ziftrpay-order.php');

 				$configuration = $this->get_configuration();

				$order = WC_Gateway_Ziftrpay_Order::from_cart(WC()->cart, $configuration);

				wp_redirect($order->get_checkout_url());
				exit;
			}

		}

		/**
		 * instantiating Class
		 **/
		$GLOBALS['wc_ziftr'] = new WC_Ziftr();
		

	}//END if ( !class_exists( WC_Ziftr ) )
}
