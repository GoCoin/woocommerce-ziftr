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
		class WC_Ziftr
		{

			public function __construct()
			{
				// adding admin settings
				add_action( 'admin_init', array( $this, 'WC_settings_init' ) );

				// adding admin menu
				add_action( 'admin_menu', array( $this, 'WC_add_admin_menu') );

				// gets called only after woocommerce has finished loading
				add_action( 'woocommerce_init', array( &$this, 'woocommerce_loaded' ) );

				// this is called after the bulk edit is saved
				add_action( 'woocommerce_product_bulk_edit_save' , array( $this,'woocommerce_product_quick_edit_save' ) );

				// this is called after the quick edit is saved
				add_action( 'woocommerce_product_quick_edit_save',array( $this,'woocommerce_product_quick_edit_save' ) );

				// this is called on the single product page
				add_action( 'woocommerce_after_single_product',array( $this,'woocommerce_after_single_product' ) );

				// this is called before the checkout form submit
				add_action( 'woocommerce_checkout_before_customer_details',array( $this,'woocommerce_checkout_before_customer_details' ) );

				// this handles edit and trash of products
				add_action( 'save_post',array( $this,'post_product_update' ),10,1 );

				// called after the product/post is trashed
				add_action( 'trashed_post',array( $this,'trashed_post' ) );

				// called after the product/post is trashed
				add_action( 'deleted_post ',array( $this,'trashed_post' ) );

				// adding ziftr checkout along with  regular woocommerce checkout
				add_filter( 'woocommerce_proceed_to_checkout', array( $this,'add_ziftr_checkout_after_reqular_checkout' ) );

				// called after the order is completed by the admin
				add_action( 'woocommerce_order_status_completed',array( $this,'woocommerce_order_status_completed' ),10,1 );

				// gets called when the payement is  completed
				add_action( 'woocommerce_thankyou',array( $this,'woocommerce_thankyou'),10,1 );

				// this hook is called on the product transition phase, with many transition phases from adding, order pending to  order completion.
				add_action( 'transition_post_status', array( $this,'transition_post_status' ), 10, 3 );

				/*** getting the information about low stock seee this action hook
				 * woocommerce_low_stock
				 ***/
			}
			/**
			 * Activate the Plugin
			 */
			public static function activate(){

			}

			/**
			 * Deactivate the Plugin
			 */
			public static function deactivate(){

			}
			/**
			 * do anything after woocommerce is loaded
			 */
			public function woocommerce_loaded( $product ) {
				//code if needed after the woocommerce is loaded.
			}

			public function woocommerce_product_quick_edit_save(){

				//write the action after the product is edited in quick mode
			}

			/**
			 * Do anything on the single product page when its loaded
			 **/
			public function woocommerce_after_single_product(){
				global $post;
				$embed_image = '';
				if( get_permalink( $post->ID ) )
					$embed_image = '<img src='.get_permalink( $post->ID ).'>';
				echo " Embed a <img> tag on every product page with some arguments saying what the product is <br />";
				echo $embed_image;
			}

			/**
			 * Do anything on the before the checkout form
			 **/
			public function woocommerce_checkout_before_customer_details( $product ){
				global $woocommerce;
				global $post;
				$checkout_url = get_permalink( $post->ID );
				$product_title = "";
				$embed_checkout_url ="";
				$items = $woocommerce->cart->get_cart();
				foreach( $items as $item => $values ){
					$item_product = $values['data']->post;
					//echo "<pre>"; print_r( $item_product );
					$product_title .= $item_product->post_title.'&';
				}
				$embed_checkout_url .= $checkout_url.'?'.$product_title;
				$embed_checkout_url = '<img src='.$embed_checkout_url.'>';
				echo "Embed a <img> tag on every checkout page with some arguments about the checkout <br />";
				echo $embed_checkout_url;
			}

			/**
			 * Do anything when the product is submitted through post
			 * Uses : $_POST
			 **/
			public function post_product_update( $post_id ){
				if ( wp_is_post_revision( $post_id ) )
					return;
				global $post_type;
				//global $post;
				global $action;
				//apply this for only post type with product
				if( $post_type == 'product' ){
					//print_r($_POST);exit;
					// $post_title = get_the_title( $post_id );

					// $post_url = get_permalink( $post_id );
					if( !empty( $action ) ){
						switch ( $action ) {
							case "editpost":
								echo "editing post";
								break;
							case "trash":
								$this->trashed_post();
								break;
						}
					}
				}
			}

			public function trashed_post(){
				// echo "after trashed"; exit;
			}

			public function add_ziftr_checkout_after_reqular_checkout(){
				global $woocommerce;
				global $options;
				// echo "<pre >"; print_r( $woocommerce->cart );
				// echo "<pre >"; print_r( $woocommerce->cart->subtotal_ex_tax );
				print_r( $options );
				echo "guru";
				?>
					<input type="button"  class ="button ziftr-button" value="Ziftr Checkout" onClick = "javascript:location.href='http://ziftr.com'">
					<?php
			}

			/**
			 * Do anything After the produt order is marked as completed by the admin
			 * Uses : is_admin() for admin repalted work
			 **/
			public function woocommerce_order_status_completed( $order_id  ){
				$order = new WC_Order( $order_id );
				// echo "<pre>";print_r( $order ); exit;
			}

			/**
			 * Do anything When the payement is completed, resulting on woocommerece thank you
			 **/
			public function woocommerce_thankyou( $order_id ){
				$order = new WC_Order( $order_id );
				print_r( $order );
			}

			/**
			 * Do anything on different product transition phases
			 * Uses : $new_status, $old_status, $post
			 **/
			public function transition_post_status( $new_status, $old_status, $post ){
				// Here if the new status is publish then the new product is added
				// so based on that  the code if any after new product could be written.
				// this method will help even for the trash , Getting the  product status after the customer makes payement , when the order is coplete etc.
				//echo $new_status;exit;
			}

			/**
			 * Add Admin Menu under Settings
			 **/
			function WC_add_admin_menu() {

				add_options_page( 'Ziftr Extension Page', 'Ziftr Extension Menu', 'manage_options', 'pluginPage', array( $this, 'ziftr_woocommerce_extension_options_page' ) );

			}

			/**
			 * Check if hte settings exists
			 **/
			function WC_settings_exist() {

				if( false == get_option( array( $this, 'ziftr_woocommerce_extension_settings') ) ) {

					add_option( 'ziftr_woocommerce_extension_settings' );

				}
			}

			/**
			 * Admin Settings init
			 **/
			function WC_settings_init() {

				register_setting( 'pluginPage', 'WC_ziftr_settings' );

				add_settings_section( 'WC_pluginPage_section', 'Main Settings', array( $this, 'WC_settings_section_callback' ),'pluginPage' );

				add_settings_field('WC_ziftr_api_field', 'Enter the API here:', array( $this, 'WC_ziftr_api_field_render' ), 'pluginPage', 'WC_pluginPage_section' );

				//example for dummy setting field
				add_settings_field('WC_ziftr_dummy_field', 'Enter the Dummy Option Here:', array( $this, 'WC_ziftr_dummy_field_render' ), 'pluginPage', 'WC_pluginPage_section' );
			}

			/**
			 * Rendering Saved Options for first setting option
			 **/
			function WC_ziftr_api_field_render() {

				$options = get_option( 'WC_ziftr_settings' );
				?>
					<input type='text' name='WC_ziftr_settings[WC_ziftr_api_field]' value='<?php echo $options['WC_ziftr_api_field']; ?>'>
					<?php
			}

			// Rendering the saved Option
			function WC_ziftr_dummy_field_render(){

				$options = get_option( 'WC_ziftr_settings' );
				?>
					<input type='text' name='WC_ziftr_settings[WC_ziftr_dummy_field]' value='<?php echo $options['WC_ziftr_dummy_field']; ?>'>
					<?php
			}


			function WC_settings_section_callback() {

				echo "Please write the setting instructions for the API here";

			}

			/**
			 * Setting Page
			 **/
			function ziftr_woocommerce_extension_options_page(  ) {

				if( !current_user_can( 'manage_options' ) )

					wp_die( 'You dont have the required permission to access this page' );

				?>
					<form action='options.php' method='post'>
					<h2>Ziftr Plugin Settings</h2>
					<?php
					settings_fields( 'pluginPage' );
					do_settings_sections( 'pluginPage' );
					submit_button();
					?>
					</form>
					<?php
			}
		}

		/**
		 * Registering Hooks
		 **/
		register_activation_hook( __FILE__, array( 'WC_Ziftr', 'activate' ) );

		register_deactivation_hook( __FILE__, array( 'WC_Ziftr', 'deactivate' ) );

		/**
		 * instntiating Class
		 **/
		$GLOBALS['wc_ziftr'] = new WC_Ziftr();
	}//END if ( !class_exists( WC_Ziftr ) )
}
