# Manifeste de release Keleva — 0.4.19 / 0.6.23

## Référence

La release candidate est construite depuis le commit GitHub `8663ce3f59da5549b3c0b29b2a2245a82789d3b2`, branche `main`. Le commit `9f39ece` est le parent avant alignement des métadonnées ; `6c69e41` et `9c7e5f3` sont historiques et ne sont pas des références de déploiement.

| Composant | Version | Archive | SHA-256 |
|---|---:|---|---|
| Thème Keleva Woo | 0.4.19 | `installables/keleva-woo-0.4.19.zip` | Voir `RELEASE-SHA256-0.4.19-0.6.23.txt` |
| Plugin Keleva Woo Addons | 0.6.23 | `installables/keleva-woo-addons-0.6.23.zip` | Voir `RELEASE-SHA256-0.4.19-0.6.23.txt` |

## Dépendances

WordPress doit satisfaire les exigences du thème et du plugin. WooCommerce est requis pour les fonctions catalogue, panier, checkout, options, variations et stock. TranslatePress Free est requis pour la couche FR/AR actuellement retenue. Les réglages et données TranslatePress ne sont pas contenus dans ces archives.

## Ordre d’installation contrôlé

1. Prendre une sauvegarde complète de la production et prouver qu’elle est restaurable.
2. Activer la maintenance et conserver les versions précédentes comme rollback.
3. Installer ou mettre à jour le thème Keleva Woo 0.4.19.
4. Installer ou mettre à jour Keleva Woo Addons 0.6.23.
5. Vérifier les extensions requises et les réglages WooCommerce sans importer de données staging.
6. Restaurer ou reporter TranslatePress par une procédure privée et ciblée, après validation du préfixe et des tables.
7. Purger les caches, régénérer les permaliens si nécessaire et exécuter la matrice FR/AR.

## Rollback

Si seul le code est en cause, restaurer d’abord les archives précédentes du thème et du plugin, purger les caches et rejouer la recette. Une restauration SQL complète est réservée aux cas de corruption de données, après sauvegarde de l’état courant et décision autorisée. Les commandes, clients, stocks et paiements de production ne doivent jamais être remplacés par les données staging.

## Limite de statut

Ces archives sont reconstruites et checksumées, mais cela ne constitue pas un GO production. La sauvegarde production restaurée, l’export TranslatePress restaurable, l’exercice de rollback, l’inventaire production, la configuration sandbox des paiements et la recette métier signée restent des contrôles P0 à produire avant ouverture commerciale.
