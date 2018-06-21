<?php
/**
 * Plugin Name: Activités d'article
 * Plugin URI: https://imathi.eu/tag/activites-d-article/
 * Description: Utilisez les activités de BuddyPress pour dynamiser les conversations dans vos articles WordPress.
 * Version: 1.0.0
 * Requires at least: 4.9
 * Tested up to: 5.0
 * License: GPLv2 or later
 * Author: imath
 * Author URI: https://imathi.eu/
 * Text Domain: activites-d-article
 * Domain Path: /languages/
 * GitHub Plugin URI: https://github.com/imath/activites-d-article
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
		$this->version = '1.0.0';

		// Domain
		$this->domain = 'activites-d-article';

		// Base name
		$this->file      = __FILE__;
		$this->basename  = plugin_basename( $this->file );

		// Paths and URLs
		$this->dir        = plugin_dir_path( $this->file );
		$this->url        = plugin_dir_url ( $this->file );
		$this->lang_dir   = trailingslashit( $this->dir . 'languages' );
        $this->inc_dir    = trailingslashit( $this->dir . 'inc' );
        $this->js_url     = trailingslashit( $this->url . 'js' );
		$this->assets_url = trailingslashit( $this->url . 'assets' );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 */
	private function inc() {
		require $this->inc_dir . 'functions.php';
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
