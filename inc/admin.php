<?php
/**
 * Post Activities admin functions.
 *
 * @package Activites_de_Publication\inc
 *
 * @since  1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the needed JavaScript for the modern editor (Gutenberg).
 *
 * @since  1.0.0
 */
function post_activities_admin_register_scripts() {
	wp_register_script(
		'activites-d-article-modern-editor',
		sprintf( '%1$smodern-editor%2$s.js', post_activities_js_url(), post_activities_min_suffix() ),
		array( 'wp-edit-post' ),
		post_activities_version(),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'post_activities_admin_register_scripts', 7 );

/**
 * Adds needed JavaScript to the loading queue.
 *
 * @since  1.0.0
 */
function post_activities_admin_enqueue_scripts() {
	$post = get_post();

	if ( ! post_activities_is_post_type_supported( $post ) ) {
		return;
	}

	wp_enqueue_script( 'activites-d-article-modern-editor' );
	wp_localize_script( 'activites-d-article-modern-editor', '_activitesDePublicationAdminSettings', array(
		'activateLabel' => __( 'Activer les activités BuddyPress', 'activites-de-publication' ),
		'moreMenuLabel' => __( 'Activités de Publication', 'activites-de-publication' ),
		'sidebarLabel'  => __( 'Conversations', 'activites-de-publication' ),
		'settingsLabel' => __( 'Réglage', 'activites-de-publication' ),
	) );
}
add_action( 'enqueue_block_editor_assets', 'post_activities_admin_enqueue_scripts' );

/**
 * Displays a back compatibility meta box for the classic editor.
 *
 * @since  1.0.0
 *
 * @param WP_Post|object $post The post object being edited.
 */
function post_activities_admin_display_metabox( $post = null ) {
	if ( empty( $post->ID ) ) {
		return;
	}

	$enabled = (int) get_post_meta( $post->ID, 'activites_de_publication', true );

	printf( '
		<p>
			<input type="checkbox" name="_activites_de_publication_meta_values[current]" id="activite-d-articles-enabled" value="1" %1$s/> <label for="activite-d-articles-enabled">%2$s</label>
			<input type="hidden" name="_activites_de_publication_meta_values[previous]" value="%3$d" />
		</p>
		',
		checked( 1, $enabled, false ),
		esc_html__( 'Activer les activités BuddyPress', 'activites-de-publication' ),
		$enabled
	);

	wp_nonce_field( 'post_activities_admin_metabox_save', 'post_activities_admin_metabox' );
}

/**
 * Adds a back compatibility meta box to the classic editor.
 *
 * @since  1.0.0
 */
function post_activities_admin_add_metabox() {
	$post = get_post();

	if ( ! post_activities_is_post_type_supported( $post ) ) {
		return;
	}

	// Add the metabox
	add_meta_box(
		'activites-de-publication',
		__( 'Conversations', 'activites-de-publication' ),
		'post_activities_admin_display_metabox',
		get_post_type( $post ),
		'side',
		'core',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}

/**
 * Saves the Activités de publication meta data edited from the classic editor.
 *
 * @since  1.0.0
 *
 * @param  integer $post_id The post ID being edited.
 * @return integer          The post ID being edited.
 */
function post_activities_admin_save_metabox( $post_id = 0 ) {
	// Bail if doing an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Bail if not a post request
	if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return $post_id;
	}

	// Bail if no meta to save
	if ( empty( $_POST['_activites_de_publication_meta_values'] ) )  {
		return $post_id;
	}

	if ( ! empty( $_POST['post_activities_admin_metabox'] ) && check_admin_referer( 'post_activities_admin_metabox_save', 'post_activities_admin_metabox' ) ) {
		if ( ! empty( $_POST['_activites_de_publication_meta_values']['current'] ) ) {
			update_post_meta( $post_id, 'activites_de_publication', true );
		} elseif ( ! empty( $_POST['_activites_de_publication_meta_values']['previous'] ) ) {
			delete_post_meta( $post_id, 'activites_de_publication' );
		}
	}

	return $post_id;
}

/**
 * Registers a back compatibility meta box for the classic editor.
 *
 * @since  1.0.0
 */
function post_activities_admin_register_metaboxes() {
	$post_types = get_post_types_by_support( 'activites_de_publication' );

	foreach ( $post_types as $post_type ) {

		// Add metabox UI
		add_action( "add_meta_boxes_{$post_type}", 'post_activities_admin_add_metabox', 10, 1 );

		// Save metabox inputs
		add_action( "save_post_{$post_type}", 'post_activities_admin_save_metabox',     10, 3 );
	}
}
add_action( 'init', 'post_activities_admin_register_metaboxes', 100 );

/**
 * Adds a delete link to the Activity Admin Edit screen.
 *
 * @todo open a ticket on https://buddypress.trac.wordpress.org
 *
 * @since 1.0.0
 */
function post_activities_admin_add_inline_script() {
	$doaction = '';
	if ( isset( $_REQUEST['action'] ) ) {
		$doaction = $_REQUEST['action'];
	}

	$aid = 0;
	if ( isset( $_REQUEST['aid'] ) ) {
		$aid = (int) $_REQUEST['aid'];
	}

	if ( 'edit' !== $doaction || ! $aid ) {
		return;
	}

	$delete_url = wp_nonce_url( add_query_arg( array(
		'page'   => 'bp-activity',
		'aid'    => $aid,
		'action' => 'delete',
	), bp_get_admin_url( 'admin.php' ) ), "spam-activity_{$aid}" );

	wp_add_inline_script( 'bp_activity_admin_js', sprintf(
		'( function( $ ) {
			$( \'#publishing-action\' ).before(
				$( \'<div></div>\' ).prop( \'id\', \'delete-action\' )
				                    .html(
										$( \'<a></a>\' ).addClass( \'submitdelete deletion confirm\' )
														.prop( \'href\', \'%1$s\' )
														.html( \'%2$s\' )
									)
			);

			$( \'#major-publishing-actions\' ).on( \'click\', \'.confirm\', function() {
				return confirm( \'%3$s\' );
			} );
		} )( jQuery );',
		str_replace( '&amp;', '&', esc_url_raw( $delete_url ) ),
		esc_html__( 'Supprimer définitivement', 'activites-de-publication' ),
		esc_html__( 'Confirmez votre demande.', 'activites-de-publication' )
	) );
}
add_action( 'bp_activity_admin_enqueue_scripts', 'post_activities_admin_add_inline_script' );

/**
 * Overrides the BuddyPress check for the comment cap in the Activity List Table.
 *
 * @since  1.0.0
 *
 * @param  boolean $can_comment Wether the user can comment the activity or not.
 * @param  array  $activity     An associative array having Activity object properties as keys.
 * @return boolean              True if the user can comment the activity.
 *                              False otherwise.
 */
function post_activities_admin_can_comment( $can_comment = false, $activity = array() ) {
	if ( isset( $activity['type'] ) && 'publication_activity' === $activity['type'] ) {
		$can_comment = false;
	}

	return $can_comment;
}
add_filter( 'bp_activity_list_table_can_comment', 'post_activities_admin_can_comment', 10, 2 );
