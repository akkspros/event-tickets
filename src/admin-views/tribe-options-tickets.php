<?php

/**
 * Filter to allow users to add/alter ignored post types
 *
 * @since TBD
 */
$post_types_to_ignore = apply_filters( 'tribe_tickets_settings_post_type_ignore_list', array(
	'attachment',
) );

$all_post_type_objects = get_post_types( array( 'public' => true ), 'objects' );
$all_post_types        = array();

foreach ( $all_post_type_objects as $post_type => $post_type_object ) {
	$should_ignore = false;

	foreach ( $post_types_to_ignore as $ignore ) {
		if ( preg_match( '/' . preg_quote( $ignore, '/' ) . '/', $post_type ) ) {
			$should_ignore = true;
			break;
		}
	}

	if ( $should_ignore ) {
		continue;
	}

	$all_post_types[ $post_type ] = $post_type_object->labels->singular_name;
}

$all_post_types = apply_filters( 'tribe_tickets_settings_post_types', $all_post_types );

$options = get_option( Tribe__Main::OPTIONNAME, array() );

/**
 * List of ticketing solutions that support login requirements (ie, disabling or
 * enabling the ticket form according to whether a user is logged in or not).
 *
 * @param array $ticket_systems
 */
$ticket_addons = apply_filters( 'tribe_tickets_settings_systems_supporting_login_requirements', array(
	'event-tickets_rsvp' => __( 'Require users to log in before they RSVP', 'event-tickets' ),
	'event-tickets_all'  => __( 'Require users to log in before they purchase tickets', 'event-tickets' ),
) );

$tickets_fields = array(
	'tribe-form-content-start'  => array(
		'type' => 'html',
		'html' => '<div class="tribe-settings-form-wrap">',
	),
	'tickets-title'             => array(
		'type' => 'html',
		'html' => '<h3>' . esc_html__( 'Ticket Settings', 'event-tickets' ) . '</h3>',
	),
	'ticket-enabled-post-types' => array(
		'type'            => 'checkbox_list',
		'label'           => esc_html__( 'Post types that can have tickets', 'event-tickets' ),
		// only set the default to tribe_events if the ticket-endabled-post-types index has never been saved
		'default'         => array_key_exists( 'ticket-enabled-post-types', $options ) ? false : 'tribe_events',
		'options'         => $all_post_types,
		'can_be_empty'    => false,
		'validation_type' => 'options_multi',
	),
);

/**
 * If  The Events Calendar is active let's add an option to control the position
 * of the ticket forms in the events view.
 */
if ( class_exists( 'Tribe__Events__Main' ) ) {
	$ticket_form_location_options = array(
		'tribe_events_single_event_after_the_meta'     => __( 'Below the event details [default]', 'event-tickets' ),
		'tribe_events_single_event_before_the_meta'    => __( 'Above the event details', 'event-tickets' ),
		'tribe_events_single_event_after_the_content'  => __( 'Below the event description', 'event-tickets' ),
		'tribe_events_single_event_before_the_content' => __( 'Above the event description', 'event-tickets' ),
	);

	$tickets_fields = array_merge( $tickets_fields, array(
		'ticket-rsvp-form-location'     => array(
			'type'            => 'dropdown',
			'label'           => esc_html__( 'Location of RSVP form', 'event-tickets' ),
			'options'         => $ticket_form_location_options,
			'validation_type' => 'options',
			'parent_option'   => Tribe__Events__Main::OPTIONNAME,
			'default'         => reset( $ticket_form_location_options ),
		),
		'ticket-commerce-form-location' => array(
			'type'            => 'dropdown',
			'label'           => esc_html__( 'Location of Tickets form', 'event-tickets' ),
			'options'         => $ticket_form_location_options,
			'validation_type' => 'options',
			'parent_option'   => Tribe__Events__Main::OPTIONNAME,
			'default'         => reset( $ticket_form_location_options ),
		),
	) );
}

$tickets_fields = array_merge( $tickets_fields, array(

		'ticket-authentication-requirements-heading' => array(
			'type' => 'html',
			'html' => '<h3>' . __( 'Login Requirements', 'event-tickets' ) . '</h3>',
		),
		'ticket-authentication-requirements-advice'  => array(
			'type' => 'html',
			'html' => '<p>'
			          . sprintf( __( 'You can require that users log into your site before they are able to RSVP (or buy tickets). Please review your WordPress Membership option (via the General Settings admin screen) before adjusting this setting.',
					'event-tickets' ), '<a href="' . esc_url( get_admin_url( null, 'options-general.php' ) ) . '" target="_blank">', '</a>' )
			          . '</p>',
		),
		'ticket-authentication-requirements'         => array(
			'type'            => 'checkbox_list',
			'options'         => $ticket_addons,
			'validation_type' => 'options_multi',
			'can_be_empty'    => true,
		),
	)
);

$tickets_fields['ticket-paypal-heading'] = array(
	'type' => 'html',
	'html' => '<h3>' . __( 'Tribe Commerce', 'event-tickets' ) . '</h3>',
);

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$tickets_plus_plugin       = 'event-tickets-plus/event-tickets-plus.php';
$available_plugins         = get_plugins();
$is_tickets_plus_available = array_key_exists( $tickets_plus_plugin, $available_plugins );

if ( ! $is_tickets_plus_available ) {
	$plus_link = sprintf(
		'<a href="https://theeventscalendar.com/product/wordpress-event-tickets-plus/?utm_campaign=in-app&utm_medium=plugin-tickets&utm_source=post-editor" target="_blank">%s</a>',
		__( 'Events Tickets Plus', 'tribe-common' )
	);

	$plus_message = sprintf(
		__( 'Tribe Commerce is a light implementation of a commerce gateway using PayPal and simplified stock handling. If you\'re looking for more advanced features, please consider %s.', 'event-tickets' ),
		$plus_link
	);

	$tickets_fields['ticket-paypal-et-plus-header'] = array(
		'type' => 'html',
		'html' => '<p>' . $plus_message . '</p>',
	);
}

$tickets_fields['ticket-paypal-enable'] = array(
	'type'            => 'checkbox_bool',
	'label'           => esc_html__( 'Enable Tribe Commerce ', 'event-tickets' ),
	'tooltip'         => esc_html__( 'Enable all functions provided by Tribe Commerce.', 'event-tickets' ),
	'size'            => 'medium',
	'default'         => '1',
	'validation_type' => 'boolean',
);

if ( tribe_get_option( 'ticket-paypal-enable', true ) ) {
	$pages = get_pages( array( 'post_status' => 'publish', 'posts_per_page' => - 1 ) );

	if ( ! empty( $pages ) ) {
		$pages        = array_combine( wp_list_pluck( $pages, 'ID' ), wp_list_pluck( $pages, 'post_title' ) );
		$default_page = reset( $pages );
	} else {
		$pages        = array( 0 => __( 'There are no published pages', 'event-tickets' ) );
		$default_page = null;
	}

	$tpp_success_shortcode = 'tribe-tpp-success';

	/**
	 * Filters the available Tribe__Tickets__Commerce__Currency currency code options for TPP
	 *
	 * @since TBD
	 */
	$paypal_currency_code_options = tribe( 'tickets.commerce.currency' )->generate_currency_code_options();

	$paypal_ipn_notify_url_setting_link = add_query_arg(
		array( 'cmd' => '_profile-ipn-notify' ),
		tribe( 'tickets.commerce.paypal.gateway' )->get_settings_url()
	);

	$ipn_notification_settings_link = '<a href="'
	                                  . $paypal_ipn_notify_url_setting_link
	                                  . '" target="_blank">' . esc_html__( 'Profile and Settings > My selling tools > Instant Payment Notification > Update', 'event-tickets' )
	                                  . '</a>';

	$paypal_ipn_notification_history_link = add_query_arg(
		array( 'cmd' => '_display-ipns-history' ),
		tribe( 'tickets.commerce.paypal.gateway' )->get_settings_url()
	);

	$ipn_notification_history_link = '<a href="'
	                                 . $paypal_ipn_notification_history_link
	                                 . '" target="_blank">'
	                                 . esc_html__( 'Profile and Settings > My selling tools > Instant Payment Notification > IPN History Page', 'event-tickets' )
	                                 . '</a>';

	$current_user = get_user_by( 'id', get_current_user_id() );

	$tickets_fields = array_merge(
		$tickets_fields,
		array(
			'ticket-paypal-email'                           => array(
				'type'            => 'email',
				'label'           => esc_html__( 'PayPal Email', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Email address that will receive PayPal payments.', 'event-tickets' ),
				'size'            => 'medium',
				'default'         => '',
				'validation_type' => 'email',
			),
			'ticket-paypal-sandbox'                         => array(
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'PayPal Sandbox', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables PayPal Sandbox mode for testing.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			),
			'ticket-paypal-notify-history'                  => array(
				'type'            => 'wrapped_html',
				'label'           => esc_html__( 'See your IPN Notification history', 'event-tickets' ),
				'html'            => '<p>' . sprintf( esc_html__( 'You can see and manage your IPN Notifications history from the IPN Notifications settings area (%s).',
						'event-tickets' ), $ipn_notification_history_link ) . '</p>',
				'size'            => 'medium',
				'validation_type' => 'html',
			),
			'ticket-paypal-notify-url'                      => array(
				'type'            => 'text',
				'label'           => esc_html__( 'IPN Notify URL', 'event-tickets' ),
				'tooltip'         => sprintf( esc_html__( 'Override the default IPN notify URL with this value. This value must be the same set in PayPal IPN Notifications settings area (%s).',
					'event-tickets' ), $ipn_notification_settings_link ),
				'default'         => home_url(),
				'validation_type' => 'html',
			),
			'ticket-paypal-identity-token'                  => array(
				'conditional'     => 'pdt' === tribe( 'tickets.commerce.paypal.gateway' )->get_handler_slug(),
				'type'            => 'text',
				'label'           => esc_html__( 'PayPal Identity Token', 'event-tickets' ),
				'tooltip'         => esc_html__( 'This is an optional field that will allow you to identify pending and successful payments without the need for PayPal IPN. To obtain your identifier, log into your PayPal account, click on Profile, then click on Website Payment Preferences. Here, enable Payment Data Transfer. You will then see your PayPal Identity Token displayed.',
					'event-tickets' ),
				'size'            => 'medium',
				'default'         => '',
				'validation_type' => 'html',
			),
			'ticket-paypal-success-page'                    => array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Success page', 'event-tickets' ),
				'tooltip'         => esc_html__( "After a successful PayPal order users will be redirected to this page; use the [{$tpp_success_shortcode}] shortcode to display the order confirmation to the user in the page content.",
					'event-tickets' ),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $pages,
				'required'        => true,
			),
			'ticket-paypal-confirmation-email-sender-email' => array(
				'type'            => 'email',
				'label'           => esc_html__( 'Confirmation email sender address', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Email address PayPal tickets customers will receive confirmation from.', 'event-tickets' ),
				'size'            => 'medium',
				'default'         => $current_user->user_email,
				'validation_type' => 'email',
			),
			'ticket-paypal-confirmation-email-sender-name'  => array(
				'type'                => 'text',
				'label'               => esc_html__( 'Confirmation email sender name', 'event-tickets' ),
				'tooltip'             => esc_html__( 'Sender name of the confirmation email sent to customers when confirming a ticket purchase.', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => $current_user->user_nicename,
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			),
			'ticket-paypal-confirmation-email-subject'      => array(
				'type'                => 'text',
				'label'               => esc_html__( 'Confirmation email subject', 'event-tickets' ),
				'tooltip'             => esc_html__( 'Subject of the confirmation email sent to customers when confirming a ticket purchase.', 'event-tickets' ),
				'size'                => 'large',
				'default'             => 'You have tickets!',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			),
			'ticket-commerce-currency-code' => array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
				'tooltip'         => esc_html__( 'The currency that will be used for Tribe Commerce transactions.', 'event-tickets' ),
				'default'         => 'USD',
				'validation_type' => 'options',
				'options'         => $paypal_currency_code_options,
			),
		)
	);
}

if ( ! $is_tickets_plus_available ) {
	$plus_link = sprintf(
		'<a href="https://theeventscalendar.com/product/wordpress-event-tickets-plus/?utm_campaign=in-app&utm_medium=plugin-tickets&utm_source=post-editor" target="_blank">%s</a>',
		__( 'Check out Events Tickets Plus', 'tribe-common' )
	);
	$plus_message = sprintf(
		__( 'Looking to collect custom information for attendees, check users in via QR codes, share stock between tickets, or integrate with other commerce providers? %s!.', 'event-tickets' ),
		$plus_link
	);
	$tickets_fields['ticket-paypal-et-plus-footer'] = array(
		'type' => 'html',
		'html' => '<p class="contained">' . $plus_message . '</p>',
	);
}

$site_name = stripslashes_deep( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
$tickets_fields = array_merge(
	$tickets_fields,
	array(
		'ticket-email-heading' => array(
			'type' => 'html',
			'html' => '<h3>' . __( 'Emails', 'event-tickets' ) . '</h3>',
		),
		'ticket-email-advice'  => array(
			'type' => 'html',
			'html' => '<p>' . apply_filters(
				'tribe_tickets_settings_email_advice',
				__( 'These settings control the emails sent when an attendee RSVPs or purchases a ticket for an event.', 'event-tickets' )
			) . '</p>',
		),
		'ticket-confirmation-email-sender-email' => array(
			'type'            => 'email',
			'label'           => esc_html__( 'Sender email address', 'event-tickets' ),
			'tooltip'         => esc_html__( 'Sender email address for the email sent to attendees.', 'event-tickets' ),
			'size'            => 'medium',
			'default'         => $current_user->user_email,
			'validation_type' => 'email',
		),
		'ticket-confirmation-email-sender-name' => array(
			'type'                => 'text',
			'label'               => esc_html__( 'Sender name', 'event-tickets' ),
			'tooltip'             => esc_html__( 'Sender name for the email sent to attendees.', 'event-tickets' ),
			'size'                => 'medium',
			'default'             => $current_user->user_nicename,
			'validation_callback' => 'is_string',
			'validation_type'     => 'textarea',
		),
		'ticket-confirmation-email-subject' => array(
			'type'                => 'text',
			'label'               => esc_html__( 'Ticket email subject line', 'event-tickets' ),
			'tooltip'             => esc_html__( 'Subject line for the ticket email sent to attendees.', 'event-tickets' ),
			'size'                => 'large',
			'default'             => 'Your tickets from ' . $site_name . '!',
			'validation_callback' => 'is_string',
			'validation_type'     => 'textarea',
		),
		'rsvp-confirmation-email-subject' => array(
			'type'                => 'text',
			'label'               => esc_html__( 'RSVP email subject line', 'event-tickets' ),
			'tooltip'             => esc_html__( 'Subject line for the RSVP email sent to attendees.', 'event-tickets' ),
			'size'                => 'large',
			'default'             => 'Your RSVPs from ' . $site_name . '!',
			'validation_callback' => 'is_string',
			'validation_type'     => 'textarea',
		),
	)
);

$tickets_fields = array_merge( $tickets_fields, array(
	'tribe-form-content-end' => array(
		'type' => 'html',
		'html' => '</div>',
	),
) );

/**
 * Filters the fields to be registered in the Events > Settings > Tickets tab.
 *
 * A field definition is one suitable to be consumed by the `Tribe__Settings_Tab` class.
 *
 * @see Tribe__Settings_Tab
 * @see Tribe__Field
 *
 * @param array $tickets_fields An associative array of fields definitions to register.
 */
$tickets_fields = apply_filters( 'tribe_tickets_settings_tab_fields', $tickets_fields );

$tickets_tab = array(
	'priority' => 20,
	'fields' => $tickets_fields,
);
