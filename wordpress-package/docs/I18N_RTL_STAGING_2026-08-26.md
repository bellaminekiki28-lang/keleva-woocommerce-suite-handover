# Fondation français-arabe et RTL — état staging

> **Périmètre :** staging Hostinger uniquement. Cette note documente la fondation technique livrée pour le français et l’arabe ; elle ne prétend pas que le catalogue existant est déjà traduit en arabe.

## État livré

| Élément | État | Release / preuve |
|---|---|---|
| Langues WordPress | Français et arabe ajoutés avec Polylang | Assistant Polylang staging terminé ; contenus existants attribués au français |
| Métadonnées médias | Traduction activée | Titres, textes alternatifs, légendes et descriptions peuvent être localisés sans dupliquer les fichiers |
| Plugin Keleva | Domaine de traduction, dossier `languages/` et catalogue `.pot` | Keleva Woo Addons 0.6.12 ; 477 chaînes extraites |
| Thème public | Feuille RTL conditionnelle et Noto Sans Arabic | Keleva Woo 0.4.13 ; police Noto auto-hébergée |
| Portail marchand | `lang="ar"`, `dir="rtl"`, styles RTL et Noto Sans Arabic | Keleva Woo Addons 0.6.15 ; police Noto auto-hébergée |
| Route marchande arabe | `/ar/espace-marchand/` est rendue localement | Keleva Woo Addons 0.6.13 |
| Mobile RTL | Vérification à 390 × 844 px sans débordement horizontal | Keleva Woo 0.4.12 |

Les URL françaises existantes ne sont pas modifiées. La route de portail française reste `/espace-marchand/`, tandis que l’arabe peut utiliser `/ar/espace-marchand/`. Les deux routes restent sur le même domaine Hostinger et utilisent l’authentification Keleva séparée de wp-admin.

## Architecture de traduction

Le plugin `keleva-woo-addons` charge maintenant son domaine de traduction lors de l’initialisation WordPress et expose le catalogue source sous `wordpress/plugin/keleva-woo-addons/languages/keleva-woo-addons.pot`. Les traductions arabes réelles devront être fournies en `.po` puis compilées en `.mo` sous ce même dossier. Les chaînes PHP doivent toujours utiliser le domaine `keleva-woo-addons` et des placeholders traduisibles.

Le thème charge `assets/css/rtl.css` seulement lorsque WordPress indique une locale RTL. Les composants clés emploient des propriétés logiques, notamment `margin-inline`, `padding-inline`, `inset-inline-start/end`, `border-inline` et `text-align:start`. La couche RTL couvre le header, les actions, la recherche, les cartes, le panier latéral, les modales, le checkout et les boutons directionnels.

Le portail marchand rend son propre document HTML hors du thème. Il choisit donc explicitement `lang`, `dir`, les styles RTL et la police **Noto Sans Arabic** lorsque `is_rtl()` est vrai. La route est validée strictement avant tout rendu afin de ne pas intercepter d’autres URLs Polylang.

## Recette effectuée

| Surface | Format | Résultat |
|---|---:|---|
| Storefront arabe `/ar/` | 390 × 844 px | `lang=ar`, `dir=rtl`, police arabe présente, 63 contrôles interactifs inspectés, aucune zone interactive hors viewport, largeur de contenu 390 px |
| Portail arabe `/ar/espace-marchand/` | 390 × 844 px | `lang=ar`, `dir=rtl`, Noto Sans Arabic chargé, formulaire local présent, aucune zone interactive hors viewport, largeur de contenu 390 px |
| Correctif mobile | 390 × 844 px | Un débordement initial de 31 px sur quatre boutons « Choisir » de cartes variables a été corrigé dans 0.4.12 par une grille logique prix/action |
| Desktop arabe | navigateur staging | Route `/ar/`, feuille RTL et police arabe chargées ; aucune transaction, paiement, message WhatsApp, commande ou donnée client créée |

## Mise à jour : Noto Sans Arabic auto-hébergée

La couche RTL ne charge plus **Noto Sans Arabic** depuis Google. Le thème 0.4.13 contient les sous-ensembles `noto-sans-arabic-latin.woff2` et `noto-sans-arabic-arabic.woff2` sous `assets/fonts/`; le plugin 0.6.15 contient les mêmes ressources pour le portail hors thème. Une inspection de ressources sur les deux routes arabes montre que les fichiers Noto proviennent du même domaine Hostinger. Aucune ressource Noto externe n’a été détectée.

| Surface | Mobile 390 × 844 px | Desktop 1280 px | Ressource Noto vérifiée |
| --- | --- | --- | --- |
| Storefront `/ar/` | `scrollWidth=390`, aucun contrôle hors écran | Aucun débordement | `themes/keleva-woo/assets/fonts/` |
| Portail `/ar/espace-marchand/` | `scrollWidth=390`, aucun contrôle hors écran | Aucun débordement | `plugins/keleva-woo-addons/assets/fonts/` |

Les requêtes Google éventuellement visibles concernent encore DM Sans et Space Grotesk. Leur retrait ne fait pas partie de ce lot ciblé ; il doit faire l’objet d’une décision séparée pour préserver la typographie latine existante.

## Prérequis bloquant : catalogue WooCommerce bilingue

Polylang gratuit sert de fondation de langues et reste activé sur le staging. **Il ne suffit pas** pour traduire et synchroniser proprement les produits WooCommerce, les variations, les catégories et les données commerce. La suite doit utiliser une seule solution : **Polylang Business Pack**, son extension officielle WooCommerce. Elle est conçue pour traduire les produits, catégories et attributs de WooCommerce sans faire coexister une solution concurrente. [1]

Après fourniture de la licence Business Pack, le développeur doit installer l’extension officielle, activer la compatibilité WooCommerce, puis traduire progressivement les produits, catégories, suppléments et variantes. Aucune traduction de produit ne doit être créée manuellement avant cette étape, afin d’éviter les doublons et les relations de variations incohérentes.

## Checklist de reprise développeur

1. Installer **uniquement** l’extension officielle Polylang for WooCommerce issue du Business Pack, sans WPML ni second plugin multilingue.
2. Vérifier les réglages de synchronisation pour les catégories, attributs, variations, stock, prix, médias et slugs avant toute traduction.
3. Produire les traductions professionnelles arabe des pages publiques, produits, options et 477 chaînes du plugin ; compiler les fichiers `.po` en `.mo`.
4. Créer une seule fixture produit test-only avec une variation disponible et une indisponible, puis vérifier le prix, le stock et le panier en français et arabe sans checkout.
5. Rejouer la matrice mobile 390 × 844 et desktop sur accueil, catégorie, produit variable, panier, checkout et portail marchand.
6. Ne jamais publier de secret, de mot de passe, de numéro WhatsApp, de donnée client ou d’archive staging dans Git.

## Références

[1] [Polylang — Managing WooCommerce products](https://polylang.pro/documentation/support/guides/managing-products/)

[2] [WordPress Developer Handbook — How to internationalize a plugin](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/)
