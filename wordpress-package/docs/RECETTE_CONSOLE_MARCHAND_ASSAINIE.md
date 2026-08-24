# Recette de validation — Console marchand Keleva

Cette recette permet de vérifier l’installation de la console sans utiliser les identifiants ni les données de la préproduction d’origine.

## Préparation

Créez une clé de dashboard propre à l’environnement depuis **WooCommerce → Keleva Dashboard**. Créez ensuite un produit de test non commercial, avec le statut **Brouillon**. N’utilisez jamais un produit de catalogue réel pour cette recette.

## Parcours à vérifier

| Étape | Action | Résultat attendu |
| --- | --- | --- |
| Accès | Ouvrir la page `keleva-merchant` et saisir la clé de l’environnement | Le catalogue et les métriques se chargent |
| Création | Choisir « Ajouter un produit » puis suivre les trois étapes | Un produit reste en brouillon après création |
| Édition | Modifier le nom ou la description courte | Un message confirme l’enregistrement |
| Variantes | Choisir « plusieurs versions », appliquer Taille ou Couleur, puis générer les combinaisons | Chaque combinaison apparaît avec son prix et son stock individuel |
| Options | Ajouter une finition ou un emballage, avec un plafond de choix | Le groupe est sauvegardé et restitué par l’API de configuration |
| Publication | Vérifier le dialogue de confirmation avant toute publication | Aucune mutation ne part sans action explicite de confirmation |
| Réversibilité | Repasser le produit de test en brouillon | Le produit n’est plus visible dans le storefront public |

## Contrôles API

Appelez les routes avec un client HTTP et le header suivant :

```text
X-Keleva-Dashboard-Key: VOTRE_CLE_D_ENVIRONNEMENT
```

Contrôlez notamment `GET /wp-json/keleva-dashboard/v1/summary`, `GET /products/{id}/configuration` et le journal `GET /audit`. Vérifiez que les variantes, groupes d’options et mutations de statut y apparaissent sans exposer la clé dans les réponses.

## Critère de succès

La recette est terminée lorsque le produit de test est à nouveau en brouillon, que l’audit contient les mutations attendues et qu’aucun secret n’est inscrit dans une page publique, une archive ZIP ou le navigateur persistant.
