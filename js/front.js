/**
 * Activités de publication front script.
 */

/* global _activitesDePublicationSettings, BP_Nouveau */

( function( $, _, bp, wp ) {

	if ( 'undefined' === typeof _activitesDePublicationSettings ) {
		return;
	}

	var postForm = bp.Views.PostForm, postContainer = $( '#tmpl-activity-post-form-buttons' ).parent(),
	    postFormAvatar = bp.Views.FormAvatar, postFormTextarea = bp.Views.WhatsNew, containerClass = 'no-nav';

	// Container for the Nav and Activity post form (comments allowed).
	if ( $( '#comments' ).length ) {
		containerClass = 'hide';

		$( '#comments' ).before( $( '<div></div>' ).prop( 'id', 'activites-de-publication-nav' ) );
		$( '#activites-de-publication-nav' ).after(
			$( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' )
			                  .addClass( containerClass + ' comments-area' )
		);

	// Container for the Activity post form (comments not allowed).
	} else {
		$( postContainer ).after(
			$( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' )
			                  .addClass( containerClass + ' comments-area' )
		);
	}

	// Container for the list of Activités de publication for this Post.
	$( '#bp-nouveau-activity-form' ).after(
		$( '<div></div>' ).prop( 'id', 'activites-de-publication-list' )
		                  .addClass( containerClass + ' comments-area' )
	);

	/**
	 * Model for the Activités de publication item.
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

			if ( 1 === parseInt( _activitesDePublicationSettings.hideSitewide, 10 ) ) {
				options.data.hidden = 1;
			}

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
				'secondary_id' : _activitesDePublicationSettings.secondaryID
			},
			dataType: 'json'
		},

		sync: function( method, collection, options ) {
			options  = options || {};
			options.context = this;
			var data = options.data || {};

			_.extend( options, this.options );
			_.extend( options.data, data );

			if ( 1 === parseInt( _activitesDePublicationSettings.hideSitewide, 10 ) ) {
				options.data.hidden = 1;
			}

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
		initialize: function() {
			// Use Parent initializer.
			postForm.prototype.initialize.apply( this, arguments );

			this.on( 'ready', this.bpMentionsRefresh, this );
		},

		bpMentionsRefresh: function() {
			if ( 'undefined' !== typeof bp_mentions || 'undefined' !== typeof bp.mentions ) {
				$( '.bp-suggestions' ).bp_mentions( bp.mentions.users );
			}
		},

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
					'prime_association' : _activitesDePublicationSettings.primaryID,
					'secondary_association' : _activitesDePublicationSettings.secondaryID,
					user: this.model.get( 'user_id' )
				} ), {
					success: function( model, response ) {
						// Get the first activity and add it to the collection.
						var published = _.extend( _.first( response ), { at: 0 } );
						bp.ActivitesDePublications.activites.add( published );

						// Make sure the paginate results are kept consistent.
						if ( _.isUndefined( bp.ActivitesDePublications.activites.options.data.exclude ) ) {
							bp.ActivitesDePublications.activites.options.data.exclude = [];
						}

						bp.ActivitesDePublications.activites.options.data.exclude.push( published.id );

						// Reset the form
						self.resetForm();
					},
					error: function( model, response ) {
						if ( ! _.isUndefined( _activitesDePublicationSettings.errors[ response.responseJSON.code ] ) ) {
							self.model.set( 'errors', { type: 'error', value: _activitesDePublicationSettings.errors[ response.responseJSON.code ] } );
						} else {
							self.model.set( 'errors', { type: 'error', value: response.responseJSON.message } );
						}
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

	/**
	 * Activity Post Form Textarea overrides.
	 */
	bp.Views.WhatsNew = postFormTextarea.extend( {
		initialize: function() {
			this.el.placeholder = _activitesDePublicationSettings.textareaPlaceholder;

			// Use Parent initializer.
			postFormTextarea.prototype.initialize.apply( this, arguments );
		}
	} );

	/**
	 * Activités de publication Loop's load more link.
	 */
	bp.Views.olderActivites = bp.View.extend( {
		tagName  : 'li',
		className: 'load-more',
		template : bp.template( 'plus-d-activites-de-publication' )
	} );

	/**
	 * Navigation to toggle between WordPress comments / BuddyPress activities.
	 */
	bp.Views.navToggle = bp.View.extend( {
		tagName  : 'ul',
		template : bp.template( 'activites-de-publication-nav' ),

		events: {
			'click .nav-item a'  : 'toggleNav'
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

	/**
	 * Activités de publication loop.
	 */
	bp.Views.Activites = bp.View.extend( {
		tagName  : 'ol',
		id       : 'activites-liste',
		className: 'comment-list',

		events: {
			'click .load-more a'  : 'fetchMoreActivities'
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
						per_page: parseInt( _activitesDePublicationSettings.activitiesPerPage, 10 )
					}
				} );
			}
		}
	} );

	/**
	 * Activités de publication entry.
	 */
	bp.Views.Activite = bp.View.extend( {
		tagName  : 'li',
		template : bp.template( 'activites-de-publication' ),
		className: 'comment depth-1'
	} );

	// Globalize the Collection.
	bp.ActivitesDePublications = {
		activites: new bp.Collections.activites()
	};

	// BP String overrides
	if ( ! _.isUndefined( BP_Nouveau.activity.strings.postUpdateButton ) ) {
		BP_Nouveau.activity.strings.postUpdateButton = _activitesDePublicationSettings.publishLabel;
	}

	// Fetch the Activités de publication.
	bp.ActivitesDePublications.activites.fetch( {
		data: {
			page: 1,
			per_page: parseInt( _activitesDePublicationSettings.activitiesPerPage, 10 )
		},
		success: function() {
			bp.Nouveau.Activity.postForm.start();
		},
		error: function( collection, response ) {
			if ( response.responseJSON && 'rest_authorization_required' === response.responseJSON.code ) {
				var feedback = new bp.Views.activityFeedback( {
					value: _activitesDePublicationSettings.mustLogIn,
					type: 'info'
				} );

				// Inject the login feedback.
				feedback.inject( '#activites-de-publication-list' );
			}
		}
	} );

	if ( $( '#activites-de-publication-nav' ).length ) {
		var navToggle = new bp.Views.navToggle();

		// Inject the Navigation if needed.
		navToggle.inject( '#activites-de-publication-nav' );
	}

	var activitesView = new bp.Views.Activites( {
		collection: bp.ActivitesDePublications.activites
	} );

	// Inject the Activités de publication main view.
	activitesView.inject( '#activites-de-publication-list' );

} )( jQuery, _, window.bp || {}, window.wp || {} );
