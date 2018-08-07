<?php
/**
 * Post Activities - BP Legacy Activity Stream (Single Item)
 *
 * NB: Used to make sure the only action button is the edit one.
 *
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * Fires before the display of an activity entry.
 *
 * @since BuddyPress 1.2.0
 */
do_action( 'bp_before_activity_entry' ); ?>

<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>">
	<div class="activity-avatar">
		<a href="<?php bp_activity_user_link(); ?>">

			<?php bp_activity_avatar(); ?>

		</a>
	</div>

	<div class="activity-content">

		<div class="activity-header">

			<?php bp_activity_action(); ?>

		</div>

		<?php if ( bp_activity_has_content() ) : ?>

			<div class="activity-inner">

				<?php bp_activity_content_body(); ?>

			</div>

		<?php endif; ?>

		<?php

		/**
		 * Fires after the display of an activity entry content.
		 *
		 * @since BuddyPress 1.2.0
		 */
		do_action( 'bp_activity_entry_content' ); ?>

		<?php if ( bp_current_user_can( 'bp_moderate' ) ) : ?>

			<div class="activity-meta">

				<?php bp_button( array(
					'id'         => 'edit-activity',
					'component'  => 'activity',
					'link_href'  => esc_url( post_activities_get_activity_edit_link( bp_get_activity_id() ) ),
					'link_text'  => __( 'Modifier', 'activites-de-publication' ),
					'block_self' => false,
				) ); ?>

			</div>

		<?php endif; ?>

	</div>

</li>

<?php

/**
 * Fires after the display of an activity entry.
 *
 * @since BuddyPress 1.2.0
 */
do_action( 'bp_after_activity_entry' );
