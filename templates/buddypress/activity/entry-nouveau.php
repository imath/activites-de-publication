<?php
/**
 * Post Activities - BP Nouveau Activity Stream (Single Item)
 *
 * NB: Used to make sure the only action button is the edit one.
 *
 * @since 1.0.0
 * @version 1.0.0
 */

bp_nouveau_activity_hook( 'before', 'entry' ); ?>

<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" data-bp-activity-id="<?php bp_activity_id(); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>">

	<div class="activity-avatar item-avatar">

		<a href="<?php bp_activity_user_link(); ?>">

			<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>

		</a>

	</div>

	<div class="activity-content">

		<div class="activity-header">

			<?php bp_activity_action(); ?>

		</div>

		<?php if ( bp_nouveau_activity_has_content() ) : ?>

			<div class="activity-inner">

				<?php bp_nouveau_activity_content(); ?>

			</div>

		<?php endif; ?>

		<?php if ( bp_current_user_can( 'bp_moderate' ) ) : ?>

			<div class="activity-meta action">

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
bp_nouveau_activity_hook( 'after', 'entry' );
