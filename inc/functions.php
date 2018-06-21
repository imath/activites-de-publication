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
    //
}
add_action( 'bp_init', 'post_activities_init' );

function post_activities_register_scripts() {
    wp_register_script(
		'activites-d-article-modern-editor',
		sprintf( '%1$smodern-editor%2$s.js', post_activities_js_url(), post_activities_min_suffix() ),
		array( 'wp-edit-post' ),
		post_activities_version(),
		true
    );
}
add_action( 'admin_enqueue_scripts', 'post_activities_register_scripts', 7 );

function post_activities_enqueue_scripts() {
    wp_enqueue_script( 'activites-d-article-modern-editor' );
}
add_action( 'enqueue_block_editor_assets', 'post_activities_enqueue_scripts' );
