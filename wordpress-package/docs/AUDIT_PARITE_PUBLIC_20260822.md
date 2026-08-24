# Audit public Velora — préproduction Hostinger

**Source auditée :** `https://darkblue-spoonbill-498612.hostingersite.com/` le 22 août 2026.

## Fiche produit — Vase Forme 02

La fiche publique conserve une composition éditoriale cohérente avec Velora : retour « La sélection », image produit cadrée dans une large surface ivoire, titre typographique, prix, statut de disponibilité et panneau d’achat structuré. Les options administrables sont nombreuses mais restent fonctionnelles ; les variantes Taille/Couleur, le prix de variation, les groupes d’options et le CTA sont réunis dans le même panneau.

Les écarts à surveiller pour une parité absolument stricte sont surtout la hauteur du panneau d’options sur desktop et le volume textuel des cinq groupes de démonstration. Ces éléments résultent du cas de test volontairement exhaustif ; ils ne traduisent pas un défaut de navigation.

## Panier vide et rail panier rempli

Le panier vide donne une sortie claire vers le catalogue avec le titre « Votre sélection ». Le rail panier du catalogue a été contrôlé avec le **Mug Nomade Sienna** temporairement ajouté : compteur `01`, miniature, quantité, total de **34,00 €** et CTA vers le panier sont cohérents avec la grammaire Velora. Le produit de test doit être retiré une fois la vérification checkout terminée.

## Parcours de suite

Le checkout doit être inspecté sans soumettre de commande ni paiement, puis le produit de test doit être retiré. Les constats doivent ensuite être rapprochés de la référence Velora React avant toute dernière correction de parité.

## Checkout de préproduction

Le checkout publie une entrée claire (« Une étape à la fois » / « Finaliser simplement »), présente les coordonnées à gauche et un récapitulatif de commande à droite. La transition depuis le rail panier et le lien « Voir le panier » reste explicite. Avec le Mug Nomade Sienna, le récapitulatif affiche correctement **34,00 €**.

La préproduction ne contient volontairement aucun moyen de paiement : WooCommerce affiche donc le message informatif correspondant et la commande n’a pas été soumise. C’est un prérequis de mise en production, non un écart de design. Aucun champ n’a été rempli et aucune donnée personnelle n’a été saisie pendant l’audit.

## Conclusion de parité

Les fondations de navigation, la palette, la typographie, les surfaces ivoire et l’ordre de lecture sont cohérents avec Velora sur les parcours contrôlés. Les seules réserves sont fonctionnelles ou liées au scénario de démonstration : activation d’un prestataire de paiement pour une vraie production, et réduction éventuelle des groupes d’options sur les produits courants afin de préserver la concision éditoriale. Aucun écart bloquant de structure ou de navigation n’a été relevé.

## Décision de correction

Aucune modification supplémentaire du thème n’est appliquée à l’issue de cet audit. Cette décision évite de dégrader le quick view 0.3.5, les parcours de variantes et les templates déjà validés ; les deux recommandations restantes sont des réglages de catalogue et de production à activer au moment du lancement commercial.
