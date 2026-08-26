# Rapport final — consolidation FR/AR du staging

**Projet :** Keleva WooCommerce Suite  
**Environnement :** staging Hostinger uniquement  
**Date :** 26 août 2026  
**Auteur :** Manus AI

## Verdict

La consolidation technique française/arabe du staging est validée pour le périmètre contrôlé. **TranslatePress Free est le moteur de traduction unique actif**, le français reste la langue source et l’arabe est disponible sous `/ar/`. Le thème actif du staging est maintenant **Keleva Woo 0.4.17** et le plugin Keleva Woo Addons est en **0.6.16**. Cette release du thème corrige le dernier débordement mobile observé sur les boutons « Choisir » des cartes produit françaises et conserve les corrections RTL précédentes ; 0.6.16 corrige la portée du cookie de session du portail pour les chemins français et arabes.

Ce rapport ne déclare pas le projet conforme à 100 % du cahier des charges commercial. La fondation RTL, la structure de traduction gratuite, la majorité du storefront, les routes critiques et les contrôles de largeur sont validés. Une traduction éditoriale humaine complémentaire reste nécessaire sur certaines descriptions, catégories et chaînes dynamiques du panier/checkout. La recette authentifiée arabe a maintenant été effectuée avec un compte de staging dédié ; son identifiant et son mot de passe ne sont pas publiés.

## Consolidation TranslatePress Free

Les dernières chaînes visibles traitées dans l’éditeur TranslatePress sont les suivantes :

| Chaîne française | Rendu arabe enregistré | Surface |
|---|---|---|
| `Rechercher dans la boutique` | `البحث في المتجر` | Recherche storefront |
| `Rechercher` | `بحث` | Champ et action de recherche |
| `Panier` | `سلة التسوق` | En-tête et navigation panier |

TranslatePress Free reste la seule solution multilingue active. Polylang demeure installé uniquement comme possibilité de retour arrière, mais il n’est pas actif en parallèle. Le catalogue WooCommerce conserve une **fiche produit canonique unique** : les prix, stocks, variations, médias et options ne sont pas dupliqués pour l’arabe. Cette architecture correspond au rôle de TranslatePress qui traduit le rendu client sans créer une seconde base commerciale [1] [2].

## Déploiement du correctif mobile

Le téléversement de l’archive 0.4.17 n’a pas remplacé automatiquement le thème déjà installé. Le contrôle WordPress a confirmé que le thème actif était encore en 0.4.16. Le correctif a donc été appliqué explicitement dans le fichier `style.css` du **thème actif `keleva-woo`**, via l’éditeur WordPress de staging, puis vérifié dans la fiche du thème et dans la feuille CSS servie par Hostinger.

La release active comprend une grille mobile bornée pour `.keleva-product-card__buy`, des contraintes `min-width`/`min-inline-size`, le retour à la ligne des prix et des boutons, ainsi que la conservation des règles RTL et des onglets produit. La feuille distante sert désormais `Version: 0.4.17` et contient le marqueur `Mobile regression fix: keep CTA rows inside the viewport for FR and RTL catalog cards.`

## Recette automatisée finale

La matrice CDP a été rejouée après l’activation réelle de la release. Elle couvre l’accueil, la catégorie, la fiche produit variable, le panier, le checkout et le portail marchand en français et en arabe.

| Matrice | Viewport | Routes | Résultat |
|---|---:|---:|---|
| Mobile FR/AR | 390 × 844 px | 12 | `overflowing=false` sur les 12 routes ; aucun débordement de document ; aucune ressource Noto externe |
| Desktop FR/AR | 1280 px | 12 | `overflowing=false`, `offCanvas=[]` et aucune ressource Noto externe sur les 12 routes |
| Storefront arabe | 390 × 844 px | Accueil, catégorie, fiche, panier, checkout | `lang=ar`, `dir=rtl`, police arabe auto-hébergée et largeur contrôlée |
| Portail marchand arabe | 390 × 844 px et 1280 px | Connexion, dashboard et déconnexion | Session persistante après rechargement avec le compte dédié ; navigation, produits, commandes, options, variantes et déconnexion contrôlées |

Le rapport mobile brut conserve quelques coordonnées hors-canevas sur la fiche produit française et le checkout. L’analyse montre qu’elles concernent respectivement des onglets horizontaux supplémentaires et des champs WooCommerce dynamiques masqués par le navigateur, avec des positions extrêmes ; le `scrollWidth` du document reste 390 px. Elles ne correspondent donc pas à un débordement de page. Le rapport desktop ne conserve aucune alerte hors-canevas.

Les rapports JSON détaillés associés à ce jalon sont :

- `docs/FINAL_FR_AR_MOBILE_2026-08-26_POST_0417.json`
- `docs/FINAL_FR_AR_DESKTOP_2026-08-26_POST_0417.json`
- `docs/FINAL_PORTAL_AR_FINDINGS_2026-08-26.md`
- `docs/FINAL_PORTAL_AR_MUTATIONS_2026-08-26.json`

## Portail marchand et recette authentifiée

Le portail marchand est conçu comme un espace séparé de `wp-admin`, avec une authentification Keleva propre, une interface simplifiée pour les produits, le stock, les médias, les commandes et l’apparence, ainsi qu’un journal d’audit. La route française reste `/espace-marchand/` et la route arabe `/ar/espace-marchand/` lorsque la locale arabe est active.

Une session dédiée a été créée dans le réglage « Accès marchand Keleva » du staging, avec un mot de passe haché côté WordPress. Le correctif 0.6.16 utilise un cookie same-origin à la racine du site, ce qui couvre `/espace-marchand/` et `/ar/espace-marchand/` sans ouvrir de session wp-admin. Le compte et le mot de passe ne sont pas inclus dans le dépôt public.

La recette authentifiée a contrôlé le dashboard arabe, la modification réversible du prix et du stock du produit de démonstration, la création d’une catégorie temporaire, l’ajout d’un groupe de supplément payant, l’ajout d’une variante avec prix/stock/disponibilité, la modification puis restauration de la commande de recette `#333`, et la déconnexion. Le produit de démonstration `331`, sa variante et la catégorie temporaire ont ensuite été nettoyés du staging ; les produits actifs historiques et les commandes existantes ont été conservés.

## Ce qui reste ouvert

La traduction gratuite est techniquement en place, mais une dernière passe éditoriale doit encore vérifier chaque titre, description, catégorie, attribut, option, message de panier et message de checkout en arabe. Le portail conserve volontairement des libellés opérateur français sur certaines actions techniques simplifiées, tandis que les noms de produits visibles et les éléments RTL de l’interface arabe sont contrôlés. Les chaînes dynamiques doivent être sélectionnées dans TranslatePress après l’action WooCommerce qui les rend visibles, notamment la sélection d’une variation, l’ouverture du panier et l’affichage des notices de checkout [2].

Aucun paiement Stripe réel, aucune commande client réelle, aucun message WhatsApp et aucune donnée client de production n’ont été créés pendant cette recette. L’intégration Stripe reste à tester uniquement en mode de compatibilité lorsque le périmètre de recette le demandera. Le flux WhatsApp/n8n reste un chantier séparé nécessitant une URL HTTPS durable, des credentials Meta et une configuration de webhook hors dépôt.

## Recommandation de handover

Le staging peut être remis à un développeur pour la passe éditoriale arabe et la recette authentifiée du portail. Avant toute production, il faut exporter une sauvegarde complète, geler les versions du thème et du plugin, rejouer les matrices mobile/desktop, confirmer le compte marchand de recette, vérifier les traductions du panier et du checkout, puis suivre le runbook de déploiement et de restauration prévu dans le dépôt.

## Références

[1] [TranslatePress — extension officielle WordPress.org](https://wordpress.org/plugins/translatepress-multilingual/)  
[2] [TranslatePress — traduire les produits WooCommerce](https://translatepress.com/translate-woocommerce-products-translatepress/)  
[3] [WordPress Developer Handbook — internationalisation des extensions](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/)
