/**
 * Custom Sidebar
 */

( function( $, _, bp, wp ) {

	if ( 'undefined' === typeof _activitesDePublicationSettings ) {
		return;
	}

	var postForm = bp.Views.PostForm, postContainer = $( '#tmpl-activity-post-form-buttons' ).parent(),
	    postFormAvatar = bp.Views.FormAvatar;

	// Container for the Nav and Activity post form (comments allowed).
	if ( $( '#comments' ).length ) {
		$( '#comments' ).before( $( '<div></div>' ).prop( 'id', 'activites-de-publication-nav' ) );
		$( '#activites-de-publication-nav' ).after( $( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' ) );

	// Container for the Activity post form (comments not allowed).
	} else {
		$( postContainer ).after( $( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' ) );
	}

	// Container for the list of Activities for this Post.
	$( '#bp-nouveau-activity-form' ).after( $( '<div></div>' ).prop( 'id', 'activites-de-publication-list' ) );

	/**
	 * Activity Post Form overrides.
	 */
	bp.Views.PostForm = postForm.extend( {
		postUpdate: function( event ) {
			if ( event ) {
				if ( 'keydown' === event.type && ( 13 !== event.keyCode || ! event.ctrlKey ) ) {
					return event;
				}

				event.preventDefault();
			}

			var self = this,
			    meta = {};

			// Set the content and meta
			_.each( this.$el.serializeArray(), function( pair ) {
				pair.name = pair.name.replace( '[]', '' );
				if ( 'whats-new' === pair.name ) {
					self.model.set( 'content', pair.value );
				} else if ( -1 === _.indexOf( ['aw-whats-new-submit', 'whats-new-post-in'], pair.name ) ) {
					if ( _.isUndefined( meta[ pair.name ] ) ) {
						meta[ pair.name ] = pair.value;
					} else {
						if ( ! _.isArray( meta[ pair.name ] ) ) {
							meta[ pair.name ] = [ meta[ pair.name ] ];
						}

						meta[ pair.name ].push( pair.value );
					}
				}
			} );

			// Silently add meta
			this.model.set( meta, { silent: true } );

			// @todo this should be in the model.
			wp.apiRequest( {
				path: _activitesDePublicationSettings.versionString + '/activity/',
				type: 'POST',
				data: _.extend( self.model.attributes, {
					type : 'publication_activity',
					'item_id' : _activitesDePublicationSettings.primaryID,
					'secondary_item_id' : _activitesDePublicationSettings.secondaryID,
				} ),
				dataType: 'json'
			} ).done( function( response ) {
				// Get the first activity and add it to the collection.
				if ( _.isArray( response ) && response.length > 0 ) {
					var published = _.extend( _.first( response ), { at: 0} );
					bp.ActivitesDePublications.activites.add( published );
				}

				// Reset the form
				self.resetForm();

			} ).fail( function( response ) {
				self.model.set( 'errors', { type: 'error', value: response.responseJSON.message } );
			} );
		}
	} );

	/**
	 * Activity Post Form Avatar overrides.
	 */
	bp.Views.FormAvatar = postFormAvatar.extend( {
		initialize: function() {
			// Use Parent initializer.
			postFormAvatar.prototype.initialize.apply( this, arguments );

			if ( this.model.get( 'display_avatar' ) ) {
				this.el.className = 'comment-author vcard';
			}
		}
	} );

	bp.Models.activite = Backbone.Model.extend( {
		defaults: {
			id      : 0,
			user    : 0,
			content : '',
			type    : ''
		},
	} );

	bp.Collections.activites = Backbone.Collection.extend( {
		model: bp.Models.activite,
		options : {
			path: _activitesDePublicationSettings.versionString + '/activity/',
			type: 'GET',
			data: {
				type : 'publication_activity',
				'primary_id' : _activitesDePublicationSettings.primaryID,
				'secondary_id' : _activitesDePublicationSettings.secondaryID,
			},
			dataType: 'json'
		},

		sync: function( method, model, options ) {
			options  = options || {};
			options.context = this;
			var data = options.data || {};

			_.extend( options, this.options );
			_.extend( options.data, data );

			if ( 'read' === method ) {
				var self = this, success = options.success;
				options.success = function( data, textStatus, request ) {
					if ( ! _.isUndefined( request ) ) {
						self.totalPages      = parseInt( request.getResponseHeader( 'X-WP-TotalPages' ), 10 );
						self.totalActivities = parseInt( request.getResponseHeader( 'X-WP-Total' ), 10 );
					}

					self.currentPage = options.data.page || 1;

					if ( success ) {
						return success.apply( this, arguments );
					}
				};
				return wp.apiRequest( options );
			}
		}
	} );

	bp.Views.olderActivites = bp.View.extend( {
		tagName  : 'li',
		className: 'load-more',
		template : bp.template( 'plus-d-activites-de-publication' )
	} );

	bp.Views.Activites = bp.View.extend( {
		tagName  : 'ol',
		id       : 'activites-liste',
		className: 'comment-list',

		events: {
			'click .load-more a'  : 'fetchMoreActivities',
		},

		initialize: function() {
			this.attachLoader();

			this.collection.on( 'add', this.addActiviteView, this );
			this.collection.on( 'sync', this.detachLoader, this );
		},

		addActiviteView: function( activite ) {
			var options = {};

			if ( ! _.isUndefined( activite.get( 'at' ) ) ) {
				options.at = activite.get( 'at' );
				activite.unset( 'at', { silent: true } );
			}

			this.views.add( new bp.Views.Activite( { model: activite } ), options );
		},

		attachLoader: function() {
			this.views.add( new bp.Views.activityFeedback( {
				tagName: 'li',
				value: _activitesDePublicationSettings.loadingConversations,
				type: 'info'
			} ) );
		},

		detachLoader: function( collection ) {
			_.each( this.views._views[''], function( view ) {
				if ( view.type && 'info' === view.type ) {
					view.remove();
				}
			} );

			if ( collection.currentPage && collection.totalPages && collection.currentPage < collection.totalPages ) {
				this.views.add( new bp.Views.olderActivites( { model: new Backbone.Model( {
					'nextPage': collection.currentPage + 1
				} ) } ) );
			}
		},

		fetchMoreActivities: function( event ) {
			event.preventDefault();

			var nextPage = $( event.currentTarget ).data( 'next-page' );

			_.each( this.views._views[''], function( view ) {
				if ( view.model.get( 'nextPage' ) ) {
					view.remove();
				}
			} );

			if ( nextPage ) {
				this.attachLoader();

				this.collection.fetch( {
					data: {
						page: nextPage,
						per_page: 2
					},
				} );
			}
		}
	} );

	bp.Views.Activite = bp.View.extend( {
		tagName  : 'li',
		template : bp.template( 'activites-de-publication' ),
		className: 'comment depth-1'
	} );

	// Globalize the Collection.
	bp.ActivitesDePublications = {
		activites: new bp.Collections.activites()
	}

	// Fetch the activities.
	bp.ActivitesDePublications.activites.fetch( {
		data: {
			page: 1,
			per_page: 2
		},
		success: function() {
			bp.Nouveau.Activity.postForm.start();
		},
		error: function( collection, response ) {
			if ( response.responseJSON && 'rest_authorization_required' === response.responseJSON.code ) {
				// Inject the login feedback.
				var feedback = new bp.Views.activityFeedback( {
					value: _activitesDePublicationSettings.commentFormFields.must_log_in,
					type: 'info'
				} ).inject( '#activites-de-publication-list' );
			}
		}
	} );

	// Inject the Activités main view.
	var activitesView = new bp.Views.Activites( {
		collection: bp.ActivitesDePublications.activites
	} ).inject( '#activites-de-publication-list' );

} )( jQuery, _, window.bp || {}, window.wp || {} );
