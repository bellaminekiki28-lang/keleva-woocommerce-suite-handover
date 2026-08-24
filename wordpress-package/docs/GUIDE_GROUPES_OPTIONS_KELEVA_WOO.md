# Guide marchand — Groupes d’options Keleva Woo

## Où configurer les choix d’un produit

Dans WordPress, ouvrez **Produits → Tous les produits**, puis éditez le produit concerné. La métabox **« Keleva — groupes d’options »** permet de créer les choix complémentaires qui apparaîtront à la fois dans la fiche produit Velora et dans son quick view.

Chaque groupe possède un intitulé, un mode d’affichage, une limite de sélection et une liste d’options. Les changements sont enregistrés avec le bouton WordPress **« Mettre à jour »** du produit.

| Réglage | Rôle dans la boutique | Exemple d’usage |
| --- | --- | --- |
| **Boutons** | Choix unique compact et visuel | Finition : Naturelle, Émail satin, Texture minérale |
| **Radio** | Choix unique explicite | Signature atelier : Sans signature ou Monogramme |
| **Cases à cocher** | Choix multiples encadrés | Emballage, carte de composition, assurance transport |
| **Limite 1** | Une seule option peut être retenue | Couleur ou finition |
| **Limite 2 à 4** | Plusieurs options peuvent être combinées | Extras, services, accessoires ou emballages |

## Ajouter un groupe d’options

Cliquez sur **« Ajouter un groupe d’options »**, puis renseignez son libellé et son identifiant. L’identifiant doit rester court, unique et sans espace, par exemple `finition`, `emballage` ou `extras-livraison`.

Sélectionnez ensuite le rendu souhaité. Pour les boutons et les radios, Keleva impose une sélection unique. Pour les cases à cocher, fixez la limite à **2, 3 ou 4** selon la règle commerciale du produit. Ajoutez ensuite les options, leur libellé public et leur supplément éventuel en euros.

> Une option à `0 €` reste affichée comme incluse. Un supplément est ajouté au prix de la variante sélectionnée, puis apparaît de manière détaillée dans le panier.

## Règles appliquées automatiquement

Le storefront désactive les options supplémentaires lorsque le plafond est atteint et affiche le nombre de choix sélectionnés. La même règle est contrôlée côté WooCommerce au moment de l’ajout au panier : contourner l’interface ne permet donc pas de dépasser la limite.

Lorsqu’un produit est variable, le client choisit d’abord ses attributs WooCommerce — par exemple Taille et Couleur — puis ses groupes d’options. Le prix de la variation et les suppléments s’additionnent dans le panier.

| Élément contrôlé | Fiche produit | Quick view | Panier WooCommerce |
| --- | --- | --- | --- |
| Sélection unique | Oui | Oui | Oui, contrôlée côté serveur |
| Limite de 2 à 4 cases | Oui | Oui | Oui, contrôlée côté serveur |
| Supplément par option | Oui | Oui | Oui, détaillé par groupe |
| Compatibilité produit variable | Oui | Oui | Oui |

## Exemple actuellement configuré

Le produit de démonstration **Vase Forme 02** contient une finition obligatoire en boutons, une signature en radio, puis trois groupes de cases à cocher limités respectivement à deux, trois et quatre choix. La combinaison testée **XL / Sienne** avec les suppléments sélectionnés est facturée **121 €** : 89 € pour la variation et 32 € pour les options.

## Bonnes pratiques

Utilisez des libellés courts et explicites. Réservez les cases à cocher aux compléments réellement cumulables, et gardez un seul groupe obligatoire lorsque le produit exige une décision de configuration. Après toute modification, testez une sélection complète dans le quick view et une autre depuis la fiche produit avant de communiquer le produit à vos clients.
