<?php
/**
 * Publication Activity entry JS Template
 *
 * @package Activites_de_Publication\templates\buddypress\common\js-templates\activity
 *
 * @since 1.0.0
 * @version 1.0.0
 */
?>
<script type="text/html" id="tmpl-activites-de-publication-nav">
	<li class="nav-item current">
		<a href="#comments" data-type="comments">
			<?php esc_html_e( 'Commentaires', 'activites-de-publication' ); ?>
		</a>
	</li>
	<li class="nav-item">
		<a href="#conversations" data-type="conversations">
			<?php esc_html_e( 'Conversations', 'activites-de-publication' ); ?>
		</a>
	</li>
</script>

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
						<a class="comment-edit-link" href="{{data.edit_link}}"><?php esc_html_e( 'Modifier', 'activites-de-publication' ); ?></a>
					</span>
				<# } #>
			</div>
		</footer>
		<div class="comment-content">
			{{{data.content.rendered}}}
		</div>
	</article>

	<# if ( ! data.parentActivite && ! data.parent ) { #>
		<div class="comment-reply">
			<# if ( data.comment_count ) { #>
				<a rel="nofollow" class="comment-reply-link activite-de-publication-action" href="#view-replies/{{data.id}}" data-parent-id="{{data.id}}" data-action="view">
					<# if ( parseInt( data.comment_count, 10 ) === 1 ) { #>
						<?php esc_html_e( 'Lire la réponse', 'activites-de-publication' ); ?>
					<# } else { #>
						<# var commentString = "<?php esc_html_e( 'Lire les %s réponses', 'activites-de-publication' ); ?>"; #>
						<# print( commentString.replace( '%s', data.comment_count ) ); #>
					<# } #>
				</a>
			<# } else { #>
				<?php if ( is_user_logged_in() ) :?>
					<a rel="nofollow" class="comment-reply-link activite-de-publication-action" href="#reply/{{data.id}}" data-parent-id="{{data.id}}" data-action="reply"><?php esc_html_e( 'Répondre', 'activites-de-publication' ); ?></a>
				<?php endif ; ?>
			<# } #>
		</div>
	<# } #>

	<# if ( data.parentActivite ) { #>
		<div class="comment-reply">
			<a crel="nofollow" class="comment-reply-link" href="#view-all" id="back-to-all-activites-de-publication"><?php esc_html_e( 'Revenir sur toutes les conversations', 'activites-de-publication' ); ?></a>
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-plus-d-activites-de-publication">
	<a href="#" data-next-page="{{{data.nextPage}}}"><?php esc_html_e( 'Charger plus de conversations', 'activites-de-publication' ); ?></a>
</script>
