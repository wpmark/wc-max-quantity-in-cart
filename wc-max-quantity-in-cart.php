<?php
/*
Plugin Name: WC Max Quantity in Cart
Plugin URI: https://highrise.digital/
Description: A WordPress plugin that allows you to limit the quantity of an item that can be added to a WooCommerce basket.
Version: 1.0
License: GPL-2.0+
Author: Highrise Digital Ltd
Author URI: https://highrise.digital/
Text domain: wc-max-quantity-in-cart

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* exist if directly accessed */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// define variable for path to this plugin file.
define( 'WCMQIC_UTILITY_LOCATION', dirname( __FILE__ ) );
define( 'WCMQIC_UTILITY_LOCATION_URL', plugins_url( '', __FILE__ ) );

/**
 * Function to run on plugins load.
 */
function wcmqic_plugins_loaded() {

	$locale = apply_filters( 'plugin_locale', get_locale(), 'wc-max-quantity-in-cart' );
	load_textdomain( 'wc-max-quantity-in-cart', WP_LANG_DIR . '/wc-max-quantity-in-cart/wc-max-quantity-in-cart-' . $locale . '.mo' );
	load_plugin_textdomain( 'wc-max-quantity-in-cart', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );

}

add_action( 'plugins_loaded', 'wcmqic_plugins_loaded' );

/**
 * Adds a max quanity field to the inventory section on the post edit screen.
 */
function wcmqic_add_max_quantity_field() {

	
	// Add a custom text field
	woocommerce_wp_text_input(
		[
			'id'          => '_max_quantity_in_cart',
			'label'       => __( 'Maximum Quantity in Basket', 'wc-max-quantity-in-cart' ),
			'description' => __( 'Set the maximum quantity of this product which can be added to the basket in a single transaction.', 'wc-max-quantity-in-cart' ),
			'type'        => 'number',
			'desc_tip'    => true,
			'custom_attributes' => array(
				'min' => '1', // Minimum value is 1
			),
		]
	);
}

add_action( 'woocommerce_product_options_inventory_product_data', 'wcmqic_add_max_quantity_field' );

/**
 * Save the max quantity custom field value.
 */
function wcmqic_save_max_quantity_field( $post_id ) {
	
	// get the max quantity value from the posted data.
	$max_quantity = isset( $_POST['_max_quantity_in_cart'] ) ? sanitize_text_field( $_POST['_max_quantity_in_cart'] ) : '';

	// if we have a max quantity value.
	if ( ! empty( $max_quantity ) ) {

		// save the value as post meta data.
		update_post_meta( $post_id, '_max_quantity_in_cart', $max_quantity );

	} else {

		// delete the post meta data if empty.
		delete_post_meta( $post_id, '_max_quantity_in_cart' );
	}
}

// Save the custom field value
add_action( 'woocommerce_process_product_meta', 'wcmqic_save_max_quantity_field' );

/**
 * Limits the number of a product that can be added to the cart.
 * When the product has a max quantity.
 */
function wcmqic_limit_product_quantity_in_cart( $passed, $product_id, $quantity ) {
    
	// get the max quantity from the product.
	$max_quantity = get_post_meta( $product_id, '_max_quantity_in_cart', true );

	//if we don't have a max quantity.
	if ( empty( $max_quantity ) ) {
		return $passed;
	}

    // Get the existing quantity of the product in the cart
    $existing_quantity = 0;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( $cart_item['product_id'] == $product_id ) {
            $existing_quantity = $cart_item['quantity'];
            break;
        }
    }

    // Check if the new quantity exceeds the maximum allowed
    if ( ( $existing_quantity + $quantity ) > $max_quantity ) {
        wc_add_notice(
            sprintf(
                'You can only add up to %d of this product to your cart.',
                $max_quantity
            ),
            'error'
        );
        return false;
    }

    return $passed;
}

add_filter( 'woocommerce_add_to_cart_validation', 'wcmqic_limit_product_quantity_in_cart', 10, 3 );

/**
 * Limits the number of a product that can be updated in the cart.
 */
function wcmqic_limit_product_quantity_in_cart_update( $passed, $cart_item_key, $values, $quantity ) {

	// get the max quantity from the product.
	$max_quantity = get_post_meta( $values['product_id'], '_max_quantity_in_cart', true );

	//if we don't have a max quantity.
	if ( empty( $max_quantity ) ) {
		return $passed;
	}

    if ( $quantity > $max_quantity ) {
        wc_add_notice(
            sprintf(
                'You can only have a maximum of %d of this product in your basket.',
                $max_quantity
            ),
            'error'
        );
        return false;
    }

    return $passed;
}

add_filter( 'woocommerce_update_cart_validation', 'wcmqic_limit_product_quantity_in_cart_update', 10, 4 );
