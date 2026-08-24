# Audit de tokenisation des couleurs — Keleva Woo

> **Périmètre :** laboratoire WordPress/WooCommerce local. Cet audit distingue les valeurs qui définissent volontairement une palette, les données éditoriales de produits et les règles de composants qui doivent impérativement hériter de la palette active.

## Résultat

Les surfaces fonctionnelles du storefront et des options WooCommerce s’appuient désormais sur les jetons centraux `--bg`, `--surface`, `--ink`, `--muted`, `--subtle`, `--line`, `--accent`, `--accent-strong`, `--success`, `--media` et `--benefit`. Les ombres, calques translucides, états de focus et fonds médias utilisent des `color-mix()` ou les mêmes jetons, ce qui évite qu’un carré clair reste visible lors de l’application de la palette Onyx.

| Surface contrôlée | Fichiers | État après audit | Validation |
| --- | --- | --- | --- |
| Storefront, drawer, quick view, galerie, panier et checkout | `themes/keleva-woo/style.css`, `assets/css/velora-parity.css`, `inc/palette.php` | Les règles de composants héritent des jetons ; les valeurs hexadécimales résiduelles dans `:root` sont uniquement la définition de secours Velora. | Recette Playwright multi-moteurs et Axe AA sans violation. |
| Options storefront et restauration | `plugins/keleva-woo-addons/assets/css/product-options.css`, `restaurant-extras.css` | Composants convertis en variables palette avec fallback de compatibilité lorsqu’un thème tiers ne définit pas les jetons. | Quick view et ajout configuré validés dans Playwright. |
| Options dans l’administration WordPress | `product-options-admin.css` | Valeurs regroupées sous variables `--keleva-admin-option-color-*` ; elles ne pilotent pas le storefront. | Contrôle statique du fichier. |
| Palettes proposées | `themes/keleva-woo/inc/palette.php` | Les hexadécimaux y constituent la source de vérité déclarative des cinq palettes et ne sont pas des couleurs de composants dispersées. | `tests/palette-contract-test.php` et recette API d’apparence. |
| Pastilles éditoriales des cartes | `woocommerce/content-product.php` | Couleurs de contenu propres aux références produits ; elles ne sont jamais utilisées pour les CTA, fonds, textes, bordures, états ou surfaces du système de design. | Revue statique ciblée. |

## Exemptions explicites

Les occurrences restantes ne sont pas des régressions de la tokenisation. Elles relèvent exclusivement de trois catégories : la définition primaire des cinq palettes, les fallback CSS garantissant la lisibilité si l’extension est utilisée avec un thème tiers, et les teintes éditoriales de petites pastilles produit. Ces valeurs sont séparées des rôles visuels globaux et ne peuvent donc pas empêcher le changement de palette du storefront.

## Commandes de contrôle exécutées

```bash
node /home/ubuntu/keleva-local-wordpress/bin/tokenize-option-css.mjs
node /home/ubuntu/keleva-local-wordpress/bin/tokenize-theme-css.mjs
node /home/ubuntu/keleva-local-wordpress/bin/tokenize-theme-residuals.mjs
pnpm run e2e
```

La recette après migration a confirmé Chromium, Firefox et WebKit pour la recherche, le tiroir, le quick view et le passage express au checkout, avec **0 violation Axe**. Les valeurs racines ont en outre été contrôlées pour prévenir toute auto-référence CSS.
