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
		'description' => __( 'Activer ou non les activités d\'articles', 'activites-d-article' ),
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
		) );

		$args = array_merge( $args, $postData, array(
			'primary_link' => get_permalink( (int) $postData['item_id'] ),
		) );
	}

	return $args;
}
add_filter( 'bp_after_activity_add_parse_args', 'post_activities_new_activity_args', 10, 1 );

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
function post_activities_prepare_buddypress_activity_value( WP_REST_Response $response ) {
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
add_filter( 'rest_prepare_buddypress_activity_value', 'post_activities_prepare_buddypress_activity_value', 10, 1 );

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
		'versionString'     => 'buddypress/v1',
		'primaryID'         => get_current_blog_id(),
		'secondaryID'       => $post->ID,
		// Use the comment_form() fields to be as close to the theme output as possible.
		'commentFormFields' => apply_filters( 'comment_form_defaults', array(
			'must_log_in' => '<p class="must-log-in">' . sprintf(
			/* translators: %s: login URL */
			__( 'Vous devez <a href="%s">être connecté·e</a> pour afficher ou publier des activités.', 'activites-d-article' ),
			wp_login_url( apply_filters( 'the_permalink', get_permalink( $post->ID ), $post->ID ) )
		) . '</p>',
		) ),
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
	require_once( $path . 'buddypress/common/js-templates/activity/form.php' );
	?>
	<script type="text/html" id="tmpl-activites-de-publication">
		<article class="comment-body">
			<footer class="comment-meta">
				<div class="comment-author vcard">
					<img alt="" src="{{data.user_avatar.full}}" class="avatar avatar-100 photo" height="100" width="100">
					<b class="fn"><a href="{{data.user_link}}" rel="nofollow" class="url">{{data.user_name}}</a></b>
				</div>
				<div class="comment-metadata">
					<a href="{{data.link}}">
						<time datetime="{{data.date}}">{{{data.human_date}}}</time>
					</a>
					<# if ( data.edit_link ) { #>
						<span class="edit-link">
							<a class="comment-edit-link" href="{{data.edit_link}}"><?php esc_html_e( 'Modifier', 'activites-d-article' ); ?></a>
						</span>
					<# } #>
				</div>
			</footer>
			<div class="comment-content">
				{{{data.content}}}
			</div>
		</article>
	</script>
	<?php
	$templates = ob_get_clean();

	remove_filter( 'the_content', 'post_activities_js_templates' );
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
		bp_get_activity_delete_url(),
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
			bp_get_activity_delete_url(),
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
