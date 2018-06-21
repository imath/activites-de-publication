/**
 * Custom Sidebar
 */

( function( wp ) {
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var registerPlugin = wp.plugins.registerPlugin;

    function activitesDarticleSidebar() {
        return el(
            Fragment,
            {},
            el(
                PluginSidebarMoreMenuItem,
                {
                    target: 'activites-d-article/conversation',
                },
                'My Sidebar'
            ),
            el(
                PluginSidebar,
                {
                    name: 'activites-d-article/conversation',
                    title: 'Activités article',
                },
                'Contenu de la barre latérale.'
            )
        );
    }

    registerPlugin( 'activites-d-article', {
        icon: 'buddicons-activity',
        render: activitesDarticleSidebar,
    } );

    wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar( 'activites-d-article/conversation' );

} )( window.wp || {} );
