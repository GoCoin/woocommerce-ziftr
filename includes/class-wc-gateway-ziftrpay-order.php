<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Creates an order and populates it with the correct data.
 */
class WC_Gateway_Ziftrpay_Order {

	private $_ziftr_order;
	private $_configuration;

	/**
	 * Gets or creates a ZiftrPAY order
	 * @return string The URL of the cart
	 */
	public function get_checkout_url() {
		$links = $this->_ziftr_order->getLinks('checkout',1);
		return empty($links) ? null : $links[0];
	}

	static function from_cart( $cart, $configuration ) {
		$instance = new WC_Gateway_Ziftrpay_Order();

		$instance->_configuration = $GLOBALS['wc_ziftr']->get_configuration();

		$wc_order = WC()->checkout()->create_order();

		$existing_zoid = get_post_meta( $wc_order, '_ziftrpay_order_id' );

		if ( !empty($existing_zoid) ) {
			// todo: resume existing order
		}

		$order = new \Ziftr\ApiClient\Request('/orders/', $configuration);

		$main_url = get_site_url();
		$success_url = $main_url;
		$failure_url = $main_url;

		try{
			$order = $order->post(
				array(
					'order' => array(
						'currency_code' => 'USD',
						'is_shipping_required' => ($cart->shipping_total > 0),
						'shipping_price' => $cart->shipping_total * 100,
						'seller_data' => array( 'wc_order_id' => $wc_order ),
						'seller_order_success_url' => $success_url,
						'seller_order_failure_url' => $failure_url
						)
				     )
				);
		} catch ( Exception $e ) {
			print_r($order->getResponse());
		}

		update_post_meta( $wc_order, '_ziftrpay_order_id', $order->getResponse()->order->id );
		update_post_meta( $wc_order, '_payment_method', 'ziftrpay' );
		update_post_meta( $wc_order, '_payment_method_title', 'ZiftrPAY' );

		$itemsReq = $order->linkRequest('items');

		foreach ( $cart->cart_contents as $item ) {

			$quantity = $item['quantity'];
			$price    = $item['data']->price;
			$name     = $item['data']->post->post_title;

			$itemsReq->post(array(
						'order_item' => array(
							'name' => $name,
							'price' => $price * 100,
							'quantity' => $quantity,
							'currency_code' => 'USD'
							)
					     ));
		}

		$instance->_ziftr_order = $order;
		return $instance;
	}
}
