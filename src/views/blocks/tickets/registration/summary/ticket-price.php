<?php
/**
 * Block: Tickets
 * Registration Summary Ticket Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/summary/ticket-icon.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.11.0
 *
 */
?>

<div class="tribe-tickets__registration__tickets__item__price">
	<?php echo $ticket->get_provider()->get_price_html( $ticket->ID ); ?>
</div>
