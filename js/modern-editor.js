/**
 * Post Activities editor sidebar.
 */

( function( wp ) {
	var el = wp.element.createElement, compose = wp.element.compose, Fragment = wp.element.Fragment,
	    PanelBody = wp.components.PanelBody, PanelRow = wp.components.PanelRow, FormToggle = wp.components.FormToggle,
	    PluginSidebar = wp.editPost.PluginSidebar, PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem,
	    registerPlugin = wp.plugins.registerPlugin, postData = wp.data;

	function activityToggle( props ) {
		return el( FormToggle, {
			key: 'toggle',
			checked: props.checked,
			onChange: function( event ) {
				props.onChangeActivityToggle();
			},
			id: 'toggle-activity'
		} );
	}

	var PostActivityToggle = compose( [
		postData.withSelect( function( select ) {
			return {
				checked: select( 'core/editor' ).getEditedPostAttribute( 'meta' ).activite_d_articles
			};
		} ),
		postData.withDispatch( function( dispatch ) {
			return {
				onChangeActivityToggle: function() {
					var metas = postData.select( 'core/editor' ).getEditedPostAttribute( 'meta' );

					if ( ! metas.activite_d_articles ) {
						metas.activite_d_articles = true;
					} else {
						metas.activite_d_articles = false;
					}

					dispatch( 'core/editor' ).editPost( { meta: metas } );
				}
			};
		} )
	] )( activityToggle );

	function activitesDarticleSidebar() {
		var metas = postData.select( 'core/editor' ).getEditedPostAttribute( 'meta' );

		var children = [
			el( 'label', {
				key: 'label',
				htmlFor: 'toggle-activity'
			}, 'Activer les activités BuddyPress' ),
			el( PostActivityToggle, {
				key: 'toggle',
				checked: metas.activite_d_articles ? true : false
			} )
		];

		return el(
			Fragment,
			{},
			el(
				PluginSidebarMoreMenuItem,
				{
					target: 'activites-d-article/conversation',
				},
				'Activités d\'article'
			),
			el(
				PluginSidebar,
				{
					name: 'activites-d-article/conversation',
					title: 'Activités d\'article',
				},
				el(
					PanelBody,
					{
						title: 'Conversations'
					},
					el( PanelRow, {}, children )
				)
			)
		);
	}

	registerPlugin( 'activites-d-article', {
		icon: 'buddicons-activity',
		render: activitesDarticleSidebar,
	} );

} )( window.wp || {} );
