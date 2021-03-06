<?php
/**
 * Plugin Name: Activités de Publication
 * Plugin URI: https://imathi.eu/tag/activites-de-publication/
 * Description: Utilisez les activités de BuddyPress pour dynamiser les conversations de vos publications WordPress.
 * Version: 2.0.0
 * Requires at least: 5.0
 * Tested up to: 5.2
 * License: GPLv2 or later
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: activites-de-publication
 * Domain Path: /languages/
 * GitHub Plugin URI: https://github.com/imath/activites-de-publication
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Post_Activities' ) ) :

/**
 * Main Plugin Class
 *
 * @since  1.0.0
 */
final class Post_Activities {
	/**
	 * Plugin's main instance
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->globals();
		$this->inc();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Setups plugin's globals
	 *
	 * @since 1.0.0
	 */
	private function globals() {
		// Version
		$this->version = '2.0.0';

		// Domain
		$this->domain = 'activites-de-publication';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Paths and URLs
		$this->dir        = plugin_dir_path( $this->file );
		$this->url        = plugin_dir_url ( $this->file );
		$this->lang_dir   = trailingslashit( $this->dir . 'languages' );
		$this->inc_dir    = trailingslashit( $this->dir . 'inc' );
		$this->js_url     = trailingslashit( $this->url . 'js' );
		$this->tpl_dir    = trailingslashit( $this->dir . 'templates' );
		$this->tpl_url    = trailingslashit( $this->url . 'templates' );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		// This plugin requires WordPress 5.0.0 & BuddyPress 5.0.0.
		if ( ! bp_is_active( 'activity' ) || ! function_exists( 'render_block' ) || ! function_exists( 'bp_rest_api_is_available' ) ) {
			_doing_it_wrong( 'post_activities()', __( 'Cette extension nécessite à minima WordPress 5.0.0, BuddyPress 5.0.0 et requiert que le composant des activités soit actif.', 'activites-de-publication' ), '1.0.0' );
			return;
		}

		require $this->inc_dir . 'functions.php';

		if ( is_admin() ) {
			require $this->inc_dir . 'admin.php';
		}
	}
}

endif;

/**
 * Boot the plugin.
 *
 * @since 1.0.0
 */
function post_activities() {
	return Post_Activities::start();
}
add_action( 'bp_include', 'post_activities', 9 );
