<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for ZiftrPAY Gateway
 */
return array(
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable ZiftrPAY', 'woocommerce' ),
		'default' => 'yes'
	),
	'title' => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'ZiftrPAY', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'Pay via ZiftrPAY; you can pay with credit card or cryptocurrency even if you don\'t have a ZiftrPAY account.', 'woocommerce' )
	),
	'show_above_checkout' => array(
		'title'   => __( 'Show above checkout', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Show above checkout', 'woocommerce' ),
		'description' => __( 'ZiftrPAY users have already entered their address. Show a notice above the checkout form to prompt them to login and save the extra data work.', 'woocommerce' ),
		'default' => 'yes'
	),
	'show_on_cart' => array(
		'title'   => __( 'Show on cart', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Show on cart page', 'woocommerce' ),
		'description' => __( 'Allow ZiftrPAY users to go directly to the ZiftrPAY checkout from the cart page.', 'woocommerce' ),
		'default' => 'yes'
	),
	'api_details' => array(
		'title'       => __( 'API Credentials', 'woocommerce' ),
		'type'        => 'title',
	),
	'api_sandbox' => array(
		'title'       => __( 'ZiftrPAY Sandbox', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Use the sandbox', 'woocommerce' ),
		'default'     => 'no',
		'description' => __( 'ZiftrPAY sandbox is used to test integration and payments.<br>Sandbox mode does not accept live credit cards or cryptocurrency.', 'woocommerce' ),
		'desc_tip'    => true
	),
	'api_publishable_key' => array(
		'title'       => __( 'API Publishable Key', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from the ZiftrPAY merchant dashboard.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'pub_################', 'woocommerce' )
	),
	'api_private_key' => array(
		'title'       => __( 'API Private Key', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from the ZiftrPAY merchant dashboard.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'prv_################################', 'woocommerce' )
	)
);
