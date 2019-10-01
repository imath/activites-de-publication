# Activités de Publication

## Français 🇫🇷
*Utilisez les activités de BuddyPress pour dynamiser les conversations de vos publications WordPress.*

Cette extension BuddyPress vous propose d'ajouter un nouvel espace de conversation à vos publications en complément ou en remplacement de leurs commentaires en utilisant le composant des activités de BuddyPress.

### Configuration requise :

- WordPress 5.0.0,
- BuddyPress 5.0.0,
- le composant des activités de BuddyPress doit être actif,
- la Rest API de WordPress doit être disponible (elle l'est par défaut).

### Compatibilité avec les thèmes

La partie la plus sensible sur ce sujet intervient lorsque vous activez les commentaires dans votre type de publication. Dans ce cas, un balisage de navigation est injecté juste au dessus du sélecteur `#comments` pour pouvoir alterner entre commentaires et conversations. Les thèmes intégrés par défaut dans WordPress sont optimaux pour Activités de publication dans la mesure où ils respectent les bonnes pratiques partagées sur la documentation de développement des thèmes de WordPress quant au [balisage du gabarit des commentaires](https://developer.wordpress.org/themes/template-files-section/partial-and-miscellaneous-template-files/comment-template/) (localisé dans le fichier `/wp-content/nom-de-votre-theme/comments.php`).

Il est important de regrouper tout ce qui concerne les commentaires dans un conteneur dont le selecteur est `#comments` par exemple :

```php
<div id="comments">

    <?php if ( have_comments() ) : ?>

        <ol class="comment-list">

            <?php // Code pour la liste des commentaires ?>

        </ol><!-- .comment-list -->

    <?php endif ; ?>

    <?php
    // Le formulaire pour ajouter un nouveau commentaire.
    comment_form(); ?>

</div><!-- #comment -->
```

#### Votre thème ne respecte pas cette bonne pratique ?

Pas de panique, s'il est bien conçu, vous pouvez normalement forcer l'application de cette bonne pratique en créant un thème enfant et en l'activant sur votre site. Pour en savoir plus sur la mise en place d'un thème enfant, reporter vous à ce [chapitre de la documentation de développement des thèmes de WordPress](https://developer.wordpress.org/themes/advanced-topics/child-themes/).

Une fois votre thème enfant en place, il vous suffira de copier le gabarit `comments.php` du thème parent dans votre thème enfant afin de rebaliser le gabarit correctement (en veillant à ce que toute ce qui concerne les commentaires soit dans un conteneur dont le selecteur - ou l'attribut `id` si vous préférez - est `#comments`).

### Installation :

Téléchargez le fichier `activites-de-publication.zip` de la version la plus récente listée dans [cette page des "releases"](https://github.com/imath/activites-de-publication/releases). Depuis l'écran d'administration des extensions de votre WordPress, cliquez sur le bouton "Ajouter" pour afficher l'outil de téléversement d'extension de WordPress et sélectionnez le fichier zip précédemment téléchargé pour le téléverser sur votre serveur. Une fois cette étape effectuée, activez l'extension.

## English 🇬🇧
*Use the BuddyPress activities to boost conversations about your WordPress posts*

This BuddyPress plugin brings to your posts a new conversation space to complement or replace their comments by using the Activity BuddyPress component.

### Required configuration:

- WordPress 5.0.0,
- BuddyPress 5.0.0,
- the Activity BuddyPress component must be active,
- the WordPress Rest API must be available (which is the case by default).

### Theme compatibility

The most sensitive part of this topic occurs when you enable comments in your post type. In this case, a navigation markup is injected just above the `#comments` selector to switch between comments and conversations. The WordPress built-in themes are optimal for "Activitités de Publication" as they follow the best practices shared on the WordPress Themes development documentation for the [Comment Template Markup](https://developer.wordpress.org/themes/template-files-section/partial-and-miscellaneous-templates-files/comment-template/) (located in the file `/wp-content/your-theme-name/comments.php`).

It is important to group everything that deals with comments in a container using the `#comments` selector. See above for an example of code.

#### Your theme does not follow the good practice?

Do not panic, if it is well designed, you can normally force the application of this good practice by creating a child theme and activating it on your site. To learn more about setting up a child theme, refer to this [chapter of the WordPress Themes development documentation](https://developer.wordpress.org/themes/advanced-topicschild-themes/).

Once you have your child theme in place, simply copy the `comments.php` template from the parent theme into your child theme. This way you'll be able to safely fix the markup making sure the selector - or the `id` attribute if you prefer - of the container is `#comments`).

### Setup

Download the `activites-de-publication.zip` file of the latest plugin version listed into this [releases page](https://github.com/imath/activites-de-publication/releases). From your WordPress Plugins Administration screen, click on the "Add" button to display the WordPress plugin uploading tool and browse to the place where you saved the zip file on your drive to upload it on your server. Once done, activate the plugin.
