# Keleva Woo — Package développeur

Ce bundle contient les sources **0.4.1 local-lab** de Keleva Woo, testées dans un WordPress 7.1 et WooCommerce 11.0.1 réels du sandbox. Il est conçu pour permettre à un développeur WordPress de réinstaller, auditer, adapter et faire évoluer l’ensemble sans dépendre d’un tableau de bord de préproduction.

> Les clés, jetons, secrets de webhook et données de préproduction ne sont volontairement pas inclus. Ils doivent être recréés pour chaque environnement.

## Contenu du bundle

| Chemin | Contenu | Usage |
| --- | --- | --- |
| `installables/keleva-woo-0.4.1-local-lab.zip` | Thème WordPress installable | À téléverser dans **Apparence → Thèmes** |
| `installables/keleva-woo-addons-0.4.1-local-lab.zip` | Extension WordPress installable | À téléverser dans **Extensions → Ajouter une extension** |
| `wordpress/theme/keleva-woo/` | Sources décompressées du thème | Revue de code et développement local |
| `wordpress/plugin/keleva-woo-addons/` | Sources décompressées de l’extension | Revue de code et développement local |
| `console/keleva-native-console.html` | Console marchand native locale | À insérer dans une page WordPress dédiée, après configuration de session |
| `docs/` | Guides d’architecture, d’installation et de sécurité | Référence technique et fonctionnelle |
| `preuves/lot-0/` | Rapports QA, Axe et Lighthouse locaux | Vérification fonctionnelle et performance |
| `verification/` | Manifestes de contrôle et sommes SHA-256 | Vérification d’intégrité |

## Prérequis

| Dépendance | Version ou rôle |
| --- | --- |
| WordPress | **6.7+** recommandé ; la préproduction a été testée sur WordPress 7.0/7.1 |
| PHP | **8.2+** |
| WooCommerce | Requis pour catalogue, variantes, panier et checkout |
| Elementor | Optionnel pour le storefront natif ; requis pour les widgets Keleva et les layouts Elementor |
| HTTPS | Obligatoire pour les webhooks et recommandé pour toute console marchande |

## Installation rapide

1. Installez et activez WooCommerce. Installez Elementor si vous souhaitez utiliser les widgets et les layouts Elementor de Keleva.
2. Téléversez `installables/keleva-woo-0.4.1-local-lab.zip` via **Apparence → Thèmes → Ajouter un thème**, puis activez **Keleva Woo**.
3. Téléversez `installables/keleva-woo-addons-0.4.1-local-lab.zip` via **Extensions → Ajouter une extension**, puis activez **Keleva Woo Addons**.
4. Vérifiez dans **WooCommerce → Keleva Dashboard** la configuration de la clé de dashboard, des webhooks et du journal d’audit.
5. Créez une page nommée par exemple `Keleva Merchant` avec le slug `keleva-merchant`, puis insérez le contenu de `console/keleva-native-console.html` dans un bloc HTML personnalisé.
6. Ajoutez une clé de dashboard propre à l’environnement et testez l’accès avec un produit brouillon, jamais avec un produit commercial existant.

## Console marchande native

La console incluse est un front-end HTML/CSS/JavaScript autonome qui se connecte à l’extension via une session opaque HTTP-only, un jeton CSRF et un contrôle d’origine. Elle apporte catalogue paginé/recherchable, catégories, options, apparence à cinq palettes, ventes, coupons, notifications et feuilles mobile accessibles.

Le mot de passe, les tokens de rotation et les secrets de webhook ne sont **pas** codés dans le fichier. Toute valeur de configuration doit être injectée hors code depuis l’environnement cible. La console locale est déjà conçue pour la session HTTP-only ; testez d’abord le parcours sur un produit brouillon.

## Points d’intégration principaux

| Surface | Fichier source principal | Rôle |
| --- | --- | --- |
| Design Velora, responsive et quick view | `wordpress/theme/keleva-woo/style.css` | Tokens, layout, composants storefront |
| Comportement sans jQuery | `wordpress/theme/keleva-woo/assets/js/storefront.js` | Quick view, panier, variantes et options client |
| Templates WooCommerce | `wordpress/theme/keleva-woo/woocommerce/` | Archive, carte, fiche, image et achat variable |
| Cache et médias | `wordpress/theme/keleva-woo/inc/cache.php`, `inc/media.php` | Headers prudents, AVIF/WebP/fallback |
| Endpoint dashboard REST | `wordpress/plugin/keleva-woo-addons/includes/class-dashboard-endpoint.php` | Produits, statuts, variantes, groupes et import photo |
| Options de produits | `wordpress/plugin/keleva-woo-addons/includes/class-product-options.php` | Boutons, radio, cases à cocher et plafonds 1–4 |
| Sécurité et audit | `class-dashboard-settings.php`, `class-dashboard-audit-log.php` | Sessions opaques, CSRF, rotation, HMAC et journal |

## Contrôles avant mise en production

Ne réutilisez jamais une clé de préproduction en production. Générez une clé active et une clé précédente distinctes, configurez un endpoint webhook HTTPS, vérifiez une signature HMAC SHA-256, puis testez la rotation. Conservez une sauvegarde complète, testez toutes les actions de statut sur un brouillon, et relisez `docs/DEPLOIEMENT_SECURITE_PRODUCTION.md` avant toute ouverture commerciale.

## Limites et éléments volontairement exclus

Le contenu WordPress, les médias, les produits, les commandes, les comptes, les données de configuration et les secrets ne sont pas exportés dans ce bundle. Les produits de recette et leurs brouillons restent uniquement sur la préproduction ; ils ne font pas partie du package d’installation.

## Vérification

Après téléchargement, exécutez la commande suivante depuis la racine du bundle :

```bash
sha256sum -c verification/SHA256SUMS.txt
```

Les archives installables sont générées à partir des mêmes sources décompressées présentes dans `wordpress/`. Consultez également `docs/MATRICE_CDC_ULTRA_PREMIUM_20260823.md` : les exigences indiquées **Partiel** restent des chantiers locaux ouverts.
