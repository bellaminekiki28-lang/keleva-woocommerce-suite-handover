# Fixtures publiques de démonstration Keleva

Ce fichier décrit uniquement des données de démonstration non personnelles destinées à la recette locale ou staging. Il ne s’agit pas d’un export SQL et il ne doit pas être importé automatiquement en production.

| Fixture | Catégorie | Description | Valeur de recette |
|---|---|---|---|
| Avocado toast | Brunch & café | Produit variable de démonstration | Option Pesto ; panier AJAX et checkout sans paiement |
| Pancakes fleur d’oranger | Brunch & café | Produit de démonstration pâtisserie | Sauces miel épicé et caramel salé |
| Œufs shakshuka | Brunch & café | Produit de démonstration brunch | Options Pesto et Hollandaise |
| Cookie noisette | Pâtisserie & café | Fixture de catalogue | Produit utilisé pour la recherche et la modification de test |
| Tiramisu café | Pâtisserie & café | Fixture de catalogue | Produit utilisé pour la recherche et la modification de test |
| Dorade grillée | Poissons & grillades | Fixture de catalogue | Recherche « Dorade » validée |
| Calamars frits | Poissons & grillades | Fixture de catalogue | Produit utilisé pour la recette de stock |

## Groupe d’options de démonstration

| Option | Supplément | Usage |
|---|---:|---|
| Fromage supplémentaire | +6 MAD | Supplément payant |
| Avocat | +8 MAD | Supplément payant |
| Sauce piquante | Inclus | Supplément gratuit |

## Variante de démonstration

Le scénario de variante utilise une taille **Petit** à 45 MAD avec un stock de 3 et une taille **Grand** à 60 MAD avec un stock de 0 et indisponible. Une mutation de recette a déjà été vérifiée sur Petit à 46 MAD avec un stock de 2, puis les éléments de recette ont été nettoyés du staging.

## Accès de démonstration

Le compte de recette public est `keleva.recette`. Le mot de passe n’est volontairement pas publié. Le prochain développeur doit le régénérer depuis l’administration sécurisée du staging ou le recevoir par un canal privé avant la recette. Aucun cookie, hash, token, email, commande ou donnée client n’est inclus ici.

## Scénarios autorisés

Les scénarios publics couvrent l’affichage FR/AR, le RTL, la recherche par nom, l’ajout et la modification d’un produit de test, les catégories, les options, les variantes, le panier et le checkout sans paiement. Toute création doit être marquée comme fixture de test et supprimée ou restaurée après la recette.

> Ne pas utiliser ce manifeste pour créer des commandes réelles, activer un paiement réel, modifier le stock de production ou remplacer la base de production.
