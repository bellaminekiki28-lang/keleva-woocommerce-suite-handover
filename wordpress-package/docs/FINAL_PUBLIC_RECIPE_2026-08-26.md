# Recette publique finale FR/AR — Keleva staging

Date de vérification : **26 août 2026**. Environnement : staging Hostinger uniquement, sans paiement réel, sans commande client, sans saisie de coordonnées et sans donnée de production.

## Résultats fonctionnels

| Langue | Viewport | Parcours contrôlé | Résultat |
| --- | --- | --- | --- |
| Français | Desktop 1280 px | Accueil → boutique → fiche variable → panier → checkout | Validé. La fiche `Avocado toast` accepte l’option `Pesto`, le panier AJAX conserve l’option et le checkout affiche les champs et l’état de paiement sans soumission. |
| Arabe | Desktop 1280 px | Accueil → boutique → fiche variable → panier → checkout | Validé. Les routes `/ar/` conservent `lang="ar"`, `dir="rtl"`, les libellés arabes, la fiche variable, le panier et le checkout localisés. |
| Français | Mobile 390 × 844 px | Accueil, catalogue, fiche variable, panier, checkout | Validé dans la matrice mobile des releases du thème 0.4.19, sans débordement horizontal bloquant. |
| Arabe | Mobile 390 × 844 px | Accueil, catalogue, fiche variable, panier, checkout | Validé dans la matrice RTL des releases du thème 0.4.19, sans débordement horizontal ni contrôle hors canevas. |

## Vérifications spécifiques

Le catalogue public rend les catégories et produits attendus. La fiche variable conserve un produit WooCommerce canonique et unique ; TranslatePress Free traduit le rendu sans dupliquer le prix, le stock, les variantes ou les médias. Les notices AJAX d’option et d’ajout au panier sont localisées par le correctif du thème.

Le checkout français et arabe expose un parcours invité, les champs d’e-mail et de livraison, le bouton de commande et l’état « aucun moyen de paiement configuré » sur le staging. Aucun formulaire n’a été soumis. Le panier expose également l’action WhatsApp de paiement à la livraison, mais aucun message n’a été envoyé pendant la recette.

## Portail marchand natif

L’entrée validée est `https://aliceblue-bison-433987.hostingersite.com/espace-marchand/`. Le compte configuré dans WordPress est `keleva.recette`. Son mot de passe temporaire a été réinitialisé dans la page **Réglages → Accès marchand Keleva** et n’est pas enregistré dans ce dépôt. Un contrôle HTTP indépendant confirme : redirection de connexion réussie, cookie `keleva_native_portal_session` émis, réponse 200 authentifiée et présence des modules **Produits**, **Stock**, **Commandes**, **Apparence** et **Dernières actions**.

Le portail est rendu directement par WordPress, sans proxy, sans URL Manus et sans réutilisation de la session `wp-admin`. Les permissions métier existantes ont été conservées. Aucune modification de produit, de commande ou de palette n’a été effectuée pendant cette passe.

## Limites honnêtes

La recette confirme le périmètre staging contrôlé ; elle ne constitue pas une homologation de paiement réel, WhatsApp/n8n, livraison ou production. Le compte de recette doit être remplacé ou désactivé avant toute mise en production. Les traductions incrustées dans les images restent des contenus graphiques et ne sont pas filtrables par TranslatePress.
