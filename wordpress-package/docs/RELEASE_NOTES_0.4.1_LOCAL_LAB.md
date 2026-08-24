# Keleva Woo 0.4.1 local-lab — notes de remise

Cette remise regroupe les évolutions validées dans le laboratoire WordPress/WooCommerce local, sans modification de la préproduction Hostinger après l’adoption du flux local-first.

| Domaine | Évolutions intégrées | Preuve principale |
| --- | --- | --- |
| Console mobile | Feuilles plein écran, focus, actions collantes, confirmations accessibles et en-têtes tactiles | Playwright 360/390/430 px |
| Options | Limites 1–4, cases imposées dès deux choix, inclus/suppléments et aperçu client | Playwright Chromium |
| Apparence | Cinq palettes, prévisualisation storefront non persistante au survol/focus, application/réinitialisation via session/CSRF, contraste Onyx | Playwright : iframe Onyx, Velora intact avant confirmation, reset ; contrôle PHP de non-persistance |
| Catalogue et catégories | Recherche serveur, pagination contrôlée, devise WooCommerce, couverture importée et réordonnancement accessible | API + Playwright ; brouillons et catégories de recette nettoyés |
| Storefront | Galerie réelle à trois médias, tiroir panier avec fermeture tactile horizontale, restitution du focus et cross-sell WooCommerce conditionnel, préchargement quick view au survol/focus et à la visibilité mobile, états 404/panier/recherche/thank-you, progression de navigation | Chromium, Firefox et WebKit ; Axe sans violation |
| Back-office | Filtres commandes, détail avec adresse/options, cycle de statut, KPI jour/semaine/attente/top produits, coupons avec échéance, badge combinant attente de commande/rupture de stock et skeletons accessibles | API + Playwright ; fixtures de commande, coupon et rupture supprimées |
| Qualité | Tokens consolidés, polices préchargées, Axe sans violation dans la recette | Rapport QA et audit jetons |

Le rapport Playwright/Axe joint confirme Chromium, Firefox et WebKit sans erreur de navigateur, 24 contrôles Axe réussis, ainsi que les assertions explicites de prévisualisation R19, de fermeture gestuelle R26, de cross-sell WooCommerce R28, de préchargement mobile R33, de skeleton accessible S3, filtres, détail de commande, badge combiné attente/rupture et échéance de coupon. La matrice CDC incluse documente les deux écarts locaux qui restent ouverts. Aucun identifiant, cookie, base de données, média client ou secret de laboratoire ne fait partie de cette archive.
