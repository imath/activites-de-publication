/**
 * Activités de publication front script.
 *
 * @todo {
 *  - Be consistent between post status & hide sitewide,
 *  - Add an exclude option with the just posted activities,
 *  - Make sure the mentions script is loaded and works.
 *  - Check why it's not possible to remove an activity from the single Activity Edit Adminscreen
 *  - Improve inline comments,
 *  - Finish the renaming from activités d'article to activités de publication.
 * }
 */

( function( $, _, bp, wp ) {

	if ( 'undefined' === typeof _activitesDePublicationSettings ) {
		return;
	}

	var postForm = bp.Views.PostForm, postContainer = $( '#tmpl-activity-post-form-buttons' ).parent(),
	    postFormAvatar = bp.Views.FormAvatar, containerClass = 'no-nav';

	// Container for the Nav and Activity post form (comments allowed).
	if ( $( '#comments' ).length ) {
		containerClass = 'hide';

		$( '#comments' ).before( $( '<div></div>' ).prop( 'id', 'activites-de-publication-nav' ) );
		$( '#activites-de-publication-nav' ).after(
			$( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' )
			                  .addClass( containerClass )
		);

	// Container for the Activity post form (comments not allowed).
	} else {
		$( postContainer ).after(
			$( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' )
			                  .addClass( containerClass )
		);
	}

	// Container for the list of Activities for this Post.
	$( '#bp-nouveau-activity-form' ).after(
		$( '<div></div>' ).prop( 'id', 'activites-de-publication-list' )
		                  .addClass( containerClass )
	);

	/**
	 * Model for the Activité de publication item.
	 */
	bp.Models.activite = Backbone.Model.extend( {
		defaults: {
			id      : 0,
			user    : 0,
			content : '',
			type    : ''
		},
		options : {
			path: _activitesDePublicationSettings.versionString + '/activity/',
			type: 'POST',
			data: {},
			dataType: 'json'
		},

		sync: function( method, model, options ) {
			options  = options || {};
			options.context = this;

			_.extend( options, this.options );
			_.extend( options.data, model.attributes );

			if ( 'create' === method || 'update' === method ) {
				return wp.apiRequest( options );
			}
		}
	} );

	/**
	 * Collection for the Activités de publication items.
	 */
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

		sync: function( method, collection, options ) {
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

			var self = this, meta = {},
			    activite = new bp.Models.activite();

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

			// Save the activity
			activite.save(
				_.extend( this.model.attributes, {
					type: 'publication_activity',
					'item_id' : _activitesDePublicationSettings.primaryID,
					'secondary_item_id' : _activitesDePublicationSettings.secondaryID,
					user: this.model.get( 'user_id' ),
				} ), {
					success: function( model, response ) {
						// Get the first activity and add it to the collection.
						var published = _.extend( _.first( response ), { at: 0 } );
						bp.ActivitesDePublications.activites.add( published );

						// Reset the form
						self.resetForm();
					},
					error: function( model, response ) {
						self.model.set( 'errors', { type: 'error', value: response.responseJSON.message } );
					}
				}
			);
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

	bp.Views.olderActivites = bp.View.extend( {
		tagName  : 'li',
		className: 'load-more',
		template : bp.template( 'plus-d-activites-de-publication' )
	} );

	bp.Views.navToggle = bp.View.extend( {
		tagName  : 'ul',
		template : bp.template( 'activites-de-publication-nav' ),

		events: {
			'click .nav-item a'  : 'toggleNav',
		},

		toggleNav: function( event ) {
			event.preventDefault();

			this.$el.find( '.nav-item' ).removeClass( 'current' );
			$( event.currentTarget ).parent().addClass( 'current' );

			if ( 'conversations' === $( event.currentTarget ).data( 'type' ) ) {
				$( '#bp-nouveau-activity-form' ).removeClass( 'hide' );
				$( '#activites-de-publication-list' ).removeClass( 'hide' );
				$( '#comments' ).hide();
			} else {
				$( '#bp-nouveau-activity-form' ).addClass( 'hide' );
				$( '#activites-de-publication-list' ).addClass( 'hide' );
				$( '#comments' ).show();
			}
		}
	} );

	bp.Views.Activites = bp.View.extend( {
		tagName  : 'ol',
		id       : 'activites-liste',
		className: 'comment-list',

		events: {
			'click .load-more a'  : 'fetchMoreActivities',
		},

		initialize: function() {
			this.attachFeedback();

			this.collection.on( 'add', this.addActiviteView, this );
			this.collection.on( 'sync', this.detachFeedback, this );
		},

		addActiviteView: function( activite ) {
			var options = {};

			if ( ! _.isUndefined( activite.get( 'at' ) ) ) {
				options.at = activite.get( 'at' );
				activite.unset( 'at', { silent: true } );
			}

			this.removeInfos();
			this.views.add( new bp.Views.Activite( { model: activite } ), options );
		},

		attachFeedback: function( message ) {
			message = message || _activitesDePublicationSettings.loadingConversations;

			this.views.add( new bp.Views.activityFeedback( {
				tagName: 'li',
				value: message,
				type: 'info'
			} ) );
		},

		removeInfos: function() {
			_.each( this.views._views[''], function( view ) {
				if ( view.type && 'info' === view.type ) {
					view.remove();
				}
			} );
		},

		detachFeedback: function( collection ) {
			this.removeInfos();

			if ( collection.currentPage && collection.totalPages && collection.currentPage < collection.totalPages ) {
				this.views.add( new bp.Views.olderActivites( { model: new Backbone.Model( {
					'nextPage': collection.currentPage + 1
				} ) } ) );
			}

			if ( 0 === collection.length ) {
				this.attachFeedback( _activitesDePublicationSettings.noConversations );
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
				this.attachFeedback();

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

	if ( $( '#activites-de-publication-nav' ).length ) {
		var navToggle = new bp.Views.navToggle().inject( '#activites-de-publication-nav' );
	}

	// Inject the Activités main view.
	var activitesView = new bp.Views.Activites( {
		collection: bp.ActivitesDePublications.activites
	} ).inject( '#activites-de-publication-list' );

} )( jQuery, _, window.bp || {}, window.wp || {} );
