# Procédure de migration Keleva — staging Hostinger vers production

## 1. Objet et périmètre

Cette procédure décrit le passage contrôlé de la suite Keleva WooCommerce du staging Hostinger vers la production. Elle couvre le thème Keleva, le plugin Keleva Woo Addons, les traductions TranslatePress Free, les réglages WooCommerce, les médias et les données métier nécessaires. Elle ne doit être exécutée qu’après validation écrite du propriétaire et pendant une fenêtre de maintenance annoncée.

La production ne doit jamais être remplacée par une copie de base de données de staging sans décision explicite. La stratégie recommandée est une migration de code et de configuration, suivie d’une migration sélective des traductions et contenus validés. Les clients, commandes, paiements et stocks de production doivent rester la source de vérité.

## 2. Pré-requis et critères d’arrêt

| Pré-requis | Critère de validation |
|---|---|
| Accès Hostinger staging et production | Accès administrateur testé, MFA disponible |
| Sauvegarde Hostinger | Archive fichiers + export SQL datés et téléchargeables |
| Versions release | Thème et plugin identifiés par version et checksum |
| Liste des extensions | Même pile gratuite validée, extensions inutiles exclues |
| TranslatePress | Export ou sauvegarde des traductions vérifié |
| Fenêtre de maintenance | Heure de début, responsable et contact de décision désignés |
| Rollback | Procédure testée sur une copie ou staging restauré |

**Arrêt immédiat** si une sauvegarde est incomplète, si un paiement réel est en cours, si les checksums ne correspondent pas, si une migration écrase des commandes de production ou si les tests critiques échouent.

## 3. Sauvegarde avant migration

1. Activer le mode maintenance ou une page de maintenance très courte, sans empêcher l’accès de l’équipe technique.
2. Depuis Hostinger, créer une sauvegarde complète de la production comprenant fichiers, base de données, médias et configuration. Télécharger une copie hors du serveur et vérifier sa taille, sa date et son intégrité.
3. Exporter la base avec l’outil Hostinger/phpMyAdmin ou WP-CLI. Conserver le fichier SQL original sans recherche-remplacement destructif.
4. Archiver séparément `wp-content/themes/keleva-*`, `wp-content/plugins/keleva-woo-addons`, les fichiers de configuration nécessaires et le dossier des médias si celui-ci est concerné.
5. Noter les versions actives, les extensions, les réglages TranslatePress, WooCommerce, les passerelles de paiement et les tâches cron.
6. Prendre un instantané fonctionnel : accueil FR, accueil AR, fiche produit, panier, checkout, connexion portail marchand, produits, commandes et apparence.
7. Calculer et consigner les SHA-256 des archives de sauvegarde et de release. Ne jamais placer de mot de passe, clé API ou export client dans GitHub.

## 4. Préparation de la release

1. Geler les modifications de contenu pendant la fenêtre de migration.
2. Vérifier que le staging est propre : aucun client réel, aucune commande réelle, aucun paiement réel et aucune donnée de production.
3. Exporter ou sauvegarder les traductions TranslatePress validées. La traduction arabe des descriptions doit être vérifiée sur les fiches produit correspondantes avant export.
4. Construire les archives du thème et du plugin avec leurs numéros de version. Vérifier PHP lint, tests automatisés, `git diff --check` et l’absence de secrets.
5. Comparer la liste des extensions staging/production. Ne pas installer automatiquement une extension payante ou non validée.
6. Préparer un manifeste de release : versions, fichiers, checksum, migrations SQL éventuelles, ordre d’installation et plan de rollback.

## 5. Déploiement contrôlé

1. Faire une dernière sauvegarde juste avant l’écriture en production.
2. Désactiver temporairement les caches applicatifs et CDN, sans supprimer les fichiers de cache nécessaires à un retour arrière.
3. Déployer d’abord le thème, puis le plugin Keleva Woo Addons. Ne pas supprimer une extension active pour forcer une mise à jour en production ; utiliser une mise à jour atomique ou conserver l’ancienne archive.
4. Importer uniquement les traductions et contenus approuvés. Ne pas importer les tables de commandes, clients, stocks ou paiements du staging.
5. Purger les caches de façon contrôlée, puis régénérer les permaliens une seule fois si nécessaire.
6. Vérifier les réglages WooCommerce, les URLs canoniques, la devise, les taxes, les zones de livraison, les moyens de paiement et les notifications sans effectuer de paiement réel.
7. Désactiver le mode maintenance uniquement après les contrôles post-déploiement.

## 6. Vérifications post-déploiement

| Domaine | Contrôle attendu |
|---|---|
| Disponibilité | Accueil, boutique, produit, panier et checkout répondent en HTTP 200 |
| Français | Textes, prix, images, options et panier inchangés |
| Arabe/RTL | Route `/ar/`, direction RTL, textes arabes, aucun débordement horizontal |
| Produit | Description, variations, suppléments, prix et stock corrects |
| Panier | Ajout, suppression, quantité et notices AJAX corrects |
| Checkout | Formulaire et moyen de paiement visibles ; aucun paiement soumis |
| Portail | Authentification indépendante, produits, commandes et apparence accessibles |
| Sécurité | Aucun mot de passe dans le dépôt, cookies sécurisés, wp-admin non exposé par le portail |
| Performance | Cache réactivé, images chargées, absence d’erreur PHP/JS critique |

Effectuer ces contrôles sur desktop et à 390 × 844 px. Conserver les URLs, heures, codes HTTP, captures et anomalies dans le rapport de recette de release.

## 7. Rollback applicatif immédiat

Déclencher le rollback si une route critique renvoie une erreur 5xx, si le panier ou le checkout est bloqué, si le RTL casse la navigation, si une commande ou un stock est incohérent ou si une erreur PHP critique apparaît.

1. Réactiver le mode maintenance.
2. Restaurer l’ancienne archive du thème et du plugin, dans l’ordre inverse du déploiement.
3. Restaurer les réglages ou fichiers de configuration modifiés depuis le manifeste ; ne pas restaurer aveuglément la base si des commandes de production ont été créées depuis la migration.
4. Purger les caches et régénérer les permaliens si le rollback les concerne.
5. Rejouer accueil, produit, panier, checkout sans transaction, portail et connexion.
6. Réouvrir le site uniquement après retour aux critères de disponibilité et consigner l’incident.

## 8. Restauration complète des données

Une restauration SQL complète n’est autorisée que si la base de production est inutilisable et après décision explicite du responsable. Avant restauration, arrêter les écritures, sauvegarder l’état courant et confirmer le point de restauration. Restaurer les fichiers correspondant au même instant que la base, vérifier les URLs et lancer les contrôles d’intégrité. Ne jamais remplacer une base de production par la base staging simplement parce que le staging est plus récent.

Si seules les traductions ou des contenus éditoriaux doivent être restaurés, préférer un export sélectif TranslatePress ou une restauration ciblée des tables concernées, après identification exacte des tables et dépendances. Conserver les commandes, clients, stocks et paiements de production.

## 9. Clôture et traçabilité

1. Désactiver le mode maintenance après validation par le responsable métier.
2. Réactiver les caches et vérifier une URL FR et une URL AR après purge.
3. Archiver le manifeste, les checksums, les sauvegardes, le rapport de recette et la décision de go/no-go.
4. Noter les versions effectivement actives en production et le point de restauration disponible.
5. Ouvrir un ticket de suivi pour toute anomalie non bloquante ; ne pas corriger directement en production hors fenêtre contrôlée.

## 10. Références

[1]: https://developer.wordpress.org/advanced-administration/upgrade/wordpress/ — documentation WordPress sur les mises à jour.
[2]: https://developer.wordpress.org/advanced-administration/upgrade/backup/ — recommandations WordPress sur les sauvegardes avant mise à jour.
[3]: https://woocommerce.com/document/understanding-the-woocommerce-system-status-report/ — vérification de l’environnement WooCommerce.

> **Règle de sécurité :** aucun mot de passe, token, export client ou donnée de commande ne doit être ajouté au dépôt GitHub public. Les sauvegardes doivent rester dans un stockage privé contrôlé.
