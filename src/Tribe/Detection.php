<?php

if ( ! class_exists( 'Tribe__Tickets__Detection' ) ) {


	class Tribe__Tickets__Detection {

		protected $active_modules;
		protected $ticket_types = array();
		protected $ticket_class = array();

		/**
		 * Class constructor
		 */
		public function __construct() {

			$this->active_modules = Tribe__Tickets__Tickets::modules();
			$this->setup_data();

		}

		protected function setup_data() {

			foreach ( Tribe__Tickets__Tickets::modules() as $module_class => $module_instance ) {

				/**
				 * The usage of plain `$module_class::ATTENDEE_EVENT_KEY` will throw a `T_PAAMAYIM_NEKUDOTAYIM`
				 * when using PHP 5.2, which is a fatal.
				 *
				 * So we have to construct the constant name using a string and use the `constant` function.
				 */
				$types['order']   = constant( "$module_class::ORDER_OBJECT" );
				$types['product'] = $module_class::get_instance()->ticket_object;
				$types['ticket']  = constant( "$module_class::ATTENDEE_OBJECT" );
				if ( 'Tribe__Tickets__RSVP' === $module_class ) {
					$types['ticket'] = $module_class::get_instance()->ticket_object;
				}
				$types['attendee'] = constant( "$module_class::ATTENDEE_OBJECT" );

				$this->ticket_class[ $module_class ] = array();

				foreach ( $types as $key => $value ) {

					$this->ticket_types[ $key ][]                = $value;
					$this->ticket_class[ $module_class ][ $key ] = $value;

				}
				if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' === $module_class ) {
					$this->ticket_class[ $module_class ]['tribe_for_event'] = $module_class::$event_key;
//					$this->ticket_class[ $module_class ]['tribe_for_event'] = $module_class::get_instance()->event_key;
				} else {
					$this->ticket_class[ $module_class ]['tribe_for_event'] = $module_class::get_instance()->event_key;
				}

				$this->ticket_class[ $module_class ]['event_id_key'] = constant( "$module_class::ATTENDEE_EVENT_KEY" );
				$this->ticket_class[ $module_class ]['order_id_key'] = constant( "$module_class::ATTENDEE_ORDER_KEY" );
			}

			$this->ticket_types['events'][] = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : '';

		}

		/**
		 * Detect what is available in the custom post type by the id passed to it
		 *
		 * @param $id
		 *
		 * @return array|bool array includes infomation available and the tribe_tickets_tickets class to use
		 */
		public function detect_by_id( $post_id ) {

			$post_id = absint( $post_id );

			$cpt = get_post_type( $post_id );

			// if no custom post type
			if ( ! $cpt ) {
				return false;
			}

			//$cpt_arr[$cpt] = array();
			$cpt_arr = array();

			$cpt_arr['post_type'] = $cpt;

			foreach ( $this->ticket_types as $type => $cpts ) {

				if ( in_array( $cpt, $cpts ) ) {
					$cpt_arr[] = $type;
				}

			}

			foreach ( $this->ticket_class as $classes => $cpts ) {

				if ( in_array( $cpt, $cpts ) ) {
					$cpt_arr['class'] = $classes;
				}

			}

			return $cpt_arr;

		}

		/**
		 * Return Array of Event IDs when pass Order, Ticket, or Attendee ID
		 *
		 * @param $post_id
		 *
		 * @return array
		 */
		public function get_event_ids( $post_id ) {

			$post_id = absint( $post_id );

			$services = Tribe__Tickets__Detection::get_instance()->detect_by_id( $post_id );
//			log_me( $services );

			// if this id is not an order id or a ticket id return
			if ( empty( array_intersect( array( 'order', 'ticket', 'attendee', 'product' ), $services ) ) ) {
				return array();
			}

			// if no post type or module class return
			if ( empty( $services['post_type'] ) || empty( $services['class'] ) ) {
				return array();
			}

			$module_class = $services['class'];
			$event_id_key = $this->ticket_class[ $module_class ]['event_id_key'];
			$event_ids    = array();

			if ( ! empty( array_intersect( array( 'product' ), $services ) ) ) {
				$tribe_for_event = $this->ticket_class[ $module_class ]['tribe_for_event'];
				$event_ids[] = get_post_meta( $post_id, $tribe_for_event, true );

				return $event_ids;
			}


			// if rsvp or a ticket id get the connected id field
			if ( 'Tribe__Tickets__RSVP' === $module_class || ! empty( array_intersect( array( 'ticket', 'attendee' ), $services ) ) ) {
				$event_ids[] = get_post_meta( $post_id, $event_id_key, true );

				return $event_ids;

			}

			$ticket_cpt   = $this->ticket_class[ $module_class ]['ticket'];
			$order_id_key = $this->ticket_class[ $module_class ]['order_id_key'];

			if ( ! $order_id_key ) {
				return array();
			}
			$order_tickets = get_posts( array(
				'post_type'      => $ticket_cpt,
				'meta_key'       => $order_id_key,
				'meta_value'     => $post_id,
				'posts_per_page' => - 1,
			) );

			foreach ( $order_tickets as $ticket ) {

				$event_id = get_post_meta( $ticket->ID, $event_id_key, true );

				if ( ! in_array( $event_id, $event_ids ) ) {
					$event_ids[] = $event_id;
				}
			}

			return $event_ids;
		}




		/**
		 * Return Ticket Provider by Order, Product, Attendee, or Ticket ID
		 *
		 * @param $post_id
		 *
		 * @return bool/object
		 */
		public function get_ticket_provider( $post_id ) {

			$post_id = absint( $post_id );

			$services = Tribe__Tickets__Detection::get_instance()->detect_by_id( $post_id );

			// if no module class return
			if ( empty( $services['class'] ) ) {
				return false;
			}

			if ( ! class_exists( $services['class'] ) ) {
				return false;
			}

			return $services['class']::get_instance();

		}

		public function get_attendees_by_id( $post_id ) {

			$post_id = absint( $post_id );

			$services = Tribe__Tickets__Detection::get_instance()->detect_by_id( $post_id );

			return $services['class']::get_instance()->get_attendees_by_id( $post_id, $services['post_type'] );

		}


		/********** SINGLETON FUNCTIONS **********/

		/**
		 * Instance of this class for use as singleton
		 */
		private static $instance;

		/**
		 * Creates the instance of the class
		 *
		 * @static
		 * @return void
		 */
		public static function init() {
			self::$instance = self::get_instance();
		}


		public static function get_instance() {
			if ( ! self::$instance instanceof self ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

}