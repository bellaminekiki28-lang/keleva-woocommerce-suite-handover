# Fondation français-arabe et RTL — état staging

> **Périmètre :** staging Hostinger uniquement. Cette note documente la fondation technique livrée pour le français et l’arabe ; elle ne prétend pas que le catalogue existant est déjà traduit en arabe.

## État livré

| Élément | État | Release / preuve |
|---|---|---|
| Moteur de langues | TranslatePress Free actif ; français source et arabe additionnel | Polylang est conservée installée mais désactivée pour retour arrière ; aucun second moteur actif |
| Métadonnées médias | Produit et fichiers uniques | Les chaînes de rendu média peuvent être traduites sans dupliquer les fichiers ou le produit WooCommerce |
| Plugin Keleva | Domaine de traduction, dossier `languages/` et catalogue `.pot` | Keleva Woo Addons 0.6.12 ; 477 chaînes extraites |
| Thème public | Feuille RTL conditionnelle et Noto Sans Arabic | Keleva Woo 0.4.14 ; police Noto auto-hébergée et onglets produit RTL mobiles corrigés |
| Portail marchand | `lang="ar"`, `dir="rtl"`, styles RTL et Noto Sans Arabic | Keleva Woo Addons 0.6.15 ; police Noto auto-hébergée |
| Route marchande arabe | `/ar/espace-marchand/` est rendue localement | Keleva Woo Addons 0.6.13 |
| Mobile RTL | Vérification à 390 × 844 px sans débordement horizontal | Keleva Woo 0.4.14 : accueil, fiche variable et portail |

Les URL françaises existantes ne sont pas modifiées. La route de portail française reste `/espace-marchand/`, tandis que l’arabe peut utiliser `/ar/espace-marchand/`. Les deux routes restent sur le même domaine Hostinger et utilisent l’authentification Keleva séparée de wp-admin.

## Architecture de traduction

Le plugin `keleva-woo-addons` charge maintenant son domaine de traduction lors de l’initialisation WordPress et expose le catalogue source sous `wordpress/plugin/keleva-woo-addons/languages/keleva-woo-addons.pot`. Les traductions arabes réelles devront être fournies en `.po` puis compilées en `.mo` sous ce même dossier. Les chaînes PHP doivent toujours utiliser le domaine `keleva-woo-addons` et des placeholders traduisibles.

Le thème charge `assets/css/rtl.css` seulement lorsque WordPress indique une locale RTL. Les composants clés emploient des propriétés logiques, notamment `margin-inline`, `padding-inline`, `inset-inline-start/end`, `border-inline` et `text-align:start`. La couche RTL couvre le header, les actions, la recherche, les cartes, le panier latéral, les modales, le checkout et les boutons directionnels.

Le portail marchand rend son propre document HTML hors du thème. Il choisit donc explicitement `lang`, `dir`, les styles RTL et la police **Noto Sans Arabic** lorsque `is_rtl()` est vrai. La route est validée strictement avant tout rendu afin de ne pas intercepter d’autres URLs TranslatePress.

## Recette effectuée

| Surface | Format | Résultat |
|---|---:|---|
| Storefront arabe `/ar/` | 390 × 844 px | `lang=ar`, `dir=rtl`, police arabe présente, 63 contrôles interactifs inspectés, aucune zone interactive hors viewport, largeur de contenu 390 px |
| Portail arabe `/ar/espace-marchand/` | 390 × 844 px | `lang=ar`, `dir=rtl`, Noto Sans Arabic chargé, formulaire local présent, aucune zone interactive hors viewport, largeur de contenu 390 px |
| Correctif mobile | 390 × 844 px | Un débordement initial de 31 px sur quatre boutons « Choisir » de cartes variables a été corrigé dans 0.4.12 par une grille logique prix/action |
| Desktop arabe | navigateur staging | Route `/ar/`, feuille RTL et police arabe chargées ; aucune transaction, paiement, message WhatsApp, commande ou donnée client créée |

## Mise à jour : Noto Sans Arabic auto-hébergée

La couche RTL ne charge plus **Noto Sans Arabic** depuis Google. Le thème 0.4.14 contient les sous-ensembles `noto-sans-arabic-latin.woff2` et `noto-sans-arabic-arabic.woff2` sous `assets/fonts/`; le plugin 0.6.15 contient les mêmes ressources pour le portail hors thème. Une inspection de ressources sur les deux routes arabes montre que les fichiers Noto proviennent du même domaine Hostinger. Aucune ressource Noto externe n’a été détectée.

| Surface | Mobile 390 × 844 px | Desktop 1280 px | Ressource Noto vérifiée |
| --- | --- | --- | --- |
| Storefront `/ar/` | `scrollWidth=390`, aucun contrôle hors écran | Aucun débordement | `themes/keleva-woo/assets/fonts/` |
| Portail `/ar/espace-marchand/` | `scrollWidth=390`, aucun contrôle hors écran | Aucun débordement | `plugins/keleva-woo-addons/assets/fonts/` |

Les requêtes Google éventuellement visibles concernent encore DM Sans et Space Grotesk. Leur retrait ne fait pas partie de ce lot ciblé ; il doit faire l’objet d’une décision séparée pour préserver la typographie latine existante.

## Traduction gratuite du catalogue : TranslatePress Free

TranslatePress Free est le seul moteur de langues actif sur le staging ; il prend en charge le couple français-arabe et traduit le rendu client à partir d’une fiche WooCommerce unique. Une chaîne de recette a été persistée sur la fiche variable `brunch-bloom-avocado-toast` : `Informations et achat` est rendu comme `معلومات الشراء` uniquement sur `/ar/`, alors que la route française conserve la chaîne source. Le produit, ses variations, prix, stock et médias restent uniques, ce qui supprime le besoin de synchroniser des copies de données. [1] [2]

La recette mobile de cette fiche a relevé l’onglet WCFM `Enquiries` hors canevas à 390 px. Le correctif thème 0.4.14 répartit les onglets RTL sur deux colonnes : après correction, l’accueil arabe, la fiche variable arabe et le portail arabe ont tous `scrollWidth=390` et aucune zone interactive hors viewport.

## Checklist de reprise développeur

1. Laisser TranslatePress Free actif et Polylang désactivé ; ne jamais faire coexister les deux moteurs.
2. Traduire explicitement, dans l’éditeur visuel, la navigation, les catégories, les titres et descriptions produit, les valeurs d’options, le panier et le checkout. Certaines chaînes dynamiques apparaissent seulement après l’action WooCommerce correspondante. [2]
3. Ne créer aucun produit, variation, stock, prix ou média arabe séparé : le produit WooCommerce canonique est la source des données commerciales.
4. Utiliser d’abord une fixture produit variable et une catégorie de recette, puis vérifier chaque option, prix, stock et le panier en français et arabe sans checkout soumis.
5. Rejouer la matrice mobile 390 × 844 et desktop sur accueil, catégorie, produit variable, panier, checkout et portail marchand.
6. Ne jamais publier de secret, de mot de passe, de numéro WhatsApp, de donnée client ou d’archive staging dans Git.

## Références

[1] [TranslatePress — extension officielle WordPress.org](https://wordpress.org/plugins/translatepress-multilingual/)

[2] [TranslatePress — traduire les produits WooCommerce](https://translatepress.com/translate-woocommerce-products-translatepress/)

[3] [WordPress Developer Handbook — How to internationalize a plugin](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/)
