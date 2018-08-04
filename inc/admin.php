<?php
/**
 * Post Activities admin functions.
 *
 * @package Activites_d_article\inc
 *
 * @since  1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

function post_activities_admin_enqueue_scripts() {
	$post = get_post();

	if ( ! post_activities_is_post_type_supported( $post ) ) {
		return;
	}

	wp_enqueue_script( 'activites-d-article-modern-editor' );
}
add_action( 'enqueue_block_editor_assets', 'post_activities_admin_enqueue_scripts' );

function post_activities_admin_display_metabox( $post = null ) {
	if ( empty( $post->ID ) ) {
		return;
	}

	$enabled = (int) get_post_meta( $post->ID, 'activite_d_articles', true );

	printf( '
		<p>
			<input type="checkbox" name="_activite_d_articles_meta_values[current]" id="activite-d-articles-enabled" value="1" %2$s/> <label for="activite-d-articles-enabled">%3$s</label>
			<input type="hidden" name="_activite_d_articles_meta_values[previous]" value="%4$d" />
		</p>
		',
		esc_html__( 'Activités d\'article', 'activite-d-article' ),
		checked( 1, $enabled, false ),
		esc_html__( 'Activer les activités BuddyPress', 'activite-d-article' ),
		$enabled
	);

	wp_nonce_field( 'post_activities_admin_metabox_save', 'post_activities_admin_metabox' );
}

function post_activities_admin_add_metabox() {
	$post = get_post();

	if ( ! post_activities_is_post_type_supported( $post ) ) {
		return;
	}

	// Add the metabox
	add_meta_box(
		'activite-d-article',
		__( 'Conversations', 'activite-d-article' ),
		'post_activities_admin_display_metabox',
		get_post_type( $post ),
		'side',
		'core',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}

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
	if ( empty( $_POST['_activite_d_articles_meta_values'] ) )  {
		return $post_id;
	}

	if ( ! empty( $_POST['post_activities_admin_metabox'] ) && check_admin_referer( 'post_activities_admin_metabox_save', 'post_activities_admin_metabox' ) ) {
		if ( ! empty( $_POST['_activite_d_articles_meta_values']['current'] ) ) {
			update_post_meta( $post_id, 'activite_d_articles', true );
		} elseif ( ! empty( $_POST['_activite_d_articles_meta_values']['previous'] ) ) {
			delete_post_meta( $post_id, 'activite_d_articles' );
		}
	}

	return $post_id;
}

function post_activities_admin_register_metaboxes() {
	$post_types = get_post_types_by_support( 'activites_de_publication' );

	foreach ( $post_types as $post_type ) {
		// Add metabox UI
		add_action( "add_meta_boxes_{$post_type}", 'post_activities_admin_add_metabox', 10, 1 );

		// Save metabox inputs
		add_action( "save_post_{$post_type}", 'post_activities_admin_save_metabox',     10, 3 );
	}
}
add_action( 'bp_loaded', 'post_activities_admin_register_metaboxes' );
