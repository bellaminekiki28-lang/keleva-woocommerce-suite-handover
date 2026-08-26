# Checklist opérationnelle — restauration et go-live Keleva

Cette checklist est destinée à une production WordPress/WooCommerce réelle. Elle doit être exécutée avec un responsable technique et un responsable métier. Toute case non validée bloque le go-live.

## A. Avant la fenêtre de changement

| Fait | Contrôle | Preuve attendue |
|---|---|---|
| [ ] | Confirmer le domaine de production, le fuseau horaire et la fenêtre de maintenance | Ticket de changement |
| [ ] | Identifier le responsable go/no-go et son remplaçant | Noms et coordonnées privées |
| [ ] | Comparer le thème réellement actif avec la release GitHub réconciliée 0.4.19 | Version, checksum et manifeste |
| [ ] | Comparer le plugin actif avec Keleva Woo Addons 0.6.23 | Version et checksum |
| [ ] | Geler le contenu et les réglages pendant la fenêtre | Confirmation métier |
| [ ] | Désactiver les tâches automatiques susceptibles d’écrire pendant la sauvegarde | Journal cron |

## B. Sauvegarde production

| Fait | Contrôle | Preuve attendue |
|---|---|---|
| [ ] | Créer une sauvegarde Hostinger complète des fichiers et de la base | ID/date de sauvegarde |
| [ ] | Télécharger une copie privée hors du serveur | Chemin privé et taille |
| [ ] | Exporter la base SQL sans la publier | Fichier privé, taille, timestamp |
| [ ] | Archiver `wp-content`, thème, plugin, uploads et configuration utile | Archive privée |
| [ ] | Vérifier l’intégrité par SHA-256 | Manifeste de checksums |
| [ ] | Restaurer cette sauvegarde sur une copie de test ou staging isolé | Preuve de restauration |
| [ ] | Vérifier accueil, produit, panier, checkout et accès opérateur sur la copie | Rapport de recette |

## C. Traductions et contenus

| Fait | Contrôle | Preuve attendue |
|---|---|---|
| [ ] | Sauvegarder/exporter les traductions TranslatePress dans un emplacement privé | Export horodaté |
| [ ] | Vérifier les descriptions FR/AR et les contenus éditoriaux approuvés | Échantillon signé |
| [ ] | Ne pas importer les commandes, clients ou stocks du staging | Comparaison de tables |
| [ ] | Décider si les traductions doivent être reportées sélectivement | Décision go/no-go |
| [ ] | Conserver les noms commerciaux et les images selon la décision éditoriale | Liste validée |

## D. Déploiement

| Fait | Contrôle | Preuve attendue |
|---|---|---|
| [ ] | Activer le mode maintenance | URL de maintenance |
| [ ] | Déployer le thème 0.4.19 sans supprimer l’ancien artefact de rollback | Journal de fichiers |
| [ ] | Déployer le plugin 0.6.23 | Version affichée |
| [ ] | Appliquer uniquement les migrations de configuration approuvées | Manifeste signé |
| [ ] | Importer les traductions validées via la méthode privée retenue | Rapport d’import |
| [ ] | Purger les caches puis régénérer les permaliens si nécessaire | Journal d’actions |
| [ ] | Vérifier HTTPS, URLs canoniques, devise, taxes, livraison et paiement | Rapport WooCommerce |

## E. Recette fonctionnelle

| Fait | Contrôle | Résultat attendu |
|---|---|---|
| [ ] | Accueil FR et AR | HTTP 200, contenu lisible |
| [ ] | Catégorie FR et AR | Filtres et cartes sans débordement |
| [ ] | Produit variable FR et AR | Options, prix, stock et description corrects |
| [ ] | Ajout au panier | Produit et option conservés |
| [ ] | Panier | Quantité, suppression, total et notice corrects |
| [ ] | Checkout | Champs et moyens de paiement visibles, aucun paiement de recette |
| [ ] | Portail marchand FR et AR | Session dédiée, RTL correct, aucun accès wp-admin |
| [ ] | Responsive 390 × 844 px | `scrollWidth` égal à la largeur viewport |
| [ ] | Desktop 1280 px | Pas de chevauchement ni contrôle hors écran |
| [ ] | Console navigateur et logs serveur | Aucune erreur critique nouvelle |

## F. Décision go/no-go

Le responsable technique confirme que les sauvegardes sont restaurables, les versions sont identifiées, les routes critiques sont accessibles et le rollback est possible. Le responsable métier confirme que le catalogue, les prix, les stocks, les traductions et le checkout correspondent aux attentes. Sans ces deux validations, maintenir la maintenance et ne pas ouvrir les ventes.

## G. Rollback

En cas d’échec, maintenir la maintenance, noter l’heure et l’erreur, restaurer d’abord le thème et le plugin précédents, purger les caches et rejouer la recette. Si le code ne suffit pas, restaurer les fichiers et la base depuis le même point de sauvegarde. Ne jamais restaurer une base staging par-dessus la production. Après retour au vert, archiver l’incident et planifier une nouvelle fenêtre.

## H. Clôture

Après validation go, désactiver la maintenance, réactiver les tâches suspendues, vérifier une URL FR et une URL AR depuis un navigateur externe, surveiller les logs et conserver la sauvegarde pré-migration. Documenter la version effectivement en production et le point de rollback disponible.
