<?php
/**
 * Post Activities functions.
 *
 * @package Activites_d_article\inc
 *
 * @since  1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get plugin's version.
 *
 * @since  1.0.0
 *
 * @return string the plugin's version.
 */
function post_activities_version() {
	return post_activities()->version;
}

/**
 * Get the plugin's JS Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's JS Url.
 */
function post_activities_js_url() {
	return post_activities()->js_url;
}

/**
 * Get the plugin's Assets Url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's Assets Url.
 */
function post_activities_assets_url() {
	return post_activities()->assets_url;
}

/**
 * Get the JS minified suffix.
 *
 * @since  1.0.0
 *
 * @return string the JS minified suffix.
 */
function post_activities_min_suffix() {
	$min = '.min';

	if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG )  {
		$min = '';
	}

	/**
	 * Filter here to edit the minified suffix.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $min The minified suffix.
	 */
	return apply_filters( 'post_activities_min_suffix', $min );
}

function post_activities_supported_post_types() {
	return apply_filters( 'post_activities_supported_post_types', array(
		'post',
		'page',
	) );
}

function post_activities_is_post_type_supported( WP_Post $post ) {
	$retval = false;
	$type   = get_post_type( $post );

	if ( in_array( $type, post_activities_supported_post_types(), true ) ) {
		$retval = true;
	}

	if ( 'page' === $type && in_array( $post->ID, bp_core_get_directory_page_ids(), true ) ) {
		$retval = false;
	}

	return $retval;
}

function post_activities_init() {
	$supported_post_types = post_activities_supported_post_types();

	$common_args = array(
		'type'        => 'boolean',
		'description' => __( 'Activer ou non les activités d\'articles', 'activite-d-articles' ),
		'single'      => true,
		'show_in_rest'=> true,
	);

	foreach ( $supported_post_types as $post_type ) {
		register_post_meta( $post_type, 'activite_d_articles', $common_args );
	}
}
add_action( 'bp_init', 'post_activities_init' );

function post_activities_rest_init() {
	if ( post_activities()->bp_rest_is_enabled ) {
		return;
	}

	$controller = new BP_REST_Activity_Endpoint();
	$controller->register_routes();
}
add_action( 'bp_rest_api_init', 'post_activities_rest_init' );

/**
 * Register activity action.
 *
 * @since 1.0.0
 */
function post_activities_register_activity_type() {
	bp_activity_set_action(
		buddypress()->activity->id,
		'publication_activity',
		__( 'Nouvelle activité d\'article', 'activite-d-articles' ),
		'bp_blogs_format_activity_action_new_blog',
		__( 'Activités d\'article', 'activite-d-articles' ),
		array( 'activity', 'member' )
	);
}
add_action( 'bp_register_activity_actions', 'post_activities_register_activity_type' );

function post_activities_front_register_scripts() {
	if ( ! isset( wp_scripts()->registered['bp-nouveau-activity-post-form'] ) ) {
		$js_base_url = trailingslashit( buddypress()->theme_compat->packages['nouveau']->__get( 'url' ) );

		foreach ( array(
			'bp-nouveau'                    => bp_core_get_js_dependencies(),
			'bp-nouveau-activity'           => array( 'bp-nouveau' ),
			'bp-nouveau-activity-post-form' => array( 'bp-nouveau', 'bp-nouveau-activity', 'json2', 'wp-backbone' ) ) as $handle => $deps ) {
			$filename = 'buddypress-nouveau';

			if ( 'bp-nouveau' !== $handle ) {
				$filename = str_replace( 'bp-nouveau', 'buddypress', $handle );
			}

			wp_register_script(
				$handle,
				sprintf( '%1$sjs/%2$s%3$s.js', $js_base_url, $filename, post_activities_min_suffix() ),
				$deps,
				post_activities_version(),
				true
			);
		}
	}

	wp_register_script(
		'activites-d-article-front-script',
		sprintf( '%1$sfront%2$s.js', post_activities_js_url(), post_activities_min_suffix() ),
		array( 'bp-nouveau-activity-post-form', 'wp-api-request' ),
		post_activities_version(),
		true
	);
}
add_action( 'bp_enqueue_scripts', 'post_activities_front_register_scripts', 4 );

function post_activities_front_enqueue_scripts() {
	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();

	if ( ! post_activities_is_post_type_supported( $post ) || true !== (bool) get_post_meta( $post->ID, 'activite_d_articles', true ) ) {
		return;
	}

	wp_enqueue_script( 'activites-d-article-front-script' );
	wp_localize_script( 'activites-d-article-front-script', '_activitesDePublicationSettings', array(
		'versionString' => 'buddypress/v1',
		'primaryID'     => get_current_blog_id(),
		'secondaryID'   => $post->ID,
	) );

	wp_localize_script( 'bp-nouveau', 'BP_Nouveau', array(
		'objects' => array( 'activity' ),
		'nonces'  => array( 'activity' => wp_create_nonce( 'bp_nouveau_activity' ) ),
		'activity' => array(
			'params' => array(
				'user_id'     => bp_loggedin_user_id(),
				'object'      => 'user',
				'backcompat'  => false,
				'post_nonce'  => wp_create_nonce( 'post_update', '_wpnonce_post_update' ),
			),
			'strings' => array(
				'whatsnewPlaceholder' => sprintf( __( "What's new, %s?", 'buddypress' ), bp_get_user_firstname( bp_get_loggedin_user_fullname() ) ),
				'whatsnewLabel'       => __( 'Post what\'s new', 'buddypress' ),
				'whatsnewpostinLabel' => __( 'Post in', 'buddypress' ),
				'postUpdateButton'    => __( 'Post Update', 'buddypress' ),
				'cancelButton'        => __( 'Cancel', 'buddypress' ),
			)
		),
	) );

	add_filter( 'the_content', 'post_activities_js_templates' );
}
add_action( 'bp_enqueue_scripts', 'post_activities_front_enqueue_scripts', 14 );

function post_activities_js_templates( $content = '' ) {
	$path = trailingslashit( buddypress()->theme_compat->packages['nouveau']->__get( 'dir' ) );

	if ( ! function_exists( 'bp_nouveau_activity_hook' ) ) {
		require_once( $path . 'includes/template-tags.php' );
		require_once( $path . 'includes/activity/template-tags.php' );
	}

	ob_start();
	require_once( $path . 'buddypress/common/js-templates/activity/form.php' );
	$templates = ob_get_clean();

	remove_filter( 'the_content', 'post_activities_js_templates' );
	return $content . $templates;
}
