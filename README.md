# Keleva Woo — dossier de reprise technique

Ce dépôt rassemble les **sources versionnables** du travail Keleva : le thème et les extensions WordPress/WooCommerce/Elementor, les scripts de vérification PHP et Playwright, ainsi que la console marchande React/TypeScript avec son BFF. Il est destiné à une reprise de développement et d’audit ; il ne constitue pas une déclaration de conformité intégrale.

> **État de décision.** La livraison locale est fortement testée sur le périmètre décrit dans les documents d’audit, mais la conformité globale demeure incomplète tant que les dépendances réelles de paiement, Merchant Center, API WooCommerce, webhooks, RUM/CWV p75, appareils physiques, pentest et stabilité Hostinger ne sont pas prouvées.

## Structure

| Répertoire | Contenu | Point de départ |
| --- | --- | --- |
| `wordpress-package/wordpress/theme/keleva-woo` | Thème storefront WordPress PHP SSR | `style.css`, `functions.php`, `inc/` et `woocommerce/` |
| `wordpress-package/wordpress/plugin/keleva-woo-addons` | Widgets Elementor, endpoints Dashboard, listes sauvegardées, audit et rate limiting | `keleva-woo-addons.php` et `includes/` |
| `wordpress-package/wordpress-dev` | Scripts WordPress, régressions PHP, Playwright et recettes staging | `README.md`, `tests/` et `qa/` |
| `wordpress-package/docs` | Inventaire staging, matrice CDC, décisions d’architecture et preuves de test | Commencer par `STAGING_HOSTINGER_INVENTORY_2026-08-24.md` |
| `merchant-console` | Console React/TypeScript et BFF Express/tRPC | `README.md`, `docs/ARCHITECTURE.md`, `server/`, `client/` |

Les dépendances installées (`vendor`, `node_modules`), caches, builds, journaux de session, archives installables et captures de preuve ont volontairement été exclus. Elles sont régénérables et ne doivent pas être publiées sans nouvelle revue.

## Démarrage local

### WordPress et WooCommerce

Préparez une installation WordPress locale avec WooCommerce et Elementor, puis installez le thème et l’extension depuis les sources de `wordpress-package/wordpress/`. Les outils PHP du package sont décrits dans `wordpress-package/README.md` et dans `wordpress-package/wordpress-dev/README.md`.

Les validations ciblées utilisent notamment PHP lint, PHPCS, PHPStan, Plugin Check, WP-CLI et Playwright. Les tests de checkout, favoris et widgets ajoutent puis retirent des données de recette : utilisez une base locale ou de staging dédiée, jamais une boutique de production.

### Console marchande

Dans `merchant-console`, installez les dépendances avec `pnpm install`, puis lancez les contrôles suivants :

```bash
pnpm check
pnpm test --run
pnpm build
pnpm audit
```

Les variables de session, base de données et intégration OAuth doivent être fournies par l’environnement de déploiement ; ne créez pas de fichier `.env` versionné. L’architecture, les rôles, les imports, les webhooks et leurs limites de preuve figurent dans `merchant-console/docs/ARCHITECTURE.md`.

## Versions et état connu

| Composant | État source du dépôt | État staging connu avant indisponibilité HTTPS |
| --- | --- | --- |
| Keleva Woo | 0.4.10 | Déployé et hardening REST/auteur/XML-RPC prouvé à l’instant du contrôle |
| Keleva Woo Addons | 0.5.6 | 0.5.5 actif ; 0.5.6 validé localement mais **non déployé** |
| Console marchande | React/TypeScript, BFF et tests Vitest | Non déployée contre une API WooCommerce réelle |

Addons 0.5.6 introduit un rate limiting REST ciblé, basé sur une fenêtre de 60 secondes et des identifiants HMAC. Son contrat local traverse le serveur REST WordPress réel et valide cinq tentatives login suivies d’un `429`, d’un `Retry-After` et de `Cache-Control: no-store`. La preuve publique demeure à réaliser après retour d’un HTTPS Hostinger stable.

## Reprise prioritaire

Les tâches en cours et leurs états sont conservés dans `merchant-console/todo.md`. Le développeur repreneur doit prioriser les points suivants :

1. Rétablir une disponibilité HTTPS stable du staging Hostinger, déployer Addons 0.5.6 et archiver le contrôle public de rate limiting.
2. Identifier la cause des bascules répétées entre Keleva Woo et RestoCommerce en utilisant le journal `theme_switch` Addons et les journaux Hostinger.
3. Configurer de vraies clés WooCommerce limitées, valider imports, workers, webhooks et rollback contre un HTTPS accessible.
4. Obtenir les comptes et clés sandbox nécessaires aux moyens de paiement Maroc, sans jamais stocker de données de carte.
5. Finaliser Merchant Center, RUM/CWV p75, essais sur appareils physiques et pentest indépendant.

## Sécurité et publication

Le dépôt public ne doit contenir ni mot de passe, clé API, Consumer Key/Secret WooCommerce, secret webhook, fichier `.env`, cookie, journal de navigateur, base locale, archive de sauvegarde ou capture de session. Avant tout nouveau push, exécutez un scan de secrets et inspectez les changements proposés.

Le seul avis pnpm connu au moment de la reprise est un avis moderate transitif d’`esbuild@0.18.20` via `drizzle-kit@0.31.10` et `@esbuild-kit/core-utils@3.3.2`, outil de migration hors bundle de production. Il est documenté dans `merchant-console/docs/ARCHITECTURE.md` et reste ouvert jusqu’à une correction amont compatible.

## Licence

Le thème et les extensions WordPress sont publiés sous licence GPL-2.0-or-later conformément à leur intégration WordPress. La console conserve ses licences déclarées dans ses propres manifestes. Vérifiez les obligations des dépendances avant toute redistribution commerciale.
