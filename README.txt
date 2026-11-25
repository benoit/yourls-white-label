WHITE LABEL — Plugin pour YOURLS
Version 1.0.0

INSTALLATION
1. Copier le dossier white-label dans user/plugins/ sur votre serveur.
   Le fichier doit être : user/plugins/white-label/plugin.php
2. Activer « White Label » dans Administration > Plugins.
3. Recharger la page, puis vérifier aussi la connexion en navigation privée.
4. Pour désinstaller, désactiver le plugin et supprimer son dossier.

PERSONNALISATION
Ajouter dans user/config.php, avant le chargement de YOURLS :
define('BE_WHITE_LABEL_NAME', 'Mon service');
Par défaut, le titre et les mentions de navigation deviennent « Liens courts ».

FONCTIONNEMENT
- Supprime du HTML le logo natif et son en-tête, ainsi que le footer natif.
- Neutralise le titre, retire la meta generator et la description native.
- Retire les favicons déclarés et ajoute une icône vide (data:,).
- Retire les commentaires HTML contenant YOURLS, hors blocs protégés.
- Remplace les mentions YOURLS / Your Own URL Shortener dans le texte
  de l'interface hors liens, tableaux, formulaires et élément main.
- Convertit les liens officiels yourls.org hors tableaux/formulaires/main
  en texte neutre. Les liens et données enregistrés restent intacts.
- Le traitement intervient sur le serveur avant l'envoi au navigateur :
  les éléments supprimés ne restent pas simplement cachés par CSS.
- Ne modifie ni les fichiers du cœur, ni la base de données.

PÉRIMÈTRE ET LIMITES
Ce plugin retire la marque dans les pages HTML chargées après son activation.
Il ne garantit pas l'absence absolue de la chaîne « yourls » dans toutes les
ressources : noms JavaScript, identifiants, champs de formulaires, URL de
l'installation et ressources CSS/JS nécessaires sont conservés. Les blocs
script, style, textarea, pre et code sont préservés, y compris leurs commentaires.
Les liens et titres stockés, contenus dans main et certaines pages de plugins
peuvent donc encore mentionner YOURLS. Ce n'est pas un dispositif d'anonymisation.

Les réponses API/JSON, fragments AJAX, téléchargements, redirections et réponses
déjà compressées sont conservés. Seuls les documents complets commençant par
<!doctype html> ou <html> sont traités (après les espaces éventuels).
Les pages d'installation, de maintenance, de mise à jour ou erreurs émises avant
le chargement des plugins ne peuvent pas être modifiées par ce plugin.
Les ressources statiques et en-têtes HTTP ne sont pas nettoyés.
Les favicons personnalisés sont également neutralisés.

Le traitement DOM peut normaliser la mise en forme du HTML. Tester avec vos
autres plugins et votre thème avant usage en production. Les documents sont
mis en tampon en mémoire jusqu'à la fin de la réponse (pas de rendu progressif).
Un plugin qui détruit ce tampon peut empêcher le nettoyage.

PRÉREQUIS ET VÉRIFICATION
PHP 7.4+ avec extension DOM/libxml. Sans DOM, le nettoyage HTML est ignoré ;
seuls les filtres du titre et du texte du footer restent actifs.
Points d'intégration vérifiés dans le dépôt officiel YOURLS, branche master,
le 25 novembre 2026. Aucune instance serveur n'a été déployée pour validation.

Vérifications à lancer sur un environnement PHP :
php -l plugin.php
php tests/check.php

Vérifier ensuite : connexion, ajout/édition/suppression d'un lien, statistiques,
pages de plugins et appel API. Inspecter le code source reçu par le navigateur.
Le favicon peut nécessiter une navigation privée à cause du cache du navigateur.

Références officielles :
https://github.com/YOURLS/YOURLS/blob/master/includes/functions-html.php
https://github.com/YOURLS/YOURLS/blob/master/includes/functions-plugins.php
https://github.com/YOURLS/YOURLS/blob/master/includes/Config/Init.php
