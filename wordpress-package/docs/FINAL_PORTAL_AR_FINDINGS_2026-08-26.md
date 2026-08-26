# Recette finale — portail marchand arabe

**Environnement :** staging Hostinger uniquement  
**Route contrôlée :** `/ar/espace-marchand/`  
**Version active :** Keleva Woo Addons 0.6.16  

## Authentification et session

Un compte marchand de recette dédié a été configuré dans « Accès marchand Keleva ». La connexion arabe affiche le dashboard, une recharge propre conserve la session, et la déconnexion affiche la confirmation attendue. Le correctif 0.6.16 utilise un cookie same-origin à la racine du site, couvrant les routes françaises et arabes sans réutiliser le cookie de `wp-admin`.

Aucun identifiant, mot de passe, token, valeur de cookie ou donnée client n’est inclus dans ce dépôt public. Le mot de passe reste uniquement dans l’environnement de staging.

## Surface contrôlée

La smoke test authentifiée a contrôlé les cinq sections du portail : ajout guidé, produits et stock, commandes, apparence, catégories et options. Le rendu arabe conserve `lang=ar`, `dir=rtl`, la police arabe auto-hébergée, les six cartes de catalogue finales et l’absence de débordement de document aux largeurs mobile et desktop prévues.

La recette métier a ensuite vérifié dans le navigateur principal authentifié la modification réversible du prix et du stock d’une fixture, la création d’une catégorie temporaire, la création d’un groupe de supplément payant (+5,00 MAD), l’ajout d’une variante avec prix, stock et disponibilité, la mutation puis la restauration de la commande de staging `#333`, et la déconnexion.

## Nettoyage après recette

Le groupe d’option a été retiré, la fixture de démonstration contenant la variante a été déplacée dans la corbeille WordPress staging, et la catégorie temporaire sans produit a été supprimée. La liste finale du portail ne montre plus la fixture de démonstration ; les produits actifs historiques et les commandes existantes ont été conservés.

## Limites

Cette recette ne valide pas un paiement réel, Stripe en production, WhatsApp, n8n, une commande client réelle ou une donnée client. Les libellés opérateur du portail restent principalement en français sur certaines actions, tandis que la route, la direction, la typographie et les données catalogue arabes visibles sont contrôlées.
