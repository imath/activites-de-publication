/**
 * Activités de publication front script.
 */

/* global _activitesDePublicationSettings, BP_Nouveau */

( function( $, _, bp, wp ) {

	if ( 'undefined' === typeof _activitesDePublicationSettings ) {
		return;
	}

	var postForm, postContainer, activityContainer, parentContainer, postFormAvatar, postFormTextarea, containerClass = 'no-nav',
	    excludedActivities = [];

	if ( typeof bp.View === 'undefined' ) {
		_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

		bp.Models      = bp.Models || {};
		bp.Collections = bp.Collections || {};
		bp.Views       = bp.Views || {};

		// Extend wp.Backbone.View with .prepare() and .inject()
		bp.View = bp.Backbone.View.extend( {
			inject: function( selector ) {
				this.render();
				$(selector).html( this.el );
				this.views.ready();
			},

			prepare: function() {
				if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
					return this.model.toJSON();
				} else {
					return {};
				}
			},

			prepend: function( selector ) {
				this.render();
				$( selector ).prepend( this.el );
				this.views.ready();
			}
		} );
	}

	if ( $( '#tmpl-activity-post-form-buttons' ).length ) {
		postContainer = $( '#tmpl-activity-post-form-buttons' ).parent();
	} else {
		postContainer = $( '#tmpl-activites-de-publication-nav' ).parent();
	}

	if ( bp.Views.PostForm ) {
		postForm = bp.Views.PostForm;
		postFormAvatar = bp.Views.FormAvatar;
		postFormTextarea = bp.Views.WhatsNew;
	}

	// Container for the Nav and Activity post form (comments allowed).
	if ( $( '#comments' ).length ) {
		containerClass = 'hide';

		$( '#comments' ).before( $( '<div></div>' ).prop( 'id', 'activites-de-publication-nav' ) );

		if ( postForm ) {
			$( '#activites-de-publication-nav' ).after(
				$( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' )
								  .addClass( containerClass + ' comments-area' )
			);
			activityContainer = $( '#bp-nouveau-activity-form' );

		} else {
			activityContainer = $( '#activites-de-publication-nav' );
		}

	// Container for the Activity post form (comments not allowed).
	} else if ( postForm ) {
		$( postContainer ).after(
			$( '<div></div>' ).prop( 'id', 'bp-nouveau-activity-form' )
			                  .addClass( containerClass + ' comments-area' )
		);

		activityContainer = $( '#bp-nouveau-activity-form' );
	} else {
		activityContainer = postContainer;
	}

	// Container for the list of Activités de publication for this Post.
	activityContainer.after(
		$( '<div></div>' ).prop( 'id', 'activites-de-publication-list' )
		                  .addClass( containerClass + ' comments-area' )
	);

	// Container for the parent Activités de publication for the displayed activity comments.
	if ( $( '#bp-nouveau-activity-form' ).length ) {
		parentContainer = $( '#bp-nouveau-activity-form' );
	} else {
		parentContainer = $( '#activites-de-publication-list' );
	}

	parentContainer.before(
		$( '<div></div>' ).prop( 'id', 'activites-de-publication-parent' )
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
				return bp.apiRequest( options );
			}
		},

		updateFavorite: function( options ) {
			var links = this.get( '_links' ), self = this, success = options.success;
			options  = options || {};
			options.context = this;
			_.extend( options, _.omit( this.options, ['path', 'data'] ) );

			if ( ! links.favorite || links.favorite.length < 1 ) {
				return false;
			}

			// Instead of the path, use the favorite route.
			options.url = _.first( links.favorite ).href;

			options.success = function( data ) {
				if ( data ) {
					self.set( { favorited: _.first( data ).favorited } );
				}

				if ( success ) {
					return success.apply( this, arguments );
				}
			};

			return bp.apiRequest( options );
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

			if ( excludedActivities.length > 0 ) {
				options.data.exclude = excludedActivities;
			} else if ( options.data.exclude ) {
				delete options.data.exclude;
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
				return bp.apiRequest( options );
			}
		}
	} );

	if ( postForm ) {
		/**
		 * Activity Post Form overrides.
		 */
		bp.Views.PostForm = postForm.extend( {
			initialize: function() {
				// Use Parent initializer.
				postForm.prototype.initialize.apply( this, arguments );

				this.options.displayedParent = bp.ActivitesDePublications.parentActivites;
				this.options.postData        = this.getActiviteDePublicationData();

				this.on( 'ready', this.bpMentionsRefresh, this );
				this.options.displayedParent.on( 'reset', this.updatePlaceholder, this );
			},

			getActiviteDePublicationData: function() {
				return {
					type: 'publication_activity',
					'primary_item_id' : _activitesDePublicationSettings.primaryID,
					'secondary_item_id' : _activitesDePublicationSettings.secondaryID,
					user: this.model.get( 'user_id' )
				};
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

				var self = this, meta = {}, activite = new bp.Models.activite();

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
					_.extend( this.model.attributes, this.options.postData ), {
						success: function( model, response ) {
							// Get the first activity and add it to the collection.
							var published = _.extend( _.first( response ), { at: 0 } );
							bp.ActivitesDePublications.activites.add( published );

							// Make sure the paginate results are kept consistent.
							excludedActivities.push( published.id );

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
			},

			updatePlaceholder: function( collection ) {
				var placeholder;

				if ( collection.models && collection.models.length === 1 ) {
					var parent    = _.first( collection.models ),
					    parent_id = parent.get( 'id' );

					_.extend( this.options.postData, {
						type: 'activity_comment',
						'primary_item_id' : parent_id,
						'secondary_item_id' : parent_id
					} );

					placeholder = _activitesDePublicationSettings.textareaPlaceholderAlt.replace( '%s', parent.get( 'user_name' ) );
				} else {
					this.options.postData = this.getActiviteDePublicationData();
					placeholder           = _activitesDePublicationSettings.textareaPlaceholder;
				}

				_.each( this.views._views[''], function( view ) {
					if ( 'whats-new-content' === $( view.el ).prop( 'id' ) ) {
						$( view.el ).find( 'textarea[name="whats-new"]').prop( 'placeholder', placeholder );
					}
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
	} else {
		// Feedback messages
		bp.Views.activityFeedback = bp.View.extend( {
			tagName  : 'div',
			id       : 'message',
			template : bp.template( 'activity-post-form-feedback' ),

			initialize: function() {
				this.model = new Backbone.Model();

				if ( this.options.value ) {
					this.model.set( 'message', this.options.value, { silent: true } );
				}

				this.type  = 'info';

				if ( ! _.isUndefined( this.options.type ) && 'info' !== this.options.type ) {
					this.type = this.options.type;
				}

				this.el.className = 'bp-messages bp-feedback ' + this.type ;
			}
		} );
	}

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

		initialize: function() {
			this.on( 'ready', this.setActiveListViews, this );
		},

		toggleNav: function( event ) {
			event.preventDefault();

			this.$el.find( '.nav-item' ).removeClass( 'current' );
			$( event.currentTarget ).parent().addClass( 'current' );

			if ( 'conversations' === $( event.currentTarget ).data( 'type' ) ) {
				$( '#bp-nouveau-activity-form' ).removeClass( 'hide' );
				$( '#activites-de-publication-list' ).removeClass( 'hide' );
				$( '#activites-de-publication-parent' ).removeClass( 'hide' );
				$( '#comments' ).hide();
			} else {
				$( '#bp-nouveau-activity-form' ).addClass( 'hide' );
				$( '#activites-de-publication-list' ).addClass( 'hide' );
				$( '#activites-de-publication-parent' ).addClass( 'hide' );
				$( '#comments' ).show();
			}
		},

		setActiveListViews: function() {
			var currentType = this.$el.find( 'li.current a' ).data( 'type' );

			// The site admin might edit the template.
			if ( 'comments' !== currentType ) {
				$( '#bp-nouveau-activity-form' ).removeClass( 'hide' );
				$( '#activites-de-publication-list' ).removeClass( 'hide' );
				$( '#activites-de-publication-parent' ).removeClass( 'hide' );
				$( '#comments' ).hide();
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
			this.collection.on( 'reset', this.resetQuery, this );
		},

		addActiviteView: function( activite ) {
			var options = {};

			if ( ! _.isUndefined( activite.get( 'at' ) ) ) {
				options.at = activite.get( 'at' );
				activite.unset( 'at', { silent: true } );
			}

			this.removeInfos();
			this.views.add( new bp.Views.Activite( { model: activite, parents: this.options.parents } ), options );
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

		removePaginationLink: function() {
			_.each( this.views._views[''], function( view ) {
				if ( view.model.get( 'nextPage' ) ) {
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
				var feedback = _activitesDePublicationSettings.noConversations;

				if ( ! _.isUndefined( collection.options.data.type ) && 'activity_comment' === collection.options.data.type ) {
					feedback = _activitesDePublicationSettings.noReplies;
				}

				this.attachFeedback( feedback );
			}
		},

		fetchMoreActivities: function( event ) {
			event.preventDefault();

			var nextPage = $( event.currentTarget ).data( 'next-page' );

			// Remove the pagination link.
			this.removePaginationLink();

			if ( nextPage ) {
				this.attachFeedback();

				this.collection.fetch( {
					data: {
						page: nextPage,
						per_page: parseInt( _activitesDePublicationSettings.activitiesPerPage, 10 ),
						display_comments: true
					}
				} );
			}
		},

		resetQuery: function( collection, options ) {
			var data = {
				page: 1,
				per_page: parseInt( _activitesDePublicationSettings.activitiesPerPage, 10 ),
				'display_comments': true
			};

			// Remove the pagination link.
			this.removePaginationLink();

			if ( options.parent && null !== options.parent ) {
				this.attachFeedback( _activitesDePublicationSettings.loadingReplies );
				_.extend( data, {
					type: 'activity_comment',
					'primary_id': options.parent,
					'secondary_id': options.parent
				} );
			} else {
				this.attachFeedback( _activitesDePublicationSettings.loadingConversations );
				_.extend( data, {
					type: 'publication_activity',
					'primary_id' : _activitesDePublicationSettings.primaryID,
					'secondary_id' : _activitesDePublicationSettings.secondaryID
				} );
			}

			// Reset the excluded Activities
			excludedActivities = [];

			// Fetch Activities or Activity comments.
			collection.fetch( { data: data } );
		}
	} );

	bp.Views.parentActivite = bp.Views.Activites.extend( {
		id: 'parent-activite',

		initialize: function() {
			this.collection.on( 'reset', this.showParentActivite, this );
		},

		showParentActivite: function( parents ) {
			if ( ! parents.models.length ) {
				return;
			}

			this.views.add( new bp.Views.Activite( { model: _.first( parents.models ), children: this.options.children } ) );
		}
	} );

	/**
	 * Activités de publication entry.
	 */
	bp.Views.Activite = bp.View.extend( {
		tagName  : 'li',
		template : bp.template( 'activites-de-publication' ),
		className: 'comment depth-1',

		events: {
			'click .activite-de-publication-action' : 'fetchActivityComments',
			'click .activite-de-publication-favorite' : 'favUnfav',
			'click #back-to-all-activites-de-publication' : 'backToAllActivites'
		},

		initialize: function() {
			this.model.collection.on( 'reset', this.cleanView, this );
			this.on( 'ready', this.scrollTo, this );
		},

		fetchActivityComments: function( event ) {
			event.preventDefault();

			var parent = this.model.clone(), action = $( event.currentTarget ).data( 'action' );

			// Make sure to avoid displaying action buttons.
			parent.set( { parentActivite: true }, { silent: true } );

			// Reset collections to clean views and query activity comments.
			this.options.parents.reset( [ parent ], { action: action } );

			if ( this.model.collection ) {
				this.model.collection.reset( null, { parent: this.model.get( 'id' ) } );
			} else {
				bp.ActivitesDePublications.activites.reset( null, { parent: this.model.get( 'id' ) } );
			}
		},

		favUnfav: function( event ) {
			event.preventDefault();

			var self = this;

			if ( ! self.model.get( 'favoriting' ) ) {
				self.model.set( { favoriting: true }, { silent: true } );

				this.model.updateFavorite( {
					success: function() {
						self.model.unset( 'favoriting', { silent: true } );

						// Re-render the view to take the change in account.
						self.render();
					}
				} );
			}

		},

		cleanView: function() {
			this.views.view.remove();
		},

		backToAllActivites: function( event ) {
			event.preventDefault();

			this.model.collection.reset( null );
			this.options.children.reset( null, { parent: null } );
		},

		/**
		 * Make sure to scrollTo the displayed parent activity.
		 */
		scrollTo: function() {
			if ( ! this.options.children ) {
				return;
			}

			var to = $('#activites-de-publication-parent').offset().top;
			if ( $( '#wpadminbar').length ) {
				to -= $( '#wpadminbar').height();
			}

			window.scrollTo( 0, to );
		}
	} );

	// Globalize the Collection.
	bp.ActivitesDePublications = {
		activites: new bp.Collections.activites(),
		parentActivites: new bp.Collections.activites()
	};

	// BP String overrides
	if ( ! _.isUndefined( BP_Nouveau.activity.strings.postUpdateButton ) ) {
		BP_Nouveau.activity.strings.postUpdateButton = _activitesDePublicationSettings.publishLabel;
	}

	if ( $( '#activites-de-publication-nav' ).length ) {
		var navToggle = new bp.Views.navToggle();

		// Inject the Navigation if needed.
		navToggle.inject( '#activites-de-publication-nav' );
	}

	var activitesView = new bp.Views.Activites( {
		collection: bp.ActivitesDePublications.activites,
		parents: bp.ActivitesDePublications.parentActivites
	} );

	// Inject the Activités de publication main view.
	activitesView.inject( '#activites-de-publication-list' );

	var parentActivitesView = new bp.Views.parentActivite( {
		collection: bp.ActivitesDePublications.parentActivites,
		children: bp.ActivitesDePublications.activites
	} );

	// Inject the Activités de publication main view.
	parentActivitesView.inject( '#activites-de-publication-parent' );

	// Fetch the Activités de publication.
	bp.ActivitesDePublications.activites.fetch( {
		data: {
			page: 1,
			per_page: parseInt( _activitesDePublicationSettings.activitiesPerPage, 10 ),
			'display_comments': true
		},
		success: function() {
			if ( postForm ) {
				bp.Nouveau.Activity.postForm.start();
			} else {
				var feedback = new bp.Views.activityFeedback( {
					value: _activitesDePublicationSettings.mustLogIn,
					type: 'info'
				} );

				// Inject the login feedback.
				feedback.prepend( '#activites-de-publication-list' );
			}
		}
	} );

} )( jQuery, _, window.bp || {}, window.wp || {} );
