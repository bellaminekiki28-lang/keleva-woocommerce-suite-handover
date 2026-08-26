# Keleva Woo 0.4.19 — release réconciliée

## Décision

La source du thème contenue dans le dépôt portait l’en-tête `0.4.17`, alors que les journaux de staging attribuaient à la release `0.4.19` les derniers correctifs des notices AJAX et de l’interface bilingue. Aucun fichier autonome 0.4.19 n’a été retrouvé localement. L’audit du code confirme que la source unique contient déjà les correctifs documentés ; l’en-tête est donc aligné sur **0.4.19** plutôt que de maintenir deux identités de release pour le même état de code.

## Correctifs inclus dans l’état réconcilié

| Domaine | Contrôle |
|---|---|
| RTL | Propriétés logiques, onglets produit et navigation arabe |
| Mobile | CTA et lignes prix/action bornés dans le viewport 390 px |
| Traduction dynamique | Dictionnaires des notices d’option et d’ajout au panier |
| Typographie | Noto Sans Arabic auto-hébergée |
| Palettes | Tokens centralisés, `accent-text` et contrastes corrigés |
| Boutique | Fiche variable, panier persistant et checkout FR/AR |

## Vérifications requises sur Hostinger

Le prochain développeur doit vérifier l’en-tête de la feuille `style.css` servie, le fichier réellement actif dans WordPress, les checksums de l’archive et les 12 routes FR/AR à 390 × 844 px et 1280 px. Si la feuille distante indique une autre version, ne pas déclarer la production prête avant remplacement contrôlé et nouvelle recette.

Cette réconciliation ne modifie ni les traductions TranslatePress stockées en base, ni les produits, ni les commandes, ni les réglages de production.
