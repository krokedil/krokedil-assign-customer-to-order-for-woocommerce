<?php
/*
 * Plugin Name: Krokedil Assign Customer to Order for WooCommerce
 * Plugin URI: https://krokedil.se
 * Description: Creates customer account during new WooCommerce order if email doesn't exist in an existing account. If account does exist, the customer ID will be tagged to the order even if customer isn't logged in.
 * Version: 1.1.0
 * Author: Krokedil
 * Author URI: https://krokedil.se
 * WC requires at least: 3.0
 * WC tested up to: 3.5.3
 *
 * Copyright (c) 2017-2019 Krokedil
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/


add_action( 'woocommerce_checkout_order_processed', 'krokedil_maybe_assign_or_create_user', 10, 3 );

/**
 * Assigns an order to a user.
 *
 * @param array $order_id The WooCommerce order ID.
 * @return void
 */
function krokedil_maybe_assign_or_create_user( $order_id, $posted_data, $order ) {

	if ( email_exists( $order->get_billing_email() ) ) {
		// Email exist in WP.
		if ( ! $order->get_customer_id() ) {
			// No customer was assigned to the order - let's set it now.
			$user        = get_user_by( 'email', $order->get_billing_email() );
			$customer_id = $user->ID;
			$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $customer_id ) );
			$order->save();
		}
	} else {
		// Email does not exist - lets create the customer.
		// Generate username - force create user name even if get_option( 'woocommerce_registration_generate_username' ) is set to no.
		$username = sanitize_user( current( explode( '@', $order->get_billing_email() ) ), true );
		// Ensure username is unique.
		$append     = 1;
		$o_username = $username;
		while ( username_exists( $username ) ) {
			$username = $o_username . $append;
			$append++;
		}
		$customer_id = wc_create_new_customer( $order->get_billing_email(), $username, wp_generate_password() );
		$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $customer_id ) );
		$order->save();

		// Save customer address data with info from the order.
		if ( $customer_id ) {
			krokedil_save_customer_data( $customer_id, $posted_data );
		}
	}
}

/**
 * Saves order address data to user.
 *
 * @param array $data The WooCommerce posted checkout form data.
 * @return void
 */
function krokedil_save_customer_data( $customer_id, $data ) {
	// Add customer info from other fields.
	$customer = new WC_Customer( $customer_id );
	if ( ! empty( $data['billing_first_name'] ) ) {
		$customer->set_first_name( $data['billing_first_name'] );
	}
	if ( ! empty( $data['billing_last_name'] ) ) {
		$customer->set_last_name( $data['billing_last_name'] );
	}
	// If the display name is an email, update to the user's full name.
	if ( is_email( $customer->get_display_name() ) ) {
		$customer->set_display_name( $data['billing_first_name'] . ' ' . $data['billing_last_name'] );
	}
	foreach ( $data as $key => $value ) {
		// Use setters where available.
		if ( is_callable( array( $customer, "set_{$key}" ) ) ) {
			$customer->{"set_{$key}"}( $value );
			// Store custom fields prefixed with wither shipping_ or billing_.
		} elseif ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) ) {
			$customer->update_meta_data( $key, $value );
		}
	}
	/**
	 * Action hook to adjust customer before save.
	 *
	 * @since 3.0.0
	 */
	do_action( 'woocommerce_checkout_update_customer', $customer, $data );
	$customer->save();
}
