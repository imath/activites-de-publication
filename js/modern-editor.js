/**
 * Post Activities editor sidebar.
 */

/* global _activitesDePublicationAdminSettings */

( function( wp ) {
	if ( 'undefined' === typeof _activitesDePublicationAdminSettings ) {
		return;
	}

	var el = wp.element.createElement, compose = wp.element.compose, Fragment = wp.element.Fragment,
	    PanelBody = wp.components.PanelBody, PanelRow = wp.components.PanelRow, FormToggle = wp.components.FormToggle,
	    PluginSidebar = wp.editPost.PluginSidebar, PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem,
	    registerPlugin = wp.plugins.registerPlugin, postData = wp.data;

	function activityToggle( props ) {
		return el( FormToggle, {
			key: 'toggle',
			checked: props.checked,
			onChange: function() {
				props.onChangeActivityToggle();
			},
			id: 'toggle-activity'
		} );
	}

	var PostActivityToggle = compose( [
		postData.withSelect( function( select ) {
			return {
				checked: select( 'core/editor' ).getEditedPostAttribute( 'meta' ).activites_de_publication
			};
		} ),
		postData.withDispatch( function( dispatch ) {
			return {
				onChangeActivityToggle: function() {
					var metas = postData.select( 'core/editor' ).getEditedPostAttribute( 'meta' );

					if ( ! metas.activites_de_publication ) {
						metas.activites_de_publication = true;
					} else {
						metas.activites_de_publication = false;
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
			}, _activitesDePublicationAdminSettings.activateLabel ),
			el( PostActivityToggle, {
				key: 'toggle',
				checked: metas.activites_de_publication ? true : false
			} )
		];

		return el(
			Fragment,
			{},
			el(
				PluginSidebarMoreMenuItem,
				{
					target: 'activites-de-publication/conversation'
				},
				_activitesDePublicationAdminSettings.moreMenuLabel
			),
			el(
				PluginSidebar,
				{
					name: 'activites-de-publication/conversation',
					title: _activitesDePublicationAdminSettings.sidebarLabel
				},
				el(
					PanelBody,
					{
						title: _activitesDePublicationAdminSettings.settingsLabel
					},
					el( PanelRow, {}, children )
				)
			)
		);
	}

	registerPlugin( 'activites-de-publication', {
		icon: 'buddicons-activity',
		render: activitesDarticleSidebar
	} );

} )( window.wp || {} );
