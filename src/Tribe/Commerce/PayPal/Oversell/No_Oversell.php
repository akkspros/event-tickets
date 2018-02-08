<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Oversell__No_Oversell
 *
 * This policy will not allow any oversell.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Oversell__No_Oversell
	extends Tribe__Tickets__Commerce__PayPal__Oversell__Policy
	implements Tribe__Tickets__Commerce__PayPal__Oversell__Policy_Interface {

	/**
	 * Whether this policy allows overselling or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function allows_overselling() {
		return false;
	}

	/**
	 * Modifies the quantity of tickets that can actually be over-sold according to
	 * this policy.
	 *
	 * @since TBD
	 *
	 * @param int $qty       The requested quantity
	 * @param int $inventory The current inventory value
	 *
	 * @return int The updated quantity
	 */
	public function modify_quantity( $qty, $inventory ) {
		return 0;
	}

	/**
	 * Returns the policy nice name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Do not oversell', 'event-tickets' );
	}
}