# Conception — Console marketplace Keleva accessible

## Objectif de parcours

La console doit pouvoir être utilisée sans connaissance préalable de WordPress, WooCommerce, des produits variables ou de la gestion de catalogue. Le point de départ n’est donc plus un formulaire, mais une question métier : **« Que voulez-vous faire ? »**. Chaque action présente le résultat attendu, puis n’affiche que les informations nécessaires à l’étape en cours.

| Ancien point de friction | Décision de conception | Résultat attendu |
|---|---|---|
| Bouton « nouveau produit » ouvrant directement plusieurs champs | Assistant en trois étapes : type, informations, vérification | Ajout rassurant et mémorisable |
| Aucun import de photo | Endpoint sécurisé et zone d’ajout de photo | Produit réellement présentable dès sa création |
| Vocabulaire WooCommerce | Libellés de langage courant : « plusieurs versions », « petits choix clients », « mettre en ligne » | Utilisation sans jargon technique |
| Variantes créées manuellement une par une | Modèles Taille/Couleur et génération automatique des combinaisons | Réduction d’erreurs et de saisie |
| Publication noyée dans les champs | Zone dédiée expliquant la conséquence avant confirmation | Publication volontaire et compréhensible |

## Parcours d’ajout retenu

> **Étape 1 — Le type.** La personne choisit entre un produit unique et un produit avec tailles ou couleurs.

> **Étape 2 — Les informations.** Le nom, le prix, le stock et la catégorie sont demandés avec un vocabulaire concret. La photo est proposée sans bloquer la création.

> **Étape 3 — La vérification.** Un résumé rappelle que le produit est créé en brouillon. Les tailles, couleurs et choix supplémentaires sont ensuite proposés uniquement si nécessaire.

## Recette Hostinger en cours

La préproduction confirme que la route sécurisée `POST /wp-json/keleva-dashboard/v1/products/{id}/image` est active : sans fichier, elle répond volontairement `422` avec le message « Choisissez une photo valide ». Elle accepte les fichiers JPG, PNG, WebP et AVIF jusqu’à 5 Mo ; l’image importée devient l’image principale du produit et l’événement est journalisé.

L’assistant a créé le **produit brouillon ID 106**, « Brouillon Marketplace — Validation », dans la catégorie Tests Keleva, à partir du parcours en trois étapes. Le produit est bien converti en variable sans être publié. Les modèles « Ajouter les tailles » et « Ajouter les couleurs » ont généré six combinaisons Petit/Moyen/Grand × Ivoire/Sienne dans l’interface. Ce brouillon documente la première recette ; il a révélé puis permis de corriger le préremplissage du prix pour les nouveaux produits à variantes.

La modification sans jargon est également validée sur ce même brouillon : la description courte « Produit de démonstration créé via l’assistant marketplace. » a été enregistrée depuis la fiche produit, avec le retour explicite « Vos changements ont été enregistrés. ». Le brouillon reste privé ; aucune publication n’a été déclenchée durant cette recette.

Après correctif, une seconde recette a créé le **brouillon ID 109**, « Brouillon Marketplace — Prix prérempli ». Dans le parcours guidé, le marchand a choisi « plusieurs versions », saisi un prix de départ de **41 €** et appliqué le modèle « Ajouter les tailles ». La génération a créé trois versions Petit/Moyen/Grand avec le prix **41 €** sur chacune et un stock de **0** par version, ce qui évite de multiplier le stock global de départ. La sauvegarde a été confirmée par l’API : le produit est toujours `draft`, de type `variable`, avec exactement ces trois variantes. Aucune publication n’a été effectuée.
