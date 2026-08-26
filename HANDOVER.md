# Guide de reprise — Keleva Woo

Ce document décrit les sources et l’état de référence publiés. Il est destiné à un développeur WordPress/WooCommerce. Les accès, identifiants et secrets ne figurent volontairement pas dans ce dépôt public.

## État de référence

| Élément | État source à reprendre | État staging validé |
| --- | --- | --- |
| Portail marchand | Portail PHP rendu par le plugin Keleva Woo Addons | Chemin natif `/espace-marchand/`, session Keleva distincte de wp-admin |
| Addons | Version source `0.6.15` | Version `0.6.15` installée sur le staging Hostinger |
| Thème et RTL | CSS RTL conditionnel, WOFF2 Noto Sans Arabic auto-hébergés | Thème `0.4.14` validé sur `/ar/`, fiche variable et portail `/ar/espace-marchand/` |
| Catalogue | Catégories, options, suppléments, limites et stock | Recettes de catégories/options/suppléments réalisées puis nettoyées |
| Variantes | Vraies variantes WooCommerce à un attribut guidé | Prix, stock et disponibilité par option validés, puis fixture supprimée |
| Console React | Sources conservées pour historique et audit | Hors parcours marchand actif ; ne pas la redéployer sans décision explicite |

> Le portail marchand actif est rendu directement par WordPress. Il ne dépend ni d’un proxy ni d’une application externe. Les anciens composants de console sont conservés pour la continuité du code, mais ne servent pas la route marchande actuelle.

## Fondation français-arabe : état et limite connue

TranslatePress Free est le moteur de langues unique actif sur le staging : le français est la source et l’arabe la seule langue additionnelle. Polylang est conservée installée mais désactivée pour un retour arrière ; ne réactivez jamais les deux moteurs ensemble. La couche RTL applique `lang="ar"`, `dir="rtl"`, la classe `translatepress-ar` et des propriétés CSS logiques au storefront et au portail. Les routes arabes servent Noto Sans Arabic depuis les fichiers WOFF2 du thème ou du plugin, sans requête Noto vers Google. La recette à 390 × 844 px et à 1280 px ne montre ni débordement horizontal ni contrôle hors canevas, y compris la fiche produit variable après le correctif 0.4.14.

> Cette fondation n’est pas encore une traduction complète du commerce. Les produits WooCommerce, leurs variantes, prix, stocks et médias restent **canoniques et uniques** ; TranslatePress traduit le rendu client sans copie de données. Une chaîne de recette a été persistée en arabe sur une fiche variable. Poursuivez avec la recette contrôlée de [`FREE_TRANSLATION_STAGING_2026-08-26.md`](wordpress-package/docs/FREE_TRANSLATION_STAGING_2026-08-26.md), sans associer Polylang, WPML ou un second produit arabe.

## Architecture utile

| Chemin | Responsabilité |
| --- | --- |
| `wordpress-package/wordpress/theme/keleva-woo` | Storefront WordPress/WooCommerce, templates, palettes, accessibilité et rendu public |
| `wordpress-package/wordpress/plugin/keleva-woo-addons` | Portail marchand, authentification Keleva, catalogue, options, variantes, commandes, audit et intégrations WooCommerce |
| `includes/class-native-merchant-portal.php` | Connexion, permissions, produits, catégories, options et variantes depuis le portail natif |
| `includes/class-product-options.php` | Groupes d’options, extras, suppléments et limites de sélection |
| `includes/class-dashboard-settings.php` | Réglages d’intégration wp-admin et isolation des notices tierces sur cet écran |
| `wordpress-package/wordpress-dev` | Scripts de recette, utilitaires et tests WordPress locaux/staging |
| `merchant-console` | Ancienne console React/TypeScript et BFF, à usage historique et de reprise technique |

## Fonctionnalités livrées

Le portail marchand permet de gérer des produits de test, les prix, le stock, les catégories, des photos et leur libellé alternatif, l’apparence, les commandes de staging et les groupes d’options. Les opérations marchandes utilisent un compte Keleva local et ne donnent jamais accès à wp-admin.

Les options et suppléments ne sont pas des variantes stockées : ils ajoutent ou non un montant au produit parent, avec obligation et maximum de choix. Pour des formats qui doivent posséder chacun leur prix, quantité et disponibilité, le panneau **Variantes avec stock** crée de vraies `WC_Product_Variation`. La première interface est volontairement limitée à un attribut unique et à seize options au plus. Les produits variables externes non créés par Keleva restent protégés contre toute réécriture depuis le portail.

La dernière recette a validé une option disponible à **45 MAD / stock 3** et une option indisponible à **60 MAD / stock 0**, puis une mutation de la première à **46 MAD / stock 2**. Le parent de recette et ses enfants ont ensuite été supprimés.

## Démarrer en local

1. Préparez WordPress, WooCommerce et Elementor dans un environnement local dédié.
2. Copiez le thème depuis `wordpress-package/wordpress/theme/keleva-woo` et activez-le.
3. Copiez le plugin depuis `wordpress-package/wordpress/plugin/keleva-woo-addons` et activez-le.
4. Configurez exclusivement des identifiants, webhooks et clés de développement locaux ou sandbox, dans les réglages WordPress ou dans l’environnement. Ne créez aucun `.env` versionné.
5. Utilisez les guides `wordpress-package/README.md` et `wordpress-package/wordpress-dev/README.md` pour les prérequis et les recettes détaillées.

La console React historique peut être vérifiée comme suit :

```bash
cd merchant-console
pnpm install
export KELEVA_CONNECTION_ENCRYPTION_KEY="<clé-base64-de-32-octets-pour-le-test>"
pnpm check
pnpm test --run
pnpm build
```

La variable de chiffrement doit être fournie par l’environnement de test ou de déploiement et ne doit jamais être inscrite dans un fichier versionné. La console n’est pas nécessaire au fonctionnement du portail WordPress natif ; toute décision de la réutiliser comme application autonome doit être séparée du parcours WordPress actif.

## Vérifier et déployer

Avant chaque déploiement du plugin :

```bash
find wordpress-package/wordpress/plugin/keleva-woo-addons -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check
```

Après déploiement staging, contrôlez au minimum : la connexion Keleva sur `/espace-marchand/`, la séparation de wp-admin, l’ajout et l’édition d’un produit de test, le rendu d’une option ou variante concernée, et le nettoyage de toute fixture. Aucun paiement, message WhatsApp, commande réelle ou donnée client ne doit être utilisé dans une recette de code.

Pour créer une archive de plugin installable :

```bash
cd wordpress-package/wordpress/plugin
zip -qr ../../../../keleva-woo-addons-release.zip keleva-woo-addons
```

Téléversez l’archive avec **Extensions → Ajouter une extension → Téléverser une extension**, puis utilisez le remplacement sécurisé proposé par WordPress. Ne déployez jamais sur production sans sauvegarde, fenêtre de changement, plan de restauration et recette finale séparée.

## Sécurité et priorités

La session marchande Keleva est opaque, limitée au chemin marchand, et distincte des cookies wp-admin. Les permissions sont métiers : catalogue, prix, inventaire, commandes et palettes. Toute modification de ce modèle doit ajouter un test de refus de permission.

Les fonctions de paiement, WhatsApp, n8n, Merchant Center et les clés WooCommerce exigent une configuration séparée avec secrets et coordonnées opérationnelles hors Git. Le bouton WhatsApp ne s’affiche que lorsqu’un numéro est configuré dans WordPress. Ces fonctions doivent être validées par des preuves sandbox propres.

Les prochaines améliorations pertinentes sont la duplication guidée d’un produit avec ses variantes Keleva, une gestion média avec recadrage et validation de taille, et une matrice de recette mobile/navigateurs pour le portail natif.
