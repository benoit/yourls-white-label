# YOURLS White Label

Plugin pour retirer les éléments de marque des pages HTML générées par YOURLS, côté serveur et sans modifier le cœur.

## Installation

1. Télécharger le dépôt et copier son contenu dans `user/plugins/white-label/`.
2. Vérifier que le fichier est à l’emplacement `user/plugins/white-label/plugin.php`.
3. Activer **White Label** dans l’administration.

PHP 7.4+ et l’extension DOM/libxml sont nécessaires pour le nettoyage HTML.

## Personnalisation

Dans `user/config.php` :

```php
define('BE_WHITE_LABEL_NAME', 'Mon service');
```

Le nom par défaut est « Liens courts ».

## Éléments retirés

- Logo et en-tête natifs, footer natif.
- Métadonnées generator et description de marque.
- Favicon, remplacé par une icône vide.
- Commentaires HTML contenant YOURLS, hors blocs protégés.
- Mentions et liens officiels dans certaines zones de navigation.

## Limites

Les identifiants, chemins techniques, scripts et données enregistrées sont préservés pour maintenir le fonctionnement. Le plugin ne garantit donc pas l’absence absolue de la chaîne `yourls` dans le code ou les ressources.

Les pages émises avant le chargement des plugins ne sont pas couvertes. Les réponses API/JSON, fragments AJAX, redirections et téléchargements sont conservés. Le HTML est mis en mémoire jusqu’à la fin de la réponse et peut être normalisé par DOM.

Consulter [README.txt](README.txt) pour le périmètre détaillé et les contrôles à effectuer sur une installation.

## Vérification

```sh
php -l plugin.php
php tests/check.php
```

Les tests sont fournis mais n’ont pas encore été exécutés dans l’environnement de création, dépourvu de PHP. Valider sur une installation de test avant déploiement.
