<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists('WC_Ziftrpay_Gateway') ) {

class WC_Ziftrpay_Gateway extends WC_Payment_Gateway
{

	public function __construct()
	{
		$this->id                 = 'ziftrpay';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to ZiftrPAY', 'woocommerce' );
		$this->method_title       = __( 'ZiftrPAY', 'woocommerce' );
		$this->method_description = __( 'ZiftrPAY works by sending customers to ZiftrPAY where they can enter their payment information and pay with credit card or cryptocurrency.', 'woocommerce' );
		$this->supports           = array(
						'products'
					  );

		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->show_above_checkout = 'yes' === $this->get_option( 'show_above_checkout', 'yes' );
		$this->show_on_cart        = 'yes' === $this->get_option( 'show_on_cart', 'yes' );
		$this->sandbox             = 'yes' === $this->get_option( 'api_sandbox', 'no' );
		$this->publishable_key     = $this->get_option( 'api_publishable_key' );
		$this->private_key         = $this->get_option( 'api_private_key' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		}
	}

	/**		
	 * Admin Panel Options		
	 * - Options for bits like 'title' and availability on a country-by-country basis		
	 *		
	 * @since 1.0.0		
	 */		
	public function admin_options() {		
		if ( $this->is_valid_for_use() ) {		
			?>		
				<h3><?php __( 'ZiftrPAY', 'woocommerce' ); ?></h3>		

				<?php if ( empty( $this->publishable_key ) && empty( $this->private_key ) ) : ?>		
				<div class="ziftrpay-banner updated">		
				<img src="<?php echo plugins_url('/assets/images/admin_logo.png',__FILE__); ?>" />		
				<p class="main"><strong><?php _e( 'Getting started', 'woocommerce' ); ?></strong></p>		
				<p><?php _e( 'ZiftrPAY is a platform that enabled you to offer your customers more choice by accepting both credit card and cryptocurrency.', 'woocommerce' ); ?></p>		

				<p><a href="https://www.ziftrpay.com/merchants/register/" target="_blank" class="button button-primary"><?php _e( 'Sign up for ZiftrPAY', 'woocommerce' ); ?></a> <a href="https://www.ziftrpay.com/" target="_blank" class="button"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>		

				</div>		
				<?php else : ?>		
				<p><?php _e( 'ZiftrPAY is a platform that enabled you to offer your customers more choice by accepting both credit card and cryptocurrency.', 'woocommerce' ); ?></p>		
				<?php endif; ?>		
				<table class="form-table">		
				<?php		
				$this->generate_settings_html();		
			?>		
				</table>			
				<?php		
		} else {		
			?>		
				<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'ZiftrPAY does not support your store currency at this time.', 'woocommerce' ); ?></p></div>		
				<?php		
		}		
	}

	/**
	 * get_icon function.
	 *
	 * @return string
	 */
	public function get_icon() {

		$icon = plugins_url('/assets/images/AC_vs_mc_zrc_tc_doge_ltc.png',__FILE__);
		$url  = 'https://www.ziftrpay.com/shoppers/';

		$html .= '<img src="' . esc_attr( $icon ) . '" alt="' . __( 'ZiftrPAY accepts credit card and cryptocurrency', 'woocommerce' ) . '" />';

		$html .= sprintf( '<a href="%1$s" class="about_ziftrpay" onclick="javascript:window.open(\'%1$s\',\'WIZiftrpay\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=700\'); return false;" title="' . esc_attr__( 'What is ZiftrPAY?', 'woocommerce' ) . '">' . esc_attr__( 'What is ZiftrPAY?', 'woocommerce' ) . '</a>', esc_url( $url ) );

		return apply_filters( 'woocommerce_gateway_icon', $html, $this->id );
	}

	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_ziftrpay_supported_currencies', array( 'USD' ) ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = include( 'settings-ziftrpay.php' );
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order
	 *
	 * @return string
	 */
	/*
	public function get_transaction_url( $order ) {
		// todo: grab the transaction URL to the admin area on ZiftrPAY

		return parent::get_transaction_url( $order );
	}
	*/

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		include_once( 'class-wc-gateway-ziftrpay-order.php' );

		$order          = wc_get_order( $order_id );

		$configuration = $GLOBALS['wc_ziftr']->get_configuration();

		$order = WC_Gateway_Ziftrpay_Order::from_cart(WC()->cart, $configuration);

		return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_url()
			    );
	}

}

}
