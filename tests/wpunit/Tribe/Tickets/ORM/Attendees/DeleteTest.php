<?php

namespace Tribe\Tickets\ORM\Attendees;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__Attendee_Repository as Attendee_Repository;

class DeleteTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true', 15 );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		}, 15 );
	}

	/**
	 * It should allow deleting ticket attendees.
	 *
	 * @test
	 */
	public function should_allow_deleting_attendees() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$deleted = $attendees->delete();

		$this->assertEqualSets( array_merge( $paypal_attendee_ids, $rsvp_attendee_ids ), $deleted );
	}

	/**
	 * It should allow deleting ticket attendees from the rsvp context.
	 *
	 * @test
	 */
	public function should_allow_deleting_attendees_from_rsvp_context() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees( 'rsvp' );

		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$deleted = $attendees->delete();

		$this->assertEqualSets( $rsvp_attendee_ids, $deleted );
	}

	/**
	 * It should allow deleting ticket attendees from the tribe-commerce context.
	 *
	 * @test
	 */
	public function should_allow_deleting_attendees_from_tribe_commerce_context() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees( 'tribe-commerce' );

		$post_id = $this->factory->post->create();

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $post_id );

		$paypal_attendee_ids = $this->create_many_attendees_for_ticket( 5, $paypal_ticket_id, $post_id );
		$rsvp_attendee_ids   = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$deleted = $attendees->delete();

		$this->assertEqualSets( $paypal_attendee_ids, $deleted );
	}

}
