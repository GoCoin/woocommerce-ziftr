<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Creates an order and populates it with the correct data.
 */
class WC_Gateway_GoPayWin_Order {

	private $_gopaywin_order;
	private $_wc_order;
	private $_configuration;

	/**
	 * Gets or creates a GoPayWinPAY order
	 * @return string The URL of the cart
	 */
	public function get_checkout_url() {
		$links = $this->_gopaywin_order->getLinks('checkout',1);
		return empty($links) ? null : $links[0];
	}

	static function from_cart( $gateway, $cart, $existing_order = null ) {
		$instance = new WC_Gateway_GoPayWin_Order();

		$configuration = $instance->_configuration = $GLOBALS['wc_gopaywin']->get_configuration();

		$instance->_wc_order = $wc_order = ($existing_order ? $existing_order : WC()->checkout()->create_order());

		$existing_zoid = get_post_meta( $wc_order, '_gopaywin_order_id' );

		if ( !empty($existing_zoid) ) {
			// todo: resume existing order
		}

		$order = new \GoPayWin\ApiClient\Request('/orders/', $configuration);

		$main_url = get_site_url();
		$success_url = $main_url;
		$failure_url = $main_url;

		try{
			$d = array(
				'order' => array(
					'currency_code' => get_woocommerce_currency(),
					'is_shipping_required' => ($cart->shipping_total > 0),
					'shipping_price' => $cart->shipping_total * 100,
					'seller_data' => array( 'wc_order_id' => $wc_order->id ),
					'seller_order_success_url' => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $wc_order ) ) ),
					'seller_order_failure_url' => $wc_order->get_cancel_order_url_raw()
				)
			);

			$order = $order->post($d);

		} catch ( Exception $e ) {
			error_log("GoPayWin Error: " . $e->getMessage());
			return false;
		}

		update_post_meta( $wc_order, '_gopaywin_order_id', $order->getResponse()->order->id );
		update_post_meta( $wc_order, '_payment_method', 'gopaywin' );
		update_post_meta( $wc_order, '_payment_method_title', 'GoPayWin' );

		$itemsReq = $order->linkRequest('items');

		foreach ( $cart->cart_contents as $item ) {

			$quantity = $item['quantity'];
			$price    = $item['data']->price;
			$name     = $item['data']->post->post_title;
			$tax      = $item['line_tax'];

			try {
				$itemsReq->post(array(
						'order_item' => array(
							'name' => $name,
							//'tax' => round($tax * 100),
							'price' => $price * 100,
							'quantity' => $quantity,
							'currency_code' => 'USD'
							)
					     ));
			} catch ( Exception $e ) {
				error_log("GoPayWin Error: " . $e->getMessage());
				return false;
			}
		}

		$instance->_gopaywin_order = $order;
		return $instance;
	}

	static function from_gopaywin_id( $gateway, $gopaywin_id ) {
		$instance = new WC_Gateway_GoPayWin_Order();

		$configuration = $instance->_configuration = $GLOBALS['wc_gopaywin']->get_configuration();

		$order = new \GoPayWin\ApiClient\Request('/orders/' . $gopaywin_id, $configuration);

		try{
			$raw = $order->get($d);
			$order = $order->getResponse();
		} catch ( Exception $e ) {
			error_log("GoPayWin Error: " . $e->getMessage());
			return false;
		}

		if ( $order && !empty($order->order->seller_data->wc_order_id) ) {
			$id = $order->order->seller_data->wc_order_id;
			$wc_order = wc_get_order($id);

			if ( $wc_order ) {
				$instance->_wc_order = $wc_order;
				$instance->_gopaywin_order = $order;

				$state = $order->order->state;
				$method = 'payment_' . str_replace(' ','_',$state);

				$instance->$method();
			} else {
				return false;
			}
		}

		return $instance;
	}

	protected function payment_awaiting_processing() {
		if ( $this->_wc_order->has_status( 'completed' ) ) {
			$this->_wc_order->payment_complete( $txn_id );
			$this->_wc_order->reduce_order_stock();
		}
        }

	protected function payment_on_hold() {
		if ( $this->_wc_order->has_status( 'on-hold' ) ) {
			$this->_wc_order->update_status( 'on-hold', $reason );
			$this->_wc_order->order->reduce_order_stock();
		}
	}
}
