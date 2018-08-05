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
 * Get the plugin's BP Templates path.
 *
 * @since  1.0.0
 *
 * @return string the plugin's BP Templates path.
 */
function post_activities_bp_templates_dir() {
	return trailingslashit( post_activities()->tpl_dir) . 'buddypress';
}

/**
 * Get the plugin's BP Templates url.
 *
 * @since  1.0.0
 *
 * @return string the plugin's BP Templates url.
 */
function post_activities_bp_templates_url() {
	return trailingslashit( post_activities()->tpl_url) . 'buddypress';
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

function post_activities_is_post_type_supported( WP_Post $post ) {
	$type   = get_post_type( $post );
	$retval = post_type_supports( $type, 'activites_de_publication' );

	if ( $retval && 'page' === $type && in_array( $post->ID, bp_core_get_directory_page_ids(), true ) ) {
		$retval = false;
	}

	return $retval;
}

/**
 * Add Support for Publication activity to WordPress posts and pages.
 *
 * @since  1.0.0
 */
function post_activities_create_initial_supports() {
	add_post_type_support( 'post', 'activites_de_publication' );
	add_post_type_support( 'page', 'activites_de_publication' );
}
add_action( 'init', 'post_activities_create_initial_supports', 1 );

function post_activities_init() {
	$post_types = get_post_types_by_support( 'activites_de_publication' );

	$common_args = array(
		'type'        => 'boolean',
		'description' => __( 'Activer ou non les activités d\'articles', 'activites-d-article' ),
		'single'      => true,
		'show_in_rest'=> true,
	);

	foreach ( $post_types as $post_type ) {
		register_post_meta( $post_type, 'activites_de_publication', $common_args );
	}
}
add_action( 'init', 'post_activities_init', 50 );

function post_activities_rest_init() {
	if ( post_activities()->bp_rest_is_enabled ) {
		return;
	}

	$controller = new BP_REST_Activity_Endpoint();
	$controller->register_routes();
}
add_action( 'bp_rest_api_init', 'post_activities_rest_init' );

function post_activities_format_activity_action( $action, $activity ) {
	$user_link = bp_core_get_userlink( $activity->user_id );

	return sprintf( __( '%s a partagé une activité de publication.', 'activites-d-article' ), $user_link );
}

/**
 * Register activity action.
 *
 * @since 1.0.0
 */
function post_activities_register_activity_type() {
	bp_activity_set_action(
		buddypress()->activity->id,
		'publication_activity',
		__( 'Nouvelle activité d\'article', 'activites-d-article' ),
		'post_activities_format_activity_action',
		__( 'Activités d\'article', 'activites-d-article' ),
		array( 'activity', 'member' )
	);
}
add_action( 'bp_register_activity_actions', 'post_activities_register_activity_type' );

function post_activities_new_activity_args( $args = array() ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		if ( ! isset( $_POST['type'] ) || 'publication_activity' !== $_POST['type'] ) {
			return $args;
		}

		$postData = wp_parse_args( $_POST, array(
			'item_id'           => 0,
			'secondary_item_id' => 0,
			'hide_sitewide'     => false,
		) );

		$args = array_merge( $args, $postData, array(
			'primary_link' => get_permalink( (int) $postData['secondary_item_id'] ),
		) );
	}

	return $args;
}
add_filter( 'bp_after_activity_add_parse_args', 'post_activities_new_activity_args', 10, 1 );

/**
 * Fixes 2 BP_REST_Activity_Endpoint issues.
 *
 * 1. The `show_hidden` argument is missing in create_item().
 * 2. It should be possible to request for show_hidden in get_items().
 *
 * @param  array  $args The arguments for bp_activity_get().
 * @return array        The arguments for bp_activity_get().
 */
function post_activities_get_activity_args( $args = array() ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		if ( ! isset( $_REQUEST['type'] ) || 'publication_activity' !== $_REQUEST['type'] ) {
			return $args;
		}

		if ( isset( $_REQUEST['hide_sitewide'] ) && true === (bool) $_REQUEST['hide_sitewide'] ) {
			$args['show_hidden'] = true;
		}
	}

	return $args;
}
add_filter( 'bp_after_activity_get_parse_args', 'post_activities_get_activity_args', 10, 1 );

function post_activities_get_activity_edit_link( $id = 0 ) {
	if ( ! $id ) {
		return '';
	}

	return add_query_arg( array(
		'page'   => 'bp-activity',
		'aid'    => $id,
		'action' => 'edit',
	), bp_get_admin_url( 'admin.php' ) );
}

/**
 * The BP Rest Activity Controller only returns raw values, we need to render the content.
 *
 * @since 1.0.0
 *
 * @param  WP_REST_Response $response The BP Rest response.
 * @return WP_REST_Response           The "rendered" BP Rest response.
 */
function post_activities_prepare_bp_activity_value( WP_REST_Response $response ) {
	if ( isset( $response->data['content'] ) ) {
		add_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		$response->data['content'] = apply_filters( 'bp_get_activity_content_body', $response->data['content'] );

		remove_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		// Add needed data for the user.
		$response->data['user_name'] = bp_core_get_user_displayname( $response->data['user'] );
		$response->data['user_link'] = apply_filters( 'bp_get_activity_user_link', bp_core_get_user_domain( $response->data['user'] ) );

		// Add needed meta data
		$timestamp = strtotime( $response->data['date'] );
		$response->data['human_date'] = sprintf(
			__( '%1$s à %2$s', 'activites-d-article' ),
			date_i18n( get_option( 'date_format' ), $timestamp ),
			date_i18n( get_option( 'time_format' ), $timestamp )
		);

		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$response->data['edit_link'] = esc_url_raw( post_activities_get_activity_edit_link( $response->data['id'] ) );
		}
	}

	return $response;
}
add_filter( 'rest_prepare_buddypress_activity_value', 'post_activities_prepare_bp_activity_value', 10, 1 );

/**
 * Add the number of activities and pages into the corresponding Request headers.
 *
 * @since 1.0.0
 *
 * @param array            $activities The Result of the activity query.
 * @param WP_REST_Response $response   The BP Rest response.
 * @param WP_REST_Request  $request    The BP Rest request.
 */
function post_activities_get_bp_activities( $activities = array(), WP_REST_Response $response, WP_REST_Request $request ) {
	if ( ! isset( $activities['activities'] ) || ! isset( $activities['total'] ) ) {
		return;
	}

	$page             = $request->get_param( 'page' );
	$per_page         = $request->get_param( 'per_page' );
	$total_activities = (int) $activities['total'];

	if ( ! $page || ! $per_page ) {
		return;
	}

	$max_pages = ceil( $total_activities / (int) $per_page );

	$response->header( 'X-WP-Total', (int) $total_activities );
	$response->header( 'X-WP-TotalPages', (int) $max_pages );
}
add_action( 'rest_activity_get_items', 'post_activities_get_bp_activities', 10, 3 );

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

function post_activities_get_template_stack( $stack = array() ) {
	return array_merge( $stack, array( post_activities_bp_templates_dir() ) );
}

function post_activities_locate_bp_template_asset( $asset = '' ) {
	add_filter( 'bp_get_theme_compat_dir', 'post_activities_bp_templates_dir' );
	add_filter( 'bp_get_theme_compat_url', 'post_activities_bp_templates_url' );

	$located = bp_locate_template_asset( $asset );

	remove_filter( 'bp_get_theme_compat_dir', 'post_activities_bp_templates_dir' );
	remove_filter( 'bp_get_theme_compat_url', 'post_activities_bp_templates_url' );

	return $located;
}

function post_activities_front_enqueue_scripts() {
	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();

	if ( ! post_activities_is_post_type_supported( $post ) || true !== (bool) get_post_meta( $post->ID, 'activites_de_publication', true ) ) {
		return;
	}

	// Take care of the Post status.
	$post_status     = get_post_status( $post );
	$post_status_obj = get_post_status_object( $post_status );
	$hide_sitewide   = 0;

	if ( true === (bool) $post_status_obj->private ) {
		$hide_sitewide = 1;
	}

	// No need to use conversations in draft mode.
	if ( true === (bool) $post_status_obj->protected && false === (bool) $post_status_obj->publicly_queryable ) {
		return;
	}

	// Temporarly overrides the BuddyPress Template Stack.
	add_filter( 'bp_get_template_stack', 'post_activities_get_template_stack' );

	$min = post_activities_min_suffix();
	$css = post_activities_locate_bp_template_asset( "css/activites-de-publication{$min}.css" );

	if ( isset( $css['uri'] ) ) {
		wp_enqueue_style( 'activites-d-article-front-style', $css['uri'], array(), post_activities_version() );
	}

	wp_enqueue_script( 'activites-d-article-front-script' );
	wp_localize_script( 'activites-d-article-front-script', '_activitesDePublicationSettings', array(
		'versionString'     => 'buddypress/v1',
		'primaryID'         => get_current_blog_id(),
		'secondaryID'       => $post->ID,
		'hideSitewide'      => $hide_sitewide,
		'mustLogIn'         => sprintf(
			/* translators: %s: login URL */
			__( 'Vous devez <a href="%s">être connecté·e</a> pour afficher ou publier des conversations.', 'activites-d-article' ),
			wp_login_url( apply_filters( 'the_permalink', get_permalink( $post->ID ), $post->ID ) )
		),
		'loadingConversations' => __( 'Merci de patienter pendant le chargement des conversations.', 'activites-d-article' ),
		'noConversations'      => __( 'Aucune conversation initiée, soyez le premier à en démarrer une !', 'activites-d-article' ),
	) );

	$activity_params = array(
		'user_id'     => bp_loggedin_user_id(),
		'object'      => 'user',
		'backcompat'  => false,
		'post_nonce'  => wp_create_nonce( 'post_update', '_wpnonce_post_update' ),
	);

	if ( is_user_logged_in() && buddypress()->avatar->show_avatars ) {
		$width  = bp_core_avatar_thumb_width();
		$height = bp_core_avatar_thumb_height();
		$activity_params = array_merge( $activity_params, array(
			'avatar_url'    => bp_get_loggedin_user_avatar( array(
				'width'  => $width,
				'height' => $height,
				'html'   => false,
			) ),
			'avatar_width'  => $width,
			'avatar_height' => $height,
			'user_domain'   => bp_loggedin_user_domain(),
			'avatar_alt'    => sprintf(
				/* translators: %s = member name */
				__( 'Profile photo of %s', 'buddypress' ),
				bp_get_loggedin_user_fullname()
			),
		) );
	}

	wp_localize_script( 'bp-nouveau', 'BP_Nouveau', array(
		'objects' => array( 'activity' ),
		'nonces'  => array( 'activity' => wp_create_nonce( 'bp_nouveau_activity' ) ),
		'activity' => array(
			'params' => $activity_params,
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

	// Load the Post Form template
	require_once( $path . 'buddypress/common/js-templates/activity/form.php' );

	// Load the Entry template
	bp_get_template_part( 'common/js-templates/activity/activites-de-publication' );

	$templates = ob_get_clean();

	// Remove temporary overrides.
	remove_filter( 'the_content', 'post_activities_js_templates' );
	remove_filter( 'bp_get_template_stack', 'post_activities_get_template_stack' );

	// Append the templates to the Post content.
	return $content . $templates;
}

function post_activities_get_activity_id( $activity = null ) {
	$id = '';

	if ( empty( $activity->id ) ) {
		global $activities_template;

		if ( isset( $activities_template->activity->id ) ) {
			$id = $activities_template->activity->id;
		}
	} else {
		$id = $activity->id;
	}

	return (int) $id;
}

function post_activities_get_activity_type( $activity = null ) {
	$type = '';

	if ( empty( $activity->type ) ) {
		global $activities_template;

		if ( isset( $activities_template->activity->type ) ) {
			$type = $activities_template->activity->type;
		}
	} else {
		$type = $activity->type;
	}

	return $type;
}

function post_activities_can_delete( $can_delete = false, $activity = null ) {
	$type = post_activities_get_activity_type( $activity );

	if ( 'publication_activity' === $type ) {
		$can_delete = bp_current_user_can( 'bp_moderate' );
	}

	return $can_delete;
}
add_filter( 'bp_activity_user_can_delete', 'post_activities_can_delete', 20, 2 );

function post_activities_can_comment( $can_comment = false, $type = '' ) {
	if ( 'publication_activity' === $type ) {
		$can_comment = false;
	}

	return $can_comment;
}
add_filter( 'bp_activity_can_comment', 'post_activities_can_comment', 10, 2 );

function post_activities_get_delete_activity_url() {
	global $activities_template;

	if ( ! isset( $activities_template->activity ) ) {
		return '';
	}

	$delete_url = bp_get_activity_delete_url();

	if ( bp_is_activity_component() && is_numeric( bp_current_action() ) ) {
		$delete_url = str_replace( '&amp;', '&#038;', $delete_url );
	}

	return $delete_url;
}

function post_activities_moderate_link( $delete_link = '', $activity = null ) {
	if ( 'nouveau' === bp_get_theme_compat_id() ) {
		return $delete_link;
	}

	$id   = post_activities_get_activity_id( $activity );
	$type = post_activities_get_activity_type( $activity );

	if ( 'publication_activity' !== $type || ! $id ) {
		return $delete_link;
	}

	return str_replace( array(
		post_activities_get_delete_activity_url(),
		__( 'Delete', 'buddypress' ),
		' confirm',
		'delete-activity'
	), array(
		esc_url( post_activities_get_activity_edit_link( $id ) ),
		__( 'Modifier', 'activites-d-article' ),
		'',
		'edit-activity',
	), $delete_link );
}
add_filter( 'bp_get_activity_delete_link', 'post_activities_moderate_link', 10, 1 );

function post_activities_get_nouveau_activity_entry_buttons( &$buttons = array(), $id = 0 ) {
	if ( 'publication_activity' !== bp_get_activity_type() ) {
		return $buttons;
	}

	unset( $buttons['activity_favorite'] );

	if ( ! empty( $buttons['activity_delete'] ) && $id ) {
		$buttons['activity_delete'] = str_replace( array(
			post_activities_get_delete_activity_url(),
			' confirm',
			'delete-activity',
			'<span class="bp-screen-reader-text"></span>'
		), array(
			esc_url( post_activities_get_activity_edit_link( $id ) ),
			'',
			'edit-activity',
			__( 'Modifier', 'activites-d-article' )
		), $buttons['activity_delete'] );
	}

	return $buttons;
}
add_filter( 'bp_nouveau_return_activity_entry_buttons', 'post_activities_get_nouveau_activity_entry_buttons', 10, 2 );

function post_activities_can_favorite( $can_favorite = true, $activity = null ) {
	if ( 'nouveau' === bp_get_theme_compat_id() ) {
		return $can_favorite;
	}

	$type = post_activities_get_activity_type( $activity );

	if ( 'publication_activity' === $type ) {
		$can_favorite = false;
	}

	return $can_favorite;
}
add_filter( 'bp_activity_can_favorite', 'post_activities_can_favorite', 20, 1 );

function post_activities_get_activity_permalink( $link = '', &$activity = null ) {
	if ( isset( $activity->type ) && 'publication_activity' === $activity->type && is_buddypress() ) {
		$link = $activity->primary_link;
	}

	return $link;
}
add_filter( 'bp_activity_get_permalink', 'post_activities_get_activity_permalink', 10, 2 );
