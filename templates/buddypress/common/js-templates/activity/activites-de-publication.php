<?php
/**
 * Publication Activity entry JS Template
 *
 * @since 1.0.0
 * @version 1.0.0
 */
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

<script type="text/html" id="tmpl-plus-d-activites-de-publication">
    <a href="#" data-next-page="{{{data.nextPage}}}"><?php esc_html_e( 'Charger plus de conversations', 'activites-d-article' ); ?></a>
</script>
