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

function post_activities_init() {
	register_meta(
		'post',
		'activite_d_articles',
		array(
			'type'        => 'boolean',
			'description' => 'Activer ou non les activités d\'articles',
			'single'      => true,
			'show_in_rest'=> true,
		)
	);
}
add_action( 'bp_init', 'post_activities_init' );

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

function post_activities_editor_enqueue_scripts() {
	wp_enqueue_script( 'activites-d-article-modern-editor' );
}
add_action( 'enqueue_block_editor_assets', 'post_activities_editor_enqueue_scripts' );

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
		array( 'bp-nouveau-activity-post-form' ),
		post_activities_version(),
		true
	);
}
add_action( 'bp_enqueue_scripts', 'post_activities_front_register_scripts', 4 );

function post_activities_front_enqueue_scripts() {
	if ( ! is_single() || true !== (bool) get_post_meta( get_the_ID(), 'activite_d_articles', true ) ) {
		return;
	}

	wp_enqueue_script( 'activites-d-article-front-script' );
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
