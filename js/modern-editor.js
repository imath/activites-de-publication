/**
 * Post Activities editor sidebar.
 */

/* global _activitesDePublicationAdminSettings */

( function( wp ) {
	if ( 'undefined' === typeof _activitesDePublicationAdminSettings ) {
		return;
	}

	var el = wp.element.createElement, compose = wp.element.compose || wp.compose.compose, Fragment = wp.element.Fragment,
	    PanelBody = wp.components.PanelBody, PanelRow = wp.components.PanelRow, CheckboxControl = wp.components.CheckboxControl,
	    PluginSidebar = wp.editPost.PluginSidebar, PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem,
	    registerPlugin = wp.plugins.registerPlugin, postData = wp.data;

	var activityToggle = function( props ) {
		return el( CheckboxControl, {
			checked: props.checked,
			label: _activitesDePublicationAdminSettings.activateLabel,
			onChange: function( checked ) {
				props.onChangeActivityToggle( checked );
			},
			id: 'toggle-activity'
		} );
	};

	var PostActivityToggle = compose( [
		postData.withSelect( function( select ) {
			return {
				checked: select( 'core/editor' ).getEditedPostAttribute( 'meta' ).activites_de_publication
			};
		} ),
		postData.withDispatch( function( dispatch ) {
			return {
				onChangeActivityToggle: function( checked ) {
					dispatch( 'core/editor' ).editPost( {
						'activites_de_publication': checked,
						meta: { 'activites_de_publication': checked }
					} );
				}
			};
		} )
	] )( activityToggle );

	function activitesDarticleSidebar() {
		var metas = postData.select( 'core/editor' ).getEditedPostAttribute( 'meta' );

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
					el( PanelRow, {}, el( PostActivityToggle, {
						key: 'toggle',
						checked: metas.activites_de_publication ? true : false
					} ) )
				)
			)
		);
	}

	registerPlugin( 'activites-de-publication', {
		icon: 'buddicons-activity',
		render: activitesDarticleSidebar
	} );

} )( window.wp || {} );
